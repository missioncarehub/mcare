<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplication;
use App\Models\PaymentAttempt;
use App\Models\PaymentTransaction;
use App\Models\TrainingBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class TraineeOnlinePayMongoCheckoutTest extends TestCase
{
    use CreatesLmsTestData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.paymongo.secret_key', 'sk_test_mock_secret_key');
        config()->set('services.paymongo.live_mode', false);
        config()->set('services.paymongo.payment_methods', ['gcash', 'card', 'qrph']);
        config()->set('logging.default', 'null');

        Http::preventStrayRequests();
    }

    public function test_trainee_can_see_online_payment_portal_form_on_payments_page(): void
    {
        $batch = $this->lmsBatch();
        ['user' => $trainee, 'application' => $app] = $this->lmsTrainee($batch, [
            'total_program_fee' => 22000.00,
            'downpayment_amount' => 2000.00,
            'total_paid_amount' => 2000.00,
            'payment_status' => EnrollmentApplication::PAYMENT_PARTIALLY_PAID,
        ]);

        $response = $this->actingAs($trainee)->get(route('trainee.payments'));

        $response->assertOk();
        $response->assertSee('Online Payment Portal');
        $response->assertSee('Open PayMongo Checkout');
        $response->assertSee('GCash');
        $response->assertSee('Maya');
        $response->assertSee(route('trainee.payments.checkout'));
    }

    public function test_trainee_can_initiate_paymongo_online_checkout_and_is_redirected(): void
    {
        $batch = $this->lmsBatch();
        ['user' => $trainee, 'application' => $app] = $this->lmsTrainee($batch, [
            'total_program_fee' => 22000.00,
            'downpayment_amount' => 2000.00,
            'total_paid_amount' => 2000.00,
            'payment_status' => EnrollmentApplication::PAYMENT_PARTIALLY_PAID,
        ]);

        Http::fake([
            'https://api.paymongo.com/v2/checkout_sessions' => Http::response([
                'data' => [
                    'id' => 'cs_test_online_tuition_123',
                    'type' => 'checkout_session',
                    'attributes' => [
                        'checkout_url' => 'https://checkout.paymongo.com/cs_test_online_tuition_123',
                        'livemode' => false,
                        'reference_number' => 'MCARE-ONL-REF1',
                        'status' => 'active',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($trainee)->post(route('trainee.payments.checkout'), [
            'transaction_type' => 'installment',
            'amount' => 5000.00,
        ]);

        $response->assertRedirect('https://checkout.paymongo.com/cs_test_online_tuition_123');

        $this->assertDatabaseHas('payment_attempts', [
            'enrollment_application_id' => $app->id,
            'provider' => 'paymongo',
            'amount_minor' => 500000,
            'status' => PaymentAttempt::STATUS_PENDING,
            'provider_checkout_id' => 'cs_test_online_tuition_123',
        ]);
    }

    public function test_trainee_returning_from_paymongo_has_verified_payment_recorded_in_ledger(): void
    {
        $batch = $this->lmsBatch();
        ['user' => $trainee, 'application' => $app] = $this->lmsTrainee($batch, [
            'total_program_fee' => 22000.00,
            'downpayment_amount' => 2000.00,
            'total_paid_amount' => 2000.00,
            'payment_status' => EnrollmentApplication::PAYMENT_PARTIALLY_PAID,
            'paymongo_checkout_reference' => 'cs_test_online_tuition_456',
        ]);

        PaymentTransaction::create([
            'enrollment_application_id' => $app->id,
            'user_id' => $trainee->id,
            'transaction_type' => PaymentTransaction::TYPE_DOWNPAYMENT,
            'payment_channel' => PaymentTransaction::CHANNEL_ONLINE,
            'amount' => 2000.00,
            'or_number' => 'OR-INITIAL-DOWN',
            'status' => PaymentTransaction::STATUS_VERIFIED,
            'paid_at' => now()->subDays(5),
            'verified_at' => now()->subDays(5),
        ]);

        PaymentAttempt::create([
            'enrollment_application_id' => $app->id,
            'provider' => 'paymongo',
            'merchant_reference' => 'MCARE-ONL-RETURN-REF',
            'provider_checkout_id' => 'cs_test_online_tuition_456',
            'idempotency_key' => 'idem-return-1',
            'amount_minor' => 500000,
            'currency' => 'PHP',
            'status' => PaymentAttempt::STATUS_PENDING,
            'livemode' => false,
            'meta' => [
                'created_for' => 'installment',
            ],
        ]);

        Http::fake([
            'https://api.paymongo.com/v1/checkout_sessions/cs_test_online_tuition_456' => Http::response([
                'data' => [
                    'id' => 'cs_test_online_tuition_456',
                    'type' => 'checkout_session',
                    'attributes' => [
                        'checkout_url' => 'https://checkout.paymongo.com/cs_test_online_tuition_456',
                        'livemode' => false,
                        'reference_number' => 'MCARE-ONL-RETURN-REF',
                        'status' => 'paid',
                        'metadata' => ['application_id' => (string) $app->id],
                        'payments' => [[
                            'id' => 'pay_test_online_999',
                            'type' => 'payment',
                            'attributes' => [
                                'amount' => 500000,
                                'currency' => 'PHP',
                                'status' => 'paid',
                                'payment_intent_id' => 'pi_test_intent_999',
                            ],
                        ]],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($trainee)->get(route('trainee.payments.return'));

        $response->assertRedirect(route('trainee.payments'));
        $response->assertSessionHas('saved');

        // Verify transaction recorded in ledger
        $this->assertDatabaseHas('payment_transactions', [
            'enrollment_application_id' => $app->id,
            'payment_channel' => PaymentTransaction::CHANNEL_ONLINE,
            'reference_number' => 'pay_test_online_999',
            'amount' => 5000.00,
            'status' => PaymentTransaction::STATUS_VERIFIED,
        ]);

        $app->refresh();
        $this->assertEquals(7000.00, (float) $app->total_paid_amount);
        $this->assertEquals(15000.00, $app->remainingBalance());
    }

    public function test_approved_trainee_visiting_enrollment_payment_show_is_redirected_to_trainee_payments(): void
    {
        $batch = $this->lmsBatch();
        ['user' => $trainee, 'application' => $app] = $this->lmsTrainee($batch, [
            'payment_verified_at' => now(),
            'total_paid_amount' => 2000.00,
            'downpayment_amount' => 2000.00,
            'payment_status' => EnrollmentApplication::PAYMENT_PARTIALLY_PAID,
        ]);

        $response = $this->actingAs($trainee)->get(route('payment.show'));

        $response->assertRedirect(route('trainee.payments'));
    }

    public function test_fully_paid_trainee_sees_tuition_fully_settled_without_checkout_form(): void
    {
        $batch = $this->lmsBatch();
        ['user' => $trainee, 'application' => $app] = $this->lmsTrainee($batch, [
            'total_program_fee' => 22000.00,
            'total_paid_amount' => 22000.00,
            'payment_status' => EnrollmentApplication::PAYMENT_PAID,
        ]);

        PaymentTransaction::create([
            'enrollment_application_id' => $app->id,
            'user_id' => $trainee->id,
            'transaction_type' => PaymentTransaction::TYPE_FULL_PAYMENT,
            'payment_channel' => PaymentTransaction::CHANNEL_ONSITE,
            'amount' => 22000.00,
            'or_number' => 'OR-FULL-PAID',
            'status' => PaymentTransaction::STATUS_VERIFIED,
            'paid_at' => now(),
            'verified_at' => now(),
        ]);

        $response = $this->actingAs($trainee)->get(route('trainee.payments'));

        $response->assertOk();
        $response->assertSee('Program tuition is fully settled. No online payment required.');
        $response->assertDontSee('Open PayMongo Checkout');
    }
}
