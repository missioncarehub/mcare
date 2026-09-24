@extends('admin.layouts.app', ['title' => $trainee->last_name.', '.$trainee->first_name.' | Trainee Record'])

@section('content')
    @php
        $statusStyles = [
            \App\Models\EnrollmentApplication::LEARNING_ACTIVE => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
            \App\Models\EnrollmentApplication::LEARNING_PAUSED => 'bg-amber-50 text-amber-900 ring-amber-200',
            \App\Models\EnrollmentApplication::LEARNING_PENDING_GRADUATE => 'bg-indigo-50 text-indigo-800 ring-indigo-200',
            \App\Models\EnrollmentApplication::LEARNING_GRADUATED => 'bg-purple-50 text-purple-800 ring-purple-200',
            \App\Models\EnrollmentApplication::LEARNING_WITHDRAWN => 'bg-slate-100 text-slate-700 ring-slate-200',
        ];
        $paymentStyles = [
            \App\Models\EnrollmentApplication::PAYMENT_PAID => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
            \App\Models\EnrollmentApplication::PAYMENT_ONSITE_PENDING => 'bg-amber-50 text-amber-900 ring-amber-200',
            \App\Models\EnrollmentApplication::PAYMENT_ONLINE_PENDING => 'bg-purple-50 text-purple-800 ring-purple-200',
            \App\Models\EnrollmentApplication::PAYMENT_EXPIRED => 'bg-red-50 text-red-800 ring-red-200',
            \App\Models\EnrollmentApplication::PAYMENT_NOT_SELECTED => 'bg-slate-100 text-slate-700 ring-slate-200',
        ];
        $paymentMethod = match ($trainee->payment_method) {
            'onsite' => 'On-site payment',
            'online' => 'Online payment',
            default => 'Not selected',
        };
        $paymentReference = $trainee->latestPaymentReference()
            ?: $trainee->payment_reference
            ?: $trainee->paymongo_checkout_reference;
        $assessmentLabel = $summary['assessment_ready']
            ? 'Ready for trainer assessment'
            : ($summary['total_modules'] > 0 ? 'Learning requirements pending' : 'Waiting for published modules');
    @endphp

    <section class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex min-w-0 items-center gap-4">
                <x-user-avatar :user="$trainee->user" :application="$trainee" :use-enrollment-photo="true" class="grid h-16 w-16 place-items-center rounded-full bg-purple-100 text-xl font-black text-purple-800" />
                <div class="min-w-0">
                    <p class="dashboard-section-kicker">Trainee record</p>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-bold text-slate-950">{{ $trainee->last_name }}, {{ $trainee->first_name }}</h1>
                        <span class="dashboard-pill {{ $statusStyles[$trainee->learning_status] ?? $statusStyles[\App\Models\EnrollmentApplication::LEARNING_ACTIVE] }}">{{ $trainee->learningStatusLabel() }}</span>
                    </div>
                    <p class="mt-1 truncate text-sm text-slate-500">{{ $trainee->email }}</p>
                    <p class="mt-2 text-xs font-semibold text-slate-600">{{ $trainee->batch ? $trainee->batch->name.' '.$trainee->batch->year : 'Unassigned batch' }} · {{ $trainee->schedule_preference ?: 'Schedule pending' }}</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.learning.trainees', ['tab' => $rosterTab]) }}" class="secondary-action inline-flex items-center gap-2">
                    <x-dashboard-icon name="arrow-left" class="h-4 w-4" />
                    Back to roster
                </a>
                @if ($trainee->isReleasedForReview())
                    <a href="{{ route('admin.enrollments.show', $trainee) }}" class="primary-action inline-flex items-center justify-center">Open enrollment record</a>
                @endif
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-3">
            <section class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-purple-50 text-purple-700"><x-dashboard-icon name="credit-card" class="h-5 w-5" /></span>
                    <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Payment</p><p class="font-bold text-slate-950">{{ $paymentMethod }}</p></div>
                </div>
                <dl class="mt-5 space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-4"><dt class="text-slate-500">Status</dt><dd><span class="dashboard-pill {{ $paymentStyles[$trainee->payment_status] ?? $paymentStyles[\App\Models\EnrollmentApplication::PAYMENT_NOT_SELECTED] }}">{{ $trainee->paymentStatusLabel() }}</span></dd></div>
                    <div class="flex items-center justify-between gap-4"><dt class="text-slate-500">Amount</dt><dd class="font-semibold text-slate-800">{{ $trainee->payment_amount ? ($trainee->payment_currency ?: 'PHP').' '.number_format((float) $trainee->payment_amount, 2) : 'Not recorded' }}</dd></div>
                    <div><dt class="text-slate-500">Reference</dt><dd class="mt-1 break-all font-semibold text-slate-800">{{ $paymentReference ?: 'Not available' }}</dd></div>
                    <div class="flex items-center justify-between gap-4"><dt class="text-slate-500">Verified</dt><dd class="font-semibold text-slate-800">{{ $trainee->payment_verified_at?->format('M d, Y g:i A') ?? 'Not verified' }}</dd></div>
                </dl>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-sky-50 text-sky-700"><x-dashboard-icon name="book-open" class="h-5 w-5" /></span>
                    <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Modules completed</p><p class="font-bold text-slate-950">{{ $summary['completed_modules'] }} of {{ $summary['total_modules'] }} published modules</p></div>
                </div>
                <div class="mt-5 h-2 overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-label="Module progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $summary['progress_percent'] }}">
                    <div class="h-full rounded-full bg-purple-600" style="width: {{ $summary['progress_percent'] }}%"></div>
                </div>
                <div class="mt-3 flex items-center justify-between text-xs font-semibold text-slate-500"><span>{{ $summary['progress_percent'] }}% overall progress</span><span>{{ $summary['in_progress_modules'] }} in progress</span></div>
                <p class="mt-5 text-sm text-slate-600">Last module activity: <span class="font-semibold text-slate-800">{{ $summary['last_activity']?->format('M d, Y g:i A') ?? 'No module opened yet' }}</span></p>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-lg {{ $summary['assessment_ready'] ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800' }}"><x-dashboard-icon name="square-check" class="h-5 w-5" /></span>
                    <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Assessment readiness</p><p class="font-bold text-slate-950">{{ $assessmentLabel }}</p></div>
                </div>
                <p class="mt-5 text-sm leading-6 text-slate-600">{{ $summary['assessment_ready'] ? 'All currently published modules are complete. The trainer can proceed once assessment recording is available.' : 'Complete the remaining learning requirements before trainer assessment.' }}</p>
                <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs font-bold text-slate-700">Assessment result: Not recorded yet</p>
                    <p class="mt-1 text-xs leading-5 text-slate-500">A real score or competency result will appear here after the assessment backend is implemented.</p>
                </div>
            </section>
        </div>

        <div class="dashboard-panel">
            <p class="text-sm font-bold text-slate-950">Lifecycle controls</p>
            <p class="mt-1 text-xs leading-5 text-slate-500">Pause, resume, mark completion progress, or permanently delete this trainee. Official graduation and alumni access open only after every required module is complete.</p>
            <div class="mt-3 flex flex-wrap gap-2">
                @if ($trainee->learning_status === \App\Models\EnrollmentApplication::LEARNING_ACTIVE)
                    <form method="POST" action="{{ route('admin.learning.trainees.status', $trainee) }}">@csrf @method('PATCH')<input type="hidden" name="learning_status" value="paused"><button class="min-h-10 rounded-lg border border-amber-200 bg-amber-50 px-4 text-xs font-bold text-amber-900 hover:bg-amber-100">Pause</button></form>
                @elseif ($trainee->learning_status !== \App\Models\EnrollmentApplication::LEARNING_GRADUATED)
                    <form method="POST" action="{{ route('admin.learning.trainees.status', $trainee) }}">@csrf @method('PATCH')<input type="hidden" name="learning_status" value="active"><button class="min-h-10 rounded-lg border border-emerald-200 bg-emerald-50 px-4 text-xs font-bold text-emerald-800 hover:bg-emerald-100">Resume</button></form>
                @endif
                @if ($trainee->learning_status !== \App\Models\EnrollmentApplication::LEARNING_GRADUATED)
                    @if ($modulesComplete)
                        <form method="POST" action="{{ route('admin.learning.trainees.status', $trainee) }}" data-confirm="Graduate {{ $trainee->first_name }} {{ $trainee->last_name }}? Required modules are complete, so this unlocks the official graduate record and alumni access.">
                            @csrf @method('PATCH')
                            <input type="hidden" name="learning_status" value="graduated">
                            <button class="min-h-10 rounded-lg border border-purple-200 bg-purple-50 px-4 text-xs font-bold text-purple-800 hover:bg-purple-100">Graduate</button>
                        </form>
                    @elseif ($trainee->learning_status !== \App\Models\EnrollmentApplication::LEARNING_PENDING_GRADUATE)
                        <form method="POST" action="{{ route('admin.learning.trainees.status', $trainee) }}" data-confirm="Mark {{ $trainee->first_name }} {{ $trainee->last_name }} as Pending Graduate? Required modules are still incomplete, so they will not be counted as a graduate or alumni.">
                            @csrf @method('PATCH')
                            <input type="hidden" name="learning_status" value="pending_graduate">
                            <button class="min-h-10 rounded-lg border border-indigo-200 bg-indigo-50 px-4 text-xs font-bold text-indigo-800 hover:bg-indigo-100">Pending Graduate</button>
                        </form>
                    @endif
                @endif
                <form method="POST" action="{{ route('admin.learning.trainees.destroy', $trainee) }}" data-confirm-title="{{ $trainee->accountDeletionTitle() }}" data-confirm="{{ $trainee->accountDeletionMessage() }}" @if($trainee->accountDeletionDetail()) data-confirm-detail="{{ $trainee->accountDeletionDetail() }}" @endif data-confirm-action="{{ $trainee->accountDeletionAction() }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="min-h-10 rounded-lg border border-red-200 bg-red-50 px-4 text-xs font-bold text-red-800 hover:bg-red-100">{{ $trainee->accountDeletionAction() }}</button>
                </form>
            </div>
            <form method="POST" action="{{ route('admin.learning.trainees.status', $trainee) }}" class="mt-3 grid max-w-3xl gap-2 sm:grid-cols-[12rem_minmax(14rem,1fr)_auto]">
                @csrf @method('PATCH')
                <select name="learning_status" class="form-field text-sm">@foreach($learningStatuses as $value => $label)<option value="{{ $value }}" @selected($trainee->learning_status === $value)>{{ $label }}</option>@endforeach</select>
                <input name="learning_status_notes" value="{{ $trainee->learning_status_notes }}" class="form-field text-sm" placeholder="Optional status note">
                <button class="secondary-action">Save status</button>
            </form>
        </div>
    </section>
@endsection
