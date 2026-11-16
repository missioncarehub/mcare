$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'
$projectRoot = $PSScriptRoot
Set-Location -LiteralPath $projectRoot

$envPath = Join-Path $projectRoot '.env'
if (!(Test-Path -LiteralPath $envPath)) { throw 'The project .env file is missing.' }

function Get-DotEnvValue([string]$key) {
    $line = Get-Content -LiteralPath $envPath |
        Where-Object { $_ -match ('^' + [regex]::Escape($key) + '=') } |
        Select-Object -Last 1
    if (!$line) { return $null }
    return (($line -split '=', 2)[1]).Trim().Trim('"').Trim("'")
}

function Set-DotEnvValue([string]$key, [string]$value) {
    $lines = [System.Collections.Generic.List[string]]::new()
    $lines.AddRange([IO.File]::ReadAllLines($envPath))
    $prefix = $key + '='
    $found = $false

    for ($i = 0; $i -lt $lines.Count; $i++) {
        if ($lines[$i].StartsWith($prefix, [StringComparison]::Ordinal)) {
            $lines[$i] = $prefix + $value
            $found = $true
        }
    }

    if (!$found) { throw "The $key setting is missing from .env." }
    [IO.File]::WriteAllLines($envPath, $lines.ToArray(), [Text.UTF8Encoding]::new($false))
}

function Test-LocalPort([int]$port) {
    return [bool](Test-NetConnection -ComputerName '127.0.0.1' -Port $port -InformationLevel Quiet -WarningAction SilentlyContinue)
}

function Stop-StartedProcess($process, [string]$label) {
    if (!$process) { return }

    try {
        $process.Refresh()
        if ($process.HasExited) { return }

        # Stop the Process object returned by Start-Process; do not look up its PID again.
        $process.Kill()
        if (!$process.WaitForExit(5000)) {
            Write-Warning "Could not stop the $label process (PID $($process.Id))."
        }
    } catch {
        try {
            $process.Refresh()
            if ($process.HasExited) { return }
        } catch { }

        Write-Warning "Could not stop the $label process (PID $($process.Id)): $($_.Exception.Message)"
    }
}

$database = Get-DotEnvValue 'DB_DATABASE'
if ([string]::IsNullOrWhiteSpace($database)) {
    throw 'DB_DATABASE is missing or empty in .env. No tunnel was started.'
}

$dbPort = Get-DotEnvValue 'DB_PORT'
if (!$dbPort) { $dbPort = '3306' }
try { $dbPortNumber = [int]$dbPort } catch { throw "DB_PORT '$dbPort' is not a valid port number." }
if ($dbPortNumber -lt 1 -or $dbPortNumber -gt 65535) { throw "DB_PORT '$dbPort' is outside the valid port range." }

if (!(Test-LocalPort $dbPortNumber)) {
    throw "MySQL is not listening on 127.0.0.1:$dbPortNumber. Start MySQL in XAMPP first."
}

$php = Get-Command php.exe -ErrorAction SilentlyContinue
$ngrok = Get-Command ngrok.exe -ErrorAction SilentlyContinue
if (!$php) { throw 'php.exe is not on PATH. Add C:\xampp\php to PATH or run this script from the configured PHP environment.' }
if (!$ngrok) { throw 'ngrok.exe is not on PATH. Install ngrok and add it to PATH.' }

$reuseExistingServer = $false
if (Test-LocalPort 8000) {
    $isMcareServer = $false
    try {
        $loginPage = Invoke-WebRequest -Uri 'http://127.0.0.1:8000/login' -UseBasicParsing -TimeoutSec 4
        $isMcareServer = $loginPage.StatusCode -eq 200 -and $loginPage.Content -match 'MCARE Training Center'
    } catch {
        $isMcareServer = $false
    }

    if (!$isMcareServer) {
        throw 'Port 8000 is occupied, but the service could not be verified as this MCARE app. Stop that service or free port 8000, then retry.'
    }

    $reuseExistingServer = $true
    Write-Host 'Found the existing MCARE server on port 8000; reusing it.'
}

# A previous run may have left ngrok behind. Offer to stop only the ngrok process
# that currently owns the local inspector port; leave every other process alone.
$listener4040 = Get-NetTCPConnection -LocalPort 4040 -State Listen -ErrorAction SilentlyContinue |
    Select-Object -First 1
if ($listener4040) {
    $existingNgrok = Get-Process -Id $listener4040.OwningProcess -ErrorAction SilentlyContinue

    if (!$existingNgrok) {
        if (Test-LocalPort 4040) {
            throw 'Port 4040 is in use, but its process could not be identified. No process was stopped.'
        }
    } elseif ($existingNgrok.ProcessName -ne 'ngrok') {
        throw "Port 4040 is owned by $($existingNgrok.ProcessName) (PID $($existingNgrok.Id)), not ngrok. No process was stopped."
    } else {
        $answer = Read-Host "An existing ngrok agent (PID $($existingNgrok.Id)) is using port 4040. Stop it and continue? [Y/N]"
        if ($answer -notmatch '^(?i:y|yes)$') { throw 'Cancelled. The existing ngrok agent was left running.' }

        $existingNgrok.Kill()
        if (!$existingNgrok.WaitForExit(5000)) {
            throw "Could not stop the existing ngrok agent (PID $($existingNgrok.Id))."
        }
        Start-Sleep -Milliseconds 300
        if (Test-LocalPort 4040) {
            throw 'Port 4040 is still in use after stopping the old ngrok agent.'
        }
    }
}

$migrationStatus = @(& $php.Source artisan migrate:status --no-ansi --no-interaction 2>&1)
$migrationExitCode = $LASTEXITCODE
if ($migrationExitCode -ne 0) {
    throw "Laravel could not verify the configured '$database' database. Check the .env database settings and ensure its migrations are ready."
}
$pendingMigrationRows = @($migrationStatus | Where-Object { [string]$_ -match '\bPending\s*$' })
if ($pendingMigrationRows.Count -gt 0) {
    throw "The '$database' database has pending migrations. Run php artisan migrate in this folder, then restart this script. No tunnel was started."
}

$runtimeDir = Join-Path $projectRoot 'storage/app/local-runtime-ngrok'
New-Item -ItemType Directory -Path $runtimeDir -Force | Out-Null
$publicUrlFile = Join-Path $runtimeDir 'public-url.txt'
Remove-Item -LiteralPath $publicUrlFile -Force -ErrorAction SilentlyContinue

$oldAppUrl = Get-DotEnvValue 'APP_URL'
$oldSecureCookie = Get-DotEnvValue 'SESSION_SECURE_COOKIE'
$oldAppDebug = Get-DotEnvValue 'APP_DEBUG'

$ngrokProcess = $null
$serverProcess = $null
$workerProcess = $null
$envChanged = $false

try {
    # Explicit IPv4 avoids ngrok resolving localhost to ::1 while artisan serve listens on 127.0.0.1.
    $ngrokProcess = Start-Process -FilePath $ngrok.Source -ArgumentList @('http', 'http://127.0.0.1:8000') -WorkingDirectory $projectRoot -WindowStyle Hidden -PassThru -RedirectStandardOutput (Join-Path $runtimeDir 'ngrok.out.log') -RedirectStandardError (Join-Path $runtimeDir 'ngrok.err.log')

    $deadline = (Get-Date).AddSeconds(30)
    $publicUrl = $null

    do {
        Start-Sleep -Milliseconds 500
        try {
            $tunnels = (Invoke-RestMethod -Uri 'http://127.0.0.1:4040/api/tunnels' -TimeoutSec 2).tunnels
            $tunnel = $tunnels |
                Where-Object { $_.public_url -like 'https://*' } |
                Select-Object -First 1
            if ($tunnel) { $publicUrl = $tunnel.public_url.TrimEnd('/') }
        } catch { }
    } while (!$publicUrl -and (Get-Date) -lt $deadline -and !$ngrokProcess.HasExited)

    if (!$publicUrl) {
        $log = Join-Path $runtimeDir 'ngrok.err.log'
        if (Test-Path -LiteralPath $log) { Get-Content -LiteralPath $log -Tail 12 }
        throw 'ngrok did not provide an HTTPS URL. Check its authtoken and local agent logs.'
    }

    $envChanged = $true
    Set-DotEnvValue 'APP_URL' $publicUrl
    Set-DotEnvValue 'SESSION_SECURE_COOKIE' 'true'
    Set-DotEnvValue 'APP_DEBUG' 'false'

    & $php.Source artisan optimize:clear --no-interaction *> $null
    if ($LASTEXITCODE -ne 0) { throw 'Laravel could not refresh its cached configuration.' }

    if (!$reuseExistingServer) {
        $serverProcess = Start-Process -FilePath $php.Source -ArgumentList @('artisan', 'serve', '--host=127.0.0.1', '--port=8000', '--no-reload') -WorkingDirectory $projectRoot -WindowStyle Hidden -PassThru -RedirectStandardOutput (Join-Path $runtimeDir 'laravel.out.log') -RedirectStandardError (Join-Path $runtimeDir 'laravel.err.log')
    }

    $workerProcess = Start-Process -FilePath $php.Source -ArgumentList @('artisan', 'queue:work', '--queue=mail,default', '--tries=3', '--timeout=600') -WorkingDirectory $projectRoot -WindowStyle Hidden -PassThru -RedirectStandardOutput (Join-Path $runtimeDir 'queue.out.log') -RedirectStandardError (Join-Path $runtimeDir 'queue.err.log')

    $health = $null
    $deadline = (Get-Date).AddSeconds(30)
    do {
        Start-Sleep -Milliseconds 500
        try {
            $health = Invoke-WebRequest -Uri 'http://127.0.0.1:8000/up' -UseBasicParsing -TimeoutSec 3
        } catch {
            $health = $null
        }
    } while ((!$health -or $health.StatusCode -ne 200) -and (Get-Date) -lt $deadline)

    if (!$health -or $health.StatusCode -ne 200) {
        throw 'The local MCARE health endpoint did not return HTTP 200. Check storage/app/local-runtime-ngrok/laravel.err.log.'
    }

    [IO.File]::WriteAllText($publicUrlFile, $publicUrl + [Environment]::NewLine, [Text.UTF8Encoding]::new($false))
    Write-Output ''
    Write-Output '========== COPY MCARE PUBLIC LINK =========='
    Write-Output $publicUrl
    Write-Output ("Temporary link file: {0}" -f $publicUrlFile)
    Write-Output '============================================'
    Write-Output 'MCARE local health check passed at http://127.0.0.1:8000.'
    Write-Output "The active database remains '$database' on local MySQL. Press Ctrl+C here to stop the tunnel and restore local .env settings."
    if ($reuseExistingServer) { Write-Output 'The reused Laravel server will remain running after Ctrl+C.' }

    while (!$ngrokProcess.HasExited) { Start-Sleep -Seconds 1 }
    throw 'The ngrok process exited.'
}
finally {
    if ($ngrokProcess) { Stop-StartedProcess $ngrokProcess 'ngrok' }
    if ($publicUrlFile -and (Test-Path -LiteralPath $publicUrlFile)) { Remove-Item -LiteralPath $publicUrlFile -Force -ErrorAction SilentlyContinue }
    Stop-StartedProcess $workerProcess 'queue worker'
    Stop-StartedProcess $serverProcess 'Laravel server'

    if ($envChanged) {
        Set-DotEnvValue 'APP_URL' $oldAppUrl
        Set-DotEnvValue 'SESSION_SECURE_COOKIE' $oldSecureCookie
        Set-DotEnvValue 'APP_DEBUG' $oldAppDebug
        & $php.Source artisan optimize:clear --no-interaction *> $null
    }
}