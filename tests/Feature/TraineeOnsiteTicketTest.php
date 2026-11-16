<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplication;
use App\Models\PaymentTransaction;
use App\Models\TrainingBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TraineeOnsiteTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_trainee_generates_one_ticket_and_admin_verifies_it_with_the_cashier_or(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $batch = TrainingBatch::create([
            'name' => 'Batch 1',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);
        $application = $this->approvedApplication($trainee, $batch);

        $this->actingAs($trainee)
            ->post(route('trainee.payments.tickets.store'), [
                'transaction_type' => 'downpayment',
                'amount' => 2000.00,
            ])
            ->assertRedirect(route('trainee.payments'))
            ->assertSessionHas('saved');

        $ticket = PaymentTransaction::query()->firstOrFail();

        $this->assertNotEmpty($ticket->ticket_number);
        $this->assertStringStartsWith('MCARE-OT-', $ticket->ticket_number);
        $this->assertSame(PaymentTransaction::STATUS_PENDING, $ticket->status);
        $this->assertSame(PaymentTransaction::CHANNEL_ONSITE, $ticket->payment_channel);
        $this->assertNull($ticket->or_number);
        $this->assertNull($ticket->paid_at);
        $this->assertSame(EnrollmentApplication::PAYMENT_ONSITE_PENDING, $application->refresh()->payment_status);

        $ticketNotice = "On-site payment ticket {$ticket->ticket_number} generated. Present it to the MCARE cashier.";
        $paymentPage = $this->actingAs($trainee)->get(route('trainee.payments'))->assertOk();
        $this->assertSame(1, substr_count($paymentPage->getContent(), $ticketNotice));

        // A second click returns the same pending ticket instead of adding another ledger row.
        $this->actingAs($trainee)
            ->post(route('trainee.payments.tickets.store'), [
                'transaction_type' => 'installment',
                'amount' => 5000.00,
            ])
            ->assertRedirect(route('trainee.payments'))
            ->assertSessionHas('saved', fn (string $message): bool => str_contains($message, $ticket->ticket_number));

        $this->assertDatabaseCount('payment_transactions', 1);

        $this->actingAs($admin)
            ->get(route('admin.payment-schedules.index'))
            ->assertOk()
            ->assertSee($ticket->ticket_number)
            ->assertSee('Pending On-Site Tickets');

        $this->actingAs($admin)
            ->patch(route('admin.payment-schedules.transactions.verify', $ticket), [
                'action' => 'verify',
                'or_number' => 'OR-2026-0001',
                'paid_at' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHas('saved');

        $ticket->refresh();
        $application->refresh();

        $this->assertSame(PaymentTransaction::STATUS_VERIFIED, $ticket->status);
        $this->assertSame('OR-2026-0001', $ticket->or_number);
        $this->assertSame(2000.00, (float) $application->total_paid_amount);
        $this->assertSame(EnrollmentApplication::PAYMENT_PARTIALLY_PAID, $application->payment_status);
    }

    public function test_trainee_cannot_generate_a_ticket_above_the_remaining_balance(): void
    {
        $trainee = User::factory()->create(['role' => 'trainee']);
        $batch = TrainingBatch::create([
            'name' => 'Batch 2',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);
        $this->approvedApplication($trainee, $batch);

        $this->actingAs($trainee)
            ->from(route('trainee.payments'))
            ->post(route('trainee.payments.tickets.store'), [
                'transaction_type' => 'installment',
                'amount' => 22001.00,
            ])
            ->assertRedirect(route('trainee.payments'))
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('payment_transactions', 0);
    }

    public function test_receipt_proof_completes_the_active_ticket_instead_of_duplicating_it(): void
    {
        Storage::fake('local');

        $trainee = User::factory()->create(['role' => 'trainee']);
        $batch = TrainingBatch::create([
            'name' => 'Batch Receipt',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);
        $application = $this->approvedApplication($trainee, $batch);

        $this->actingAs($trainee)
            ->post(route('trainee.payments.tickets.store'), [
                'transaction_type' => 'downpayment',
                'amount' => 2000.00,
            ])
            ->assertSessionHasNoErrors();

        $ticket = PaymentTransaction::query()->firstOrFail();

        $this->actingAs($trainee)
            ->post(route('trainee.payments.upload-proof'), [
                'transaction_type' => 'downpayment',
                'amount' => 2000.00,
                'or_number' => 'OR-TICKET-2000',
                'paid_at' => now()->toDateString(),
                'receipt_proof' => UploadedFile::fake()->create('ticket-receipt.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('payment_transactions', 1);
        $ticket->refresh();
        $this->assertSame('OR-TICKET-2000', $ticket->or_number);
        $this->assertNotNull($ticket->receipt_proof_path);
        $this->assertSame(PaymentTransaction::STATUS_PENDING, $ticket->status);
        $this->assertSame(EnrollmentApplication::PAYMENT_ONSITE_PENDING, $application->refresh()->payment_status);
    }

    public function test_enrollment_onsite_downpayment_creates_ticket_and_admin_and_trainer_see_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainer = User::factory()->create(['role' => 'trainer']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $batch = TrainingBatch::create([
            'name' => 'Batch 3',
            'year' => 2026,
            'trainer_id' => $trainer->id,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);
        $application = $this->approvedApplication($trainee, $batch);

        // Trainee chooses onsite downpayment on enrollment page
        $this->actingAs($trainee)
            ->withSession(['enrollment.payment_application_id' => $application->id])
            ->post(route('payment.select'), [
                'payment_method' => 'onsite',
            ])
            ->assertRedirect(route('payment.receipt'));

        // Trainee dashboard payments page shows the generated ticket
        $this->actingAs($trainee)
            ->get(route('trainee.payments'))
            ->assertOk()
            ->assertSee('Ticket waiting for cashier verification')
            ->assertSee('₱2,000.00');

        $this->assertDatabaseHas('payment_transactions', [
            'enrollment_application_id' => $application->id,
            'transaction_type' => 'downpayment',
            'amount' => 2000.00,
            'status' => 'pending_verification',
        ]);

        $ticket = PaymentTransaction::query()
            ->where('enrollment_application_id', $application->id)
            ->where('status', PaymentTransaction::STATUS_PENDING)
            ->firstOrFail();

        // Cashier verification supplies the real OR instead of fabricating a ledger entry.
        $this->actingAs($admin)
            ->patch(route('admin.payment-schedules.transactions.verify', $ticket), [
                'action' => 'verify',
                'or_number' => 'OR-2026-BATCH3-01',
                'paid_at' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHas('saved');

        $application->refresh();
        $this->assertSame(2000.00, (float) $application->total_paid_amount);
        $this->assertSame(EnrollmentApplication::PAYMENT_PARTIALLY_PAID, $application->payment_status);
        $this->assertTrue($application->isDownpaymentSatisfied());

        // Trainer views the trainee list and sees the enrolled learner
        $this->actingAs($trainer)
            ->get(route('trainer.trainees'))
            ->assertOk()
            ->assertSee($application->last_name)
            ->assertSee($application->first_name);
    }

    private function approvedApplication(User $user, TrainingBatch $batch): EnrollmentApplication
    {
        return EnrollmentApplication::create([
            'user_id' => $user->id,
            'training_batch_id' => $batch->id,
            'email' => $user->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'birth_date' => '2001-03-15',
            'gender' => 'Female',
            'contact_number' => '09191234567',
            'schedule_preference' => 'AM',
            'street' => 'Magsaysay Ave',
            'barangay' => 'San Roque',
            'city' => 'Naga City',
            'province' => 'Camarines Sur',
            'zip_code' => '4400',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'Ateneo de Naga',
            'year_graduated' => 2023,
            'status' => EnrollmentApplication::STATUS_APPROVED,
            'learning_status' => EnrollmentApplication::LEARNING_ACTIVE,
            'total_program_fee' => 22000.00,
            'downpayment_amount' => 2000.00,
            'total_paid_amount' => 0.00,
            'payment_status' => EnrollmentApplication::PAYMENT_NOT_SELECTED,
        ]);
    }
}
