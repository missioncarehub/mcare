<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\CompetencyOutcome;
use App\Models\CompetencyUnit;
use App\Models\EnrollmentApplication;
use App\Models\ModuleProgress;
use App\Models\TraineeCompetencyRecord;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use App\Models\User;
use App\Notifications\LmsQuizPublished;
use App\Notifications\TrainerModuleAssignedByAdmin;
use App\Rules\TrainingModuleFileType;
use App\Services\AccountDeletionService;
use App\Services\CompetencyCatalogService;
use App\Services\CompletionEligibilityService;
use App\Services\ModuleSubmoduleService;
use App\Services\RollingModuleReleaseService;
use App\Services\TraineeRosterCsv;
use App\Services\TrainingModuleDeletionService;
use App\Support\CaregivingNcIiCatalog;
use App\Support\TrainingModuleFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminLearningSystemController extends Controller
{
    public function trainees(Request $request): View
    {
        $roster = $this->traineeRosterSelection($request);
        $filters = $roster['form_filters'];

        $query = $this->filteredTrainees($roster['query_filters'], $roster['exclude_graduated'])
            ->with(['batch', 'moduleProgress', 'user']);
        $trainees = $query->paginate(20)->withQueryString();

        // Build one compact dashboard summary per learner from already eager-loaded
        // payment, module, and batch data. Assessment results are deliberately not
        // invented here because the assessment-recording phase is not implemented yet.
        $traineeSummaries = $trainees->getCollection()->mapWithKeys(
            fn (EnrollmentApplication $trainee): array => [$trainee->id => $this->summarizeTrainee($trainee)]
        );

        return view('admin.learning.trainees-lifecycle', [
            'batches' => $this->batches(),
            'filters' => $filters,
            'learningStatuses' => EnrollmentApplication::learningStatuses(),
            'statusCounts' => EnrollmentApplication::query()
                ->where('status', EnrollmentApplication::STATUS_APPROVED)
                ->selectRaw('learning_status, count(*) as aggregate')
                ->groupBy('learning_status')
                ->pluck('aggregate', 'learning_status'),
            'trainees' => $trainees,
            'traineeSummaries' => $traineeSummaries,
            'activeTab' => $roster['tab'],
            'isGraduatedTab' => $roster['tab'] === 'graduated',
        ]);
    }

    public function showTrainee(EnrollmentApplication $enrollmentApplication): View
    {
        abort_unless(
            $enrollmentApplication->status === EnrollmentApplication::STATUS_APPROVED,
            404
        );

        $enrollmentApplication->load(['batch', 'moduleProgress', 'user']);

        return view('admin.learning.trainees-show', [
            'trainee' => $enrollmentApplication,
            'summary' => $this->summarizeTrainee($enrollmentApplication),
            'learningStatuses' => EnrollmentApplication::learningStatuses(),
            'rosterTab' => $enrollmentApplication->learning_status === EnrollmentApplication::LEARNING_GRADUATED
                ? 'graduated'
                : 'current',
        ]);
    }

    public function exportTrainees(Request $request, TraineeRosterCsv $csv): StreamedResponse
    {
        $roster = $this->traineeRosterSelection($request);
        $filters = $roster['query_filters'];
        $trainees = $this->filteredTrainees($filters, $roster['exclude_graduated'])
            ->with(['batch', 'moduleProgress'])
            ->get();
        $scope = filled($filters['batch_id'] ?? null) ? 'batch-'.$filters['batch_id'] : 'all-batches';

        AdminActivityLog::record($request->user(), 'admin.trainee-roster.exported', null, [
            'scope' => $scope,
            'row_count' => $trainees->count(),
        ]);

        return $csv->download($trainees, 'mcare-trainee-roster-'.$scope.'-'.now()->format('Y-m-d').'.csv');
    }

    public function updateTraineeStatus(
        Request $request,
        EnrollmentApplication $enrollmentApplication,
        CompletionEligibilityService $eligibility,
        RollingModuleReleaseService $releases,
    ): RedirectResponse {
        abort_unless(
            $enrollmentApplication->status === EnrollmentApplication::STATUS_APPROVED,
            422,
            'Only approved trainees can have a learning status.'
        );

        $validated = $request->validate([
            'learning_status' => ['required', Rule::in(array_keys(EnrollmentApplication::learningStatuses()))],
            'learning_status_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $previousStatus = $enrollmentApplication->learning_status ?: EnrollmentApplication::LEARNING_ACTIVE;
        $isExpeditedGraduation = false;

        if ($validated['learning_status'] === EnrollmentApplication::LEARNING_GRADUATED) {
            $completion = $eligibility->evaluate($enrollmentApplication->fresh('batch'));
            $isExpeditedGraduation = ! $completion['eligible'];
        }

        DB::transaction(function () use ($enrollmentApplication, $previousStatus, $request, $validated, $isExpeditedGraduation): void {
            if ($validated['learning_status'] === EnrollmentApplication::LEARNING_GRADUATED && $isExpeditedGraduation) {
                // Fulfill and record all standard Caregiving NC II units & outcomes as Competent
                $requiredUnits = CompetencyUnit::query()
                    ->with('outcomes')
                    ->where('program_code', CaregivingNcIiCatalog::PROGRAM_CODE)
                    ->where('is_required', true)
                    ->get();

                foreach ($requiredUnits as $unit) {
                    $compRecord = TraineeCompetencyRecord::query()->firstOrNew([
                        'enrollment_application_id' => $enrollmentApplication->id,
                        'competency_unit_id' => $unit->id,
                    ]);

                    if ($compRecord->status !== TraineeCompetencyRecord::STATUS_COMPETENT) {
                        $compRecord->fill([
                            'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
                            'percentage_score' => null, // Hidden/omitted for offline/direct graduation
                            'tor_grade' => null,
                            'notes' => 'Administrative Graduation (Offline/Onsite Assessment Verified)',
                            'assessed_by_id' => $request->user()->id,
                            'assessed_at' => now(),
                        ])->save();
                    }

                    foreach ($unit->outcomes as $outcome) {
                        $compRecord->outcomeResults()->updateOrCreate(
                            ['competency_outcome_id' => $outcome->id],
                            [
                                'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
                                'assessed_by_id' => $request->user()->id,
                                'assessed_at' => now(),
                            ]
                        );
                    }
                }

                // Mark any assigned module progress as completed with competent rating
                ModuleProgress::query()
                    ->where('enrollment_application_id', $enrollmentApplication->id)
                    ->where('status', '!=', ModuleProgress::STATUS_COMPLETED)
                    ->update([
                        'status' => ModuleProgress::STATUS_COMPLETED,
                        'progress_percent' => 100,
                        'competency_outcome' => ModuleProgress::OUTCOME_COMPETENT,
                        'practical_rating' => ModuleProgress::RATING_COMPETENT,
                        'evaluated_by_id' => $request->user()->id,
                        'evaluated_at' => now(),
                        'completed_at' => now(),
                    ]);
            }

            $enrollmentApplication->update([
                'learning_status' => $validated['learning_status'],
                'learning_status_notes' => filled($validated['learning_status_notes'] ?? null)
                    ? trim($validated['learning_status_notes'])
                    : ($isExpeditedGraduation ? 'Administrative Graduation (Offline/Onsite Course Completion)' : null),
                'learning_status_changed_at' => now(),
                'learning_status_changed_by_id' => $request->user()->id,
                'learning_started_at' => $enrollmentApplication->learning_started_at ?: now(),
            ]);

            $user = User::query()->lockForUpdate()->find($enrollmentApplication->user_id);

            if ($user && $validated['learning_status'] === EnrollmentApplication::LEARNING_GRADUATED) {
                // Graduation unlocks Career Hub on the same trainee account.
                // Normalize old alumni-role records without replacing credentials or history.
                if ($user->role === 'alumni') {
                    $user->update(['role' => 'trainee']);
                }
                $user->notifications()
                    ->where('type', LmsQuizPublished::class)
                    ->delete();
                $user->alumniProfile()->firstOrCreate([], ['is_available_for_duty' => false]);
            } elseif ($user && $previousStatus === EnrollmentApplication::LEARNING_GRADUATED
                && $validated['learning_status'] !== EnrollmentApplication::LEARNING_GRADUATED) {
                $user->alumniProfile()->update([
                    'is_available_for_duty' => false,
                    'availability_updated_at' => now(),
                ]);
            }
        });

        if ($validated['learning_status'] === EnrollmentApplication::LEARNING_ACTIVE
            && $previousStatus !== EnrollmentApplication::LEARNING_ACTIVE) {
            $releases->assignCurrentTo($enrollmentApplication->fresh());
        }

        AdminActivityLog::record($request->user(), 'trainee.learning-status.updated', $enrollmentApplication, [
            'from' => $previousStatus,
            'to' => $validated['learning_status'],
            'notes' => $enrollmentApplication->learning_status_notes,
            'portal_role' => 'trainee',
            'career_hub_unlocked' => $validated['learning_status'] === EnrollmentApplication::LEARNING_GRADUATED,
        ]);

        return redirect()
            ->route('admin.learning.trainees.show', $enrollmentApplication)
            ->with('saved', "{$enrollmentApplication->first_name} {$enrollmentApplication->last_name} is now {$enrollmentApplication->learningStatusLabel()}.");
    }

    public function destroyTrainee(
        Request $request,
        EnrollmentApplication $enrollmentApplication,
        AccountDeletionService $accounts,
    ): RedirectResponse {
        abort_unless(
            $enrollmentApplication->status === EnrollmentApplication::STATUS_APPROVED,
            422,
            'Only approved trainees can be deleted from trainee records.'
        );

        $traineeName = trim($enrollmentApplication->first_name.' '.$enrollmentApplication->last_name);
        $wasHistoricalAlumni = $enrollmentApplication->is_historical_record;
        $user = $enrollmentApplication->user;

        if (! $user) {
            return redirect()
                ->route('admin.learning.trainees')
                ->withErrors([
                    'trainee' => "{$traineeName} has no linked account and cannot be deleted from trainee records.",
                ]);
        }

        try {
            $deleted = $accounts->delete($user, $request->user());
        } catch (ValidationException $exception) {
            return redirect()
                ->route('admin.learning.trainees')
                ->withErrors($exception->errors());
        }

        return redirect()
            ->route('admin.learning.trainees', [
                'tab' => $wasHistoricalAlumni ? 'graduated' : 'current',
            ])
            ->with(
                'saved',
                $wasHistoricalAlumni
                    ? "Verified alumni record for {$traineeName} ({$deleted['email']}) was permanently removed."
                    : "Trainee {$traineeName} ({$deleted['email']}) and related records were permanently removed."
            );
    }

    public function modules(Request $request, TrainingModuleDeletionService $deletion, CompetencyCatalogService $catalog): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'batch_id' => ['nullable', 'integer', 'exists:training_batches,id'],
            'published' => ['nullable', Rule::in(['yes', 'no'])],
            'tab' => ['nullable', 'string', Rule::in(['modules', 'presets', 'units', 'outcomes'])],
            'edit_preset' => ['nullable', 'integer', 'exists:competency_units,id'],
            'edit_unit' => ['nullable', 'integer', 'exists:competency_units,id'],
            'edit_outcome' => ['nullable', 'integer', 'exists:competency_outcomes,id'],
            'unit_id' => ['nullable', 'integer', 'exists:competency_units,id'],
        ]);

        $query = TrainingModule::query()->with(['batch', 'trainer', 'submodules'])->latest('published_at');

        if ($batchId = $filters['batch_id'] ?? null) {
            $query->where('training_batch_id', $batchId);
        }

        if (isset($filters['published'])) {
            $query->where('is_published', $filters['published'] === 'yes');
        }

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $query->where(fn ($builder) => $builder
                ->where('title', 'like', "%{$search}%")
                ->orWhere('module_code', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('trainer', fn ($trainer) => $trainer->where('name', 'like', "%{$search}%")));
        }

        $modules = $query->paginate(15)->appends(collect($filters)->except(['edit_preset', 'edit_unit', 'edit_outcome', 'tab'])->all());
        $moduleImpacts = $modules->getCollection()->mapWithKeys(
            fn (TrainingModule $module): array => [$module->id => $deletion->impact($module)],
        );
        $catalogUnits = $catalog->caregivingUnits();
        $catalogUnits->loadCount(['trainingModules', 'traineeRecords']);
        $editingUnitId = (int) ($filters['edit_unit'] ?? $filters['edit_preset'] ?? 0);
        $editingPreset = $editingUnitId > 0
            ? $catalogUnits->firstWhere('id', $editingUnitId)
            : null;
        $editingOutcomeId = (int) ($filters['edit_outcome'] ?? 0);
        $editingOutcome = $editingOutcomeId > 0
            ? CompetencyOutcome::query()->with('unit')->find($editingOutcomeId)
            : null;
        $outcomeUnitId = (int) ($filters['unit_id'] ?? 0);
        $catalogOutcomes = CompetencyOutcome::query()
            ->with('unit')
            ->withCount(['traineeResults', 'submodules'])
            ->whereHas('unit', fn ($query) => $query->where('program_code', CaregivingNcIiCatalog::PROGRAM_CODE))
            ->when($outcomeUnitId > 0, fn ($query) => $query->where('competency_unit_id', $outcomeUnitId))
            ->orderBy('competency_unit_id')
            ->orderBy('sort_order')
            ->get();
        $requestedTab = $filters['tab'] ?? 'modules';
        if ($requestedTab === 'presets') {
            $requestedTab = 'units';
        }
        $activeTab = $editingOutcome
            ? 'outcomes'
            : (($editingPreset || $requestedTab === 'units')
                ? 'units'
                : ($requestedTab === 'outcomes' ? 'outcomes' : 'modules'));

        return view('admin.learning.modules', [
            'batches' => $this->batches(),
            'trainers' => User::query()->where('role', 'trainer')->orderBy('name')->get(),
            'filters' => $filters,
            'modules' => $modules,
            'moduleImpacts' => $moduleImpacts,
            'catalogUnits' => $catalogUnits,
            'catalogOutcomes' => $catalogOutcomes,
            'trainerCatalogUnits' => $catalog->caregivingUnits(true),
            'editingPreset' => $editingPreset,
            'editingOutcome' => $editingOutcome,
            'activeTab' => $activeTab,
        ]);
    }

    public function storeCatalogUnit(Request $request, CompetencyCatalogService $catalog): RedirectResponse
    {
        $validated = $request->validateWithBag('catalog', $this->catalogUnitRules());
        $unit = $catalog->create([
            ...$validated,
            'outcomes' => $catalog->parseOutcomeLines($validated['outcomes'] ?? ''),
            'is_tor_included' => $request->boolean('is_tor_included'),
            'is_selectable' => $request->boolean('is_selectable'),
        ]);

        AdminActivityLog::record($request->user(), 'learning.catalog.created', $unit, [
            'code' => $unit->code,
            'title' => $unit->title,
        ]);

        return redirect()
            ->route('admin.learning.modules', ['tab' => 'units'])
            ->with('saved', "Competency unit {$unit->code} was saved. Trainers can now choose it when creating a classwork module.");
    }

    public function updateCatalogUnit(
        Request $request,
        CompetencyUnit $competencyUnit,
        CompetencyCatalogService $catalog,
    ): RedirectResponse {
        $validated = $request->validateWithBag('catalog', $this->catalogUnitRules($competencyUnit));
        $unit = $catalog->update($competencyUnit, [
            ...$validated,
            'outcomes' => $catalog->parseOutcomeLines($validated['outcomes'] ?? ''),
            'is_tor_included' => $request->boolean('is_tor_included'),
            'is_selectable' => $request->boolean('is_selectable'),
        ]);

        AdminActivityLog::record($request->user(), 'learning.catalog.updated', $unit, [
            'code' => $unit->code,
            'title' => $unit->title,
        ]);

        return redirect()
            ->route('admin.learning.modules', ['tab' => 'units'])
            ->with('saved', "Competency unit {$unit->code} was updated.");
    }

    public function destroyCatalogUnit(
        Request $request,
        CompetencyUnit $competencyUnit,
        CompetencyCatalogService $catalog,
    ): RedirectResponse {
        try {
            $code = $competencyUnit->code;
            $title = $competencyUnit->title;
            $catalog->deleteUnit($competencyUnit);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('admin.learning.modules', ['tab' => 'units'])
                ->withErrors($exception->errors());
        }

        AdminActivityLog::record($request->user(), 'learning.catalog.deleted', null, [
            'code' => $code,
            'title' => $title,
        ]);

        return redirect()
            ->route('admin.learning.modules', ['tab' => 'units'])
            ->with('saved', "Competency unit {$code} was deleted.");
    }

    public function storeCatalogOutcome(Request $request, CompetencyCatalogService $catalog): RedirectResponse
    {
        $validated = $request->validateWithBag('catalogOutcome', $this->catalogOutcomeRules());
        $outcome = $catalog->createOutcome([
            ...$validated,
            'is_required' => $request->boolean('is_required'),
        ]);

        AdminActivityLog::record($request->user(), 'learning.catalog.outcome.created', $outcome, [
            'unit_id' => $outcome->competency_unit_id,
            'title' => $outcome->title,
        ]);

        return redirect()
            ->route('admin.learning.modules', ['tab' => 'outcomes', 'unit_id' => $outcome->competency_unit_id])
            ->with('saved', "Outcome \"{$outcome->title}\" was added.");
    }

    public function updateCatalogOutcome(
        Request $request,
        CompetencyOutcome $competencyOutcome,
        CompetencyCatalogService $catalog,
    ): RedirectResponse {
        $validated = $request->validateWithBag('catalogOutcome', $this->catalogOutcomeRules());
        $outcome = $catalog->updateOutcome($competencyOutcome, [
            ...$validated,
            'is_required' => $request->boolean('is_required'),
        ]);

        AdminActivityLog::record($request->user(), 'learning.catalog.outcome.updated', $outcome, [
            'unit_id' => $outcome->competency_unit_id,
            'title' => $outcome->title,
        ]);

        return redirect()
            ->route('admin.learning.modules', ['tab' => 'outcomes', 'unit_id' => $outcome->competency_unit_id])
            ->with('saved', "Outcome \"{$outcome->title}\" was updated.");
    }

    public function destroyCatalogOutcome(
        Request $request,
        CompetencyOutcome $competencyOutcome,
        CompetencyCatalogService $catalog,
    ): RedirectResponse {
        $unitId = $competencyOutcome->competency_unit_id;
        $title = $competencyOutcome->title;

        try {
            $catalog->deleteOutcome($competencyOutcome);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('admin.learning.modules', ['tab' => 'outcomes', 'unit_id' => $unitId])
                ->withErrors($exception->errors());
        }

        AdminActivityLog::record($request->user(), 'learning.catalog.outcome.deleted', null, [
            'unit_id' => $unitId,
            'title' => $title,
        ]);

        return redirect()
            ->route('admin.learning.modules', ['tab' => 'outcomes', 'unit_id' => $unitId])
            ->with('saved', "Outcome \"{$title}\" was deleted.");
    }

    /**
     * @return array<string, mixed>
     */
    private function catalogOutcomeRules(): array
    {
        return [
            'competency_unit_id' => ['required', 'integer', 'exists:competency_units,id'],
            'title' => ['required', 'string', 'max:255'],
            'is_required' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function catalogUnitRules(?CompetencyUnit $unit = null): array
    {
        $program = CaregivingNcIiCatalog::PROGRAM_CODE;

        return [
            'category' => ['required', 'string', Rule::in(array_keys(CompetencyUnit::categoryLabels()))],
            'code' => [
                'required',
                'string',
                'max:40',
                Rule::unique('competency_units', 'code')
                    ->where(fn ($query) => $query->where('program_code', $program))
                    ->ignore($unit?->id),
            ],
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('competency_units', 'title')
                    ->where(fn ($query) => $query->where('program_code', $program))
                    ->ignore($unit?->id),
            ],
            'estimated_hours' => ['nullable', 'integer', 'min:1', 'max:500'],
            'outcomes' => ['nullable', 'string', 'max:4000'],
            'is_tor_included' => ['sometimes', 'boolean'],
            'is_selectable' => ['sometimes', 'boolean'],
        ];
    }

    public function storeModule(
        Request $request,
        RollingModuleReleaseService $releases,
        ModuleSubmoduleService $submodules,
    ): RedirectResponse {
        $request->merge([
            'completion_mode' => $request->input('completion_mode', TrainingModule::COMPLETION_ASSESSED),
        ]);

        $validated = $request->validate($this->modulePayloadRules(true), $this->moduleValidationMessages());

        /** @var UploadedFile $file */
        $file = $request->file('module_file');
        $path = null;
        $supplementaryList = [];

        try {
            $path = TrainingModuleFiles::storeLearningFile($file, "training-modules/admin/{$request->user()->id}");

            if ($request->hasFile('supplementary_files')) {
                $supplementaryList = TrainingModuleFiles::storeSupplementaryFiles(
                    $request->file('supplementary_files'),
                    $request->user()->id
                );
            }

            $module = DB::transaction(function () use ($validated, $path, $file, $supplementaryList, $request, $submodules): TrainingModule {
                $module = TrainingModule::create([
                    ...collect($validated)->except(['module_file', 'supplementary_files', 'submodule_titles'])->all(),
                    'release_mode' => ($validated['competency_category'] ?? null) === TrainingModule::CATEGORY_CUSTOM
                        ? TrainingModule::RELEASE_SUPPLEMENTAL
                        : TrainingModule::RELEASE_ROLLING,
                    'file_path' => $path,
                    'original_file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => Storage::disk('local')->size($path) ?: ($file->getSize() ?: 0),
                    'supplementary_files' => $supplementaryList,
                    'is_published' => $request->boolean('is_published'),
                    'published_at' => $request->boolean('is_published') ? now() : null,
                ]);
                $submodules->ensureStructure($module, $validated['submodule_titles'] ?? []);

                return $module;
            });
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            TrainingModuleFiles::deleteSupplementaryFiles($supplementaryList);

            throw $exception;
        }

        AdminActivityLog::record($request->user(), 'admin.module.created', $module, [
            'trainer_id' => $module->trainer_id,
            'batch_id' => $module->training_batch_id,
            'module_code' => $module->module_code,
        ]);

        if ($module->is_published) {
            $releases->activate($module);
        }

        $module->loadMissing(['batch', 'trainer']);
        $module->trainer?->notify(new TrainerModuleAssignedByAdmin($module));

        return $this->redirectToModulesIndex()
            ->with('saved', "Module {$module->title} was added.");
    }

    public function updateModule(
        Request $request,
        TrainingModule $module,
        RollingModuleReleaseService $releases,
        ModuleSubmoduleService $submodules,
    ): RedirectResponse {
        $request->merge([
            'completion_mode' => $request->input('completion_mode', $module->completion_mode ?: TrainingModule::COMPLETION_ASSESSED),
        ]);

        $validated = $request->validateWithBag(
            'moduleUpdate',
            $this->modulePayloadRules(false),
            $this->moduleValidationMessages(),
        );

        $previousTrainerId = $module->trainer_id;
        $wasPublished = $module->is_published;
        $requestedPublished = $request->boolean('is_published');
        $shouldCloseDelivery = in_array($module->delivery_status, [
            TrainingModule::DELIVERY_ACTIVE,
            TrainingModule::DELIVERY_AVAILABLE,
        ], true)
            && $wasPublished
            && ! $requestedPublished;

        if ($module->isSupplemental()
            && ($validated['competency_category'] ?? null) !== TrainingModule::CATEGORY_CUSTOM) {
            throw ValidationException::withMessages([
                'competency_category' => 'A supplemental custom module cannot be converted into the active rolling competency delivery.',
            ])->errorBag('moduleUpdate');
        }

        if ($validated['completion_mode'] === TrainingModule::COMPLETION_MATERIAL_ONLY
            && $module->requiresEvaluation()
            && ($module->quizzes()->exists()
                || $module->progressRecords()
                    ->where(fn ($query) => $query
                        ->whereNotNull('submitted_at')
                        ->orWhereNotNull('evaluated_at'))
                    ->exists())) {
            throw ValidationException::withMessages([
                'completion_mode' => 'An assessed module with classwork or submitted evaluations cannot be converted to learning-material-only.',
            ])->errorBag('moduleUpdate');
        }

        $replacement = $request->file('module_file');
        $replacementPath = null;
        $oldPath = $module->file_path;
        $currentSupplementary = $module->supplementaryList();
        $newSupplementary = [];

        try {
            if ($replacement) {
                $replacementPath = TrainingModuleFiles::storeLearningFile(
                    $replacement,
                    "training-modules/admin/{$request->user()->id}",
                );
            }

            if ($request->hasFile('supplementary_files')) {
                $newSupplementary = TrainingModuleFiles::storeSupplementaryFiles(
                    $request->file('supplementary_files'),
                    $request->user()->id
                );
                $currentSupplementary = array_merge($currentSupplementary, $newSupplementary);
            }

            DB::transaction(function () use (
                $validated,
                $request,
                $module,
                $replacement,
                $replacementPath,
                $currentSupplementary,
                $shouldCloseDelivery,
            ): void {
                $published = $request->boolean('is_published');
                if ($shouldCloseDelivery || $module->delivery_status === TrainingModule::DELIVERY_CLOSED) {
                    $published = true;
                }

                $attributes = [
                    ...collect($validated)->except(['module_file', 'supplementary_files', 'submodule_titles'])->all(),
                    'release_mode' => ($validated['competency_category'] ?? null) === TrainingModule::CATEGORY_CUSTOM
                        ? TrainingModule::RELEASE_SUPPLEMENTAL
                        : TrainingModule::RELEASE_ROLLING,
                    'supplementary_files' => $currentSupplementary,
                    'is_published' => $published,
                    'published_at' => $published ? ($module->published_at ?? now()) : null,
                ];

                if ($replacement && $replacementPath) {
                    $attributes = [
                        ...$attributes,
                        'file_path' => $replacementPath,
                        'original_file_name' => $replacement->getClientOriginalName(),
                        'mime_type' => $replacement->getMimeType(),
                        'file_size' => Storage::disk('local')->size($replacementPath) ?: ($replacement->getSize() ?: 0),
                    ];
                }

                $module->update($attributes);
            });
            $submodules->ensureStructure($module->fresh(), $validated['submodule_titles'] ?? []);
        } catch (\Throwable $exception) {
            if ($replacementPath) {
                Storage::disk('local')->delete($replacementPath);
            }
            TrainingModuleFiles::deleteSupplementaryFiles($newSupplementary);

            throw $exception;
        }

        if ($replacementPath && $oldPath !== $replacementPath) {
            Storage::disk('local')->delete($oldPath);
        }

        AdminActivityLog::record($request->user(), 'admin.module.updated', $module, [
            'trainer_id' => $module->trainer_id,
            'batch_id' => $module->training_batch_id,
            'module_code' => $module->module_code,
            'file_replaced' => (bool) $replacementPath,
        ]);

        if ($shouldCloseDelivery) {
            $releases->close($module);
        } elseif (! $wasPublished && $module->is_published) {
            $releases->activate($module);
        }

        $module->loadMissing(['batch', 'trainer']);
        if ((int) $module->trainer_id !== (int) $previousTrainerId) {
            $module->trainer?->notify(new TrainerModuleAssignedByAdmin($module));
        }

        return $this->redirectToModulesIndex()
            ->with('saved', "Module {$module->title} was updated.");
    }

    private function redirectToModulesIndex(): RedirectResponse
    {
        return redirect()->to($this->modulesIndexUrl());
    }

    private function modulesIndexUrl(): string
    {
        $index = route('admin.learning.modules');
        $previous = url()->previous($index);
        $previousPath = rtrim((string) parse_url($previous, PHP_URL_PATH), '/') ?: '/';
        $indexPath = rtrim((string) parse_url($index, PHP_URL_PATH), '/') ?: '/';

        if ($previousPath !== $indexPath) {
            return $index;
        }

        return $previous;
    }

    /**
     * @return array<string, mixed>
     */
    private function modulePayloadRules(bool $fileRequired): array
    {
        return [
            'trainer_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'trainer')),
            ],
            'training_batch_id' => ['required', 'integer', 'exists:training_batches,id'],
            'module_code' => ['nullable', 'string', 'max:50'],
            'competency_category' => ['nullable', 'string', Rule::in(['core', 'common', 'basic', 'custom'])],
            'submodule_titles' => ['nullable', 'array', 'max:30'],
            'submodule_titles.*' => ['nullable', 'string', 'max:255'],
            'completion_mode' => [
                'required',
                Rule::in([
                    TrainingModule::COMPLETION_ASSESSED,
                    TrainingModule::COMPLETION_MATERIAL_ONLY,
                ]),
            ],
            'title' => ['required', 'string', 'max:160'],
            'topic' => ['nullable', 'string', 'max:120'],
            'estimated_hours' => ['nullable', 'integer', 'min:1', 'max:500'],
            'description' => ['required', 'string', 'max:5000'],
            'module_file' => [
                $fileRequired ? 'required' : 'nullable',
                'file',
                'max:'.TrainingModuleFiles::MAX_UPLOAD_KB,
                new TrainingModuleFileType,
            ],
            'supplementary_files' => [
                'nullable',
                'array',
                'max:'.TrainingModuleFiles::MAX_SUPPLEMENTARY_FILES,
            ],
            'supplementary_files.*' => [
                'nullable',
                'file',
                'max:'.TrainingModuleFiles::MAX_SUPPLEMENTARY_UPLOAD_KB,
                new TrainingModuleFileType,
            ],
            'is_published' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function moduleValidationMessages(): array
    {
        return [
            'module_file.max' => 'Learning materials must not exceed 38MB on the current MCARE server.',
            'module_file.uploaded' => 'The upload did not reach MCARE. Check the server upload limit and try a smaller file.',
        ];
    }

    public function previewModule(Request $request, TrainingModule $module): View
    {
        AdminActivityLog::record($request->user(), 'admin.module.preview.opened', $module, [
            'title' => $module->title,
        ]);

        return view('admin.learning.module-preview', [
            'module' => $module->load(['batch', 'trainer']),
        ]);
    }

    public function moduleContent(Request $request, TrainingModule $module): BinaryFileResponse
    {
        abort_unless(Storage::disk('local')->exists($module->file_path), 404);

        AdminActivityLog::record($request->user(), 'admin.module.content.viewed', $module, [
            'mime_type' => $module->mime_type,
        ]);

        return $this->moduleFileResponse($module, HeaderUtils::DISPOSITION_INLINE);
    }

    public function downloadModule(Request $request, TrainingModule $module): BinaryFileResponse
    {
        abort_unless(Storage::disk('local')->exists($module->file_path), 404);

        AdminActivityLog::record($request->user(), 'admin.module.content.downloaded', $module, [
            'mime_type' => $module->mime_type,
        ]);

        return $this->moduleFileResponse($module, HeaderUtils::DISPOSITION_ATTACHMENT);
    }

    private function moduleFileResponse(TrainingModule $module, string $disposition): BinaryFileResponse
    {
        $filename = basename($module->original_file_name);
        $fallbackFilename = str($filename)->ascii()->replaceMatches('/[^A-Za-z0-9._-]/', '-')->toString();

        return response()->file(Storage::disk('local')->path($module->file_path), [
            'Content-Type' => $module->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => HeaderUtils::makeDisposition($disposition, $filename, $fallbackFilename),
            'Accept-Ranges' => 'bytes',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroyModule(
        Request $request,
        TrainingModule $module,
        TrainingModuleDeletionService $deletion,
    ): RedirectResponse {
        if (strtoupper(trim((string) $request->input('confirmation'))) !== 'DELETE') {
            return redirect()
                ->route('admin.learning.modules')
                ->withErrors(['module' => 'Type DELETE to confirm permanent module deletion.']);
        }

        try {
            $summary = $deletion->delete($module, $request->user());
        } catch (ValidationException $exception) {
            return redirect()
                ->route('admin.learning.modules')
                ->withErrors($exception->errors());
        }

        $counts = $summary['counts'];

        return redirect()
            ->route('admin.learning.modules')
            ->with('saved', "Module {$summary['title']} was permanently deleted, including {$counts['parent_progress_records']} parent progress record(s), {$counts['submodule_progress_records']} submodule progress record(s), {$counts['quizzes']} quiz(zes), and {$counts['quiz_attempts']} attempt(s).");
    }

    public function certificates(Request $request): View
    {
        $filters = $request->validate([
            'batch_id' => ['nullable', 'integer', 'exists:training_batches,id'],
            'schedule' => ['nullable', Rule::in(['AM', 'PM'])],
            'eligibility' => ['nullable', Rule::in(['eligible', 'blocked'])],
        ]);

        $query = EnrollmentApplication::query()
            ->with(['batch', 'user'])
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->latest('reviewed_at');

        if ($batchId = $filters['batch_id'] ?? null) {
            $query->where('training_batch_id', $batchId);
        }

        if ($schedule = $filters['schedule'] ?? null) {
            $query->where('schedule_preference', $schedule);
        }

        if (($filters['eligibility'] ?? null) === 'eligible') {
            $query->where('payment_status', EnrollmentApplication::PAYMENT_PAID);
        } elseif (($filters['eligibility'] ?? null) === 'blocked') {
            $query->where('payment_status', '!=', EnrollmentApplication::PAYMENT_PAID);
        }

        return view('admin.learning.certificates', [
            'batches' => $this->batches(),
            'filters' => $filters,
            'records' => $query->paginate(15)->withQueryString(),
        ]);
    }

    public function alumniJobs(): View
    {
        return view('admin.learning.alumni-jobs', [
            'approvedTrainees' => EnrollmentApplication::query()->where('status', EnrollmentApplication::STATUS_APPROVED)->count(),
            'completedBatches' => TrainingBatch::query()->where('training_ends_at', '<=', now())->count(),
            'alumniAccounts' => User::query()
                ->whereHas('enrollmentApplication', fn ($query) => $query
                    ->where('status', EnrollmentApplication::STATUS_APPROVED)
                    ->where('learning_status', EnrollmentApplication::LEARNING_GRADUATED))
                ->count(),
        ]);
    }

    public function reports(): View
    {
        return view('admin.learning.reports', [
            'batches' => TrainingBatch::query()
                ->withCount([
                    'applications',
                    'applications as am_count' => fn ($query) => $query->where('schedule_preference', 'AM'),
                    'applications as pm_count' => fn ($query) => $query->where('schedule_preference', 'PM'),
                    'applications as approved_count' => fn ($query) => $query->where('status', EnrollmentApplication::STATUS_APPROVED),
                    'applications as paid_count' => fn ($query) => $query->where('payment_status', EnrollmentApplication::PAYMENT_PAID),
                    'modules',
                ])
                ->orderByDesc('year')
                ->orderBy('name')
                ->get(),
        ]);
    }

    private function batches()
    {
        return TrainingBatch::query()
            ->withCount([
                'applications as approved_trainees_count' => fn ($query) => $query
                    ->where('status', EnrollmentApplication::STATUS_APPROVED),
            ])
            ->orderByDesc('year')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array{total_modules: int, completed_modules: int, in_progress_modules: int, progress_percent: int, last_activity: mixed, assessment_ready: bool}
     */
    private function summarizeTrainee(EnrollmentApplication $trainee): array
    {
        // Use the exact same audience query as the trainee portal so an
        // individual, batch, or future global module cannot drift between dashboards.
        $availableModules = TrainingModule::query()
            ->assignedTo($trainee)
            ->orderBy('position')
            ->get();
        $availableModuleIds = $availableModules->pluck('id');
        $progress = $trainee->moduleProgress
            ->whereIn('training_module_id', $availableModuleIds->all());
        $completedModules = $progress
            ->where('status', ModuleProgress::STATUS_COMPLETED)
            ->count();
        $inProgressModules = $progress
            ->where('status', ModuleProgress::STATUS_IN_PROGRESS)
            ->count();
        $totalModules = $availableModules->count();
        $progressPercent = $totalModules > 0
            ? (int) round($availableModules->sum(function (TrainingModule $module) use ($progress) {
                return (int) ($progress->firstWhere('training_module_id', $module->id)?->progress_percent ?? 0);
            }) / $totalModules)
            : 0;
        $lastActivity = $progress
            ->filter(fn (ModuleProgress $record) => $record->last_viewed_at !== null)
            ->sortByDesc('last_viewed_at')
            ->first()?->last_viewed_at;

        return [
            'total_modules' => $totalModules,
            'completed_modules' => $completedModules,
            'in_progress_modules' => $inProgressModules,
            'progress_percent' => $progressPercent,
            'last_activity' => $lastActivity,
            'assessment_ready' => $totalModules > 0 && $completedModules === $totalModules,
        ];
    }

    /**
     * @return array{form_filters: array<string, mixed>, query_filters: array<string, mixed>, tab: string, exclude_graduated: bool}
     */
    private function traineeRosterSelection(Request $request): array
    {
        $validated = $this->validateTraineeFilters($request);
        $requestedTab = $validated['tab'] ?? null;
        unset($validated['tab']);

        $isGraduatedTab = $requestedTab === 'graduated'
            || ($validated['learning_status'] ?? null) === EnrollmentApplication::LEARNING_GRADUATED;

        $queryFilters = $validated;
        $excludeGraduated = false;

        if ($isGraduatedTab) {
            $queryFilters['learning_status'] = EnrollmentApplication::LEARNING_GRADUATED;
        } elseif (! filled($validated['learning_status'] ?? null)) {
            $excludeGraduated = true;
        }

        return [
            'form_filters' => $validated,
            'query_filters' => $queryFilters,
            'tab' => $isGraduatedTab ? 'graduated' : 'current',
            'exclude_graduated' => $excludeGraduated,
        ];
    }

    private function validateTraineeFilters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'batch_id' => ['nullable', 'integer', 'exists:training_batches,id'],
            'schedule' => ['nullable', Rule::in(['AM', 'PM'])],
            'tab' => ['nullable', Rule::in(['current', 'graduated'])],
            'learning_status' => ['nullable', Rule::in(array_keys(EnrollmentApplication::learningStatuses()))],
            'training_state' => ['nullable', Rule::in(['not_started', 'in_progress', 'completed'])],
            'joined_from' => ['nullable', 'date'],
            'joined_to' => ['nullable', 'date', 'after_or_equal:joined_from'],
        ]);
    }

    private function filteredTrainees(array $filters, bool $excludeGraduated = false)
    {
        $query = EnrollmentApplication::query()
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->latest('reviewed_at');

        if ($batchId = $filters['batch_id'] ?? null) {
            $query->where('training_batch_id', $batchId);
        }
        if ($schedule = $filters['schedule'] ?? null) {
            $query->where('schedule_preference', $schedule);
        }
        if ($excludeGraduated) {
            $query->where(function ($builder) {
                $builder->whereNull('learning_status')
                    ->orWhere('learning_status', '!=', EnrollmentApplication::LEARNING_GRADUATED);
            });
        } elseif ($learningStatus = $filters['learning_status'] ?? null) {
            $query->where('learning_status', $learningStatus);
        }
        if ($trainingState = $filters['training_state'] ?? null) {
            $query->whereHas('batch', function ($batchQuery) use ($trainingState) {
                match ($trainingState) {
                    'not_started' => $batchQuery->where(fn ($builder) => $builder->whereNull('training_starts_at')->orWhere('training_starts_at', '>', now())),
                    'in_progress' => $batchQuery->where('training_starts_at', '<=', now())->where(fn ($builder) => $builder->whereNull('training_ends_at')->orWhere('training_ends_at', '>', now())),
                    'completed' => $batchQuery->where('training_ends_at', '<=', now()),
                };
            });
        }
        if ($joinedFrom = $filters['joined_from'] ?? null) {
            $query->whereDate('reviewed_at', '>=', $joinedFrom);
        }
        if ($joinedTo = $filters['joined_to'] ?? null) {
            $query->whereDate('reviewed_at', '<=', $joinedTo);
        }
        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $query->where(fn ($builder) => $builder
                ->where('email', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%"));
        }

        return $query;
    }
}
