<?php

namespace Tests\Feature\Lms;

use App\Models\CompetencyUnit;
use App\Models\ModuleProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\TraineeCompetencyRecord;
use App\Models\TrainingModule;
use App\Services\QuizQuestionImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class QuizAuthoringTest extends TestCase
{
    use CreatesLmsTestData;
    use RefreshDatabase;

    public function test_trainer_can_author_edit_publish_review_and_delete_a_quiz(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        $module = $this->lmsModule($trainer, $batch);

        $this->actingAs($trainer)
            ->get(route('trainer.quizzes.create'))
            ->assertRedirect(route('trainer.resources'));

        $this->actingAs($trainer)
            ->get(route('trainer.assessments'))
            ->assertRedirect(route('trainer.resources'));

        $this->actingAs($trainer)
            ->post(route('trainer.quizzes.store'), $this->quizPayload($batch->id, [
                'training_module_id' => $module->id,
            ]))
            ->assertRedirect(route('trainer.modules.show', ['module' => $module, 'tab' => 'assessments']).'#assessments')
            ->assertSessionHas('saved');

        $quiz = Quiz::query()->where('title', 'Infection control check')->firstOrFail();
        $this->assertSame($trainer->id, $quiz->trainer_id);
        $this->assertSame($module->id, $quiz->training_module_id);
        $this->assertFalse($quiz->is_published);
        $this->assertCount(2, $quiz->questions);

        $this->actingAs($trainer)
            ->get(route('trainer.quizzes.edit', $quiz))
            ->assertOk()
            ->assertSee('data-quiz-builder', false)
            ->assertSee('id="quiz-builder-form"', false)
            ->assertSee('data-quiz-overview', false)
            ->assertSee('Infection control check')
            ->assertSee('>Title</dt>', false)
            ->assertDontSee('id="quiz-title"', false)
            ->assertDontSee('type="datetime-local"', false)
            ->assertSee('data-quiz-question', false);

        $this->actingAs($trainer)
            ->patch(route('trainer.quizzes.update', $quiz), $this->quizPayload($batch->id, [
                'training_module_id' => $module->id,
                'title' => 'Updated infection control check',
                'passing_score_percent' => 80,
            ]))
            ->assertRedirect(route('trainer.modules.show', ['module' => $module, 'tab' => 'assessments']).'#assessments');

        $this->actingAs($trainer)
            ->patch(route('trainer.quizzes.publication', $quiz), ['is_published' => '1'])
            ->assertRedirect(route('trainer.modules.show', ['module' => $module, 'tab' => 'assessments']).'#assessments');

        $quiz->refresh();
        $this->assertTrue($quiz->is_published);
        $this->assertNotNull($quiz->published_at);
        $this->assertSame('Updated infection control check', $quiz->title);

        $this->actingAs($trainer)
            ->get(route('trainer.quizzes.results', $quiz))
            ->assertOk()
            ->assertSee('data-quiz-results', false);

        $this->actingAs($trainer)
            ->delete(route('trainer.quizzes.destroy', $quiz))
            ->assertRedirect(route('trainer.modules.show', ['module' => $module, 'tab' => 'assessments']).'#assessments');

        $this->assertDatabaseMissing('quizzes', ['id' => $quiz->id]);
        $this->assertDatabaseMissing('quiz_questions', ['quiz_id' => $quiz->id]);
    }

    public function test_quiz_publication_requires_a_valid_question_set(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        $module = $this->lmsModule($trainer, $batch);
        $quiz = $this->quiz($trainer->id, $batch->id, ['training_module_id' => $module->id]);

        $this->actingAs($trainer)
            ->from(route('trainer.quizzes.edit', $quiz))
            ->patch(route('trainer.quizzes.publication', $quiz), ['is_published' => '1'])
            ->assertRedirect(route('trainer.quizzes.edit', $quiz))
            ->assertSessionHasErrors();

        $this->assertFalse($quiz->fresh()->is_published);
    }

    public function test_quiz_rejects_an_answer_key_outside_the_available_options(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        $module = $this->lmsModule($trainer, $batch);
        $payload = $this->quizPayload($batch->id, ['training_module_id' => $module->id]);
        $payload['questions'][0]['correct_option'] = 99;

        $this->actingAs($trainer)
            ->from(route('trainer.quizzes.create'))
            ->post(route('trainer.quizzes.store'), $payload)
            ->assertRedirect(route('trainer.quizzes.create'))
            ->assertSessionHasErrors('questions.0.correct_option');

        $this->assertDatabaseMissing('quizzes', [
            'title' => 'Infection control check',
        ]);
    }

    public function test_quiz_cannot_be_attached_to_another_trainers_module(): void
    {
        $trainer = $this->lmsUser('trainer');
        $otherTrainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        $otherModule = $this->lmsModule($otherTrainer, $batch);
        $payload = $this->quizPayload($batch->id, [
            'training_module_id' => $otherModule->id,
        ]);

        $this->actingAs($trainer)
            ->from(route('trainer.quizzes.create'))
            ->post(route('trainer.quizzes.store'), $payload)
            ->assertRedirect(route('trainer.quizzes.create'))
            ->assertSessionHasErrors('training_module_id');

        $this->assertDatabaseMissing('quizzes', ['title' => 'Infection control check']);
    }

    public function test_trainer_cannot_manage_another_trainers_quiz(): void
    {
        $owner = $this->lmsUser('trainer');
        $otherTrainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        $module = $this->lmsModule($owner, $batch);
        $quiz = $this->quiz($owner->id, $batch->id, ['training_module_id' => $module->id]);
        $this->question($quiz);

        $this->actingAs($otherTrainer)
            ->get(route('trainer.quizzes.edit', $quiz))
            ->assertForbidden();

        $this->actingAs($otherTrainer)
            ->patch(route('trainer.quizzes.update', $quiz), $this->quizPayload($batch->id))
            ->assertForbidden();

        $this->actingAs($otherTrainer)
            ->patch(route('trainer.quizzes.publication', $quiz), ['is_published' => '1'])
            ->assertForbidden();

        $this->actingAs($otherTrainer)
            ->get(route('trainer.quizzes.results', $quiz))
            ->assertForbidden();

        $this->actingAs($otherTrainer)
            ->delete(route('trainer.quizzes.destroy', $quiz))
            ->assertForbidden();
    }

    public function test_first_attempt_locks_grading_metadata_and_prevents_quiz_deletion(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['application' => $application] = $this->lmsTrainee($batch);
        $module = $this->lmsModule($trainer, $batch);
        $quiz = $this->quiz($trainer->id, $batch->id, [
            'training_module_id' => $module->id,
            'is_published' => true,
        ]);
        $question = $this->question($quiz);
        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'enrollment_application_id' => $application->id,
            'attempt_number' => 1,
            'status' => QuizAttempt::STATUS_IN_PROGRESS,
            'answers' => [],
            'started_at' => now(),
        ]);
        $payload = [
            'audience_type' => 'batch',
            'training_batch_id' => $batch->id,
            'training_module_id' => $module->id,
            'title' => 'Safe title update',
            'instructions' => 'Updated explanation only.',
            'available_at' => $quiz->available_at?->toDateTimeString(),
            'due_at' => $quiz->due_at?->toDateTimeString(),
            'time_limit_minutes' => $quiz->time_limit_minutes,
            'attempt_limit' => $quiz->attempt_limit,
            'passing_score_percent' => 90,
            'is_published' => '0',
            'questions' => [[
                'type' => $question->type,
                'prompt' => $question->prompt,
                'options' => $question->options,
                'correct_option' => $question->correct_option,
                'points' => $question->points,
                'position' => $question->position,
            ]],
        ];

        $this->actingAs($trainer)
            ->from(route('trainer.quizzes.edit', $quiz))
            ->patch(route('trainer.quizzes.update', $quiz), $payload)
            ->assertRedirect(route('trainer.quizzes.edit', $quiz))
            ->assertSessionHasErrors('passing_score_percent');

        $quiz->refresh();
        $this->assertSame('Draft knowledge check', $quiz->title);
        $this->assertEquals(75.0, (float) $quiz->passing_score_percent);

        $payload['passing_score_percent'] = 75;

        $this->actingAs($trainer)
            ->patch(route('trainer.quizzes.update', $quiz), $payload)
            ->assertRedirect(route('trainer.modules.show', ['module' => $module, 'tab' => 'assessments']).'#assessments')
            ->assertSessionHas('saved');

        $quiz->refresh();
        $this->assertSame('Safe title update', $quiz->title);
        $this->assertFalse($quiz->is_published);
        $this->assertEquals(75.0, (float) $quiz->passing_score_percent);

        $this->actingAs($trainer)
            ->from(route('trainer.modules.show', ['module' => $module, 'tab' => 'assessments']).'#assessments')
            ->delete(route('trainer.quizzes.destroy', $quiz))
            ->assertRedirect(route('trainer.modules.show', ['module' => $module, 'tab' => 'assessments']).'#assessments')
            ->assertSessionHasErrors('quiz');

        $this->assertDatabaseHas('quizzes', ['id' => $quiz->id]);
        $this->assertDatabaseHas('quiz_attempts', ['quiz_id' => $quiz->id]);
    }

    private function quizPayload(int $batchId, array $overrides = []): array
    {
        $payload = array_merge([
            'training_batch_id' => $batchId,
            'title' => 'Infection control check',
            'instructions' => 'Choose the safest answer for each item.',
            'available_at' => now()->subHour()->toDateTimeString(),
            'due_at' => now()->addWeek()->toDateTimeString(),
            'time_limit_minutes' => 20,
            'attempt_limit' => 2,
            'passing_score_percent' => 75,
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'prompt' => 'What should happen before patient contact?',
                    'options' => ['Perform hand hygiene', 'Remove all PPE', 'Skip preparation'],
                    'correct_option' => 0,
                    'points' => 2,
                    'position' => 0,
                ],
                [
                    'type' => 'true_false',
                    'prompt' => 'Gloves replace hand hygiene.',
                    'options' => ['True', 'False'],
                    'correct_option' => 1,
                    'points' => 1,
                    'position' => 1,
                ],
            ],
        ], $overrides);

        if (filled($payload['training_module_id'] ?? null)
            && ! array_key_exists('training_submodule_id', $payload)) {
            $payload['training_submodule_id'] = $this->lmsSubmodule(
                TrainingModule::query()->findOrFail($payload['training_module_id'])
            )->id;
        }

        return $payload;
    }

    public function test_trainer_can_create_quiz_with_file_upload_and_enumeration_questions(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        $module = $this->lmsModule($trainer, $batch);

        $payload = $this->quizPayload($batch->id, [
            'training_module_id' => $module->id,
            'title' => 'Practical Activity Assessment',
            'questions' => [
                [
                    'type' => 'file_upload',
                    'prompt' => 'Upload your completed Caregiving Activity Sheet (PDF or image).',
                    'points' => 5,
                    'position' => 0,
                ],
                [
                    'type' => 'enumeration',
                    'prompt' => 'Enumerate the 5 steps of hand washing hygiene.',
                    'points' => 5,
                    'position' => 1,
                ],
            ],
        ]);

        $this->actingAs($trainer)
            ->post(route('trainer.quizzes.store'), $payload)
            ->assertRedirect(route('trainer.modules.show', ['module' => $module, 'tab' => 'assessments']).'#assessments')
            ->assertSessionHas('saved');

        $quiz = Quiz::query()->where('title', 'Practical Activity Assessment')->firstOrFail();
        $this->assertCount(2, $quiz->questions);
        $this->assertSame('file_upload', $quiz->questions[0]->type);
        $this->assertSame('enumeration', $quiz->questions[1]->type);
    }

    public function test_evaluating_module_updates_competency_outcomes_without_overwriting_the_separate_overall_grade(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        $trainee = $this->lmsTrainee($batch)['application'];
        $module = $this->lmsModule($trainer, $batch, [
            'module_code' => '500311105',
            'title' => 'Participate in Workplace Communication',
        ])->fresh(['submodules']);

        $unit = CompetencyUnit::where('title', 'Participate in Workplace Communication')->firstOrFail();

        $this->lmsPassedAssessment($trainer, $module, $trainee, 90);

        foreach ($module->submodules as $submodule) {
            $this->actingAs($trainer)
                ->post(route('trainer.modules.evaluate', $module), [
                    'training_submodule_id' => $submodule->id,
                    'enrollment_application_id' => $trainee->id,
                    'practical_rating' => 'competent',
                    'competency_outcome' => 'competent',
                    'evaluation_remarks' => 'Demonstrated clear communication techniques.',
                ])
                ->assertRedirect(route('trainer.modules.show', ['module' => $module, 'tab' => 'evaluations']).'#evaluations')
                ->assertSessionHas('saved');
        }

        $record = TraineeCompetencyRecord::query()
            ->where('enrollment_application_id', $trainee->id)
            ->where('competency_unit_id', $unit->id)
            ->firstOrFail();

        $this->assertSame('competent', $record->status);
        $this->assertNull($record->percentage_score);
        $this->assertNull($record->tor_grade);
        $this->assertCount(3, $record->outcomeResults);
        $this->assertTrue($record->outcomeResults->every(fn ($res) => $res->status === 'competent'));
    }

    public function test_trainer_can_import_questions_from_a_file_while_building_a_quiz(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        $module = $this->lmsModule($trainer, $batch);
        $quiz = $this->quiz($trainer->id, $batch->id, ['training_module_id' => $module->id]);
        $file = UploadedFile::fake()->createWithContent('questions.txt', QuizQuestionImporter::sampleText());

        $this->actingAs($trainer)
            ->get(route('trainer.quizzes.edit', $quiz))
            ->assertOk()
            ->assertSee('data-quiz-import', false)
            ->assertSee('Import questions', false)
            ->assertSee(route('trainer.quizzes.import'), false);

        $this->actingAs($trainer)
            ->post(route('trainer.quizzes.import'), ['file' => $file], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('questions.0.prompt', 'What should happen before patient contact?')
            ->assertJsonCount(4, 'questions');

        $payload = $this->quizPayload($batch->id, [
            'training_module_id' => $module->id,
            'title' => 'Imported class quiz',
            'question_file' => UploadedFile::fake()->createWithContent('questions.txt', QuizQuestionImporter::sampleText()),
        ]);

        $this->actingAs($trainer)
            ->post(route('trainer.quizzes.store'), $payload)
            ->assertRedirect();

        $imported = Quiz::query()->where('title', 'Imported class quiz')->firstOrFail();
        $this->assertCount(4, $imported->questions);
        $this->assertSame('enumeration', $imported->questions[2]->type);
        $this->assertSame([], $imported->questions[2]->options);

        $submodule = $this->lmsSubmodule($module);
        $this->actingAs($trainer)
            ->post(route('trainer.modules.quizzes.store', $module), [
                'training_submodule_id' => $submodule->id,
                'title' => 'Module file quiz',
                'question_file' => UploadedFile::fake()->createWithContent('questions.csv', <<<'CSV'
type,prompt,points,answer,option_a,option_b
multiple_choice,Which action comes first?,1,A,Hand hygiene,Leave the room
CSV),
            ])
            ->assertRedirect();

        $moduleQuiz = Quiz::query()->where('title', 'Module file quiz')->firstOrFail();
        $this->assertSame('Hand hygiene', $moduleQuiz->questions()->first()->options[0]);
    }

    public function test_admin_can_import_questions_when_creating_and_editing_a_quiz(): void
    {
        $admin = $this->lmsUser('admin');
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        $module = $this->lmsModule($trainer, $batch);
        $submodule = $this->lmsSubmodule($module);

        $this->actingAs($admin)
            ->get(route('admin.learning.modules'))
            ->assertOk()
            ->assertSee('Module quizzes', false);

        $this->actingAs($admin)
            ->get(route('admin.learning.modules.quizzes', $module))
            ->assertOk()
            ->assertSee('Import questions from a file', false)
            ->assertSee(route('admin.learning.quizzes.import-template'), false);

        $this->actingAs($admin)
            ->post(route('admin.learning.modules.quizzes.store', $module), [
                'title' => 'Admin imported quiz',
                'training_submodule_id' => $submodule->id,
                'question_file' => UploadedFile::fake()->createWithContent('questions.txt', QuizQuestionImporter::sampleText()),
            ])
            ->assertRedirect();

        $quiz = Quiz::query()->where('title', 'Admin imported quiz')->firstOrFail();
        $this->assertSame($trainer->id, $quiz->trainer_id);
        $this->assertCount(4, $quiz->questions);

        $this->actingAs($admin)
            ->get(route('admin.learning.quizzes.edit', $quiz))
            ->assertOk()
            ->assertSee('data-import-url="'.route('admin.learning.quizzes.import').'"', false)
            ->assertSee('What should happen before patient contact?');

        $this->actingAs($admin)
            ->post(route('admin.learning.quizzes.import'), [
                'file' => UploadedFile::fake()->createWithContent('questions.json', json_encode([[
                    'prompt' => 'Reviewed from a file',
                    'options' => ['Yes', 'No'],
                    'answer' => 'A',
                ]], JSON_THROW_ON_ERROR)),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('questions.0.correct_option', 0);

        $this->actingAs($admin)
            ->patch(route('admin.learning.quizzes.update', $quiz), $this->quizPayload($batch->id, [
                'training_module_id' => $module->id,
                'training_submodule_id' => $submodule->id,
                'title' => 'Admin imported quiz',
                'questions' => [[
                    'type' => 'multiple_choice',
                    'prompt' => 'Reviewed question',
                    'options' => ['Yes', 'No'],
                    'correct_option' => 1,
                    'points' => 1,
                ]],
            ]))
            ->assertRedirect(route('admin.learning.modules.quizzes', $module));

        $this->assertSame('Reviewed question', $quiz->fresh()->questions()->first()->prompt);
        $this->assertSame(1, $quiz->fresh()->questions()->first()->correct_option);
    }

    public function test_a_trainee_cannot_import_quiz_questions(): void
    {
        $trainee = $this->lmsUser('trainee');

        $this->actingAs($trainee)
            ->post(route('trainer.quizzes.import'), [
                'file' => UploadedFile::fake()->createWithContent('questions.txt', QuizQuestionImporter::sampleText()),
            ])
            ->assertForbidden();
    }

    private function quiz(int $trainerId, int $batchId, array $overrides = []): Quiz
    {
        if (filled($overrides['training_module_id'] ?? null)
            && ! array_key_exists('training_submodule_id', $overrides)) {
            $overrides['training_submodule_id'] = $this->lmsSubmodule(
                TrainingModule::query()->findOrFail($overrides['training_module_id'])
            )->id;
        }

        return Quiz::create(array_merge([
            'trainer_id' => $trainerId,
            'training_batch_id' => $batchId,
            'title' => 'Draft knowledge check',
            'instructions' => 'Answer every question.',
            'is_published' => false,
            'available_at' => now()->subHour(),
            'due_at' => now()->addWeek(),
            'time_limit_minutes' => 20,
            'attempt_limit' => 1,
            'passing_score_percent' => 75,
        ], $overrides));
    }

    private function question(Quiz $quiz): QuizQuestion
    {
        return QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'type' => 'multiple_choice',
            'prompt' => 'Choose the correct procedure.',
            'options' => ['Safe procedure', 'Unsafe procedure'],
            'correct_option' => 0,
            'points' => 1,
            'position' => 0,
        ]);
    }
}
