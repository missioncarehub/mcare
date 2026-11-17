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
                    <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">📄 Academic Heading &amp; Course Title</h3>
                            <p class="text-xs text-slate-500">Document title printed at the top center of the Transcript of Records.</p>
                        </div>
                        <span class="rounded bg-purple-100 px-2 py-0.5 text-[10px] font-bold text-purple-800">Field Map: ① &amp; ③</span>
                    </div>

                    <div>
                        <label for="tor-title-input" class="form-label font-bold">Document Heading / Title</label>
                        <input id="tor-title-input" name="title" type="text" value="{{ $field('title') ?: 'OFFICIAL TRANSCRIPT OF RECORDS' }}" required class="form-field font-semibold text-slate-900" placeholder="e.g. OFFICIAL TRANSCRIPT OF RECORDS">
                        <p class="mt-1 text-xs text-slate-500">Heading printed in bold capital letters at the top center of the official transcript.</p>
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
                    <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">📝 Remarks &amp; Certification Purpose</h3>
                            <p class="text-xs text-slate-500">Official statement printed in the Remarks section on the bottom-left of the transcript.</p>
                        </div>
                        <span class="rounded bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800">Field Map: ⑤</span>
                    </div>
                    <div>
                        <label for="tor-body-input" class="form-label font-bold">Official Transcript Remarks</label>
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
                                <span class="rounded bg-purple-100 px-2 py-0.5 text-[10px] font-bold text-purple-800">Field Map: ⑧ Certified by</span>
                            </div>

                            <div>
                                <label for="tor-registrar-name" class="form-label text-xs font-bold">Registrar Full Name</label>
                                <input id="tor-registrar-name" name="signatory_name" type="text" value="{{ $field('signatory_name') }}" class="form-field text-sm bg-white" placeholder="e.g. Amina Dela Cruz">
                            </div>

                            <div>
                                <label for="tor-registrar-role" class="form-label text-xs font-bold">Registrar Designation / Role</label>
                                <input id="tor-registrar-role" name="signatory_role" type="text" value="{{ $field('signatory_role') ?: 'TVI Registrar' }}" class="form-field text-sm bg-white">
                            </div>

                            <div>
                                <label for="tor-registrar-sig" class="form-label text-xs font-bold">Upload Signature (Optional)</label>
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
                                <span class="rounded bg-sky-100 px-2 py-0.5 text-[10px] font-bold text-sky-800">Field Map: ⑦ Prepared by</span>
                            </div>

                            <div>
                                <label for="tor-trainer-name" class="form-label text-xs font-bold">Lead Trainer Full Name</label>
                                <input id="tor-trainer-name" name="second_signatory_name" type="text" value="{{ $field('second_signatory_name') }}" class="form-field text-sm bg-white" placeholder="e.g. Paolo Reyes">
                            </div>

                            <div>
                                <label for="tor-trainer-role" class="form-label text-xs font-bold">Trainer Designation / Role</label>
                                <input id="tor-trainer-role" name="second_signatory_role" type="text" value="{{ $field('second_signatory_role') ?: 'Lead Trainer' }}" class="form-field text-sm bg-white">
                            </div>

                            <div>
                                <label for="tor-trainer-sig" class="form-label text-xs font-bold">Upload Signature (Optional)</label>
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
                            <label class="form-label font-bold" for="{{ $type }}-paper">Paper size</label>
                            <select id="{{ $type }}-paper" name="paper_size" class="form-field" data-preview-paper>
                                @foreach (['a4' => 'A4 (210 × 297 mm) — Standard for TOR', 'letter' => 'Letter (8.5 × 11 in)', 'legal' => 'Legal (8.5 × 14 in)'] as $value => $paperLabel)
                                    <option value="{{ $value }}" @selected($field('paper_size') === $value)>{{ $paperLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label font-bold" for="{{ $type }}-orientation">Orientation</label>
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
                            <p class="text-xs text-slate-500">Units of competency and performance ratings for <strong>{{ $programTitleDisplay }}</strong>.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-bold text-emerald-700">{{ count($torRows) }} Units</span>
                            <span class="rounded bg-purple-100 px-2 py-0.5 text-[10px] font-bold text-purple-800">Field Map: ⑥ Competency Matrix</span>
                        </div>
                    </div>

                    <div class="max-h-56 overflow-y-auto rounded-lg border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-100 text-xs">
                            <thead class="bg-slate-50 text-slate-600 font-bold uppercase text-[10px]">
                                <tr>
                                    <th class="px-3 py-2 text-left">Course Code</th>
                                    <th class="px-3 py-2 text-left">Course Title / Unit of Competency</th>
                                    <th class="px-3 py-2 text-center">Grade</th>
                                    <th class="px-3 py-2 text-center">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @foreach($torRows as $row)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-3 py-2 font-mono font-bold text-slate-900">{{ $row[0] }}</td>
                                        <td class="px-3 py-2 font-medium">{{ $row[1] }}</td>
                                        <td class="px-3 py-2 text-center font-bold text-slate-900">{{ $row[2] }}</td>
                                        <td class="px-3 py-2 text-center font-bold text-emerald-700">{{ $row[3] ?? 'COMPETENT' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="text-[11px] text-slate-500 italic">💡 The final grades and competency ratings are dynamically loaded from each student's assessed coursework outcomes upon graduation.</p>
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
                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                    <div>
                        <p class="dashboard-section-kicker">{{ $isTor ? 'Official Academic Preview' : 'Imported template' }}</p>
                        <h3 class="mt-0.5 text-base font-bold text-slate-950">{{ $heading }}</h3>
                    </div>
                    @if($isTor)
                        <button type="button" id="toggle-field-map-btn" class="secondary-action !min-h-8 !px-2.5 text-xs font-bold flex items-center gap-1.5 text-purple-700 border-purple-300 hover:bg-purple-50">
                            <x-dashboard-icon name="tag" class="h-3.5 w-3.5 text-purple-600" />
                            <span id="toggle-field-map-label">Show Field Map</span>
                        </button>
                    @endif
                </div>

                <p class="mt-2 text-xs text-slate-500">
                    @if($isTor)
                        Live, high-fidelity preview of the official TESDA transcript. Click "Show Field Map" to see where each field on the left is positioned.
                    @else
                        This is the imported design. The trainee’s name and record are filled in when you generate the certificate.
                    @endif
                </p>

                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-100/70 p-3 sm:p-4">
                    @if($isTor)
                        {{-- High-Fidelity Official TOR Preview Canvas (Proportionately Sized & Centered) --}}
                        <div class="mx-auto w-full" style="max-width: 420px;">
                            <div id="tor-preview-wrapper" class="relative w-full overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-slate-900/10 transition-all" style="aspect-ratio: 210 / 297;" data-import-frame>
                                @include('admin.learning.partials.tor-default-preview', [
                                    'organization' => $organization,
                                    'torTitle' => $field('title') ?: 'OFFICIAL TRANSCRIPT OF RECORDS',
                                    'torTrainee' => 'MARIA REYES SANTOS',
                                    'torProgram' => mb_strtoupper($programTitleDisplay),
                                    'torHours' => $courseHours,
                                    'torRemarks' => $field('body') ?: 'For Records and Reference Purposes',
                                    'torRows' => $torRows,
                                    'torTrainer' => $field('second_signatory_name') ?: 'Lead Trainer',
                                    'torTrainerRole' => $field('second_signatory_role') ?: 'Lead Trainer',
                                    'torRegistrar' => $field('signatory_name') ?: 'TVI Registrar',
                                    'torRegistrarRole' => $field('signatory_role') ?: 'Registrar',
                                    'torLive' => true,
                                    'savedBackground' => $savedBackground,
                                    'backgroundIsPdf' => $backgroundIsPdf,
                                ])

                                <img src="{{ $savedBackground }}" alt="Imported {{ $label }} template" class="absolute inset-0 h-full w-full object-fill pointer-events-none" data-import-image @unless($savedBackground !== '') hidden @endunless>
                                <iframe title="Imported {{ $label }} PDF" class="pointer-events-none absolute inset-0 h-full w-full bg-white" data-import-pdf @if($savedImportUrl !== '' && $backgroundIsPdf) src="{{ $savedImportUrl }}" @else hidden @endif></iframe>
                                <p class="hidden" data-import-empty>Import a finished design to preview it here.</p>
                            </div>
                        </div>

                    @else
                        {{-- COTC Certificate Preview Frame (Proportionately Sized & Centered) --}}
                        <div class="mx-auto w-full" style="max-width: 540px;">
                            <div class="relative w-full overflow-hidden rounded-lg border-2 border-slate-300 bg-slate-100 shadow-inner" 
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
                        </div>
                    @endif
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

    // Toggle Field Map Labels Button
    const toggleFieldMapBtn = document.getElementById('toggle-field-map-btn');
    const toggleFieldMapLabel = document.getElementById('toggle-field-map-label');
    const torPreviewWrapper = document.getElementById('tor-preview-wrapper');

    toggleFieldMapBtn?.addEventListener('click', () => {
        if (!torPreviewWrapper) return;
        const isShown = torPreviewWrapper.classList.toggle('show-field-guides');
        if (toggleFieldMapLabel) {
            toggleFieldMapLabel.textContent = isShown ? 'Hide Field Map' : 'Show Field Map';
        }
        toggleFieldMapBtn.classList.toggle('bg-purple-100', isShown);
    });

    // Clicking a badge jumps to and highlights the target form field
    document.querySelectorAll('.tor-field-guide-badge').forEach(badge => {
        badge.addEventListener('click', (e) => {
            e.stopPropagation();
            const targetId = badge.getAttribute('data-guide-target');
            if (!targetId) return;
            const targetEl = document.getElementById(targetId);
            if (targetEl) {
                targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                targetEl.focus();
                targetEl.classList.add('ring-4', 'ring-purple-400');
                setTimeout(() => targetEl.classList.remove('ring-4', 'ring-purple-400'), 1500);
            }
        });
    });

    // Real-time bindings for inputs updating the live preview
    const torTitleInput = document.getElementById('tor-title-input');
    const torPreviewTitle = document.querySelector('[data-tor-title]');
    torTitleInput?.addEventListener('input', () => {
        if (torPreviewTitle) {
            torPreviewTitle.textContent = torTitleInput.value.trim() || 'OFFICIAL TRANSCRIPT OF RECORDS';
        }
    });

    const torBodyInput = document.getElementById('tor-body-input');
    const torPreviewRemarks = document.querySelector('[data-tor-remarks]');
    torBodyInput?.addEventListener('input', () => {
        if (torPreviewRemarks) {
            torPreviewRemarks.textContent = torBodyInput.value.trim() || 'For Records and Reference Purposes';
        }
    });

    const torTrainerInput = document.getElementById('tor-trainer-name');
    const torPreviewTrainer = document.querySelector('[data-tor-trainer]');
    torTrainerInput?.addEventListener('input', () => {
        if (torPreviewTrainer) {
            torPreviewTrainer.textContent = torTrainerInput.value.trim() || 'Lead Trainer';
        }
    });

    const torTrainerRoleInput = document.getElementById('tor-trainer-role');
    const torPreviewTrainerRole = document.querySelector('[data-tor-trainer-role]');
    torTrainerRoleInput?.addEventListener('input', () => {
        if (torPreviewTrainerRole) {
            torPreviewTrainerRole.textContent = torTrainerRoleInput.value.trim() || 'Lead Trainer';
        }
    });

    const torRegistrarInput = document.getElementById('tor-registrar-name');
    const torPreviewRegistrar = document.querySelector('[data-tor-registrar]');
    torRegistrarInput?.addEventListener('input', () => {
        if (torPreviewRegistrar) {
            torPreviewRegistrar.textContent = torRegistrarInput.value.trim() || 'TVI Registrar';
        }
    });

    const torRegistrarRoleInput = document.getElementById('tor-registrar-role');
    const torPreviewRegistrarRole = document.querySelector('[data-tor-registrar-role]');
    torRegistrarRoleInput?.addEventListener('input', () => {
        if (torPreviewRegistrarRole) {
            torPreviewRegistrarRole.textContent = torRegistrarRoleInput.value.trim() || 'Registrar';
        }
    });

    let objectUrl = '';
    const officialTor = form.querySelector('[data-official-tor]');
    const showImport = (url, isPdf) => {
        if (objectUrl && objectUrl !== url) URL.revokeObjectURL(objectUrl);
        if (officialTor) officialTor.hidden = true;
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
