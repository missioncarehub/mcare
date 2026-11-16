<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\TrainingModule;
use App\Services\ModuleSubmoduleService;
use App\Services\QuizQuestionImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;

class AdminQuizController extends Controller
{
    public function index(TrainingModule $module, ModuleSubmoduleService $submodules): View
    {
        $this->authorize('viewAny', Quiz::class);

        if ($module->requiresEvaluation()) {
            $submodules->ensureStructure($module);
        }

        $module->load([
            'trainer',
            'batch',
            'submodules',
            'quizzes' => fn ($query) => $query->with('trainingSubmodule')->withCount('questions'),
        ]);

        return view('admin.learning.quizzes', [
            'module' => $module,
        ]);
    }

    public function store(
        Request $request,
        TrainingModule $module,
        QuizQuestionImporter $importer,
    ): RedirectResponse {
        $this->authorize('create', Quiz::class);

        if (! $module->requiresEvaluation()) {
            throw ValidationException::withMessages([
                'quiz' => 'This is a learning-material-only module. Create an assessed module before adding a quiz.',
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
            'question_file' => ['nullable', 'file', 'max:256', 'extensions:txt,text,csv,json'],
        ]);

        $importedQuestions = $request->hasFile('question_file')
            ? $importer->import($request->file('question_file'), 'question_file')
            : null;

        $quiz = DB::transaction(function () use ($request, $module, $validated, $importedQuestions): Quiz {
            $created = Quiz::create([
                'trainer_id' => $module->trainer_id ?: $request->user()->id,
                'training_batch_id' => $module->training_batch_id,
                'target_enrollment_application_id' => $module->target_enrollment_application_id,
                'training_module_id' => $module->id,
                'training_submodule_id' => $validated['training_submodule_id'],
                'title' => $validated['title'],
                'instructions' => $validated['instructions'] ?? null,
                'time_limit_minutes' => $validated['time_limit_minutes'] ?? 20,
                'passing_score_percent' => $validated['passing_score_percent'] ?? 75,
                'is_published' => false,
                'published_at' => null,
            ]);

            if ($importedQuestions !== null) {
                $created->questions()->createMany($importedQuestions);
            }

            return $created;
        });

        AdminActivityLog::record($request->user(), 'admin.quiz.created', $quiz, [
            'title' => $quiz->title,
            'module_id' => $module->id,
            'question_count' => $importedQuestions === null ? 0 : count($importedQuestions),
        ]);

        return redirect()
            ->route('admin.learning.quizzes.edit', $quiz)
            ->with('saved', $importedQuestions === null
                ? 'Quiz created. Import a question file or add questions below.'
                : 'Imported '.count($importedQuestions).' '.str('question')->plural(count($importedQuestions)).'. Review them, then save.');
    }

    public function results(Request $request, Quiz $quiz): View
    {
        $this->authorize('viewAny', Quiz::class);

        $gradedAttempts = $quiz->attempts()->where('status', QuizAttempt::STATUS_GRADED);

        return view('admin.learning.quiz-results', [
            'quiz' => $quiz->load(['batch', 'targetTrainee', 'questions', 'trainingModule']),
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

    public function downloadAttemptSubmission(
        Request $request,
        Quiz $quiz,
        QuizAttempt $attempt,
        int $question,
    ): BinaryFileResponse {
        $this->authorize('viewAny', Quiz::class);
        abort_unless((int) $attempt->quiz_id === (int) $quiz->id, 404);

        $answers = $attempt->answers ?? [];
        $answer = $answers[$question] ?? $answers[(string) $question] ?? null;

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
