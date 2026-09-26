<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrainerPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_trainer_login(): void
    {
        $this->get('/trainer')
            ->assertRedirect(route('login'));
    }

    public function test_non_trainer_cannot_open_trainer_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'applicant']);

        $this->actingAs($user)
            ->get('/trainer')
            ->assertForbidden();
    }

    public function test_trainer_can_view_dashboard_with_active_trainees(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        $applicant = User::factory()->create(['role' => 'applicant']);
        $batch = TrainingBatch::create([
            'name' => 'Batch 1',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
            'am_days' => 'MWF',
            'am_start_time' => '08:00',
            'am_end_time' => '12:00',
            'am_room' => 'Skills Lab A',
            'pm_days' => 'TTS',
            'pm_start_time' => '13:00',
            'pm_end_time' => '17:00',
            'pm_room' => 'Lecture Room 2',
        ]);

        EnrollmentApplication::create([
            'user_id' => $applicant->id,
            'training_batch_id' => $batch->id,
            'email' => 'trainee@gmail.com',
            'program' => 'Caregiving NC II',
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => '123 Training Street',
            'barangay' => 'Central',
            'city' => 'Pili',
            'province' => 'Camarines Sur',
            'zip_code' => '4418',
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'MCARE High School',
            'year_graduated' => 2020,
            'status' => EnrollmentApplication::STATUS_APPROVED,
            'learning_status' => EnrollmentApplication::LEARNING_GRADUATED,
        ]);

        $this->actingAs($trainer)
            ->get(route('trainer.dashboard'))
            ->assertOk()
            ->assertSee('Ana')
            ->assertSee('Graduated in Batch 1 2026')
            ->assertSee('MWF | 8:00 AM - 12:00 PM');

        $this->actingAs($trainer)
            ->get(route('trainer.trainees'))
            ->assertOk()
            ->assertSee('Graduated in Batch 1 2026');
    }

    public function test_trainer_can_upload_private_module(): void
    {
        Storage::fake('local');

        $trainer = User::factory()->create(['role' => 'trainer']);
        TrainingBatch::create([
            'name' => 'Batch 1',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
            'am_days' => 'MWF',
            'pm_days' => 'TTS',
        ]);

        $file = UploadedFile::fake()->create('infection-control.pdf', 128, 'application/pdf');

        $this->actingAs($trainer)
            ->post(route('trainer.modules.store'), [
                'module_code' => 'HCS323301',
                'title' => 'Provide Care and Support to Infants and Toddlers',
                'topic' => 'Comfort infants and toddlers',
                'description' => 'Safe infant care lesson for Caregiving NC II trainees.',
                'module_file' => $file,
            ])
            ->assertRedirect(route('trainer.resources'));

        $this->assertDatabaseHas('training_modules', [
            'trainer_id' => $trainer->id,
            'module_code' => 'HCS323301',
            'title' => 'Provide Care and Support to Infants and Toddlers',
            'topic' => 'Comfort infants and toddlers',
            'original_file_name' => 'infection-control.pdf',
        ]);

        $path = Storage::disk('local')->files("training-modules/{$trainer->id}")[0] ?? null;
        $this->assertNotNull($path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_each_trainer_sidebar_destination_has_its_own_page(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);

        foreach ([
            'trainer.dashboard',
            'trainer.trainings',
            'trainer.trainees',
            'trainer.sessions',
            'trainer.resources',
            'trainer.certificates',
            'trainer.reports',
        ] as $routeName) {
            $this->actingAs($trainer)->get(route($routeName))->assertOk();
        }

        $this->actingAs($trainer)
            ->get(route('trainer.assessments'))
            ->assertRedirect(route('trainer.resources'));
    }

    public function test_admin_batch_schedule_is_reflected_in_trainer_month_calendar(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        TrainingBatch::create([
            'name' => 'Realtime Batch',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
            'training_starts_at' => now()->startOfMonth(),
            'training_ends_at' => now()->endOfMonth(),
            'am_days' => strtoupper(now()->format('D')),
            'am_start_time' => '08:30',
            'am_end_time' => '11:30',
            'am_room' => 'Admin Scheduled Skills Lab',
            'pm_days' => 'MON',
        ]);

        $this->actingAs($trainer)
            ->get(route('trainer.sessions', ['month' => now()->format('Y-m')]))
            ->assertOk()
            ->assertSee('Realtime Batch')
            ->assertSee('8:30 AM - 11:30 AM')
            ->assertSee('Admin Scheduled Skills Lab');
    }

    public function test_trainer_can_publish_module_to_one_approved_trainee(): void
    {
        Storage::fake('local');
        $trainer = User::factory()->create(['role' => 'trainer']);
        $student = User::factory()->create(['role' => 'trainee']);
        $batch = TrainingBatch::create([
            'name' => 'Target Batch',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
            'am_days' => 'MWF',
            'pm_days' => 'TTS',
        ]);
        $application = EnrollmentApplication::create([
            'user_id' => $student->id,
            'training_batch_id' => $batch->id,
            'email' => $student->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Target',
            'last_name' => 'Student',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => 'Street',
            'barangay' => 'Barangay',
            'city' => 'City',
            'province' => 'Province',
            'zip_code' => '1000',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'MCARE College',
            'year_graduated' => 2022,
            'status' => EnrollmentApplication::STATUS_APPROVED,
        ]);

        $this->actingAs($trainer)
            ->post(route('trainer.modules.store'), [
                'title' => 'Private Remediation Module',
                'description' => 'Visible only to the selected trainee.',
                'audience_type' => 'trainee',
                'target_enrollment_application_id' => $application->id,
                'module_file' => UploadedFile::fake()->create('private-module.pdf', 64, 'application/pdf'),
            ])
            ->assertRedirect(route('trainer.resources'));

        $this->assertDatabaseHas('training_modules', [
            'title' => 'Private Remediation Module',
            'training_batch_id' => $batch->id,
            'target_enrollment_application_id' => $application->id,
        ]);
    }

    public function test_trainer_can_upload_image_and_pdf_learning_materials(): void
    {
        Storage::fake('local');
        $trainer = User::factory()->create(['role' => 'trainer']);
        $batch = TrainingBatch::create([
            'name' => 'Media Batch',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
            'am_days' => 'MWF',
            'pm_days' => 'TTS',
        ]);
        $files = [
            ['title' => 'Positioning Diagram', 'file' => UploadedFile::fake()->create('positioning.png', 128, 'image/png')],
            ['title' => 'Transfer Guide', 'file' => UploadedFile::fake()->create('transfer.pdf', 512, 'application/pdf')],
        ];

        foreach ($files as $material) {
            $this->actingAs($trainer)
                ->post(route('trainer.modules.store'), [
                    'title' => $material['title'],
                    'description' => 'Browser-viewable learning material.',
                    'audience_type' => 'batch',
                    'training_batch_id' => $batch->id,
                    'module_file' => $material['file'],
                ])
                ->assertRedirect(route('trainer.resources'));
        }

        $this->assertDatabaseHas('training_modules', ['title' => 'Positioning Diagram']);
        $this->assertDatabaseHas('training_modules', ['title' => 'Transfer Guide']);
    }

    public function test_trainer_cannot_upload_video_learning_materials(): void
    {
        Storage::fake('local');
        $trainer = User::factory()->create(['role' => 'trainer']);
        $batch = TrainingBatch::create([
            'name' => 'Media Batch',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
            'am_days' => 'MWF',
            'pm_days' => 'TTS',
        ]);

        $this->actingAs($trainer)
            ->post(route('trainer.modules.store'), [
                'title' => 'Transfer Demonstration',
                'description' => 'Video is no longer accepted.',
                'audience_type' => 'batch',
                'training_batch_id' => $batch->id,
                'module_file' => UploadedFile::fake()->create('transfer.mp4', 512, 'video/mp4'),
            ])
            ->assertSessionHasErrors('module_file');
    }

    public function test_trainer_save_feedback_uses_a_toast_instead_of_a_page_banner(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);

        $this->actingAs($trainer)
            ->withSession(['saved' => 'Attendance recorded for 2 trainee(s) on Sep 04, 2026.'])
            ->get(route('trainer.dashboard'))
            ->assertOk()
            ->assertSee('data-dashboard-toast', false)
            ->assertSee('dashboard-toast-success', false)
            ->assertSee('Attendance recorded for 2 trainee(s) on Sep 04, 2026.')
            ->assertDontSee('mb-6 rounded-lg border border-emerald-200 bg-emerald-50', false);

        $this->actingAs($trainer)
            ->withSession(['error' => 'That attendance date cannot be saved.'])
            ->get(route('trainer.dashboard'))
            ->assertOk()
            ->assertSee('data-dashboard-toast', false)
            ->assertSee('dashboard-toast-error', false)
            ->assertSee('That attendance date cannot be saved.')
            ->assertDontSee('mb-6 rounded-lg border border-rose-200 bg-rose-50', false);
    }

    public function test_trainer_can_export_a_filtered_trainee_summary(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $batch = TrainingBatch::create([
            'name' => 'Export Batch',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);
        $application = EnrollmentApplication::create([
            'user_id' => $trainee->id,
            'training_batch_id' => $batch->id,
            'email' => $trainee->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Export',
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
            'reviewed_at' => now(),
        ]);

        $response = $this->actingAs($trainer)->get(route('trainer.trainees.export', [
            'batch_id' => $batch->id,
            'schedule' => 'AM',
        ]));

        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString($application->email, $response->streamedContent());
    }
}
