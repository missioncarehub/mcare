<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\ModuleProgress;
use App\Models\Quiz;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use App\Models\TrainingSubmodule;
use App\Models\TrainingSubmoduleProgress;
use App\Rules\TrainingModuleFileType;
use App\Services\LearningPdfWatermark;
use App\Services\ModuleAssessmentService;
use App\Services\ModuleSubmoduleService;
use App\Services\RollingModuleReleaseService;
use App\Support\TrainingModuleFiles;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TrainingModuleController extends Controller
{
    public function previewWatermark(Request $request, LearningPdfWatermark $watermark): BinaryFileResponse
    {
        $request->validate([
            'module_file' => ['required', 'file', 'max:'.TrainingModuleFiles::MAX_UPLOAD_KB, new TrainingModuleFileType],
        ]);

        $lines = $request->user()?->role === 'admin'
            ? ['Enrollment number', 'Student number']
            : ['Enrollment number', 'Trainee name'];

        return $watermark->previewUpload($request->file('module_file'), $lines);
    }

    public function store(
        Request $request,
        RollingModuleReleaseService $releases,
        ModuleSubmoduleService $submodules,
    ): RedirectResponse {
        $validated = $this->validatedPayload($request, true);
        $this->assertCustomAudience($validated);
        [$batchId, $targetTrainee] = $this->resolveAudience($validated);
        $this->assertTrainerBatch($request, $batchId, $targetTrainee);
        $trainer = $request->user();

        /** @var UploadedFile $file */
        $file = $request->file('module_file');
        $path = null;
        $supplementaryList = [];

        try {
            $path = $file->store("training-modules/{$trainer->id}", 'local');
            if ($path === false) {
                throw new \RuntimeException('The primary learning material could not be stored.');
            }

            if ($request->hasFile('supplementary_files')) {
                $supplementaryList = TrainingModuleFiles::storeSupplementaryFiles(
                    $request->file('supplementary_files'),
                    $trainer->id
                );
            }

            $module = DB::transaction(function () use (
                $validated,
                $trainer,
                $file,
                $path,
                $batchId,
                $targetTrainee,
                $supplementaryList,
                $request,
                $submodules,
            ): TrainingModule {
                $published = $request->has('is_published')
                    ? $request->boolean('is_published')
                    : true;

                $module = TrainingModule::create([
                    'trainer_id' => $trainer->id,
                    'training_batch_id' => $batchId,
                    'target_enrollment_application_id' => $targetTrainee?->id,
                    'module_code' => $validated['module_code'] ?? null,
                    'competency_category' => $validated['competency_category'] ?? null,
                    'completion_mode' => $validated['completion_mode'],
                    'release_mode' => ($validated['competency_category'] ?? null) === TrainingModule::CATEGORY_CUSTOM
                        ? TrainingModule::RELEASE_SUPPLEMENTAL
                        : TrainingModule::RELEASE_ROLLING,
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                    'topic' => $validated['topic'] ?? null,
                    'estimated_hours' => $validated['estimated_hours'] ?? null,
                    'available_at' => $validated['available_at'] ?? null,
                    'due_at' => $validated['due_at'] ?? null,
                    'position' => $validated['position'] ?? 0,
                    'file_path' => $path,
                    'original_file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize() ?: 0,
                    'supplementary_files' => $supplementaryList,
                    'is_published' => $published,
                    'published_at' => $published ? now() : null,
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

        AdminActivityLog::record($trainer, 'trainer.module.uploaded', $module, [
            'title' => $module->title,
            'batch_id' => $batchId,
            'audience' => $targetTrainee ? 'trainee' : 'batch',
            'target_trainee_id' => $targetTrainee?->id,
            'published' => $module->is_published,
        ]);

        if ($module->is_published) {
            $releases->activate($module);
        }

        return redirect()
            ->route('trainer.resources')
            ->with('saved', $module->is_published
                ? 'Learning material published.'
                : 'Learning material saved as a draft.');
    }

    public function update(
        Request $request,
        TrainingModule $module,
        RollingModuleReleaseService $releases,
        ModuleSubmoduleService $submodules,
    ): RedirectResponse {
        $this->authorize('update', $module);

        $wasPublished = $module->is_published;
        $requestedPublished = $request->has('is_published')
            ? $request->boolean('is_published')
            : $module->is_published;
        $shouldCloseDelivery = in_array($module->delivery_status, [
            TrainingModule::DELIVERY_ACTIVE,
            TrainingModule::DELIVERY_AVAILABLE,
        ], true)
            && $wasPublished
            && ! $requestedPublished;
        $validated = $this->validatedPayload($request, false, count($module->supplementaryList()));
        $this->assertCustomAudience($validated);
        if ($module->isSupplemental()
            && ($validated['competency_category'] ?? null) !== TrainingModule::CATEGORY_CUSTOM) {
            throw ValidationException::withMessages([
                'competency_category' => 'A supplemental custom module cannot be converted into the active rolling competency delivery.',
            ]);
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
            ]);
        }
        [$batchId, $targetTrainee] = $this->resolveAudience($validated);
        $this->assertTrainerBatch($request, $batchId, $targetTrainee);
        $replacement = $request->file('module_file');
        $replacementPath = null;
        $oldPath = $module->file_path;
        $currentSupplementary = $module->supplementaryList();
        $newSupplementary = [];

        try {
            if ($replacement) {
                $replacementPath = $replacement->store("training-modules/{$request->user()->id}", 'local');
                if ($replacementPath === false) {
                    throw new \RuntimeException('The replacement learning material could not be stored.');
                }
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
                $batchId,
                $targetTrainee,
                $replacement,
                $replacementPath,
                $currentSupplementary,
                $shouldCloseDelivery,
            ): void {
                $published = $request->has('is_published')
                    ? $request->boolean('is_published')
                    : $module->is_published;
                if ($shouldCloseDelivery || $module->delivery_status === TrainingModule::DELIVERY_CLOSED) {
                    // Closing controls future assignment only. Existing recipients
                    // retain access to their historical delivery and evidence.
                    $published = true;
                }
                $attributes = [
                    'training_batch_id' => $batchId,
                    'target_enrollment_application_id' => $targetTrainee?->id,
                    'module_code' => $validated['module_code'] ?? null,
                    'competency_category' => $validated['competency_category'] ?? null,
                    'completion_mode' => $validated['completion_mode'],
                    'release_mode' => ($validated['competency_category'] ?? null) === TrainingModule::CATEGORY_CUSTOM
                        ? TrainingModule::RELEASE_SUPPLEMENTAL
                        : TrainingModule::RELEASE_ROLLING,
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                    'topic' => $validated['topic'] ?? null,
                    'estimated_hours' => $validated['estimated_hours'] ?? null,
                    'available_at' => $validated['available_at'] ?? null,
                    'due_at' => $validated['due_at'] ?? null,
                    'position' => $validated['position'] ?? 0,
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
                        'file_size' => $replacement->getSize() ?: 0,
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

        AdminActivityLog::record($request->user(), 'trainer.module.updated', $module, [
            'title' => $module->title,
            'batch_id' => $module->training_batch_id,
            'published' => $module->is_published,
            'file_replaced' => (bool) $replacementPath,
        ]);

        if ($shouldCloseDelivery) {
            $releases->close($module);
        } elseif (! $wasPublished && $module->is_published) {
            $releases->activate($module);
        }

        if ($request->boolean('_return_to_module')) {
            return redirect()
                ->route('trainer.modules.show', $module)
                ->with('saved', $shouldCloseDelivery
                    ? 'Module closed to future enrollees. Existing assigned trainees keep their access.'
                    : 'Learning material updated.');
        }

        return redirect()
            ->route('trainer.resources')
            ->with('saved', $shouldCloseDelivery
                ? 'Module closed to future enrollees. Existing assigned trainees keep their access.'
                : 'Learning material updated.');
    }

    public function destroy(
        Request $request,
        TrainingModule $module,
    ): RedirectResponse {
        $this->authorize('delete', $module);

        if ($module->progressRecords()->exists()) {
            return back()->withErrors([
                'module' => 'This delivery has trainee assignments or evidence and cannot be deleted. Close it to future enrollees instead.',
            ]);
        }

        $title = $module->title;
        $path = $module->file_path;
        $supplementary = $module->supplementaryList();

        AdminActivityLog::record($request->user(), 'trainer.module.deleted', $module, [
            'title' => $title,
            'batch_id' => $module->training_batch_id,
        ]);

        $module->delete();
        Storage::disk('local')->delete($path);
        TrainingModuleFiles::deleteSupplementaryFiles($supplementary);

        return redirect()
            ->route('trainer.resources')
            ->with('saved', "Learning material {$title} was removed.");
    }

    public function destroySupplementary(
        Request $request,
        TrainingModule $module,
        int $index,
    ): RedirectResponse {
        $this->authorize('update', $module);

        $removed = DB::transaction(function () use ($module, $index): array {
            $lockedModule = TrainingModule::query()->lockForUpdate()->findOrFail($module->id);
            $files = $lockedModule->supplementaryList();
            abort_unless(isset($files[$index]), 404);

            $removedFile = $files[$index];
            array_splice($files, $index, 1);
            $lockedModule->update(['supplementary_files' => $files]);

            return $removedFile;
        }, 3);

        TrainingModuleFiles::deleteSupplementaryFiles([$removed]);

        AdminActivityLog::record($request->user(), 'trainer.module.supplementary.deleted', $module, [
            'filename' => $removed['original_name'] ?? 'supplementary',
        ]);

        return redirect()
            ->route('trainer.modules.show', $module)
            ->with('saved', 'Supplementary attachment removed.');
    }

    public function evaluate(
        Request $request,
        TrainingModule $module,
        RollingModuleReleaseService $releases,
        ModuleAssessmentService $assessments,
        ModuleSubmoduleService $submodules,
    ): RedirectResponse {
        $this->authorize('update', $module);
        $moduleSubmodules = $submodules->ensureStructure($module);

        $validated = $request->validate([
            'training_submodule_id' => [
                'nullable',
                'integer',
                Rule::exists('training_submodules', 'id')->where(
                    fn ($query) => $query->where('training_module_id', $module->id)
                ),
            ],
            'enrollment_application_id' => [
                'required',
                'integer',
                Rule::exists('enrollment_applications', 'id')->where(
                    fn ($query) => $query->where('training_batch_id', $module->training_batch_id)
                ),
            ],
            'practical_rating' => ['nullable', Rule::in(['competent', 'not_yet_competent', 'pending'])],
            'competency_outcome' => ['required', Rule::in(['competent', 'not_yet_competent', 'in_progress'])],
            'evaluation_remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $application = EnrollmentApplication::findOrFail($validated['enrollment_application_id']);
        if (! $module->requiresEvaluation()) {
            throw ValidationException::withMessages([
                'competency_outcome' => 'Learning-material-only modules do not accept competency evaluations.',
            ]);
        }
        if (
            $module->target_enrollment_application_id !== null
            && (int) $module->target_enrollment_application_id !== (int) $application->id
        ) {
            throw ValidationException::withMessages([
                'enrollment_application_id' => 'This private module can only evaluate its assigned trainee.',
            ]);
        }

        $submodule = filled($validated['training_submodule_id'] ?? null)
            ? $moduleSubmodules->firstWhere('id', (int) $validated['training_submodule_id'])
            : ($moduleSubmodules->count() === 1 ? $moduleSubmodules->first() : null);
        if (! $submodule) {
            throw ValidationException::withMessages([
                'training_submodule_id' => 'Choose the competency submodule being evaluated.',
            ]);
        }

        $assignedProgress = ModuleProgress::query()
            ->where('enrollment_application_id', $application->id)
            ->where('training_module_id', $module->id)
            ->where('status', '!=', ModuleProgress::STATUS_LOCKED)
            ->first();

        if (! $assignedProgress) {
            throw ValidationException::withMessages([
                'enrollment_application_id' => 'This trainee was never assigned this delivery. Closed historical modules cannot be graded for late enrollees.',
            ]);
        }

        $submodules->assignProgress($assignedProgress);
        $childProgress = TrainingSubmoduleProgress::query()
            ->where('enrollment_application_id', $application->id)
            ->where('training_submodule_id', $submodule->id)
            ->firstOrFail();

        $assessmentSummary = $assessments->summary($module, $application, $submodule);

        if ($validated['competency_outcome'] === ModuleProgress::OUTCOME_COMPETENT
            && $assessmentSummary['required_count'] > 0
            && ! $assessmentSummary['all_passed']) {
            throw ValidationException::withMessages([
                'competency_outcome' => 'A Competent outcome requires any assigned classwork for this submodule to be passed.',
            ]);
        }

        $progress = DB::transaction(function () use (
            $request,
            $validated,
            $application,
            $module,
            $submodule,
            $assessmentSummary,
            $submodules,
        ): TrainingSubmoduleProgress {
            $progress = TrainingSubmoduleProgress::query()->where([
                'enrollment_application_id' => $application->id,
                'training_submodule_id' => $submodule->id,
            ])->lockForUpdate()->firstOrFail();

            $isCompetent = $validated['competency_outcome'] === ModuleProgress::OUTCOME_COMPETENT;
            $practicalRating = $validated['practical_rating'] ?? $progress->practical_rating;
            if ($isCompetent && in_array($practicalRating, [null, '', ModuleProgress::RATING_PENDING], true)) {
                $practicalRating = ModuleProgress::RATING_COMPETENT;
            }
            $currentProgress = (int) ($progress->progress_percent ?: 50);
            $progress->fill([
                'quiz_score' => $assessmentSummary['average_score'],
                'practical_rating' => $practicalRating,
                'competency_outcome' => $validated['competency_outcome'],
                'evaluation_remarks' => $validated['evaluation_remarks'] ?? null,
                'evaluated_by_id' => $request->user()->id,
                'evaluated_at' => now(),
                'status' => match ($validated['competency_outcome']) {
                    ModuleProgress::OUTCOME_COMPETENT => TrainingSubmoduleProgress::STATUS_COMPLETED,
                    ModuleProgress::OUTCOME_NOT_YET_COMPETENT => TrainingSubmoduleProgress::STATUS_NEEDS_REMEDIATION,
                    default => TrainingSubmoduleProgress::STATUS_IN_PROGRESS,
                },
                'progress_percent' => $isCompetent ? 100 : min($currentProgress, 99),
                'submitted_at' => $isCompetent ? ($progress->submitted_at ?: now()) : null,
                'completed_at' => $isCompetent ? ($progress->completed_at ?: now()) : null,
            ])->save();

            $submodules->syncCompetencyOutcome(
                $application,
                $module,
                $submodule,
                $progress,
                $request->user(),
            );
            $submodules->recalculateParent($application, $module);

            return $progress;
        }, 3);

        $releases->unlockNext($application);

        AdminActivityLog::record($request->user(), 'trainer.module.evaluated', $progress, [
            'module_id' => $module->id,
            'application_id' => $application->id,
            'submodule_id' => $submodule->id,
            'trainee_name' => trim($application->first_name.' '.$application->last_name),
            'outcome' => $validated['competency_outcome'],
        ]);

        return redirect()
            ->to(route('trainer.modules.show', ['module' => $module, 'tab' => 'evaluations']).'#evaluations')
            ->with('saved', "{$submodule->title} evaluation recorded for {$application->first_name} {$application->last_name}.");
    }

    public function storeQuiz(Request $request, TrainingModule $module): RedirectResponse
    {
        $this->authorize('update', $module);

        if (! $module->requiresEvaluation()) {
            throw ValidationException::withMessages([
                'quiz' => 'This is a learning-material-only module. Create an assessed module before adding required classwork.',
            ]);
        }

        $validated = $request->validate([
            'training_submodule_id' => [
                'required',
                'integer',
                Rule::exists('training_submodules', 'id')->where(
                    fn ($query) => $query->where('training_module_id', $module->id)
                ),
            ],
            'title' => ['required', 'string', 'max:160'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:180'],
            'passing_score_percent' => ['nullable', 'numeric', 'min:50', 'max:100'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $quiz = Quiz::create([
            'trainer_id' => $request->user()->id,
            'training_batch_id' => $module->training_batch_id,
            'target_enrollment_application_id' => $module->target_enrollment_application_id,
            'training_module_id' => $module->id,
            'training_submodule_id' => $validated['training_submodule_id'],
            'title' => $validated['title'],
            'instructions' => $validated['instructions'] ?? null,
            'time_limit_minutes' => $validated['time_limit_minutes'] ?? 20,
            'passing_score_percent' => $validated['passing_score_percent'] ?? 75.00,
            // A quiz cannot be released until the trainer adds at least one question.
            'is_published' => false,
            'published_at' => null,
        ]);

        AdminActivityLog::record($request->user(), 'trainer.quiz.created', $quiz, [
            'title' => $quiz->title,
            'module_id' => $module->id,
        ]);

        return redirect()
            ->route('trainer.quizzes.edit', $quiz)
            ->with('saved', 'Module assessment created. Add your questions and choices below.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(
        Request $request,
        bool $fileRequired,
        int $existingSupplementaryCount = 0,
    ): array {
        $activeBatch = TrainingBatch::assignedTo($request->user());
        $request->merge([
            'audience_type' => $request->input('audience_type', 'batch'),
            'training_batch_id' => $request->input('training_batch_id', $activeBatch?->id),
            'completion_mode' => $request->input('completion_mode', TrainingModule::COMPLETION_ASSESSED),
        ]);

        $validated = $request->validate([
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
            'description' => ['required', 'string', 'max:5000'],
            'topic' => ['nullable', 'string', 'max:120'],
            'estimated_hours' => ['nullable', 'integer', 'min:1', 'max:500'],
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
            'audience_type' => ['required', Rule::in(['batch', 'trainee'])],
            'training_batch_id' => ['nullable', 'integer', 'exists:training_batches,id'],
            'target_enrollment_application_id' => [
                'nullable',
                'integer',
                Rule::exists('enrollment_applications', 'id')->where(
                    fn ($query) => $query->where('status', EnrollmentApplication::STATUS_APPROVED)
                ),
            ],
            'available_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
            'position' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'is_published' => ['nullable', 'boolean'],
            '_return_to_module' => ['nullable', 'boolean'],
        ], [
            'module_file.max' => 'Learning materials must not exceed 38MB on the current MCARE server.',
            'module_file.uploaded' => 'The upload did not reach MCARE. Check the server upload limit and try a smaller file.',
        ]);

        $newSupplementaryCount = count($request->file('supplementary_files', []));
        if ($existingSupplementaryCount + $newSupplementaryCount > TrainingModuleFiles::MAX_SUPPLEMENTARY_FILES) {
            throw ValidationException::withMessages([
                'supplementary_files' => 'A module can have at most '.TrainingModuleFiles::MAX_SUPPLEMENTARY_FILES.' supplementary files.',
            ]);
        }

        if (
            filled($validated['available_at'] ?? null)
            && filled($validated['due_at'] ?? null)
            && Carbon::parse($validated['due_at'])->lte(Carbon::parse($validated['available_at']))
        ) {
            throw ValidationException::withMessages([
                'due_at' => 'The due date must be after the material becomes available.',
            ]);
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: int, 1: EnrollmentApplication|null}
     */
    private function resolveAudience(array $validated): array
    {
        $targetTrainee = $validated['audience_type'] === 'trainee'
            ? EnrollmentApplication::query()->find($validated['target_enrollment_application_id'] ?? null)
            : null;
        $batchId = $targetTrainee?->training_batch_id ?? ($validated['training_batch_id'] ?? null);

        if (! $batchId || ($validated['audience_type'] === 'trainee' && ! $targetTrainee)) {
            throw ValidationException::withMessages([
                'audience_type' => 'Choose a batch or an approved trainee before saving.',
            ]);
        }

        return [(int) $batchId, $targetTrainee];
    }

    private function assertTrainerBatch(
        Request $request,
        int $batchId,
        ?EnrollmentApplication $targetTrainee,
    ): void {
        $assignedBatch = TrainingBatch::assignedTo($request->user());

        if (! $assignedBatch || $batchId !== (int) $assignedBatch->id) {
            throw ValidationException::withMessages([
                'training_batch_id' => 'Learning materials can only be published to the trainer\'s assigned batch.',
            ]);
        }

        if ($targetTrainee && (int) $targetTrainee->training_batch_id !== $batchId) {
            throw ValidationException::withMessages([
                'target_enrollment_application_id' => 'The selected trainee is outside the trainer\'s assigned batch.',
            ]);
        }
    }

    /** @param array<string, mixed> $validated */
    private function assertCustomAudience(array $validated): void
    {
        if (($validated['competency_category'] ?? null) === TrainingModule::CATEGORY_CUSTOM
            && ($validated['audience_type'] ?? 'batch') !== 'batch') {
            throw ValidationException::withMessages([
                'audience_type' => 'Custom modules are supplemental resources for everyone in the trainer\'s assigned class.',
            ]);
        }
    }
}
