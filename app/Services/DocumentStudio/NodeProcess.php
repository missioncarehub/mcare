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
            // Hostinger supplies Node outside the PHP request PATH.
            $hostingerBinary = '/opt/alt/alt-nodejs24/root/usr/bin/node';
            $binary = is_executable($hostingerBinary) ? $hostingerBinary : null;
        }
        $process = new Process([
            $binary ?: 'node',
            '--max-old-space-size='.$memoryMb, ...$arguments,
        ], base_path(), $environment);
        $process->setTimeout($timeout);

        return $process;
    }
}
