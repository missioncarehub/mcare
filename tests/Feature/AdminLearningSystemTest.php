<?php

namespace Tests\Feature;

use App\Mail\StaffAccountCredentialsMail;
use App\Models\AdminActivityLog;
use App\Models\CompetencyOutcome;
use App\Models\CompetencyUnit;
use App\Models\EnrollmentApplication;
use App\Models\ModuleProgress;
use App\Models\PaymentTransaction;
use App\Models\TraineeCompetencyRecord;
use App\Models\TraineeOutcomeResult;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use App\Models\User;
use App\Notifications\LmsQuizPublished;
use App\Notifications\TrainerModuleAssignedByAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminLearningSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_learning_destinations_are_separate_and_accessible(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach ([
            'admin.learning.trainees',
            'admin.learning.modules',
            'admin.learning.certificates',
            'admin.learning.alumni-jobs',
            'admin.learning.reports',
        ] as $routeName) {
            $this->actingAs($admin)->get(route($routeName))->assertOk();
        }

        $this->actingAs($admin)
            ->get(route('admin.learning.alumni-jobs'))
            ->assertSee('data-dashboard-nav-key="admin-career-hub"', false)
            ->assertSee('aria-current="page"', false);
    }

    public function test_non_admin_cannot_open_admin_learning_destinations(): void
    {
        $trainee = User::factory()->create(['role' => 'trainee']);

        $this->actingAs($trainee)
            ->get(route('admin.learning.trainees'))
            ->assertForbidden();
    }

    public function test_large_admin_creation_forms_are_hidden_in_native_dialogs(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create([
            'role' => 'trainee',
            'avatar_url' => 'https://example.test/managed-trainee.jpg',
        ]);
        $photoTrainee = User::factory()->create(['role' => 'trainee']);
        $batch = TrainingBatch::create([
            'name' => 'Photo Batch',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);
        $photoApplication = $this->approvedApplication($photoTrainee, $batch);
        $photoPath = "enrollment-documents/{$photoTrainee->id}/id-photo.png";
        Storage::disk('local')->put($photoPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZkN0AAAAASUVORK5CYII='));
        $photoApplication->update(['id_photo_path' => $photoPath]);
        $photoUrl = route('admin.accounts.photo', $photoTrainee, absolute: false);

        $this->actingAs($admin)
            ->get(route('admin.accounts.index'))
            ->assertOk()
            ->assertSee('data-dashboard-dialog-open="trainer-account-dialog"', false)
            ->assertSee('<dialog id="trainer-account-dialog"', false)
            ->assertSee('data-dashboard-dialog-open="trainee-account-dialog"', false)
            ->assertSee('<dialog id="trainee-account-dialog"', false)
            ->assertDontSee('name="password"', false)
            ->assertDontSee('id="trainer-password"', false)
            ->assertDontSee('id="trainee-password"', false)
            ->assertSee('https://example.test/managed-trainee.jpg', false)
            ->assertSee($photoUrl, false);

        $this->actingAs($admin)
            ->get(route('admin.accounts.photo', $photoTrainee))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');

        $this->actingAs($photoTrainee)
            ->get(route('admin.accounts.photo', $photoTrainee))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.learning.alumni-jobs'))
            ->assertOk()
            ->assertSee('data-dashboard-dialog-open="career-opportunity-dialog"', false)
            ->assertSee('<dialog id="career-opportunity-dialog"', false)
            ->assertSee('career-form-layout', false);

        $this->actingAs($admin)
            ->get(route('admin.learning.modules'))
            ->assertOk()
            ->assertSee('Add a learning module')
            ->assertSee('lms-composer-form', false)
            ->assertSee('PDF or image')
            ->assertSee('.pdf,.jpg,.jpeg,.png,.webp,.gif', false)
            ->assertDontSee('Office, image, video, or audio')
            ->assertDontSee('Admin action');
    }

    public function test_admin_creation_validation_uses_the_matching_dialog_error_bag(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.accounts.trainers.store'), [])
            ->assertSessionHasErrors(['name', 'email'], null, 'trainer');

        $this->actingAs($admin)
            ->post(route('admin.learning.alumni-jobs.store'), [])
            ->assertSessionHasErrors([
                'title',
                'estimated_salary',
                'estimated_start_date',
                'patient_gender',
                'mobility_status',
            ], null, 'careerCreate');
    }

    public function test_admin_can_filter_and_update_a_trainee_learning_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $traineeUser = User::factory()->create(['role' => 'trainee']);
        $batch = TrainingBatch::create([
            'name' => 'Batch 8',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
            'training_starts_at' => now()->subWeek(),
            'training_ends_at' => now()->addMonths(3),
        ]);
        $application = $this->approvedApplication($traineeUser, $batch);

        $this->actingAs($admin)
            ->get(route('admin.learning.trainees', [
                'batch_id' => $batch->id,
                'schedule' => 'AM',
                'learning_status' => EnrollmentApplication::LEARNING_ACTIVE,
                'training_state' => 'in_progress',
                'joined_from' => now()->subDay()->toDateString(),
                'joined_to' => now()->addDay()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Roster filters')
            ->assertSee('Delete')
            ->assertSee('View details')
            ->assertSee($application->email);

        $this->actingAs($admin)
            ->get(route('admin.learning.trainees.show', $application))
            ->assertOk()
            ->assertSee('Pause')
            ->assertSee('Graduate')
            ->assertSee('Delete')
            ->assertSee($application->email);

        $this->actingAs($admin)
            ->patch(route('admin.learning.trainees.status', $application), [
                'learning_status' => EnrollmentApplication::LEARNING_PAUSED,
                'learning_status_notes' => 'Paused while learner confirms availability.',
            ])
            ->assertRedirect()
            ->assertSessionHas('saved');

        $this->assertDatabaseHas('enrollment_applications', [
            'id' => $application->id,
            'learning_status' => EnrollmentApplication::LEARNING_PAUSED,
            'learning_status_changed_by_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $traineeUser->id,
            'trainee_status' => EnrollmentApplication::LEARNING_PAUSED,
        ]);
        $this->assertTrue(AdminActivityLog::query()
            ->where('action', 'trainee.learning-status.updated')
            ->where('subject_id', $application->id)
            ->exists());

        $this->actingAs($admin)
            ->get(route('admin.learning.trainees.show', $application))
            ->assertOk()
            ->assertSee($application->email)
            ->assertSee('Resume');
    }

    public function test_non_admin_cannot_change_a_trainee_learning_status(): void
    {
        $trainee = User::factory()->create(['role' => 'trainee']);
        $batch = TrainingBatch::create([
            'name' => 'Batch 9',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);
        $application = $this->approvedApplication($trainee, $batch);

        $this->actingAs($trainee)
            ->patch(route('admin.learning.trainees.status', $application), [
                'learning_status' => EnrollmentApplication::LEARNING_GRADUATED,
            ])
            ->assertForbidden();
    }

    public function test_admin_can_delete_a_trainee_and_related_records_from_the_roster(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create([
            'role' => 'trainee',
            'name' => 'Lifecycle Trainee',
            'email' => 'lifecycle.trainee@gmail.com',
        ]);
        $batch = TrainingBatch::create([
            'name' => 'Batch Delete',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);
        $application = $this->approvedApplication($trainee, $batch);
        $photoPath = "enrollment-documents/{$trainee->id}/id-photo.png";
        Storage::disk('local')->put($photoPath, 'id-photo');
        $application->update(['id_photo_path' => $photoPath]);

        $this->actingAs($admin)
            ->from(route('admin.learning.trainees'))
            ->delete(route('admin.learning.trainees.destroy', $application))
            ->assertRedirect(route('admin.learning.trainees', ['tab' => 'current']))
            ->assertSessionHas('saved');

        $this->assertDatabaseMissing('users', ['id' => $trainee->id]);
        $this->assertDatabaseMissing('enrollment_applications', ['id' => $application->id]);
        $this->assertFalse(Storage::disk('local')->exists($photoPath));
        $this->assertTrue(AdminActivityLog::query()
            ->where('action', 'admin.account.deleted')
            ->where('subject_id', $trainee->id)
            ->exists());
    }

    public function test_non_admin_cannot_delete_a_trainee_from_the_roster(): void
    {
        $trainee = User::factory()->create(['role' => 'trainee']);
        $batch = TrainingBatch::create([
            'name' => 'Batch Delete Guard',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);
        $application = $this->approvedApplication($trainee, $batch);

        $this->actingAs($trainee)
            ->delete(route('admin.learning.trainees.destroy', $application))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $trainee->id]);
        $this->assertDatabaseHas('enrollment_applications', ['id' => $application->id]);
    }

    public function test_admin_can_delete_a_historical_alumni_trainee_after_the_alumni_claim_warning(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $batch = TrainingBatch::create([
            'name' => 'Historical Batch',
            'year' => 2020,
            'is_active' => true,
            'enrollment_ends_at' => now()->subYear(),
        ]);
        $application = $this->approvedApplication($trainee, $batch);
        $application->update([
            'is_historical_record' => true,
            'intake_channel' => 'historical_alumni',
            'learning_status' => EnrollmentApplication::LEARNING_GRADUATED,
            'training_batch_id' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.learning.trainees', ['tab' => 'graduated']))
            ->assertOk()
            ->assertSee($trainee->email)
            ->assertSee('Delete verified alumni record?')
            ->assertSee('verified historical alumni claim')
            ->assertSee('uploaded certificate or TOR evidence')
            ->assertSee('Delete alumni record');

        $this->actingAs($admin)
            ->from(route('admin.learning.trainees', ['tab' => 'graduated']))
            ->delete(route('admin.learning.trainees.destroy', $application))
            ->assertRedirect(route('admin.learning.trainees', ['tab' => 'graduated']))
            ->assertSessionHas('saved');

        $this->assertDatabaseMissing('users', ['id' => $trainee->id]);
        $this->assertDatabaseMissing('enrollment_applications', ['id' => $application->id]);
    }

    public function test_graduation_unlocks_career_hub_on_the_same_trainee_account_and_a_correction_locks_it_again(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $batch = TrainingBatch::create([
            'name' => 'Batch Alumni Transition',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);
        $application = $this->approvedApplication($trainee, $batch);
        $this->makeEligibleForGraduation($application, $admin);
        $trainee->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => LmsQuizPublished::class,
            'data' => [
                'title' => 'Old quiz notification',
                'quiz_id' => 999,
            ],
        ]);

        $this->actingAs($admin)
            ->from(route('admin.accounts.photo', $trainee))
            ->patch(route('admin.learning.trainees.status', $application), [
                'learning_status' => EnrollmentApplication::LEARNING_GRADUATED,
            ])
            ->assertRedirect(route('admin.learning.trainees.show', $application))
            ->assertSessionHas('saved');

        $this->assertDatabaseHas('users', [
            'id' => $trainee->id,
            'role' => 'trainee',
            'trainee_status' => EnrollmentApplication::LEARNING_GRADUATED,
        ]);
        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $trainee->id,
            'type' => LmsQuizPublished::class,
        ]);
        $this->actingAs($trainee->fresh())
            ->get(route('alumni.dashboard'))
            ->assertOk();

        $this->actingAs($admin)
            ->patch(route('admin.learning.trainees.status', $application->fresh()), [
                'learning_status' => EnrollmentApplication::LEARNING_ACTIVE,
                'learning_status_notes' => 'Graduation was recorded in error.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $trainee->id,
            'role' => 'trainee',
            'trainee_status' => EnrollmentApplication::LEARNING_ACTIVE,
        ]);
        $this->actingAs($trainee->fresh())
            ->get(route('alumni.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_trainee_roster_opens_a_details_page_for_payment_module_and_assessment_summary(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $traineeUser = User::factory()->create(['role' => 'trainee']);
        $trainer = User::factory()->create(['role' => 'trainer']);
        $batch = TrainingBatch::create([
            'name' => 'Batch Summary',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);
        $application = $this->approvedApplication($traineeUser, $batch);
        $application->update([
            'payment_method' => 'online',
            'payment_status' => EnrollmentApplication::PAYMENT_PAID,
            'payment_amount' => 2000,
            'payment_currency' => 'PHP',
            'payment_reference' => 'MCARE-SUMMARY-001',
            'payment_verified_at' => now(),
        ]);
        $module = TrainingModule::create([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'title' => 'Completed Summary Module',
            'description' => 'Used to verify the admin trainee summary.',
            'file_path' => 'training-modules/summary.pdf',
            'original_file_name' => 'summary.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'is_published' => true,
            'published_at' => now(),
        ]);
        ModuleProgress::create([
            'enrollment_application_id' => $application->id,
            'training_module_id' => $module->id,
            'status' => ModuleProgress::STATUS_COMPLETED,
            'progress_percent' => 100,
            'assigned_at' => now(),
            'unlocked_at' => now(),
            'last_viewed_at' => now(),
            'completed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.learning.trainees'))
            ->assertOk()
            ->assertSee('data-trainee-roster', false)
            ->assertSee('data-trainee-card', false)
            ->assertSee('dashboard-table', false)
            ->assertSee('Current trainees')
            ->assertSee('Graduates')
            ->assertSee('View details')
            ->assertSee(route('admin.learning.trainees.show', $application, absolute: false), false)
            ->assertSee('data-dashboard-sidebar-collapse', false)
            ->assertDontSee('data-dashboard-menu-open', false)
            ->assertDontSee('data-trainee-row-toggle', false);

        $this->actingAs($admin)
            ->get(route('admin.learning.trainees.show', $application))
            ->assertOk()
            ->assertSee('Back to roster')
            ->assertSee('Online payment')
            ->assertSee('1 of 1 published modules')
            ->assertSee('Ready for trainer assessment')
            ->assertSee('Assessment result: Not recorded yet');
    }

    public function test_admin_trainee_roster_lists_graduates_in_a_separate_table_tab(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $activeUser = User::factory()->create(['role' => 'trainee', 'email' => 'active.roster@example.test']);
        $graduateUser = User::factory()->create(['role' => 'trainee', 'email' => 'graduate.roster@example.test']);
        $batch = TrainingBatch::create([
            'name' => 'Batch Tabs',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);
        $active = $this->approvedApplication($activeUser, $batch);
        $graduate = $this->approvedApplication($graduateUser, $batch);
        $graduate->update(['learning_status' => EnrollmentApplication::LEARNING_GRADUATED]);

        $this->actingAs($admin)
            ->get(route('admin.learning.trainees'))
            ->assertOk()
            ->assertSee('lms-context-tabs', false)
            ->assertSee('Current trainees')
            ->assertSee($active->email)
            ->assertDontSee($graduate->email);

        $this->actingAs($admin)
            ->get(route('admin.learning.trainees', ['tab' => 'graduated']))
            ->assertOk()
            ->assertSee($graduate->email)
            ->assertDontSee($active->email)
            ->assertSee('aria-current="page"', false);
    }

    public function test_admin_can_add_and_remove_a_training_module(): void
    {
        Notification::fake();
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $trainer = User::factory()->create(['role' => 'trainer']);
        $batchTrainer = User::factory()->create(['role' => 'trainer']);
        $batch = TrainingBatch::create([
            'name' => 'Batch 10',
            'year' => 2026,
            'is_active' => true,
            'trainer_id' => $batchTrainer->id,
            'enrollment_ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($admin)->post(route('admin.learning.modules.store'), [
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'module_code' => 'HCS323302',
            'title' => 'Provide Care and Support to Children',
            'topic' => 'Bathe and dress children',
            'description' => 'A module uploaded by the administrator.',
            'module_file' => UploadedFile::fake()->create('lesson.pdf', 100, 'application/pdf'),
            'is_published' => '1',
        ])->assertRedirect(route('admin.learning.modules'))->assertSessionHas('saved');

        $module = TrainingModule::query()->where('title', 'Provide Care and Support to Children')->firstOrFail();
        $this->assertEquals('HCS323302', $module->module_code);
        $this->assertEquals('Bathe and dress children', $module->topic);
        $this->assertTrue(Storage::disk('local')->exists($module->file_path));

        $this->actingAs($trainer)
            ->get(route('trainer.resources'))
            ->assertOk()
            ->assertSee('Provide Care and Support to Children');

        Notification::assertSentTo(
            $trainer,
            TrainerModuleAssignedByAdmin::class,
            fn (TrainerModuleAssignedByAdmin $notification): bool => $notification->module->is($module)
                && $notification->via($trainer) === ['database', 'mail']
                && $notification->queue === 'mail',
        );

        $this->actingAs($admin)
            ->get(route('admin.learning.modules.preview', $module))
            ->assertOk()
            ->assertSee('data-module-file-preview', false)
            ->assertSee('data-pdf-fit-mode="page"', false);

        $this->actingAs($admin)
            ->get(route('admin.learning.modules.content', $module))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($admin)
            ->delete(route('admin.learning.modules.destroy', $module), [
                'confirmation' => 'DELETE',
            ])
            ->assertRedirect()
            ->assertSessionHas('saved');

        $this->assertDatabaseMissing('training_modules', ['id' => $module->id]);
        $this->assertFalse(Storage::disk('local')->exists($module->file_path));
    }

    public function test_module_create_form_posts_to_a_store_path_instead_of_the_list_url(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertNotSame(
            route('admin.learning.modules'),
            route('admin.learning.modules.store'),
        );

        $this->actingAs($admin)
            ->get(route('admin.learning.modules'))
            ->assertOk()
            ->assertSee('action="'.route('admin.learning.modules.store').'"', false);
    }

    public function test_saving_a_module_returns_to_the_list_instead_of_a_file_url(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $trainer = User::factory()->create(['role' => 'trainer']);
        $batch = TrainingBatch::create([
            'name' => 'Batch Redirect',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);

        Storage::disk('local')->put('training-modules/existing.pdf', '%PDF-1.4');
        $existing = TrainingModule::create([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'title' => 'Existing preview module',
            'description' => 'Opened in an iframe before adding another module.',
            'file_path' => 'training-modules/existing.pdf',
            'original_file_name' => 'existing.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 8,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.learning.modules'))
            ->get(route('admin.learning.modules.content', $existing))
            ->assertOk();

        $this->actingAs($admin)
            ->post(route('admin.learning.modules.store'), [])
            ->assertRedirect(route('admin.learning.modules'))
            ->assertSessionHasErrors(['trainer_id', 'training_batch_id', 'title', 'description', 'module_file']);

        $this->actingAs($admin)
            ->from(route('admin.learning.modules.content', $existing))
            ->post(route('admin.learning.modules.store'), [
                'trainer_id' => $trainer->id,
                'training_batch_id' => $batch->id,
                'module_code' => 'HCS233302',
                'title' => 'Provide Care and Support to Children',
                'description' => 'Added after previewing another module file.',
                'module_file' => UploadedFile::fake()->create('chapter.pdf', 100, 'application/pdf'),
                'is_published' => '1',
            ])
            ->assertRedirect(route('admin.learning.modules'))
            ->assertSessionHas('saved');
    }

    public function test_admin_module_composer_shows_and_saves_competency_outcomes_like_trainer_classwork(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $trainer = User::factory()->create(['role' => 'trainer']);
        $batch = TrainingBatch::create([
            'name' => 'Outcome Composer Batch',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.learning.modules'))
            ->assertOk()
            ->assertSee('Submodules / Competency Outcomes')
            ->assertSee('name="submodule_titles[]"', false)
            ->assertSee('data-role="module-submodule-list"', false)
            ->assertSee('data-outcomes=', false);

        $this->actingAs($admin)->post(route('admin.learning.modules.store'), [
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'module_code' => 'MCARE-CUSTOM-OUTCOMES',
            'competency_category' => 'custom',
            'completion_mode' => 'assessed',
            'title' => 'Custom assessed housekeeping',
            'description' => 'Admin-created custom module with outcomes.',
            'submodule_titles' => ['Prepare cleaning cart', 'Sanitize high-touch surfaces'],
            'module_file' => UploadedFile::fake()->create('lesson.pdf', 100, 'application/pdf'),
            'is_published' => '0',
        ])->assertRedirect()->assertSessionHas('saved');

        $module = TrainingModule::query()->where('title', 'Custom assessed housekeeping')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            ['Prepare cleaning cart', 'Sanitize high-touch surfaces'],
            $module->submodules()->orderBy('position')->pluck('title')->all(),
        );
    }

    public function test_admin_can_edit_a_training_module_from_the_modules_table(): void
    {
        Notification::fake();
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $trainer = User::factory()->create(['role' => 'trainer']);
        $nextTrainer = User::factory()->create(['role' => 'trainer']);
        $batch = TrainingBatch::create([
            'name' => 'Batch 12',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($admin)->post(route('admin.learning.modules.store'), [
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'module_code' => 'HCS323301',
            'title' => 'Provide Care and Support to Infants and Toddlers',
            'topic' => 'Original topic',
            'description' => 'Original description.',
            'module_file' => UploadedFile::fake()->create('lesson.pdf', 100, 'application/pdf'),
            'is_published' => '1',
        ])->assertRedirect()->assertSessionHas('saved');

        $module = TrainingModule::query()->where('title', 'Provide Care and Support to Infants and Toddlers')->firstOrFail();
        $originalPath = $module->file_path;

        $this->actingAs($admin)
            ->get(route('admin.learning.modules'))
            ->assertOk()
            ->assertSee('data-dashboard-dialog-open="edit-module-'.$module->id.'"', false)
            ->assertSee('aria-label="Edit module"', false)
            ->assertSee('id="edit-module-'.$module->id.'"', false);

        $this->actingAs($admin)
            ->from(route('admin.learning.modules'))
            ->patch(route('admin.learning.modules.update', $module), [
                '_editing_module_id' => $module->id,
                'trainer_id' => $nextTrainer->id,
                'training_batch_id' => $batch->id,
                'module_code' => 'HCS323301',
                'competency_category' => 'core',
                'completion_mode' => TrainingModule::COMPLETION_ASSESSED,
                'title' => 'Updated infant care module',
                'topic' => 'Comfort infants and toddlers',
                'description' => 'Updated by the administrator.',
                'is_published' => '1',
            ])
            ->assertRedirect(route('admin.learning.modules'))
            ->assertSessionHas('saved');

        $module->refresh();
        $this->assertSame('Updated infant care module', $module->title);
        $this->assertSame('Comfort infants and toddlers', $module->topic);
        $this->assertSame($nextTrainer->id, $module->trainer_id);
        $this->assertSame($originalPath, $module->file_path);
        $this->assertTrue(Storage::disk('local')->exists($module->file_path));

        Notification::assertSentTo($nextTrainer, TrainerModuleAssignedByAdmin::class);
    }

    public function test_admin_can_create_trainer_and_approved_trainee_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $batch = TrainingBatch::create([
            'name' => 'Batch 11',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);

        Mail::fake();

        $this->actingAs($admin)->post(route('admin.accounts.trainers.store'), [
            'name' => 'New Trainer',
            'email' => 'new.trainer@example.test',
        ])->assertRedirect()->assertSessionHas('saved');

        $this->actingAs($admin)->post(route('admin.accounts.trainees.store'), [
            'first_name' => 'New',
            'middle_name' => 'M',
            'last_name' => 'Trainee',
            'email' => 'new.trainee@example.test',
            'training_batch_id' => $batch->id,
            'birth_date' => '2001-01-01',
            'gender' => 'Female',
            'contact_number' => '09170001111',
            'schedule_preference' => 'AM',
            'street' => '11 Training Street',
            'barangay' => 'Central',
            'city' => 'Iriga City',
            'province' => 'Camarines Sur',
            'zip_code' => '4431',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'MCARE School',
            'year_graduated' => 2023,
            'birth_certificate_onsite' => '1',
            'education_document_onsite' => '1',
            'good_moral_onsite' => '1',
            'id_photo_onsite' => '1',
            'signature_onsite' => '1',
            'privacy_consent_onsite' => '1',
            'onsite_payment_received' => '1',
            'onsite_payment_amount' => '2000.00',
            'onsite_or_number' => 'OR-ASSISTED-001',
            'onsite_verification_notes' => 'Original requirements, consent, and downpayment receipt were verified onsite.',
        ])->assertRedirect()->assertSessionHas('saved');

        $this->assertDatabaseHas('users', ['email' => 'new.trainer@example.test', 'role' => 'trainer']);
        $this->assertDatabaseHas('users', [
            'email' => 'new.trainee@example.test',
            'role' => 'trainee',
            'first_name' => 'New',
            'last_name' => 'Trainee',
            'contact_email' => 'new.trainee@example.test',
            'contact_number' => '09170001111',
            'gender' => 'Female',
            'city' => 'Iriga City',
            'trainee_status' => EnrollmentApplication::LEARNING_ACTIVE,
        ]);
        $this->assertDatabaseHas('enrollment_applications', [
            'email' => 'new.trainee@example.test',
            'training_batch_id' => $batch->id,
            'status' => EnrollmentApplication::STATUS_APPROVED,
            'intake_channel' => 'admin_assisted',
            'payment_verified_by_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('payment_transactions', [
            'user_id' => User::query()->where('email', 'new.trainee@example.test')->value('id'),
            'or_number' => 'OR-ASSISTED-001',
            'status' => PaymentTransaction::STATUS_VERIFIED,
        ]);

        $trainer = User::query()->where('email', 'new.trainer@example.test')->firstOrFail();
        $trainee = User::query()->where('email', 'new.trainee@example.test')->firstOrFail();

        Mail::assertSent(StaffAccountCredentialsMail::class, 2);
        Mail::assertSent(StaffAccountCredentialsMail::class, function (StaffAccountCredentialsMail $mail) use ($trainer): bool {
            return $mail->hasTo($trainer->email)
                && $mail->user->is($trainer)
                && Hash::check($mail->plainPassword, $trainer->password);
        });
        Mail::assertSent(StaffAccountCredentialsMail::class, function (StaffAccountCredentialsMail $mail) use ($trainee): bool {
            return $mail->hasTo($trainee->email)
                && $mail->user->is($trainee)
                && Hash::check($mail->plainPassword, $trainee->password);
        });
    }

    public function test_admin_can_export_the_filtered_trainee_roster_for_excel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $batch = TrainingBatch::create([
            'name' => 'Batch Export',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);
        $application = $this->approvedApplication($trainee, $batch);

        $response = $this->actingAs($admin)->get(route('admin.learning.trainees.export', [
            'batch_id' => $batch->id,
            'schedule' => 'AM',
        ]));

        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString($application->email, $response->streamedContent());
    }

    private function approvedApplication(User $user, TrainingBatch $batch): EnrollmentApplication
    {
        return EnrollmentApplication::create([
            'user_id' => $user->id,
            'training_batch_id' => $batch->id,
            'email' => $user->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Lifecycle',
            'last_name' => 'Trainee',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => '1 Training Street',
            'barangay' => 'Central',
            'city' => 'Iriga City',
            'province' => 'Camarines Sur',
            'zip_code' => '4431',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'MCARE School',
            'year_graduated' => 2022,
            'status' => EnrollmentApplication::STATUS_APPROVED,
            'learning_status' => EnrollmentApplication::LEARNING_ACTIVE,
            'reviewed_at' => now(),
            'learning_started_at' => now(),
        ]);
    }

    private function makeEligibleForGraduation(EnrollmentApplication $application, User $assessor): void
    {
        $application->update([
            'payment_status' => EnrollmentApplication::PAYMENT_PAID,
            'learning_started_at' => now(),
        ]);

        CompetencyUnit::query()
            ->where('category', TrainingModule::CATEGORY_CORE)
            ->where('is_required', true)
            ->with('outcomes')
            ->each(function (CompetencyUnit $unit) use ($application, $assessor): void {
                $record = TraineeCompetencyRecord::create([
                    'enrollment_application_id' => $application->id,
                    'competency_unit_id' => $unit->id,
                    'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
                    'percentage_score' => 95,
                    'assessed_by_id' => $assessor->id,
                    'assessed_at' => now(),
                ]);

                foreach ($unit->outcomes as $outcome) {
                    TraineeOutcomeResult::create([
                        'trainee_competency_record_id' => $record->id,
                        'competency_outcome_id' => $outcome->id,
                        'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
                        'assessed_by_id' => $assessor->id,
                        'assessed_at' => now(),
                    ]);
                }

                $module = TrainingModule::create([
                    'trainer_id' => $assessor->id,
                    'training_batch_id' => $application->training_batch_id,
                    'module_code' => $unit->code,
                    'competency_category' => TrainingModule::CATEGORY_CORE,
                    'title' => $unit->title,
                    'description' => 'Completed required core delivery.',
                    'file_path' => "training-modules/testing/{$unit->code}.pdf",
                    'original_file_name' => "{$unit->code}.pdf",
                    'is_published' => true,
                    'delivery_status' => TrainingModule::DELIVERY_CLOSED,
                    'published_at' => now()->subDay(),
                    'activated_at' => now()->subDay(),
                    'closed_at' => now(),
                ]);

                ModuleProgress::create([
                    'enrollment_application_id' => $application->id,
                    'training_module_id' => $module->id,
                    'status' => ModuleProgress::STATUS_COMPLETED,
                    'progress_percent' => 100,
                    'assigned_at' => now()->subDay(),
                    'unlocked_at' => now()->subDay(),
                    'submitted_at' => now(),
                    'competency_outcome' => ModuleProgress::OUTCOME_COMPETENT,
                    'evaluated_by_id' => $assessor->id,
                    'evaluated_at' => now(),
                    'completed_at' => now(),
                ]);
            });
    }

    public function test_admin_catalog_presets_are_stored_and_shown_to_trainers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainer = User::factory()->create(['role' => 'trainer']);

        $this->actingAs($admin)
            ->get(route('admin.learning.modules'))
            ->assertOk()
            ->assertSee('Units')
            ->assertSee('Outcomes')
            ->assertDontSee('id="catalog-units-title"', false)
            ->assertDontSee('id="catalog-outcomes-title"', false);

        $this->actingAs($admin)
            ->get(route('admin.learning.modules', ['tab' => 'presets']))
            ->assertOk()
            ->assertSee('Competency units')
            ->assertSee('HCS323301')
            ->assertSee('Provide Care and Support to Infants and Toddlers');

        $this->actingAs($admin)
            ->from(route('admin.learning.modules', ['tab' => 'units']))
            ->post(route('admin.learning.modules.presets.store'), [
                'category' => 'custom',
                'code' => 'MCARE-HOUSEKEEPING',
                'title' => 'Institutional Housekeeping Drill',
                'estimated_hours' => 8,
                'outcomes' => "Prepare cleaning cart\nSanitize high-touch surfaces",
                'is_selectable' => '1',
                'is_tor_included' => '0',
            ])
            ->assertRedirect(route('admin.learning.modules', ['tab' => 'units']))
            ->assertSessionHas('saved');

        $this->assertDatabaseHas('competency_units', [
            'code' => 'MCARE-HOUSEKEEPING',
            'title' => 'Institutional Housekeeping Drill',
            'category' => 'custom',
            'estimated_hours' => 8,
            'is_selectable' => 1,
        ]);

        $unit = CompetencyUnit::query()->where('code', 'MCARE-HOUSEKEEPING')->firstOrFail();
        $this->assertSame(
            ['Prepare cleaning cart', 'Sanitize high-touch surfaces'],
            $unit->outcomes()->orderBy('sort_order')->pluck('title')->all(),
        );

        $this->actingAs($trainer)
            ->get(route('trainer.resources'))
            ->assertOk()
            ->assertSee('data-code="MCARE-HOUSEKEEPING"', false)
            ->assertSee('Institutional Housekeeping Drill');

        $this->actingAs($admin)
            ->from(route('admin.learning.modules', ['tab' => 'units']))
            ->patch(route('admin.learning.modules.presets.update', $unit), [
                'category' => 'custom',
                'code' => 'MCARE-HOUSEKEEPING',
                'title' => 'Institutional Housekeeping Drill',
                'estimated_hours' => 8,
                'outcomes' => "Prepare cleaning cart\nSanitize high-touch surfaces",
                'is_selectable' => '0',
                'is_tor_included' => '0',
            ])
            ->assertRedirect(route('admin.learning.modules', ['tab' => 'units']))
            ->assertSessionHas('saved');

        $this->assertDatabaseHas('competency_units', [
            'id' => $unit->id,
            'is_selectable' => 0,
        ]);

        $this->actingAs($trainer)
            ->get(route('trainer.resources'))
            ->assertOk()
            ->assertDontSee('data-code="MCARE-HOUSEKEEPING"', false);
    }

    public function test_admin_can_add_edit_and_delete_catalog_units_and_outcomes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $unit = CompetencyUnit::query()->where('code', 'HCS323301')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.learning.modules', ['tab' => 'outcomes', 'unit_id' => $unit->id]))
            ->assertOk()
            ->assertSee('Competency outcomes')
            ->assertSee('Obtain and convey workplace information');

        $this->actingAs($admin)
            ->from(route('admin.learning.modules', ['tab' => 'outcomes', 'unit_id' => $unit->id]))
            ->post(route('admin.learning.modules.outcomes.store'), [
                'competency_unit_id' => $unit->id,
                'title' => 'Document infant feeding records',
                'is_required' => '1',
            ])
            ->assertRedirect(route('admin.learning.modules', ['tab' => 'outcomes', 'unit_id' => $unit->id]))
            ->assertSessionHas('saved');

        $outcome = CompetencyOutcome::query()
            ->where('competency_unit_id', $unit->id)
            ->where('title', 'Document infant feeding records')
            ->firstOrFail();

        $this->actingAs($admin)
            ->from(route('admin.learning.modules', ['tab' => 'outcomes', 'edit_outcome' => $outcome->id]))
            ->patch(route('admin.learning.modules.outcomes.update', $outcome), [
                'competency_unit_id' => $unit->id,
                'title' => 'Document infant feeding and rest records',
                'is_required' => '0',
            ])
            ->assertRedirect(route('admin.learning.modules', ['tab' => 'outcomes', 'unit_id' => $unit->id]))
            ->assertSessionHas('saved');

        $this->assertDatabaseHas('competency_outcomes', [
            'id' => $outcome->id,
            'title' => 'Document infant feeding and rest records',
            'is_required' => 0,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.learning.modules', ['tab' => 'outcomes', 'unit_id' => $unit->id]))
            ->delete(route('admin.learning.modules.outcomes.destroy', $outcome))
            ->assertRedirect(route('admin.learning.modules', ['tab' => 'outcomes', 'unit_id' => $unit->id]))
            ->assertSessionHas('saved');

        $this->assertDatabaseMissing('competency_outcomes', [
            'id' => $outcome->id,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.learning.modules', ['tab' => 'units']))
            ->post(route('admin.learning.modules.presets.store'), [
                'category' => 'custom',
                'code' => 'MCARE-TEMP-UNIT',
                'title' => 'Temporary catalog unit',
                'estimated_hours' => 4,
                'outcomes' => 'Temporary outcome',
                'is_selectable' => '0',
                'is_tor_included' => '0',
            ])
            ->assertRedirect(route('admin.learning.modules', ['tab' => 'units']));

        $temporary = CompetencyUnit::query()->where('code', 'MCARE-TEMP-UNIT')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('admin.learning.modules', ['tab' => 'units']))
            ->delete(route('admin.learning.modules.presets.destroy', $temporary))
            ->assertRedirect(route('admin.learning.modules', ['tab' => 'units']))
            ->assertSessionHas('saved');

        $this->assertDatabaseMissing('competency_units', [
            'id' => $temporary->id,
        ]);
    }

    public function test_admin_cannot_delete_a_competency_unit_used_by_a_module(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainer = User::factory()->create(['role' => 'trainer']);
        $batch = TrainingBatch::create([
            'name' => 'Catalog Guard Batch',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);
        $unit = CompetencyUnit::query()->where('code', 'HCS323301')->firstOrFail();

        TrainingModule::create([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'competency_unit_id' => $unit->id,
            'title' => 'Linked catalog module',
            'description' => 'Used to block unit deletion.',
            'file_path' => 'training-modules/catalog-guard.pdf',
            'original_file_name' => 'catalog-guard.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'is_published' => false,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.learning.modules', ['tab' => 'units']))
            ->delete(route('admin.learning.modules.presets.destroy', $unit))
            ->assertRedirect(route('admin.learning.modules', ['tab' => 'units']))
            ->assertSessionHasErrors('unit');

        $this->assertDatabaseHas('competency_units', [
            'id' => $unit->id,
            'code' => 'HCS323301',
        ]);
    }
}
