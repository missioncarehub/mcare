<?php

namespace App\Services;

use App\Models\CompetencyUnit;
use App\Models\EnrollmentApplication;
use App\Models\ModuleProgress;
use App\Models\TraineeCompetencyRecord;
use App\Models\TraineeOutcomeResult;
use App\Models\TrainingModule;
use App\Models\TrainingSubmoduleProgress;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CompetencyRecordUpdater
{
    public function __construct(
        private readonly TorGradeScale $gradeScale,
        private readonly ModuleSubmoduleService $submodules,
        private readonly RollingModuleReleaseService $releases,
    ) {}

    /**
     * Persist one trainee/unit assessment inside the caller's transaction.
     *
     * @param  array{status: string, percentage_score?: mixed, notes?: ?string, outcomes: array<int|string, string>}  $payload
     */
    public function save(
        EnrollmentApplication $application,
        CompetencyUnit $unit,
        array $payload,
        User $assessor,
    ): TraineeCompetencyRecord {
        $record = TraineeCompetencyRecord::query()
            ->where('enrollment_application_id', $application->id)
            ->where('competency_unit_id', $unit->id)
            ->lockForUpdate()
            ->first() ?? new TraineeCompetencyRecord([
                'enrollment_application_id' => $application->id,
                'competency_unit_id' => $unit->id,
            ]);

        if ($record->exists && $record->locked_at) {
            throw ValidationException::withMessages([
                'records' => "{$application->first_name} {$application->last_name}: {$unit->title} is locked because an official document was generated.",
            ]);
        }

        $outcomeStatuses = collect($payload['outcomes']);
        $expectedOutcomeIds = $unit->outcomes->pluck('id')->map(fn ($id) => (string) $id);
        $submittedOutcomeIds = $outcomeStatuses->keys()->map(fn ($id) => (string) $id);

        if ($expectedOutcomeIds->sort()->values()->all()
            !== $submittedOutcomeIds->sort()->values()->all()) {
            throw ValidationException::withMessages([
                'records' => "The submitted outcomes for {$unit->title} are incomplete.",
            ]);
        }

        $score = filled($payload['percentage_score'] ?? null)
            ? (float) $payload['percentage_score']
            : null;

        if ($payload['status'] === TraineeCompetencyRecord::STATUS_NOT_ASSESSED) {
            $score = null;
        }

        if ($payload['status'] === TraineeCompetencyRecord::STATUS_COMPETENT
            && ($score === null || $score < 75
                || $outcomeStatuses->contains(
                    fn ($status) => $status !== TraineeCompetencyRecord::STATUS_COMPETENT
                ))) {
            throw ValidationException::withMessages([
                'records' => "{$unit->title} needs a score of at least 75 and every outcome marked competent.",
            ]);
        }

        if ($payload['status'] === TraineeCompetencyRecord::STATUS_NOT_YET_COMPETENT
            && $expectedOutcomeIds->isNotEmpty()
            && ! $outcomeStatuses->contains(TraineeCompetencyRecord::STATUS_NOT_YET_COMPETENT)) {
            throw ValidationException::withMessages([
                'records' => "{$unit->title} needs at least one achievement outcome marked Not yet competent.",
            ]);
        }

        $assessed = $payload['status'] !== TraineeCompetencyRecord::STATUS_NOT_ASSESSED;
        $attributes = [
            'status' => $payload['status'],
            'percentage_score' => $score,
            'tor_grade' => $payload['status'] === TraineeCompetencyRecord::STATUS_COMPETENT
                ? $this->gradeScale->fromPercentage($score)
                : null,
            'assessed_by_id' => $assessed ? $assessor->id : null,
            'assessed_at' => $assessed ? now() : null,
        ];

        // Bulk updates preserve existing notes unless the trainer supplied a batch note.
        if (array_key_exists('notes', $payload)) {
            $attributes['notes'] = $payload['notes'];
        }

        $record->fill($attributes)->save();

        $outcomeIds = $unit->outcomes->pluck('id')->all();

        // Preload existing outcome results in one query so the upsert loop
        // becomes a set of targeted UPDATEs / INSERTs instead of the previous
        // updateOrCreate() which fired two round-trips per outcome. $record
        // was just persisted above, so it always exists at this point.
        $existingResults = $record->outcomeResults()
            ->whereIn('competency_outcome_id', $outcomeIds)
            ->get()
            ->keyBy('competency_outcome_id');

        foreach ($unit->outcomes as $outcome) {
            $status = $outcomeStatuses->get((string) $outcome->id);
            $isAssessed = $status !== TraineeCompetencyRecord::STATUS_NOT_ASSESSED;
            $resultAttributes = [
                // Manual trainer records are shared unit/outcome records,
                // not attributable to one learning module.
                'training_module_id' => null,
                'status' => $status,
                'assessed_by_id' => $isAssessed ? $assessor->id : null,
                'assessed_at' => $isAssessed ? now() : null,
            ];

            /** @var TraineeOutcomeResult|null $existing */
            $existing = $existingResults->get($outcome->id);

            if ($existing) {
                $existing->fill($resultAttributes)->save();
            } else {
                $record->outcomeResults()->create(array_merge($resultAttributes, [
                    'competency_outcome_id' => $outcome->id,
                ]));
            }
        }

        $this->syncClassworkProgress($application, $unit, $record, $assessor);
        $this->releases->unlockNext($application);

        return $record;
    }

    /**
     * Push competency-board results onto the matching published classwork so
     * the trainee portal does not stay on "Awaiting trainer evaluation".
     *
     * On shared hosting (Hostinger) the trainer save felt sluggish because
     * this method used to issue N per-module SELECT/UPDATE round-trips and
     * always re-ran assignProgress()/ensureStructure() (which is ~15 more
     * queries per module). We now batch-load the parent + child progress
     * rows and only fall back to assignProgress() when a trainee is actually
     * missing submodule rows.
     */
    private function syncClassworkProgress(
        EnrollmentApplication $application,
        CompetencyUnit $unit,
        TraineeCompetencyRecord $record,
        User $assessor,
    ): void {
        $modules = TrainingModule::query()
            ->with('submodules')
            ->where('is_published', true)
            ->where('training_batch_id', $application->training_batch_id)
            ->where(function ($query) use ($unit): void {
                $query->where('competency_unit_id', $unit->id)
                    ->orWhere(function ($codeQuery) use ($unit): void {
                        $codeQuery->where('module_code', $unit->code)
                            ->whereNotNull('module_code')
                            ->where('module_code', '!=', '');
                    });
            })
            ->get();

        if ($modules->isEmpty()) {
            return;
        }

        /** @var Collection<int, ModuleProgress> $parents */
        $parents = ModuleProgress::query()
            ->where('enrollment_application_id', $application->id)
            ->whereIn('training_module_id', $modules->pluck('id'))
            ->where('status', '!=', ModuleProgress::STATUS_LOCKED)
            ->lockForUpdate()
            ->get()
            ->keyBy('training_module_id');

        if ($parents->isEmpty()) {
            return;
        }

        $results = $record->outcomeResults()->get()->keyBy('competency_outcome_id');

        // Bulk-load every submodule progress row this save could touch so the
        // per-submodule loop below never hits the database on its own.
        $submoduleIds = $modules
            ->flatMap(fn (TrainingModule $module) => $module->submodules->pluck('id'))
            ->unique()
            ->values();

        /** @var Collection<int, TrainingSubmoduleProgress> $childProgressBySubmodule */
        $childProgressBySubmodule = $submoduleIds->isEmpty()
            ? collect()
            : TrainingSubmoduleProgress::query()
                ->where('enrollment_application_id', $application->id)
                ->whereIn('training_submodule_id', $submoduleIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('training_submodule_id');

        foreach ($modules as $module) {
            $parent = $parents->get($module->id);

            if (! $parent) {
                continue;
            }

            // Only run the (expensive) defensive structure sync if the trainee
            // is actually missing a submodule progress row for this module.
            // ensureStructure() re-checks every catalog outcome and touches
            // TrainingSubmodule + TrainingSubmoduleProgress rows, so skipping
            // it saves ~15 round-trips per module on every trainer save.
            $missingChildren = $module->submodules->contains(
                fn ($submodule) => ! $childProgressBySubmodule->has($submodule->id),
            );

            if ($missingChildren) {
                $this->submodules->assignProgress($parent);
                $refreshed = TrainingSubmoduleProgress::query()
                    ->where('enrollment_application_id', $application->id)
                    ->whereIn('training_submodule_id', $module->submodules->pluck('id'))
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('training_submodule_id');
                foreach ($refreshed as $submoduleId => $childRow) {
                    $childProgressBySubmodule->put($submoduleId, $childRow);
                }
            }

            foreach ($module->submodules as $submodule) {
                $resultStatus = $submodule->competency_outcome_id
                    ? $results->get($submodule->competency_outcome_id)?->status
                    : null;

                if (! $resultStatus || $resultStatus === TraineeCompetencyRecord::STATUS_NOT_ASSESSED) {
                    if ($submodule->competency_outcome_id) {
                        continue;
                    }

                    $resultStatus = match ($record->status) {
                        TraineeCompetencyRecord::STATUS_COMPETENT => TraineeCompetencyRecord::STATUS_COMPETENT,
                        TraineeCompetencyRecord::STATUS_NOT_YET_COMPETENT => TraineeCompetencyRecord::STATUS_NOT_YET_COMPETENT,
                        default => null,
                    };
                }

                $isCompetent = $resultStatus === TraineeCompetencyRecord::STATUS_COMPETENT;
                $isNyc = $resultStatus === TraineeCompetencyRecord::STATUS_NOT_YET_COMPETENT;

                if (! $isCompetent && ! $isNyc) {
                    continue;
                }

                $child = $childProgressBySubmodule->get($submodule->id);

                if (! $child) {
                    continue;
                }

                $child->fill([
                    'practical_rating' => $isCompetent
                        ? ModuleProgress::RATING_COMPETENT
                        : ModuleProgress::RATING_NOT_YET_COMPETENT,
                    'competency_outcome' => $isCompetent
                        ? ModuleProgress::OUTCOME_COMPETENT
                        : ModuleProgress::OUTCOME_NOT_YET_COMPETENT,
                    'evaluation_remarks' => $record->notes ?: $child->evaluation_remarks,
                    'evaluated_by_id' => $assessor->id,
                    'evaluated_at' => now(),
                    'status' => $isCompetent
                        ? TrainingSubmoduleProgress::STATUS_COMPLETED
                        : TrainingSubmoduleProgress::STATUS_NEEDS_REMEDIATION,
                    'progress_percent' => $isCompetent ? 100 : min((int) ($child->progress_percent ?: 50), 99),
                    'submitted_at' => $isCompetent
                        ? ($child->submitted_at ?: now())
                        : null,
                    'completed_at' => $isCompetent
                        ? ($child->completed_at ?: now())
                        : null,
                ])->save();
            }

            $parent = $this->submodules->recalculateParent($application, $module);

            $hasNyc = $record->status === TraineeCompetencyRecord::STATUS_NOT_YET_COMPETENT
                || $results->contains(
                    fn ($result): bool => $result->status === TraineeCompetencyRecord::STATUS_NOT_YET_COMPETENT
                );

            if ($hasNyc) {
                $this->applyUnitRemediation($parent);
            }
        }
    }

    private function applyUnitRemediation(ModuleProgress $parent): ModuleProgress
    {
        $parent->forceFill([
            'status' => ModuleProgress::STATUS_NEEDS_REMEDIATION,
            'practical_rating' => ModuleProgress::RATING_NOT_YET_COMPETENT,
            'competency_outcome' => ModuleProgress::OUTCOME_NOT_YET_COMPETENT,
            'completed_at' => null,
            'progress_percent' => min((int) $parent->progress_percent, 99),
        ])->save();

        return $parent;
    }
}
