<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use App\Models\AdmissionApplication;
use App\Models\PublicSiteSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class UnusedApprovedApplicationPurgeService
{
    public function purgeDue(?Carbon $now = null, bool $dryRun = false): int
    {
        $count = 0;

        $this->dueQuery($now)
            ->orderBy('id')
            ->chunkById(100, function ($applications) use ($dryRun, &$count): void {
                foreach ($applications as $application) {
                    if ($application->enrollment()->exists()) {
                        continue;
                    }

                    if (! $dryRun) {
                        AdminActivityLog::record(null, 'admission.unused-expired', $application, [
                            'application_number' => $application->application_number,
                            'email' => $application->email,
                            'status' => $application->status,
                            'reviewed_at' => optional($application->reviewed_at)?->toIso8601String(),
                        ]);

                        $application->delete();
                    }

                    $count++;
                }
            });

        return $count;
    }

    public function dueQuery(?Carbon $now = null): Builder
    {
        $settings = PublicSiteSetting::current();
        $now = $now ? $now->copy() : now();
        $query = AdmissionApplication::query()
            ->where('status', AdmissionApplication::STATUS_APPROVED)
            ->whereDoesntHave('enrollment');

        $mode = $settings->unusedApprovedExpiryMode();

        if ($mode === PublicSiteSetting::UNUSED_APPROVED_EXPIRY_DAYS) {
            $amount = (int) $settings->unused_approved_expiry_amount;
            if ($amount < 1) {
                return $query->whereRaw('0 = 1');
            }

            return $query->whereRaw('COALESCE(reviewed_at, created_at) <= ?', [
                $now->copy()->subDays($amount),
            ]);
        }

        if ($mode === PublicSiteSetting::UNUSED_APPROVED_EXPIRY_MONTHS) {
            $amount = (int) $settings->unused_approved_expiry_amount;
            if ($amount < 1) {
                return $query->whereRaw('0 = 1');
            }

            return $query->whereRaw('COALESCE(reviewed_at, created_at) <= ?', [
                $now->copy()->subMonthsNoOverflow($amount),
            ]);
        }

        if ($mode === PublicSiteSetting::UNUSED_APPROVED_EXPIRY_DATE) {
            $deadline = $settings->unused_approved_expiry_date;
            if (! $deadline || $now->startOfDay()->lt($deadline->copy()->startOfDay())) {
                return $query->whereRaw('0 = 1');
            }

            return $query->whereRaw('COALESCE(reviewed_at, created_at) <= ?', [
                $deadline->copy()->endOfDay(),
            ]);
        }

        return $query->whereRaw('0 = 1');
    }
}
