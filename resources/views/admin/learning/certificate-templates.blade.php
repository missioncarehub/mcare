@extends('admin.layouts.app', ['title' => 'Certificate templates | MCARE Admin'])

@section('content')
<section class="space-y-6">
    <header class="flex flex-col gap-3 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="dashboard-section-kicker">Training records</p>
            <h1 class="dashboard-section-title mt-2 text-3xl">Certificate templates</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">The title, statement, logo, and signatories saved here are used the next time you generate a COTC or TOR. Enrollment ID, document number, and the MCARE watermark stay on every file.</p>
        </div>
        <a href="{{ route('admin.learning.certificates') }}" class="secondary-action">Back to Training Records</a>
    </header>

    @if (session('saved'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">{{ session('saved') }}</div>
    @endif

    @foreach ($sections as $type => $section)
        @php
            $active = $section['active'];
            $defaults = $section['defaults'];
            $label = $type === \App\Models\OfficialDocument::TYPE_COTC ? 'COTC' : 'TOR';
            $usingOld = old('type') === $type;
            $field = function (string $key) use ($usingOld, $active, $defaults) {
                if ($usingOld) {
                    return old($key);
                }

                if ($active) {
                    $stored = $active->{$key};

                    return ($stored === null || $stored === '') ? ($defaults[$key] ?? '') : $stored;
                }

                return $defaults[$key] ?? '';
            };
            $artwork = $usingOld ? (bool) old('use_official_artwork') : (bool) ($active->use_official_artwork ?? $defaults['use_official_artwork']);
        @endphp
        <form method="POST" action="{{ route('admin.learning.certificate-templates.store') }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(18rem,0.8fr)]" data-certificate-form>
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="dashboard-panel space-y-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="dashboard-section-kicker">{{ $label }}</p>
                        <h2 class="mt-1 text-xl font-bold text-slate-950">{{ $label === 'COTC' ? 'Certificate of Training Completion' : 'Transcript of Records' }}</h2>
                        <p class="mt-1 text-sm text-slate-600">{{ $active ? 'Active version '.$active->version.'. Saving creates the next version and makes it active.' : 'No saved version yet. Generate still uses the current official layout until you save.' }}</p>
                    </div>
                    <a href="{{ route('admin.learning.certificate-templates.preview', $type) }}" target="_blank" rel="noopener" class="secondary-action">Preview active PDF</a>
                </div>

                <div>
                    <label class="form-label" for="{{ $type }}-title">Title</label>
                    <input id="{{ $type }}-title" name="title" value="{{ $field('title') }}" maxlength="180" required class="form-field" data-preview-title-input>
                    @error('title') @if($usingOld)<span class="mt-1 block text-sm font-semibold text-red-600">{{ $message }}</span>@endif @enderror
                </div>

                <div>
                    <label class="form-label" for="{{ $type }}-body">{{ $label === 'COTC' ? 'Statement' : 'Statement added to the remarks' }}</label>
                    <textarea id="{{ $type }}-body" name="body" rows="4" maxlength="2000" class="form-field" data-preview-body-input @if($label === 'COTC') required @endif>{{ $field('body') }}</textarea>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach (\App\Models\CertificateTemplate::PLACEHOLDERS as $token)
                            <button type="button" class="rounded-md border border-slate-200 bg-slate-50 px-2 py-1 text-xs font-bold text-slate-700 hover:border-purple-300 hover:text-purple-800" data-insert-token="{{ $token }}" data-insert-target="{{ $type }}-body">{{ '{'.'{'.$token.'}'.'}' }}</button>
                        @endforeach
                    </div>
                    @error('body') @if($usingOld)<span class="mt-1 block text-sm font-semibold text-red-600">{{ $message }}</span>@endif @enderror
                </div>

                @if ($label === 'COTC')
                    <label class="flex items-start gap-3 text-sm text-slate-700">
                        <input type="checkbox" name="use_official_artwork" value="1" class="mt-1 rounded border-slate-300 text-purple-600" data-artwork-toggle @checked($artwork)>
                        <span><span class="font-bold text-slate-950">Use the official certificate artwork</span><span class="mt-1 block leading-5">Keeps the letter landscape certificate background. The statement and signatories are printed on top of it. Turn this off to use the paper size below.</span></span>
                    </label>
                @endif

                <div class="grid gap-4 sm:grid-cols-2" data-paper-fields>
                    <div>
                        <label class="form-label" for="{{ $type }}-paper">Paper size</label>
                        <select id="{{ $type }}-paper" name="paper_size" class="form-field" data-preview-paper>
                            @foreach (['letter' => 'Letter', 'a4' => 'A4', 'legal' => 'Legal'] as $value => $paperLabel)
                                <option value="{{ $value }}" @selected($field('paper_size') === $value)>{{ $paperLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="{{ $type }}-orientation">Orientation</label>
                        <select id="{{ $type }}-orientation" name="orientation" class="form-field" data-preview-orientation>
                            <option value="landscape" @selected($field('orientation') === 'landscape')>Landscape</option>
                            <option value="portrait" @selected($field('orientation') === 'portrait')>Portrait</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="form-label" for="{{ $type }}-logo">Logo</label>
                    <input id="{{ $type }}-logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp" class="form-field" data-preview-logo>
                    @if ($active?->absoluteAsset($active->logo_path))
                        <img src="{{ route('admin.learning.certificate-templates.asset', [$active, 'logo']) }}" alt="Current logo" class="mt-2 h-14 w-14 object-contain">
                    @endif
                    <p class="mt-1 text-xs text-slate-500">Leave this empty to keep the current logo. JPG, PNG, or WEBP, up to 2 MB.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-3 rounded-xl border border-slate-200 p-4">
                        <p class="text-sm font-bold text-slate-950">{{ $label === 'COTC' ? 'Registrar signatory' : 'Certified by' }}</p>
                        <div>
                            <label class="form-label" for="{{ $type }}-signatory-name">Name</label>
                            <input id="{{ $type }}-signatory-name" name="signatory_name" value="{{ $field('signatory_name') }}" maxlength="180" class="form-field" data-preview-signatory>
                        </div>
                        <div>
                            <label class="form-label" for="{{ $type }}-signatory-role">Role</label>
                            <input id="{{ $type }}-signatory-role" name="signatory_role" value="{{ $field('signatory_role') }}" maxlength="120" class="form-field" data-preview-role>
                        </div>
                        <div>
                            <label class="form-label" for="{{ $type }}-signature">E-signature</label>
                            <input id="{{ $type }}-signature" name="signature" type="file" accept="image/png,image/jpeg,image/webp" class="form-field">
                            @if ($active?->absoluteAsset($active->signature_path))
                                <img src="{{ route('admin.learning.certificate-templates.asset', [$active, 'signature']) }}" alt="Current registrar signature" class="mt-2 h-12 object-contain">
                            @endif
                        </div>
                    </div>
                    <div class="space-y-3 rounded-xl border border-slate-200 p-4">
                        <p class="text-sm font-bold text-slate-950">{{ $label === 'COTC' ? 'Trainer signatory' : 'Prepared by' }}</p>
                        <div>
                            <label class="form-label" for="{{ $type }}-second-name">Name</label>
                            <input id="{{ $type }}-second-name" name="second_signatory_name" value="{{ $field('second_signatory_name') }}" maxlength="180" class="form-field" data-preview-second-signatory>
                        </div>
                        <div>
                            <label class="form-label" for="{{ $type }}-second-role">Role</label>
                            <input id="{{ $type }}-second-role" name="second_signatory_role" value="{{ $field('second_signatory_role') }}" maxlength="120" class="form-field" data-preview-second-role>
                        </div>
                        <div>
                            <label class="form-label" for="{{ $type }}-second-signature">E-signature</label>
                            <input id="{{ $type }}-second-signature" name="second_signature" type="file" accept="image/png,image/jpeg,image/webp" class="form-field">
                            @if ($active?->absoluteAsset($active->second_signature_path))
                                <img src="{{ route('admin.learning.certificate-templates.asset', [$active, 'second-signature']) }}" alt="Current trainer signature" class="mt-2 h-12 object-contain">
                            @endif
                        </div>
                    </div>
                </div>

                <button type="submit" class="primary-action">Save as active version</button>
            </div>

            <aside class="space-y-4">
                <div class="dashboard-panel">
                    <p class="dashboard-section-kicker">Live preview</p>
                    <h2 class="mt-1 text-lg font-bold text-slate-950">Sample {{ $label }}</h2>
                    <p class="mt-1 text-xs leading-5 text-slate-500">This sheet updates as you type. Preview active PDF uses the last saved version.</p>
                    <div class="mt-4 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm" data-preview-sheet>
                        <div class="space-y-3 p-4 text-center">
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-purple-300" data-preview-watermark>MCARE OFFICIAL</p>
                            <p class="font-serif text-lg font-bold text-slate-950" data-preview-title></p>
                            <p class="text-xs uppercase tracking-wide text-slate-500">Maria Reyes Santos</p>
                            <p class="text-sm leading-6 text-slate-700" data-preview-body></p>
                            <div class="grid grid-cols-2 gap-3 pt-4 text-xs">
                                <p><span class="block font-bold text-slate-950" data-preview-second-name></span><span data-preview-second-role-text></span></p>
                                <p><span class="block font-bold text-slate-950" data-preview-signatory-name></span><span data-preview-role-text></span></p>
                            </div>
                            <p class="pt-2 text-left text-[10px] font-semibold text-slate-500">Enrollment ID: MCE-2026-SAMPLE · Document no. MCARE-{{ $label }}-2026-00002-V1</p>
                        </div>
                    </div>
                </div>

                <div class="dashboard-panel">
                    <p class="dashboard-section-kicker">Version history</p>
                    <div class="mt-3 space-y-3">
                        @forelse ($section['history'] as $version)
                            <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3">
                                <div>
                                    <p class="text-sm font-bold text-slate-950">Version {{ $version->version }} @if($version->is_active)<span class="ml-1 rounded bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700">Active</span>@endif</p>
                                    <p class="mt-1 text-xs text-slate-600">{{ $version->title }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $version->created_at?->format('M j, Y g:i A') }} · {{ $version->createdBy?->name ?? 'Admin' }}</p>
                                </div>
                                @unless($version->is_active)
                                    <button type="submit" form="activate-template-{{ $version->id }}" class="secondary-action">Use</button>
                                @endunless
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No saved versions yet.</p>
                        @endforelse
                    </div>
                </div>
            </aside>
        </form>
        @foreach ($section['history'] as $version)
            @unless($version->is_active)
                <form id="activate-template-{{ $version->id }}" method="POST" action="{{ route('admin.learning.certificate-templates.activate', $version) }}">@csrf @method('PATCH')</form>
            @endunless
        @endforeach
    @endforeach
</section>
<script>
document.querySelectorAll('[data-certificate-form]').forEach((form) => {
    const samples = {
        trainee_name: 'Maria Reyes Santos',
        program: 'Caregiving NC II',
        batch: 'Batch 1 2026',
        completion_date: 'July 18, 2026',
        certificate_no: 'MCARE-SAMPLE-2026-00002-V1',
        enrollment_id: 'MCE-2026-SAMPLE',
        organization: 'Mission Care Training Center',
    };
    const fill = (value) => String(value || '').replace(/\{\{\s*([a-z_]+)\s*\}\}/g, (match, token) => samples[token] ?? match);
    const title = form.querySelector('[data-preview-title]');
    const body = form.querySelector('[data-preview-body]');
    const signatory = form.querySelector('[data-preview-signatory-name]');
    const role = form.querySelector('[data-preview-role-text]');
    const secondName = form.querySelector('[data-preview-second-name]');
    const secondRole = form.querySelector('[data-preview-second-role-text]');
    const sheet = form.querySelector('[data-preview-sheet]');
    const paper = form.querySelector('[data-preview-paper]');
    const orientation = form.querySelector('[data-preview-orientation]');
    const artwork = form.querySelector('[data-artwork-toggle]');
    const render = () => {
        title.textContent = fill(form.querySelector('[data-preview-title-input]').value);
        body.textContent = fill(form.querySelector('[data-preview-body-input]').value);
        signatory.textContent = form.querySelector('[data-preview-signatory]').value;
        role.textContent = form.querySelector('[data-preview-role]').value;
        secondName.textContent = form.querySelector('[data-preview-second-signatory]').value;
        secondRole.textContent = form.querySelector('[data-preview-second-role]').value;
        const ratios = { letter: [11, 8.5], a4: [210, 297], legal: [8.5, 14] };
        const paperValue = artwork?.checked ? 'letter' : paper.value;
        const pair = ratios[paperValue] || ratios.a4;
        const landscape = artwork?.checked || orientation.value === 'landscape';
        sheet.style.aspectRatio = landscape ? `${Math.max(...pair)} / ${Math.min(...pair)}` : `${pair[0]} / ${pair[1]}`;
    };
    form.addEventListener('input', render);
    form.addEventListener('change', render);
    form.querySelectorAll('[data-insert-token]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = document.getElementById(button.dataset.insertTarget);
            const token = '{' + '{' + button.dataset.insertToken + '}' + '}';
            const start = target.selectionStart ?? target.value.length;
            const end = target.selectionEnd ?? start;
            target.value = target.value.slice(0, start) + token + target.value.slice(end);
            target.focus();
            render();
        });
    });
    render();
});
</script>
@endsection
