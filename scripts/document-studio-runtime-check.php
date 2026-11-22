<?php

use App\Services\DocumentStudio\NodeProcess;
use Illuminate\Contracts\Console\Kernel;

// Verify the renderer through the same Laravel process factory used by requests.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
NodeProcess::make([
    base_path('scripts/document-studio-render.mjs'), '--smoke-test',
])->mustRun();
echo "Laravel Document Studio runtime check passed.\n";
