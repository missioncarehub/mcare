<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Notifications\AdminOperationsNotification;
use App\Notifications\PaymentVerifiedNotification;
use App\Services\EnrollmentPaymentLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EnrollmentLifecycleVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_publish_a_program_batch_to_the_public_batch_picker(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.training-programs.store'), [
                'program_name' => 'Caregiving NC III',
                'program_code' => 'CAREGIVING-NC-III',
                'program_description' => 'Advanced caregiving training.',
                'program_total_fee' => '30000.00',
                'program_downpayment' => '3500.00',
                'program_is_active' => '1',
            ])
            ->assertRedirect(route('admin.training-programs.index'));

        $program = TrainingProgram::query()->where('code', 'CAREGIVING-NC-III')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.batches.store'), [
                'training_program_id' => $program->id,
                'name' => 'Advanced Batch Alpha',
                'year' => 2026,
                'is_active' => '1',
                'show_on_enrollment_page' => '1',
                'enrollment_starts_at' => now()->subDay()->format('Y-m-d\TH:i'),
                'enrollment_ends_at' => now()->addMonth()->format('Y-m-d\TH:i'),
                'am_days' => 'MWF',
                'pm_days' => 'TTH',
            ])
            ->assertRedirect(route('admin.batches.index'));

        $publishedBatch = TrainingBatch::query()->where('name', 'Advanced Batch Alpha')->firstOrFail();

        TrainingBatch::create([
            'training_program_id' => $program->id,
            'name' => 'Hidden Internal Batch',
            'year' => 2026,
            'is_active' => true,
            'show_on_enrollment_page' => false,
            'enrollment_starts_at' => now()->subDay(),
            'enrollment_ends_at' => now()->addMonth(),
            'am_days' => 'MWF',
            'pm_days' => 'TTH',
        ]);

        $this->withSession([
            'enrollment.admission_application_id' => $this->makeApprovedAdmission()->id,
        ])->get(route('enrollment.create', ['batch' => $publishedBatch->id]))
            ->assertOk()
            ->assertSee('Available active batches')
            ->assertSee('Caregiving NC III')
            ->assertSee('Advanced Batch Alpha')
            ->assertSee('Required downpayment: ₱3,500.00')
            ->assertSee('name="training_batch_id" value="'.$publishedBatch->id.'"', false)
            ->assertDontSee('Hidden Internal Batch');
    }

    public function test_flashed_old_batch_id_is_restored_on_the_enrollment_form(): void
    {
        $program = TrainingProgram::query()->firstOrFail();
        $oldBatch = TrainingBatch::create([
            'training_program_id' => $program->id,
            'name' => 'Old Input Batch',
            'year' => 2026,
            'is_active' => true,
            'show_on_enrollment_page' => true,
            'enrollment_starts_at' => now()->subDay(),
            'enrollment_ends_at' => now()->addMonth(),
        ]);
        TrainingBatch::create([
            'training_program_id' => $program->id,
            'name' => 'Other Published Batch',
            'year' => 2026,
            'is_active' => true,
            'show_on_enrollment_page' => true,
            'enrollment_starts_at' => now()->subDay(),
            'enrollment_ends_at' => now()->addMonth(),
        ]);

        $this->withSession([
            'enrollment.admission_application_id' => $this->makeApprovedAdmission()->id,
            '_old_input' => ['training_batch_id' => $oldBatch->id],
        ])->get(route('enrollment.create'))
            ->assertOk()
            ->assertSee('name="training_batch_id" value="'.$oldBatch->id.'"', false);
    }

    public function test_unpaid_registration_stays_in_payment_operations_but_out_of_admin_review_and_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create([
            'name' => 'Payment Pending Learner',
            'email' => 'payment.pending@gmail.com',
            'role' => 'applicant',
        ]);
        $application = $this->applicationFor($applicant, [
            'payment_method' => 'onsite',
            'payment_status' => EnrollmentApplication::PAYMENT_ONSITE_PENDING,
            'payment_selected_at' => now(),
            'review_released_at' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.enrollments.index'))
            ->assertOk()
            ->assertDontSee('payment.pending@gmail.com');

        $this->actingAs($admin)
            ->get(route('admin.enrollments.show', $application))
            ->assertOk()
            ->assertSee('Payment Pending Learner')
            ->assertSee('This enrollment is not in the review queue yet.')
            ->assertDontSee('Save decision');

        $this->actingAs($admin)
            ->patch(route('admin.enrollments.update', $application), [
                'status' => EnrollmentApplication::STATUS_APPROVED,
            ])
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('admin.accounts.index'))
            ->assertOk()
            ->assertDontSee('payment.pending@gmail.com');

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Payment Pending Learner')
            ->assertSee('Pay on-site receipt');

        $this->actingAs($admin)
            ->get(route('admin.payment-schedules.index'))
            ->assertOk()
            ->assertSee('payment.pending@gmail.com');
    }

    public function test_verified_payment_releases_review_and_notifies_each_party_once(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create([
            'name' => 'Ready Learner',
            'email' => 'ready.learner@gmail.com',
            'role' => 'applicant',
        ]);
        $application = $this->applicationFor($applicant, [
            'total_paid_amount' => 2000,
            'downpayment_amount' => 2000,
            'payment_status' => EnrollmentApplication::PAYMENT_PARTIALLY_PAID,
            'payment_verified_at' => now(),
            'review_released_at' => null,
        ]);

        $lifecycle = app(EnrollmentPaymentLifecycle::class);
        $this->assertTrue($lifecycle->handleVerifiedPayment($application));
        $this->assertTrue($lifecycle->handleVerifiedPayment($application->fresh()));
        $this->assertNotNull($application->fresh()->review_released_at);
        $this->assertSame(
            EnrollmentApplication::STATUS_PRE_ENLISTMENT,
            $application->fresh()->status,
        );

        $this->assertCount(1, Notification::sent($admin, AdminOperationsNotification::class));
        $this->assertCount(1, Notification::sent($applicant, PaymentVerifiedNotification::class));

        $this->actingAs($admin)
            ->get(route('admin.enrollments.index'))
            ->assertOk()
            ->assertSee('ready.learner@gmail.com');

        $this->actingAs($admin)
            ->get(route('admin.accounts.index'))
            ->assertOk()
            ->assertSee('ready.learner@gmail.com');
    }

    /** @param array<string, mixed> $overrides */
    private function applicationFor(User $user, array $overrides = []): EnrollmentApplication
    {
        return EnrollmentApplication::create(array_merge([
            'user_id' => $user->id,
            'email' => $user->email,
            'program' => 'Caregiving NC II',
            'first_name' => str($user->name)->beforeLast(' ')->toString(),
            'last_name' => str($user->name)->afterLast(' ')->toString(),
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => 'Training Street',
            'barangay' => 'Central',
            'city' => 'Pili',
            'province' => 'Camarines Sur',
            'zip_code' => '4418',
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'MCARE High School',
            'year_graduated' => 2020,
            'status' => EnrollmentApplication::STATUS_PRE_ENLISTMENT,
            'total_program_fee' => 22000,
            'downpayment_amount' => 2000,
            'total_paid_amount' => 0,
        ], $overrides));
    }
}
