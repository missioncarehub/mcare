<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#f3f2f6]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application status | MCARE</title>
    <x-dashboard-theme-head />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="enrollment-page application-page application-status-page min-h-screen bg-[#f3f2f6] font-sans text-slate-900 antialiased">
    <x-public-official-header
        masthead-aside="Caregiving NC II · Official application"
        nav-label="Application status"
        :secondary-href="route('applications.create')"
        secondary-label="Apply"
        :primary-href="route('login')"
        primary-label="Sign in"
    />

    <main class="enrollment-main mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-10">
        <article class="enrollment-sheet">
            <header class="enrollment-intro">
                <p class="enrollment-kicker">Applicant self-service</p>
                <h1>Check application status</h1>
                <p class="enrollment-lede">Enter the application number issued after you submitted the applications page. Enrollment opens only when the status is approved.</p>
            </header>
            <div class="enrollment-form-body space-y-6">
                <form method="POST" action="{{ route('applications.lookup') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="form-label" for="application_number">Application number</label>
                        <input class="form-field" id="application_number" name="application_number" value="{{ old('application_number', $submittedNumber) }}" placeholder="MCA-2026-XXXXXX" required>
                        @error('application_number')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="primary-action">Look up status</button>
                </form>

                @if ($lookedUp && ! $admission)
                    <p class="enrollment-notice enrollment-notice-error" role="alert">That application number was not found. Check the number from your confirmation email and try again.</p>
                @endif

                @if ($admission)
                    @php
                        $stages = $admission->progressStages();
                        $enrollment = $admission->enrollment;
                        $currentStage = $admission->currentStage();
                        $badgeToneByState = [
                            'complete' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                            'in_progress' => 'bg-amber-50 text-amber-700 ring-amber-100',
                            'pending' => 'bg-slate-50 text-slate-500 ring-slate-100',
                            'blocked' => 'bg-red-50 text-red-700 ring-red-100',
                        ];
                        $stateLabels = [
                            'complete' => 'Complete',
                            'in_progress' => 'In progress',
                            'pending' => 'Pending',
                            'blocked' => 'Blocked',
                        ];
                    @endphp

                    <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-5">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Application number</p>
                                <p class="mt-1 font-display text-2xl font-extrabold text-slate-950">{{ $admission->application_number }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Current stage</p>
                                <p class="mt-1 font-display text-lg font-bold text-slate-950">{{ $currentStage['label'] }}</p>
                                <span class="mt-1 inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold ring-1 {{ $badgeToneByState[$currentStage['state']] ?? 'bg-slate-50 text-slate-600 ring-slate-100' }}">{{ $stateLabels[$currentStage['state']] ?? ucfirst($currentStage['state']) }}</span>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 text-sm">
                            <p><span class="text-xs font-bold uppercase tracking-wider text-slate-500">Program</span><br>{{ $admission->program }}</p>
                            <p><span class="text-xs font-bold uppercase tracking-wider text-slate-500">Preferred schedule</span><br>{{ $admission->schedule_preference ?: 'No preference' }}</p>
                            <p><span class="text-xs font-bold uppercase tracking-wider text-slate-500">Submitted</span><br>{{ $admission->created_at?->format('M d, Y g:i A') ?? '—' }}</p>
                            <p><span class="text-xs font-bold uppercase tracking-wider text-slate-500">Overall status</span><br>{{ $admission->statusLabel() }}</p>
                            @if ($enrollment)
                                <p><span class="text-xs font-bold uppercase tracking-wider text-slate-500">Enrollment number</span><br>{{ $enrollment->enrollment_number }}</p>
                                <p><span class="text-xs font-bold uppercase tracking-wider text-slate-500">Batch</span><br>{{ $enrollment->batch ? $enrollment->batch->name.' '.$enrollment->batch->year : 'Awaiting assignment' }}</p>
                                <p><span class="text-xs font-bold uppercase tracking-wider text-slate-500">Payment</span><br>{{ $enrollment->paymentStatusLabel() }}</p>
                                @if ($enrollment->batch?->enrollment_ends_at && ! $enrollment->batch->is_continuous_enrollment)
                                    <p><span class="text-xs font-bold uppercase tracking-wider text-slate-500">Enrollment deadline</span><br>{{ $enrollment->batch->enrollment_ends_at->format('M d, Y g:i A') }}</p>
                                @endif
                            @endif
                        </div>

                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Applicant progress</p>
                            <ol class="mt-3 space-y-3">
                                @foreach ($stages as $index => $stage)
                                    <li class="flex items-start gap-3 rounded-xl border border-slate-100 bg-slate-50/50 p-3">
                                        <span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full text-[11px] font-black ring-1 {{ $badgeToneByState[$stage['state']] ?? 'bg-slate-50 text-slate-500 ring-slate-100' }}">{{ $index + 1 }}</span>
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-900">{{ $stage['label'] }}
                                                <span class="ml-2 inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold ring-1 {{ $badgeToneByState[$stage['state']] ?? 'bg-slate-50 text-slate-500 ring-slate-100' }}">{{ $stateLabels[$stage['state']] ?? ucfirst($stage['state']) }}</span>
                                            </p>
                                            <p class="mt-1 text-xs text-slate-600">{{ $stage['description'] }}</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        </div>

                        @if ($admission->isPending())
                            <p class="enrollment-notice enrollment-notice-amber">MCARE is still reviewing this application. Return here anytime with the same number.</p>
                        @elseif ($admission->isApproved())
                            <p class="enrollment-notice enrollment-notice-ok">This application is approved. Enter this number on the enrollment page to open the TESDA form.</p>
                            @if ($enrollment)
                                <a href="{{ route('login') }}" class="primary-action">Sign in to continue enrollment</a>
                            @else
                                <a href="{{ $admission->enrollmentUrl() }}" class="primary-action">Continue to enrollment</a>
                            @endif
                        @else
                            <p class="enrollment-notice enrollment-notice-error">This application was not approved.@if(filled($admission->admin_notes)) {{ $admission->admin_notes }}@endif</p>
                            <a href="{{ route('applications.create') }}" class="secondary-action">Submit a new application</a>
                        @endif
                    </div>
                @endif
            </div>
        </article>
    </main>

    <x-public-official-footer />
</body>
</html>
