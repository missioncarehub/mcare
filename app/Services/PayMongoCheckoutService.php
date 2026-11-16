<?php

namespace App\Services;

use App\Exceptions\PayMongoCheckoutException;
use App\Models\EnrollmentApplication;
use App\Models\PaymentAttempt;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PayMongoCheckoutService
{
    private const CHECKOUT_CREATE_ENDPOINT = 'https://api.paymongo.com/v2/checkout_sessions';

    private const CHECKOUT_RETRIEVE_ENDPOINT = 'https://api.paymongo.com/v1/checkout_sessions';

    public function secretConfigured(): bool
    {
        $secret = $this->secretKey();
        $expectedPrefix = $this->isLiveMode() ? 'sk_live_' : 'sk_test_';

        return filled($secret) && Str::startsWith($secret, $expectedPrefix);
    }

    public function ready(): bool
    {
        return $this->secretConfigured();
    }

    /**
     * @return array{checkout_id: string, livemode: bool, reference: string, metadata: array<string, mixed>, attributes: array<string, mixed>}|null
     *
     * @throws PayMongoCheckoutException
     */
    public function retrieveCheckout(string $checkoutId): ?array
    {
        if (! $this->secretConfigured() || ! str_starts_with($checkoutId, 'cs_')) {
            return null;
        }

        try {
            $response = Http::acceptJson()
                ->withBasicAuth($this->secretKey(), '')
                ->connectTimeout(5)
                ->timeout(15)
                ->get(self::CHECKOUT_RETRIEVE_ENDPOINT.'/'.$checkoutId);
        } catch (ConnectionException) {
            throw new PayMongoCheckoutException(
                'PayMongo could not be reached.',
                retryable: true,
            );
        }

        if ($response->failed()) {
            throw new PayMongoCheckoutException(
                'PayMongo rejected checkout retrieval.',
                retryable: $response->status() >= 500,
                responseStatus: $response->status(),
            );
        }

        $id = $response->json('data.id');
        $attributes = $response->json('data.attributes');
        $livemode = $response->json('data.attributes.livemode');
        $reference = $response->json('data.attributes.reference_number')
            ?? $response->json('data.attributes.metadata.merchant_reference');

        if (
            ! is_string($id)
            || $id !== $checkoutId
            || ! is_array($attributes)
            || ! is_bool($livemode)
            || ! is_string($reference)
            || $reference === ''
        ) {
            return null;
        }

        $metadata = $attributes['metadata'] ?? [];

        return [
            'checkout_id' => $id,
            'livemode' => $livemode,
            'reference' => $reference,
            'metadata' => is_array($metadata) ? $metadata : [],
            'attributes' => $attributes,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{id: string, amount: int, currency: string, payment_intent_id: ?string}|null
     */
    public function paidPaymentFrom(array $attributes): ?array
    {
        $collections = [
            $attributes['payments'] ?? null,
            data_get($attributes, 'payment_intent.attributes.payments'),
            data_get($attributes, 'payment_intent.payments'),
        ];

        foreach ($collections as $payments) {
            if (! is_array($payments)) {
                continue;
            }

            foreach ($payments as $payment) {
                $normalized = $this->normalizePaidPayment($payment);
                if ($normalized) {
                    return $normalized;
                }
            }
        }

        return $this->normalizePaidPayment($attributes['payment'] ?? null);
    }

    /**
     * @return array{id: string, amount: int, currency: string, payment_intent_id: ?string}|null
     */
    private function normalizePaidPayment(mixed $payment): ?array
    {
        if (! is_array($payment)) {
            return null;
        }

        if (is_array($payment['data'] ?? null)) {
            $payment = $payment['data'];
        }

        $paymentAttributes = is_array($payment['attributes'] ?? null)
            ? $payment['attributes']
            : $payment;

        if (strtolower((string) ($paymentAttributes['status'] ?? '')) !== 'paid') {
            return null;
        }

        $paymentId = $payment['id'] ?? $paymentAttributes['id'] ?? null;
        $amount = $this->normalizePaidAmount($paymentAttributes['amount'] ?? null);
        $currency = strtoupper((string) ($paymentAttributes['currency'] ?? ''));
        $paymentIntentId = $paymentAttributes['payment_intent_id'] ?? null;

        if (! is_string($paymentId) || ! str_starts_with($paymentId, 'pay_') || $amount === null || $currency === '') {
            return null;
        }

        return [
            'id' => $paymentId,
            'amount' => $amount,
            'currency' => $currency,
            'payment_intent_id' => is_string($paymentIntentId) ? $paymentIntentId : null,
        ];
    }

    private function normalizePaidAmount(mixed $amount): ?int
    {
        if (is_int($amount)) {
            return $amount >= 0 ? $amount : null;
        }

        if (is_float($amount) && is_finite($amount) && abs($amount - round($amount)) < 0.0001) {
            $normalized = (int) round($amount);

            return $normalized >= 0 ? $normalized : null;
        }

        if (is_string($amount) && preg_match('/^\d+$/', $amount) === 1) {
            return (int) $amount;
        }

        return null;
    }

    public function isLiveMode(): bool
    {
        $secret = $this->secretKey();

        if (Str::startsWith($secret, 'sk_live_')) {
            return true;
        }

        if (Str::startsWith($secret, 'sk_test_')) {
            return false;
        }

        return (bool) config('services.paymongo.live_mode', false);
    }

    /**
     * @return list<string>
     */
    public function enabledMethods(): array
    {
        $configured = config('services.paymongo.payment_methods', ['gcash', 'card', 'qrph']);
        $methods = is_array($configured) ? $configured : explode(',', (string) $configured);

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $method): string => strtolower(trim((string) $method)),
            $methods,
        ), static fn (string $method): bool => (bool) preg_match('/^[a-z0-9_]{2,40}$/', $method))));
    }

    /**
     * @return array{checkout_id: string, checkout_url: string, livemode: bool}
     *
     * @throws PayMongoCheckoutException
     */
    public function createCheckout(PaymentAttempt $attempt): array
    {
        if (! $this->secretConfigured()) {
            throw new PayMongoCheckoutException('PayMongo checkout is not configured.');
        }

        if ($attempt->livemode !== $this->isLiveMode()) {
            throw new PayMongoCheckoutException('PayMongo checkout mode does not match the payment attempt.');
        }

        $payload = data_get($attempt->meta, 'checkout_payload');

        if (! is_array($payload)) {
            throw new PayMongoCheckoutException('The PayMongo checkout request is incomplete.');
        }

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->withBasicAuth($this->secretKey(), '')
                ->withHeaders([
                    'Idempotency-Key' => $attempt->idempotency_key,
                ])
                ->connectTimeout(5)
                ->timeout(15)
                ->post(self::CHECKOUT_CREATE_ENDPOINT, $payload);
        } catch (ConnectionException) {
            throw new PayMongoCheckoutException(
                'PayMongo could not be reached.',
                retryable: true,
            );
        }

        if ($response->failed()) {
            $status = $response->status();

            throw new PayMongoCheckoutException(
                'PayMongo rejected checkout creation.',
                retryable: in_array($status, [409, 429], true) || $status >= 500,
                responseStatus: $status,
            );
        }

        $checkoutId = $response->json('data.id');
        $checkoutUrl = $response->json('data.attributes.checkout_url');
        $livemode = $response->json('data.attributes.livemode');

        if (
            ! is_string($checkoutId)
            || ! Str::startsWith($checkoutId, 'cs_')
            || ! is_string($checkoutUrl)
            || ! $this->isTrustedCheckoutUrl($checkoutUrl)
            || ! is_bool($livemode)
            || $livemode !== $this->isLiveMode()
        ) {
            throw new PayMongoCheckoutException('PayMongo returned an invalid checkout response.');
        }

        return [
            'checkout_id' => $checkoutId,
            'checkout_url' => $checkoutUrl,
            'livemode' => $livemode,
        ];
    }

    /**
     * Build once, persist on the attempt, and reuse verbatim with the same
     * idempotency key even if batch/config values change after a timeout.
     *
     * @return array<string, mixed>
     */
    public function buildCheckoutPayload(
        PaymentAttempt $attempt,
        EnrollmentApplication $application,
        ?string $title = null,
        ?string $description = null,
        ?string $successUrl = null,
        ?string $cancelUrl = null,
    ): array {
        $methods = $this->enabledMethods();

        if ($methods === []) {
            throw new PayMongoCheckoutException('No PayMongo payment methods are configured.');
        }

        $lineItemName = $title ?: ('MCARE '.($application->program ?: 'training program').' downpayment');
        $lineItemDesc = $description ?: 'Enrollment downpayment for Mission Care Training Center';

        return [
            'data' => [
                'attributes' => [
                    'line_items' => [[
                        'name' => $lineItemName,
                        'description' => $lineItemDesc,
                        'amount' => $attempt->amount_minor,
                        'currency' => $attempt->currency,
                        'quantity' => 1,
                    ]],
                    'payment_method_types' => $methods,
                    'success_url' => $successUrl ?: route('payment.return'),
                    'cancel_url' => $cancelUrl ?: route('payment.cancel'),
                    'reference_number' => $attempt->merchant_reference,
                    'description' => $description ?: 'MCARE enrollment downpayment',
                    'send_email_receipt' => true,
                    'show_description' => true,
                    'show_line_items' => true,
                    'pass_on_fees' => false,
                    // Keep gateway metadata minimal and free of uploaded-document data.
                    'metadata' => [
                        'application_id' => (string) $application->getKey(),
                        'merchant_reference' => (string) $attempt->merchant_reference,
                        'batch_id' => (string) ($application->training_batch_id ?? ''),
                    ],
                ],
            ],
        ];
    }

    public function isTrustedCheckoutUrl(?string $url): bool
    {
        if (! is_string($url) || $url === '') {
            return false;
        }

        $parts = parse_url($url);

        return is_array($parts)
            && strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && strtolower((string) ($parts['host'] ?? '')) === 'checkout.paymongo.com'
            && ! isset($parts['user'])
            && ! isset($parts['pass'])
            && (! isset($parts['port']) || $parts['port'] === 443);
    }

    private function secretKey(): string
    {
        return trim((string) config('services.paymongo.secret_key'));
    }
}
