<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use App\Models\User;
use App\Services\RollingModuleReleaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class TraineePortalTest extends TestCase
{
    use CreatesLmsTestData;
    use RefreshDatabase;

    public function test_guest_is_redirected_to_trainee_login(): void
    {
        $this->get('/trainee')
            ->assertRedirect(route('login'));
    }

    public function test_non_trainee_cannot_open_trainee_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'applicant']);

        $this->actingAs($user)
            ->get('/trainee')
            ->assertForbidden();
    }

    public function test_admin_approval_promotes_applicant_to_trainee_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'applicant']);
        $application = $this->approvedReadyApplication($applicant, EnrollmentApplication::STATUS_PRE_ENLISTMENT);

        $this->actingAs($admin)
            ->patch(route('admin.enrollments.update', $application), [
                'status' => EnrollmentApplication::STATUS_APPROVED,
                'admin_notes' => 'Approved for Batch 1.',
            ])
            ->assertRedirect(route('admin.enrollments.show', $application));

        $this->assertDatabaseHas('users', [
            'id' => $applicant->id,
            'role' => 'trainee',
            'applicant_status' => EnrollmentApplication::STATUS_APPROVED,
        ]);
    }

    public function test_admin_cannot_approve_applicant_before_required_payment_is_verified(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'applicant']);
        $application = $this->approvedReadyApplication($applicant, EnrollmentApplication::STATUS_PRE_ENLISTMENT);
        $application->forceFill([
            'payment_status' => EnrollmentApplication::PAYMENT_ONSITE_PENDING,
            'total_paid_amount' => 0,
            'payment_verified_at' => null,
        ])->save();

        $this->actingAs($admin)
            ->patch(route('admin.enrollments.update', $application), [
                'status' => EnrollmentApplication::STATUS_APPROVED,
                'admin_notes' => 'Attempted before payment verification.',
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame('applicant', $applicant->refresh()->role);
        $this->assertSame(EnrollmentApplication::STATUS_PRE_ENLISTMENT, $application->refresh()->status);
    }

    public function test_admin_cannot_approve_applicant_until_every_required_document_is_accepted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'applicant']);
        $application = $this->approvedReadyApplication($applicant, EnrollmentApplication::STATUS_PRE_ENLISTMENT);
        $review = $application->document_review;
        $review['education-document']['status'] = 'replace';
        $application->forceFill(['document_review' => $review])->save();

        $this->actingAs($admin)
            ->patch(route('admin.enrollments.update', $application), [
                'status' => EnrollmentApplication::STATUS_APPROVED,
                'admin_notes' => 'Attempted before all documents were accepted.',
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame('applicant', $applicant->refresh()->role);
        $this->assertSame(EnrollmentApplication::STATUS_PRE_ENLISTMENT, $application->refresh()->status);
    }

    public function test_approved_trainee_can_open_dashboard(): void
    {
        $trainee = User::factory()->create(['role' => 'trainee']);
        $trainer = User::factory()->create(['role' => 'trainer']);
        $batch = $this->batch();
        $this->approvedReadyApplication($trainee, EnrollmentApplication::STATUS_APPROVED, $batch);

        $module = TrainingModule::create([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'title' => 'Infection Control',
            'description' => 'Core caregiving safety lesson.',
            'file_path' => 'training-modules/sample.pdf',
            'original_file_name' => 'sample.pdf',
            'is_published' => true,
            'published_at' => now(),
        ]);
        app(RollingModuleReleaseService::class)->activate($module);

        $this->actingAs($trainee)
            ->get(route('trainee.dashboard'))
            ->assertOk()
            ->assertSee('Welcome back')
            ->assertSee('Infection Control')
            ->assertSee('MWF | 8:00 AM - 12:00 PM')
            ->assertSee('TESDA-Accredited Training and Assessment Center')
            ->assertSee('data-dashboard-sidebar-collapse', false)
            ->assertSee('id="trainee-dashboard-sidebar"', false)
            ->assertSee('dashboard-sidebar-header', false)
            ->assertDontSee('dashboard-gradient', false)
            ->assertSee('href="'.route('trainee.payments').'"', false)
            ->assertDontSee('href="'.route('payment.show').'"', false);
    }

    public function test_trainee_sidebar_destinations_are_separate_pages(): void
    {
        $trainee = User::factory()->create(['role' => 'trainee']);
        $batch = $this->batch();
        $this->approvedReadyApplication($trainee, EnrollmentApplication::STATUS_APPROVED, $batch);

        foreach ([
            'trainee.modules.index' => 'Modules',
            'trainee.schedule' => 'Class calendar',
            'trainee.payments' => 'Payment summary',
            'trainee.documents' => 'Submitted registration files',
        ] as $routeName => $heading) {
            $this->actingAs($trainee)
                ->get(route($routeName))
                ->assertOk()
                ->assertSee($heading);
        }
    }

    public function test_private_module_is_visible_only_to_its_selected_trainee(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        $targetUser = User::factory()->create(['role' => 'trainee']);
        $otherUser = User::factory()->create(['role' => 'trainee']);
        $batch = $this->batch();
        $targetApplication = $this->approvedReadyApplication($targetUser, EnrollmentApplication::STATUS_APPROVED, $batch);
        $this->approvedReadyApplication($otherUser, EnrollmentApplication::STATUS_APPROVED, $batch);

        $module = TrainingModule::create([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'target_enrollment_application_id' => $targetApplication->id,
            'title' => 'Private Coaching Module',
            'description' => 'Targeted learner follow-up.',
            'file_path' => 'training-modules/private.pdf',
            'original_file_name' => 'private.pdf',
            'is_published' => true,
            'published_at' => now(),
        ]);
        app(RollingModuleReleaseService::class)->activate($module);

        $this->actingAs($targetUser)
            ->get(route('trainee.dashboard'))
            ->assertOk()
            ->assertSee('Private Coaching Module');

        $this->actingAs($otherUser)
            ->get(route('trainee.dashboard'))
            ->assertOk()
            ->assertDontSee('Private Coaching Module');

        $this->actingAs($otherUser)
            ->get(route('trainee.modules.content', $module))
            ->assertNotFound();
    }

    public function test_module_viewing_and_completion_are_recorded_on_the_server(): void
    {
        Storage::fake('local');
        $trainer = User::factory()->create(['role' => 'trainer']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $batch = $this->batch();
        $application = $this->approvedReadyApplication($trainee, EnrollmentApplication::STATUS_APPROVED, $batch);
        Storage::disk('local')->put('training-modules/lesson.pdf', '%PDF-1.4 test');
        $module = TrainingModule::create([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'title' => 'Protected PDF Lesson',
            'description' => 'Tracked learning material.',
            'file_path' => 'training-modules/lesson.pdf',
            'original_file_name' => 'lesson.pdf',
            'mime_type' => 'application/pdf',
            'is_published' => true,
            'published_at' => now(),
        ]);
        app(RollingModuleReleaseService::class)->activate($module);
        $this->lmsPassedAssessment($trainer, $module, $application);
        $submodule = $module->fresh()->submodules()->firstOrFail();

        $this->actingAs($trainee)
            ->get(route('trainee.modules.show', $module))
            ->assertOk()
            ->assertSee('Protected content')
            ->assertSee('data-protected-module-viewer', false)
            ->assertSee('data-pdf-canvas-viewer', false)
            ->assertSee('data-pdf-canvas', false)
            ->assertSee('data-pdf-scroll-sizer', false)
            ->assertDontSee('block max-w-full bg-white', false)
            ->assertSee(route('trainee.modules.security-event', $module), false);

        $this->assertDatabaseHas('module_progress', [
            'enrollment_application_id' => $application->id,
            'training_module_id' => $module->id,
            'status' => 'in_progress',
            'progress_percent' => 0,
        ]);

        $this->actingAs($trainee)
            ->get(route('trainee.modules.content', $module))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'inline; filename=lesson.pdf')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');

        $this->actingAs($trainee)
            ->patch(route('trainee.modules.submodules.progress', [$module, $submodule]), ['action' => 'submit'])
            ->assertSessionHasErrors('action');

        $this->assertDatabaseHas('module_progress', [
            'enrollment_application_id' => $application->id,
            'training_module_id' => $module->id,
            'status' => 'in_progress',
            'progress_percent' => 0,
        ]);

        $this->actingAs($trainee)
            ->postJson(route('trainee.modules.security-event', $module), ['event' => 'print_shortcut'])
            ->assertNoContent();

        $this->assertDatabaseHas('admin_activity_logs', [
            'user_id' => $trainee->id,
            'action' => 'trainee.module.restricted-action',
            'subject_id' => $module->id,
        ]);
    }

    private function batch(): TrainingBatch
    {
        return TrainingBatch::create([
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
    }

    private function approvedReadyApplication(User $user, string $status, ?TrainingBatch $batch = null): EnrollmentApplication
    {
        $batch ??= $this->batch();

        return EnrollmentApplication::create([
            'user_id' => $user->id,
            'training_batch_id' => $batch->id,
            'email' => $user->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
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
            'birth_certificate_path' => 'enrollment-documents/test/birth-certificate.pdf',
            'education_document_path' => 'enrollment-documents/test/education-document.pdf',
            'good_moral_certificate_path' => 'enrollment-documents/test/good-moral-certificate.pdf',
            'id_photo_path' => 'enrollment-documents/test/id-photo.jpg',
            'signature_path' => 'enrollment-documents/test/signature.png',
            'document_review' => [
                'birth-certificate' => ['status' => 'accepted', 'note' => null],
                'education-document' => ['status' => 'accepted', 'note' => null],
                'good-moral-certificate' => ['status' => 'accepted', 'note' => null],
                'id-photo' => ['status' => 'accepted', 'note' => null],
                'signature' => ['status' => 'accepted', 'note' => null],
            ],
            'documents_reviewed_at' => now(),
            'status' => $status,
            'total_program_fee' => 22000.00,
            'downpayment_amount' => 2000.00,
            'total_paid_amount' => 2000.00,
            'payment_status' => EnrollmentApplication::PAYMENT_PARTIALLY_PAID,
            'payment_method' => 'onsite',
            'payment_verified_at' => now(),
            'review_released_at' => now(),
        ]);
    }
}
