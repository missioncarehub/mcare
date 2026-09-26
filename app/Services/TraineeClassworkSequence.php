<?php

namespace App\Services;

use App\Models\EnrollmentApplication;
use App\Models\ModuleProgress;
use App\Models\TrainingModule;
use Illuminate\Support\Collection;

class TraineeClassworkSequence
{
    /** @var array<int, array{ordered: Collection<int, TrainingModule>, progress: Collection<int, ModuleProgress>}> */
    private array $stateByApplication = [];

    /**
     * Sort key: numbered module codes in numeric order, then uncoded titles, then id.
     *
     * @return array{0: int, 1: string, 2: string, 3: int}
     */
    public function sortKey(TrainingModule $module): array
    {
        $code = trim((string) $module->module_code);
        preg_match_all('/\d+/', $code, $matches);
        $digits = implode('', $matches[0] ?? []);

        return [
            $digits === '' ? 1 : 0,
            $digits === '' ? '' : str_pad($digits, 24, '0', STR_PAD_LEFT),
            $code,
            (int) $module->id,
        ];
    }

    public function sort(Collection $modules): Collection
    {
        return $modules
            ->sortBy(fn (TrainingModule $module): array => $this->sortKey($module))
            ->values();
    }

    /**
     * Order modules for a trainee, pushing deferred (missed) modules after
     * every non-deferred one so late enrollees finish their current path
     * before returning to the modules they missed on approval.
     */
    public function orderedFor(EnrollmentApplication $application, Collection $modules): Collection
    {
        $progress = $this->stateFor($application)['progress'];

        return $modules
            ->sortBy(function (TrainingModule $module) use ($progress): array {
                $record = $progress->get($module->id);
                $deferred = $record && (bool) $record->is_deferred ? 1 : 0;
                $key = $this->sortKey($module);

                return [$deferred, $key[0], $key[1], $key[2], $key[3]];
            })
            ->values();
    }

    public function isDeferred(EnrollmentApplication $application, TrainingModule $module): bool
    {
        return (bool) ($this->stateFor($application)['progress']->get($module->id)?->is_deferred);
    }

    public function canAccess(EnrollmentApplication $application, TrainingModule $module): bool
    {
        if ($application->is_historical_record
            || $application->learning_status === EnrollmentApplication::LEARNING_GRADUATED) {
            return false;
        }

        $progress = $this->progressFor($application, $module);

        if (! $progress) {
            return false;
        }

        if ($progress->isTrainerValidated()) {
            return true;
        }

        $inSequence = $this->stateFor($application)['ordered']
            ->contains(fn (TrainingModule $candidate): bool => (int) $candidate->id === (int) $module->id);

        if (! $inSequence) {
            return false;
        }

        return $this->blockingPredecessor($application, $module) === null;
    }

    public function blockingPredecessor(EnrollmentApplication $application, TrainingModule $module): ?TrainingModule
    {
        if (! $module->locksUntilPreviousFinished()) {
            return null;
        }

        foreach ($this->stateFor($application)['ordered'] as $candidate) {
            if ((int) $candidate->id === (int) $module->id) {
                return null;
            }

            if (! $candidate->requiresEvaluation()) {
                continue;
            }

            $progress = $this->stateFor($application)['progress']->get($candidate->id);

            if (! $progress?->isTrainerValidated()) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return Collection<int, array{accessible: bool, blocker: ?TrainingModule}>
     */
    public function accessByModule(EnrollmentApplication $application, Collection $modules): Collection
    {
        return $modules->mapWithKeys(function (TrainingModule $module) use ($application): array {
            $accessible = $this->canAccess($application, $module);

            return [
                $module->id => [
                    'accessible' => $accessible,
                    'blocker' => $accessible ? null : $this->blockingPredecessor($application, $module),
                ],
            ];
        });
    }

    public function lockMessage(EnrollmentApplication $application, TrainingModule $module): string
    {
        $blocker = $this->blockingPredecessor($application, $module);
        $isDeferred = $this->isDeferred($application, $module);

        if (! $blocker) {
            return $isDeferred
                ? 'This missed module will open after you finish the modules your batch is currently on.'
                : 'This module stays locked until the previous module in code order has a trainer grade.';
        }

        $label = filled($blocker->module_code) ? $blocker->module_code : $blocker->title;
        $blockerProgress = $this->stateFor($application)['progress']->get($blocker->id);
        $needsCompetent = $blockerProgress?->needsRemediation();

        if ($isDeferred) {
            return $needsCompetent
                ? "You missed this module earlier. It opens after {$label} is Competent."
                : "You missed this module earlier. It opens after {$label} has a trainer grade.";
        }

        if ($needsCompetent) {
            return "This module stays locked until {$label} is Competent. Not yet competent units require remediation first.";
        }

        return "This module stays locked until {$label} has a trainer grade. Classwork opens in module-code number order.";
    }

    /**
     * Lock or unlock assigned classwork so only the current code in sequence is open.
     *
     * @return Collection<int, ModuleProgress> Newly unlocked progress rows
     */
    public function syncLocks(EnrollmentApplication $application): Collection
    {
        $this->forget($application);

        $state = $this->stateFor($application);
        $newlyUnlocked = collect();
        $blocking = null;

        foreach ($state['ordered'] as $module) {
            $progress = $state['progress']->get($module->id);

            if (! $progress) {
                continue;
            }

            $shouldAccess = $progress->isTrainerValidated()
                || $blocking === null
                || ! $module->locksUntilPreviousFinished();

            if ($shouldAccess) {
                $wasInaccessible = $progress->status === ModuleProgress::STATUS_LOCKED
                    || $progress->unlocked_at === null;

                if ($wasInaccessible) {
                    $progress->forceFill([
                        'status' => $progress->status === ModuleProgress::STATUS_LOCKED
                            ? ModuleProgress::STATUS_NOT_STARTED
                            : $progress->status,
                        'unlocked_at' => $progress->unlocked_at ?: now(),
                        'progress_percent' => $progress->hasRecordedClassworkProgress()
                            ? (int) $progress->progress_percent
                            : 0,
                    ])->save();
                    $newlyUnlocked->push($progress);
                } elseif (
                    $progress->status === ModuleProgress::STATUS_IN_PROGRESS
                    && ! $progress->hasRecordedClassworkProgress()
                    && (int) $progress->progress_percent !== 0
                ) {
                    $progress->forceFill(['progress_percent' => 0])->save();
                }
            } elseif ($this->canLockProgress($progress)) {
                $progress->forceFill([
                    'status' => ModuleProgress::STATUS_LOCKED,
                    'unlocked_at' => null,
                    'progress_percent' => $progress->hasRecordedClassworkProgress()
                        ? (int) $progress->progress_percent
                        : 0,
                ])->save();
            }

            if ($module->requiresEvaluation() && ! $progress->isTrainerValidated()) {
                $blocking = $module;
            }
        }

        $this->forget($application);

        return $newlyUnlocked;
    }

    public function syncAssigned(TrainingModule $module): void
    {
        if (! $module->training_batch_id) {
            return;
        }

        EnrollmentApplication::query()
            ->where('training_batch_id', $module->training_batch_id)
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->whereNull('archived_at')
            ->orderBy('id')
            ->each(function (EnrollmentApplication $application) use ($module): void {
                if ($module->target_enrollment_application_id
                    && (int) $module->target_enrollment_application_id !== (int) $application->id) {
                    return;
                }

                $this->syncLocks($application);
            });
    }

    public function forget(?EnrollmentApplication $application = null): void
    {
        if ($application) {
            unset($this->stateByApplication[$application->id]);

            return;
        }

        $this->stateByApplication = [];
    }

    private function canLockProgress(ModuleProgress $progress): bool
    {
        return ! $progress->isTrainerValidated();
    }

    /**
     * @return array{ordered: Collection<int, TrainingModule>, progress: Collection<int, ModuleProgress>}
     */
    private function stateFor(EnrollmentApplication $application): array
    {
        $id = (int) $application->id;

        if (! isset($this->stateByApplication[$id])) {
            $modules = TrainingModule::query()
                ->assignedTo($application)
                ->get();

            $progress = ModuleProgress::query()
                ->where('enrollment_application_id', $application->id)
                ->get()
                ->keyBy('training_module_id');

            $ordered = $modules
                ->sortBy(function (TrainingModule $module) use ($progress): array {
                    $record = $progress->get($module->id);
                    $deferred = $record && (bool) $record->is_deferred ? 1 : 0;
                    $key = $this->sortKey($module);

                    return [$deferred, $key[0], $key[1], $key[2], $key[3]];
                })
                ->values();

            $this->stateByApplication[$id] = [
                'ordered' => $ordered,
                'progress' => $progress,
            ];
        }

        return $this->stateByApplication[$id];
    }

    private function progressFor(EnrollmentApplication $application, TrainingModule $module): ?ModuleProgress
    {
        return $this->stateFor($application)['progress']->get($module->id)
            ?? ModuleProgress::query()
                ->where('enrollment_application_id', $application->id)
                ->where('training_module_id', $module->id)
                ->first();
    }
}
