<?php

namespace App\Services;

use App\Models\AlumniProfile;
use App\Models\CareerOpportunity;
use App\Models\EnrollmentApplication;
use App\Models\User;
use App\Notifications\CareerOpportunityPublished;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class CareerGraduateNotifier
{
    public function __construct(
        private readonly SemaphoreSmsService $sms,
    ) {}

    public function notifyInApp(CareerOpportunity $opportunity): void
    {
        $alumni = $this->graduates()->get();

        if ($alumni->isNotEmpty()) {
            Notification::send($alumni, new CareerOpportunityPublished($opportunity));
        }
    }

    /** @return array{sent: int, skipped: int, delivered: bool} */
    public function sendDueSms(CareerOpportunity $opportunity): array
    {
        if ($opportunity->sms_sent_at || ! $opportunity->is_published || $opportunity->sms_mode === CareerOpportunity::SMS_NONE) {
            return ['sent' => (int) $opportunity->sms_sent_count, 'skipped' => (int) $opportunity->sms_skipped_count, 'delivered' => (bool) $opportunity->sms_sent_at];
        }

        $graduates = $this->graduates($opportunity->sms_mode === CareerOpportunity::SMS_IMMEDIATE)
            ->with('enrollmentApplication')
            ->get();
        $numbers = [];
        $skipped = 0;

        foreach ($graduates as $graduate) {
            $number = $this->sms->normalizePhilippineNumber(
                $graduate->contact_number ?: $graduate->enrollmentApplication?->contact_number
            );

            if ($number === null) {
                $skipped++;

                continue;
            }

            $numbers[] = $number;
        }

        $numbers = array_values(array_unique($numbers));
        $delivered = false;
        $error = null;

        if ($numbers === []) {
            $delivered = true;
            $error = 'No valid graduate contact numbers were found.';
        } else {
            $scheduledAt = $opportunity->sms_mode === CareerOpportunity::SMS_SCHEDULED && $opportunity->sms_scheduled_at?->isFuture()
                ? $opportunity->sms_scheduled_at->timezone(config('app.timezone'))->format('Y-m-d H:i:s')
                : null;

            $result = $this->sms->send(
                $numbers,
                $opportunity->graduateSmsMessage(),
                $scheduledAt,
                $opportunity->sms_mode === CareerOpportunity::SMS_IMMEDIATE,
            );
            $delivered = $result['sent'];
            $error = $result['error'] ?? null;
        }

        $opportunity->forceFill([
            'sms_sent_at' => $delivered ? now() : null,
            'sms_sent_count' => $delivered ? count($numbers) : 0,
            'sms_skipped_count' => $skipped,
            'sms_last_error' => $delivered && $numbers !== [] ? null : $error,
        ])->save();

        return [
            'sent' => $delivered ? count($numbers) : 0,
            'skipped' => $skipped,
            'delivered' => $delivered,
        ];
    }

    public function dispatchIfDue(CareerOpportunity $opportunity): array
    {
        if (! $opportunity->is_published || $opportunity->sms_sent_at || $opportunity->sms_mode === CareerOpportunity::SMS_NONE) {
            return ['sent' => 0, 'skipped' => 0, 'delivered' => false];
        }

        if (in_array($opportunity->sms_mode, [CareerOpportunity::SMS_IMMEDIATE, CareerOpportunity::SMS_SCHEDULED], true)) {
            return $this->sendDueSms($opportunity);
        }

        return ['sent' => 0, 'skipped' => 0, 'delivered' => false];
    }

    /** @return Collection<int, CareerOpportunity> */
    public function dueOpportunities(): Collection
    {
        return CareerOpportunity::query()
            ->where('is_published', true)
            ->whereNull('sms_sent_at')
            ->whereIn('sms_mode', [CareerOpportunity::SMS_IMMEDIATE, CareerOpportunity::SMS_SCHEDULED])
            ->where(function ($query) {
                $query->whereNull('sms_last_error')
                    ->orWhere('sms_last_error', 'like', '%could not be reached%');
            })
            ->get();
    }

    private function graduates(bool $seniorsOnly = false)
    {
        return User::query()
            ->where(function ($query) {
                $query->where('trainee_status', EnrollmentApplication::LEARNING_GRADUATED)
                    ->orWhereHas('enrollmentApplication', function ($enrollment) {
                        $enrollment
                            ->where('status', EnrollmentApplication::STATUS_APPROVED)
                            ->whereNull('archived_at')
                            ->where('learning_status', EnrollmentApplication::LEARNING_GRADUATED);
                    });
            })
            ->when($seniorsOnly, function ($query) {
                $query->whereHas('alumniProfile', function ($profile) {
                    $profile->where('rank', AlumniProfile::RANK_SENIOR);
                });
            });
    }
}
