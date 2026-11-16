<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\PaymentAttempt;
use App\Models\PaymentTransaction;
use App\Models\TrainingBatch;
use App\Services\EnrollmentPaymentLifecycle;
use App\Services\OfficialReceiptNumberGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;

class PaymentScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'method' => ['nullable', Rule::in(['onsite', 'online'])],
            'status' => ['nullable', Rule::in(array_keys(EnrollmentApplication::paymentStatuses()))],
            'batch_id' => ['nullable', 'integer', 'exists:training_batches,id'],
            'schedule' => ['nullable', Rule::in(['AM', 'PM'])],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $paymentsQuery = EnrollmentApplication::query()
            ->with(['batch', 'user', 'paymentVerifier', 'paymentTransactions.recordedByAdmin', 'paymentTransactions.verifier'])
            ->whereNotNull('payment_method')
            ->where('payment_status', '!=', EnrollmentApplication::PAYMENT_NOT_SELECTED)
            ->latest('payment_selected_at');

        if ($method = $filters['method'] ?? null) {
            $paymentsQuery->where('payment_method', $method);
        }

        if ($status = $filters['status'] ?? null) {
            $paymentsQuery->where('payment_status', $status);
        }

        if ($batchId = $filters['batch_id'] ?? null) {
            $paymentsQuery->where('training_batch_id', $batchId);
        }

        if ($schedule = $filters['schedule'] ?? null) {
            $paymentsQuery->where('schedule_preference', $schedule);
        }

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $paymentsQuery->where(function ($query) use ($search) {
                $query->where('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('payment_reference', 'like', "%{$search}%")
                    ->orWhere('payment_receipt_number', 'like', "%{$search}%")
                    ->orWhere('paymongo_checkout_reference', 'like', "%{$search}%")
                    ->orWhereHas('paymentTransactions', function ($tQuery) use ($search) {
                        $tQuery->where('or_number', 'like', "%{$search}%")
                            ->orWhere('ticket_number', 'like', "%{$search}%")
                            ->orWhere('reference_number', 'like', "%{$search}%");
                    });
            });
        }

        $totalCollected = (float) PaymentTransaction::query()->where('status', PaymentTransaction::STATUS_VERIFIED)->sum('amount');
        $totalDirectPaid = (float) EnrollmentApplication::query()->where('payment_status', EnrollmentApplication::PAYMENT_PAID)->whereDoesntHave('paymentTransactions')->sum('payment_amount');

        return view('admin.payment-schedules.index', [
            'batches' => TrainingBatch::query()->orderByDesc('year')->orderBy('name')->get(),
            'filters' => $filters,
            'paymentStatuses' => EnrollmentApplication::paymentStatuses(),
            'payments' => $paymentsQuery->paginate(15)->withQueryString(),
            'stats' => [
                'onsite' => EnrollmentApplication::query()->where('payment_method', 'onsite')->count(),
                'online' => EnrollmentApplication::query()->where('payment_method', 'online')->count(),
                'fully_paid' => EnrollmentApplication::query()->where('payment_status', EnrollmentApplication::PAYMENT_PAID)->count(),
                'partially_paid' => EnrollmentApplication::query()->where('payment_status', EnrollmentApplication::PAYMENT_PARTIALLY_PAID)->count(),
                'pending' => EnrollmentApplication::query()->whereIn('payment_status', [
                    EnrollmentApplication::PAYMENT_ONSITE_PENDING,
                    EnrollmentApplication::PAYMENT_ONLINE_PENDING,
                ])->count(),
                'pending_receipts' => PaymentTransaction::query()->where('status', PaymentTransaction::STATUS_PENDING)->count(),
                'pending_tickets' => PaymentTransaction::query()
                    ->where('status', PaymentTransaction::STATUS_PENDING)
                    ->whereNotNull('ticket_number')
                    ->count(),
                'total_collected' => $totalCollected + $totalDirectPaid,
            ],
        ]);
    }

    public function lookupEnrollee(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'max:100'],
        ]);

        $raw = trim($validated['q']);
        if (strlen($raw) < 3) {
            return response()->json(['found' => false]);
        }

        return response()->json($this->findEnrolleeByPaymentReference($raw) ?? ['found' => false]);
    }

    public function storeTransaction(
        Request $request,
        EnrollmentApplication $enrollmentApplication,
        EnrollmentPaymentLifecycle $paymentLifecycle,
    ): RedirectResponse {
        $pendingTicket = PaymentTransaction::query()
            ->where('enrollment_application_id', $enrollmentApplication->id)
            ->where('status', PaymentTransaction::STATUS_PENDING)
            ->where('payment_channel', PaymentTransaction::CHANNEL_ONSITE)
            ->first();

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:1', 'max:100000'],
            'transaction_type' => ['required', Rule::in(array_keys(PaymentTransaction::types()))],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $enrolleeName = trim($enrollmentApplication->first_name.' '.$enrollmentApplication->last_name);
        $orNumber = '';

        DB::transaction(function () use ($request, $validated, $enrollmentApplication, &$orNumber): void {
            $locked = EnrollmentApplication::query()->lockForUpdate()->findOrFail($enrollmentApplication->id);

            if ($locked->payment_status === EnrollmentApplication::PAYMENT_PAID) {
                throw ValidationException::withMessages([
                    'amount' => 'This enrollee is already fully paid.',
                ]);
            }

            $activeOnlineAttempt = PaymentAttempt::query()
                ->where('enrollment_application_id', $locked->id)
                ->where('provider', 'paymongo')
                ->whereIn('status', [PaymentAttempt::STATUS_CREATING, PaymentAttempt::STATUS_PENDING])
                ->exists();
            if ($activeOnlineAttempt) {
                throw ValidationException::withMessages([
                    'payment' => 'An online PayMongo checkout is still active for this enrollee.',
                ]);
            }

            $amount = round((float) $validated['amount'], 2);
            if ($amount > $locked->remainingBalance()) {
                throw ValidationException::withMessages([
                    'amount' => 'The payment cannot exceed the remaining balance of PHP '.number_format($locked->remainingBalance(), 2).'.',
                ]);
            }

            $pendingTicket = PaymentTransaction::query()
                ->where('enrollment_application_id', $locked->id)
                ->where('status', PaymentTransaction::STATUS_PENDING)
                ->where('payment_channel', PaymentTransaction::CHANNEL_ONSITE)
                ->lockForUpdate()
                ->first();

            $orNumber = $pendingTicket?->or_number
                ?: $locked->payment_receipt_number
                ?: app(OfficialReceiptNumberGenerator::class)->generate();

            if ($pendingTicket) {
                $pendingTicket->update([
                    'recorded_by_admin_id' => $request->user()->id,
                    'transaction_type' => $validated['transaction_type'],
                    'amount' => $amount,
                    'reference_number' => $pendingTicket->reference_number ?: $locked->payment_reference,
                    'or_number' => $orNumber,
                    'status' => PaymentTransaction::STATUS_VERIFIED,
                    'paid_at' => $validated['paid_at'],
                    'verified_at' => now(),
                    'verified_by_id' => $request->user()->id,
                    'notes' => $validated['notes'] ?? $pendingTicket->notes,
                ]);
            } else {
                PaymentTransaction::create([
                    'enrollment_application_id' => $locked->id,
                    'user_id' => $locked->user_id,
                    'recorded_by_admin_id' => $request->user()->id,
                    'transaction_type' => $validated['transaction_type'],
                    'payment_channel' => PaymentTransaction::CHANNEL_ONSITE,
                    'amount' => $amount,
                    'reference_number' => $locked->payment_reference,
                    'or_number' => $orNumber,
                    'status' => PaymentTransaction::STATUS_VERIFIED,
                    'paid_at' => $validated['paid_at'],
                    'verified_at' => now(),
                    'verified_by_id' => $request->user()->id,
                    'notes' => $validated['notes'] ?? null,
                ]);
            }

            $locked->payment_method = 'onsite';
            $locked->payment_selected_at ??= now();

            $locked->payment_receipt_number = $orNumber;
            $locked->payment_verified_by_id = $request->user()->id;
            $locked->payment_verified_at = now();
            $locked->recalculatePaymentStatus();

            AdminActivityLog::record($request->user(), 'payment.onsite_transaction.recorded', $locked, [
                'amount' => $validated['amount'],
                'or_number' => $orNumber,
                'reference_number' => $locked->payment_reference,
                'type' => $validated['transaction_type'],
                'new_total_paid' => $locked->total_paid_amount,
                'remaining_balance' => $locked->remainingBalance(),
            ]);
        });

        $paymentLifecycle->handleVerifiedPayment($enrollmentApplication->refresh());

        return $this->redirectToLedger($request)
            ->with('saved', 'On-site payment of ₱'.number_format((float) $validated['amount'], 2)." recorded for {$enrolleeName} (OR #{$orNumber}).");
    }

    public function verifyTransaction(
        Request $request,
        PaymentTransaction $transaction,
        EnrollmentPaymentLifecycle $paymentLifecycle,
    ): RedirectResponse {
        $rules = [
            'action' => ['required', Rule::in(['verify', 'reject'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        $rules['or_number'] = [
            'nullable',
            'string',
            'max:100',
            'regex:/^[A-Za-z0-9][A-Za-z0-9._-]*$/',
            Rule::unique('payment_transactions', 'or_number')->ignore($transaction->id),
        ];

        // Cashier OR numbers are generated by MCARE when an on-site ticket is verified unless specified.
        if ($request->input('action') === 'verify' && $transaction->isOnsiteTicket()) {
            $rules['paid_at'] = ['required', 'date', 'before_or_equal:today'];
        } else {
            $rules['paid_at'] = ['nullable', 'date', 'before_or_equal:today'];
        }

        $validated = $request->validate($rules);

        if ($transaction->status !== PaymentTransaction::STATUS_PENDING) {
            return $this->redirectToLedger($request)
                ->withErrors(['payment' => 'This payment request has already been processed.']);
        }

        $application = $transaction->enrollmentApplication;
        $enrolleeName = trim($application->first_name.' '.$application->last_name);

        $result = DB::transaction(function () use ($request, $validated, $transaction, $application): array {
            $lockedApp = EnrollmentApplication::query()->lockForUpdate()->findOrFail($application->id);
            $lockedTransaction = PaymentTransaction::query()->lockForUpdate()->findOrFail($transaction->id);

            if ($lockedTransaction->status !== PaymentTransaction::STATUS_PENDING) {
                return ['error' => 'This payment request has already been processed.'];
            }

            if ($validated['action'] === 'verify') {
                $orNumber = filled($validated['or_number'] ?? null)
                    ? $validated['or_number']
                    : ($lockedTransaction->or_number ?: app(OfficialReceiptNumberGenerator::class)->generate());

                if ((float) $lockedTransaction->amount > $lockedApp->remainingBalance()) {
                    return ['error' => 'This transaction exceeds the enrollee\'s current remaining balance. Review the ledger before verifying it.'];
                }

                $lockedTransaction->update([
                    'status' => PaymentTransaction::STATUS_VERIFIED,
                    'recorded_by_admin_id' => $request->user()->id,
                    'or_number' => $orNumber,
                    'paid_at' => $validated['paid_at'] ?? ($lockedTransaction->paid_at ?: now()),
                    'verified_at' => now(),
                    'verified_by_id' => $request->user()->id,
                    'notes' => ($validated['notes'] ?? null) ?: $lockedTransaction->notes,
                ]);

                $lockedApp->payment_receipt_number = $lockedTransaction->or_number ?: $lockedApp->payment_receipt_number;
                $lockedApp->payment_verified_by_id = $request->user()->id;
                $lockedApp->payment_verified_at = now();
                $lockedApp->recalculatePaymentStatus();

                AdminActivityLog::record($request->user(), 'payment.transaction.verified', $lockedApp, [
                    'transaction_id' => $lockedTransaction->id,
                    'amount' => $lockedTransaction->amount,
                    'or_number' => $lockedTransaction->or_number,
                ]);
            } else {
                $lockedTransaction->update([
                    'status' => PaymentTransaction::STATUS_REJECTED,
                    'recorded_by_admin_id' => $request->user()->id,
                    'notes' => ($validated['notes'] ?? null) ?: 'Payment proof was not accepted by administration.',
                ]);

                $lockedApp->recalculatePaymentStatus();

                AdminActivityLog::record($request->user(), 'payment.transaction.rejected', $lockedApp, [
                    'transaction_id' => $lockedTransaction->id,
                    'reason' => $validated['notes'] ?? null,
                ]);
            }

            return ['ok' => true];
        }, 3);

        if (isset($result['error'])) {
            return $this->redirectToLedger($request)
                ->withErrors(['payment' => $result['error']]);
        }

        if ($validated['action'] === 'verify') {
            $paymentLifecycle->handleVerifiedPayment($application->refresh());
        }

        $actionVerb = $validated['action'] === 'verify' ? 'verified' : 'rejected';

        return $this->redirectToLedger($request)
            ->with('saved', "Payment transaction for {$enrolleeName} was {$actionVerb}.");
    }

    public function receiptProof(Request $request, PaymentTransaction $transaction): BinaryFileResponse
    {
        $path = $transaction->receipt_proof_path;
        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404);

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        $reference = $transaction->or_number ?: 'transaction-'.$transaction->id;
        $filename = 'receipt-'.$reference.($extension !== '' ? '.'.$extension : '');
        $fallbackFilename = str($filename)->ascii()->replaceMatches('/[^A-Za-z0-9._-]/', '-')->toString();

        AdminActivityLog::record($request->user(), 'payment.receipt_proof.viewed', $transaction, [
            'application_id' => $transaction->enrollment_application_id,
            'or_number' => $transaction->or_number,
        ]);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        return response()->file($disk->path($path), [
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, $filename, $fallbackFilename),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function update(Request $request, EnrollmentApplication $enrollmentApplication): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['verify_paid', 'verify_downpayment', 'return_pending', 'mark_expired'])],
            'payment_verification_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = DB::transaction(function () use ($request, $validated, $enrollmentApplication): array {
            $lockedApplication = EnrollmentApplication::query()
                ->whereKey($enrollmentApplication->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedApplication->payment_method) {
                return ['error' => 'This enrollee has not selected a payment method.'];
            }

            if ($lockedApplication->payment_status === EnrollmentApplication::PAYMENT_PAID) {
                return ['error' => 'This payment is already confirmed and cannot be changed from this queue.'];
            }

            if (in_array($validated['action'], ['verify_paid', 'verify_downpayment'], true)) {
                return ['error' => $lockedApplication->payment_method === 'online'
                    ? 'Online payments are confirmed only after PayMongo reports the checkout as paid. Admins cannot mark them paid manually.'
                    : 'Verify the actual ticket or receipt ledger entry with its official OR, or record a new on-site payment.'];
            }

            $verifiedAmount = (float) $lockedApplication->paymentTransactions()
                ->where('status', PaymentTransaction::STATUS_VERIFIED)
                ->sum('amount');
            if ($verifiedAmount > 0) {
                return ['error' => 'This enrollee already has verified ledger entries. Add or review transactions instead of overriding the derived payment status.'];
            }

            $beforeStatus = $lockedApplication->payment_status;
            $meta = array_merge($lockedApplication->payment_meta ?? [], [
                'last_verification_action' => $validated['action'],
                'last_verified_at' => now()->toIso8601String(),
                'last_verified_by' => $request->user()->id,
            ]);

            $newStatus = match ($validated['action']) {
                'mark_expired' => EnrollmentApplication::PAYMENT_EXPIRED,
                default => $lockedApplication->payment_method === 'onsite'
                    ? EnrollmentApplication::PAYMENT_ONSITE_PENDING
                    : EnrollmentApplication::PAYMENT_ONLINE_PENDING,
            };

            $lockedApplication->forceFill([
                'payment_status' => $newStatus,
                'payment_verified_by_id' => $request->user()->id,
                'payment_verified_at' => now(),
                'payment_verification_notes' => $validated['payment_verification_notes'] ?? null,
                'payment_meta' => $meta,
            ])->save();

            AdminActivityLog::record($request->user(), 'payment.verification.updated', $lockedApplication, [
                'before' => $beforeStatus,
                'after' => $lockedApplication->payment_status,
                'method' => $lockedApplication->payment_method,
            ]);

            return [
                'name' => trim($lockedApplication->first_name.' '.$lockedApplication->last_name),
            ];
        }, 3);

        if (isset($result['error'])) {
            return $this->redirectToLedger($request)
                ->withErrors(['payment' => $result['error']]);
        }

        return $this->redirectToLedger($request)
            ->with('saved', 'Payment verification updated for '.$result['name'].'.');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findEnrolleeByPaymentReference(string $raw): ?array
    {
        $exact = $this->findEnrolleeExact($raw);
        if ($exact !== null) {
            return $exact;
        }

        $compact = EnrollmentApplication::compactLookupKey($raw);
        if (strlen($compact) < 6) {
            return null;
        }

        return $this->findEnrolleeByReferenceSuffix($compact);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findEnrolleeExact(string $raw): ?array
    {
        $normalized = strtoupper(trim($raw));
        $compact = EnrollmentApplication::compactLookupKey($raw);

        $transaction = PaymentTransaction::query()
            ->with(['enrollmentApplication.batch'])
            ->where(function ($query) use ($normalized, $compact): void {
                $query->whereRaw('UPPER(or_number) = ?', [$normalized])
                    ->orWhereRaw('UPPER(ticket_number) = ?', [$normalized])
                    ->orWhereRaw('UPPER(reference_number) = ?', [$normalized]);

                if ($compact !== '') {
                    $query->orWhereRaw($this->compactLookupSql('or_number').' = ?', [$compact])
                        ->orWhereRaw($this->compactLookupSql('ticket_number').' = ?', [$compact])
                        ->orWhereRaw($this->compactLookupSql('reference_number').' = ?', [$compact]);
                }
            })
            ->orderByRaw("CASE WHEN status = 'pending_verification' THEN 0 WHEN status = 'verified' THEN 1 ELSE 2 END")
            ->orderByDesc('id')
            ->first();

        if ($transaction?->enrollmentApplication) {
            $matchedBy = match (true) {
                $this->lookupKeysMatch($transaction->or_number, $raw) => 'or_number',
                $this->lookupKeysMatch($transaction->ticket_number, $raw) => 'ticket',
                default => 'reference',
            };

            return $this->enrolleeLookupPayload($transaction->enrollmentApplication, $matchedBy, $transaction);
        }

        $enrollmentNumber = EnrollmentApplication::normalizeNumber($raw);
        $application = EnrollmentApplication::query()
            ->with(['batch', 'paymentTransactions'])
            ->where(function ($query) use ($normalized, $compact, $enrollmentNumber): void {
                $query->whereRaw('UPPER(payment_receipt_number) = ?', [$normalized])
                    ->orWhereRaw('UPPER(payment_reference) = ?', [$normalized])
                    ->orWhereRaw('UPPER(paymongo_checkout_reference) = ?', [$normalized]);

                if ($compact !== '') {
                    $query->orWhereRaw($this->compactLookupSql('payment_receipt_number').' = ?', [$compact])
                        ->orWhereRaw($this->compactLookupSql('payment_reference').' = ?', [$compact])
                        ->orWhereRaw($this->compactLookupSql('paymongo_checkout_reference').' = ?', [$compact]);
                }

                if ($enrollmentNumber !== '') {
                    $query->orWhere('enrollment_number', $enrollmentNumber);
                }
            })
            ->latest('id')
            ->first();

        if (! $application) {
            return null;
        }

        $matchedBy = match (true) {
            $this->lookupKeysMatch($application->payment_receipt_number, $raw) => 'receipt',
            $this->lookupKeysMatch($application->payment_reference, $raw) => 'reference',
            $this->lookupKeysMatch($application->paymongo_checkout_reference, $raw) => 'reference',
            default => 'enrollment',
        };

        return $this->enrolleeLookupPayload($application, $matchedBy);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findEnrolleeByReferenceSuffix(string $compactSuffix): ?array
    {
        $likeSuffix = '%'.$compactSuffix;

        $transactionIds = PaymentTransaction::query()
            ->where(function ($query) use ($likeSuffix): void {
                $query->whereRaw($this->compactLookupSql('or_number').' LIKE ?', [$likeSuffix])
                    ->orWhereRaw($this->compactLookupSql('ticket_number').' LIKE ?', [$likeSuffix])
                    ->orWhereRaw($this->compactLookupSql('reference_number').' LIKE ?', [$likeSuffix]);
            })
            ->pluck('enrollment_application_id')
            ->filter()
            ->unique()
            ->values();

        $applicationIds = EnrollmentApplication::query()
            ->where(function ($query) use ($likeSuffix): void {
                $query->whereRaw($this->compactLookupSql('payment_receipt_number').' LIKE ?', [$likeSuffix])
                    ->orWhereRaw($this->compactLookupSql('payment_reference').' LIKE ?', [$likeSuffix])
                    ->orWhereRaw($this->compactLookupSql('paymongo_checkout_reference').' LIKE ?', [$likeSuffix]);
            })
            ->pluck('id');

        $candidateIds = $transactionIds->merge($applicationIds)->unique()->values();

        if ($candidateIds->count() !== 1) {
            return null;
        }

        $application = EnrollmentApplication::query()
            ->with(['batch', 'paymentTransactions'])
            ->find($candidateIds->first());

        if (! $application) {
            return null;
        }

        $matchedTransaction = $application->paymentTransactions
            ->first(fn (PaymentTransaction $transaction): bool => $this->lookupKeysSuffixMatch($transaction->reference_number, $compactSuffix)
                || $this->lookupKeysSuffixMatch($transaction->ticket_number, $compactSuffix)
                || $this->lookupKeysSuffixMatch($transaction->or_number, $compactSuffix));

        $matchedBy = match (true) {
            $matchedTransaction && $this->lookupKeysSuffixMatch($matchedTransaction->or_number, $compactSuffix) => 'or_number',
            $matchedTransaction && $this->lookupKeysSuffixMatch($matchedTransaction->ticket_number, $compactSuffix) => 'ticket',
            $matchedTransaction && $this->lookupKeysSuffixMatch($matchedTransaction->reference_number, $compactSuffix) => 'reference',
            $this->lookupKeysSuffixMatch($application->payment_reference, $compactSuffix) => 'reference',
            $this->lookupKeysSuffixMatch($application->payment_receipt_number, $compactSuffix) => 'receipt',
            $this->lookupKeysSuffixMatch($application->paymongo_checkout_reference, $compactSuffix) => 'reference',
            default => 'reference',
        };

        return $this->enrolleeLookupPayload($application, $matchedBy, $matchedTransaction);
    }

    private function lookupKeysSuffixMatch(?string $stored, string $compactSuffix): bool
    {
        if (blank($stored)) {
            return false;
        }

        $compactStored = EnrollmentApplication::compactLookupKey($stored);

        return $compactStored !== ''
            && str_ends_with($compactStored, $compactSuffix);
    }

    private function compactLookupSql(string $column): string
    {
        return "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE({$column}, ''), '-', ''), ' ', ''), '_', ''), '.', ''))";
    }

    private function lookupKeysMatch(?string $stored, string $raw): bool
    {
        if (blank($stored)) {
            return false;
        }

        $compactStored = EnrollmentApplication::compactLookupKey($stored);
        $compactRaw = EnrollmentApplication::compactLookupKey($raw);

        return $compactStored === $compactRaw
            || strtoupper(trim($stored)) === strtoupper(trim($raw));
    }

    /**
     * @return array<string, mixed>
     */
    private function enrolleeLookupPayload(
        EnrollmentApplication $application,
        string $matchedBy,
        ?PaymentTransaction $matchedTransaction = null,
    ): array {
        $application->loadMissing(['batch', 'paymentTransactions']);

        $pending = $application->paymentTransactions->first(
            fn (PaymentTransaction $transaction): bool => $transaction->status === PaymentTransaction::STATUS_PENDING
                && $transaction->payment_channel === PaymentTransaction::CHANNEL_ONSITE
        );

        $alreadyRecorded = $matchedBy === 'or_number'
            && $matchedTransaction?->status === PaymentTransaction::STATUS_VERIFIED;
        $reuseOrNumber = filled($pending?->or_number) && ! $alreadyRecorded;
        $alreadyPaid = $application->payment_status === EnrollmentApplication::PAYMENT_PAID;
        $downpayment = round((float) ($application->downpayment_amount ?? 0), 2);
        $paid = round((float) ($application->total_paid_amount ?? 0), 2);
        $suggestedAmount = $pending
            ? round((float) $pending->amount, 2)
            : ($downpayment > 0 && $paid <= 0 ? $downpayment : null);

        $matchedLabel = match ($matchedBy) {
            'or_number' => 'Official receipt '.$matchedTransaction?->or_number,
            'ticket' => 'On-site ticket '.$matchedTransaction?->ticket_number,
            'receipt' => 'Receipt '.$application->payment_receipt_number,
            'reference' => 'Payment reference '.($matchedTransaction?->reference_number ?: $application->payment_reference),
            default => 'Enrollment '.$application->enrollment_number,
        };

        return [
            'found' => true,
            'application_id' => $application->id,
            'name' => trim($application->last_name.', '.$application->first_name),
            'email' => $application->email,
            'batch' => $application->batch
                ? trim($application->batch->name.' '.$application->batch->year)
                : 'Unassigned',
            'schedule' => $application->schedule_preference,
            'balance' => $application->remainingBalance(),
            'downpayment_amount' => $downpayment,
            'payment_status' => $application->paymentStatusLabel(),
            'already_paid' => $alreadyPaid,
            'already_recorded' => $alreadyRecorded,
            'can_record' => ! $alreadyPaid,
            'matched_by' => $matchedBy,
            'matched_label' => $matchedLabel,
            'reuse_or_number' => $reuseOrNumber,
            'or_number' => $reuseOrNumber
                ? $pending?->or_number
                : ($matchedBy === 'or_number' ? $matchedTransaction?->or_number : null),
            'needs_cashier_or' => false,
            'pending_ticket' => $pending?->ticket_number,
            'suggested_amount' => $suggestedAmount,
            'suggested_type' => $pending?->transaction_type,
        ];
    }

    private function redirectToLedger(Request $request): RedirectResponse
    {
        return redirect()->to($this->ledgerUrl());
    }

    private function ledgerUrl(): string
    {
        $index = route('admin.payment-schedules.index');
        $previous = url()->previous($index);
        $previousPath = (string) parse_url($previous, PHP_URL_PATH);
        $indexPath = (string) parse_url($index, PHP_URL_PATH);

        if ($previousPath !== $indexPath) {
            return $index;
        }

        return $previous;
    }
}
