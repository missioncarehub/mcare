@php
    $layout = \App\Support\AccountPortal::dashboardLayoutFor($user);
    $isAdmin = $user->role === 'admin';
    $siteSettings = $siteSettings ?? null;
    $registrarHasErrors = $errors->registrar->any();
    $registrarName = $registrarHasErrors ? old('registrar_name') : ($siteSettings?->registrarNameForForm() ?? '');
    $registrarSignatureType = $registrarHasErrors ? old('registrar_signature_type', 'draw') : ($siteSettings?->registrar_signature_type ?: 'draw');
    $hasSavedRegistrarSignature = (bool) $siteSettings?->hasRegistrarSignature();
    $expiryModes = \App\Models\PublicSiteSetting::unusedApprovedExpiryModes();
    $expiryOff = \App\Models\PublicSiteSetting::UNUSED_APPROVED_EXPIRY_OFF;
    $expiryDays = \App\Models\PublicSiteSetting::UNUSED_APPROVED_EXPIRY_DAYS;
    $expiryMonths = \App\Models\PublicSiteSetting::UNUSED_APPROVED_EXPIRY_MONTHS;
    $expiryDateMode = \App\Models\PublicSiteSetting::UNUSED_APPROVED_EXPIRY_DATE;
@endphp

@extends($layout, ['title' => 'Account Settings | MCARE '.$roleLabel])

@section('content')
<section class="space-y-6">
    @if (session('saved') && $user->role === 'admin')
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status" aria-live="polite" data-auto-dismiss="5000">{{ session('saved') }}</div>
    @endif

    <header class="border-b border-slate-200 pb-6">
        <p class="dashboard-section-kicker">Account preferences</p>
        <h1 class="dashboard-section-title mt-2 text-3xl">Settings</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Manage the signed-in {{ strtolower($roleLabel) }} account, profile photo, display preference, and password.{{ $isAdmin ? ' Admins can also save unused approved-application expiry and the TESDA form registrar here.' : '' }}</p>
    </header>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.35fr)]">
        <div class="space-y-6">
            <section class="dashboard-panel space-y-4" data-profile-photo-form>
                <p class="dashboard-section-kicker">Signed-in account</p>
                <div class="flex items-center gap-4">
                    <x-user-avatar :user="$user" class="grid h-14 w-14 place-items-center rounded-full bg-purple-100 text-lg font-black text-purple-800" />
                    <div class="min-w-0">
                        <h2 class="truncate text-xl font-bold text-slate-950">{{ $user->name }}</h2>
                        <p class="mt-1 truncate text-sm text-slate-600">{{ $user->email }}</p>
                    </div>
                </div>
                <span class="inline-flex rounded-lg bg-purple-50 px-3 py-1.5 text-xs font-bold text-purple-800">{{ $roleLabel }}</span>

                <form method="POST" action="{{ route('account.avatar.update') }}" enctype="multipart/form-data" class="space-y-3 border-t border-slate-200 pt-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label for="avatar" class="block text-sm font-bold text-slate-700">Profile photo</label>
                        <p class="mt-1 text-xs leading-5 text-slate-500">JPG, PNG, or WEBP. Maximum 5MB. The photo is stored in public storage and used across MCARE dashboards.</p>
                        <input
                            id="avatar"
                            name="avatar"
                            type="file"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            required
                            class="form-field mt-3 text-sm"
                            data-profile-photo-input
                        >
                        @error('avatar') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <button type="submit" class="secondary-action w-full sm:w-auto">
                        <x-dashboard-icon name="cloud-arrow-up" class="h-4 w-4" />
                        <span>Save photo</span>
                    </button>
                </form>
            </section>

            <section id="preferences" class="dashboard-panel space-y-3">
                <p class="dashboard-section-kicker">Display</p>
                <h2 class="text-lg font-bold text-slate-950">Theme preference</h2>
                <p class="text-sm leading-6 text-slate-600">Night mode is stored only on this browser and can be changed anytime.</p>
                <button type="button" class="secondary-action w-full sm:w-auto" data-dashboard-theme-toggle aria-pressed="false">
                    <x-dashboard-icon name="moon" class="h-4 w-4" data-dashboard-theme-icon="moon" />
                    <x-dashboard-icon name="sun" class="hidden h-4 w-4" data-dashboard-theme-icon="sun" />
                    <span data-dashboard-theme-label>Night mode</span>
                </button>
            </section>

            <a href="{{ route('account.help') }}" class="dashboard-panel block space-y-2 transition hover:border-purple-300">
                <p class="dashboard-section-kicker">Need assistance?</p>
                <h2 class="text-lg font-bold text-slate-950">Open help center</h2>
                <p class="text-sm leading-6 text-slate-600">View guidance tailored to the {{ strtolower($roleLabel) }} portal.</p>
            </a>
        </div>

        <section id="change-password" class="dashboard-panel scroll-mt-8 space-y-4">
            <p class="dashboard-section-kicker">Security</p>
            <h2 class="text-lg font-bold text-slate-950">Change password</h2>
            <p class="text-sm leading-6 text-slate-600">Use at least eight characters with both letters and numbers.</p>
            <form method="POST" action="{{ route('account.password.update') }}" class="space-y-5">
                @csrf
                @method('PATCH')
                <label class="block text-sm font-bold text-slate-700">Current password
                    <input name="current_password" type="password" autocomplete="current-password" required class="form-field mt-2 text-base">
                    @error('current_password') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="block text-sm font-bold text-slate-700">New password
                    <input name="password" type="password" autocomplete="new-password" required class="form-field mt-2 text-base">
                    @error('password') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="block text-sm font-bold text-slate-700">Confirm new password
                    <input name="password_confirmation" type="password" autocomplete="new-password" required class="form-field mt-2 text-base">
                </label>
                <button type="submit" class="primary-action">
                    <x-dashboard-icon name="key" class="h-4 w-4" />
                    <span>Update password</span>
                </button>
            </form>
        </section>
    </div>

    @if ($isAdmin)
        @php
            $expiryHasErrors = $errors->unusedApprovedExpiry->any();
            $expiryMode = $expiryHasErrors
                ? old('unused_approved_expiry_mode', $expiryOff)
                : ($siteSettings?->unusedApprovedExpiryMode() ?? $expiryOff);
            $expiryAmount = $expiryHasErrors
                ? old('unused_approved_expiry_amount')
                : ($siteSettings?->unused_approved_expiry_amount ?? '');
            $expiryDate = $expiryHasErrors
                ? old('unused_approved_expiry_date')
                : optional($siteSettings?->unused_approved_expiry_date)->format('Y-m-d');
            $expirySummary = $siteSettings?->unusedApprovedExpirySummary();
        @endphp
        <section id="unused-approved-expiry" class="dashboard-panel scroll-mt-8 space-y-4">
            <p class="dashboard-section-kicker">Applications</p>
            <h2 class="text-lg font-bold text-slate-950">Unused approved applications</h2>
            <p class="text-sm leading-6 text-slate-600">If an application is approved but the applicant never continues to enrollment, MCARE can delete that application automatically. Submitted enrollments are never removed by this setting.</p>
            @if ($expirySummary && ! $expiryHasErrors)
                <p class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium leading-6 text-amber-900">{{ $expirySummary }}</p>
            @endif
            <form method="POST" action="{{ route('account.unused-approved-expiry.update') }}" class="space-y-5" data-unused-approved-expiry-form>
                @csrf
                @method('PATCH')
                <fieldset class="space-y-3">
                    <legend class="text-sm font-bold text-slate-700">Delete unused approved applications</legend>
                    @foreach ($expiryModes as $mode => $label)
                        <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800">
                            <input type="radio" name="unused_approved_expiry_mode" value="{{ $mode }}" class="mt-1" @checked($expiryMode === $mode)>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                    @error('unused_approved_expiry_mode', 'unusedApprovedExpiry') <span class="block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
                </fieldset>

                <div data-expiry-amount-fields class="{{ in_array($expiryMode, [$expiryDays, $expiryMonths], true) ? '' : 'hidden' }}">
                    <label for="unused_approved_expiry_amount" class="block text-sm font-bold text-slate-700">Keep unused approved applications for</label>
                    <input id="unused_approved_expiry_amount" name="unused_approved_expiry_amount" type="number" min="1" max="365" value="{{ $expiryAmount }}" class="form-field mt-2 max-w-xs text-base" placeholder="14">
                    <p class="mt-2 text-xs leading-5 text-slate-500">Counted from the day the application was approved. Days accept 1–365. Months accept 1–36.</p>
                    @error('unused_approved_expiry_amount', 'unusedApprovedExpiry') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
                </div>

                <div data-expiry-date-fields class="{{ $expiryMode === $expiryDateMode ? '' : 'hidden' }}">
                    <label for="unused_approved_expiry_date" class="block text-sm font-bold text-slate-700">Delete unused approved applications starting</label>
                    <input id="unused_approved_expiry_date" name="unused_approved_expiry_date" type="date" value="{{ $expiryDate }}" class="form-field mt-2 max-w-xs text-base">
                    <p class="mt-2 text-xs leading-5 text-slate-500">Applications approved on or before this date are removed if the applicant still has not enrolled. Applications approved after this date stay until you set a new deadline.</p>
                    @error('unused_approved_expiry_date', 'unusedApprovedExpiry') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="primary-action">
                    <x-dashboard-icon name="save" class="h-4 w-4" />
                    <span>Save application expiry</span>
                </button>
            </form>
        </section>

        <section id="tesda-registrar" class="dashboard-panel scroll-mt-8 space-y-4">
            <p class="dashboard-section-kicker">TESDA form</p>
            <h2 class="text-lg font-bold text-slate-950">TESDA form registrar</h2>
            <p class="text-sm leading-6 text-slate-600">This name and signature are placed on the <span class="font-semibold">Noted by: Registrar / School Administrator</span> line when you preview or download Registration Form MIS 03-01.</p>
            <form
                method="POST"
                action="{{ route('account.registrar.update') }}"
                enctype="multipart/form-data"
                class="space-y-5"
                data-single-action
                data-registrar-signature-form
                data-signature-saved="{{ $hasSavedRegistrarSignature ? '1' : '0' }}"
            >
                @csrf
                @method('PATCH')
                <div>
                    <label for="registrar_name" class="block text-sm font-bold text-slate-700">Registrar / school administrator name</label>
                    <input id="registrar_name" name="registrar_name" type="text" value="{{ $registrarName }}" required maxlength="180" placeholder="Salvacion A. Collao" class="form-field mt-2 max-w-xl text-base">
                    <p class="mt-2 text-xs leading-5 text-slate-500">Use the same printed name that should appear under the signature.</p>
                    @error('registrar_name', 'registrar') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-purple-700">Signature method</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Draw on the pad or upload a clear JPG or PNG. Maximum 5MB.</p>
                        </div>
                        <div class="flex rounded-full border border-purple-100 bg-white p-1">
                            <label class="cursor-pointer rounded-full px-4 py-2 text-sm font-bold text-slate-700">
                                <input type="radio" name="registrar_signature_type" value="draw" class="mr-1" @checked($registrarSignatureType === 'draw')>
                                Draw
                            </label>
                            <label class="cursor-pointer rounded-full px-4 py-2 text-sm font-bold text-slate-700">
                                <input type="radio" name="registrar_signature_type" value="upload" class="mr-1" @checked($registrarSignatureType === 'upload')>
                                Upload
                            </label>
                        </div>
                    </div>

                    <div id="registrar-draw-signature-panel" class="mt-4">
                        <canvas id="registrar_signature_canvas" width="900" height="220" class="block h-44 w-full touch-none rounded-lg border border-slate-200 bg-white" aria-label="Draw the registrar signature"></canvas>
                        <input id="registrar_signature_data" name="registrar_signature_data" type="hidden" value="{{ old('registrar_signature_data') }}">
                        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p id="registrar_signature_draw_status" class="text-xs font-semibold text-slate-500">
                                {{ $hasSavedRegistrarSignature ? 'Previously saved. Draw again to replace it.' : 'No signature drawn yet.' }}
                            </p>
                            <button type="button" id="clear_registrar_signature" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:border-purple-200 hover:text-purple-700">
                                Clear signature
                            </button>
                        </div>
                        @error('registrar_signature_data', 'registrar') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div id="registrar-upload-signature-panel" class="mt-4 hidden">
                        <div data-registrar-upload-zone class="relative rounded-xl border-2 border-dashed border-purple-200 bg-white px-5 py-7 text-center transition hover:border-purple-400 hover:bg-purple-50/50">
                            <input id="registrar_signature_upload" name="registrar_signature_upload" type="file" accept=".jpg,.jpeg,.png" class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0">
                            <p class="text-sm font-bold text-purple-700">Click to upload signature</p>
                            <p data-registrar-upload-name class="mt-1 text-xs font-semibold text-slate-500">
                                {{ $hasSavedRegistrarSignature ? 'Previously saved. Upload a new image to replace it.' : 'No file selected' }}
                            </p>
                        </div>
                        @error('registrar_signature_upload', 'registrar') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
                    </div>

                    @if ($hasSavedRegistrarSignature)
                        <div class="mt-4 overflow-hidden rounded-xl border border-emerald-200 bg-white p-3">
                            <p class="text-[11px] font-black uppercase tracking-wide text-emerald-700">Saved signature</p>
                            <img src="{{ route('account.registrar.signature') }}" alt="Saved registrar signature" class="mt-2 max-h-24 w-full bg-white object-contain">
                        </div>
                    @endif
                </div>

                <button type="submit" data-action-button class="primary-action">
                    <x-dashboard-icon name="save" class="h-4 w-4" />
                    <span>Save registrar signature</span>
                </button>
            </form>
        </section>
    @endif
</section>

@if ($isAdmin)
    <script>
        (() => {
            const form = document.querySelector('[data-unused-approved-expiry-form]');
            if (!form) return;

            const amountFields = form.querySelector('[data-expiry-amount-fields]');
            const dateFields = form.querySelector('[data-expiry-date-fields]');
            const radios = form.querySelectorAll('input[name="unused_approved_expiry_mode"]');

            const syncExpiryFields = () => {
                const mode = form.querySelector('input[name="unused_approved_expiry_mode"]:checked')?.value || 'off';
                amountFields?.classList.toggle('hidden', mode !== 'days' && mode !== 'months');
                dateFields?.classList.toggle('hidden', mode !== 'date');
            };

            radios.forEach((radio) => radio.addEventListener('change', syncExpiryFields));
            syncExpiryFields();
        })();
    </script>
@endif

@if ($isAdmin)
    <script>
        (() => {
            const form = document.querySelector('[data-registrar-signature-form]');
            const canvas = document.getElementById('registrar_signature_canvas');
            const signatureData = document.getElementById('registrar_signature_data');
            const clearButton = document.getElementById('clear_registrar_signature');
            const status = document.getElementById('registrar_signature_draw_status');
            const drawPanel = document.getElementById('registrar-draw-signature-panel');
            const uploadPanel = document.getElementById('registrar-upload-signature-panel');
            const uploadInput = document.getElementById('registrar_signature_upload');
            const uploadName = document.querySelector('[data-registrar-upload-name]');
            const radios = document.querySelectorAll('input[name="registrar_signature_type"]');
            if (!form || !canvas || !signatureData) return;

            const existingSignatureSaved = form.dataset.signatureSaved === '1';
            const restoredSignatureData = signatureData.value;
            const context = canvas.getContext('2d');
            let drawing = false;
            let signatureDrawn = false;

            function resizeCanvas() {
                const image = signatureDrawn ? canvas.toDataURL('image/png') : null;
                const rect = canvas.getBoundingClientRect();
                const ratio = window.devicePixelRatio || 1;
                canvas.width = Math.max(Math.floor(rect.width * ratio), 1);
                canvas.height = Math.max(Math.floor(rect.height * ratio), 1);
                context.setTransform(ratio, 0, 0, ratio, 0, 0);
                context.lineWidth = 2.5;
                context.lineCap = 'round';
                context.lineJoin = 'round';
                context.strokeStyle = '#1e293b';
                if (image) {
                    const restored = new Image();
                    restored.onload = () => context.drawImage(restored, 0, 0, rect.width, rect.height);
                    restored.src = image;
                }
            }

            function pointFromEvent(event) {
                const rect = canvas.getBoundingClientRect();
                return { x: event.clientX - rect.left, y: event.clientY - rect.top };
            }

            function startDrawing(event) {
                event.preventDefault();
                drawing = true;
                const point = pointFromEvent(event);
                context.beginPath();
                context.moveTo(point.x, point.y);
            }

            function draw(event) {
                if (!drawing) return;
                event.preventDefault();
                const point = pointFromEvent(event);
                context.lineTo(point.x, point.y);
                context.stroke();
                signatureDrawn = true;
                signatureData.value = canvas.toDataURL('image/png');
                if (status) {
                    status.textContent = 'Signature captured.';
                    status.classList.remove('text-red-600');
                    status.classList.add('text-slate-500');
                }
            }

            function stopDrawing() {
                drawing = false;
                context.beginPath();
            }

            function syncSignatureMode() {
                const mode = document.querySelector('input[name="registrar_signature_type"]:checked')?.value || 'draw';
                drawPanel?.classList.toggle('hidden', mode !== 'draw');
                uploadPanel?.classList.toggle('hidden', mode !== 'upload');
            }

            function restoreSignature(data) {
                if (!data || !data.startsWith('data:image/png;base64,')) return;
                const restored = new Image();
                restored.onload = () => {
                    const rect = canvas.getBoundingClientRect();
                    context.drawImage(restored, 0, 0, rect.width, rect.height);
                    signatureDrawn = true;
                    signatureData.value = data;
                    if (status) status.textContent = 'Signature restored. Draw again to replace it.';
                };
                restored.src = data;
            }

            canvas.addEventListener('pointerdown', startDrawing);
            canvas.addEventListener('pointermove', draw);
            canvas.addEventListener('pointerup', stopDrawing);
            canvas.addEventListener('pointerleave', stopDrawing);
            clearButton?.addEventListener('click', () => {
                context.clearRect(0, 0, canvas.width, canvas.height);
                signatureDrawn = false;
                signatureData.value = '';
                if (status) {
                    status.textContent = existingSignatureSaved
                        ? 'Previously saved. Draw again to replace it.'
                        : 'No signature drawn yet.';
                    status.classList.remove('text-red-600');
                    status.classList.add('text-slate-500');
                }
            });
            radios.forEach((radio) => radio.addEventListener('change', syncSignatureMode));
            uploadInput?.addEventListener('change', () => {
                const file = uploadInput.files?.[0];
                if (uploadName) uploadName.textContent = file ? `Selected: ${file.name}` : (existingSignatureSaved ? 'Previously saved. Upload a new image to replace it.' : 'No file selected');
            });
            window.addEventListener('resize', resizeCanvas);
            form.addEventListener('submit', (event) => {
                const mode = document.querySelector('input[name="registrar_signature_type"]:checked')?.value || 'draw';
                if (mode === 'draw' && !signatureDrawn && !existingSignatureSaved) {
                    event.preventDefault();
                    if (status) {
                        status.textContent = 'Draw the registrar signature before saving.';
                        status.classList.remove('text-slate-500');
                        status.classList.add('text-red-600');
                    }
                    return;
                }
                if (mode === 'upload' && !uploadInput?.files?.length && !existingSignatureSaved) {
                    event.preventDefault();
                    if (uploadName) uploadName.textContent = 'Upload a registrar signature image before saving.';
                }
            });
            resizeCanvas();
            restoreSignature(restoredSignatureData);
            syncSignatureMode();
        })();
    </script>
@endif
@endsection
