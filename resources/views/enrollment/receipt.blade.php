<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>MCARE Receipt {{ $application->payment_reference ?: $application->payment_receipt_number }}</title>
    <x-site-favicon />
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            background: #f4f4f5;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            font-weight: normal;
            padding: max(0.75rem, env(safe-area-inset-top)) max(0.75rem, env(safe-area-inset-right)) max(1.25rem, env(safe-area-inset-bottom)) max(0.75rem, env(safe-area-inset-left));
        }

        .page {
            width: min(28rem, 100%);
            margin: 0 auto;
        }

        .actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 0.85rem;
        }

        .actions-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 2.5rem;
            padding: 0 1rem;
            border: 0;
            background: #fff;
            color: #334155;
            text-decoration: none;
            font-size: 0.875rem;
            cursor: pointer;
            font-family: Arial, Helvetica, sans-serif;
        }

        .button-primary {
            background: #6b21a8;
            color: #fff;
        }

        .status-banner {
            margin: 0 auto 0.85rem;
            padding: 0.85rem 0.95rem;
            background: #fff8e8;
            color: #7a4b0a;
            font-size: 0.8125rem;
            line-height: 1.55;
        }

        .receipt-container {
            background: #fff;
            padding: 1.5rem 1.25rem 1.35rem;
        }

        .brand {
            text-align: center;
        }

        .brand img {
            display: block;
            width: 2.75rem;
            height: 2.75rem;
            margin: 0 auto 0.45rem;
            object-fit: contain;
        }

        .brand h1 {
            margin: 0;
            font-size: 0.95rem;
            font-weight: normal;
            line-height: 1.35;
        }

        .brand p {
            margin: 0.3rem 0 0;
            color: #6b7280;
            font-size: 0.75rem;
            line-height: 1.45;
        }

        .receipt-title {
            margin: 1rem 0 0;
            text-align: center;
            font-size: 0.78rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .receipt-title span {
            display: inline-block;
            margin-top: 0.45rem;
            color: #6b21a8;
            font-size: 0.7rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .rule {
            height: 0;
            margin: 0.9rem 0;
            border: 0;
            border-top: 1px solid #d4d4d8;
        }

        .meta {
            display: grid;
            gap: 0.55rem;
            text-align: center;
        }

        .meta span {
            display: block;
            color: #6b7280;
            font-size: 0.7rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .meta strong {
            display: block;
            margin-top: 0.2rem;
            font-size: 1rem;
            font-weight: normal;
            line-height: 1.3;
            overflow-wrap: anywhere;
        }

        .rows {
            display: grid;
            gap: 0.7rem;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            font-size: 0.875rem;
        }

        .row .label {
            color: #6b7280;
        }

        .row .value {
            margin: 0;
            text-align: right;
            overflow-wrap: anywhere;
        }

        .row.is-block {
            flex-direction: column;
            gap: 0.2rem;
        }

        .row.is-block .value {
            text-align: left;
        }

        .total {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.7rem 0;
            font-size: 0.875rem;
        }

        .total strong {
            font-size: 1.15rem;
            font-weight: normal;
        }

        .notice,
        .footer {
            color: #6b7280;
            font-size: 0.75rem;
            line-height: 1.5;
            text-align: center;
        }

        .cashier {
            display: grid;
            gap: 0.7rem;
        }

        .cashier p {
            margin: 0;
            color: #6b7280;
            font-size: 0.7rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .cashier-line {
            display: flex;
            align-items: flex-end;
            gap: 0.5rem;
            min-height: 1.45rem;
            font-size: 0.8rem;
        }

        .cashier-line b {
            flex: 1 1 auto;
            min-height: 1rem;
            border-bottom: 1px solid #111827;
            font-weight: normal;
        }

        .stamp-box {
            min-height: 3.5rem;
            display: grid;
            place-items: center;
            color: #9ca3af;
            font-size: 0.75rem;
            text-align: center;
        }

        @media (min-width: 640px) {
            body { padding: 1.5rem; }

            .actions,
            .actions-group {
                flex-direction: row;
                align-items: center;
            }

            .actions { justify-content: space-between; }

            .actions-group { flex: 1 1 auto; justify-content: flex-end; }

            .button { width: auto; }
        }

        @page {
            size: Letter portrait;
            margin: 10mm;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .actions,
            .status-banner { display: none !important; }

            .page {
                width: 196mm;
                max-width: 196mm;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    @php
        $fullName = collect([
            $application->first_name,
            $application->middle_name,
            $application->last_name,
            $application->extension_name,
        ])->filter(fn ($part) => filled($part))->join(' ');
        $batch = $application->batch;
        $scheduleLabel = $batch?->scheduleLabelFor($application->schedule_preference) ?? 'Schedule to be confirmed by admin';
        $roomLabel = $batch?->roomFor($application->schedule_preference) ?: 'Room to be announced';
        $deadline = $application->effectivePaymentDeadline() ?: $batch?->enrollment_ends_at;
        $paymentCleared = $application->hasEnrollmentPaymentClearance();
        $verifiedPayment = $application->paymentTransactions
            ->where('status', \App\Models\PaymentTransaction::STATUS_VERIFIED)
            ->sortByDesc(fn ($transaction) => $transaction->verified_at?->getTimestamp() ?? $transaction->id)
            ->first();
        $pendingTicket = $application->paymentTransactions
            ->first(fn ($transaction) => $transaction->isOnsiteTicket());
        $displayPayment = $verifiedPayment ?: $pendingTicket;
        $paidAmount = (float) ($verifiedPayment?->amount ?? $application->total_paid_amount ?? $application->payment_amount ?? 0);
        $amount = 'PHP '.number_format($paymentCleared ? $paidAmount : (float) $application->payment_amount, 2);
        $orNumber = $displayPayment?->or_number ?: $application->payment_receipt_number;
        $referenceNumber = $application->payment_reference
            ?: $displayPayment?->ticket_number
            ?: $application->latestPaymentReference();
        $paymongoPaymentNumber = $application->paymongoPaymentId();
        $receivedBy = $verifiedPayment?->recordedByAdmin?->name ?: 'MCARE Cashier';
        $receiptReturnUrl = $receiptReturnUrl ?? route('payment.show');
        $receiptReturnLabel = $receiptReturnLabel ?? 'Back to payment';
        $logoPath = public_path('assets/images/logoicon.png');
        $logoSource = file_exists($logoPath)
            ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath))
            : asset('assets/images/logoicon.png');
    @endphp

    <main class="page">
        @unless ($downloadMode)
            <div class="actions">
                <a class="button" href="{{ $receiptReturnUrl }}">{{ $receiptReturnLabel }}</a>
                <span class="actions-group">
                    <a class="button" href="{{ route('payment.receipt.download') }}">Download receipt</a>
                    <button class="button button-primary" type="button" onclick="window.print()">Print / Save PDF</button>
                </span>
            </div>

            @unless ($paymentCleared)
                <div
                    id="payment-confirmation-status"
                    class="status-banner"
                    role="status"
                    aria-live="polite"
                    data-payment-status-url="{{ route('payment.status') }}"
                    data-payment-complete-url="{{ route('payment.complete') }}"
                >
                    Waiting for cashier verification. Keep this page open and MCARE will return you to the landing page automatically after the required payment is verified.
                </div>
            @endunless
        @endunless

        <section class="receipt-container" aria-label="{{ $paymentCleared ? 'Official payment receipt' : 'Pay-on-site receipt' }}">
            <div class="brand">
                <img src="{{ $logoSource }}" alt="Mission Care logo">
                <h1>Mission Care Training and Assessment Center</h1>
                <p>TESDA-accredited Caregiving NC II<br>{{ $paymentCleared ? 'Official payment receipt' : 'Pay-on-site cashier slip' }}</p>
            </div>

            <p class="receipt-title">
                {{ $paymentCleared ? 'OFFICIAL PAYMENT RECEIPT' : 'PAY-ON-SITE RECEIPT' }}
                <span>{{ $application->paymentStatusLabel() }}</span>
            </p>

            <hr class="rule">

            <div class="meta">
                @if (filled($orNumber))
                    <div>
                        <span>Official Receipt (OR) #</span>
                        <strong>{{ $orNumber }}</strong>
                    </div>
                @endif
                <div>
                    <span>Reference number</span>
                    <strong>{{ $referenceNumber }}</strong>
                </div>
                @if (filled($paymongoPaymentNumber))
                    <div>
                        <span>PayMongo payment number</span>
                        <strong>{{ $paymongoPaymentNumber }}</strong>
                    </div>
                @endif
                <div>
                    <span>Issued</span>
                    <strong>{{ $application->payment_selected_at?->format('M d, Y g:i A') ?? now()->format('M d, Y g:i A') }}</strong>
                </div>
            </div>

            <hr class="rule">

            <dl class="rows">
                <div class="row">
                    <dt class="label">Trainee</dt>
                    <dd class="value">{{ $fullName }}</dd>
                </div>
                <div class="row is-block">
                    <dt class="label">Email account</dt>
                    <dd class="value">{{ $application->email }}</dd>
                </div>
                <div class="row">
                    <dt class="label">Contact</dt>
                    <dd class="value">{{ $application->contact_number }}</dd>
                </div>
                <div class="row">
                    <dt class="label">Program</dt>
                    <dd class="value">{{ $application->program }}</dd>
                </div>
                <div class="row">
                    <dt class="label">Batch</dt>
                    <dd class="value">{{ $batch ? $batch->name.' '.$batch->year : 'Batch to be assigned' }}</dd>
                </div>
                <div class="row is-block">
                    <dt class="label">Schedule and room</dt>
                    <dd class="value">{{ $application->schedule_preference }} · {{ $scheduleLabel }} · {{ $roomLabel }}</dd>
                </div>
                @if (filled($application->enrollment_number))
                    <div class="row is-block">
                        <dt class="label">Enrollment number</dt>
                        <dd class="value">{{ $application->enrollment_number }}</dd>
                    </div>
                @endif
            </dl>

            <hr class="rule">

            <div class="row">
                <span class="label">Total program tuition</span>
                <strong class="value">PHP {{ number_format((float) $application->total_program_fee, 2) }}</strong>
            </div>
            <div class="row" style="margin-top: 0.7rem;">
                <span class="label">Remaining balance</span>
                <strong class="value">PHP {{ number_format($application->remainingBalance(), 2) }}</strong>
            </div>
            <div class="total">
                <span>{{ $paymentCleared ? 'Amount paid' : 'Amount due now' }}</span>
                <strong>{{ $amount }}</strong>
            </div>

            <hr class="rule">

            @if (filled($orNumber))
                <div class="row is-block">
                    <span class="label">Official Receipt (OR) #</span>
                    <strong class="value">{{ $orNumber }}</strong>
                </div>
            @endif

            <div class="row is-block" style="margin-top: 0.85rem;">
                <span class="label">Reference number</span>
                <strong class="value">{{ $referenceNumber }}</strong>
            </div>

            @if (filled($paymongoPaymentNumber))
                <div class="row is-block" style="margin-top: 0.85rem;">
                    <span class="label">PayMongo payment number</span>
                    <strong class="value">{{ $paymongoPaymentNumber }}</strong>
                </div>
            @endif

            @if ($paymentCleared)
                <p class="notice" style="margin: 0.85rem 0 0;">
                    This is your MCARE payment receipt. Keep a copy for your records. The official BIR receipt, if issued, remains with the MCARE cashier.
                </p>

                <hr class="rule">

                <div class="cashier">
                    <p>Recorded payment</p>
                    <div class="cashier-line"><span>Channel</span><b>{{ $verifiedPayment ? str($verifiedPayment->payment_channel)->headline() : str($application->payment_method)->headline() }}</b></div>
                    <div class="cashier-line"><span>Official Receipt (OR) #</span><b>{{ $orNumber ?: 'Pending' }}</b></div>
                    <div class="cashier-line"><span>Reference number</span><b>{{ $referenceNumber }}</b></div>
                    @if (filled($paymongoPaymentNumber))
                        <div class="cashier-line"><span>PayMongo payment number</span><b>{{ $paymongoPaymentNumber }}</b></div>
                    @endif
                    <div class="cashier-line"><span>Date</span><b>{{ $verifiedPayment?->paid_at?->format('M d, Y g:i A') ?? $application->payment_verified_at?->format('M d, Y g:i A') ?? now()->format('M d, Y g:i A') }}</b></div>
                    <div class="cashier-line"><span>Received by</span><b>{{ $receivedBy }}</b></div>
                    <div class="cashier-line"><span>Signature</span><b></b></div>
                </div>

                <p class="footer">
                    Receipt identifier is generated by MCARE. TESDA-accredited Caregiving NC II enrollment remains subject to current batch records and administrative review.
                </p>
            @else
                <p class="notice" style="margin: 0.85rem 0 0;">
                    This receipt is unique to this applicant and expires on {{ $deadline?->format('M d, Y g:i A') ?? 'the listed expiration date' }}. Bring this receipt to MCARE for cashier verification. This slip is a payment order, not the official BIR receipt.
                </p>

                <hr class="rule">

                <div class="cashier">
                    <p>Cashier / finance desk use</p>
                    <div class="cashier-line"><span>Verified by</span><b></b></div>
                    <div class="cashier-line"><span>Official Receipt (OR) #</span><b>{{ $orNumber }}</b></div>
                    <div class="cashier-line"><span>Reference number</span><b>{{ $referenceNumber }}</b></div>
                    <div class="cashier-line"><span>Date</span><b></b></div>
                    <div class="stamp-box">Official MCARE cashier stamp</div>
                </div>

                <p class="footer">
                    Receipt identifier and expiration date are generated by MCARE. TESDA-accredited Caregiving NC II enrollment remains subject to payment verification, administrative review, and current batch availability.
                </p>
            @endif
        </section>
    </main>

    @if (! $downloadMode && ! $paymentCleared)
        <script>
            const confirmationStatus = document.getElementById('payment-confirmation-status');
            const paymentStatusUrl = confirmationStatus?.dataset.paymentStatusUrl;
            const paymentCompleteUrl = confirmationStatus?.dataset.paymentCompleteUrl;

            if (confirmationStatus && paymentStatusUrl) {
                let paymentStatusChecks = 0;

                window.setInterval(async () => {
                    if (document.hidden) return;
                    paymentStatusChecks += 1;

                    try {
                        const response = await fetch(paymentStatusUrl, {
                            credentials: 'same-origin',
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            cache: 'no-store',
                        });
                        if (!response.ok) return;

                        const status = await response.json();
                        if (status.payment_verified === true) {
                            confirmationStatus.textContent = 'Payment verified successfully. Returning you to MCARE while your account awaits approval...';
                            window.setTimeout(() => window.location.assign(status.completion_url || paymentCompleteUrl), 500);
                            return;
                        }
                    } catch (error) {
                        // Keep waiting; email provides the fallback when the phone
                        // temporarily loses its data connection.
                    }

                    if (paymentStatusChecks >= 15) {
                        confirmationStatus.textContent = 'Still waiting for cashier verification. Checking continues automatically; you may also close this page and wait for the MCARE email.';
                    }
                }, 4000);
            }
        </script>
    @endif
</body>
</html>
