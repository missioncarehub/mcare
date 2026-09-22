<?php

namespace Tests\Feature;

use App\Mail\StaffAccountCredentialsMail;
use App\Models\CompetencyUnit;
use App\Models\EnrollmentApplication;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\TraineeCompetencyRecord;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use App\Models\User;
use App\Notifications\LmsAnnouncementPublished;
use App\Notifications\LmsModulePublished;
use App\Notifications\LmsQuizPublished;
use App\Notifications\QueuedVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class TrainerEndToEndLifecycleFlowTest extends TestCase
{
    use CreatesLmsTestData;
    use RefreshDatabase;

    public function test_complete_trainer_lifecycle_from_creation_to_delivery_and_grading(): void
    {
        Notification::fake();
        Mail::fake();
        Storage::fake('local');

        // Seeded Caregiving NC II Competency Units for grading test
        $unit = CompetencyUnit::query()->with('outcomes')->orderBy('sort_order')->firstOrFail();
        $outcome = $unit->outcomes->first();

        // =========================================================================
        // STEP 1: Admin creates trainer account with temporary credentials
        // =========================================================================
        $admin = $this->lmsUser('admin');
        $trainerEmail = 'trainer.mcare@gmail.com';

        $createTrainerResponse = $this->actingAs($admin)->post(route('admin.accounts.trainers.store'), [
            'name' => 'Prof. Eduardo Ramos',
            'email' => $trainerEmail,
        ]);

        $createTrainerResponse->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => $trainerEmail,
            'role' => 'trainer',
            'applicant_status' => 'staff_created',
        ]);

        $trainer = User::where('email', $trainerEmail)->firstOrFail();
        $this->assertFalse($trainer->hasVerifiedEmail());

        $trainerPassword = '';
        Mail::assertSent(StaffAccountCredentialsMail::class, function (StaffAccountCredentialsMail $mail) use ($trainer, &$trainerPassword): bool {
            if (! $mail->hasTo($trainer->email) || ! $mail->user->is($trainer)) {
                return false;
            }

            $trainerPassword = $mail->plainPassword;

            return filled($trainerPassword);
        });
        $this->assertNotSame('', $trainerPassword);

        // Verify that QueuedVerifyEmail notification was dispatched
        Notification::assertSentTo($trainer, QueuedVerifyEmail::class);

        // =========================================================================
        // STEP 2: Trainer verifies email address
        // =========================================================================
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $trainer->id, 'hash' => sha1($trainer->getEmailForVerification())]
        );

        $verifyResponse = $this->actingAs($trainer)->get($verificationUrl);
        $verifyResponse->assertRedirect();
        $trainer->refresh();
        $this->assertTrue($trainer->hasVerifiedEmail());

        // =========================================================================
        // STEP 3: Admin creates a Batch and assigns the verified Trainer
        // =========================================================================
        $createBatchResponse = $this->actingAs($admin)->post(route('admin.batches.store'), [
            'name' => 'Caregiving NC II Batch Alpha',
            'year' => 2026,
            'trainer_id' => $trainer->id,
            'is_active' => '1',
            'enrollment_starts_at' => now()->subDay()->format('Y-m-d\TH:i'),
            'enrollment_ends_at' => now()->addMonth()->format('Y-m-d\TH:i'),
            'training_starts_at' => now()->addMonth()->format('Y-m-d'),
            'training_ends_at' => now()->addMonths(4)->format('Y-m-d'),
            'am_start_time' => '08:00',
            'am_end_time' => '12:00',
            'am_room' => 'Skills Lab 1',
            'am_days' => 'MWF',
            'pm_start_time' => '13:00',
            'pm_end_time' => '17:00',
            'pm_room' => 'Lecture Room 2',
            'pm_days' => 'TTS',
        ]);

        $createBatchResponse->assertRedirect(route('admin.batches.index'));
        $batch = TrainingBatch::where('name', 'Caregiving NC II Batch Alpha')->firstOrFail();
        $this->assertEquals($trainer->id, $batch->trainer_id);
        $this->assertTrue($batch->is_active);

        // =========================================================================
        // STEP 4: Enrolled & Approved Trainee in the Batch
        // =========================================================================
        ['user' => $traineeUser, 'application' => $traineeApplication] = $this->lmsTrainee($batch, [
            'status' => EnrollmentApplication::STATUS_APPROVED,
        ]);

        // =========================================================================
        // STEP 5: Trainer logs in and accesses Dashboard
        // =========================================================================
        auth()->logout();
        $loginResponse = $this->post(route('login'), [
            'email' => $trainerEmail,
            'password' => $trainerPassword,
        ]);

        $loginResponse->assertRedirect(route('trainer.dashboard'));
        $this->assertAuthenticatedAs($trainer);

        $dashboardResponse = $this->actingAs($trainer)->get(route('trainer.dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('Caregiving NC II Batch Alpha');
        $dashboardResponse->assertSee('Delivery snapshot');

        // =========================================================================
        // STEP 6: Trainer posts an announcement to the batch stream
        // =========================================================================
        $announcementResponse = $this->actingAs($trainer)->post(route('trainer.announcements.store'), [
            'training_batch_id' => $batch->id,
            'kind' => 'announcement',
            'audience' => 'trainees',
            'title' => 'Welcome to Caregiving Batch Alpha',
            'message' => 'Please download Module 1 before tomorrow morning session.',
            'is_published' => '1',
        ]);

        $announcementResponse->assertRedirect(route('trainer.stream'));
        $this->assertDatabaseHas('trainer_announcements', [
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'title' => 'Welcome to Caregiving Batch Alpha',
            'is_published' => true,
        ]);

        Notification::assertSentTo($traineeUser, LmsAnnouncementPublished::class, function ($notification) {
            return $notification->queue === 'mail';
        });

        // =========================================================================
        // STEP 7: Trainer uploads a learning module with attachments
        // =========================================================================
        $primaryPdf = UploadedFile::fake()->create('module_1_infant_care.pdf', 512, 'application/pdf');
        $worksheetPdf = UploadedFile::fake()->create('worksheet_1.pdf', 256, 'application/pdf');

        $moduleResponse = $this->actingAs($trainer)->post(route('trainer.modules.store'), [
            'module_code' => 'HCS323301',
            'competency_category' => 'core',
            'title' => 'Provide Care and Support to Infants and Toddlers',
            'topic' => 'Comfort infants and toddlers',
            'estimated_hours' => 40,
            'description' => 'Comprehensive learning module for infant and toddler caregiving competencies.',
            'audience_type' => 'batch',
            'training_batch_id' => $batch->id,
            'module_file' => $primaryPdf,
            'supplementary_files' => [$worksheetPdf],
            'is_published' => '1',
        ]);

        $moduleResponse->assertRedirect(route('trainer.resources'));
        $this->assertDatabaseHas('training_modules', [
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'module_code' => 'HCS323301',
            'title' => 'Provide Care and Support to Infants and Toddlers',
            'is_published' => true,
        ]);

        $module = TrainingModule::where('module_code', 'HCS323301')->firstOrFail();
        $submodule = $module->fresh()->submodules()->firstOrFail();

        Notification::assertSentTo($traineeUser, LmsModulePublished::class, function ($notification) {
            return $notification->queue === 'mail';
        });

        // =========================================================================
        // STEP 8: Trainer creates and publishes an in-module quiz assessment
        // =========================================================================
        $quizResponse = $this->actingAs($trainer)->post(route('trainer.quizzes.store'), [
            'title' => 'Unit Assessment: Infant Care Competency',
            'training_module_id' => $module->id,
            'training_submodule_id' => $submodule->id,
            'audience_type' => 'batch',
            'training_batch_id' => $batch->id,
            'instructions' => 'Answer all multiple choice questions within 20 minutes.',
            'time_limit_minutes' => 20,
            'passing_score_percent' => 75,
            'attempt_limit' => 2,
            'is_published' => '1',
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'prompt' => 'What is the standard normal body temperature for an infant?',
                    'options' => ['36.5°C to 37.5°C', '34.0°C to 35.0°C', '38.5°C to 39.5°C'],
                    'correct_option' => 0,
                    'points' => 1,
                ],
                [
                    'type' => 'multiple_choice',
                    'prompt' => 'When soothing a crying infant, which technique is most effective?',
                    'options' => ['Gentle rocking and swaddling', 'Leaving in a dark room alone', 'Loud noises'],
                    'correct_option' => 0,
                    'points' => 1,
                ],
            ],
        ]);

        $quiz = Quiz::where('training_module_id', $module->id)->firstOrFail();
        $this->assertTrue($quiz->is_published);
        $this->assertEquals(2, $quiz->questions()->count());

        Notification::assertSentTo($traineeUser, LmsQuizPublished::class, function ($notification) {
            return $notification->queue === 'mail';
        });

        // =========================================================================
        // STEP 9: Trainee takes and submits the quiz attempt
        // =========================================================================
        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'enrollment_application_id' => $traineeApplication->id,
            'attempt_number' => 1,
            'status' => QuizAttempt::STATUS_GRADED,
            'score' => 2.0,
            'score_percent' => 100.0,
            'passed' => true,
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now(),
            'answers' => [
                ['question_id' => $quiz->questions[0]->id, 'selected_option' => 0, 'is_correct' => true, 'points_awarded' => 1],
                ['question_id' => $quiz->questions[1]->id, 'selected_option' => 0, 'is_correct' => true, 'points_awarded' => 1],
            ],
        ]);

        // Trainer reviews quiz results
        $resultsResponse = $this->actingAs($trainer)->get(route('trainer.quizzes.results', $quiz));
        $resultsResponse->assertOk();
        $resultsResponse->assertSee($traineeApplication->last_name);
        $resultsResponse->assertSee('100.0%');

        // =========================================================================
        // STEP 10: Trainer grades Competency Record for the Trainee
        // =========================================================================
        $outcomesMap = $unit->outcomes->mapWithKeys(fn ($o) => [
            (string) $o->id => TraineeCompetencyRecord::STATUS_COMPETENT,
        ])->all();

        $this->actingAs($traineeUser)
            ->patch(route('trainee.modules.progress', $module), ['action' => 'submit'])
            ->assertSessionHasNoErrors();

        $gradeResponse = $this->actingAs($trainer)->patch(route('trainer.competencies.update', $traineeApplication), [
            'records' => [
                [
                    'unit_id' => $unit->id,
                    'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
                    'percentage_score' => 95,
                    'notes' => 'Demonstrated complete mastery in infant comforting and care routines.',
                    'outcomes' => $outcomesMap,
                ],
            ],
        ]);

        $gradeResponse->assertRedirect();
        $this->assertDatabaseHas('trainee_competency_records', [
            'enrollment_application_id' => $traineeApplication->id,
            'competency_unit_id' => $unit->id,
            'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
        ]);

        // Trainer verifies competency overview
        $competencyIndexResponse = $this->actingAs($trainer)->get(route('trainer.competencies.index', ['batch_id' => $batch->id]));
        $competencyIndexResponse->assertOk();
        $competencyIndexResponse->assertSee('Competent');
    }
}
