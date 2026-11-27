@extends('admin.layouts.app')

@section('title', 'Document Studio')

@push('styles')
    @vite(['resources/css/document-studio.css'])
@endpush
@push('scripts')
    <script>
        window.DOCUMENT_STUDIO_PREVIEW_FALLBACKS = @json($previewFallbacks ?? []);
    </script>
    @vite(['resources/js/document-studio/preview.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById('preview-modal');
            document.querySelectorAll('[data-document-preview]').forEach(function (el) {
                el.addEventListener('click', function (e) {
                    var url = this.getAttribute('data-document-preview') || this.getAttribute('href');
                    if (!url || url === '#') return;
                    var directLink = document.getElementById('preview-direct-link');
                    if (directLink) directLink.href = url;
                });
            });
        });
    </script>
@endpush

@section('content')
<div class="space-y-6">
    <!-- Header with Back to Training Records navigation -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-5">
        <div class="space-y-1">
            <a href="{{ route('admin.learning.certificates') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 transition-colors">
                <x-dashboard-icon name="arrow-left" class="w-3.5 h-3.5" />
                <span>Back to Training Records</span>
            </a>
            <div class="flex items-center gap-2.5 pt-0.5">
                <span class="inline-flex items-center justify-center p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 shadow-xs">
                    <x-dashboard-icon name="award" class="w-6 h-6" />
                </span>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Document Studio</h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Design and customize official credentials (COTC & TOR) uniquely per program with drag-and-drop PDFme designer.</p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <button type="button"
                    onclick="document.getElementById('import-modal').classList.remove('hidden')"
                    class="inline-flex items-center gap-2 min-h-[44px] px-3.5 py-2 text-xs font-medium rounded-lg text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700 dark:hover:bg-gray-700 shadow-xs transition-colors">
                <x-dashboard-icon name="cloud-arrow-up" class="w-4 h-4 text-gray-500" />
                <span>Import JSON</span>
            </button>

            <a href="{{ route('admin.document-studio.create', ['program_id' => $selectedProgramId]) }}"
               class="inline-flex items-center gap-2 min-h-[44px] px-4 py-2 text-xs font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 shadow-xs transition-colors">
                <x-dashboard-icon name="plus" class="w-4 h-4" />
                <span>New document</span>
            </a>
        </div>
    </div>

    <!-- Main Navigation Tabs: Published vs Drafts -->
    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-800">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.document-studio.index', array_filter(['view' => 'published', 'program_id' => $selectedProgramId])) }}"
               class="inline-flex items-center gap-2 min-h-[44px] px-4 py-2.5 text-sm font-semibold border-b-2 transition-colors {{ $activeView !== 'drafts' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                <x-dashboard-icon name="award" class="w-4 h-4" />
                <span>Active Credentials</span>
            </a>
            <a href="{{ route('admin.document-studio.index', array_filter(['view' => 'drafts', 'program_id' => $selectedProgramId])) }}"
               class="inline-flex items-center gap-2 min-h-[44px] px-4 py-2.5 text-sm font-semibold border-b-2 transition-colors {{ $activeView === 'drafts' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                <x-dashboard-icon name="pencil" class="w-4 h-4" />
                <span>Working Drafts ({{ $draftCount }})</span>
            </a>
        </div>
    </div>

    @if($activeView !== 'drafts')
        <!-- ========================================== -->
        <!-- PROGRAM SELECTOR BAR (1 BY 1 UNIQUENESS)   -->
        <!-- ========================================== -->
        <div class="bg-white dark:bg-gray-850 p-4 rounded-xl border border-gray-200 dark:border-gray-800 shadow-xs space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-dashboard-icon name="graduation-cap" class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Select Training Program:</span>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400">Each qualification has its own unique certificate & transcript design</span>
            </div>

            <!-- Program Selector Pills -->
            <div class="flex flex-wrap gap-2">
                @foreach($programs as $prog)
                    <a href="{{ route('admin.document-studio.index', ['view' => 'published', 'program_id' => $prog->id]) }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold rounded-xl transition-all {{ $selectedProgramId === $prog->id ? 'bg-indigo-600 text-white shadow-sm ring-2 ring-indigo-400/30' : 'bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-gray-300' }}">
                        <span>{{ $prog->name }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ACTIVE OFFICIAL CREDENTIALS (COTC & TOR)   -->
        <!-- ========================================== -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
            @foreach($publishedDesigns as $kind => $info)
                @php
                    $isCotc = ($kind === 'cotc');
                    $effective = $info['effective'];
                    $latestDraft = $info['latest_draft'] ?? null;
                    $hasOverride = $info['is_program_override'];
                    $ver = $effective?->activeVersion;

                    $previewTemplate = $latestDraft ?? $effective ?? $info['global'];
                    $previewVersionId = $ver?->id ?? $info['global']?->active_version_id;
                    $previewUrl = $latestDraft
                        ? route('admin.document-studio.preview', ['template' => $latestDraft, 'mode' => 'draft'], false)
                        : ($previewTemplate
                            ? route('admin.document-studio.preview', array_filter(['template' => $previewTemplate, 'version_id' => $previewVersionId]), false)
                            : '#');
                @endphp
                <div class="bg-white dark:bg-gray-850 rounded-2xl border {{ $isCotc ? 'border-emerald-200 dark:border-emerald-900/50' : 'border-blue-200 dark:border-blue-900/50' }} shadow-xs flex flex-col justify-between overflow-hidden">
                    <div class="p-6 space-y-4">
                        <!-- Card Top Badges -->
                        <div class="flex items-center justify-between gap-2 border-b border-gray-100 dark:border-gray-800 pb-3">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-extrabold tracking-wide {{ $isCotc ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-950/80 dark:text-blue-300' }}">
                                    {{ strtoupper($kind) }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                                    {{ $isCotc ? 'Landscape • Letter (279.4 × 215.9 mm)' : 'Portrait • A4 (210 × 297 mm)' }}
                                </span>
                            </div>

                            <div class="flex flex-wrap items-center justify-end gap-1.5">
                                @if($latestDraft)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200 dark:bg-amber-950/70 dark:text-amber-200 dark:border-amber-800">
                                        Previewing Edited Draft
                                    </span>
                                @endif
                                <!-- Scope Badge -->
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-950/50 dark:text-indigo-300 dark:border-indigo-800">
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                    Program: {{ $selectedProgram?->name ?? 'Caregiving NC II' }}
                                </span>
                            </div>
                        </div>

                        <!-- Title and Scope Subtitle -->
                        <div>
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ $info['label'] }}
                            </h2>
                            @if($selectedProgram)
                                <p class="text-xs text-indigo-600 dark:text-indigo-400 font-medium mt-0.5">
                                    Program: {{ $selectedProgram->name }}
                                </p>
                            @endif
                        </div>

                        <!-- LIVE CERTIFICATE / TRANSCRIPT DIRECT PREVIEW FRAME -->
                        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-stone-100/80 dark:bg-gray-900/60 p-3 sm:p-4">
                            <div class="mx-auto w-full flex items-center justify-center {{ $isCotc ? 'max-w-[460px]' : 'max-w-[268px]' }}">
                                <a href="{{ $previewUrl }}" target="_blank" data-document-preview="{{ $previewUrl }}" data-pdfme-fallback-key="{{ $kind }}"
                                   class="group relative w-full {{ $isCotc ? '' : 'min-h-[350px] sm:min-h-[380px]' }} overflow-hidden rounded-lg bg-white shadow-md ring-1 ring-slate-900/10 transition-all hover:shadow-lg hover:ring-indigo-500/50 block cursor-pointer"
                                   style="aspect-ratio: {{ $isCotc ? '11 / 8.5' : '210 / 297' }}"
                                   title="Click to preview {{ strtoupper($kind) }} PDF">
                                    <canvas data-live-pdf-thumbnail="{{ $previewUrl }}" data-pdfme-fallback-key="{{ $kind }}" class="w-full h-full block bg-white"></canvas>

                                    <div data-live-pdf-loading class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-stone-50/90 dark:bg-gray-900/85 text-xs text-gray-500 dark:text-gray-400">
                                        <span class="h-5 w-5 animate-spin rounded-full border-2 border-indigo-500 border-t-transparent"></span>
                                        <span>Rendering live PDF preview...</span>
                                    </div>

                                    {{-- Hover Overlay --}}
                                    <div class="absolute inset-0 bg-indigo-950/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center z-20">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/95 dark:bg-gray-900/95 text-xs font-bold text-gray-900 dark:text-white shadow-md">
                                            <x-dashboard-icon name="eye" class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                                            <span>Click to preview full PDF</span>
                                        </span>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <!-- Template Status Meta -->
                        @if($ver)
                            <div class="rounded-xl bg-gray-50 dark:bg-gray-900/60 p-3.5 text-xs space-y-1.5 border border-gray-200/80 dark:border-gray-800">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-500 dark:text-gray-400">Template Name:</span>
                                    <span class="font-bold text-gray-900 dark:text-white truncate max-w-[15rem]">{{ $effective->name }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-500 dark:text-gray-400">Active Version:</span>
                                    <span class="font-extrabold text-indigo-600 dark:text-indigo-400">
                                        v{{ $ver->version_number }} (Published)
                                        @if($latestDraft)
                                            · <span class="text-amber-600 dark:text-amber-400">Draft Modified {{ ($latestDraft->draft_updated_at ?? $latestDraft->updated_at)?->diffForHumans() }}</span>
                                        @endif
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-500 dark:text-gray-400">Last Published:</span>
                                    <span class="text-gray-700 dark:text-gray-300">{{ $ver->created_at->format('M j, Y') }}</span>
                                </div>
                            </div>
                        @else
                            <div class="rounded-xl bg-amber-50/70 dark:bg-amber-950/30 p-3.5 text-xs text-amber-900 dark:text-amber-200 border border-amber-200 dark:border-amber-900/60">
                                <p class="font-bold">Built-in MCARE Institutional Baseline</p>
                                <p class="mt-0.5 text-[11px] text-amber-800 dark:text-amber-300">Using standardized TVET certificate layout. You can customize this anytime.</p>
                            </div>
                        @endif

                        <!-- Highlight: Dynamic Placeholders Showcase -->
                        <div class="rounded-xl border border-gray-200/70 dark:border-gray-800 bg-stone-50/60 dark:bg-gray-900/40 p-3.5 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider flex items-center gap-1.5">
                                    <x-dashboard-icon name="sparkles" class="w-3.5 h-3.5 text-amber-500" />
                                    Dynamic Placeholders
                                </span>
                                <span class="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400">PDFme Interactive</span>
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                @if($isCotc)
                                    <span class="px-2 py-0.5 rounded text-[11px] font-mono font-medium bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200">@{{trainee_name}}</span>
                                    <span class="px-2 py-0.5 rounded text-[11px] font-mono font-medium bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200">@{{training_program}}</span>
                                    <span class="px-2 py-0.5 rounded text-[11px] font-mono font-medium bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200">@{{completion_date}}</span>
                                    <span class="px-2 py-0.5 rounded text-[11px] font-mono font-semibold bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300">@{{release_date}}</span>
                                    <span class="px-2 py-0.5 rounded text-[11px] font-mono font-medium bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200">@{{certificate_number}}</span>
                                    <span class="px-2 py-0.5 rounded text-[11px] font-mono font-medium bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200">@{{registrar_name}}</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[11px] font-mono font-medium bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200">@{{trainee_name}}</span>
                                    <span class="px-2 py-0.5 rounded text-[11px] font-mono font-semibold bg-blue-50 dark:bg-blue-950/80 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300">@{{tor_competencies}} (table)</span>
                                    <span class="px-2 py-0.5 rounded text-[11px] font-mono font-medium bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200">@{{training_program}}</span>
                                    <span class="px-2 py-0.5 rounded text-[11px] font-mono font-medium bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200">@{{nominal_hours}}</span>
                                    <span class="px-2 py-0.5 rounded text-[11px] font-mono font-semibold bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300">@{{release_date}}</span>
                                    <span class="px-2 py-0.5 rounded text-[11px] font-mono font-medium bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200">@{{trainer_name}}</span>
                                    <span class="px-2 py-0.5 rounded text-[11px] font-mono font-medium bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200">@{{registrar_name}}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Card Actions -->
                    <div class="bg-gray-50 dark:bg-gray-900/60 px-6 py-4 border-t border-gray-200 dark:border-gray-800 flex flex-wrap items-center justify-between gap-3">
                        @if($effective)
                            <div class="flex items-center gap-2 w-full sm:w-auto flex-1">
                                <a href="{{ $previewUrl }}" target="_blank"
                                   data-document-preview="{{ $previewUrl }}"
                                   data-pdfme-fallback-key="{{ $kind }}"
                                   class="flex-1 inline-flex items-center justify-center gap-1.5 px-3.5 py-2 text-xs font-medium rounded-xl text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700 dark:hover:bg-gray-700 transition-colors min-h-[44px] cursor-pointer">
                                    <x-dashboard-icon name="eye" class="w-4 h-4 text-gray-500" />
                                    <span>Preview PDF</span>
                                </a>

                                <form action="{{ route('admin.document-studio.start-draft', $latestDraft ?? $effective) }}" method="POST" class="flex-1">
                                    @csrf
                                    <button type="submit"
                                            class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 shadow-xs transition-colors min-h-[44px]">
                                        <x-dashboard-icon name="pencil" class="w-4 h-4" />
                                        <span>Open PDFme Designer</span>
                                    </button>
                                </form>
                            </div>
                            <div class="flex items-center gap-1">
                                <a href="{{ route('admin.document-studio.versions', $effective) }}"
                                   class="p-2 text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white rounded-lg hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors"
                                   title="Version History" style="min-width:44px;min-height:44px;display:flex;align-items:center;justify-content:center">
                                    <x-dashboard-icon name="clock" class="w-4 h-4" />
                                </a>
                                <a href="{{ route('admin.document-studio.export', $effective) }}"
                                   class="p-2 text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white rounded-lg hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors"
                                   title="Export JSON" style="min-width:44px;min-height:44px;display:flex;align-items:center;justify-content:center">
                                    <x-dashboard-icon name="download" class="w-4 h-4" />
                                </a>
                            </div>
                        @else
                            <form action="{{ route('admin.document-studio.existing-design') }}" method="POST" class="w-full">
                                @csrf
                                <input type="hidden" name="kind" value="{{ $kind }}">
                                <input type="hidden" name="training_program_id" value="{{ $selectedProgramId }}">
                                <button type="submit"
                                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-semibold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 shadow-xs transition-colors min-h-[44px]">
                                    <x-dashboard-icon name="pencil" class="w-4 h-4" />
                                    <span>Initialize {{ strtoupper($kind) }} Template</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- ========================================== -->
        <!-- VIEW 2: WORKING DRAFTS                     -->
        <!-- ========================================== -->
        <div class="space-y-6">
            <!-- Search & Filters -->
            <div class="bg-white dark:bg-gray-850 p-4 rounded-xl border border-gray-200 dark:border-gray-800 shadow-xs">
                <form method="GET" action="{{ route('admin.document-studio.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-end">
                    <input type="hidden" name="view" value="drafts">

                    <!-- Search -->
                    <div>
                        <label for="search" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Search Template</label>
                        <div class="relative">
                            <input type="text"
                                   id="search"
                                   name="search"
                                   value="{{ request('search') }}"
                                   placeholder="Search by name or slug..."
                                   class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white pl-9 pr-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 min-h-[44px]" />
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <x-dashboard-icon name="magnifying-glass" class="w-4 h-4 text-gray-400" />
                            </div>
                        </div>
                    </div>

                    <!-- Kind Filter (COTC and TOR only) -->
                    <div>
                        <label for="kind" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Document Kind</label>
                        <select id="kind" name="kind" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 min-h-[44px]">
                            <option value="">All Credentials</option>
                            @foreach($kinds as $k)
                                <option value="{{ $k }}" @selected(request('kind') === $k)>{{ strtoupper($k) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Program Filter -->
                    <div>
                        <label for="program_id" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Program</label>
                        <select id="program_id" name="program_id" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 min-h-[44px]">
                            <option value="">All Programs</option>
                            @foreach($programs as $p)
                                <option value="{{ $p->id }}" @selected(request('program_id') == $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2">
                        <button type="submit" class="flex-1 px-4 py-2 text-xs font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition-colors min-h-[44px]">
                            Filter
                        </button>
                        @if(request()->hasAny(['search', 'kind', 'program_id']))
                            <a href="{{ route('admin.document-studio.index', ['view' => 'drafts']) }}" class="px-3 py-2 text-xs font-medium rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors min-h-[44px] flex items-center justify-center">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Templates Grid -->
            @if($templates->isEmpty())
                <div class="p-12 text-center bg-white dark:bg-gray-850 rounded-2xl border border-gray-200 dark:border-gray-800">
                    <x-dashboard-icon name="award" class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" />
                    <h3 class="mt-3 text-sm font-bold text-gray-900 dark:text-white">No document drafts found</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Create a draft from an active template or customize for a program above.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($templates as $tmpl)
                        @php
                            $draftPreviewUrl = route('admin.document-studio.preview', ['template' => $tmpl, 'mode' => 'draft'], false);
                        @endphp
                        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-xs hover:border-indigo-300 dark:hover:border-indigo-700 transition-all flex flex-col justify-between overflow-hidden">
                            <div class="p-5">
                                <div class="flex items-start justify-between gap-2 mb-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold
                                        @if($tmpl->document_kind === 'cotc') bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300
                                        @else bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 @endif">
                                        {{ strtoupper($tmpl->document_kind) }}
                                    </span>
                                    <div class="flex items-center gap-1.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-stone-100 text-stone-600 dark:bg-stone-800 dark:text-stone-300">Draft</span>
                                        @if($tmpl->is_system_default)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300">Default</span>
                                        @endif
                                    </div>
                                </div>

                                <h3 class="text-sm font-bold text-gray-900 dark:text-white line-clamp-1 mb-1">
                                    <a href="{{ route('admin.document-studio.edit', $tmpl) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                        {{ $tmpl->name }}
                                    </a>
                                </h3>

                                @if($tmpl->trainingProgram)
                                    <div class="flex items-center gap-1 text-xs text-indigo-600 dark:text-indigo-400 font-semibold mb-3">
                                        <x-dashboard-icon name="graduation-cap" class="w-3.5 h-3.5" />
                                        <span>{{ $tmpl->trainingProgram->name }}</span>
                                    </div>
                                @endif

                                <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1 pt-2 border-t border-gray-100 dark:border-gray-800">
                                    <div class="flex justify-between items-center">
                                        <span>Revision:</span>
                                        <span class="font-bold text-gray-800 dark:text-gray-200">rev {{ $tmpl->draft_revision }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span>Updated:</span>
                                        <span>{{ $tmpl->updated_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-50 dark:bg-gray-900/60 px-5 py-3 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between gap-2">
                                <div class="flex items-center gap-1">
                                    <a href="{{ $draftPreviewUrl }}" target="_blank"
                                       data-document-preview="{{ $draftPreviewUrl }}"
                                       class="p-1.5 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white rounded-md hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors cursor-pointer"
                                       aria-label="Preview draft" title="Preview draft" style="min-width:44px;min-height:44px;display:flex;align-items:center;justify-content:center">
                                        <x-dashboard-icon name="eye" class="w-4 h-4" />
                                    </a>
                                    <a href="{{ route('admin.document-studio.export', $tmpl) }}"
                                       class="p-1.5 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white rounded-md hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors"
                                       title="Export JSON" style="min-width:44px;min-height:44px;display:flex;align-items:center;justify-content:center">
                                        <x-dashboard-icon name="download" class="w-4 h-4" />
                                    </a>
                                </div>

                                <a href="{{ route('admin.document-studio.edit', $tmpl) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition-colors"
                                   style="min-height: 44px">
                                    <x-dashboard-icon name="pencil" class="w-3.5 h-3.5" />
                                    <span>Open Studio</span>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $templates->links() }}
                </div>
            @endif
        </div>
    @endif

    <!-- Import JSON Modal -->
    <div id="import-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-850 rounded-2xl max-w-md w-full p-6 shadow-xl border border-gray-200 dark:border-gray-800">
            <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-800">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Import Document Template</h3>
                <button type="button" onclick="document.getElementById('import-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-500">
                    <x-dashboard-icon name="xmark" class="w-5 h-5" />
                </button>
            </div>
            <form method="POST" action="{{ route('admin.document-studio.import') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Document template JSON</label>
                    <input type="file" name="template_file" accept=".json,application/json" required
                           class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-950 dark:file:text-indigo-300" />
                </div>
                <div class="flex justify-end gap-2 pt-3">
                    <button type="button" onclick="document.getElementById('import-modal').classList.add('hidden')" class="px-3.5 py-2 text-xs font-medium rounded-lg text-gray-700 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 min-h-[44px]">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 min-h-[44px]">
                        Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@include('admin.document-studio.preview')
@endsection
