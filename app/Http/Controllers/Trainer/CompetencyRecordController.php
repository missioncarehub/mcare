<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\ModuleProgress;
use App\Models\TraineeCompetencyRecord;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use App\Services\CompetencyCatalogService;
use App\Services\CompetencyRecordUpdater;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CompetencyRecordController extends Controller
{
    public function index(Request $request): View
    {
        $assignedBatch = TrainingBatch::assignedTo($request->user());
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'batch_id' => ['nullable', 'integer', 'exists:training_batches,id'],
            'schedule' => ['nullable', Rule::in(['AM', 'PM'])],
        ]);
        $batches = $assignedBatch ? collect([$assignedBatch]) : collect();
        $requestedBatchId = isset($validated['batch_id']) ? (int) $validated['batch_id'] : null;
        $this->assertBatchAccess($request, $requestedBatchId);
        $selectedBatchId = $requestedBatchId ?? $assignedBatch?->id;
        $units = $this->unitsForBatch($selectedBatchId);

        $trainees = collect();
        $traineeLimitReached = false;

        if ($selectedBatchId) {
            $trainees = EnrollmentApplication::query()
                ->with(['batch', 'user', 'competencyRecords.outcomeResults'])
                ->where('status', EnrollmentApplication::STATUS_APPROVED)
                ->where('training_batch_id', $selectedBatchId)
                ->when($validated['schedule'] ?? null, fn ($query, $schedule) => $query
                    ->where('schedule_preference', $schedule))
                ->when(trim((string) ($validated['search'] ?? '')), function ($query, $search) {
                    $query->where(fn ($nested) => $nested
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
                })
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->limit(101)
                ->get();

            $traineeLimitReached = $trainees->count() > 100;
            $trainees = $trainees->take(100)->values();
        }

        $recordsByTrainee = $trainees->mapWithKeys(fn ($trainee) => [
            $trainee->id => $trainee->competencyRecords->keyBy('competency_unit_id'),
        ]);
        $evaluationByTrainee = $this->evaluationStateByTrainee($trainees, $units);
        $unitsByCategory = $this->orderUnitsByProgress(
            $this->catalog()->groupByCategory($units),
            $evaluationByTrainee,
        );
        $requiredUnits = $units->where('is_required', true);
        $competentMarks = $trainees->sum(fn ($trainee) => $trainee->competencyRecords
            ->whereIn('competency_unit_id', $requiredUnits->pluck('id'))
            ->where('status', TraineeCompetencyRecord::STATUS_COMPETENT)
            ->count());
        $possibleMarks = $trainees->count() * $requiredUnits->count();

        return view('trainer.competencies.index', [
            'trainees' => $trainees,
            'traineeLimitReached' => $traineeLimitReached,
            'unitsByCategory' => $unitsByCategory,
            'recordsByTrainee' => $recordsByTrainee,
            'evaluationByTrainee' => $evaluationByTrainee,
            'statuses' => TraineeCompetencyRecord::statuses(),
            'filters' => array_merge($validated, ['batch_id' => $selectedBatchId]),
            'batches' => $batches,
            'selectedBatch' => $batches->firstWhere('id', $selectedBatchId),
            'summary' => [
                'trainees' => $trainees->count(),
                'competent' => $competentMarks,
                'possible' => $possibleMarks,
                'percent' => $possibleMarks > 0
                    ? (int) round(($competentMarks / $possibleMarks) * 100)
                    : 0,
            ],
        ]);
    }

    public function edit(Request $request, EnrollmentApplication $enrollmentApplication): View
    {
        $this->assertApproved($enrollmentApplication);
        $this->assertBatchAccess($request, (int) $enrollmentApplication->training_batch_id);
        $enrollmentApplication->load(['batch', 'user', 'competencyRecords.outcomeResults']);

        $units = $this->unitsForBatch((int) $enrollmentApplication->training_batch_id);
        $evaluableUnitIds = $this->evaluableUnitIdsFor($enrollmentApplication, $units);

        return view('trainer.competencies.edit', [
            'trainee' => $enrollmentApplication,
            'unitsByCategory' => $this->catalog()->groupByCategory($units),
            'recordsByUnit' => $enrollmentApplication->competencyRecords->keyBy('competency_unit_id'),
            'statuses' => TraineeCompetencyRecord::statuses(),
            'evaluableUnitIds' => $evaluableUnitIds,
        ]);
    }

    // Path: app/Http/Controllers/Trainer/CompetencyRecordController.php | Label: Gate evaluation on trainee "Mark as done"
    // A competency unit becomes evaluable for a trainee once at least one of the trainee's
    // assigned modules that maps to that unit has been marked as done (awaiting evaluation)
    // or already trainer-validated (completed / competent). Units whose modules are still
    // locked or in progress stay closed until the trainee submits.
    private function evaluableUnitIdsFor(EnrollmentApplication $application, Collection $units): Collection
    {
        return $this->evaluationStateByTrainee(collect([$application]), $units)
            ->get($application->id, collect())
            ->filter(fn (array $state): bool => $state['evaluable'])
            ->keys()
            ->map(fn ($unitId): int => (int) $unitId)
            ->values();
    }

    /**
     * @return Collection<int, Collection<int, array{evaluable: bool, reason: string}>>
     */
    private function evaluationStateByTrainee(Collection $trainees, Collection $units): Collection
    {
        $states = [];
        foreach ($trainees as $trainee) {
            foreach ($units as $unit) {
                $states[(int) $trainee->id][(int) $unit->id] = [
                    'evaluable' => false,
                    'reason' => 'locked',
                ];
            }
        }

        if ($trainees->isEmpty() || $units->isEmpty()) {
            return collect($states)->map(fn (array $unitStates) => collect($unitStates));
        }

        $modules = TrainingModule::query()
            ->where('is_published', true)
            ->whereIn('competency_unit_id', $units->pluck('id'))
            ->whereIn('training_batch_id', $trainees->pluck('training_batch_id')->unique()->filter())
            ->get(['id', 'competency_unit_id', 'training_batch_id']);

        if ($modules->isEmpty()) {
            return collect($states)->map(fn (array $unitStates) => collect($unitStates));
        }

        $progressRows = ModuleProgress::query()
            ->whereIn('enrollment_application_id', $trainees->pluck('id'))
            ->whereIn('training_module_id', $modules->pluck('id'))
            ->get(['enrollment_application_id', 'training_module_id', 'status']);

        $doneStatuses = [
            ModuleProgress::STATUS_AWAITING_EVALUATION,
            ModuleProgress::STATUS_COMPLETED,
            ModuleProgress::STATUS_NEEDS_REMEDIATION,
        ];
        $openStatuses = [
            ModuleProgress::STATUS_NOT_STARTED,
            ModuleProgress::STATUS_IN_PROGRESS,
        ];
        $modulesById = $modules->keyBy('id');

        foreach ($progressRows as $progress) {
            $module = $modulesById->get($progress->training_module_id);
            if (! $module) {
                continue;
            }

            $traineeId = (int) $progress->enrollment_application_id;
            $unitId = (int) $module->competency_unit_id;
            $current = $states[$traineeId][$unitId] ?? ['evaluable' => false, 'reason' => 'locked'];

            if (in_array($progress->status, $doneStatuses, true)) {
                $states[$traineeId][$unitId] = [
                    'evaluable' => true,
                    'reason' => 'ready',
                ];
                continue;
            }

            if ($current['evaluable']) {
                continue;
            }

            if (in_array($progress->status, $openStatuses, true)) {
                $states[$traineeId][$unitId] = [
                    'evaluable' => false,
                    'reason' => 'in_progress',
                ];
            }
        }

        return collect($states)->map(fn (array $unitStates) => collect($unitStates));
    }

    /**
     * Keep in-progress modules first in each category so locked units do not lead the board.
     *
     * @param  Collection<string, Collection<int, mixed>>  $unitsByCategory
     * @param  Collection<int, Collection<int, array{evaluable: bool, reason: string}>>  $evaluationByTrainee
     * @return Collection<string, Collection<int, mixed>>
     */
    private function orderUnitsByProgress(Collection $unitsByCategory, Collection $evaluationByTrainee): Collection
    {
        $rankFor = function (int $unitId) use ($evaluationByTrainee): int {
            $reasons = $evaluationByTrainee->map(
                fn (Collection $states): string => $states->get($unitId)['reason'] ?? 'locked'
            );

            if ($reasons->contains('in_progress')) {
                return 0;
            }

            if ($reasons->contains('ready')) {
                return 1;
            }

            return 2;
        };

        return $unitsByCategory->map(
            fn (Collection $units) => $units->values()->sortBy(
                fn ($unit, $index) => [$rankFor((int) $unit->id), $index]
            )->values()
        );
    }

    public function chart(Request $request, TrainingBatch $trainingBatch, string $chart): View
    {
        abort_unless(in_array($chart, ['progress', 'achievement'], true), 404);
        $this->assertBatchAccess($request, (int) $trainingBatch->id);

        $validated = $request->validate([
            'schedule' => ['nullable', Rule::in(['AM', 'PM'])],
        ]);
        $units = $this->unitsForBatch((int) $trainingBatch->id);
        $trainees = $trainingBatch->applications()
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->when($validated['schedule'] ?? null, fn ($query, $schedule) => $query
                ->where('schedule_preference', $schedule))
            ->with(['user', 'competencyRecords.outcomeResults'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        // Build a lookup once so the wide chart does not repeatedly scan each trainee's records.
        $recordsByTrainee = $trainees->mapWithKeys(fn ($trainee) => [
            $trainee->id => $trainee->competencyRecords->keyBy('competency_unit_id'),
        ]);

        return view('trainer.competencies.chart', [
            'batch' => $trainingBatch,
            'chart' => $chart,
            'schedule' => $validated['schedule'] ?? null,
            'trainees' => $trainees,
            'unitsByCategory' => $this->catalog()->groupByCategory($units),
            'recordsByTrainee' => $recordsByTrainee,
        ]);
    }

    public function update(
        Request $request,
        EnrollmentApplication $enrollmentApplication,
        CompetencyRecordUpdater $updater,
    ): RedirectResponse {
        $this->assertApproved($enrollmentApplication);
        $this->assertBatchAccess($request, (int) $enrollmentApplication->training_batch_id);
        $statuses = array_keys(TraineeCompetencyRecord::statuses());
        $validated = $request->validate([
            'records' => ['required', 'array'],
            'records.*.unit_id' => ['required', 'integer', 'distinct', 'exists:competency_units,id'],
            'records.*.status' => ['required', Rule::in($statuses)],
            'records.*.percentage_score' => ['nullable', 'numeric', 'between:0,100'],
            'records.*.notes' => ['nullable', 'string', 'max:1000'],
            'records.*.outcomes' => ['required', 'array'],
            'records.*.outcomes.*' => ['required', Rule::in($statuses)],
        ]);

        $deliveredUnits = $this->unitsForBatch((int) $enrollmentApplication->training_batch_id)
            ->keyBy('id');
        $units = collect($validated['records'])
            ->mapWithKeys(function (array $payload) use ($deliveredUnits): array {
                $unitId = (int) $payload['unit_id'];
                $unit = $deliveredUnits->get($unitId);

                return $unit ? [$unitId => $unit] : [];
            });

        if ($units->count() !== count($validated['records'])) {
            throw ValidationException::withMessages([
                'records' => 'One or more competency units are not part of Caregiving NC II.',
            ]);
        }

        // Path: app/Http/Controllers/Trainer/CompetencyRecordController.php | Label: Server-side "Mark as done" gate
        // Refuse evaluations for units where none of the trainee's mapped modules have been marked
        // as done. This mirrors the disabled UI so a hand-crafted POST cannot bypass it.
        $evaluableUnitIds = $this->evaluableUnitIdsFor(
            $enrollmentApplication,
            $deliveredUnits->values(),
        )->all();
        $locked = TraineeCompetencyRecord::query()
            ->where('enrollment_application_id', $enrollmentApplication->id)
            ->whereNotNull('locked_at')
            ->pluck('competency_unit_id')
            ->all();
        $blocked = collect($validated['records'])
            ->reject(function (array $payload) use ($evaluableUnitIds, $locked): bool {
                $unitId = (int) $payload['unit_id'];
                if (in_array($unitId, $evaluableUnitIds, true) || in_array($unitId, $locked, true)) {
                    return true;
                }
                // Allow rows that are still "not_assessed" (skip / draft) to pass through untouched.
                return ($payload['status'] ?? null) === TraineeCompetencyRecord::STATUS_NOT_ASSESSED
                    && blank($payload['percentage_score'] ?? null)
                    && blank($payload['notes'] ?? null);
            });
        if ($blocked->isNotEmpty()) {
            throw ValidationException::withMessages([
                'records' => 'You can only evaluate a competency after the trainee has marked its module as done. Ask them to submit their pending modules first.',
            ]);
        }

        DB::transaction(function () use (
            $request,
            $enrollmentApplication,
            $validated,
            $units,
            $updater,
        ): void {
            foreach ($validated['records'] as $payload) {
                $unit = $units->get((int) $payload['unit_id']);
                $updater->save($enrollmentApplication, $unit, $payload, $request->user());
            }
        });

        AdminActivityLog::record($request->user(), 'trainer.competency-records.updated', $enrollmentApplication, [
            'trainee' => trim("{$enrollmentApplication->first_name} {$enrollmentApplication->last_name}"),
            'units_submitted' => count($validated['records']),
        ]);

        return back()->with('saved', 'Competency record updated. Progress and achievement views now use these results.');
    }

    public function bulkUpdate(Request $request, CompetencyRecordUpdater $updater): RedirectResponse
    {
        $statuses = array_keys(TraineeCompetencyRecord::statuses());
        $validated = $request->validate([
            'batch_id' => ['required', 'integer', 'exists:training_batches,id'],
            'unit_id' => ['required', 'integer', 'exists:competency_units,id'],
            'trainee_ids' => ['required', 'array', 'min:1', 'max:100'],
            'trainee_ids.*' => ['required', 'integer', 'distinct', 'exists:enrollment_applications,id'],
            'status' => ['required', Rule::in($statuses)],
            'percentage_score' => ['nullable', 'numeric', 'between:0,100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $this->assertBatchAccess($request, (int) $validated['batch_id']);

        $unit = $this->unitsForBatch((int) $validated['batch_id'])
            ->firstWhere('id', (int) $validated['unit_id']);

        if (! $unit) {
            throw ValidationException::withMessages([
                'unit_id' => 'The selected competency is not part of Caregiving NC II.',
            ]);
        }

        if ($validated['status'] === TraineeCompetencyRecord::STATUS_COMPETENT
            && (! filled($validated['percentage_score'] ?? null)
                || (float) $validated['percentage_score'] < 75)) {
            throw ValidationException::withMessages([
                'percentage_score' => 'A bulk Competent update needs a shared score from 75 to 100.',
            ]);
        }

        $traineeIds = collect($validated['trainee_ids'])->map(fn ($id) => (int) $id)->values();

        DB::transaction(function () use ($request, $validated, $unit, $traineeIds, $updater): void {
            $trainees = EnrollmentApplication::query()
                ->whereIn('id', $traineeIds)
                ->where('training_batch_id', $validated['batch_id'])
                ->where('status', EnrollmentApplication::STATUS_APPROVED)
                ->lockForUpdate()
                ->get();

            // Fail the whole request before changing a record if a submitted trainee is outside the batch.
            if ($trainees->count() !== $traineeIds->count()) {
                throw ValidationException::withMessages([
                    'trainee_ids' => 'Every selected trainee must be approved and assigned to the selected batch.',
                ]);
            }

            // Path: app/Http/Controllers/Trainer/CompetencyRecordController.php | Label: Bulk update mark-as-done gate
            $unitCollection = collect([$unit]);
            $blockedNames = collect();
            foreach ($trainees as $trainee) {
                $evaluableUnitIds = $this->evaluableUnitIdsFor($trainee, $unitCollection);
                if (! $evaluableUnitIds->contains((int) $unit->id)) {
                    $blockedNames->push(trim("{$trainee->first_name} {$trainee->last_name}") ?: 'Trainee #'.$trainee->id);
                }
            }
            if ($blockedNames->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'trainee_ids' => 'These trainees have not marked the related module as done yet: '.$blockedNames->implode(', '),
                ]);
            }

            $payload = [
                'status' => $validated['status'],
                'percentage_score' => $validated['percentage_score'] ?? null,
                'outcomes' => $unit->outcomes->mapWithKeys(
                    fn ($outcome) => [$outcome->id => $validated['status']]
                )->all(),
            ];

            if (filled($validated['notes'] ?? null)) {
                $payload['notes'] = $validated['notes'];
            }

            foreach ($trainees as $trainee) {
                $updater->save($trainee, $unit, $payload, $request->user());
            }
        });

        AdminActivityLog::record($request->user(), 'trainer.competency-records.bulk-updated', $unit, [
            'batch_id' => (int) $validated['batch_id'],
            'trainee_count' => $traineeIds->count(),
            'status' => $validated['status'],
            'percentage_score' => $validated['percentage_score'] ?? null,
        ]);

        return back()->with(
            'saved',
            "{$traineeIds->count()} trainee records were updated for {$unit->title}."
        );
    }

    private function assertApproved(EnrollmentApplication $application): void
    {
        abort_unless($application->status === EnrollmentApplication::STATUS_APPROVED, 404);
    }

    private function unitsForBatch(?int $batchId)
    {
        return $this->catalog()->unitsDeliveredForBatch($batchId);
    }

    private function catalog(): CompetencyCatalogService
    {
        return app(CompetencyCatalogService::class);
    }

    private function assertBatchAccess(Request $request, ?int $batchId): void
    {
        if ($batchId === null) {
            return;
        }

        $assignedBatch = TrainingBatch::assignedTo($request->user());

        if (! $assignedBatch || ! $batchId || (int) $assignedBatch->id !== $batchId) {
            throw ValidationException::withMessages([
                'batch_id' => 'This trainer can only access competency records for the assigned batch.',
            ]);
        }
    }
}
