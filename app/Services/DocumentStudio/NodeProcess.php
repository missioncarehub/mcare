<?php

namespace App\Services\DocumentStudio;

use Symfony\Component\Process\Process;

class NodeProcess
{
    public static function make(array $arguments, int $timeout = 30, int $memoryMb = 512): Process
    {
        $environment = ['NODE_OPTIONS' => false, 'OPENSSL_CONF' => false, 'OPENSSL_MODULES' => false];
        if (PHP_OS_FAMILY === 'Windows') {
            // Request environment filtering can omit variables needed by Windows crypto.
            $environment['SYSTEMROOT'] = getenv('SystemRoot') ?: 'C:\\Windows';
            $environment['WINDIR'] = getenv('WINDIR') ?: $environment['SYSTEMROOT'];
        }
        $binary = config('official_documents.browsershot.node_binary');
        if (! $binary && PHP_OS_FAMILY !== 'Windows') {
            // Hostinger and shared Linux hosts supply Node outside PHP's default PATH.
            foreach ([
                '/opt/alt/alt-nodejs24/root/usr/bin/node',
                '/opt/alt/alt-nodejs22/root/usr/bin/node',
                '/opt/alt/alt-nodejs20/root/usr/bin/node',
                '/opt/alt/alt-nodejs18/root/usr/bin/node',
                '/usr/local/bin/node',
                '/usr/bin/node',
            ] as $candidate) {
                if (@is_executable($candidate)) {
                    $binary = $candidate;
                    break;
                }
            }
        }
        $process = new Process([
            $binary ?: 'node',
            '--max-old-space-size='.$memoryMb, ...$arguments,
        ], base_path(), $environment);
        $process->setTimeout($timeout);

        return $process;
    }
}
