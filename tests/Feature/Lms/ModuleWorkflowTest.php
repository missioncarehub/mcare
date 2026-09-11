<?php

namespace Tests\Feature\Lms;

use App\Models\ModuleProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\TrainingModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class ModuleWorkflowTest extends TestCase
{
    use CreatesLmsTestData;
    use RefreshDatabase;

    public function test_learning_material_only_has_no_mark_as_done_and_does_not_block_assessed_modules(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch(['trainer_id' => $trainer->id]);
        ['user' => $user, 'application' => $application] = $this->lmsTrainee($batch);

        $reference = $this->lmsModule($trainer, $batch, [
            'title' => 'Optional Caregiving Reference',
            'completion_mode' => TrainingModule::COMPLETION_MATERIAL_ONLY,
        ]);
        $assessed = $this->lmsModule($trainer, $batch, [
            'title' => 'Required Caregiving Classwork',
            'completion_mode' => TrainingModule::COMPLETION_ASSESSED,
        ]);

        $this->assertSame(
            ModuleProgress::STATUS_NOT_STARTED,
            $this->progress($application->id, $reference->id)->status,
        );
        $this->assertSame(
            ModuleProgress::STATUS_NOT_STARTED,
            $this->progress($application->id, $assessed->id)->status,
        );

        $this->actingAs($user)
            ->get(route('trainee.modules.show', $reference))
            ->assertOk()
            ->assertSee('Learning material only')
            ->assertDontSee('data-module-progress-form', false);

        $this->actingAs($user)
            ->patch(route('trainee.modules.progress', $reference), ['action' => 'submit'])
            ->assertSessionHasErrors('action');

        $this->actingAs($user)
            ->get(route('trainee.modules.show', $assessed))
            ->assertOk()
            ->assertSee('Submodules')
            ->assertSee('Trainer records Competent or Not yet competent.')
            ->assertDontSee('Mark Submodule as Done')
            ->assertDontSee('data-module-progress-form', false);
    }

    public function test_completed_module_keeps_the_pdf_behind_a_show_hide_toggle(): void
    {
        Storage::fake('local');
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch(['trainer_id' => $trainer->id]);
        ['user' => $user, 'application' => $application] = $this->lmsTrainee($batch);
        $module = $this->lmsModule($trainer, $batch, [
            'file_path' => 'training-modules/required.pdf',
            'original_file_name' => 'required.pdf',
        ]);
        Storage::disk('local')->put($module->file_path, '%PDF test');
        $submodule = $this->lmsSubmodule($module);
        $this->lmsPassedAssessment($trainer, $module, $application, 88);

        $this->actingAs($user)
            ->patch(route('trainee.modules.submodules.progress', [$module, $submodule]), ['action' => 'submit'])
            ->assertSessionHasErrors('action');

        $this->actingAs($user)
            ->get(route('trainee.modules.show', $module))
            ->assertOk()
            ->assertSee('Awaiting trainer evaluation.')
            ->assertSee('data-pdf-canvas-viewer', false)
            ->assertDontSee('Mark Submodule as Done');

        $this->actingAs($trainer)
            ->post(route('trainer.modules.evaluate', $module), [
                'training_submodule_id' => $submodule->id,
                'enrollment_application_id' => $application->id,
                'practical_rating' => ModuleProgress::RATING_COMPETENT,
                'competency_outcome' => ModuleProgress::OUTCOME_COMPETENT,
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->get(route('trainee.modules.show', $module))
            ->assertOk()
            ->assertSee('Completed.')
            ->assertSee('Show lesson document')
            ->assertSee('data-pdf-canvas-viewer', false)
            ->assertSee('data-lesson-document-toggle', false)
            ->assertSee('Quiz average')
            ->assertSee('88.0%')
            ->assertDontSee('The lesson document and downloads are closed');

        $this->actingAs($user)
            ->get(route('trainee.modules.content', $module))
            ->assertOk();
    }

    public function test_trainer_can_record_remediation_after_all_failed_attempts_without_opening_pdf_panel(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch(['trainer_id' => $trainer->id]);
        ['application' => $application] = $this->lmsTrainee($batch);
        $module = $this->lmsModule($trainer, $batch, [
            'original_file_name' => 'caregiving-remediation.pdf',
        ]);
        $submodule = $this->lmsSubmodule($module);
        $quiz = $this->publishedQuiz($trainer->id, $module, attemptLimit: 2);
        $this->gradedAttempt($quiz, $application->id, 1, 55, false);
        $this->gradedAttempt($quiz, $application->id, 2, 65, false);

        $this->actingAs($trainer)
            ->get(route('trainer.modules.show', ['module' => $module, 'tab' => 'evaluations']))
            ->assertOk()
            ->assertSee('Attempts are exhausted')
            ->assertSee('65.0%')
            ->assertSee('Competent unlocks after assigned classwork is passed.')
            ->assertDontSee('Primary Lesson Material')
            ->assertDontSee('caregiving-remediation.pdf');

        $this->actingAs($trainer)
            ->post(route('trainer.modules.evaluate', $module), [
                'training_submodule_id' => $submodule->id,
                'enrollment_application_id' => $application->id,
                'competency_outcome' => ModuleProgress::OUTCOME_COMPETENT,
            ])
            ->assertSessionHasErrors('competency_outcome');

        $this->actingAs($trainer)
            ->post(route('trainer.modules.evaluate', $module), [
                'training_submodule_id' => $submodule->id,
                'enrollment_application_id' => $application->id,
                'practical_rating' => ModuleProgress::RATING_NOT_YET_COMPETENT,
                'competency_outcome' => ModuleProgress::OUTCOME_NOT_YET_COMPETENT,
                'evaluation_remarks' => 'Schedule a guided remediation activity.',
            ])
            ->assertRedirect(route('trainer.modules.show', ['module' => $module, 'tab' => 'evaluations']).'#evaluations')
            ->assertSessionHasNoErrors();

        $progress = $this->progress($application->id, $module->id);
        $this->assertSame(ModuleProgress::STATUS_NEEDS_REMEDIATION, $progress->status);
        $this->assertEquals(65.0, (float) $progress->quiz_score);
        $this->assertNull($progress->submitted_at);
    }

    public function test_newly_published_classwork_resets_pending_submission_but_preserves_completed_badge(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch(['trainer_id' => $trainer->id]);
        ['user' => $pendingUser, 'application' => $pendingApplication] = $this->lmsTrainee($batch, [
            'first_name' => 'Pending',
        ]);
        ['user' => $completedUser, 'application' => $completedApplication] = $this->lmsTrainee($batch, [
            'first_name' => 'Completed',
        ]);
        $module = $this->lmsModule($trainer, $batch, ['title' => 'Basic Caregiving']);
        $submodule = $this->lmsSubmodule($module);
        $firstQuiz = $this->publishedQuiz($trainer->id, $module);

        foreach ([$pendingApplication, $completedApplication] as $application) {
            $this->gradedAttempt($firstQuiz, $application->id, 1, 90, true);
        }

        $this->actingAs($trainer)
            ->post(route('trainer.modules.evaluate', $module), [
                'training_submodule_id' => $submodule->id,
                'enrollment_application_id' => $completedApplication->id,
                'practical_rating' => ModuleProgress::RATING_COMPETENT,
                'competency_outcome' => ModuleProgress::OUTCOME_COMPETENT,
            ])
            ->assertSessionHasNoErrors();

        $newQuiz = Quiz::create([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'training_module_id' => $module->id,
            'training_submodule_id' => $submodule->id,
            'title' => 'New Required Care Activity',
            'is_published' => false,
            'attempt_limit' => 1,
            'passing_score_percent' => 75,
        ]);
        QuizQuestion::create([
            'quiz_id' => $newQuiz->id,
            'type' => QuizQuestion::TYPE_TRUE_FALSE,
            'prompt' => 'New required check',
            'options' => ['True', 'False'],
            'correct_option' => 0,
            'points' => 10,
            'position' => 0,
        ]);

        $this->actingAs($trainer)
            ->patch(route('trainer.quizzes.publication', $newQuiz), ['is_published' => 1])
            ->assertSessionHasNoErrors();

        $pendingProgress = $this->progress($pendingApplication->id, $module->id);
        $this->assertNotSame(ModuleProgress::STATUS_COMPLETED, $pendingProgress->status);
        $this->assertNull($pendingProgress->submitted_at);

        $completedProgress = $this->progress($completedApplication->id, $module->id);
        $this->assertTrue($completedProgress->isTrainerValidated());
        $this->assertNotNull($completedProgress->completed_at);
        $this->assertFalse($newQuiz->fresh()->targets($completedApplication));
        $this->assertTrue($newQuiz->fresh()->targets($pendingApplication));

        $this->actingAs($completedUser)
            ->get(route('trainee.modules.index'))
            ->assertOk()
            ->assertSee('Completed')
            ->assertSee('Basic Caregiving')
            ->assertSee('Review');

        $this->actingAs($completedUser)
            ->get(route('trainee.quizzes.show', $newQuiz))
            ->assertNotFound();
    }

    private function publishedQuiz(int $trainerId, TrainingModule $module, int $attemptLimit = 1): Quiz
    {
        $submodule = $this->lmsSubmodule($module);
        $quiz = Quiz::create([
            'trainer_id' => $trainerId,
            'training_batch_id' => $module->training_batch_id,
            'target_enrollment_application_id' => $module->target_enrollment_application_id,
            'training_module_id' => $module->id,
            'training_submodule_id' => $submodule->id,
            'title' => $module->title.' Classwork',
            'is_published' => true,
            'published_at' => now(),
            'attempt_limit' => $attemptLimit,
            'passing_score_percent' => 75,
        ]);

        QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'type' => QuizQuestion::TYPE_TRUE_FALSE,
            'prompt' => 'Required check',
            'options' => ['True', 'False'],
            'correct_option' => 0,
            'points' => 10,
            'position' => 0,
        ]);

        return $quiz;
    }

    private function gradedAttempt(
        Quiz $quiz,
        int $applicationId,
        int $attemptNumber,
        float $score,
        bool $passed,
    ): QuizAttempt {
        return QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'enrollment_application_id' => $applicationId,
            'attempt_number' => $attemptNumber,
            'status' => QuizAttempt::STATUS_GRADED,
            'earned_points' => $score / 10,
            'total_points' => 10,
            'score_percent' => $score,
            'passed' => $passed,
            'started_at' => now()->subMinutes(5),
            'submitted_at' => now(),
            'graded_at' => now(),
        ]);
    }

    private function progress(int $applicationId, int $moduleId): ModuleProgress
    {
        return ModuleProgress::query()
            ->where('enrollment_application_id', $applicationId)
            ->where('training_module_id', $moduleId)
            ->firstOrFail();
    }
}
