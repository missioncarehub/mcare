@extends('trainee.layouts.app', ['title' => 'Tuition & Payments | MCARE Trainee'])

@section('content')
<section class="space-y-6" data-trainee-payments>
    <header class="border-b border-slate-200 pb-5 sm:pb-6">
        <p class="dashboard-section-kicker">Tuition & Financial Records</p>
        <h1 class="dashboard-section-title mt-2 text-2xl sm:text-3xl">Payment summary</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
            View your program tuition breakdown, monitor installment payments, upload on-site Official Receipt (OR) details for administrative validation, or make online payments.
        </p>
    </header>

    @if (session('payment_notice'))
        <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm font-semibold text-sky-800">
            {{ session('payment_notice') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-800">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Milestone Financial Status -->
    @if ($application->remainingBalance() <= 0)
        <div class="rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-teal-50 p-4 sm:p-5 text-emerald-950">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white">
                    <x-dashboard-icon name="check" class="h-6 w-6" />
                </div>
                <div class="min-w-0">
                    <h2 class="text-base font-bold">Program Tuition Fully Settled</h2>
                    <p class="text-xs text-emerald-800">You have completed all financial requirements for the Caregiving NC II program.</p>
                </div>
            </div>
        </div>
    @elseif ($application->isDownpaymentSatisfied())
        <div class="rounded-2xl border border-sky-200 bg-gradient-to-r from-sky-50 to-indigo-50 p-4 sm:p-5 text-sky-950">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-600 text-white">
                    <x-dashboard-icon name="circle-check" class="h-6 w-6" />
                </div>
                <div class="min-w-0">
                    <h2 class="text-base font-bold">Downpayment Milestone Confirmed — In Good Standing</h2>
                    <p class="text-xs text-sky-800">Your initial downpayment was verified. You have full access to training modules and LMS activities. You can pay your remaining balance in installments.</p>
                </div>
            </div>
        </div>
    @else
        <div class="rounded-2xl border border-amber-200 bg-gradient-to-r from-amber-50 to-orange-50 p-4 sm:p-5 text-amber-950">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-600 text-white">
                    <x-dashboard-icon name="triangle-exclamation" class="h-6 w-6" />
                </div>
                <div class="min-w-0">
                    <h2 class="text-base font-bold">Initial Downpayment (₱2,000.00) Required</h2>
                    <p class="text-xs text-amber-800">Please pay at the cashier or online to activate your classroom and training schedule.</p>
                </div>
            </div>
        </div>
    @endif

    <!-- 4 Metric Cards -->
    <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4" data-trainee-stat-cards>
        <article class="dashboard-stat min-h-0" data-trainee-stat-card>
            <div class="min-w-0" data-trainee-stat-body>
                <p class="dashboard-stat-label">Total Program Tuition</p>
                <p class="dashboard-stat-value">₱{{ number_format((float) ($application->total_program_fee ?? 22000.00), 2) }}</p>
            </div>
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-600 ring-1 ring-slate-200" data-trainee-stat-icon><x-dashboard-icon name="file-text" class="h-5 w-5" /></span>
        </article>
        <article class="dashboard-stat min-h-0" data-trainee-stat-card>
            <div class="min-w-0" data-trainee-stat-body>
                <p class="dashboard-stat-label">Downpayment Requirement</p>
                <p class="dashboard-stat-value text-purple-700">₱{{ number_format((float) ($application->downpayment_amount ?? 2000.00), 2) }}</p>
            </div>
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-purple-50 text-purple-700 ring-1 ring-purple-100" data-trainee-stat-icon><x-dashboard-icon name="credit-card" class="h-5 w-5" /></span>
        </article>
        <article class="dashboard-stat min-h-0" data-trainee-stat-card>
            <div class="min-w-0" data-trainee-stat-body>
                <p class="dashboard-stat-label">Total Paid to Date</p>
                <p class="dashboard-stat-value text-emerald-700">₱{{ number_format((float) ($application->total_paid_amount ?? 0.00), 2) }}</p>
            </div>
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100" data-trainee-stat-icon><x-dashboard-icon name="circle-check" class="h-5 w-5" /></span>
        </article>
        <article class="dashboard-stat min-h-0" data-trainee-stat-card>
            <div class="min-w-0" data-trainee-stat-body>
                <p class="dashboard-stat-label">Remaining Balance</p>
                <p class="dashboard-stat-value {{ $application->remainingBalance() <= 0 ? 'text-emerald-700' : 'text-amber-700' }}">
                    ₱{{ number_format($application->remainingBalance(), 2) }}
                </p>
            </div>
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg {{ $application->remainingBalance() <= 0 ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-amber-50 text-amber-700 ring-amber-100' }} ring-1" data-trainee-stat-icon><x-dashboard-icon name="signal" class="h-5 w-5" /></span>
        </article>
    </div>

    <!-- Main Content: Generate Ticket, Submit Proof & Transaction History -->
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="space-y-6">
            <!-- Primary flow: generate a server-backed ticket before visiting the cashier. -->
            <section id="onsite-ticket" class="dashboard-panel space-y-4 border-2 border-purple-100">
                <header class="border-b border-slate-100 pb-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="text-base font-bold text-slate-950">Generate On-Site Payment Ticket</h2>
                            <p class="mt-1 text-xs text-slate-500">Create a ticket before visiting MCARE. The ticket is saved for the admin cashier queue; it is not marked paid until the cashier verifies the actual OR.</p>
                        </div>
                        <x-dashboard-icon name="file-invoice" class="hidden h-5 w-5 shrink-0 text-purple-700 sm:block" />
                    </div>
                </header>

                @if ($activeOnsiteTicket)
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
                        <div class="flex items-start gap-3">
                            <x-dashboard-icon name="clock" class="mt-0.5 h-5 w-5 shrink-0 text-amber-700" />
                            <div class="min-w-0 flex-1">
                                <p class="font-bold">Ticket waiting for cashier verification</p>
                                <p class="mt-1 text-xs text-amber-800">Present this ticket number at MCARE. The admin will add the official receipt number after receiving your payment.</p>
                                <dl class="mt-3 grid grid-cols-2 gap-3 text-xs sm:grid-cols-3">
                                    <div><dt class="text-amber-700">Ticket number</dt><dd class="mt-1 break-all font-mono font-bold">{{ $activeOnsiteTicket->ticket_number }}</dd></div>
                                    <div><dt class="text-amber-700">Amount</dt><dd class="mt-1 font-bold">₱{{ number_format((float) $activeOnsiteTicket->amount, 2) }}</dd></div>
                                    <div><dt class="text-amber-700">Purpose</dt><dd class="mt-1 font-semibold">{{ $activeOnsiteTicket->typeLabel() }}</dd></div>
                                </dl>
                            </div>
                        </div>
                    </div>
                @elseif ($balance > 0)
                    <form method="POST" action="{{ route('trainee.payments.tickets.store') }}" data-single-action data-submit-label="Generating ticket..." class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="ticket-transaction-type" class="mb-1.5 block text-xs font-bold uppercase text-slate-600">Payment purpose</label>
                                <select id="ticket-transaction-type" name="transaction_type" class="form-field" required>
                                    @if (! $application->isDownpaymentSatisfied())
                                        <option value="downpayment">Downpayment (₱{{ number_format($downpayment, 2) }})</option>
                                    @endif
                                    <option value="installment">Installment / custom amount</option>
                                    <option value="balance_settlement">Pay remaining balance</option>
                                </select>
                            </div>
                            <div>
                                <label for="ticket-amount" class="mb-1.5 block text-xs font-bold uppercase text-slate-600">Ticket amount (PHP)</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-bold text-slate-500">₱</span>
                                    <input id="ticket-amount" name="amount" type="number" step="0.01" min="1" max="{{ number_format($balance, 2, '.', '') }}" required class="form-field pl-8" value="{{ old('amount', number_format($ticketDefaultAmount, 2, '.', '')) }}">
                                </div>
                                <p class="mt-1 text-[11px] text-slate-400">Maximum remaining balance: ₱{{ number_format($balance, 2) }}</p>
                            </div>
                        </div>
                        <button type="submit" class="primary-action w-full sm:w-auto">
                            <x-dashboard-icon name="file-invoice" class="h-4 w-4" />
                            <span>Generate Payment Ticket</span>
                        </button>
                    </form>
                @else
                    <p class="text-sm font-semibold text-emerald-700">No ticket is needed because your recorded tuition balance is fully settled.</p>
                @endif

                <details class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <summary class="cursor-pointer text-xs font-bold text-slate-700">Already paid at the cashier? Submit receipt proof</summary>
                    <form method="POST" action="{{ route('trainee.payments.upload-proof') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="trainee-amount" class="mb-1.5 block text-xs font-bold uppercase text-slate-600">Amount paid (PHP)</label>
                                <input id="trainee-amount" name="amount" type="number" step="0.01" min="1" max="100000" required class="form-field" placeholder="e.g. 2000.00" value="{{ old('amount') }}">
                            </div>
                            <div>
                                <label for="trainee-or-number" class="mb-1.5 block text-xs font-bold uppercase text-slate-600">Official Receipt (OR) number</label>
                                <input id="trainee-or-number" name="or_number" required maxlength="100" pattern="[A-Za-z0-9][A-Za-z0-9._-]*" class="form-field font-mono" placeholder="e.g. OR-89412" value="{{ old('or_number') }}">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="trainee-tx-type" class="mb-1.5 block text-xs font-bold uppercase text-slate-600">Payment purpose</label>
                                <select id="trainee-tx-type" name="transaction_type" class="form-field" required>
                                    <option value="downpayment">Downpayment</option>
                                    <option value="installment">Monthly Installment</option>
                                    <option value="full_payment">Full Tuition Payment</option>
                                    <option value="balance_settlement">Balance Settlement</option>
                                </select>
                            </div>
                            <div>
                                <label for="trainee-paid-at" class="mb-1.5 block text-xs font-bold uppercase text-slate-600">Date on receipt</label>
                                <input id="trainee-paid-at" name="paid_at" type="date" required max="{{ now()->toDateString() }}" class="form-field" value="{{ old('paid_at', now()->toDateString()) }}">
                            </div>
                        </div>
                        <div>
                            <label for="trainee-receipt-proof" class="mb-1.5 block text-xs font-bold uppercase text-slate-600">Receipt photo/document</label>
                            <input id="trainee-receipt-proof" name="receipt_proof" type="file" required accept=".pdf,.jpg,.jpeg,.png,.webp" class="form-field max-w-full file:mr-3 file:rounded-lg file:border-0 file:bg-purple-50 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-purple-700 hover:file:bg-purple-100">
                            <p class="mt-1 text-[11px] text-slate-500">PDF or clear JPG, PNG, or WebP; maximum 10 MB.</p>
                        </div>
                        <button type="submit" class="secondary-action w-full sm:w-auto">Submit receipt proof</button>
                    </form>
                </details>
            </section>

            <!-- Transaction History Ledger -->
            <section class="dashboard-table-wrap">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                    <h2 class="text-base font-bold text-slate-950">Payment History & Receipt Ledger</h2>
                    <p class="text-xs text-slate-500">Official log of all recorded tuition payments, installment deposits, and verification records.</p>
                </div>

                <div class="space-y-3 p-4 md:hidden">
                    @forelse ($application->paymentTransactions as $tx)
                        <article class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-mono text-xs font-bold break-all text-slate-950">{{ $tx->referenceLabel() }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $tx->paid_at?->format('M d, Y') ?? $tx->created_at->format('M d, Y') }}</p>
                                </div>
                                <span class="shrink-0 inline-flex rounded px-2 py-0.5 text-xs font-bold {{ $tx->status === 'verified' ? 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200' : ($tx->status === 'pending_verification' ? 'bg-amber-50 text-amber-800 ring-1 ring-amber-200' : 'bg-rose-50 text-rose-800 ring-1 ring-rose-200') }}">
                                    {{ $tx->statusLabel() }}
                                </span>
                            </div>
                            <dl class="mt-3 grid grid-cols-2 gap-3 text-xs">
                                <div>
                                    <dt class="text-slate-500">Classification</dt>
                                    <dd class="mt-0.5 font-semibold text-slate-900">{{ $tx->typeLabel() }}</dd>
                                </div>
                                <div>
                                    <dt class="text-slate-500">Channel</dt>
                                    <dd class="mt-0.5 font-semibold text-slate-900">{{ str($tx->payment_channel)->headline() }}</dd>
                                </div>
                                <div class="col-span-2">
                                    <dt class="text-slate-500">Amount</dt>
                                    <dd class="mt-0.5 font-bold text-slate-900">₱{{ number_format((float) $tx->amount, 2) }}</dd>
                                </div>
                            </dl>
                            @if ($tx->ticket_number)
                                <p class="mt-2 text-[11px] font-semibold text-amber-700">On-site ticket</p>
                            @endif
                        </article>
                    @empty
                        <div class="py-6 text-center text-slate-500">
                            <p class="font-bold text-slate-900">No payment transactions recorded yet</p>
                            <p class="mt-1 text-xs">Generate an on-site ticket above or complete an online payment.</p>
                        </div>
                    @endforelse
                </div>

                <div class="hidden overflow-x-auto md:block">
                    <table class="dashboard-table w-full min-w-[36rem]">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>OR / Reference #</th>
                                <th>Classification</th>
                                <th>Channel</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($application->paymentTransactions as $tx)
                                <tr>
                                    <td class="text-xs text-slate-600">{{ $tx->paid_at?->format('M d, Y') ?? $tx->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <p class="font-mono text-xs font-bold text-slate-950">{{ $tx->referenceLabel() }}</p>
                                        @if ($tx->ticket_number)
                                            <p class="mt-1 text-[11px] font-semibold text-amber-700">On-site ticket</p>
                                        @endif
                                    </td>
                                    <td class="text-xs">{{ $tx->typeLabel() }}</td>
                                    <td class="text-xs font-semibold">{{ str($tx->payment_channel)->headline() }}</td>
                                    <td class="font-bold text-slate-900">₱{{ number_format((float) $tx->amount, 2) }}</td>
                                    <td>
                                        <span class="inline-flex rounded px-2 py-0.5 text-xs font-bold {{ $tx->status === 'verified' ? 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200' : ($tx->status === 'pending_verification' ? 'bg-amber-50 text-amber-800 ring-1 ring-amber-200' : 'bg-rose-50 text-rose-800 ring-1 ring-rose-200') }}">
                                            {{ $tx->statusLabel() }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-500">
                                        <p class="font-bold text-slate-900">No payment transactions recorded yet</p>
                                        <p class="mt-1 text-xs">Generate an on-site ticket above or complete an online payment.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <!-- Sidebar / On-Site Guidelines & Online Alternative -->
        <aside class="space-y-6">
            <!-- Official On-Site Payment Slip Card -->
            <div class="dashboard-panel space-y-4 border-2 border-purple-200/80 bg-gradient-to-b from-purple-50/50 to-white">
                <div class="flex items-center gap-2.5 text-slate-900">
                    <x-dashboard-icon name="receipt" class="h-5 w-5 text-purple-700" />
                    <h3 class="font-bold text-sm">Official On-Site Payment Slip</h3>
                </div>

                @if ($activeOnsiteTicket)
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-3.5 space-y-2 text-xs">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-amber-800">Ticket number:</span>
                            <strong class="break-all text-right font-mono text-amber-950">{{ $activeOnsiteTicket->ticket_number }}</strong>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-amber-800">Cashier amount:</span>
                            <strong class="text-amber-950">₱{{ number_format((float) $activeOnsiteTicket->amount, 2) }}</strong>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-t border-amber-200 pt-1.5">
                            <span class="text-amber-800">Status:</span>
                            <strong class="text-amber-950">Waiting for admin</strong>
                        </div>
                    </div>
                    <p class="text-xs leading-5 text-slate-600">Show this ticket number at the MCARE cashier. Once verified, the admin will attach the official receipt number to your payment history.</p>
                    <button type="button" onclick="window.print()" class="secondary-action w-full justify-center">
                        <x-dashboard-icon name="print" class="h-4 w-4" />
                        <span>Print Ticket</span>
                    </button>
                @elseif ($application->payment_reference || $application->payment_receipt_number)
                    <div class="rounded-xl border border-purple-100 bg-white p-3.5 space-y-2 text-xs">
                        <div class="flex items-start justify-between gap-3">
                            <span class="shrink-0 text-slate-500">Reference #:</span>
                            <strong class="min-w-0 break-all text-right font-mono font-bold text-purple-950">{{ $application->payment_reference ?: $application->payment_receipt_number }}</strong>
                        </div>
                        @if ($application->payment_receipt_number && $application->payment_receipt_number !== $application->payment_reference)
                            <div class="flex items-start justify-between gap-3">
                                <span class="shrink-0 text-slate-500">Official Receipt:</span>
                                <span class="min-w-0 break-all text-right font-mono text-[11px] text-slate-700">{{ $application->payment_receipt_number }}</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-1.5">
                            <span class="text-slate-500">Amount for Cashier:</span>
                            <strong class="font-bold text-slate-900">PHP {{ number_format((float) ($application->total_paid_amount > 0 ? $application->remainingBalance() : ($application->downpayment_amount ?? 2000.00)), 2) }}</strong>
                        </div>
                    </div>

                    <div class="space-y-2 pt-1">
                        <a href="{{ route('payment.receipt') }}" target="_blank" rel="noopener noreferrer" class="primary-action w-full">
                            <x-dashboard-icon name="print" class="h-4 w-4 shrink-0" />
                            <span>View & Print Official Slip</span>
                        </a>
                        <a href="{{ route('payment.receipt.download') }}" class="secondary-action w-full">
                            <x-dashboard-icon name="download" class="h-4 w-4 shrink-0" />
                            <span>Download Slip</span>
                        </a>
                    </div>
                @else
                    <p class="text-xs leading-5 text-slate-600">
                        Generate a server-backed on-site ticket from the payment panel. The same ticket will appear in the admin cashier queue.
                    </p>
                    <a href="#onsite-ticket" class="primary-action w-full justify-center">
                        <x-dashboard-icon name="file-invoice" class="h-4 w-4" />
                        <span>Generate On-Site Ticket</span>
                    </a>
                @endif
            </div>

            <div class="dashboard-panel space-y-4">
                <div class="flex items-center gap-2.5 text-slate-900">
                    <x-dashboard-icon name="building-columns" class="h-5 w-5 text-purple-700" />
                    <h3 class="font-bold text-sm">On-Site Payment Guidelines</h3>
                </div>
                <p class="text-xs leading-5 text-slate-600">
                    You can pay in person at the MCARE Training Center finance desk during office hours.
                </p>
                <div class="rounded-xl bg-slate-50 p-3.5 space-y-2 text-xs text-slate-700">
                    <p><strong>Office:</strong> MCARE Admin & Cashier Office</p>
                    <p><strong>Schedule:</strong> Mon – Sat, 8:00 AM – 5:00 PM</p>
                    <p><strong>Accepted:</strong> Cash, Check, On-site GCash</p>
                    <p class="text-[11px] text-amber-800">⚠️ Always ensure you receive a printed Official Receipt (OR) with a valid number.</p>
                </div>
            </div>

            <div class="dashboard-panel space-y-4">
                <div class="flex items-center gap-2.5 text-slate-900">
                    <x-dashboard-icon name="credit-card" class="h-5 w-5 text-sky-700" />
                    <h3 class="font-bold text-sm">Online Payment Portal</h3>
                </div>
                <p class="text-xs leading-5 text-slate-600">
                    Prefer online payment via GCash, Maya, or Credit/Debit card? Open the PayMongo checkout portal.
                </p>
                <a href="{{ route('payment.show') }}" class="secondary-action w-full justify-center">
                    <x-dashboard-icon name="arrow-up-right-from-square" class="h-4 w-4" />
                    <span>Open Online Checkout</span>
                </a>
            </div>
        </aside>
    </div>
</section>
@endsection
