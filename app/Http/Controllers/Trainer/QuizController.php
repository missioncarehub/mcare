<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use App\Models\User;
use App\Notifications\LmsQuizPublished;
use App\Services\ClassroomComments;
use App\Services\ModuleAssessmentService;
use App\Services\QuizQuestionImporter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;

class QuizController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('trainer.resources');
    }

    public function create(): RedirectResponse
    {
        return redirect()
            ->route('trainer.resources')
            ->with('saved', 'Open a learning module to create its assessment.');
    }

    public function store(Request $request, ModuleAssessmentService $assessments): RedirectResponse
    {
        $validated = $this->validatedPayload($request);
        [$batchId, $targetTrainee] = $this->resolveAudience($validated);
        $this->assertTrainerBatch($request, $batchId, $targetTrainee);
        $parentModule = $this->resolveParentModule($request, $validated, $batchId, $targetTrainee);
        $submodule = $this->resolveSubmodule($parentModule, $validated);
        $importedQuestions = $this->questionsFromUpload($request);
        $questions = $importedQuestions ?? $this->normalizedQuestions($validated['questions']);
        $published = $request->boolean('is_published');

        $quiz = DB::transaction(function () use (
            $request,
            $validated,
            $batchId,
            $targetTrainee,
            $parentModule,
            $submodule,
            $questions,
            $published,
        ): Quiz {
            $quiz = Quiz::create([
                'trainer_id' => $request->user()->id,
                'training_batch_id' => $batchId,
                'target_enrollment_application_id' => $targetTrainee?->id,
                'training_module_id' => $parentModule?->id,
                'training_submodule_id' => $submodule?->id,
                'title' => $validated['title'],
                'instructions' => $validated['instructions'] ?? null,
                'available_at' => $validated['available_at'] ?? null,
                'due_at' => $validated['due_at'] ?? null,
                'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
                'attempt_limit' => $validated['attempt_limit'],
                'passing_score_percent' => $validated['passing_score_percent'],
                'is_published' => $published,
                'published_at' => $published ? now() : null,
            ]);

            $this->replaceQuestions($quiz, $questions);

            return $quiz;
        });

        AdminActivityLog::record($request->user(), 'trainer.quiz.created', $quiz, [
            'title' => $quiz->title,
            'batch_id' => $quiz->training_batch_id,
            'question_count' => count($questions),
            'published' => $quiz->is_published,
        ]);

        if ($quiz->is_published) {
            $assessments->resetPendingProgressForPublishedQuiz($quiz);
            $this->notifyTrainees($quiz);
        }

        if ($importedQuestions !== null) {
            return redirect()
                ->route('trainer.quizzes.edit', $quiz)
                ->with('saved', 'Imported '.count($questions).' '.str('question')->plural(count($questions)).'. Review them, then save.');
        }

        return $this->moduleRedirect($quiz)
            ->with('saved', $quiz->is_published ? 'Quiz published.' : 'Quiz saved as a draft.');
    }

    public function importQuestions(Request $request, QuizQuestionImporter $importer): JsonResponse
    {
        $this->authorize('create', Quiz::class);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:256', 'extensions:txt,text,csv,json'],
        ]);

        $preview = $importer->preview($validated['file']);

        return response()->json([
            'questions' => $preview['questions'],
            'errors' => $preview['errors'],
        ], $preview['errors'] === [] ? 200 : 422);
    }

    public function importTemplate(): Response
    {
        $this->authorize('create', Quiz::class);

        return response(QuizQuestionImporter::sampleText(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="quiz-questions.txt"',
        ]);
    }

    public function edit(
        Request $request,
        Quiz $quiz,
        ClassroomComments $comments,
    ): View {
        $this->authorize('update', $quiz);

        return view($request->routeIs('admin.learning.quizzes.edit') ? 'admin.learning.quiz-edit' : 'trainer.quizzes.edit', [
            'quiz' => $quiz->load(['questions', 'batch', 'targetTrainee', 'trainingModule', 'trainingSubmodule']),
            'classroomComments' => $comments->visibleFor($request->user(), $quiz),
            'privateCommentRecipients' => $comments->privateRecipients($request->user(), $quiz),
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, Quiz $quiz, ModuleAssessmentService $assessments): RedirectResponse
    {
        $this->authorize('update', $quiz);

        $wasPublished = $quiz->is_published;
        $validated = $this->validatedPayload($request);
        [$batchId, $targetTrainee] = $this->resolveAudience($validated);
        $this->assertTrainerBatch($request, $batchId, $targetTrainee);
        $parentModule = $this->resolveParentModule($request, $validated, $batchId, $targetTrainee);
        $submodule = $this->resolveSubmodule($parentModule, $validated, $quiz);
        $questions = $this->normalizedQuestions($validated['questions']);

        DB::transaction(function () use (
            $request,
            $quiz,
            $validated,
            $batchId,
            $targetTrainee,
            $parentModule,
            $submodule,
            $questions,
        ): void {
            $lockedQuiz = Quiz::query()->lockForUpdate()->findOrFail($quiz->id);
            $hasAttempts = $lockedQuiz->attempts()->exists();

            if ($hasAttempts) {
                $this->assertGradingMetadataUnchanged(
                    $lockedQuiz,
                    $validated,
                    $batchId,
                    $targetTrainee,
                    $parentModule,
                    $submodule,
                    $questions,
                );
            }

            $published = $request->has('is_published')
                ? $request->boolean('is_published')
                : $lockedQuiz->is_published;
            $attributes = [
                'title' => $validated['title'],
                'instructions' => $validated['instructions'] ?? null,
                'available_at' => $validated['available_at'] ?? null,
                'due_at' => $validated['due_at'] ?? null,
                'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
                'attempt_limit' => $validated['attempt_limit'],
                'is_published' => $published,
                'published_at' => $published ? ($lockedQuiz->published_at ?? now()) : null,
            ];

            if (! $hasAttempts) {
                $attributes = [
                    ...$attributes,
                    'training_module_id' => $parentModule?->id,
                    'training_submodule_id' => $submodule?->id,
                    'training_batch_id' => $batchId,
                    'target_enrollment_application_id' => $targetTrainee?->id,
                    'passing_score_percent' => $validated['passing_score_percent'],
                ];
            }

            $lockedQuiz->update($attributes);

            if (! $hasAttempts) {
                $this->replaceQuestions($lockedQuiz, $questions);
            }
        });
        $quiz->refresh();

        AdminActivityLog::record($request->user(), 'trainer.quiz.updated', $quiz, [
            'title' => $quiz->title,
            'batch_id' => $quiz->training_batch_id,
            'published' => $quiz->is_published,
        ]);

        if (! $wasPublished && $quiz->is_published) {
            $assessments->resetPendingProgressForPublishedQuiz($quiz);
            $this->notifyTrainees($quiz);
        }

        return $this->moduleRedirect($quiz)
            ->with('saved', 'Quiz updated.');
    }

    public function publication(
        Request $request,
        Quiz $quiz,
        ModuleAssessmentService $assessments,
    ): RedirectResponse {
        $this->authorize('update', $quiz);
        $validated = $request->validate(['is_published' => ['required', 'boolean']]);
        $published = (bool) $validated['is_published'];
        $wasPublished = $quiz->is_published;

        if ($published && ! $quiz->questions()->exists()) {
            throw ValidationException::withMessages([
                'quiz' => 'Add at least one valid question before publishing.',
            ]);
        }

        $quiz->update([
            'is_published' => $published,
            'published_at' => $published ? ($quiz->published_at ?? now()) : null,
        ]);

        AdminActivityLog::record($request->user(), 'trainer.quiz.publication.updated', $quiz, [
            'title' => $quiz->title,
            'published' => $published,
        ]);

        if (! $wasPublished && $published) {
            $assessments->resetPendingProgressForPublishedQuiz($quiz);
            $this->notifyTrainees($quiz);
        }

        return $this->moduleRedirect($quiz)
            ->with('saved', $published ? 'Quiz published.' : 'Quiz returned to draft.');
    }

    public function results(Request $request, Quiz $quiz): View
    {
        $this->authorize('viewResults', $quiz);

        $gradedAttempts = $quiz->attempts()->where('status', QuizAttempt::STATUS_GRADED);

        return view('trainer.quizzes.results', [
            'quiz' => $quiz->load(['batch', 'targetTrainee', 'questions']),
            'attempts' => $quiz->attempts()
                ->with(['application.user'])
                ->where('status', QuizAttempt::STATUS_GRADED)
                ->latest('submitted_at')
                ->paginate(20),
            'classSummary' => [
                'average' => (clone $gradedAttempts)->avg('score_percent'),
                'highest' => (clone $gradedAttempts)->max('score_percent'),
                'lowest' => (clone $gradedAttempts)->min('score_percent'),
                'passed' => (clone $gradedAttempts)->where('passed', true)->count(),
            ],
        ]);
    }

    public function destroy(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('delete', $quiz);

        [$title, $moduleId] = DB::transaction(function () use ($request, $quiz): array {
            $lockedQuiz = Quiz::query()->lockForUpdate()->findOrFail($quiz->id);

            if ($lockedQuiz->attempts()->exists()) {
                throw ValidationException::withMessages([
                    'quiz' => 'This quiz already has learner attempts and cannot be deleted. Return it to draft to close access while preserving grades.',
                ]);
            }

            $title = $lockedQuiz->title;
            $moduleId = $lockedQuiz->training_module_id;
            AdminActivityLog::record($request->user(), 'trainer.quiz.deleted', $lockedQuiz, [
                'title' => $title,
                'batch_id' => $lockedQuiz->training_batch_id,
            ]);
            $lockedQuiz->delete();

            return [$title, $moduleId];
        });

        return $this->moduleRedirect($quiz, $moduleId)
            ->with('saved', "Quiz {$title} was removed.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request): array
    {
        $request->merge([
            'audience_type' => $request->input('audience_type', 'batch'),
        ]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'training_module_id' => [
                'required',
                'integer',
                $this->managesAnyQuiz($request)
                    ? Rule::exists('training_modules', 'id')
                    : Rule::exists('training_modules', 'id')->where(
                        fn ($query) => $query->where('trainer_id', $request->user()->id)
                    ),
            ],
            'training_submodule_id' => ['nullable', 'integer', 'exists:training_submodules,id'],
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
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:240'],
            'attempt_limit' => ['required', 'integer', 'min:1', 'max:5'],
            'passing_score_percent' => ['required', 'numeric', 'min:1', 'max:100'],
            'is_published' => ['nullable', 'boolean'],
            'questions' => ['required', 'array', 'min:1', 'max:50'],
            'questions.*.type' => ['required', Rule::in(['multiple_choice', 'true_false', 'file_upload', 'enumeration'])],
            'questions.*.prompt' => ['required', 'string', 'max:2000'],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.options.*' => ['nullable', 'string', 'max:500'],
            'questions.*.correct_option' => ['nullable', 'integer', 'min:0', 'max:5'],
            'questions.*.points' => ['required', 'numeric', 'min:0.25', 'max:100'],
            'questions.*.position' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ]);

        if (
            filled($validated['available_at'] ?? null)
            && filled($validated['due_at'] ?? null)
            && Carbon::parse($validated['due_at'])->lte(Carbon::parse($validated['available_at']))
        ) {
            throw ValidationException::withMessages([
                'due_at' => 'The quiz due date must be after its availability date.',
            ]);
        }

        return $validated;
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     * @return array<int, array<string, mixed>>
     */
    private function normalizedQuestions(array $questions): array
    {
        $normalized = [];

        foreach (array_values($questions) as $index => $question) {
            $type = $question['type'];
            if (in_array($type, ['file_upload', 'enumeration'], true)) {
                $options = [];
                $correctOption = null;
            } else {
                $rawOptions = array_values(array_filter($question['options'] ?? [], fn ($opt) => filled(trim((string) $opt))));
                $options = $type === 'true_false'
                    ? ['True', 'False']
                    : array_map(static fn ($option) => trim((string) $option), $rawOptions);
                $correctOption = (int) ($question['correct_option'] ?? 0);

                if (count($options) < 2) {
                    throw ValidationException::withMessages([
                        "questions.{$index}.options" => 'Provide at least two answer options.',
                    ]);
                }

                if (count(array_unique(array_map('mb_strtolower', $options))) !== count($options)) {
                    throw ValidationException::withMessages([
                        "questions.{$index}.options" => 'Answer options must be unique.',
                    ]);
                }

                if (! array_key_exists($correctOption, $options)) {
                    throw ValidationException::withMessages([
                        "questions.{$index}.correct_option" => 'Choose a valid correct answer.',
                    ]);
                }
            }

            $normalized[] = [
                'type' => $type,
                'prompt' => trim((string) $question['prompt']),
                'options' => $options,
                'correct_option' => $correctOption,
                'points' => (float) $question['points'],
                'position' => isset($question['position']) ? (int) $question['position'] : $index,
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     */
    private function replaceQuestions(Quiz $quiz, array $questions): void
    {
        $quiz->questions()->delete();
        $quiz->questions()->createMany($questions);
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
                'audience_type' => 'Choose a class or an approved trainee before saving.',
            ]);
        }

        return [(int) $batchId, $targetTrainee];
    }

    /**
     * @return array{batches: Collection, trainees: Collection}
     */
    private function formOptions(): array
    {
        $assignedBatch = TrainingBatch::assignedTo(request()->user());

        return [
            'batches' => $assignedBatch ? collect([$assignedBatch]) : collect(),
            'modules' => TrainingModule::query()
                ->with('submodules')
                ->where('trainer_id', request()->user()->id)
                ->where('completion_mode', TrainingModule::COMPLETION_ASSESSED)
                ->orderBy('title')
                ->get(),
            'trainees' => EnrollmentApplication::query()
                ->with('batch')
                ->where('status', EnrollmentApplication::STATUS_APPROVED)
                ->when($assignedBatch, fn ($query) => $query->where('training_batch_id', $assignedBatch->id))
                ->when(! $assignedBatch, fn ($query) => $query->whereRaw('1 = 0'))
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveParentModule(
        Request $request,
        array $validated,
        int $batchId,
        ?EnrollmentApplication $targetTrainee,
    ): TrainingModule {
        $moduleId = $validated['training_module_id'];
        $module = TrainingModule::query()
            ->whereKey($moduleId)
            ->when(! $this->managesAnyQuiz($request), fn ($query) => $query->where('trainer_id', $request->user()->id))
            ->first();

        if (! $module) {
            throw ValidationException::withMessages([
                'training_module_id' => 'Choose one of your own learning modules.',
            ]);
        }

        if (
            (int) $module->training_batch_id !== $batchId
            || $this->nullableInt($module->target_enrollment_application_id) !== $this->nullableInt($targetTrainee?->id)
        ) {
            throw ValidationException::withMessages([
                'training_module_id' => 'The parent module and quiz must use the same class and trainee audience.',
            ]);
        }

        if (! $module->requiresEvaluation()) {
            throw ValidationException::withMessages([
                'training_module_id' => 'Choose an assessed module. Learning-material-only modules cannot contain required classwork.',
            ]);
        }

        return $module;
    }

    /** @param array<string, mixed> $validated */
    private function resolveSubmodule(
        TrainingModule $module,
        array $validated,
        ?Quiz $existingQuiz = null,
    ): ?\App\Models\TrainingSubmodule {
        $submoduleId = $validated['training_submodule_id'] ?? $existingQuiz?->training_submodule_id;
        if ($submoduleId) {
            $submodule = $module->submodules()->find($submoduleId);
            if ($submodule) {
                return $submodule;
            }

            throw ValidationException::withMessages([
                'training_submodule_id' => 'Choose a submodule inside the selected learning module.',
            ]);
        }

        $submodules = $module->submodules()->get();
        if ($submodules->count() === 1) {
            return $submodules->first();
        }

        if ($existingQuiz && ! $existingQuiz->training_submodule_id) {
            return null;
        }

        throw ValidationException::withMessages([
            'training_submodule_id' => 'Choose which submodule this assessment evaluates.',
        ]);
    }

    private function moduleRedirect(Quiz $quiz, ?int $moduleId = null): RedirectResponse
    {
        $moduleId ??= $quiz->training_module_id;

        if (request()->routeIs('admin.learning.quizzes.*')) {
            if (! $moduleId) {
                return redirect()->route('admin.learning.modules');
            }

            return redirect()->route('admin.learning.modules.quizzes', $moduleId);
        }

        if (! $moduleId) {
            return redirect()->route('trainer.resources');
        }

        return redirect()->to(route('trainer.modules.show', ['module' => $moduleId, 'tab' => 'assessments']).'#assessments');
    }

    private function assertTrainerBatch(
        Request $request,
        int $batchId,
        ?EnrollmentApplication $targetTrainee,
    ): void {
        if ($this->managesAnyQuiz($request)) {
            if ($targetTrainee && (int) $targetTrainee->training_batch_id !== $batchId) {
                throw ValidationException::withMessages([
                    'target_enrollment_application_id' => 'The selected trainee is outside the quiz class.',
                ]);
            }

            return;
        }

        $assignedBatch = TrainingBatch::assignedTo($request->user());

        if (! $assignedBatch || $batchId !== (int) $assignedBatch->id) {
            throw ValidationException::withMessages([
                'training_batch_id' => 'Quizzes can only be assigned to the trainer\'s current batch.',
            ]);
        }

        if ($targetTrainee && (int) $targetTrainee->training_batch_id !== $batchId) {
            throw ValidationException::withMessages([
                'target_enrollment_application_id' => 'The selected trainee is outside the trainer\'s assigned batch.',
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function questionsFromUpload(Request $request): ?array
    {
        if (! $request->hasFile('question_file')) {
            return null;
        }

        $validated = $request->validate([
            'question_file' => ['required', 'file', 'max:256', 'extensions:txt,text,csv,json'],
        ]);

        return app(QuizQuestionImporter::class)->import($validated['question_file'], 'question_file');
    }

    private function managesAnyQuiz(Request $request): bool
    {
        return $request->routeIs('admin.learning.quizzes.*')
            && $request->user()?->hasRole('admin') === true;
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     */
    private function questionFingerprint(array $questions): string
    {
        return json_encode($questions, JSON_THROW_ON_ERROR);
    }

    private function storedQuestionFingerprint(Quiz $quiz): string
    {
        $questions = $quiz->questions()
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->values()
            ->map(fn ($question, $index) => [
                'type' => $question->type,
                'prompt' => $question->prompt,
                'options' => array_values($question->options ?? []),
                'correct_option' => $question->correct_option !== null ? (int) $question->correct_option : null,
                'points' => (float) $question->points,
                'position' => (int) ($question->position ?? $index),
            ])
            ->all();

        return $this->questionFingerprint($questions);
    }

    /**
     * Once an attempt exists, keep every value that affects access or grading
     * immutable. Trainers may still edit explanatory text or close/reopen access.
     *
     * @param  array<string, mixed>  $validated
     * @param  array<int, array<string, mixed>>  $questions
     */
    private function assertGradingMetadataUnchanged(
        Quiz $quiz,
        array $validated,
        int $batchId,
        ?EnrollmentApplication $targetTrainee,
        ?TrainingModule $parentModule,
        ?\App\Models\TrainingSubmodule $submodule,
        array $questions,
    ): void {
        $message = 'This setting is locked because a trainee has already started the quiz.';
        $errors = [];

        if ($this->nullableInt($quiz->training_module_id) !== $this->nullableInt($parentModule?->id)) {
            $errors['training_module_id'] = $message;
        }

        if ($this->nullableInt($quiz->training_submodule_id) !== $this->nullableInt($submodule?->id)) {
            $errors['training_submodule_id'] = $message;
        }

        if ((int) $quiz->training_batch_id !== $batchId) {
            $errors['training_batch_id'] = $message;
        }

        if (
            $this->nullableInt($quiz->target_enrollment_application_id)
            !== $this->nullableInt($targetTrainee?->id)
        ) {
            $errors['target_enrollment_application_id'] = $message;
        }

        if (
            abs(
                (float) $quiz->passing_score_percent
                - (float) $validated['passing_score_percent']
            ) > 0.001
        ) {
            $errors['passing_score_percent'] = $message;
        }

        if (
            $this->questionFingerprint($questions)
            !== $this->storedQuestionFingerprint($quiz)
        ) {
            $errors['questions'] = $message;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function sameDateTime(mixed $current, mixed $incoming): bool
    {
        if (blank($incoming)) {
            return $current === null;
        }

        return $current !== null && $current->equalTo(Carbon::parse($incoming));
    }

    private function nullableInt(mixed $value): ?int
    {
        return filled($value) ? (int) $value : null;
    }

    private function notifyTrainees(Quiz $quiz): void
    {
        if (! $quiz->is_published || ($quiz->available_at && $quiz->available_at->isFuture())) {
            return;
        }

        $query = EnrollmentApplication::query()
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->where('learning_status', '!=', EnrollmentApplication::LEARNING_GRADUATED)
            ->where('is_historical_record', false)
            ->whereNotNull('user_id');

        if ($quiz->target_enrollment_application_id !== null) {
            $query->whereKey($quiz->target_enrollment_application_id);
        } elseif ($quiz->training_batch_id) {
            $query->where('training_batch_id', $quiz->training_batch_id);
        }

        $traineeIds = $query->get()
            ->filter(fn (EnrollmentApplication $application): bool => $quiz->targets($application))
            ->pluck('user_id')
            ->unique();

        $trainees = User::query()
            ->where('role', 'trainee')
            ->whereIn('id', $traineeIds)
            ->get();

        if ($trainees->isNotEmpty()) {
            Notification::send($trainees, new LmsQuizPublished($quiz));
        }
    }

    public function downloadAttemptSubmission(
        Request $request,
        Quiz $quiz,
        QuizAttempt $attempt,
        int $questionId,
    ): BinaryFileResponse {
        $this->authorize('view', $quiz);
        abort_unless((int) $attempt->quiz_id === (int) $quiz->id, 404);

        $answers = $attempt->answers ?? [];
        $answer = $answers[$questionId] ?? $answers[(string) $questionId] ?? null;

        abort_unless(is_array($answer) && ($answer['type'] ?? null) === 'file', 404);
        $path = $answer['file_path'] ?? null;
        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404);

        $filename = basename($answer['original_name'] ?? 'submission');
        $fallbackFilename = str($filename)->ascii()->replaceMatches('/[^A-Za-z0-9._-]/', '-')->toString();

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => ($answer['mime_type'] ?? null) ?: 'application/octet-stream',
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                $filename,
                $fallbackFilename
            ),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
