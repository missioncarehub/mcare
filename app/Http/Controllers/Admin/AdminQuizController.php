<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Quiz;
use App\Models\TrainingModule;
use App\Services\ModuleSubmoduleService;
use App\Services\QuizQuestionImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

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
}
