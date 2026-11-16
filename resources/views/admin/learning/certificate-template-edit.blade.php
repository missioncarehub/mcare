@extends('admin.layouts.app', ['title' => 'Manage '.$heading.' | MCARE Admin'])

@section('content')
@php
    $isTor = $type === \App\Models\OfficialDocument::TYPE_TOR;
    $isCotc = $type === \App\Models\OfficialDocument::TYPE_COTC;
@endphp
<section class="space-y-6">
    <header class="flex flex-col gap-3 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="dashboard-section-kicker">{{ $isTor ? 'Official Academic Records' : 'Training records' }}</p>
            <h1 class="dashboard-section-title mt-2 text-3xl">Manage {{ $heading }}</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                @if($isTor)
                    Configure the official Transcript of Records (TOR) for this program: document title, academic remarks, authorizing signatories (Registrar &amp; Lead Trainer), and paper layout.
                @else
                    Upload a certificate design (from Canva, scan, or graphic artwork). Use the sliders below to easily line up the student's name on the certificate line.
                @endif
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
        $layout = $active?->resolvedFieldLayout() ?? \App\Models\CertificateTemplate::defaultFieldLayout($type, (string) ($field('orientation') ?: ($isTor ? 'portrait' : 'landscape')));
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
        $organization = config('official_documents.organization', []);
        $courseHours = (int) ($organization['course_hours'] ?? 786);
        $programTitleDisplay = $selectedProgram ? $selectedProgram->name : 'Caregiving NC II';
    @endphp

    <form method="POST" action="{{ route('admin.learning.certificate-templates.store') }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-[minmax(0,1.25fr)_minmax(21rem,0.75fr)]" data-certificate-form>
        @csrf
        <input type="hidden" name="type" value="{{ $type }}">
        <input type="hidden" name="training_program_id" value="{{ $selectedProgramId }}">

        <div class="dashboard-panel space-y-6">
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 pb-4">
                <div>
                    <p class="dashboard-section-kicker">{{ $label }} Template Configuration</p>
                    <h2 class="mt-1 text-xl font-bold text-slate-950">{{ $heading }}</h2>
                    <p class="mt-1 text-xs text-slate-600">{{ $active ? 'Active Version '.$active->version : 'No saved template version yet for this program.' }}</p>
                </div>
                <a href="{{ route('admin.learning.certificate-templates.preview', ['type' => $type, 'program_id' => $selectedProgramId]) }}" target="_blank" rel="noopener" class="secondary-action">
                    Preview active PDF
                </a>
            </div>

            @if($isTor)
                {{-- ========================================================================= --}}
                {{-- TRANSCRIPT OF RECORDS (TOR) DEDICATED FORM CONFIGURATION                  --}}
                {{-- ========================================================================= --}}

                {{-- 1. Document Title & Program Overview --}}
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                    <div class="border-b border-slate-200 pb-2">
                        <h3 class="text-sm font-bold text-slate-900">📄 Academic Heading &amp; Course Title</h3>
                        <p class="text-xs text-slate-500">Document title printed at the top center of the Transcript of Records.</p>
                    </div>

                    <div>
                        <label for="tor-title-input" class="form-label">Document Heading / Title</label>
                        <input id="tor-title-input" name="title" type="text" value="{{ $field('title') ?: 'OFFICIAL TRANSCRIPT OF RECORDS' }}" required class="form-field font-semibold text-slate-900" placeholder="e.g. OFFICIAL TRANSCRIPT OF RECORDS">
                        @error('title')<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 pt-1">
                        <div class="rounded-lg bg-slate-50 p-3 border border-slate-200">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Program / Qualification</span>
                            <strong class="mt-1 block text-sm font-bold text-slate-900">{{ $programTitleDisplay }}</strong>
                            <span class="text-[10px] text-purple-700 font-semibold">Dynamic Program Scoped</span>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-3 border border-slate-200">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Nominal Course Duration</span>
                            <strong class="mt-1 block text-sm font-bold text-slate-900">{{ $courseHours }} Hours</strong>
                            <span class="text-[10px] text-slate-500">TESDA standard curriculum</span>
                        </div>
                    </div>
                </div>

                {{-- 2. Official Remarks & Statement --}}
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
                    <div class="border-b border-slate-200 pb-2">
                        <h3 class="text-sm font-bold text-slate-900">📝 Remarks &amp; Certification Purpose</h3>
                        <p class="text-xs text-slate-500">Official statement printed in the Remarks section on the bottom-left of the transcript.</p>
                    </div>
                    <div>
                        <label for="tor-body-input" class="form-label">Official Transcript Remarks</label>
                        <textarea id="tor-body-input" name="body" rows="3" class="form-field text-sm" placeholder="e.g. For Records and Reference Purposes">{{ $field('body') ?: 'For Records and Reference Purposes' }}</textarea>
                        <p class="mt-1 text-xs text-slate-500">Examples: "For Records and Reference Purposes", "Issued upon request for employment verification", or honors distinction.</p>
                        @error('body')<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                    </div>
                </div>

                {{-- 3. Authorizing Signatories --}}
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                    <div class="border-b border-slate-200 pb-2">
                        <h3 class="text-sm font-bold text-slate-900">✍️ Authorizing Signatories</h3>
                        <p class="text-xs text-slate-500">Official signatories shown in the approval and certification block at the bottom of the transcript.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        {{-- Certified by: Registrar --}}
                        <div class="rounded-xl border border-purple-200 bg-purple-50/40 p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-purple-900">Certified by (Registrar)</span>
                                <span class="rounded bg-purple-100 px-2 py-0.5 text-[10px] font-bold text-purple-800">Right Column</span>
                            </div>

                            <div>
                                <label for="tor-registrar-name" class="form-label text-xs">Registrar Full Name</label>
                                <input id="tor-registrar-name" name="signatory_name" type="text" value="{{ $field('signatory_name') }}" class="form-field text-sm bg-white" placeholder="e.g. Amina Dela Cruz">
                            </div>

                            <div>
                                <label for="tor-registrar-role" class="form-label text-xs">Registrar Designation / Role</label>
                                <input id="tor-registrar-role" name="signatory_role" type="text" value="{{ $field('signatory_role') ?: 'TVI Registrar' }}" class="form-field text-sm bg-white">
                            </div>

                            <div>
                                <label for="tor-registrar-sig" class="form-label text-xs">Upload Signature (Optional)</label>
                                <input id="tor-registrar-sig" name="signature" type="file" accept="image/png,image/jpeg,image/webp" class="form-field text-xs bg-white">
                                <p class="mt-1 text-[11px] text-slate-500">PNG with transparent background (up to 2 MB).</p>
                                @if ($active?->signature_path && $active->absoluteAsset($active->signature_path))
                                    <div class="mt-2 flex items-center justify-between rounded bg-white p-2 border border-purple-200">
                                        <span class="text-xs text-slate-700 font-medium">Signature uploaded</span>
                                        <label class="flex items-center gap-1.5 text-xs text-red-600 font-semibold">
                                            <input type="checkbox" name="clear_signature" value="1" class="rounded border-slate-300 text-red-600"> Clear
                                        </label>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Prepared by: Trainer --}}
                        <div class="rounded-xl border border-sky-200 bg-sky-50/40 p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-sky-900">Prepared by (Trainer)</span>
                                <span class="rounded bg-sky-100 px-2 py-0.5 text-[10px] font-bold text-sky-800">Left Column</span>
                            </div>

                            <div>
                                <label for="tor-trainer-name" class="form-label text-xs">Lead Trainer Full Name</label>
                                <input id="tor-trainer-name" name="second_signatory_name" type="text" value="{{ $field('second_signatory_name') }}" class="form-field text-sm bg-white" placeholder="e.g. Paolo Reyes">
                            </div>

                            <div>
                                <label for="tor-trainer-role" class="form-label text-xs">Trainer Designation / Role</label>
                                <input id="tor-trainer-role" name="second_signatory_role" type="text" value="{{ $field('second_signatory_role') ?: 'Lead Trainer' }}" class="form-field text-sm bg-white">
                            </div>

                            <div>
                                <label for="tor-trainer-sig" class="form-label text-xs">Upload Signature (Optional)</label>
                                <input id="tor-trainer-sig" name="second_signature" type="file" accept="image/png,image/jpeg,image/webp" class="form-field text-xs bg-white">
                                <p class="mt-1 text-[11px] text-slate-500">PNG with transparent background (up to 2 MB).</p>
                                @if ($active?->second_signature_path && $active->absoluteAsset($active->second_signature_path))
                                    <div class="mt-2 flex items-center justify-between rounded bg-white p-2 border border-sky-200">
                                        <span class="text-xs text-slate-700 font-medium">Signature uploaded</span>
                                        <label class="flex items-center gap-1.5 text-xs text-red-600 font-semibold">
                                            <input type="checkbox" name="clear_second_signature" value="1" class="rounded border-slate-300 text-red-600"> Clear
                                        </label>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 4. Paper Specifications & Optional Stationery Background --}}
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                    <div class="border-b border-slate-200 pb-2">
                        <h3 class="text-sm font-bold text-slate-900">📐 Paper &amp; Layout Settings</h3>
                        <p class="text-xs text-slate-500">Official paper size and optional custom stationery background.</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2" data-paper-fields>
                        <div>
                            <label class="form-label" for="{{ $type }}-paper">Paper size</label>
                            <select id="{{ $type }}-paper" name="paper_size" class="form-field" data-preview-paper>
                                @foreach (['a4' => 'A4 (210 × 297 mm) — Standard for TOR', 'letter' => 'Letter (8.5 × 11 in)', 'legal' => 'Legal (8.5 × 14 in)'] as $value => $paperLabel)
                                    <option value="{{ $value }}" @selected($field('paper_size') === $value)>{{ $paperLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="{{ $type }}-orientation">Orientation</label>
                            <select id="{{ $type }}-orientation" name="orientation" class="form-field" data-preview-orientation>
                                <option value="portrait" @selected($field('orientation') === 'portrait')>Portrait (Vertical) — Standard</option>
                                <option value="landscape" @selected($field('orientation') === 'landscape')>Landscape (Horizontal)</option>
                            </select>
                        </div>
                    </div>

                    {{-- Optional Letterhead / Pre-printed Background --}}
                    <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4">
                        <label class="form-label font-bold text-slate-800" for="{{ $type }}-background">Import a pre-made template</label>
                        <input id="{{ $type }}-background" name="background" type="file" accept="image/png,image/jpeg,image/webp,application/pdf" class="form-field bg-white" data-background-file>
                        <p class="mt-1 text-xs text-slate-500">Optional. Leave empty to use MCARE's official TESDA-compliant structured table layout. Upload a file only if your institution prints onto pre-printed stationery or letterhead paper.</p>
                        @if ($active?->absoluteAsset($active->background_path))
                            <div class="mt-3 flex items-center justify-between rounded bg-white p-2.5 border border-slate-200">
                                <span class="text-xs font-semibold text-slate-800">Current file: {{ basename($active->background_path) }}</span>
                                <label class="flex items-center gap-1.5 text-xs font-medium text-red-600">
                                    <input type="checkbox" name="clear_background" value="1" class="rounded border-slate-300 text-red-600">
                                    Remove custom background
                                </label>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- 5. Included Competency Units Matrix Preview --}}
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">📚 Included Competency Units (TESDA Matrix)</h3>
                            <p class="text-xs text-slate-500">These units of competency are automatically populated on the student's TOR.</p>
                        </div>
                        <span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-bold text-emerald-700">Official Modules</span>
                    </div>

                    <div class="max-h-48 overflow-y-auto rounded-lg border border-slate-100">
                        <table class="min-w-full divide-y divide-slate-100 text-xs">
                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                                <tr>
                                    <th class="px-3 py-2 text-left">Course Code</th>
                                    <th class="px-3 py-2 text-left">Course Title / Competency</th>
                                    <th class="px-3 py-2 text-center">Grade</th>
                                    <th class="px-3 py-2 text-center">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @forelse($competencyUnits as $unit)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-3 py-2 font-mono font-bold text-slate-900">{{ $unit->code }}</td>
                                        <td class="px-3 py-2">{{ $unit->title }}</td>
                                        <td class="px-3 py-2 text-center font-semibold text-slate-800">Numeric</td>
                                        <td class="px-3 py-2 text-center font-bold text-emerald-700">COMPETENT</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="px-3 py-2 font-mono font-bold">HCS323301</td>
                                        <td class="px-3 py-2">Provide care and support to infants and toddlers</td>
                                        <td class="px-3 py-2 text-center">1.25</td>
                                        <td class="px-3 py-2 text-center text-emerald-700 font-bold">COMPETENT</td>
                                    </tr>
                                    <tr>
                                        <td class="px-3 py-2 font-mono font-bold">HCS323302</td>
                                        <td class="px-3 py-2">Provide care and support to children</td>
                                        <td class="px-3 py-2 text-center">1.25</td>
                                        <td class="px-3 py-2 text-center text-emerald-700 font-bold">COMPETENT</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            @else
                {{-- ========================================================================= --}}
                {{-- CERTIFICATE OF TRAINING COMPLETION (COTC) CANVAS / ARTWORK CONFIGURATION   --}}
                {{-- ========================================================================= --}}

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

                {{-- Hidden default metadata for COTC --}}
                <input type="hidden" name="title" value="{{ $field('title') ?: ($selectedProgram ? $selectedProgram->name.' Certificate' : 'Certificate of Training Completion') }}">
                <input type="hidden" name="body" value="{{ $field('body') }}">
                <input type="hidden" name="signatory_name" value="{{ $field('signatory_name') }}">
                <input type="hidden" name="signatory_role" value="{{ $field('signatory_role') }}">
                <input type="hidden" name="second_signatory_name" value="{{ $field('second_signatory_name') }}">
                <input type="hidden" name="second_signatory_role" value="{{ $field('second_signatory_role') }}">
            @endif

            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" id="activate-immediately" name="activate_immediately" value="1" class="rounded border-slate-300 text-purple-600 focus:ring-purple-500" checked>
                <label for="activate-immediately" class="text-xs font-semibold text-slate-800">
                    Set as active template immediately for newly generated {{ $isTor ? 'transcripts' : 'certificates' }}
                </label>
            </div>

            <button type="submit" class="primary-action !min-h-11 w-full text-sm font-bold">
                Save &amp; Activate {{ $isTor ? 'Transcript Template' : 'Template' }}
            </button>
        </div>

        {{-- Aside: Visual Preview & Version History --}}
        <aside class="space-y-5">
            <div class="dashboard-panel">
                <p class="dashboard-section-kicker">{{ $isTor ? 'Transcript of Records Preview' : 'Imported template' }}</p>
                <h3 class="mt-1 text-base font-bold text-slate-950">{{ $heading }}</h3>
                <p class="mt-0.5 text-xs text-slate-500">
                    @if($isTor)
                        Interactive preview showing how academic titles, grades, and signatories will be printed on the official transcript.
                    @else
                        This is the imported design. The trainee’s name and record are filled in when you generate the certificate.
                    @endif
                </p>

                @if($isTor)
                    {{-- TOR Interactive Document Miniature Preview --}}
                    <div class="relative mt-4 overflow-hidden rounded-lg border-2 border-slate-300 bg-white p-3 shadow-sm text-[10px]" style="aspect-ratio: 210 / 297;" data-import-frame>
                        {{-- Header --}}
                        <div class="flex items-center justify-between border-b border-purple-200 pb-2">
                            <div class="flex items-center gap-2">
                                <div class="h-6 w-6 rounded-full bg-purple-600 flex items-center justify-center text-white text-[9px] font-bold">MC</div>
                                <div>
                                    <strong class="block text-[11px] font-bold text-slate-900 leading-tight">Mission Care Training Center</strong>
                                    <span class="text-[9px] text-slate-500">Pili, Camarines Sur · Official Record</span>
                                </div>
                            </div>
                            <span class="rounded bg-purple-100 px-1.5 py-0.5 text-[9px] font-bold text-purple-800">REGISTRAR</span>
                        </div>

                        {{-- Title banner --}}
                        <div class="my-2.5 text-center">
                            <strong id="tor-preview-title" class="block font-bold text-[11px] uppercase tracking-wide text-slate-950">
                                {{ $field('title') ?: 'OFFICIAL TRANSCRIPT OF RECORDS' }}
                            </strong>
                            <span class="mt-0.5 block font-serif text-[12px] font-bold text-purple-900">"MARIA REYES SANTOS"</span>
                            <span class="text-[10px] font-bold text-slate-700 uppercase">{{ $programTitleDisplay }}</span>
                            <span class="block text-[9px] italic text-slate-500">Course ({{ $courseHours }} Hours)</span>
                        </div>

                        {{-- 2-column grid --}}
                        <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)] gap-1.5 rounded border border-slate-300 text-[9px]">
                            {{-- Left Column --}}
                            <div class="bg-indigo-50/60 p-2 space-y-1.5 border-r border-slate-300">
                                <div>
                                    <span class="font-bold text-slate-700 block">Entrance Date:</span>
                                    <span class="text-slate-600">Jan 15, 2026</span>
                                </div>
                                <div>
                                    <span class="font-bold text-slate-700 block">Address:</span>
                                    <span class="text-slate-600">Pili, Camarines Sur</span>
                                </div>
                                <div>
                                    <span class="font-bold text-slate-700 block">Remarks:</span>
                                    <p id="tor-preview-body" class="italic text-slate-800 leading-tight">
                                        {{ $field('body') ?: 'For Records and Reference Purposes' }}
                                    </p>
                                </div>
                            </div>

                            {{-- Right Column (Competency Table) --}}
                            <div class="bg-pink-50/40 p-1.5">
                                <div class="font-bold text-slate-800 border-b border-pink-200 pb-0.5 mb-1 flex justify-between text-[8px] uppercase">
                                    <span>Course Code &amp; Title</span>
                                    <span>Grade / Remark</span>
                                </div>
                                <div class="space-y-1 text-[8.5px] leading-tight">
                                    <div class="flex justify-between items-start">
                                        <span class="font-semibold text-slate-800">HCS323301 Infant Care</span>
                                        <span class="font-bold text-emerald-700">1.25 COMP</span>
                                    </div>
                                    <div class="flex justify-between items-start">
                                        <span class="font-semibold text-slate-800">HCS323302 Child Care</span>
                                        <span class="font-bold text-emerald-700">1.25 COMP</span>
                                    </div>
                                    <div class="flex justify-between items-start">
                                        <span class="font-semibold text-slate-800">HCS323303 Elderly Care</span>
                                        <span class="font-bold text-emerald-700">1.25 COMP</span>
                                    </div>
                                    <div class="pt-1 text-center font-mono text-[8px] text-slate-400">
                                        * * NOTHING FOLLOWS * *
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Signatures Footer --}}
                        <div class="mt-2.5 grid grid-cols-3 gap-1 rounded bg-slate-50 p-1.5 text-center text-[8.5px] border border-slate-200">
                            <div>
                                <span class="text-[8px] text-slate-500 block">Prepared by:</span>
                                <strong id="tor-preview-trainer" class="block text-slate-900 border-b border-slate-400 pb-0.5 mt-0.5 font-bold">
                                    {{ $field('second_signatory_name') ?: 'Lead Trainer' }}
                                </strong>
                                <span id="tor-preview-trainer-role" class="text-[7.5px] text-slate-500 block">
                                    {{ $field('second_signatory_role') ?: 'Lead Trainer' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[8px] text-slate-500 block">Certified by:</span>
                                <strong id="tor-preview-registrar" class="block text-slate-900 border-b border-slate-400 pb-0.5 mt-0.5 font-bold">
                                    {{ $field('signatory_name') ?: 'TVI Registrar' }}
                                </strong>
                                <span id="tor-preview-registrar-role" class="text-[7.5px] text-slate-500 block">
                                    {{ $field('signatory_role') ?: 'Registrar' }}
                                </span>
                            </div>
                            <div class="flex flex-col items-center justify-center">
                                <span class="text-[8px] font-bold uppercase text-purple-900">Official Seal</span>
                                <span class="text-[7px] text-slate-400">Valid with seal</span>
                            </div>
                        </div>

                        {{-- Hidden image/pdf containers for optional letterhead --}}
                        <img src="{{ $savedBackground }}" alt="Imported {{ $label }} template" class="hidden" data-import-image>
                        <iframe title="Imported {{ $label }} PDF" class="hidden" data-import-pdf></iframe>
                        <p class="hidden" data-import-empty>Import a finished design to preview it here.</p>
                    </div>

                @else
                    {{-- COTC Certificate Preview Frame --}}
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
                @endif

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
                        <p class="text-xs text-slate-500">No saved versions yet for this program.</p>
                    @endforelse
                </div>
            </div>
        </aside>
    </form>

    {{-- Forms for activation and deletion --}}
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
    
    // Live Trainee Name positioning elements (COTC)
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

    // Live TOR interactive preview bindings
    const torTitleInput = document.getElementById('tor-title-input');
    const torPreviewTitle = document.getElementById('tor-preview-title');
    torTitleInput?.addEventListener('input', () => {
        if (torPreviewTitle) {
            torPreviewTitle.textContent = torTitleInput.value.trim() || 'OFFICIAL TRANSCRIPT OF RECORDS';
        }
    });

    const torBodyInput = document.getElementById('tor-body-input');
    const torPreviewBody = document.getElementById('tor-preview-body');
    torBodyInput?.addEventListener('input', () => {
        if (torPreviewBody) {
            torPreviewBody.textContent = torBodyInput.value.trim() || 'For Records and Reference Purposes';
        }
    });

    const torTrainerInput = document.getElementById('tor-trainer-name');
    const torPreviewTrainer = document.getElementById('tor-preview-trainer');
    torTrainerInput?.addEventListener('input', () => {
        if (torPreviewTrainer) {
            torPreviewTrainer.textContent = torTrainerInput.value.trim() || 'Lead Trainer';
        }
    });

    const torTrainerRoleInput = document.getElementById('tor-trainer-role');
    const torPreviewTrainerRole = document.getElementById('tor-preview-trainer-role');
    torTrainerRoleInput?.addEventListener('input', () => {
        if (torPreviewTrainerRole) {
            torPreviewTrainerRole.textContent = torTrainerRoleInput.value.trim() || 'Lead Trainer';
        }
    });

    const torRegistrarInput = document.getElementById('tor-registrar-name');
    const torPreviewRegistrar = document.getElementById('tor-preview-registrar');
    torRegistrarInput?.addEventListener('input', () => {
        if (torPreviewRegistrar) {
            torPreviewRegistrar.textContent = torRegistrarInput.value.trim() || 'TVI Registrar';
        }
    });

    const torRegistrarRoleInput = document.getElementById('tor-registrar-role');
    const torPreviewRegistrarRole = document.getElementById('tor-preview-registrar-role');
    torRegistrarRoleInput?.addEventListener('input', () => {
        if (torPreviewRegistrarRole) {
            torPreviewRegistrarRole.textContent = torRegistrarRoleInput.value.trim() || 'Registrar';
        }
    });

    let objectUrl = '';
    const showImport = (url, isPdf) => {
        if (objectUrl && objectUrl !== url) URL.revokeObjectURL(objectUrl);
        if (image && pdf && empty) {
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
        }
    };

    fileInput?.addEventListener('change', () => {
        const chosen = fileInput.files?.[0];
        if (!chosen) return;
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = URL.createObjectURL(chosen);
        showImport(objectUrl, chosen.type === 'application/pdf');
    });

    const fitPaper = () => {
        if (!frame || !paper || !orientation) return;
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
