@extends('admin.layouts.app', ['title' => 'Certificate templates | MCARE Admin'])

@section('content')
<section class="space-y-6">
    <header class="flex flex-col gap-3 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="dashboard-section-kicker">Training records</p>
            <h1 class="dashboard-section-title mt-2 text-3xl">Certificate templates</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                Manage dynamic certificate templates per program. Each course (e.g. Caregiving NC II) can have its own unique certificate design and layout.
            </p>
        </div>
        <a href="{{ route('admin.learning.certificates') }}" class="secondary-action">Back to Training Records</a>
    </header>

    @if (session('saved'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">{{ session('saved') }}</div>
    @endif

    {{-- Program Selector Bar --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Program Certificate Designs</span>
                <p class="mt-0.5 text-xs text-slate-600">Select a specific training program below to upload and configure its unique certificate template:</p>
            </div>
            <div class="flex flex-wrap items-center gap-1.5">
                <a href="{{ route('admin.learning.certificate-templates') }}" 
                   class="rounded-xl px-3.5 py-1.5 text-xs font-bold transition-all {{ is_null($selectedProgramId) ? 'bg-purple-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    General Default
                </a>
                @foreach ($programs as $prog)
                    <a href="{{ route('admin.learning.certificate-templates', ['program_id' => $prog->id]) }}" 
                       class="rounded-xl px-3.5 py-1.5 text-xs font-bold transition-all {{ $selectedProgramId === $prog->id ? 'bg-purple-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                        {{ $prog->name }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    @if($selectedProgram)
        <div class="rounded-xl border border-purple-200 bg-purple-50/70 p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-purple-900">Active Program: {{ $selectedProgram->name }} ({{ $selectedProgram->code }})</p>
            <p class="mt-1 text-xs text-purple-700">Any template configured and activated here will be exclusively used when generating completion documents for graduates of {{ $selectedProgram->name }}.</p>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
    @foreach ($sections as $type => $section)
        @php
            $active = $section['active'];
            $defaults = $section['defaults'];
            $label = \App\Models\CertificateTemplate::label($type);
            $heading = match ($label) {
                'COTC' => ($selectedProgram ? $selectedProgram->name.' — ' : '').'Certificate of Training Completion',
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
            $savedBackground = (! $artwork && $active && ! $backgroundIsPdf && $active->absoluteAsset($active->background_path))
                ? route('admin.learning.certificate-templates.asset', [$active, 'background'])
                : '';
            $importFields = \App\Models\CertificateTemplate::importFields($type);
            $previewLandscape = $artwork || $field('orientation') === 'landscape';
            $organization = config('official_documents.organization', []);
            $sampleValues = [
                'trainee_name' => 'Sample Graduate',
                'program' => $selectedProgram?->name ?? 'Caregiving NC II',
                'batch' => 'Batch 1 2026',
                'schedule' => 'MWF | 8:00 AM - 12:00 PM',
                'payment_status' => 'Fully paid',
                'completion_date' => now()->format('F j, Y'),
                'generation_date' => now()->format('F j, Y'),
                'certificate_no' => 'CERT-SAMPLE',
                'enrollment_id' => 'MCE-2026-SAMPLE',
                'organization' => (string) ($organization['name'] ?? 'Mission Care Training Center'),
            ];
            $previewTitle = \App\Models\CertificateTemplate::replacePlaceholders((string) $field('title'), $sampleValues);
            $previewBody = \App\Models\CertificateTemplate::replacePlaceholders((string) $field('body'), $sampleValues);
            $usesImport = $savedBackground !== '' || $backgroundIsPdf;
            $templateState = ! $active
                ? 'Default official template'
                : ($usesImport ? 'Custom Template · Version '.$active->version : 'Active Template · Version '.$active->version);
        @endphp
        <article class="dashboard-panel flex flex-col shadow-sm">
            <div class="flex items-center justify-between">
                <p class="dashboard-section-kicker">{{ $label }}</p>
                @if($selectedProgram)
                    <span class="rounded bg-purple-100 px-2 py-0.5 text-[10px] font-bold text-purple-800">{{ $selectedProgram->code }}</span>
                @else
                    <span class="rounded bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-700">Default Global</span>
                @endif
            </div>
            <h2 class="mt-1 text-xl font-bold text-slate-950">{{ $heading }}</h2>
            <p class="mt-1 text-sm text-slate-600">{{ $templateState }}</p>
            {{-- Preview Frame Container: Balanced width and height so COTC and TOR cards are 100% pantay pantay --}}
            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-100/70 p-3 sm:p-4">
                <div class="mx-auto w-full" @if($previewLandscape) style="max-width: 460px;" @else style="max-width: 265px;" @endif>
                    <div class="relative w-full overflow-hidden rounded-lg bg-white shadow-md ring-1 ring-slate-900/10" 
                         @if($previewLandscape) style="aspect-ratio: 11 / 8.5" @else style="aspect-ratio: 210 / 297" @endif>
                        @if ($savedBackground !== '')
                            <img src="{{ $savedBackground }}" alt="Active {{ $label }} template" class="h-full w-full object-contain">
                        @elseif ($backgroundIsPdf)
                            <div class="grid h-full place-items-center px-6 text-center text-sm font-semibold text-slate-600">Imported one-page PDF</div>
                        @elseif ($label === 'COTC')
                            <img src="{{ asset('assets/cotc-official-template.png') }}" alt="Default Certificate of Training Completion" class="h-full w-full object-contain">
                        @elseif ($label === 'TOR')
                            @include('admin.learning.partials.tor-default-preview', [
                                'organization' => $organization,
                                'torTitle' => $previewTitle,
                                'torTrainee' => 'NAME OF THE STUDENT',
                                'torProgram' => mb_strtoupper($selectedProgram?->name ?? 'CAREGIVING NC II'),
                                'torHours' => (int) ($organization['course_hours'] ?? 786),
                                'torRemarks' => $previewBody ?: 'For Records and Reference Purposes',
                                'torRows' => $torRows ?? null,
                                'torTrainer' => $field('second_signatory_name'),
                                'torTrainerRole' => $field('second_signatory_role') ?: 'Lead Trainer',
                                'torRegistrar' => $field('signatory_name'),
                                'torRegistrarRole' => $field('signatory_role') ?: 'Registrar',
                                'torLive' => false,
                            ])
                        @else
                            <div class="absolute inset-0 flex flex-col overflow-hidden bg-white px-4 py-3 text-center text-slate-950">
                                <img src="{{ asset('assets/official-logo.png') }}" alt="" class="mx-auto h-8 w-8 object-contain">
                                <p class="mt-1 text-[10px] font-bold leading-tight">{{ $organization['name'] ?? 'Mission Care Training Center' }}</p>
                                <p class="text-[8px] text-slate-600">{{ $organization['address'] ?? '' }}</p>
                                <p class="mt-3 font-serif text-sm font-bold leading-tight">{{ $previewTitle }}</p>
                                <p class="mt-2 line-clamp-3 text-[9px] leading-4 text-slate-700">{{ $previewBody }}</p>
                                <div class="mt-3 space-y-1 text-left text-[9px]">
                                    <p><span class="font-bold">Trainee</span> Name of the Student</p>
                                    <p><span class="font-bold">Program</span> {{ $selectedProgram?->name ?? 'Caregiving NC II' }}</p>
                                    <p><span class="font-bold">Payment status</span> Fully paid</p>
                                </div>
                                <p class="mt-auto pt-3 text-[9px] font-bold">{{ $field('signatory_name') ?: 'Registrar' }}</p>
                                <p class="text-[8px]">{{ $field('signatory_role') ?: 'Registrar' }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @php $pendingImport = $section['history']->first(fn ($version) => filled($version->background_path) && ! $version->is_active); @endphp
            <div class="mt-auto pt-4 flex flex-col gap-2">
                <a href="{{ $selectedProgramId ? route('admin.learning.certificate-templates.edit', ['type' => $type, 'program_id' => $selectedProgramId]) : route('admin.learning.certificate-templates.edit', $type) }}" class="primary-action">
                    Manage template
                </a>
                @if ($pendingImport)
                    <form method="POST" action="{{ route('admin.learning.certificate-templates.activate', $pendingImport) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="secondary-action w-full">Make active template</button>
                    </form>
                @endif
                @if ($active)
                    <form method="POST" action="{{ route('admin.learning.certificate-templates.destroy', $active) }}" onsubmit="return confirm('Delete this saved template? The default layout will be used when no saved version remains.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="secondary-action w-full border-red-200 text-red-700 hover:bg-red-50">Delete saved template</button>
                    </form>
                @endif
            </div>
        </article>
    @endforeach
    </div>
</section>
@endsection
