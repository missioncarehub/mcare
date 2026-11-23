<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#f3f2f6]">
<head>
    <x-site-favicon />
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
                <p class="enrollment-lede">Enter your application number (e.g. MCA-2026-XXXXXX) or enrollment number (e.g. MCE-2026-XXXXXX) to view your application progress, enrollment status, and document review.</p>
            </header>
            <div class="enrollment-form-body space-y-6">
                <form method="POST" action="{{ route('applications.lookup') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="form-label" for="application_number">Application or Enrollment number</label>
                        <input class="form-field font-mono uppercase" id="application_number" name="application_number" value="{{ old('application_number', $submittedNumber) }}" placeholder="e.g. MCA-2026-XXXXXX or MCE-2026-XXXXXX" required>
                        @error('application_number')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="primary-action">Look up status</button>
                </form>

                @if (session('saved'))
                    <p class="enrollment-notice enrollment-notice-ok" role="status">{{ session('saved') }}</p>
                @endif

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
                            'blocked' => 'bg-amber-100 text-amber-900 ring-amber-300',
                        ];
                        $stateLabels = [
                            'complete' => 'Complete',
                            'in_progress' => 'In progress',
                            'pending' => 'Pending',
                            'blocked' => 'Need changes',
                        ];
                        $documentStatuses = $enrollment?->documentStatuses() ?? [];
                        $canReplaceDocuments = $enrollment && $enrollment->status !== \App\Models\EnrollmentApplication::STATUS_APPROVED;
                        $documentStatusTones = [
                            'accepted' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                            'replace' => 'bg-amber-50 text-amber-800 ring-amber-100',
                            'missing' => 'bg-red-50 text-red-700 ring-red-100',
                            'unreviewed' => 'bg-slate-50 text-slate-600 ring-slate-100',
                            'not_uploaded' => 'bg-slate-50 text-slate-500 ring-slate-100',
                        ];
                    @endphp

                    <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-5">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Application number</p>
                                <p class="mt-1 font-display text-2xl font-extrabold text-slate-950">{{ $admission->application_number }}</p>
                                @if ($enrollment)
                                    <p class="mt-1 text-xs font-semibold text-purple-700">Enrollment ID: <span class="font-mono font-bold">{{ $enrollment->enrollment_number }}</span></p>
                                @endif
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Current stage</p>
                                <p class="mt-1 font-display text-lg font-bold text-slate-950">{{ $currentStage['label'] }}</p>
                                <span class="mt-1 inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold ring-1 {{ $badgeToneByState[$currentStage['state']] ?? 'bg-slate-50 text-slate-600 ring-slate-100' }}">
                                    {{ $currentStage['state'] === 'blocked' && ($currentStage['key'] ?? '') === 'documents' ? 'Need changes' : ($stateLabels[$currentStage['state']] ?? ucfirst($currentStage['state'])) }}
                                </span>
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
                                <p>
                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Payment</span><br>
                                    @if ($enrollment->hasEnrollmentPaymentClearance() || ($enrollment->payment_verified_at && (float) $enrollment->total_paid_amount > 0))
                                        <span class="font-bold text-emerald-700">✓ {{ $enrollment->paymentStatusLabel() }} (Verified ₱{{ number_format((float) ($enrollment->total_paid_amount ?: $enrollment->downpayment_amount ?: 2000), 2) }})</span>
                                    @elseif (in_array($enrollment->payment_status, [\App\Models\EnrollmentApplication::PAYMENT_ONLINE_PENDING, \App\Models\EnrollmentApplication::PAYMENT_ONSITE_PENDING], true))
                                        <span class="font-bold text-amber-700">⏳ {{ $enrollment->paymentStatusLabel() }} (Awaiting verification)</span>
                                    @elseif ($enrollment->payment_status === \App\Models\EnrollmentApplication::PAYMENT_EXPIRED)
                                        <span class="font-bold text-red-700">✕ {{ $enrollment->paymentStatusLabel() }}</span>
                                    @else
                                        {{ $enrollment->paymentStatusLabel() }}
                                    @endif
                                </p>
                                <p>
                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Document status</span><br>
                                    @php
                                        $flaggedCount = collect($documentStatuses)->filter(fn($doc) => in_array($doc['status'] ?? '', ['replace', 'missing'], true))->count();
                                    @endphp
                                    @if ($enrollment->documents_reviewed_at && $flaggedCount === 0)
                                        <span class="font-bold text-emerald-700">✓ All Verified</span>
                                    @elseif ($flaggedCount > 0)
                                        <span class="font-bold text-amber-800">⚠️ {{ $flaggedCount }} {{ Str::plural('document', $flaggedCount) }} need changes</span>
                                    @elseif (filled($enrollment->birth_certificate_path) || filled($enrollment->education_document_path))
                                        <span class="text-slate-700 font-semibold">⏳ In review queue</span>
                                    @else
                                        <span class="text-slate-500">Pending submission</span>
                                    @endif
                                </p>
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
                                                <span class="ml-2 inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold ring-1 {{ $badgeToneByState[$stage['state']] ?? 'bg-slate-50 text-slate-500 ring-slate-100' }}">
                                                    {{ $stage['state'] === 'blocked' && $stage['key'] === 'documents' ? 'Need changes' : ($stateLabels[$stage['state']] ?? ucfirst($stage['state'])) }}
                                                </span>
                                            </p>
                                            <p class="mt-1 text-xs text-slate-600">{{ $stage['description'] }}</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        </div>

                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Document status</p>
                            @if ($documentStatuses === [])
                                <p class="mt-2 text-sm leading-6 text-slate-600">Document review starts after you submit the enrollment form.</p>
                            @else
                                @if ($errors->has('verifier') || $errors->has('replacement') || $errors->has('document'))
                                    <p class="mt-2 text-sm font-semibold text-red-700" role="alert">{{ $errors->first('verifier') ?: $errors->first('replacement') ?: $errors->first('document') }}</p>
                                @endif
                                <ul class="mt-3 space-y-2">
                                    @foreach ($documentStatuses as $document)
                                        <li class="rounded-xl border border-slate-100 bg-slate-50/50 px-3 py-2.5">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <p class="text-sm font-bold text-slate-900">{{ $document['label'] }}</p>
                                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-bold ring-1 {{ $documentStatusTones[$document['status']] ?? 'bg-slate-50 text-slate-600 ring-slate-100' }}">{{ $document['status_label'] }}</span>
                                            </div>
                                            @if ($document['note'])
                                                <p class="mt-1 text-xs leading-5 text-slate-600">{{ $document['note'] }}</p>
                                            @endif
                                            @if ($canReplaceDocuments && in_array($document['status'], ['replace', 'missing'], true))
                                                <div class="mt-3" data-doc-wrapper="{{ $document['key'] }}">
                                                    <!-- Trigger OTP if session is not yet authorized -->
                                                    <div data-doc-locked class="{{ $isDocumentUploadAuthorized ? 'hidden' : '' }}">
                                                        <button type="button" data-otp-trigger class="secondary-action text-xs">Upload replacement</button>
                                                    </div>

                                                    <!-- Unlocked replacement form (no verifier field needed) -->
                                                    <form method="POST" action="{{ route('applications.documents.replace') }}" enctype="multipart/form-data" data-doc-unlocked class="{{ $isDocumentUploadAuthorized ? '' : 'hidden' }} space-y-2">
                                                        @csrf
                                                        <input type="hidden" name="application_number" value="{{ $admission->application_number }}">
                                                        <input type="hidden" name="document" value="{{ $document['key'] }}">
                                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500" for="replacement-{{ $document['key'] }}">Upload replacement</label>
                                                        <input id="replacement-{{ $document['key'] }}" name="replacement" type="file" accept="{{ in_array($document['key'], ['id-photo', 'signature'], true) ? '.jpg,.jpeg,.png' : '.pdf,.jpg,.jpeg,.png' }}" required>
                                                        <button type="submit" class="secondary-action">Submit replacement</button>
                                                    </form>
                                                </div>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
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

    @if ($admission)
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
