<?php

namespace App\Console\Commands;

use App\Services\UnusedApprovedApplicationPurgeService;
use Illuminate\Console\Command;

class PurgeUnusedApprovedApplications extends Command
{
    protected $signature = 'mcare:purge-unused-approved-applications
                            {--dry-run : Count matching applications without deleting them}';

    protected $description = 'Delete approved applications that were never enrolled after the admin expiry setting.';

    public function handle(UnusedApprovedApplicationPurgeService $purge): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $count = $purge->purgeDue(now(), $dryRun);

        $verb = $dryRun ? 'Matched' : 'Deleted';
        $this->info($verb.' '.$count.' unused approved '.str('application')->plural($count).'.');

        return self::SUCCESS;
    }
}
