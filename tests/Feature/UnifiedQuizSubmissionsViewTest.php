<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplication;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\TrainingBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class UnifiedQuizSubmissionsViewTest extends TestCase
{
    use CreatesLmsTestData;
    use RefreshDatabase;

    protected function publishedQuiz(
        User $trainer,
        TrainingBatch $batch,
        array $overrides = [],
    ): Quiz {
        return Quiz::create(array_merge([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'title' => 'Caregiving safety quiz',
            'instructions' => 'Complete the quiz before its deadline.',
            'is_published' => true,
            'published_at' => now()->subHour(),
            'available_at' => now()->subHour(),
            'due_at' => now()->addWeek(),
            'time_limit_minutes' => 20,
            'attempt_limit' => 1,
            'passing_score_percent' => 75,
        ], $overrides));
    }

    public function test_admin_can_view_all_submitted_quizzes_in_unified_submissions_tab(): void
    {
        $admin = $this->lmsUser('admin');
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch(['name' => 'Batch 2026-Alpha']);
        ['user' => $trainee1, 'application' => $app1] = $this->lmsTrainee($batch, [
            'first_name' => 'Alice',
            'last_name' => 'Wonderland',
            'email' => 'alice@example.com',
        ]);
        ['user' => $trainee2, 'application' => $app2] = $this->lmsTrainee($batch, [
            'first_name' => 'Bob',
            'last_name' => 'Builder',
            'email' => 'bob@example.com',
        ]);

        $module = $this->lmsModule($trainer, $batch, ['title' => 'Caregiving Essentials', 'module_code' => 'CARE101']);
        $quiz = $this->publishedQuiz($trainer, $batch, [
            'training_module_id' => $module->id,
            'title' => 'Midterm Assessment',
            'passing_score' => 70,
        ]);

        // Attempt 1: Alice passed
        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'enrollment_application_id' => $app1->id,
            'attempt_number' => 1,
            'score' => 9,
            'total_questions' => 10,
            'score_percent' => 90.0,
            'passed' => true,
            'status' => QuizAttempt::STATUS_GRADED,
            'started_at' => now()->subHours(2),
            'submitted_at' => now()->subHour(),
            'answers' => [],
        ]);

        // Attempt 2: Bob failed
        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'enrollment_application_id' => $app2->id,
            'attempt_number' => 1,
            'score' => 5,
            'total_questions' => 10,
            'score_percent' => 50.0,
            'passed' => false,
            'status' => QuizAttempt::STATUS_GRADED,
            'started_at' => now()->subHours(3),
            'submitted_at' => now()->subHours(2),
            'answers' => [],
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.learning.modules', ['tab' => 'submissions']));

        $response->assertOk();
        $response->assertSee('Submitted Quizzes');
        $response->assertSee('Alice Wonderland');
        $response->assertSee('Bob Builder');
        $response->assertSee('Midterm Assessment');
        $response->assertSee('CARE101');
        $response->assertSee('Passed');
        $response->assertSee('Failed');
        $response->assertSee('90.0%');
        $response->assertSee('50.0%');

        // Test filtering by result: passed
        $filteredPassed = $this->actingAs($admin)
            ->get(route('admin.learning.modules', ['tab' => 'submissions', 'result' => 'passed']));
        $filteredPassed->assertOk();
        $filteredPassed->assertSee('Alice Wonderland');
        $filteredPassed->assertDontSee('Bob Builder');

        // Test filtering by search: Bob
        $filteredBob = $this->actingAs($admin)
            ->get(route('admin.learning.modules', ['tab' => 'submissions', 'search' => 'Bob']));
        $filteredBob->assertOk();
        $filteredBob->assertSee('Bob Builder');
        $filteredBob->assertDontSee('Alice Wonderland');
    }

    public function test_admin_can_view_individual_quiz_results_page(): void
    {
        $admin = $this->lmsUser('admin');
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $trainee, 'application' => $app] = $this->lmsTrainee($batch, [
            'first_name' => 'Charlie',
            'last_name' => 'Brown',
        ]);
        $module = $this->lmsModule($trainer, $batch);
        $quiz = $this->publishedQuiz($trainer, $batch, [
            'training_module_id' => $module->id,
            'title' => 'Final Exam',
        ]);

        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'enrollment_application_id' => $app->id,
            'attempt_number' => 1,
            'score' => 8,
            'total_questions' => 10,
            'score_percent' => 80.0,
            'passed' => true,
            'status' => QuizAttempt::STATUS_GRADED,
            'started_at' => now()->subHour(),
            'submitted_at' => now()->subMinutes(30),
            'answers' => [],
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.learning.quizzes.results', $quiz));

        $response->assertOk();
        $response->assertSee('Final Exam');
        $response->assertSee('Charlie Brown');
        $response->assertSee('80.0%');
        $response->assertSee(route('admin.learning.modules', ['tab' => 'submissions']));
    }

    public function test_trainer_can_view_all_student_quiz_submissions_in_resources_tab(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch(['name' => 'Batch 2026-Beta']);
        ['user' => $trainee, 'application' => $app] = $this->lmsTrainee($batch, [
            'first_name' => 'Diana',
            'last_name' => 'Prince',
            'email' => 'diana@example.com',
        ]);

        $module = $this->lmsModule($trainer, $batch, ['title' => 'First Aid & Safety']);
        $quiz = $this->publishedQuiz($trainer, $batch, [
            'training_module_id' => $module->id,
            'title' => 'CPR Evaluation Quiz',
        ]);

        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'enrollment_application_id' => $app->id,
            'attempt_number' => 1,
            'score' => 10,
            'total_questions' => 10,
            'score_percent' => 100.0,
            'passed' => true,
            'status' => QuizAttempt::STATUS_GRADED,
            'started_at' => now()->subMinutes(40),
            'submitted_at' => now()->subMinutes(10),
            'answers' => [],
        ]);

        $response = $this->actingAs($trainer)
            ->get(route('trainer.resources'));

        $response->assertOk();
        $response->assertSee('Quiz Submissions');
        $response->assertSee('Diana Prince');
        $response->assertSee('CPR Evaluation Quiz');
        $response->assertSee('100.0%');
        $response->assertSee('Passed');
    }

    public function test_non_admin_cannot_access_admin_quiz_results(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $trainee] = $this->lmsTrainee($batch);
        $module = $this->lmsModule($trainer, $batch);
        $quiz = $this->publishedQuiz($trainer, $batch, [
            'training_module_id' => $module->id,
        ]);

        $this->actingAs($trainee)
            ->get(route('admin.learning.quizzes.results', $quiz))
            ->assertForbidden();

        $this->get(route('admin.learning.quizzes.results', $quiz))
            ->assertForbidden();
    }
}
