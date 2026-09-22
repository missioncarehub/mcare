@extends('trainee.layouts.app', ['title' => 'Trainee Dashboard'])

@section('content')
    @php
        $fullName = trim($application->first_name.' '.$application->middle_name.' '.$application->last_name.' '.$application->extension_name);
        $batchLabel = $batch ? $batch->name.' '.$batch->year : 'Batch to be assigned';
        $scheduleLabel = $batch?->scheduleLabelFor($application->schedule_preference) ?? 'Schedule to be confirmed';
        $roomLabel = $batch?->roomFor($application->schedule_preference) ?: 'Room TBA';
        $deadline = $application->effectivePaymentDeadline() ?: $batch?->enrollment_ends_at;
        $progressPercent = max(0, min(100, (int) ($stats['progress'] ?? 0)));
    @endphp

    <section id="dashboard" class="space-y-6">
        <div class="dashboard-hero">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0">
                    <span class="dashboard-pill bg-purple-50 text-purple-700 ring-purple-100">Approved trainee</span>
                    <h1 class="mt-4">Welcome back, {{ $application->first_name }}</h1>
                    <p>
                        Continue your Caregiving NC II training with your approved batch schedule, modules, payment status, and submitted records in one place.
                    </p>
                </div>
                <div class="min-w-0 rounded-xl bg-purple-50 px-5 py-4 ring-1 ring-purple-100">
                    <p class="text-xs font-bold uppercase tracking-wide text-purple-700">Current batch</p>
                    <p class="mt-1 font-display text-2xl font-extrabold text-slate-950">{{ $batchLabel }}</p>
                </div>
            </div>
        </div>

        {{-- Path: resources/views/trainee/dashboard.blade.php | Label: Compact status cards link to the matching trainee page --}}
        <div class="dashboard-stat-rail grid grid-cols-2 gap-3 xl:grid-cols-4" data-trainee-stat-cards>
            <a href="{{ route('trainee.modules.index') }}" class="dashboard-stat dashboard-stat-link" data-trainee-stat-card>
                <div class="min-w-0 flex-1" data-trainee-stat-body>
                    <p class="dashboard-stat-label">Training progress</p>
                    <p class="dashboard-stat-value">{{ $progressPercent }}%</p>
                    <div class="dashboard-stat-meter" role="progressbar" aria-label="Training progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progressPercent }}">
                        <span style="width: {{ $progressPercent }}%"></span>
                    </div>
                    <p class="dashboard-stat-help">Continue in classwork</p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-purple-50 text-purple-700 ring-1 ring-purple-100" data-trainee-stat-icon>
                    <x-dashboard-icon name="signal" class="h-5 w-5" />
                </span>
            </a>
            <a href="{{ route('trainee.modules.index') }}" class="dashboard-stat dashboard-stat-link" data-trainee-stat-card>
                <div class="min-w-0 flex-1" data-trainee-stat-body>
                    <p class="dashboard-stat-label">Available modules</p>
                    <p class="dashboard-stat-value">{{ $stats['modules'] }}</p>
                    <p class="dashboard-stat-help">Open classwork</p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-sky-50 text-sky-700 ring-1 ring-sky-100" data-trainee-stat-icon>
                    <x-dashboard-icon name="book-open" class="h-5 w-5" />
                </span>
            </a>
            <a href="{{ route('trainee.documents') }}" class="dashboard-stat dashboard-stat-link" data-trainee-stat-card>
                <div class="min-w-0 flex-1" data-trainee-stat-body>
                    <p class="dashboard-stat-label">Documents</p>
                    <p class="dashboard-stat-value">{{ $stats['documents'] }}/5</p>
                    <p class="dashboard-stat-help">Review TESDA files</p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-amber-50 text-amber-700 ring-1 ring-amber-100" data-trainee-stat-icon>
                    <x-dashboard-icon name="file-text" class="h-5 w-5" />
                </span>
            </a>
            <a href="{{ route('trainee.payments') }}" class="dashboard-stat dashboard-stat-link" data-trainee-stat-card>
                <div class="min-w-0 flex-1" data-trainee-stat-body>
                    <p class="dashboard-stat-label">Payment</p>
                    <p class="dashboard-stat-value text-lg leading-tight">{{ $stats['payment'] }}</p>
                    <p class="dashboard-stat-help">{{ $deadline ? 'Deadline '.$deadline->format('M d, Y g:i A') : 'Deadline TBA' }}</p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100" data-trainee-stat-icon>
                    <x-dashboard-icon name="credit-card" class="h-5 w-5" />
                </span>
            </a>
        </div>
    </section>

    <section id="modules" class="mt-8 dashboard-panel">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="dashboard-section-kicker">My modules</p>
                <h2 class="dashboard-section-title">Learning materials</h2>
            </div>
            <span class="dashboard-pill bg-purple-50 text-purple-700 ring-purple-100">Private LMS access</span>
        </div>

        @php
            // Path: resources/views/trainee/dashboard.blade.php | Label: Open modules first, closed after
            // The service already orders by module code and defer status; re-sort here so accessible
            // (open) modules come before locked ones, preserving the underlying sequence within each group.
            $orderedModules = collect($modules)->values()->sortBy(function ($module) use ($classworkAccess) {
                $access = ($classworkAccess ?? collect())[$module->id] ?? ['accessible' => true];
                return $access['accessible'] ?? true ? 0 : 1;
            }, SORT_REGULAR, false)->values();
        @endphp
        <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($orderedModules as $module)
                @php
                    $moduleProgress = $progressByModule->get($module->id);
                    $access = ($classworkAccess ?? collect())[$module->id] ?? ['accessible' => true, 'blocker' => null];
                    $isLocked = ! ($access['accessible'] ?? true);
                    $blocker = $access['blocker'] ?? null;
                    $progressValue = $isLocked ? 0 : (int) ($moduleProgress?->displayProgressPercent() ?? 0);
                    $isTrainerValidated = $moduleProgress?->isTrainerValidated() ?? false;
                    $isAwaitingEval = ($moduleProgress?->status ?? null) === \App\Models\ModuleProgress::STATUS_AWAITING_EVALUATION;
                    $canMarkAsDone = ! $isLocked
                        && $module->requiresEvaluation()
                        && ! $isTrainerValidated
                        && ! $isAwaitingEval
                        && ! ($module->submodules()->where('is_required', true)->exists());
                @endphp
                <article class="dashboard-card p-5{{ $isLocked ? ' opacity-75' : '' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-black uppercase tracking-wide text-purple-600">{{ $module->batch ? $module->batch->name.' '.$module->batch->year : 'General module' }}</p>
                            <h3 class="mt-2 font-display text-xl font-black leading-tight text-slate-900">{{ $module->title }}</h3>
                        </div>
                        <span class="dashboard-pill {{ $isLocked ? 'bg-rose-50 text-rose-700 ring-rose-100' : ($moduleProgress?->status === 'completed' ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : ($moduleProgress ? 'bg-amber-50 text-amber-700 ring-amber-100' : 'bg-slate-50 text-slate-600 ring-slate-100')) }}">{{ $isLocked ? 'Locked' : ($moduleProgress ? str($moduleProgress->status)->headline() : 'Not started') }}</span>
                    </div>
                    <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-500">{{ $module->description }}</p>
                    <div class="mt-3 flex items-center gap-2 text-xs font-semibold text-slate-500">
                        <x-user-avatar :user="$module->trainer" :name="$module->trainer?->name ?? 'MCARE Trainer'" class="grid h-7 w-7 place-items-center rounded-full bg-purple-100 text-[10px] font-black text-purple-800" />
                        <span class="truncate">Trainer: {{ $module->trainer?->name ?? 'MCARE Trainer' }}</span>
                    </div>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progressValue }}">
                        @if($progressValue > 0)
                            <div class="h-full bg-purple-600" style="width: {{ $progressValue }}%"></div>
                        @endif
                    </div>
                    <p class="mt-2 text-xs font-bold text-slate-500">{{ $isLocked && $blocker ? 'Unlocks after a trainer grade on '.($blocker->module_code ?: $blocker->title) : ($progressValue.'% recorded') }}</p>
                    @if($isLocked)
                        <span class="secondary-action mt-5 w-full" aria-disabled="true">Locked</span>
                    @else
                        <a href="{{ route('trainee.modules.show', $module) }}" class="secondary-action mt-5 w-full">
                            Open protected viewer
                        </a>
                        {{-- Path: resources/views/trainee/dashboard.blade.php | Label: Mark as done → awaits trainer evaluation --}}
                        @if($canMarkAsDone)
                            <form method="POST" action="{{ route('trainee.modules.progress', $module) }}" class="mt-2" data-confirm="Mark this module as done and send it to your trainer for evaluation? You will not be able to edit it after this.">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="action" value="submit">
                                <button type="submit" class="primary-action w-full">Mark as done</button>
                            </form>
                        @elseif($isAwaitingEval)
                            <span class="dashboard-pill mt-3 w-full text-center bg-amber-50 text-amber-700 ring-amber-100">Awaiting trainer evaluation</span>
                        @elseif($isTrainerValidated)
                            <span class="dashboard-pill mt-3 w-full text-center bg-emerald-50 text-emerald-700 ring-emerald-100">Competent · evaluated by trainer</span>
                        @endif
                    @endif
                </article>
            @empty
                <div class="dashboard-card p-10 text-center md:col-span-2 xl:col-span-3">
                    <p class="font-display text-xl font-black text-slate-900">No modules yet</p>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Your trainer's published materials will appear here once available.</p>
                </div>
            @endforelse
        </div>
    </section>

    <section id="schedule" class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-[360px_1fr]">
        <aside class="dashboard-panel border-amber-100">
            <p class="text-xs font-black uppercase tracking-wide text-amber-600">Announcements</p>
            <h2 class="mt-2 font-display text-2xl font-black text-slate-900">Trainer notices</h2>
            <div class="mt-5 space-y-3">
                @forelse ($announcements as $announcement)
                    <article class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <p class="font-bold text-slate-900">{{ $announcement->title }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $announcement->message }}</p>
                        <p class="mt-2 text-xs font-bold text-amber-700">{{ $announcement->posted_at?->format('M d, Y g:i A') ?? 'Recently posted' }}</p>
                    </article>
                @empty
                    <article class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <p class="font-bold text-slate-900">No announcements yet</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Class announcements will appear here.</p>
                    </article>
                @endforelse
            </div>
        </aside>

        <section class="dashboard-panel">
            <p class="dashboard-section-kicker">My schedule</p>
            <h2 class="dashboard-section-title">Class details</h2>
            <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Preferred class</p>
                    <p class="mt-2 font-display text-2xl font-black text-slate-900">{{ $application->schedule_preference }}</p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-5 md:col-span-2">
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Schedule</p>
                    <p class="mt-2 font-display text-2xl font-black text-slate-900">{{ $scheduleLabel }}</p>
                    <p class="mt-2 text-sm text-slate-500">{{ $roomLabel }}</p>
                </div>
                <div class="rounded-2xl bg-purple-50 p-5 ring-1 ring-purple-100 md:col-span-3">
                    <p class="text-xs font-black uppercase tracking-wide text-purple-700">Enrollment/payment deadline</p>
                    <p class="mt-2 font-display text-2xl font-black text-slate-900">{{ $deadline?->format('M d, Y g:i A') ?? 'Deadline TBA' }}</p>
                </div>
            </div>
        </section>
    </section>

    <section id="payments" class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-[1fr_360px]">
        <div class="dashboard-panel">
            <p class="dashboard-section-kicker">Billing and payments</p>
            <h2 class="dashboard-section-title">Payment summary</h2>
            <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Payment method</p>
                    <p class="mt-2 text-lg font-black text-slate-900">{{ $application->payment_method ? str($application->payment_method)->headline() : 'Not selected' }}</p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Amount</p>
                    <p class="mt-2 text-lg font-black text-slate-900">{{ $application->payment_currency }} {{ number_format((float) $application->payment_amount, 2) }}</p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-5 md:col-span-2">
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Reference</p>
                    <p class="mt-2 break-all text-lg font-black text-slate-900">{{ $application->latestPaymentReference() ?: 'Reference pending' }}</p>
                </div>
            </div>
        </div>

        <aside class="dashboard-panel">
            <p class="text-xs font-black uppercase tracking-wide text-purple-600">Need payment action?</p>
            <p class="mt-2 text-sm leading-6 text-slate-500">Use the payment page to review your current online/on-site payment status or receipt.</p>
            <a href="{{ route('trainee.payments') }}" class="primary-action mt-5 w-full">Open payment page</a>
        </aside>
    </section>
@endsection
