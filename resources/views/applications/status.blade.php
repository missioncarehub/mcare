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
                            <p class="font-bold">Update Received</p>
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

                        $stageBadgeTones = [
                            'complete' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                            'in_progress' => 'bg-purple-50 text-purple-800 border-purple-200',
                            'pending' => 'bg-slate-50 text-slate-500 border-slate-200',
                            'blocked' => 'bg-rose-50 text-rose-800 border-rose-200',
                        ];

                        $stagePillLabels = [
                            'complete' => 'Complete',
                            'in_progress' => 'In progress',
                            'pending' => 'Pending',
                            'blocked' => 'Action required',
                        ];

                        $documentStatusTones = [
                            'accepted' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
                            'replace' => 'bg-amber-100 text-amber-900 ring-amber-300',
                            'missing' => 'bg-rose-50 text-rose-800 ring-rose-200',
                            'unreviewed' => 'bg-indigo-50 text-indigo-800 ring-indigo-200',
                            'not_uploaded' => 'bg-slate-50 text-slate-600 ring-slate-200',
                        ];
                    @endphp

                    <!-- 1. APPLICANT IDENTIFIER & OVERVIEW CARD -->
                    <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-xs space-y-4">
                        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-4">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Applicant Name &amp; Numbers</p>
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
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 block">Program &amp; Status</span>
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

                        <!-- Current Stage Quick Banner -->
                        @if ($admission->isPending())
                            <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-4">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-200 text-amber-900 font-bold text-xs">⏳</span>
                                    <div>
                                        <h3 class="font-bold text-amber-950 text-sm">Admission Evaluation in Progress</h3>
                                        <p class="mt-0.5 text-xs text-amber-800 leading-relaxed">
                                            Your application is currently being evaluated by MCARE Admissions. You can return anytime with this application number to check for approval.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @elseif ($admission->isApproved() && ! $enrollment)
                            <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-4">
                                <div class="flex items-start justify-between gap-3 flex-wrap">
                                    <div class="flex items-start gap-3">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-200 text-emerald-900 font-bold text-xs">✓</span>
                                        <div>
                                            <h3 class="font-bold text-emerald-950 text-sm">Admission Approved &mdash; Complete Enrollment Form</h3>
                                            <p class="mt-0.5 text-xs text-emerald-800 leading-relaxed">
                                                Your application was approved! Please complete the official TESDA enrollment form to secure your training slot.
                                            </p>
                                        </div>
                                    </div>
                                    <a href="{{ $admission->enrollmentUrl() }}" class="primary-action text-xs px-4 py-2">
                                        Continue to enrollment &rarr;
                                    </a>
                                </div>
                            </div>
                        @elseif ($isSettled)
                            <div class="rounded-xl border border-emerald-300 bg-emerald-50 p-4">
                                <div class="flex items-start justify-between gap-3 flex-wrap">
                                    <div class="flex items-start gap-3">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white font-bold text-base">✓</span>
                                        <div>
                                            <h3 class="font-display font-black text-emerald-950 text-base">Enrollment Cleared &mdash; Official Trainee</h3>
                                            <p class="mt-0.5 text-xs text-emerald-800 leading-relaxed">
                                                Tuition confirmed and all required documents have been verified by the registrar. Your trainee access is active.
                                            </p>
                                        </div>
                                    </div>
                                    <a href="{{ route('login') }}" class="primary-action text-xs px-4 py-2">
                                        Sign in to trainee portal &rarr;
                                    </a>
                                </div>
                            </div>
                        @endif
                    </section>

                    <!-- 2. REVERTED & ENHANCED 1-TO-5 PROGRESS LIFECYCLE STEPPER -->
                    <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-xs space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Application progress</h3>
                                <p class="text-xs text-slate-500 mt-0.5">5-stage lifecycle from admission submission to official trainee clearance.</p>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-purple-50 px-3 py-1 text-xs font-bold text-purple-800 border border-purple-200">
                                Current: {{ $currentStage['label'] }}
                            </span>
                        </div>

                        <ol class="grid grid-cols-1 md:grid-cols-5 gap-3 pt-1">
                            @foreach ($stages as $index => $stage)
                                @php
                                    $stepNumber = $index + 1;
                                    $isDone = $stage['state'] === 'complete';
                                    $isActive = $stage['state'] === 'in_progress';
                                    $isBlocked = $stage['state'] === 'blocked';
                                @endphp
                                <li class="flex flex-col justify-between rounded-xl border p-4 transition-all {{ $isDone ? 'border-emerald-200 bg-emerald-50/40' : ($isActive ? 'border-purple-300 bg-purple-50/40 ring-2 ring-purple-400/20 shadow-xs' : ($isBlocked ? 'border-rose-200 bg-rose-50/40' : 'border-slate-200 bg-slate-50/60 opacity-70')) }}">
                                    <div>
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-black {{ $isDone ? 'bg-emerald-600 text-white' : ($isActive ? 'bg-purple-700 text-white shadow-xs' : ($isBlocked ? 'bg-rose-600 text-white' : 'bg-slate-300 text-slate-700')) }}">
                                                {{ $isDone ? '✓' : $stepNumber }}
                                            </span>
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold border {{ $stageBadgeTones[$stage['state']] ?? 'bg-slate-50 text-slate-600 border-slate-200' }}">
                                                {{ $stagePillLabels[$stage['state']] ?? ucfirst($stage['state']) }}
                                            </span>
                                        </div>
                                        <h4 class="mt-3 font-bold text-slate-900 text-sm">{{ $stepNumber }}. {{ $stage['label'] }}</h4>
                                        <p class="mt-1 text-xs text-slate-600 leading-relaxed">{{ $stage['description'] }}</p>
                                    </div>

                                    <!-- Contextual stage action if applicable -->
                                    @if ($stage['key'] === 'enrollment_form' && ! $enrollment && $admission->isApproved())
                                        <div class="mt-3 pt-2 border-t border-purple-200/60">
                                            <a href="{{ $admission->enrollmentUrl() }}" class="inline-flex w-full items-center justify-center rounded-lg bg-purple-700 px-3 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-purple-800 transition">
                                                Fill Out Form &rarr;
                                            </a>
                                        </div>
                                    @elseif ($stage['key'] === 'payment' && $isActive && $enrollment)
                                        <div class="mt-3 pt-2 border-t border-purple-200/60">
                                            <a href="{{ route('payments.show') }}" class="inline-flex w-full items-center justify-center rounded-lg bg-purple-700 px-3 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-purple-800 transition">
                                                Pay Downpayment &rarr;
                                            </a>
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </section>

                    <!-- 3. STREAMLINED DOCUMENT STATUS CHECKING -->
                    <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-xs space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Document status</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Official compliance review for submitted registration requirements.</p>
                            </div>
                            <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-bold {{ $areDocsVerified ? 'bg-emerald-100 text-emerald-800' : ($flaggedDocuments->isNotEmpty() ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">
                                {{ $areDocsVerified ? '✓ Verified' : ($flaggedDocuments->isNotEmpty() ? '⚠️ '.$flaggedDocuments->count().' Needs replacement' : '⏳ In review queue') }}
                            </span>
                        </div>

                        @if ($documentStatuses === [])
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-6 text-center">
                                <p class="text-xs sm:text-sm text-slate-600">Document review starts after you submit the enrollment form.</p>
                            </div>
                        @else
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5 pt-1">
                                @foreach ($documentStatuses as $document)
                                    @php
                                        $needsAction = $canReplaceDocuments && in_array($document['status'], ['replace', 'missing'], true);
                                    @endphp
                                    <div class="flex flex-col justify-between rounded-xl border p-4 text-xs space-y-3 transition-all {{ $needsAction ? 'border-amber-300 bg-amber-50/50 shadow-xs' : 'border-slate-200 bg-slate-50/60' }}">
                                        <div>
                                            <div class="flex items-start justify-between gap-2">
                                                <h4 class="font-bold text-slate-900 text-sm leading-snug">{{ $document['label'] }}</h4>
                                                <span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-[10px] font-bold ring-1 {{ $documentStatusTones[$document['status']] ?? 'bg-slate-50 text-slate-600 ring-slate-200' }}">
                                                    {{ $document['status_label'] }}
                                                </span>
                                            </div>

                                            @if ($document['note'])
                                                <div class="mt-2.5 rounded-lg border border-amber-200 bg-white p-2.5 text-xs text-amber-950 shadow-2xs leading-relaxed">
                                                    <span class="font-bold uppercase tracking-wider text-[10px] text-amber-800 block">Registrar Note:</span>
                                                    <span>{{ $document['note'] }}</span>
                                                </div>
                                            @endif
                                        </div>

                                        @if ($needsAction)
                                            <div class="pt-2 border-t border-amber-200/70" data-doc-container="{{ $document['key'] }}">
                                                <!-- Locked state (triggers OTP modal if not yet authenticated) -->
                                                <div data-doc-locked class="{{ $isDocumentUploadAuthorized ? 'hidden' : '' }}">
                                                    <button type="button" data-otp-trigger data-doc-key="{{ $document['key'] }}" data-doc-label="{{ $document['label'] }}" class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-amber-600 px-3 py-2 text-xs font-bold text-white shadow-xs hover:bg-amber-700 transition">
                                                        <x-dashboard-icon name="arrow-up-tray" class="w-3.5 h-3.5" />
                                                        <span>Upload replacement</span>
                                                    </button>
                                                </div>

                                                <!-- Unlocked state (file picker shown once OTP is verified on this session) -->
                                                <form method="POST" action="{{ route('applications.documents.replace') }}" enctype="multipart/form-data" data-doc-unlocked class="{{ $isDocumentUploadAuthorized ? '' : 'hidden' }} space-y-2">
                                                    @csrf
                                                    <input type="hidden" name="application_number" value="{{ $admission->application_number }}">
                                                    <input type="hidden" name="document" value="{{ $document['key'] }}">

                                                    <label class="block text-[11px] font-bold text-slate-700" for="file-{{ $document['key'] }}">
                                                        Select replacement file:
                                                    </label>
                                                    <input id="file-{{ $document['key'] }}" name="replacement" type="file" accept="{{ in_array($document['key'], ['id-photo', 'signature'], true) ? '.jpg,.jpeg,.png' : '.pdf,.jpg,.jpeg,.png' }}" class="block w-full text-xs text-slate-700 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-[11px] file:font-bold file:bg-amber-100 file:text-amber-800 hover:file:bg-amber-200 cursor-pointer" required>
                                                    <p class="text-[10px] text-slate-500">{{ in_array($document['key'], ['id-photo', 'signature'], true) ? 'JPG, PNG (max 5MB)' : 'PDF, JPG, PNG (max 5MB)' }}</p>

                                                    <button type="submit" class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-amber-600 px-3 py-2 text-xs font-bold text-white shadow-xs hover:bg-amber-700 transition">
                                                        <span>Submit replacement</span>
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    <!-- OTP VERIFICATION MODAL -->
                    <div id="otp-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="otp-modal-title">
                        <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl space-y-4">
                            <!-- Close button -->
                            <button type="button" data-modal-close class="absolute top-4 right-4 rounded-full p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition" aria-label="Close modal">
                                <span class="text-xl font-bold leading-none">&times;</span>
                            </button>

                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-purple-100 text-purple-700 font-bold">
                                    <x-dashboard-icon name="shield-check" class="w-6 h-6" />
                                </div>
                                <div>
                                    <h3 id="otp-modal-title" class="font-display text-base font-extrabold text-slate-950">Verify Your Identity</h3>
                                    <p class="text-xs text-slate-500">Authorize document update for your application</p>
                                </div>
                            </div>

                            <p class="text-xs text-slate-600 leading-relaxed">
                                For your security, enter the 6-digit verification code sent to your registered email:
                            </p>

                            <!-- Masked Email Pill -->
                            <div class="flex items-center justify-center">
                                <span id="otp-masked-email" class="inline-flex items-center gap-1.5 font-mono text-xs font-bold text-purple-900 bg-purple-50 border border-purple-200 px-3 py-1.5 rounded-lg">
                                    Sending verification code...
                                </span>
                            </div>

                            <!-- OTP Input Form -->
                            <div class="space-y-3">
                                <div>
                                    <label for="otp-code-input" class="sr-only">6-Digit Verification Code</label>
                                    <input type="text" id="otp-code-input" maxlength="6" inputmode="numeric" pattern="[0-9]*" placeholder="••••••" class="w-full text-center font-mono text-2xl font-black tracking-[0.4em] rounded-xl border border-slate-300 py-3 bg-slate-50 focus:bg-white focus:border-purple-600 focus:ring-3 focus:ring-purple-200 transition" autocomplete="one-time-code">
                                </div>

                                <div id="otp-error-msg" class="hidden rounded-lg border border-rose-200 bg-rose-50 p-2.5 text-xs text-rose-800 font-semibold" role="alert"></div>

                                <button type="button" id="btn-verify-otp" class="primary-action w-full justify-center py-2.5 text-sm font-bold">
                                    Verify Code &amp; Unlock Upload
                                </button>
                            </div>

                            <!-- Resend & Cooldown Footer -->
                            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                                <button type="button" id="btn-resend-otp" class="font-bold text-purple-700 hover:text-purple-900 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Resend code
                                </button>
                                <button type="button" data-modal-close class="text-slate-500 hover:text-slate-700">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </article>
    </main>

    <x-public-official-footer />

    @if ($admission)
        <script>
            (function () {
                const applicationNumber = @json($admission->application_number);
                const sendOtpUrl = @json(route('applications.status.otp.send'));
                const verifyOtpUrl = @json(route('applications.status.otp.verify'));
                const xsrfValue = @json(csrf_token());

                const modal = document.getElementById('otp-modal');
                const otpInput = document.getElementById('otp-code-input');
                const maskedEmailEl = document.getElementById('otp-masked-email');
                const errorMsgEl = document.getElementById('otp-error-msg');
                const verifyBtn = document.getElementById('btn-verify-otp');
                const resendBtn = document.getElementById('btn-resend-otp');

                let cooldownTimer = null;
                let otpSent = false;

                function openModal() {
                    if (!modal) return;
                    modal.classList.remove('hidden');
                    if (errorMsgEl) {
                        errorMsgEl.classList.add('hidden');
                        errorMsgEl.textContent = '';
                    }
                    if (otpInput) {
                        otpInput.value = '';
                        setTimeout(() => otpInput.focus(), 100);
                    }
                    if (!otpSent) {
                        sendOtp();
                    }
                }

                function closeModal() {
                    if (!modal) return;
                    modal.classList.add('hidden');
                }

                function startCooldown(seconds) {
                    if (cooldownTimer) clearInterval(cooldownTimer);
                    let remaining = seconds;
                    if (resendBtn) {
                        resendBtn.disabled = true;
                        resendBtn.textContent = 'Resend in ' + remaining + 's';
                    }

                    cooldownTimer = setInterval(() => {
                        remaining--;
                        if (remaining <= 0) {
                            clearInterval(cooldownTimer);
                            if (resendBtn) {
                                resendBtn.disabled = false;
                                resendBtn.textContent = 'Resend code';
                            }
                        } else if (resendBtn) {
                            resendBtn.textContent = 'Resend in ' + remaining + 's';
                        }
                    }, 1000);
                }

                async function sendOtp() {
                    if (maskedEmailEl) maskedEmailEl.textContent = 'Sending verification code...';
                    if (errorMsgEl) errorMsgEl.classList.add('hidden');
                    if (resendBtn) resendBtn.disabled = true;

                    try {
                        const response = await fetch(sendOtpUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': xsrfValue,
                            },
                            body: JSON.stringify({ application_number: applicationNumber })
                        });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            otpSent = true;
                            if (maskedEmailEl) maskedEmailEl.textContent = 'Code sent to ' + (data.masked_email || 'your email');
                            startCooldown(data.cooldown_seconds || 60);
                        } else {
                            if (errorMsgEl) {
                                errorMsgEl.textContent = data.message || 'Unable to send verification code. Please try again.';
                                errorMsgEl.classList.remove('hidden');
                            }
                            if (data.cooldown && data.remaining_seconds) {
                                startCooldown(data.remaining_seconds);
                            } else if (resendBtn) {
                                resendBtn.disabled = false;
                            }
                        }
                    } catch (err) {
                        if (errorMsgEl) {
                            errorMsgEl.textContent = 'Connection error. Please try again.';
                            errorMsgEl.classList.remove('hidden');
                        }
                        if (resendBtn) resendBtn.disabled = false;
                    }
                }

                async function verifyOtp() {
                    const code = (otpInput?.value || '').trim();
                    if (!code || code.length !== 6) {
                        if (errorMsgEl) {
                            errorMsgEl.textContent = 'Please enter the 6-digit verification code.';
                            errorMsgEl.classList.remove('hidden');
                        }
                        otpInput?.focus();
                        return;
                    }

                    if (errorMsgEl) errorMsgEl.classList.add('hidden');
                    if (verifyBtn) {
                        verifyBtn.disabled = true;
                        verifyBtn.textContent = 'Verifying...';
                    }

                    try {
                        const response = await fetch(verifyOtpUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': xsrfValue,
                            },
                            body: JSON.stringify({
                                application_number: applicationNumber,
                                otp_code: code,
                            })
                        });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            closeModal();
                            // Unlock all replacement upload forms on this page
                            document.querySelectorAll('[data-doc-locked]').forEach(el => el.classList.add('hidden'));
                            document.querySelectorAll('[data-doc-unlocked]').forEach(el => el.classList.remove('hidden'));
                        } else {
                            if (errorMsgEl) {
                                errorMsgEl.textContent = data.message || 'Invalid verification code.';
                                errorMsgEl.classList.remove('hidden');
                            }
                        }
                    } catch (err) {
                        if (errorMsgEl) {
                            errorMsgEl.textContent = 'Verification error. Please check your connection and try again.';
                            errorMsgEl.classList.remove('hidden');
                        }
                    } finally {
                        if (verifyBtn) {
                            verifyBtn.disabled = false;
                            verifyBtn.textContent = 'Verify Code & Unlock Upload';
                        }
                    }
                }

                // Attach trigger buttons
                document.querySelectorAll('[data-otp-trigger]').forEach(btn => {
                    btn.addEventListener('click', openModal);
                });

                // Attach modal close buttons
                document.querySelectorAll('[data-modal-close]').forEach(btn => {
                    btn.addEventListener('click', closeModal);
                });

                // Attach verify button
                if (verifyBtn) {
                    verifyBtn.addEventListener('click', verifyOtp);
                }

                // Attach resend button
                if (resendBtn) {
                    resendBtn.addEventListener('click', sendOtp);
                }

                // Keyboard enter in otpInput
                if (otpInput) {
                    otpInput.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            verifyOtp();
                        }
                    });
                }

                // Close on backdrop click
                if (modal) {
                    modal.addEventListener('click', (e) => {
                        if (e.target === modal) closeModal();
                    });
                }

                // Close on Escape key
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
                        closeModal();
                    }
                });
            })();
        </script>
    @endif
</body>
</html>
