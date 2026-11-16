@extends('admin.layouts.app', ['title' => 'Manage '.$heading.' | MCARE Admin'])

@section('content')
<section class="space-y-6">
    <header class="flex flex-col gap-3 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="dashboard-section-kicker">Training records</p>
            <h1 class="dashboard-section-title mt-2 text-3xl">Manage {{ $heading }}</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                Upload a certificate design (from Canva, scan, or graphic artwork). Use the sliders below to easily line up the student's name on the certificate line.
            </p>
        </div>
        <a href="{{ route('admin.learning.certificate-templates', ['program_id' => $selectedProgramId]) }}" class="secondary-action">Back to Certificate templates</a>
    </header>

    @if (session('saved'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">{{ session('saved') }}</div>
    @endif

    @if($selectedProgram)
        <div class="rounded-xl border border-purple-200 bg-purple-50/70 p-4">
            <span class="text-xs font-bold uppercase tracking-wider text-purple-900">Program: {{ $selectedProgram->name }} ({{ $selectedProgram->code }})</span>
            <p class="mt-0.5 text-xs text-purple-700">This custom design will be generated exclusively for graduates enrolled under {{ $selectedProgram->name }}.</p>
        </div>
    @else
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">General Default Template</span>
            <p class="mt-0.5 text-xs text-slate-600">This template serves as the fallback for all programs without their own specific certificate.</p>
        </div>
    @endif

    @php
        $active = $section['active'];
        $defaults = $section['defaults'];
        $label = \App\Models\CertificateTemplate::label($type);
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
        $layout = $active?->resolvedFieldLayout() ?? \App\Models\CertificateTemplate::defaultFieldLayout($type, (string) ($field('orientation') ?: 'landscape'));
        if ($usingOld && is_string(old('field_layout'))) {
            $postedLayout = json_decode(old('field_layout'), true);
            if (is_array($postedLayout)) {
                $layout = array_replace($layout, $postedLayout);
            }
        }
        $nameBox = $layout['trainee_name'] ?? ['x' => 5, 'y' => 42, 'size' => 24, 'align' => 'C'];
        $currentNameY = old('trainee_name_y', $nameBox['y'] ?? 42);
        $currentNameX = old('trainee_name_x', $nameBox['x'] ?? 5);
        $currentNameSize = old('trainee_name_size', $nameBox['size'] ?? 24);
        $currentNameAlign = old('trainee_name_align', $nameBox['align'] ?? 'C');

        $backgroundIsPdf = is_string($active?->background_path) && str_ends_with(strtolower($active->background_path), '.pdf');
        $savedImportUrl = (! $artwork && $active && $active->absoluteAsset($active->background_path))
            ? route('admin.learning.certificate-templates.asset', [$active, 'background'])
            : '';
        $savedBackground = $backgroundIsPdf ? '' : $savedImportUrl;
        $previewLandscape = $artwork || $field('orientation') === 'landscape';
    @endphp

    <form method="POST" action="{{ route('admin.learning.certificate-templates.store') }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(20rem,0.8fr)]" data-certificate-form>
        @csrf
        <input type="hidden" name="type" value="{{ $type }}">
        <input type="hidden" name="training_program_id" value="{{ $selectedProgramId }}">

        <div class="dashboard-panel space-y-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="dashboard-section-kicker">{{ $label }} Template Configuration</p>
                    <h2 class="mt-1 text-xl font-bold text-slate-950">{{ $heading }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $active ? 'Active Version '.$active->version : 'No saved template version yet for this program.' }}</p>
                </div>
                <a href="{{ route('admin.learning.certificate-templates.preview', ['type' => $type, 'program_id' => $selectedProgramId]) }}" target="_blank" rel="noopener" class="secondary-action">
                    Preview active PDF
                </a>
            </div>

            {{-- 1. Upload Certificate Image / PDF --}}
            <div class="rounded-xl border border-dashed border-purple-300 bg-purple-50/60 p-4">
                <label class="form-label font-bold text-purple-950" for="{{ $type }}-background">Import a pre-made template</label>
                <input id="{{ $type }}-background" name="background" type="file" accept="image/png,image/jpeg,image/webp,application/pdf" class="form-field bg-white" data-background-file @unless($active?->absoluteAsset($active->background_path)) required @endunless>
                <p class="mt-1.5 text-xs text-slate-600">PNG, JPG, WEBP, or 1-page PDF (up to 2 MB). Upload your finished certificate artwork from Canva, Photoshop, or your scanner.</p>
                @if ($active?->absoluteAsset($active->background_path))
                    <div class="mt-3 flex items-center justify-between rounded-lg bg-white p-2.5 border border-purple-200">
                        <span class="text-xs font-semibold text-slate-800">Current file: {{ basename($active->background_path) }}</span>
                        <label class="flex items-center gap-1.5 text-xs font-medium text-red-600">
                            <input type="checkbox" name="clear_background" value="1" class="rounded border-slate-300 text-red-600">
                            Remove
                        </label>
                    </div>
                @endif
                @error('background')<span class="mt-1 block text-sm font-semibold text-red-600">{{ $message }}</span>@enderror
            </div>

            {{-- 2. Trainee Name Placement Card (Simplified & Prominent) --}}
            <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">🎯 Trainee Name Placement</h3>
                        <p class="text-xs text-slate-500">Fine-tune where the student's name appears on your uploaded certificate.</p>
                    </div>
                    <span class="rounded bg-sky-100 px-2 py-0.5 text-[11px] font-bold text-sky-800">Primary Field</span>
                </div>

                {{-- Vertical Position Slider --}}
                <div>
                    <div class="flex items-center justify-between">
                        <label for="trainee-name-y" class="text-xs font-bold uppercase text-slate-700">Vertical Position (Height %)</label>
                        <span class="text-xs font-bold text-sky-700" id="label-name-y">{{ $currentNameY }}%</span>
                    </div>
                    <input type="range" id="trainee-name-y" name="trainee_name_y" min="15" max="85" step="0.5" value="{{ $currentNameY }}" class="mt-2 w-full accent-purple-600 cursor-pointer">
                    <p class="mt-1 text-[11px] text-slate-500">Slide left or right to move the name up or down until it rests perfectly on the certificate line.</p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {{-- Font Size --}}
                    <div>
                        <label for="trainee-name-size" class="form-label">Font Size</label>
                        <select id="trainee-name-size" name="trainee_name_size" class="form-field">
                            @foreach([16, 18, 20, 22, 24, 26, 28, 30, 32, 36] as $sz)
                                <option value="{{ $sz }}" @selected((int)$currentNameSize === $sz)>{{ $sz }} pt {{ $sz === 24 ? '(Standard)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Alignment --}}
                    <div>
                        <label for="trainee-name-align" class="form-label">Name Alignment</label>
                        <select id="trainee-name-align" name="trainee_name_align" class="form-field">
                            <option value="C" @selected($currentNameAlign === 'C')>Center (Recommended)</option>
                            <option value="L" @selected($currentNameAlign === 'L')>Left-aligned</option>
                            <option value="R" @selected($currentNameAlign === 'R')>Right-aligned</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-6 pt-2 border-t border-slate-200">
                    <label class="flex items-center gap-2 text-xs font-semibold text-slate-700">
                        <input type="checkbox" name="include_cert_no" value="1" class="rounded border-slate-300 text-purple-600" checked>
                        Include Certificate Number (bottom corner)
                    </label>
                    <label class="flex items-center gap-2 text-xs font-semibold text-slate-700">
                        <input type="checkbox" name="include_date" value="1" class="rounded border-slate-300 text-purple-600" checked>
                        Include Completion Date
                    </label>
                </div>
            </div>

            {{-- 3. Paper Size & Orientation --}}
            <div class="grid gap-4 sm:grid-cols-2" data-paper-fields>
                <div>
                    <label class="form-label" for="{{ $type }}-paper">Paper size</label>
                    <select id="{{ $type }}-paper" name="paper_size" class="form-field" data-preview-paper>
                        @foreach (['letter' => 'Letter (8.5 × 11 in)', 'a4' => 'A4 (210 × 297 mm)', 'legal' => 'Legal (8.5 × 14 in)'] as $value => $paperLabel)
                            <option value="{{ $value }}" @selected($field('paper_size') === $value)>{{ $paperLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="{{ $type }}-orientation">Orientation</label>
                    <select id="{{ $type }}-orientation" name="orientation" class="form-field" data-preview-orientation>
                        <option value="landscape" @selected($field('orientation') === 'landscape')>Landscape (Horizontal)</option>
                        <option value="portrait" @selected($field('orientation') === 'portrait')>Portrait (Vertical)</option>
                    </select>
                </div>
            </div>

            {{-- Hidden default metadata --}}
            <input type="hidden" name="title" value="{{ $field('title') ?: ($selectedProgram ? $selectedProgram->name.' Certificate' : 'Certificate of Training Completion') }}">
            <input type="hidden" name="body" value="{{ $field('body') }}">
            <input type="hidden" name="signatory_name" value="{{ $field('signatory_name') }}">
            <input type="hidden" name="signatory_role" value="{{ $field('signatory_role') }}">
            <input type="hidden" name="second_signatory_name" value="{{ $field('second_signatory_name') }}">
            <input type="hidden" name="second_signatory_role" value="{{ $field('second_signatory_role') }}">

            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" id="activate-immediately" name="activate_immediately" value="1" class="rounded border-slate-300 text-purple-600 focus:ring-purple-500" checked>
                <label for="activate-immediately" class="text-xs font-semibold text-slate-800">
                    Set as active template immediately for newly generated certificates
                </label>
            </div>

            <button type="submit" class="primary-action !min-h-11 w-full text-sm font-bold">
                Save &amp; Activate Template
            </button>
        </div>

        {{-- Aside: Visual Placement Preview & Version History --}}
        <aside class="space-y-4">
            <div class="dashboard-panel">
                <p class="dashboard-section-kicker">Imported template</p>
                <h3 class="mt-1 text-base font-bold text-slate-950">{{ $heading }}</h3>
                <p class="mt-0.5 text-xs text-slate-500">This is the imported design. The trainee’s name and record are filled in when you generate the certificate.</p>

                <div class="relative mt-4 overflow-hidden rounded-lg border-2 border-slate-300 bg-slate-100 shadow-inner" 
                     @if($previewLandscape) style="aspect-ratio: 11 / 8.5" @else style="aspect-ratio: 210 / 297" @endif 
                     data-import-frame>
                    <img src="{{ $savedBackground }}" alt="Imported {{ $label }} template" class="absolute inset-0 h-full w-full object-fill pointer-events-none" data-import-image @unless($savedBackground !== '') hidden @endunless>
                    <iframe title="Imported {{ $label }} PDF" class="pointer-events-none absolute inset-0 h-full w-full bg-white" data-import-pdf @if($savedImportUrl !== '' && $backgroundIsPdf) src="{{ $savedImportUrl }}" @else hidden @endif></iframe>
                    
                    <p class="absolute inset-0 grid place-items-center px-6 text-center text-xs font-semibold text-slate-400" data-import-empty @if($savedImportUrl !== '') hidden @endif>
                        Import a finished design to preview it here.
                    </p>

                    {{-- Visual Trainee Name Overlay --}}
                    <div id="preview-trainee-name" 
                         class="pointer-events-none absolute font-bold text-slate-900 transition-all font-serif drop-shadow-sm" 
                         style="left: 5%; top: {{ $currentNameY }}%; width: 90%; font-size: 16px; text-align: {{ $currentNameAlign === 'C' ? 'center' : ($currentNameAlign === 'R' ? 'right' : 'left') }};">
                        JUAN DELA CRUZ
                    </div>
                </div>

                @if ($active?->background_path)
                    <p class="mt-3 text-xs font-semibold text-emerald-700 flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 inline-block"></span>
                        Active template for {{ $selectedProgram ? $selectedProgram->name : 'General Default' }}.
                    </p>
                @endif
            </div>

            {{-- Version History --}}
            <div class="dashboard-panel">
                <p class="dashboard-section-kicker">Version history</p>
                <div class="mt-3 space-y-3">
                    @forelse ($section['history'] as $version)
                        <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3">
                            <div>
                                <p class="text-sm font-bold text-slate-950">
                                    Version {{ $version->version }}
                                    @if($version->is_active)
                                        <span class="ml-1 rounded bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700">Active</span>
                                    @endif
                                </p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{ $version->created_at?->format('M j, Y g:i A') }} · {{ $version->createdBy?->name ?? 'Admin' }}
                                </p>
                            </div>
                            <div class="flex shrink-0 flex-col gap-1.5">
                                @unless($version->is_active)
                                    <button type="submit" form="activate-template-{{ $version->id }}" class="primary-action !min-h-7 !px-2.5 text-[11px]">Make active</button>
                                @endunless
                                <button type="submit" form="delete-template-{{ $version->id }}" class="secondary-action !min-h-7 !px-2.5 text-[11px] border-red-200 text-red-700 hover:bg-red-50">Delete</button>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500">No previous versions saved.</p>
                    @endforelse
                </div>
            </div>
        </aside>
    </form>

    @foreach ($section['history'] as $version)
        @unless($version->is_active)
            <form id="activate-template-{{ $version->id }}" method="POST" action="{{ route('admin.learning.certificate-templates.activate', $version) }}">@csrf @method('PATCH')</form>
        @endunless
        <form id="delete-template-{{ $version->id }}" method="POST" action="{{ route('admin.learning.certificate-templates.destroy', $version) }}" onsubmit="return confirm('Delete version {{ $version->version }}?');">@csrf @method('DELETE')</form>
    @endforeach
</section>

<script>
document.querySelectorAll('[data-certificate-form]').forEach((form) => {
    const frame = form.querySelector('[data-import-frame]');
    const image = form.querySelector('[data-import-image]');
    const pdf = form.querySelector('[data-import-pdf]');
    const empty = form.querySelector('[data-import-empty]');
    const paper = form.querySelector('[data-preview-paper]');
    const orientation = form.querySelector('[data-preview-orientation]');
    const fileInput = form.querySelector('[data-background-file]');
    
    // Live Trainee Name positioning elements
    const nameYSlider = document.getElementById('trainee-name-y');
    const nameYLabel = document.getElementById('label-name-y');
    const nameSizeSelect = document.getElementById('trainee-name-size');
    const nameAlignSelect = document.getElementById('trainee-name-align');
    const previewName = document.getElementById('preview-trainee-name');

    const updateNamePosition = () => {
        if (!nameYSlider || !previewName) return;
        const yVal = nameYSlider.value;
        if (nameYLabel) nameYLabel.textContent = yVal + '%';
        previewName.style.top = yVal + '%';

        if (nameSizeSelect) {
            // Scale font size proportionally for preview frame
            const pt = parseInt(nameSizeSelect.value, 10) || 24;
            previewName.style.fontSize = Math.round(pt * 0.75) + 'px';
        }

        if (nameAlignSelect) {
            const align = nameAlignSelect.value;
            previewName.style.textAlign = align === 'C' ? 'center' : (align === 'R' ? 'right' : 'left');
        }
    };

    nameYSlider?.addEventListener('input', updateNamePosition);
    nameSizeSelect?.addEventListener('change', updateNamePosition);
    nameAlignSelect?.addEventListener('change', updateNamePosition);
    updateNamePosition();

    let objectUrl = '';
    const showImport = (url, isPdf) => {
        if (objectUrl && objectUrl !== url) URL.revokeObjectURL(objectUrl);
        if (isPdf) {
            image.hidden = true;
            pdf.hidden = false;
            pdf.src = url;
        } else {
            pdf.hidden = true;
            pdf.removeAttribute('src');
            image.hidden = false;
            image.src = url;
        }
        empty.hidden = true;
    };

    fileInput?.addEventListener('change', () => {
        const chosen = fileInput.files?.[0];
        if (!chosen) return;
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = URL.createObjectURL(chosen);
        showImport(objectUrl, chosen.type === 'application/pdf');
    });

    const fitPaper = () => {
        const ratios = { letter: [11, 8.5], a4: [210, 297], legal: [8.5, 14] };
        const pair = ratios[paper.value] || ratios.a4;
        const landscape = orientation.value === 'landscape';
        frame.style.aspectRatio = landscape ? `${Math.max(...pair)} / ${Math.min(...pair)}` : `${pair[0]} / ${pair[1]}`;
    };

    paper?.addEventListener('change', fitPaper);
    orientation?.addEventListener('change', fitPaper);
});
</script>
@endsection
