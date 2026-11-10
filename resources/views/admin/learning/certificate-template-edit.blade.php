@extends('admin.layouts.app', ['title' => 'Manage '.$heading.' | MCARE Admin'])

@section('content')
<section class="space-y-6">
    <header class="flex flex-col gap-3 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="dashboard-section-kicker">Training records</p>
            <h1 class="dashboard-section-title mt-2 text-3xl">Manage {{ $heading }}</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Import a finished design. When you generate a certificate, the trainee’s name and record are filled in automatically.</p>
        </div>
        <a href="{{ route('admin.learning.certificate-templates') }}" class="secondary-action">Back to Certificate templates</a>
    </header>

    @if (session('saved'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">{{ session('saved') }}</div>
    @endif

                @php
            $active = $section['active'];
            $defaults = $section['defaults'];
            $label = \App\Models\CertificateTemplate::label($type);
            $heading = match ($label) {
                'COTC' => 'Certificate of Training Completion',
                'COR' => 'Certificate of Registration',
                default => 'Transcript of Records',
            };
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
            $layout = $active?->resolvedFieldLayout() ?? \App\Models\CertificateTemplate::defaultFieldLayout($type, (string) ($field('orientation') ?: 'portrait'));
            if ($usingOld && is_string(old('field_layout'))) {
                $postedLayout = json_decode(old('field_layout'), true);
                if (is_array($postedLayout)) {
                    $layout = array_replace($layout, $postedLayout);
                }
            }
            $backgroundIsPdf = is_string($active?->background_path) && str_ends_with(strtolower($active->background_path), '.pdf');
            $savedImportUrl = (! $artwork && $active && $active->absoluteAsset($active->background_path))
                ? route('admin.learning.certificate-templates.asset', [$active, 'background'])
                : '';
            $savedBackground = $backgroundIsPdf ? '' : $savedImportUrl;
            $importFields = \App\Models\CertificateTemplate::importFields($type);
            $previewLandscape = $artwork || $field('orientation') === 'landscape';
            $organization = config('official_documents.organization', []);
            $sampleValues = [
                'trainee_name' => 'Sample Graduate',
                'program' => 'Caregiving NC II',
                'batch' => 'Batch 1 2026',
                'schedule' => 'MWF | 8:00 AM - 12:00 PM',
                'payment_status' => 'Fully paid',
                'completion_date' => now()->format('F j, Y'),
                'generation_date' => now()->format('F j, Y'),
                'certificate_no' => 'COR-SAMPLE',
                'enrollment_id' => 'MCE-2026-SAMPLE',
                'organization' => (string) ($organization['name'] ?? 'Mission Care Training Center'),
            ];
            $previewTitle = \App\Models\CertificateTemplate::replacePlaceholders((string) $field('title'), $sampleValues);
            $previewBody = \App\Models\CertificateTemplate::replacePlaceholders((string) $field('body'), $sampleValues);
            $usesImport = $savedBackground !== '' || $backgroundIsPdf;
            $templateState = ! $active
                ? 'Default official template'
                : ($usesImport ? 'Imported template · Version '.$active->version : 'Active template · Version '.$active->version);
        @endphp
        <form method="POST" action="{{ route('admin.learning.certificate-templates.store') }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(18rem,0.8fr)]" data-certificate-form>
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="dashboard-panel space-y-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="dashboard-section-kicker">{{ $label }}</p>
                        <h2 class="mt-1 text-xl font-bold text-slate-950">{{ $heading }}</h2>
                        <p class="mt-1 text-sm text-slate-600">{{ $active ? 'Active version '.$active->version.'. Saving creates the next version and makes it active.' : 'No saved version yet. Generate still uses the current official layout until you save.' }}</p>
                    </div>
                    <a href="{{ route('admin.learning.certificate-templates.preview', $type) }}" target="_blank" rel="noopener" class="secondary-action">Preview active PDF</a>
                </div>

                <div class="rounded-xl border border-dashed border-purple-300 bg-purple-50/60 p-4">
                    <label class="form-label" for="{{ $type }}-background">Import a pre-made template</label>
                    <input id="{{ $type }}-background" name="background" type="file" accept="image/png,image/jpeg,image/webp,application/pdf" class="form-field bg-white" data-background-file @unless($active?->absoluteAsset($active->background_path)) required @endunless>
                    <p class="mt-1 text-xs leading-5 text-slate-600">PNG, JPG, WEBP, or a one-page PDF, up to 2 MB. Leave this empty to keep the current import.</p>
                    @if ($active?->absoluteAsset($active->background_path))
                        <p class="mt-2 text-xs font-bold text-slate-800">Current import: {{ basename($active->background_path) }}</p>
                        @if ($savedBackground !== '')
                            <img src="{{ $savedBackground }}" alt="Imported {{ $label }} template" class="mt-2 max-h-28 rounded border border-slate-200 bg-white object-contain">
                        @endif
                        <label class="mt-3 flex items-center gap-2 text-xs font-semibold text-slate-700">
                            <input type="checkbox" name="clear_background" value="1" class="rounded border-slate-300 text-purple-600">
                            Remove the imported template
                        </label>
                    @endif
                    @error('background') @if($usingOld)<span class="mt-1 block text-sm font-semibold text-red-600">{{ $message }}</span>@endif @enderror
                </div>
                <input type="hidden" name="title" value="{{ $field('title') }}" data-preview-title-input>
                <input type="hidden" name="body" value="{{ $field('body') }}" data-preview-body-input>
                <input type="hidden" name="signatory_name" value="{{ $field('signatory_name') }}" data-preview-signatory>
                <input type="hidden" name="signatory_role" value="{{ $field('signatory_role') }}" data-preview-role>
                <input type="hidden" name="second_signatory_name" value="{{ $field('second_signatory_name') }}" data-preview-second-signatory>
                <input type="hidden" name="second_signatory_role" value="{{ $field('second_signatory_role') }}" data-preview-second-role>

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

                <button type="submit" class="primary-action">Save imported template</button>
            </div>

            <aside class="space-y-4">
                <div class="dashboard-panel">
                    <p class="dashboard-section-kicker">Imported template</p>
                    <h2 class="mt-1 text-lg font-bold text-slate-950">{{ $heading }}</h2>
                    <p class="mt-1 text-xs leading-5 text-slate-500">This is the imported design. The trainee’s name and record are filled in when you generate the certificate.</p>
                    <div class="relative mt-4 overflow-hidden rounded-lg border border-slate-200 bg-slate-50" @if($previewLandscape) style="aspect-ratio: 11 / 8.5" @else style="aspect-ratio: 210 / 297" @endif data-import-frame>
                        <img src="{{ $savedBackground }}" alt="Imported {{ $label }} template" class="absolute inset-0 h-full w-full object-fill" data-import-image @unless($savedBackground !== '') hidden @endunless>
                        <iframe title="Imported {{ $label }} PDF" class="pointer-events-none absolute inset-0 h-full w-full bg-white" data-import-pdf @if($savedImportUrl !== '' && $backgroundIsPdf) src="{{ $savedImportUrl }}" @else hidden @endif></iframe>
                        <p class="absolute inset-0 grid place-items-center px-6 text-center text-sm font-semibold text-slate-500" data-import-empty @if($savedImportUrl !== '') hidden @endif>Import a finished design to preview it here.</p>
                    </div>
                    @php $latestImport = $section['history']->first(fn ($version) => filled($version->background_path) && ! $version->is_active); @endphp
                    @if ($latestImport)
                        <form method="POST" action="{{ route('admin.learning.certificate-templates.activate', $latestImport) }}" class="mt-4">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="primary-action w-full">Make active template</button>
                        </form>
                    @elseif ($active?->background_path)
                        <p class="mt-3 text-sm font-semibold text-emerald-700">This imported design is the active template.</p>
                    @endif
                </div>

                <div class="dashboard-panel">
                    <p class="dashboard-section-kicker">Version history</p>
                    <div class="mt-3 space-y-3">
                        @forelse ($section['history'] as $version)
                            <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3">
                                <div>
                                    <p class="text-sm font-bold text-slate-950">Version {{ $version->version }} @if($version->is_active)<span class="ml-1 rounded bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700">Active</span>@endif</p>
                                    <p class="mt-1 text-xs text-slate-600">{{ $version->title }}</p>
                                    @if ($version->background_path)
                                        <p class="mt-1 text-xs font-bold text-purple-700">Imported template</p>
                                    @endif
                                    <p class="mt-1 text-xs text-slate-500">{{ $version->created_at?->format('M j, Y g:i A') }} · {{ $version->createdBy?->name ?? 'Admin' }}</p>
                                </div>
                                <div class="flex shrink-0 flex-col gap-2">
                                    @unless($version->is_active)
                                        <button type="submit" form="activate-template-{{ $version->id }}" class="primary-action">Make active template</button>
                                    @endunless
                                    <button type="submit" form="delete-template-{{ $version->id }}" class="secondary-action border-red-200 text-red-700 hover:bg-red-50">Delete</button>
                                </div>
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
            <form id="delete-template-{{ $version->id }}" method="POST" action="{{ route('admin.learning.certificate-templates.destroy', $version) }}" onsubmit="return confirm('Delete version {{ $version->version }}? The official layout is used again when no saved version remains.');">@csrf @method('DELETE')</form>
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
