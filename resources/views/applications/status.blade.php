<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#f3f2f6]">
<head>
    <x-site-favicon />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application &amp; Document Status | MCARE</title>
    <x-dashboard-theme-head />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="enrollment-page application-page application-status-page min-h-screen bg-[#f3f2f6] font-sans text-slate-900 antialiased">
    <x-public-official-header
        masthead-aside="Caregiving NC II · Official application"
        nav-label="Application status"
        :secondary-href="route('landing')"
        secondary-label="Home"
    />

    <main class="enrollment-main mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-10">
        <article class="enrollment-sheet space-y-6">
            <header class="enrollment-intro">
                <p class="enrollment-kicker">Applicant &amp; Trainee Self-Service</p>
                <h1>Check application status</h1>
                <p class="enrollment-lede">Enter your application number (e.g. MCA-2026-XXXXXX) or enrollment number (e.g. MCE-2026-XXXXXX) to track review stages, payment verification, and document requirements.</p>
            </header>

            <div class="enrollment-form-body space-y-6">
                <!-- Search Form -->
                <form method="POST" action="{{ route('applications.lookup') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                    @csrf
                    <label class="form-label mb-2 block font-bold text-slate-800" for="application_number">
                        Application or Enrollment Number
                    </label>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <input class="form-field flex-1 font-mono uppercase tracking-wider text-sm sm:text-base bg-slate-50 focus:bg-white" id="application_number" name="application_number" value="{{ old('application_number', $submittedNumber) }}" placeholder="e.g. MCA-2026-XXXXXX or MCE-2026-XXXXXX" required>
                        <button type="submit" class="primary-action shrink-0 flex items-center justify-center gap-2 px-6">
                            <x-dashboard-icon name="magnifying-glass" class="w-4 h-4" />
                            <span>Look up status</span>
                        </button>
                    </div>
                    @error('application_number')<p class="form-error mt-2">{{ $message }}</p>@enderror
                </form>

                <!-- Feedback Notifications -->
                @if (session('saved'))
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900 text-sm flex items-start gap-3 shadow-xs" role="status" aria-live="polite">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-200 text-emerald-900 font-bold text-xs">✓</span>
                        <div>
                            <p class="font-bold">Update Received Successfully</p>
                            <p class="mt-0.5 text-xs text-emerald-800">{{ session('saved') }}</p>
                        </div>
                    </div>
                @endif

                @if ($errors->has('verifier') || $errors->has('replacement') || $errors->has('document'))
                    <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-900 text-sm flex items-start gap-3 shadow-xs" role="alert">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-rose-200 text-rose-900 font-bold text-xs">!</span>
                        <div>
                            <p class="font-bold">Document Replacement Error</p>
                            <p class="mt-0.5 text-xs text-rose-800">{{ $errors->first('verifier') ?: $errors->first('replacement') ?: $errors->first('document') }}</p>
                        </div>
                    </div>
                @endif

                @if ($lookedUp && ! $admission)
                    <div class="rounded-xl border border-rose-200 bg-rose-50 p-5 text-rose-900 text-sm flex items-start gap-3.5 shadow-xs" role="alert">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-rose-200 text-rose-900 font-black text-sm">✕</span>
                        <div>
                            <p class="font-bold text-base text-rose-950">Record Not Found</p>
                            <p class="mt-1 text-xs sm:text-sm text-rose-800 leading-relaxed">
                                We could not find an application or enrollment matching <span class="font-mono font-bold">{{ $submittedNumber }}</span>. Please verify the number sent to your email and try again.
                            </p>
                            <a href="{{ route('applications.create') }}" class="mt-3 inline-flex items-center gap-1 text-xs font-bold text-rose-900 underline hover:text-rose-950">
                                Apply for a training program &rarr;
                            </a>
                        </div>
                    </div>
                @endif

                @if ($admission)
                    @php
                        $enrollment = $admission->enrollment;
                        $stages = $admission->progressStages();
                        $currentStage = $admission->currentStage();
                        $documentStatuses = $enrollment?->documentStatuses() ?? [];
                        $canReplaceDocuments = $enrollment && $enrollment->status !== \App\Models\EnrollmentApplication::STATUS_APPROVED;

                        $flaggedDocuments = collect($documentStatuses)->filter(function ($doc) {
                            return in_array($doc['status'] ?? '', ['replace', 'missing'], true);
                        })->values();

                        $isPaid = $enrollment && (
                            $enrollment->payment_verified_at !== null
                            || in_array($enrollment->payment_status, [\App\Models\EnrollmentApplication::PAYMENT_PAID, \App\Models\EnrollmentApplication::PAYMENT_PARTIALLY_PAID], true)
                            || (float) $enrollment->total_paid_amount > 0
                        );
                        $areDocsVerified = $enrollment && $enrollment->documents_reviewed_at !== null;
                        $isSettled = $enrollment && $enrollment->status === \App\Models\EnrollmentApplication::STATUS_APPROVED;

                        $applicantName = $admission->fullName();
                        $programName = $admission->program ?? $enrollment?->program ?? 'Caregiving NC II';
                        $batchName = $enrollment?->batch?->name;

                        // Stage resolution for the 3 stages
                        if ($isSettled) {
                            $activeStageNumber = 4;
                        } elseif ($areDocsVerified) {
                            $activeStageNumber = 3;
                        } elseif ($isPaid) {
                            $activeStageNumber = 3;
                        } elseif ($enrollment) {
                            $activeStageNumber = 2;
                        } elseif ($admission->isApproved()) {
                            $activeStageNumber = 1;
                        } else {
                            $activeStageNumber = 0;
                        }

                        $documentStatusTones = [
                            'accepted' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
                            'replace' => 'bg-amber-100 text-amber-900 ring-amber-300',
                            'missing' => 'bg-red-50 text-red-800 ring-red-200',
                            'unreviewed' => 'bg-indigo-50 text-indigo-800 ring-indigo-200',
                            'not_uploaded' => 'bg-slate-50 text-slate-600 ring-slate-200',
                        ];
                    @endphp

                    <!-- 1. APPLICANT SUMMARY HEADER CARD -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-xs space-y-4">
                        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-4">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Applicant Name &amp; Identifiers</p>
                                <h2 class="mt-1 font-display text-2xl font-black text-slate-950">{{ $applicantName }}</h2>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center gap-1.5 rounded-md bg-purple-50 px-2.5 py-1 text-xs font-mono font-bold text-purple-800 border border-purple-200">
                                        <span class="text-[10px] uppercase font-bold tracking-wider text-purple-600">Application:</span>
                                        {{ $admission->application_number }}
                                    </span>
                                    @if ($enrollment)
                                        <span class="inline-flex items-center gap-1.5 rounded-md bg-teal-50 px-2.5 py-1 text-xs font-mono font-bold text-teal-800 border border-teal-200">
                                            <span class="text-[10px] uppercase font-bold tracking-wider text-teal-600">Enrollment:</span>
                                            {{ $enrollment->enrollment_number }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-left sm:text-right">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 block">Program &amp; Review Status</span>
                                <div class="mt-1 flex flex-wrap items-center justify-start sm:justify-end gap-2">
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-800 border border-slate-200">{{ $programName }}</span>
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $admission->isApproved() ? 'bg-emerald-50 text-emerald-800 ring-emerald-200' : ($admission->isPending() ? 'bg-amber-50 text-amber-800 ring-amber-200' : 'bg-rose-50 text-rose-800 ring-rose-200') }}">
                                        {{ $admission->statusLabel() }}
                                    </span>
                                </div>
                                @if ($batchName)
                                    <span class="mt-1 block text-xs font-semibold text-slate-500">{{ $batchName }}</span>
                                @endif
                            </div>
                        </div>

                        <!-- Current Stage Callout Banner -->
                        @if ($admission->isPending())
                            <div class="rounded-xl border border-amber-200 bg-amber-50/80 p-4">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-200 text-amber-900 font-bold text-xs">⏳</span>
                                    <div>
                                        <h3 class="font-bold text-amber-950 text-sm">Admission Evaluation in Progress</h3>
                                        <p class="mt-0.5 text-xs text-amber-800 leading-relaxed">
                                            Your application is currently being evaluated by MCARE Admissions. Return here anytime using this application number to check for approval.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @elseif ($admission->isApproved() && ! $enrollment)
                            <div class="rounded-xl border border-emerald-200 bg-emerald-50/80 p-4">
                                <div class="flex items-start justify-between gap-3 flex-wrap">
                                    <div class="flex items-start gap-3">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-200 text-emerald-900 font-bold text-xs">✓</span>
                                        <div>
                                            <h3 class="font-bold text-emerald-950 text-sm">Admission Approved — Proceed to Stage 1</h3>
                                            <p class="mt-0.5 text-xs text-emerald-800 leading-relaxed">
                                                Your training application has been approved! Complete the official TESDA enrollment form to secure your training slot.
                                            </p>
                                        </div>
                                    </div>
                                    <a href="{{ $admission->enrollmentUrl() }}" class="primary-action text-xs px-4 py-2">
                                        Continue to Enrollment &rarr;
                                    </a>
                                </div>
                            </div>
                        @elseif ($isSettled)
                            <div class="rounded-xl border border-emerald-300 bg-emerald-50 p-4">
                                <div class="flex items-start justify-between gap-3 flex-wrap">
                                    <div class="flex items-start gap-3">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white font-bold text-base">✓</span>
                                        <div>
                                            <h3 class="font-display font-black text-emerald-950 text-base">Enrollment Cleared — Official Trainee</h3>
                                            <p class="mt-0.5 text-xs text-emerald-800 leading-relaxed">
                                                Tuition confirmed and all required documents have been verified by the registrar. Your trainee access is active.
                                            </p>
                                        </div>
                                    </div>
                                    <a href="{{ route('login') }}" class="primary-action text-xs px-4 py-2">
                                        Open Trainee Portal &rarr;
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- 2. PROMINENT DOCUMENT REPLACEMENT HUB (Rendered when registrar requested replacement) -->
                    @if ($canReplaceDocuments && $flaggedDocuments->isNotEmpty())
                        <section id="replacement-hub" class="rounded-2xl border-2 border-amber-300 bg-amber-50/60 p-5 sm:p-6 shadow-sm" aria-labelledby="replacement-heading">
                            <div class="flex items-start gap-3.5 border-b border-amber-200/80 pb-4">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-500 text-white font-black text-lg shadow-xs">!</span>
                                <div class="flex-1">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <h3 id="replacement-heading" class="font-display text-lg font-black text-amber-950">Action Required: Replace Flagged Documents</h3>
                                        <span class="rounded-full bg-amber-200/90 px-3 py-0.5 text-xs font-bold text-amber-900 border border-amber-300">
                                            {{ $flaggedDocuments->count() }} {{ Str::plural('document', $flaggedDocuments->count()) }} need replacement
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs sm:text-sm leading-relaxed text-amber-900">
                                        The registrar audited your submitted enrollment requirements and requested replacement files. Please review the reviewer's feedback note below and upload clear, updated copies.
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5 space-y-4">
                                @foreach ($flaggedDocuments as $flaggedDoc)
                                    <div class="rounded-xl border border-amber-200 bg-white p-4 sm:p-5 shadow-xs">
                                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
                                            <div>
                                                <h4 class="font-bold text-slate-900 text-sm sm:text-base">{{ $flaggedDoc['label'] }}</h4>
                                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-800 bg-amber-100 px-2.5 py-0.5 rounded-md mt-0.5 border border-amber-200">
                                                    ⚠️ {{ $flaggedDoc['status_label'] }}
                                                </span>
                                            </div>
                                        </div>

                                        @if ($flaggedDoc['note'])
                                            <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50/70 p-3 text-xs leading-relaxed text-amber-950">
                                                <span class="font-bold uppercase tracking-wider text-[10px] text-amber-800 block">Registrar Feedback / Note:</span>
                                                <span class="font-semibold text-amber-950">{{ $flaggedDoc['note'] }}</span>
                                            </div>
                                        @endif

                                        <form method="POST" action="{{ route('applications.documents.replace') }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                                            @csrf
                                            <input type="hidden" name="application_number" value="{{ $admission->application_number }}">
                                            <input type="hidden" name="document" value="{{ $flaggedDoc['key'] }}">

                                            <div class="grid gap-3 sm:grid-cols-2">
                                                <div>
                                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1" for="verifier-{{ $flaggedDoc['key'] }}">
                                                        Email or Mobile Verification
                                                    </label>
                                                    <input class="form-field text-sm" id="verifier-{{ $flaggedDoc['key'] }}" name="verifier" value="{{ old('document') === $flaggedDoc['key'] ? old('verifier') : ($enrollment->email ?? $admission->email) }}" placeholder="e.g. {{ $enrollment->email ?? $admission->email }}" required autocomplete="username">
                                                    <p class="text-[11px] text-slate-500 mt-1">Confirm with the contact number or email on your enrollment form.</p>
                                                </div>

                                                <div>
                                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1" for="replacement-{{ $flaggedDoc['key'] }}">
                                                        Upload replacement
                                                    </label>
                                                    <input id="replacement-{{ $flaggedDoc['key'] }}" name="replacement" type="file" accept="{{ in_array($flaggedDoc['key'], ['id-photo', 'signature'], true) ? '.jpg,.jpeg,.png' : '.pdf,.jpg,.jpeg,.png' }}" class="block w-full text-xs text-slate-700 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-purple-100 file:text-purple-700 hover:file:bg-purple-200 cursor-pointer" required>
                                                    <p class="text-[11px] text-slate-500 mt-1">{{ in_array($flaggedDoc['key'], ['id-photo', 'signature'], true) ? 'Accepted: JPG, JPEG, PNG (max 5MB)' : 'Accepted: PDF, JPG, JPEG, PNG (max 5MB)' }}</p>
                                                </div>
                                            </div>

                                            <div class="pt-2 flex justify-end">
                                                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-amber-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-amber-700 transition">
                                                    <x-dashboard-icon name="arrow-up-tray" class="w-4 h-4" />
                                                    <span>Upload Replacement for {{ $flaggedDoc['label'] }}</span>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    <!-- 3. THREE ENROLLMENT STAGES PROGRESS FUNNEL -->
                    <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-xs space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">3 Stages of Enrollment</h3>
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full {{ $isSettled ? 'bg-emerald-100 text-emerald-800' : 'bg-teal-100 text-teal-800' }}">
                                @if ($activeStageNumber === 1) Stage 1: Enrollment Form
                                @elseif ($activeStageNumber === 2) Stage 2: Payment
                                @elseif ($activeStageNumber === 3) Stage 3: Document Verification
                                @else Settled Trainee
                                @endif
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- STAGE 1 CARD -->
                            <div class="rounded-xl border p-4 transition {{ $enrollment ? 'border-emerald-300 bg-emerald-50/50' : ($activeStageNumber === 1 ? 'border-teal-500 bg-teal-50/40 ring-2 ring-teal-500/20' : 'border-slate-200 bg-slate-50 opacity-60') }}">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold {{ $enrollment ? 'bg-emerald-600 text-white' : ($activeStageNumber === 1 ? 'bg-teal-600 text-white' : 'bg-slate-300 text-slate-700') }}">
                                        {{ $enrollment ? '✓' : '1' }}
                                    </span>
                                    <span class="text-[11px] font-bold uppercase tracking-wider {{ $enrollment ? 'text-emerald-700' : ($activeStageNumber === 1 ? 'text-teal-700' : 'text-slate-500') }}">
                                        {{ $enrollment ? 'Completed' : ($activeStageNumber === 1 ? 'Action Required' : 'Stage 1') }}
                                    </span>
                                </div>
                                <h4 class="mt-3 font-bold text-slate-900 text-sm">1. Enrollment Form</h4>
                                <p class="mt-1 text-xs text-slate-600 leading-relaxed">
                                    @if ($enrollment)
                                        TESDA enrollment form submitted on {{ $enrollment->created_at?->format('M d, Y') }}.
                                    @else
                                        Admission approved. Complete the official TESDA enrollment form to continue.
                                    @endif
                                </p>
                                @if (! $enrollment && $admission->isApproved())
                                    <div class="mt-3">
                                        <a href="{{ $admission->enrollmentUrl() }}" class="inline-flex w-full items-center justify-center rounded-lg bg-teal-600 px-3 py-2 text-xs font-bold text-white shadow-xs hover:bg-teal-700 transition">
                                            Fill Out Form &rarr;
                                        </a>
                                    </div>
                                @endif
                            </div>

                            <!-- STAGE 2 CARD -->
                            <div class="rounded-xl border p-4 transition {{ $isPaid ? 'border-emerald-300 bg-emerald-50/50' : ($activeStageNumber === 2 ? 'border-amber-500 bg-amber-50/40 ring-2 ring-amber-500/20' : 'border-slate-200 bg-slate-50 opacity-60') }}">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold {{ $isPaid ? 'bg-emerald-600 text-white' : ($activeStageNumber === 2 ? 'bg-amber-600 text-white' : 'bg-slate-300 text-slate-700') }}">
                                        {{ $isPaid ? '✓' : '2' }}
                                    </span>
                                    <span class="text-[11px] font-bold uppercase tracking-wider {{ $isPaid ? 'text-emerald-700' : ($activeStageNumber === 2 ? 'text-amber-800' : 'text-slate-500') }}">
                                        {{ $isPaid ? 'Paid & Verified' : ($activeStageNumber === 2 ? 'Awaiting Payment' : 'Stage 2') }}
                                    </span>
                                </div>
                                <h4 class="mt-3 font-bold text-slate-900 text-sm">2. Tuition / Downpayment</h4>
                                <p class="mt-1 text-xs text-slate-600 leading-relaxed">
                                    @if ($isPaid)
                                        Payment verified! ₱{{ number_format((float) ($enrollment->total_paid_amount ?: $enrollment->downpayment_amount ?: 2000), 2) }} recorded by cashier.
                                    @elseif ($activeStageNumber === 2)
                                        Enrollment received. Settle your downpayment (₱{{ number_format((float)($enrollment->downpayment_amount ?? 2000), 2) }}) to proceed.
                                    @else
                                        Payment unlocks after submitting the enrollment form in Stage 1.
                                    @endif
                                </p>
                                @if ($activeStageNumber === 2)
                                    <div class="mt-3">
                                        <a href="{{ route('payments.show') }}" class="inline-flex w-full items-center justify-center rounded-lg bg-amber-600 px-3 py-2 text-xs font-bold text-white shadow-xs hover:bg-amber-700 transition">
                                            Proceed to Payment &rarr;
                                        </a>
                                    </div>
                                @endif
                            </div>

                            <!-- STAGE 3 CARD -->
                            <div class="rounded-xl border p-4 transition {{ $isSettled ? 'border-emerald-300 bg-emerald-50/50' : ($activeStageNumber === 3 ? 'border-indigo-500 bg-indigo-50/40 ring-2 ring-indigo-500/20' : 'border-slate-200 bg-slate-50 opacity-60') }}">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold {{ $isSettled ? 'bg-emerald-600 text-white' : ($activeStageNumber === 3 ? 'bg-indigo-600 text-white' : 'bg-slate-300 text-slate-700') }}">
                                        {{ $isSettled ? '✓' : '3' }}
                                    </span>
                                    <span class="text-[11px] font-bold uppercase tracking-wider {{ $isSettled ? 'text-emerald-700' : ($activeStageNumber === 3 ? 'text-indigo-800' : 'text-slate-500') }}">
                                        {{ $isSettled ? 'Verified & Settled' : ($activeStageNumber === 3 ? 'Under Audit' : 'Stage 3') }}
                                    </span>
                                </div>
                                <h4 class="mt-3 font-bold text-slate-900 text-sm">3. Document Verification</h4>
                                <p class="mt-1 text-xs text-slate-600 leading-relaxed">
                                    @if ($isSettled)
                                        All documents verified by the registrar. Official trainee enrollment cleared.
                                    @elseif ($activeStageNumber === 3)
                                        @if ($flaggedDocuments->isNotEmpty())
                                            Some documents need replacement. Please see the action box above.
                                        @else
                                            Payment cleared! Registrar is auditing your uploaded requirements.
                                        @endif
                                    @else
                                        Registrar verification starts once payment is confirmed in Stage 2.
                                    @endif
                                </p>
                                @if ($activeStageNumber === 3 && $flaggedDocuments->isEmpty())
                                    <div class="mt-2 text-[11px] font-semibold text-indigo-700">
                                        ⏳ Documents in registrar review queue
                                    </div>
                                @endif
                            </div>
                        </div>
                    </section>

                    <!-- 4. OFFICIAL DOCUMENT AUDIT & VERIFICATION CHECKLIST -->
                    <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-xs space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Document status</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Every submitted requirement is audited for compliance and legibility by the registrar before clearance.</p>
                            </div>
                            <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-bold {{ $areDocsVerified ? 'bg-emerald-100 text-emerald-800' : ($flaggedDocuments->isNotEmpty() ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">
                                {{ $areDocsVerified ? '✓ All Documents Verified' : ($flaggedDocuments->isNotEmpty() ? '⚠️ Replacements Needed' : '⏳ In Review Queue') }}
                            </span>
                        </div>

                        @if ($documentStatuses === [])
                            <p class="py-4 text-center text-xs text-slate-500">Document review starts after you submit the enrollment form.</p>
                        @else
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                                @foreach ($documentStatuses as $document)
                                    <div class="flex flex-col justify-between rounded-xl bg-slate-50/70 p-3.5 border border-slate-200 text-xs space-y-2">
                                        <div class="flex items-start justify-between gap-2">
                                            <span class="font-bold text-slate-900">{{ $document['label'] }}</span>
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold ring-1 {{ $documentStatusTones[$document['status']] ?? 'bg-slate-50 text-slate-600 ring-slate-100' }}">
                                                {{ $document['status_label'] }}
                                            </span>
                                        </div>

                                        @if ($document['note'])
                                            <p class="rounded bg-white p-2 text-[11px] leading-relaxed text-slate-700 border border-slate-100">
                                                <span class="font-bold text-slate-500">Note:</span> {{ $document['note'] }}
                                            </p>
                                        @endif

                                        @if ($canReplaceDocuments && in_array($document['status'], ['replace', 'missing'], true))
                                            <div class="pt-1">
                                                <a href="#replacement-hub" class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-700 hover:text-amber-900 underline">
                                                    Upload replacement above &rarr;
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </section>
                @endif
            </div>
        </article>
    </main>

    <x-public-official-footer />
</body>
</html>
