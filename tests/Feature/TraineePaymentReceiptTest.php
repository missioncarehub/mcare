<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplication;
use App\Models\PaymentTransaction;
use App\Models\TrainingBatch;
use App\Models\User;
use App\Mail\PaymentReceiptMail;
use App\Services\BrowsershotOfficialDocumentRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TraineePaymentReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_trainee_can_generate_onsite_receipt_and_spamming_keeps_single_output(): void
    {
        Mail::fake();
        $trainee = User::factory()->create(['role' => 'trainee']);
        $batch = TrainingBatch::create([
            'name' => 'Batch 1',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);

        $application = EnrollmentApplication::create([
            'user_id' => $trainee->id,
            'training_batch_id' => $batch->id,
            'email' => $trainee->email,
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
            'total_program_fee' => 22000.00,
            'downpayment_amount' => 2000.00,
            'total_paid_amount' => 0.00,
            'payment_status' => EnrollmentApplication::PAYMENT_NOT_SELECTED,
        ]);

        // First click: Generates the on-site payment slip
        $response1 = $this->actingAs($trainee)
            ->post(route('payment.select'), [
                'payment_method' => 'onsite',
            ]);

        $response1->assertRedirect(route('payment.receipt'));

        $application->refresh();
        $firstReference = $application->payment_reference;
        $pendingTicket = $application->paymentTransactions()->first();

        $this->assertNotEmpty($firstReference);
        $this->assertStringStartsWith('MCARE-SITE-', $firstReference);
        $this->assertNotNull($application->payment_receipt_number);
        $this->assertStringStartsWith('MCARE-OR-', $application->payment_receipt_number);
        $this->assertEquals('onsite', $application->payment_method);
        $this->assertEquals(EnrollmentApplication::PAYMENT_ONSITE_PENDING, $application->payment_status);
        $this->assertNotNull($pendingTicket);
        $this->assertSame($firstReference, $pendingTicket->ticket_number);
        $this->assertSame($firstReference, $pendingTicket->reference_number);
        $this->assertSame($application->payment_receipt_number, $pendingTicket->or_number);

        Mail::assertSent(PaymentReceiptMail::class, 1);
        Mail::assertSent(PaymentReceiptMail::class, function (PaymentReceiptMail $mail) use ($trainee, $firstReference, $application): bool {
            $html = $mail->render();

            return $mail->hasTo($trainee->email)
                && str_contains($html, 'Official payment receipt')
                && str_contains($html, 'Official Receipt (OR) #')
                && str_contains($html, $application->payment_receipt_number)
                && str_contains($html, 'Reference number')
                && str_contains($html, $firstReference)
                && ! str_contains($html, 'PayMongo payment number')
                && str_contains($html, 'On-site');
        });

        // Spam clicks: 5 repeated calls simulating button mash / double submit
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($trainee)
                ->post(route('payment.select'), [
                    'payment_method' => 'onsite',
                ])
                ->assertRedirect(route('payment.receipt'));
        }

        $application->refresh();

        // Must still equal the EXACT same reference number (single output)
        $this->assertEquals($firstReference, $application->payment_reference);
        $this->assertStringStartsWith('MCARE-OR-', (string) $application->payment_receipt_number);
        Mail::assertSent(PaymentReceiptMail::class, 1);

        // Trainee can open and view the receipt page
        $this->actingAs($trainee)
            ->get(route('payment.receipt'))
            ->assertOk()
            ->assertSee($firstReference)
            ->assertSee($application->payment_receipt_number)
            ->assertSee('Official Receipt (OR) #', false)
            ->assertSee('Ana Reyes')
            ->assertSee('PAY-ON-SITE RECEIPT', false)
            ->assertSee('PHP 22,000.00')
            ->assertSee('PHP 2,000.00')
            ->assertSee('Print / Save PDF')
            ->assertSee('size: Letter portrait', false);

        // Once the cashier has verified the active ticket, the dashboard
        // presents the official receipt action instead of the pending ticket.
        $application->paymentTransactions()->update([
            'status' => PaymentTransaction::STATUS_VERIFIED,
            'or_number' => 'OR-TEST-22000',
            'paid_at' => now(),
            'verified_at' => now(),
        ]);

        // Trainee billing summary shows the active slip details
        $this->actingAs($trainee)
            ->get(route('trainee.payments'))
            ->assertOk()
            ->assertSee($firstReference)
            ->assertSee('View & Print Official Slip', false)
            ->assertSee('Download Slip', false)
            ->assertSee('href="'.route('payment.receipt').'"', false)
            ->assertSee('href="'.route('payment.receipt.download').'"', false)
            ->assertSee('target="_blank"', false)
            ->assertSee('rel="noopener noreferrer"', false)
            ->assertSee('m7 10 5 5 5-5', false);

        $application->forceFill([
            'total_paid_amount' => 22000.00,
            'payment_status' => EnrollmentApplication::PAYMENT_PAID,
            'payment_verified_at' => now(),
        ])->save();

        Mail::fake();

        $this->actingAs($trainee)
            ->get(route('payment.receipt'))
            ->assertOk()
            ->assertSee($firstReference)
            ->assertSee('OFFICIAL PAYMENT RECEIPT', false)
            ->assertSee('Received by')
            ->assertSee('Signature')
            ->assertSee('Ana Reyes')
            ->assertDontSee('TESDA-accredited Caregiving NC II training and assessment.', false)
            ->assertDontSee('Log in to MCARE', false);

        $this->app->instance(BrowsershotOfficialDocumentRenderer::class, \Mockery::mock(BrowsershotOfficialDocumentRenderer::class, function ($mock): void {
            $mock->shouldReceive('renderHtml')
                ->once()
                ->withArgs(fn (string $html, string $paper): bool => str_contains($html, 'OFFICIAL PAYMENT RECEIPT') && $paper === 'Letter')
                ->andReturn('%PDF-1.7 receipt');
        }));

        $download = $this->actingAs($trainee)->get(route('payment.receipt.download'));

        $download->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="mcare-receipt-'.$firstReference.'.pdf"');
        $this->assertStringStartsWith('%PDF-', $download->getContent());

        $this->actingAs($trainee)
            ->get(route('payment.complete'))
            ->assertRedirect(route('trainee.payments'));
    }

    public function test_trainee_payments_page_icons_are_registered(): void
    {
        $component = file_get_contents(resource_path('views/components/dashboard-icon.blade.php'));
        $page = file_get_contents(resource_path('views/trainee/payments.blade.php'));

        $this->assertIsString($component);
        $this->assertIsString($page);
        preg_match_all('/<x-dashboard-icon\s+name="([^"]+)"/', $page, $matches);

        $this->assertNotEmpty($matches[1]);

        foreach (array_unique($matches[1]) as $name) {
            $this->assertStringContainsString("'{$name}' =>", $component, "Missing dashboard icon: {$name}");
        }
    }
}
