<?php

namespace App\Http\Controllers;

use App\Exceptions\PayMongoCheckoutException;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\PaymentAttempt;
use App\Models\PaymentTransaction;
use App\Services\EnrollmentPaymentLifecycle;
use App\Services\BrowsershotOfficialDocumentRenderer;
use App\Services\OfficialReceiptNumberGenerator;
use App\Services\PayMongoCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EnrollmentPaymentController extends Controller
{
    private const DEFAULT_DOWNPAYMENT = '2000.00';

    public function __construct(
        private readonly PayMongoCheckoutService $payMongo,
    ) {}

    public function show(Request $request, EnrollmentPaymentLifecycle $paymentLifecycle): View|RedirectResponse
    {
        $application = $this->applicationFor($request);

        if (! $application) {
            return redirect()
                ->route('enrollment.create')
                ->with('saved', 'Complete your enrollment registration before choosing a payment method.');
        }

        $this->expireStaleReceipt($application);
        $this->expireOppositeModeAttempts($application);
        $application->refresh()->load('batch');

        try {
            $this->confirmOnlinePaymentFromPayMongo($application);
            $application->refresh()->load('batch');
        } catch (PayMongoCheckoutException $exception) {
            Log::warning('PayMongo checkout confirmation failed.', [
                'application_id' => $application->getKey(),
                'retryable' => $exception->retryable,
                'response_status' => $exception->responseStatus,
            ]);
        }

        if ($paymentLifecycle->handleVerifiedPayment($application)) {
            return redirect()->route('payment.complete');
        }

        return view('enrollment.payment', $this->paymentPageData($application));
    }

    public function payments(Request $request): View
    {
        $submittedNumber = (string) $request->input('enrollment_number', $request->query('enrollment_number', ''));
        $lookedUp = $request->isMethod('post') || $request->filled('enrollment_number');
        $application = $lookedUp ? EnrollmentApplication::findByNumber($submittedNumber) : null;

        if (
            $application
            && $request->user()
            && (int) $application->user_id !== (int) $request->user()->id
            && ! $request->user()->hasRole('admin')
        ) {
            $application = null;
        }

        if ($application) {
            $request->session()->put('enrollment.payment_application_id', $application->id);
            $this->expireStaleReceipt($application);
            $this->expireOppositeModeAttempts($application);
            $application->refresh()->load(['batch.program']);

            try {
                $this->confirmOnlinePaymentFromPayMongo($application);
                $application->refresh()->load(['batch.program']);
            } catch (PayMongoCheckoutException $exception) {
                Log::warning('PayMongo checkout confirmation failed.', [
                    'application_id' => $application->getKey(),
                    'retryable' => $exception->retryable,
                    'response_status' => $exception->responseStatus,
                ]);
            }
        }

        $paymentCleared = $application?->hasEnrollmentPaymentClearance() ?? false;

        return view('enrollment.payments', array_merge([
            'lookedUp' => $lookedUp,
            'submittedNumber' => $submittedNumber,
            'application' => $application,
            'paymentCleared' => $paymentCleared,
        ], $application && ! $paymentCleared
            ? $this->paymentPageData($application)
            : $this->emptyPaymentPageData()));
    }

    public function lookup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enrollment_number' => ['required', 'string', 'max:40'],
        ]);

        return redirect()->route('payments.show', [
            'enrollment_number' => EnrollmentApplication::normalizeNumber($validated['enrollment_number']),
        ]);
    }

    public function select(Request $request, EnrollmentPaymentLifecycle $paymentLifecycle): RedirectResponse
    {
        $validated = $request->validate([
            'payment_method' => ['required', 'in:onsite,online'],
        ]);

        $application = $this->applicationFor($request);

        if (! $application) {
            return redirect()->route('enrollment.create');
        }

        $this->expireStaleReceipt($application);
        $this->expireOppositeModeAttempts($application);
        $application->refresh();

        if ($paymentLifecycle->handleVerifiedPayment($application)) {
            return redirect()->route('payment.complete');
        }

        // A confirmed payment is terminal; UI retries must never downgrade it.
        if ($application->payment_status === EnrollmentApplication::PAYMENT_PAID) {
            return redirect()
                ->route('payment.show')
                ->with('payment_notice', 'Your payment is already confirmed.');
        }

        if ($application->payment_status === EnrollmentApplication::PAYMENT_ONSITE_PENDING) {
            return redirect()
                ->route('payment.receipt')
                ->with('payment_notice', 'Pay on site is already selected. Use the receipt when you pay at MCARE.');
        }

        if ($validated['payment_method'] === 'onsite') {
            try {
                $this->confirmOnlinePaymentFromPayMongo($application);
            } catch (PayMongoCheckoutException $exception) {
                Log::warning('PayMongo confirmation skipped while switching to pay on site.', [
                    'application_id' => $application->getKey(),
                    'retryable' => $exception->retryable,
                    'response_status' => $exception->responseStatus,
                ]);
            }

            $application->refresh();

            if ($paymentLifecycle->handleVerifiedPayment($application)) {
                return redirect()->route('payment.complete');
            }

            $this->expireUnpaidOnlineAttempts($application);
            $application->refresh();

            if (! $this->prepareOnsitePayment($application)) {
                $application->refresh();

                if ($application->payment_status === EnrollmentApplication::PAYMENT_PAID) {
                    return redirect()
                        ->route('payment.show')
                        ->with('payment_notice', 'Your payment is already confirmed.');
                }

                return redirect()
                    ->route('payment.show')
                    ->withErrors([
                        'payment' => 'A PayMongo checkout is still active. Continue that checkout or contact MCARE before changing payment methods.',
                    ]);
            }

            $application->refresh();
            $paymentLifecycle->sendOfficialReceipt($application, allowPending: true);

            return redirect()
                ->route('payment.receipt')
                ->with('payment_notice', 'Pay-on-site receipt created. Bring this official receipt and reference to MCARE before it expires.');
        }

        if (! $this->payMongo->ready()) {
            return redirect()
                ->route('payment.show')
                ->withErrors([
                    'payment' => 'Online payment is temporarily unavailable. Add a valid PayMongo secret key or choose Pay on site.',
                ]);
        }

        try {
            $checkoutUrl = $this->prepareOnlinePayment($application);
        } catch (PayMongoCheckoutException $exception) {
            // Log only operational identifiers; never log keys or PayMongo response bodies.
            Log::warning('PayMongo checkout creation failed.', [
                'application_id' => $application->getKey(),
                'retryable' => $exception->retryable,
                'response_status' => $exception->responseStatus,
            ]);

            return redirect()
                ->route('payment.show')
                ->withErrors([
                    'payment' => 'Secure checkout could not be started. No payment was recorded. Please wait a moment and try again.',
                ]);
        }

        if (! $checkoutUrl) {
            return redirect()
                ->route('payment.show')
                ->with('payment_notice', 'Your payment is already confirmed.');
        }

        return redirect()->away($checkoutUrl);
    }

    public function returned(Request $request, EnrollmentPaymentLifecycle $paymentLifecycle): RedirectResponse
    {
        $application = $this->applicationFor($request);

        if (! $application) {
            return redirect()->route('enrollment.create');
        }

        try {
            $this->confirmOnlinePaymentFromPayMongo($application);
        } catch (PayMongoCheckoutException $exception) {
            Log::warning('PayMongo checkout confirmation failed.', [
                'application_id' => $application->getKey(),
                'retryable' => $exception->retryable,
                'response_status' => $exception->responseStatus,
            ]);

            return redirect()
                ->route('payment.show')
                ->withErrors([
                    'payment' => 'PayMongo could not confirm the checkout yet. No payment was recorded as paid. Open checkout again or try this return link in a moment.',
                ]);
        }

        $application->refresh();

        if ($paymentLifecycle->handleVerifiedPayment($application)) {
            return redirect()->route('payment.complete');
        }

        $notice = $application->payment_status === EnrollmentApplication::PAYMENT_PAID
            ? 'Payment confirmed. Your MCARE payment record is now updated.'
            : 'You returned from PayMongo. The checkout is not marked paid yet, so you can open it again or choose Pay on site later.';

        return redirect()
            ->route('payment.show')
            ->with('payment_notice', $notice);
    }

    public function cancelled(Request $request): RedirectResponse
    {
        if (! $this->applicationFor($request)) {
            return redirect()->route('enrollment.create');
        }

        return redirect()
            ->route('payment.show')
            ->with('payment_notice', 'Checkout was closed. No payment was confirmed, and you may continue the same secure checkout when ready.');
    }

    public function status(Request $request, EnrollmentPaymentLifecycle $paymentLifecycle): JsonResponse
    {
        $application = $this->applicationFor($request);

        abort_unless($application, 404);
        $paymentLifecycle->handleVerifiedPayment($application);
        $application->refresh();

        return response()->json([
            'status' => $application->payment_status,
            'label' => $application->paymentStatusLabel(),
            'paid' => $application->payment_status === EnrollmentApplication::PAYMENT_PAID,
            'payment_verified' => $application->hasEnrollmentPaymentClearance(),
            'application_status' => $application->status,
            'account_approved' => $application->status === EnrollmentApplication::STATUS_APPROVED,
            'account_denied' => $application->status === EnrollmentApplication::STATUS_DENIED,
            'completion_url' => route('payment.complete'),
            'verified_at' => $application->payment_verified_at?->toIso8601String(),
        ]);
    }

    public function completed(Request $request, EnrollmentPaymentLifecycle $paymentLifecycle): RedirectResponse
    {
        $application = $this->applicationFor($request);

        if (! $application) {
            return redirect()
                ->route('landing')
                ->with('payment_error', 'Your payment session expired. Check your email for verification and account-review updates.');
        }

        if (! $paymentLifecycle->handleVerifiedPayment($application)) {
            return redirect()
                ->route('payment.show')
                ->withErrors(['payment' => 'Your required enrollment payment is still waiting for verification.']);
        }

        $application->refresh();
        if ($request->user()?->role === 'applicant') {
            Auth::logout();
            $request->session()->regenerate();
        }

        if ($application->status === EnrollmentApplication::STATUS_DENIED) {
            $request->session()->forget('enrollment.awaiting_approval');

            return redirect()
                ->route('landing')
                ->with('account_denied', 'Your enrollment application was not approved. Your verified payment remains recorded; contact MCARE administration regarding the next steps.');
        }

        if ($application->status === EnrollmentApplication::STATUS_APPROVED) {
            $request->session()->forget('enrollment.awaiting_approval');

            if ($request->user()?->role === 'trainee') {
                return redirect()
                    ->route('trainee.payments')
                    ->with('payment_notice', 'Your official payment slip is ready to view and print.');
            }

            return redirect()
                ->route('landing')
                ->with('account_approved', 'Your payment and MCARE account are approved. You can now log in.');
        }

        $request->session()->put('enrollment.awaiting_approval', true);

        return redirect()
            ->route('landing')
            ->with('payment_verified', 'Payment verified successfully. Please wait while the administrator completes your account verification. We will email you when you can log in.');
    }

    public function receipt(Request $request, EnrollmentPaymentLifecycle $paymentLifecycle): View|RedirectResponse
    {
        $application = $this->applicationFor($request);

        if (! $application || ! $application->canViewPaymentSlip()) {
            return redirect()
                ->route($this->receiptReturnRoute($request))
                ->with('payment_notice', 'Choose Pay on site first to generate a receipt.');
        }

        $this->expireStaleReceipt($application);
        $application->refresh();

        if (
            $this->shouldFinishEnrollmentAfterVerifiedReceipt($request, $application)
            && $paymentLifecycle->handleVerifiedPayment($application)
        ) {
            return redirect()->route('payment.complete');
        }

        return view('enrollment.receipt', [
            'application' => $application->refresh()->load(['batch', 'paymentTransactions']),
            'downloadMode' => false,
            'receiptReturnUrl' => $this->receiptReturnUrl($request),
            'receiptReturnLabel' => $this->receiptReturnLabel($request),
        ]);
    }

    public function downloadReceipt(Request $request, BrowsershotOfficialDocumentRenderer $pdfRenderer): Response|RedirectResponse
    {
        $application = $this->applicationFor($request);

        if (! $application || ! $application->canViewPaymentSlip()) {
            return redirect()->route($this->receiptReturnRoute($request));
        }

        $this->expireStaleReceipt($application);
        $application->refresh()->load(['batch', 'paymentTransactions']);

        $html = view('enrollment.receipt', [
            'application' => $application,
            'downloadMode' => true,
            'receiptReturnUrl' => $this->receiptReturnUrl($request),
            'receiptReturnLabel' => $this->receiptReturnLabel($request),
        ])->render();

        $pdf = $pdfRenderer->renderHtml($html, 'Letter');

        $filename = 'mcare-receipt-'.($application->payment_reference ?: $application->payment_receipt_number ?: 'slip').'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function applicationFor(Request $request): ?EnrollmentApplication
    {
        $sessionId = $request->session()->get('enrollment.payment_application_id');
        $fromSession = is_numeric($sessionId)
            ? EnrollmentApplication::query()->whereKey((int) $sessionId)->first()
            : null;

        if ($fromSession && ! $request->user()) {
            return $fromSession;
        }

        if ($fromSession && $request->user() && (int) $fromSession->user_id === (int) $request->user()->id) {
            return $fromSession;
        }

        if ($request->user()) {
            return EnrollmentApplication::where('user_id', $request->user()->id)
                ->latest()
                ->first();
        }

        return null;
    }

    private function shouldFinishEnrollmentAfterVerifiedReceipt(Request $request, EnrollmentApplication $application): bool
    {
        if ($application->status === EnrollmentApplication::STATUS_APPROVED) {
            return false;
        }

        return ! in_array($request->user()?->role, ['trainee', 'trainer', 'admin', 'alumni'], true);
    }

    private function receiptReturnRoute(Request $request): string
    {
        return $request->user()?->role === 'trainee' ? 'trainee.payments' : 'payment.show';
    }

    private function receiptReturnUrl(Request $request): string
    {
        return route($this->receiptReturnRoute($request));
    }

    private function receiptReturnLabel(Request $request): string
    {
        return $request->user()?->role === 'trainee' ? 'Back to payments' : 'Back to payment';
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentPageData(EnrollmentApplication $application): array
    {
        $activeAttempt = $application->paymentAttempts()
            ->where('provider', 'paymongo')
            ->where('status', PaymentAttempt::STATUS_PENDING)
            ->where('livemode', $this->payMongo->isLiveMode())
            ->latest()
            ->first();

        return [
            'application' => $application,
            'paymongoConfigured' => $this->payMongo->secretConfigured(),
            'paymongoReady' => $this->payMongo->ready(),
            'paymongoLiveMode' => $this->payMongo->isLiveMode(),
            'paymongoMethods' => $this->payMongo->enabledMethods(),
            'paymongoModeConflict' => $application->paymentAttempts()
                ->where('provider', 'paymongo')
                ->whereIn('status', [
                    PaymentAttempt::STATUS_CREATING,
                    PaymentAttempt::STATUS_PENDING,
                ])
                ->where('livemode', '!=', $this->payMongo->isLiveMode())
                ->exists(),
            'activeCheckoutUrl' => $activeAttempt
                && $this->payMongo->isTrustedCheckoutUrl($activeAttempt->checkout_url)
                    ? $activeAttempt->checkout_url
                    : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPaymentPageData(): array
    {
        return [
            'paymongoConfigured' => false,
            'paymongoReady' => false,
            'paymongoLiveMode' => false,
            'paymongoMethods' => [],
            'paymongoModeConflict' => false,
            'activeCheckoutUrl' => null,
        ];
    }

    private function prepareOnsitePayment(EnrollmentApplication $application): bool
    {
        return DB::transaction(function () use ($application): bool {
            $lockedApplication = EnrollmentApplication::query()
                ->whereKey($application->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedApplication->load('batch');

            if ($lockedApplication->payment_status === EnrollmentApplication::PAYMENT_PAID) {
                return false;
            }

            $refNumber = $lockedApplication->payment_reference ?: $this->uniqueReference('MCARE-SITE');
            $orNumber = $lockedApplication->payment_receipt_number
                ?: app(OfficialReceiptNumberGenerator::class)->generate();
            $expiresAt = $lockedApplication->payment_receipt_expires_at ?: $this->defaultDeadlineFor($lockedApplication);
            $downpayment = round((float) ($lockedApplication->downpayment_amount ?: self::DEFAULT_DOWNPAYMENT), 2);

            $lockedApplication->forceFill([
                'payment_method' => 'onsite',
                'payment_status' => $lockedApplication->payment_status === EnrollmentApplication::PAYMENT_PAID ? EnrollmentApplication::PAYMENT_PAID : EnrollmentApplication::PAYMENT_ONSITE_PENDING,
                'payment_amount' => $downpayment,
                'payment_currency' => 'PHP',
                'payment_reference' => $refNumber,
                'payment_receipt_number' => $orNumber,
                'payment_receipt_expires_at' => $expiresAt,
                'payment_selected_at' => $lockedApplication->payment_selected_at ?: now(),
                'paymongo_checkout_reference' => null,
                'paymongo_checkout_url' => null,
                'payment_meta' => [
                    'channel' => 'on_site',
                    'issued_by' => 'MCARE Hub',
                    'note' => 'Bring the official receipt and reference number to the MCARE cashier for verification.',
                    'batch' => $lockedApplication->batch?->name,
                    'batch_deadline' => $lockedApplication->batch?->enrollment_ends_at?->toDateTimeString(),
                ],
            ])->save();

            $ticket = PaymentTransaction::firstOrCreate(
                [
                    'enrollment_application_id' => $lockedApplication->id,
                    'payment_channel' => PaymentTransaction::CHANNEL_ONSITE,
                    'transaction_type' => PaymentTransaction::TYPE_DOWNPAYMENT,
                    'ticket_number' => $refNumber,
                ],
                [
                    'user_id' => $lockedApplication->user_id,
                    'reference_number' => $refNumber,
                    'or_number' => $orNumber,
                    'amount' => $downpayment,
                    'status' => PaymentTransaction::STATUS_PENDING,
                    'paid_at' => now(),
                    'notes' => 'On-site downpayment order (₱'.number_format($downpayment, 2).') for cashier verification.',
                ]
            );

            if (blank($ticket->or_number) || blank($ticket->reference_number)) {
                $ticket->forceFill([
                    'reference_number' => $ticket->reference_number ?: $refNumber,
                    'or_number' => $ticket->or_number ?: $orNumber,
                ])->save();
            }

            return true;
        }, 3);
    }

    /**
     * Persist the idempotency key before contacting PayMongo. Concurrent
     * submissions then reuse one provider request instead of charging twice.
     */
    private function prepareOnlinePayment(EnrollmentApplication $application): ?string
    {
        $attempt = DB::transaction(function () use ($application): ?PaymentAttempt {
            $lockedApplication = EnrollmentApplication::query()
                ->whereKey($application->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedApplication->payment_status === EnrollmentApplication::PAYMENT_PAID) {
                return null;
            }

            $existing = PaymentAttempt::query()
                ->where('enrollment_application_id', $lockedApplication->getKey())
                ->where('provider', 'paymongo')
                ->whereIn('status', [
                    PaymentAttempt::STATUS_CREATING,
                    PaymentAttempt::STATUS_PENDING,
                ])
                ->latest()
                ->lockForUpdate()
                ->first();

            if ($existing && $existing->livemode !== $this->payMongo->isLiveMode()) {
                $existing->forceFill([
                    'status' => PaymentAttempt::STATUS_EXPIRED,
                    'expired_at' => now(),
                    'meta' => array_merge($existing->meta ?? [], [
                        'expired_reason' => 'paymongo_mode_changed',
                    ]),
                ])->save();
                $existing = null;
            }

            if ($existing) {
                if (! is_array(data_get($existing->meta, 'checkout_payload'))) {
                    $payload = $this->payMongo->buildCheckoutPayload($existing, $lockedApplication);
                    $existing->forceFill([
                        'meta' => array_merge($existing->meta ?? [], [
                            'checkout_payload' => $payload,
                            'checkout_payload_sha256' => hash(
                                'sha256',
                                json_encode($payload, JSON_THROW_ON_ERROR),
                            ),
                        ]),
                    ])->save();
                }

                return $existing;
            }

            $downpaymentMinor = (int) round(
                (float) ($lockedApplication->downpayment_amount ?: self::DEFAULT_DOWNPAYMENT) * 100,
            );

            $createdAttempt = PaymentAttempt::create([
                'enrollment_application_id' => $lockedApplication->getKey(),
                'provider' => 'paymongo',
                'merchant_reference' => $this->uniqueReference('MCARE-ONLINE'),
                'idempotency_key' => (string) Str::uuid(),
                'amount_minor' => $downpaymentMinor,
                'currency' => 'PHP',
                'status' => PaymentAttempt::STATUS_CREATING,
                'livemode' => $this->payMongo->isLiveMode(),
                'meta' => [
                    'created_for' => 'enrollment_downpayment',
                    'created_at' => now()->toIso8601String(),
                ],
            ]);

            $payload = $this->payMongo->buildCheckoutPayload($createdAttempt, $lockedApplication);
            $createdAttempt->forceFill([
                'meta' => array_merge($createdAttempt->meta ?? [], [
                    'checkout_payload' => $payload,
                    'checkout_payload_sha256' => hash(
                        'sha256',
                        json_encode($payload, JSON_THROW_ON_ERROR),
                    ),
                ]),
            ])->save();

            return $createdAttempt;
        }, 3);

        if (! $attempt) {
            return null;
        }

        if (
            $attempt->status === PaymentAttempt::STATUS_PENDING
            && $this->payMongo->isTrustedCheckoutUrl($attempt->checkout_url)
        ) {
            return $attempt->checkout_url;
        }

        try {
            $checkout = $this->payMongo->createCheckout($attempt);
        } catch (PayMongoCheckoutException $exception) {
            $failureOutcome = DB::transaction(function () use ($attempt, $exception): array {
                $lockedAttempt = PaymentAttempt::query()
                    ->whereKey($attempt->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                // A concurrent success always wins over this stale failure.
                if (
                    $lockedAttempt->status === PaymentAttempt::STATUS_PENDING
                    && $this->payMongo->isTrustedCheckoutUrl($lockedAttempt->checkout_url)
                ) {
                    return ['checkout_url' => $lockedAttempt->checkout_url];
                }

                if ($lockedAttempt->status === PaymentAttempt::STATUS_PAID) {
                    return ['paid' => true];
                }

                $lockedAttempt->forceFill([
                    // Unknown network/409/429/5xx outcomes retain the same key.
                    'status' => $exception->retryable
                        ? PaymentAttempt::STATUS_CREATING
                        : PaymentAttempt::STATUS_FAILED,
                    'meta' => array_merge($lockedAttempt->meta ?? [], [
                        'last_failure_at' => now()->toIso8601String(),
                        'last_failure_status' => $exception->responseStatus,
                        'retryable' => $exception->retryable,
                    ]),
                ])->save();

                return [];
            }, 3);

            if (isset($failureOutcome['checkout_url'])) {
                return $failureOutcome['checkout_url'];
            }

            if (isset($failureOutcome['paid'])) {
                return null;
            }

            throw $exception;
        }

        $stillPayable = DB::transaction(function () use ($application, $attempt, $checkout): bool {
            $lockedApplication = EnrollmentApplication::query()
                ->whereKey($application->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedAttempt = PaymentAttempt::query()
                ->whereKey($attempt->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedApplication->payment_status === EnrollmentApplication::PAYMENT_PAID) {
                if ($lockedAttempt->status !== PaymentAttempt::STATUS_PAID) {
                    $lockedAttempt->forceFill([
                        'status' => PaymentAttempt::STATUS_EXPIRED,
                        'expired_at' => now(),
                    ])->save();
                }

                return false;
            }

            $lockedAttempt->forceFill([
                'provider_checkout_id' => $checkout['checkout_id'],
                'checkout_url' => $checkout['checkout_url'],
                'status' => PaymentAttempt::STATUS_PENDING,
                'meta' => array_merge($lockedAttempt->meta ?? [], [
                    'checkout_created_at' => now()->toIso8601String(),
                ]),
            ])->save();

            $lockedApplication->forceFill([
                'payment_method' => 'online',
                'payment_status' => EnrollmentApplication::PAYMENT_ONLINE_PENDING,
                'payment_amount' => $this->amountFromMinor($lockedAttempt->amount_minor),
                'payment_currency' => 'PHP',
                'payment_reference' => $lockedAttempt->merchant_reference,
                'payment_receipt_number' => null,
                'payment_receipt_expires_at' => null,
                'payment_selected_at' => now(),
                'paymongo_checkout_reference' => $checkout['checkout_id'],
                'paymongo_checkout_url' => $checkout['checkout_url'],
                'payment_meta' => [
                    'channel' => 'paymongo',
                    'gateway_verified' => false,
                    'payment_attempt_id' => $lockedAttempt->getKey(),
                    'accepted_methods' => data_get(
                        $lockedAttempt->meta,
                        'checkout_payload.data.attributes.payment_method_types',
                        [],
                    ),
                    'mode' => $checkout['livemode'] ? 'live' : 'test',
                    'batch' => $lockedApplication->batch?->name,
                    'batch_deadline' => $lockedApplication->batch?->enrollment_ends_at?->toDateTimeString(),
                    'payment_deadline' => $lockedApplication->batch?->enrollment_ends_at?->toDateTimeString(),
                ],
            ])->save();

            return true;
        }, 3);

        return $stillPayable ? $checkout['checkout_url'] : null;
    }

    private function confirmOnlinePaymentFromPayMongo(EnrollmentApplication $application): void
    {
        $checkoutId = $application->paymongo_checkout_reference;

        if (
            $application->payment_status === EnrollmentApplication::PAYMENT_PAID
            || $application->payment_method !== 'online'
            || ! is_string($checkoutId)
            || ! str_starts_with($checkoutId, 'cs_')
        ) {
            return;
        }

        $session = $this->payMongo->retrieveCheckout($checkoutId);
        $payment = $session ? $this->payMongo->paidPaymentFrom($session['attributes']) : null;

        if (! $session || ! $payment) {
            return;
        }

        DB::transaction(function () use ($application, $session, $payment): void {
            $lockedApplication = EnrollmentApplication::query()
                ->whereKey($application->getKey())
                ->lockForUpdate()
                ->first();

            if (
                ! $lockedApplication
                || $lockedApplication->payment_status === EnrollmentApplication::PAYMENT_PAID
                || $lockedApplication->payment_method !== 'online'
            ) {
                return;
            }

            $attempt = PaymentAttempt::query()
                ->where('enrollment_application_id', $lockedApplication->getKey())
                ->where('provider', 'paymongo')
                ->where('provider_checkout_id', $session['checkout_id'])
                ->where('merchant_reference', $session['reference'])
                ->lockForUpdate()
                ->first();

            if (
                ! $attempt
                || $attempt->livemode !== $session['livemode']
                || $attempt->livemode !== $this->payMongo->isLiveMode()
                || $payment['amount'] !== $attempt->amount_minor
                || $payment['currency'] !== strtoupper($attempt->currency)
                || $lockedApplication->payment_reference !== $attempt->merchant_reference
                || $lockedApplication->paymongo_checkout_reference !== $attempt->provider_checkout_id
                || strtoupper((string) $lockedApplication->payment_currency) !== strtoupper($attempt->currency)
            ) {
                return;
            }

            $metadataApplicationId = $session['metadata']['application_id'] ?? null;
            if (
                filled($metadataApplicationId)
                && (string) $metadataApplicationId !== (string) $lockedApplication->getKey()
            ) {
                return;
            }

            $paidAt = now();
            $amount = round($attempt->amount_minor / 100, 2);
            $transactionType = $amount >= (float) ($lockedApplication->total_program_fee ?? 22000.00)
                ? PaymentTransaction::TYPE_FULL_PAYMENT
                : PaymentTransaction::TYPE_DOWNPAYMENT;

            $attempt->forceFill([
                'provider_payment_id' => $payment['id'],
                'provider_payment_intent_id' => $payment['payment_intent_id'],
                'status' => PaymentAttempt::STATUS_PAID,
                'paid_at' => $paidAt,
                'meta' => array_merge($attempt->meta ?? [], [
                    'verified_at' => $paidAt->toIso8601String(),
                    'verified_from' => 'payment.return',
                ]),
            ])->save();

            $existingOnline = PaymentTransaction::query()
                ->where('enrollment_application_id', $lockedApplication->id)
                ->where('payment_channel', PaymentTransaction::CHANNEL_ONLINE)
                ->where('reference_number', $payment['id'])
                ->first();

            $orNumber = $existingOnline?->or_number
                ?: $lockedApplication->payment_receipt_number
                ?: app(OfficialReceiptNumberGenerator::class)->generate();

            PaymentTransaction::query()->updateOrCreate([
                'enrollment_application_id' => $lockedApplication->id,
                'payment_channel' => PaymentTransaction::CHANNEL_ONLINE,
                'reference_number' => $payment['id'],
            ], [
                'user_id' => $lockedApplication->user_id,
                'transaction_type' => $transactionType,
                'amount' => $amount,
                'or_number' => $orNumber,
                'status' => PaymentTransaction::STATUS_VERIFIED,
                'paid_at' => $paidAt,
                'verified_at' => $paidAt,
                'notes' => 'Verified from the PayMongo checkout session after the applicant returned to MCARE.',
            ]);

            $lockedApplication->forceFill([
                'payment_amount' => $this->amountFromMinor($attempt->amount_minor),
                'payment_verified_by_id' => null,
                'payment_verified_at' => $paidAt,
                'payment_verification_notes' => 'Verified from the PayMongo checkout session after the applicant returned to MCARE.',
                'payment_receipt_number' => $orNumber,
                'payment_meta' => array_merge($lockedApplication->payment_meta ?? [], [
                    'gateway_verified' => true,
                    'gateway' => 'paymongo',
                    'paymongo_payment_id' => $payment['id'],
                    'paymongo_payment_intent_id' => $payment['payment_intent_id'],
                    'gateway_verified_at' => $paidAt->toIso8601String(),
                ]),
            ])->save();
            $lockedApplication->recalculatePaymentStatus();

            AdminActivityLog::record(null, 'payment.paymongo.verified', $lockedApplication, [
                'checkout_id' => $session['checkout_id'],
                'payment_reference' => $attempt->merchant_reference,
                'amount_minor' => $attempt->amount_minor,
                'currency' => $attempt->currency,
                'livemode' => $attempt->livemode,
            ]);
        }, 3);
    }

    private function expireUnpaidOnlineAttempts(EnrollmentApplication $application): void
    {
        DB::transaction(function () use ($application): void {
            $lockedApplication = EnrollmentApplication::query()
                ->whereKey($application->getKey())
                ->lockForUpdate()
                ->first();

            if (
                ! $lockedApplication
                || $lockedApplication->payment_status === EnrollmentApplication::PAYMENT_PAID
            ) {
                return;
            }

            $attempts = PaymentAttempt::query()
                ->where('enrollment_application_id', $lockedApplication->getKey())
                ->where('provider', 'paymongo')
                ->whereIn('status', [
                    PaymentAttempt::STATUS_CREATING,
                    PaymentAttempt::STATUS_PENDING,
                ])
                ->lockForUpdate()
                ->get();

            $now = now();

            foreach ($attempts as $attempt) {
                $attempt->forceFill([
                    'status' => PaymentAttempt::STATUS_EXPIRED,
                    'expired_at' => $now,
                    'meta' => array_merge($attempt->meta ?? [], [
                        'expired_reason' => 'switched_to_onsite',
                        'expired_at' => $now->toIso8601String(),
                    ]),
                ])->save();
            }
        }, 3);
    }

    /**
     * An unpaid checkout from the other PayMongo mode cannot be confirmed
     * with the current secret key, so it must not block a new checkout.
     */
    private function expireOppositeModeAttempts(EnrollmentApplication $application): void
    {
        DB::transaction(function () use ($application): void {
            $lockedApplication = EnrollmentApplication::query()
                ->whereKey($application->getKey())
                ->lockForUpdate()
                ->first();

            if (
                ! $lockedApplication
                || $lockedApplication->payment_status === EnrollmentApplication::PAYMENT_PAID
            ) {
                return;
            }

            $currentLive = $this->payMongo->isLiveMode();
            $mismatched = PaymentAttempt::query()
                ->where('enrollment_application_id', $lockedApplication->getKey())
                ->where('provider', 'paymongo')
                ->whereIn('status', [
                    PaymentAttempt::STATUS_CREATING,
                    PaymentAttempt::STATUS_PENDING,
                ])
                ->where('livemode', '!=', $currentLive)
                ->lockForUpdate()
                ->get();

            if ($mismatched->isEmpty()) {
                return;
            }

            $now = now();
            $expiredCheckoutIds = [];

            foreach ($mismatched as $attempt) {
                $attempt->forceFill([
                    'status' => PaymentAttempt::STATUS_EXPIRED,
                    'expired_at' => $now,
                    'meta' => array_merge($attempt->meta ?? [], [
                        'expired_reason' => 'paymongo_mode_changed',
                        'expired_at' => $now->toIso8601String(),
                    ]),
                ])->save();

                if (is_string($attempt->provider_checkout_id) && $attempt->provider_checkout_id !== '') {
                    $expiredCheckoutIds[] = $attempt->provider_checkout_id;
                }
            }

            $pointsAtExpired = filled($lockedApplication->paymongo_checkout_reference)
                && in_array($lockedApplication->paymongo_checkout_reference, $expiredCheckoutIds, true);
            $hasCurrentModeAttempt = PaymentAttempt::query()
                ->where('enrollment_application_id', $lockedApplication->getKey())
                ->where('provider', 'paymongo')
                ->whereIn('status', [
                    PaymentAttempt::STATUS_CREATING,
                    PaymentAttempt::STATUS_PENDING,
                ])
                ->where('livemode', $currentLive)
                ->exists();

            if (! $pointsAtExpired && $hasCurrentModeAttempt) {
                return;
            }

            $lockedApplication->forceFill([
                'payment_status' => $lockedApplication->payment_status === EnrollmentApplication::PAYMENT_ONLINE_PENDING
                    ? EnrollmentApplication::PAYMENT_NOT_SELECTED
                    : $lockedApplication->payment_status,
                'paymongo_checkout_reference' => $pointsAtExpired ? null : $lockedApplication->paymongo_checkout_reference,
                'paymongo_checkout_url' => $pointsAtExpired ? null : $lockedApplication->paymongo_checkout_url,
            ])->save();
        }, 3);
    }

    private function expireStaleReceipt(EnrollmentApplication $application): void
    {
        DB::transaction(function () use ($application): void {
            $lockedApplication = EnrollmentApplication::query()
                ->whereKey($application->getKey())
                ->lockForUpdate()
                ->first();

            if (
                ! $lockedApplication
                || $lockedApplication->payment_status !== EnrollmentApplication::PAYMENT_ONSITE_PENDING
            ) {
                return;
            }

            $lockedApplication->load('batch');
            $deadline = $lockedApplication->effectivePaymentDeadline();

            if (! $deadline || $deadline->isFuture()) {
                return;
            }

            $lockedApplication->forceFill([
                'payment_status' => EnrollmentApplication::PAYMENT_EXPIRED,
            ])->save();
        }, 3);
    }

    private function uniqueReference(string $prefix): string
    {
        do {
            $reference = $prefix.'-'.now()->format('ymd').'-'.Str::upper(Str::random(8));
        } while (
            EnrollmentApplication::where('payment_reference', $reference)->exists()
            || EnrollmentApplication::where('payment_receipt_number', $reference)->exists()
            || EnrollmentApplication::where('paymongo_checkout_reference', $reference)->exists()
            || PaymentAttempt::where('merchant_reference', $reference)->exists()
            || PaymentTransaction::where('ticket_number', $reference)->exists()
            || PaymentTransaction::where('reference_number', $reference)->exists()
        );

        return $reference;
    }

    private function amountFromMinor(int $amountMinor): string
    {
        return number_format($amountMinor / 100, 2, '.', '');
    }

    private function defaultDeadlineFor(EnrollmentApplication $application)
    {
        return $application->batch?->enrollment_ends_at ?: now()->addDays(7)->endOfDay();
    }
}
