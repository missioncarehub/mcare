$ErrorActionPreference = 'Stop'
$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..\..\..\..')).Path
Set-Location -LiteralPath $projectRoot

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "  MCARE Pre-Push Health & Security Check  " -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

# 1. Clear view & config cache to eliminate churn
Write-Host "`n[1/5] Clearing compiled view and configuration caches..." -ForegroundColor Yellow
php artisan view:clear
php artisan config:clear

# 2. Restore git-tracked compiled views if any
Write-Host "[2/5] Sanitizing tracked storage framework views..." -ForegroundColor Yellow
$trackedViews = git ls-files storage/framework/views
if ($trackedViews) {
    git restore storage/framework/views
    Write-Host "  -> Storage framework views restored to clean state." -ForegroundColor Green
} else {
    Write-Host "  -> No tracked storage framework views to restore." -ForegroundColor Green
}

# 3. Check for exposed secrets in git diff (excluding compiled public/build assets)
Write-Host "[3/5] Auditing git diff for sensitive credentials..." -ForegroundColor Yellow
$secrets = git diff -- ':!public/build' | Select-String -Pattern "^\+.*(sk_live|sk_test|api_key|password)\s*=\s*['""][^'""]{4,}|^\+.*\btoken\s*=\s*['""][^'""]{4,}"
if ($secrets) {
    Write-Host "WARNING: Potential secrets found in git diff!" -ForegroundColor Red
    $secrets | ForEach-Object { Write-Host "  $_" -ForegroundColor Red }
    throw "Audit failed: Please remove sensitive credentials before committing."
} else {
    Write-Host "  -> No exposed secrets found in diff." -ForegroundColor Green
}

# 4. Check PHP syntax on modified files
Write-Host "[4/5] Running PHP syntax lint (php -l) on modified files..." -ForegroundColor Yellow
$phpFiles = git diff --name-only | Where-Object { $_ -like "*.php" }
$syntaxErrors = 0
foreach ($file in $phpFiles) {
    $result = php -l $file 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-Host "  ERROR in $file : $result" -ForegroundColor Red
        $syntaxErrors++
    }
}
if ($syntaxErrors -gt 0) {
    throw "Syntax validation failed: $syntaxErrors file(s) contain syntax errors."
} else {
    Write-Host "  -> All modified PHP files passed syntax checks." -ForegroundColor Green
}

# 5. Run full test suite
Write-Host "[5/5] Running PHPUnit test suite (php artisan test)..." -ForegroundColor Yellow
php artisan test
if ($LASTEXITCODE -ne 0) {
    throw "Tests failed: All tests must pass before pushing."
}

Write-Host "`n==========================================" -ForegroundColor Green
Write-Host "  ALL PRE-PUSH CHECKS PASSED SUCCESSFULLY " -ForegroundColor Green
Write-Host "  Ready to stage, commit, and push!      " -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green
