<?php

namespace Tests\Feature\Lms;

use App\Models\EnrollmentApplication;
use App\Models\Quiz;
use App\Notifications\LmsAnnouncementPublished;
use App\Notifications\LmsModulePublished;
use App\Notifications\LmsQuizPublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class TrainerNotificationAndQuizVisibilityTest extends TestCase
{
    use CreatesLmsTestData;
    use RefreshDatabase;

    public function test_trainer_announcement_notifies_trainees(): void
    {
        Notification::fake();

        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $student] = $this->lmsTrainee($batch);

        $this->actingAs($trainer)->post(route('trainer.announcements.store'), [
            'training_batch_id' => $batch->id,
            'kind' => 'announcement',
            'audience' => 'trainees',
            'title' => 'Important Class Schedule Update',
            'message' => 'Please note tomorrow sessions start at 8:30 AM.',
            'is_published' => '1',
        ])->assertRedirect(route('trainer.stream'));

        Notification::assertSentTo($student, LmsAnnouncementPublished::class, function ($notification) use ($student) {
            return $notification->via($student) === ['database', 'mail'];
        });
    }

    public function test_trainer_announcement_writes_inbox_notification_immediately(): void
    {
        Mail::fake();

        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $student] = $this->lmsTrainee($batch);

        $this->actingAs($trainer)->post(route('trainer.announcements.store'), [
            'training_batch_id' => $batch->id,
            'kind' => 'announcement',
            'audience' => 'trainees',
            'title' => 'Bring your workbook',
            'message' => 'Please bring the caregiving workbook tomorrow.',
            'is_published' => '1',
        ])->assertRedirect(route('trainer.stream'));

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $student->id,
            'notifiable_type' => $student->getMorphClass(),
            'type' => LmsAnnouncementPublished::class,
        ]);
        $this->assertSame(1, $student->unreadNotifications()->count());
    }

    public function test_trainer_module_dispatches_queued_mail_notification(): void
    {
        Storage::fake('local');
        Notification::fake();

        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $student] = $this->lmsTrainee($batch);

        $file = UploadedFile::fake()->create('sample-lesson.pdf', 128, 'application/pdf');

        $this->actingAs($trainer)->post(route('trainer.modules.store'), [
            'module_code' => 'HCS323301',
            'title' => 'Provide Care and Support to Infants',
            'description' => 'Detailed caregiving lesson module.',
            'audience_type' => 'batch',
            'training_batch_id' => $batch->id,
            'module_file' => $file,
            'is_published' => '1',
        ])->assertRedirect(route('trainer.resources'));

        Notification::assertSentTo($student, LmsModulePublished::class, function ($notification) use ($student) {
            return $notification->via($student) === ['database', 'mail']
                && $notification->queue === 'mail';
        });
    }

    public function test_trainer_quiz_publication_dispatches_queued_mail_notification(): void
    {
        Notification::fake();

        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $student] = $this->lmsTrainee($batch);
        ['user' => $graduate, 'application' => $graduateApplication] = $this->lmsTrainee($batch);
        $graduateApplication->update([
            'learning_status' => EnrollmentApplication::LEARNING_GRADUATED,
        ]);
        $module = $this->lmsModule($trainer, $batch);

        $quiz = Quiz::create([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'training_module_id' => $module->id,
            'title' => 'Module 1 Knowledge Check',
            'passing_score_percent' => 75,
            'attempt_limit' => 2,
            'is_published' => false,
        ]);

        $quiz->questions()->create([
            'type' => 'multiple_choice',
            'prompt' => 'Sample test question?',
            'options' => ['Correct', 'Wrong'],
            'correct_option' => 0,
            'points' => 1,
            'position' => 0,
        ]);

        $this->actingAs($trainer)
            ->patch(route('trainer.quizzes.publication', $quiz), ['is_published' => '1'])
            ->assertRedirect(route('trainer.modules.show', ['module' => $module, 'tab' => 'assessments']).'#assessments');

        Notification::assertSentTo($student, LmsQuizPublished::class, function ($notification) use ($student) {
            return $notification->via($student) === ['database', 'mail']
                && $notification->queue === 'mail';
        });
        Notification::assertNotSentTo($graduate, LmsQuizPublished::class);
        $this->assertSame([], (new LmsQuizPublished($quiz->fresh()))->via($graduate));
    }

    public function test_repeating_an_already_published_quiz_request_does_not_notify_twice(): void
    {
        Notification::fake();

        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch(['trainer_id' => $trainer->id]);
        ['user' => $student] = $this->lmsTrainee($batch);
        $module = $this->lmsModule($trainer, $batch);
        $quiz = Quiz::create([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'training_module_id' => $module->id,
            'title' => 'Already Published Quiz',
            'passing_score_percent' => 75,
            'attempt_limit' => 1,
            'is_published' => true,
            'published_at' => now(),
        ]);
        $quiz->questions()->create([
            'type' => 'multiple_choice',
            'prompt' => 'Existing question?',
            'options' => ['Yes', 'No'],
            'correct_option' => 0,
            'points' => 1,
            'position' => 0,
        ]);

        $this->actingAs($trainer)
            ->patch(route('trainer.quizzes.publication', $quiz), ['is_published' => '1'])
            ->assertRedirect();

        Notification::assertNotSentTo($student, LmsQuizPublished::class);
    }

    public function test_quizzes_are_visible_on_classwork_and_dashboard(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        $module = $this->lmsModule($trainer, $batch, ['title' => 'First Aid and Emergency Response']);

        $quiz = Quiz::create([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'training_module_id' => $module->id,
            'title' => 'First Aid Mastery Quiz',
            'passing_score_percent' => 80,
            'attempt_limit' => 2,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $quiz->questions()->create([
            'type' => 'multiple_choice',
            'prompt' => 'First step in CPR?',
            'options' => ['Check responsiveness', 'Call 911', 'Begin compressions'],
            'correct_option' => 0,
            'points' => 1,
            'position' => 0,
        ]);

        $response = $this->actingAs($trainer)->get(route('trainer.resources'));
        $response->assertOk();
        $response->assertSee('First Aid Mastery Quiz');
        $response->assertSee('Quizzes & Assessments', false);
        $response->assertSee('80%');
        $response->assertDontSee('Attached Assessments');

        $dashboardResponse = $this->actingAs($trainer)->get(route('trainer.dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('Quizzes & Assessments', false);
    }
}
