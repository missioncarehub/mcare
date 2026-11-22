@extends('admin.layouts.app')

@section('title', 'Document Studio')

@push('styles')
    @vite(['resources/css/document-studio.css'])
@endpush
@push('scripts')
    @vite(['resources/js/document-studio/preview.js'])
@endpush

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-5">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center p-2 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400">
                    <x-dashboard-icon name="award" class="w-6 h-6" />
                </span>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Document Studio</h1>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <!-- Import Template Button -->
            <button type="button"
                    onclick="document.getElementById('import-modal').classList.remove('hidden')"
                    class="inline-flex items-center gap-2 min-h-[44px] px-3.5 py-2 text-sm font-medium rounded-lg text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700 dark:hover:bg-gray-700 shadow-xs transition-colors">
                <x-dashboard-icon name="cloud-arrow-up" class="w-4 h-4 text-gray-500" />
                Import template
            </button>

            <a href="{{ route('admin.document-studio.create') }}"
               class="inline-flex items-center gap-2 min-h-[44px] px-4 py-2 text-sm font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 shadow-xs transition-colors">
                <x-dashboard-icon name="pencil" class="w-4 h-4" />
                New document
            </a>
        </div>
    </div>

    <!-- Dismissible Lifecycle Notice -->
    <div x-data="{ dismissed: localStorage.getItem('mcare_studio_notice_dismissed') === '1' }"
         x-show="!dismissed"
         class="rounded-xl border border-indigo-200 bg-indigo-50/70 p-4 text-xs text-indigo-900 dark:border-indigo-900/60 dark:bg-indigo-950/30 dark:text-indigo-200 flex items-start justify-between gap-3 shadow-xs">
        <div class="flex items-start gap-3">
            <x-dashboard-icon name="info" class="w-5 h-5 text-indigo-600 dark:text-indigo-400 shrink-0 mt-0.5" />
            <div class="space-y-1">
                <p>Changes stay in Drafts until you publish. Published designs apply to new documents; issued files keep their saved design.</p>
            </div>
        </div>
        <button type="button"
                @click="dismissed = true; localStorage.setItem('mcare_studio_notice_dismissed', '1')"
                class="text-indigo-500 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-white p-1 rounded-md min-h-[44px] min-w-[44px]"
                aria-label="Dismiss notice">
            <x-dashboard-icon name="xmark" class="w-4 h-4" />
        </button>
    </div>

    <!-- View Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-gray-200 dark:border-gray-800">
        <a href="{{ route('admin.document-studio.index', ['view' => 'published']) }}"
           class="inline-flex items-center gap-2 min-h-[44px] px-4 py-2.5 text-sm font-semibold border-b-2 transition-colors {{ $activeView !== 'drafts' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
            <x-dashboard-icon name="award" class="w-4 h-4" />
            <span>Published</span>
        </a>
        <a href="{{ route('admin.document-studio.index', ['view' => 'drafts']) }}"
           class="inline-flex items-center gap-2 min-h-[44px] px-4 py-2.5 text-sm font-semibold border-b-2 transition-colors {{ $activeView === 'drafts' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
            <x-dashboard-icon name="pencil" class="w-4 h-4" />
            <span>Drafts ({{ $draftCount }})</span>
        </a>
    </div>

    @if($activeView !== 'drafts')
        <!-- ========================================== -->
        <!-- VIEW 1: PUBLISHED DESIGNS (DEFAULT)        -->
        <!-- ========================================== -->
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">Active Official Document Designs</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">These designs are actively used when generating official credentials and statements.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                @foreach($publishedDesigns as $kind => $info)
                    <div class="bg-white dark:bg-gray-850 rounded-xl border border-gray-200 dark:border-gray-800 shadow-xs flex flex-col justify-between overflow-hidden">
                        <div class="p-5 space-y-4">
                            <!-- Card Header -->
                            <div class="flex items-center justify-between gap-2 border-b border-gray-100 dark:border-gray-800 pb-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold
                                    @if($kind === 'cotc') bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300
                                    @elseif($kind === 'tor') bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300
                                    @elseif($kind === 'soa') bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ strtoupper($kind) }}
                                </span>
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                    Global Scope
                                </span>
                            </div>

                            <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                {{ $info['label'] }}
                            </h3>

                            @if($info['global'] && $info['global']->activeVersion)
                                @php $tmpl = $info['global']; $ver = $tmpl->activeVersion; @endphp
                                <div class="rounded-lg bg-stone-50 dark:bg-gray-900/50 p-3 text-xs space-y-1.5 border border-stone-200/70 dark:border-gray-800">
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-500">Design Name:</span>
                                        <span class="font-semibold text-gray-900 dark:text-white truncate max-w-[12rem]">{{ $tmpl->name }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-500">Active Version:</span>
                                        <span class="font-bold text-indigo-600 dark:text-indigo-400">Version {{ $ver->version_number }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-500">Published:</span>
                                        <span>{{ $ver->created_at->format('M j, Y') }}</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 pt-2">
                                    <button type="button"
                                            data-document-preview="{{ route('admin.document-studio.preview', ['template' => $tmpl, 'version_id' => $ver->id]) }}"
                                            class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-medium rounded-lg text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700 dark:hover:bg-gray-700 transition-colors"
                                            style="min-height: 44px">
                                        <x-dashboard-icon name="eye" class="w-4 h-4 text-gray-500" />
                                        <span>Preview PDF</span>
                                    </button>
                                    <form action="{{ route('admin.document-studio.start-draft', $tmpl) }}" method="POST" class="flex-1">
                                        @csrf
                                        <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 min-h-[44px] text-xs font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
                                            <x-dashboard-icon name="pencil" class="w-3.5 h-3.5" />
                                            <span>Edit draft</span>
                                        </button>
                                    </form>
                                </div>
                            @else
                                <div class="rounded-lg bg-amber-50/60 dark:bg-amber-950/20 p-3 text-xs text-amber-900 dark:text-amber-200 border border-amber-200 dark:border-amber-900/50">
                                    <p class="font-semibold">Built-in MCARE Baseline Design</p>
                                    <p class="mt-1 text-[11px] text-amber-700 dark:text-amber-300">Using standard institutional format. You can create an editable Studio draft copy from this design anytime.</p>
                                </div>
                                <form action="{{ route('admin.document-studio.existing-design') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="kind" value="{{ $kind }}">
                                    <button type="submit"
                                            class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:text-indigo-300 dark:hover:bg-indigo-900 transition-colors"
                                            style="min-height: 44px">
                                        <x-dashboard-icon name="copy" class="w-3.5 h-3.5" />
                                        <span>Create Draft Copy</span>
                                    </button>
                                </form>
                            @endif

                            <!-- Program Overrides if any -->
                            @if($info['program_overrides']->isNotEmpty())
                                <div class="border-t border-gray-100 dark:border-gray-800 pt-3 space-y-2">
                                    <p class="text-[11px] font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Program-Specific Overrides</p>
                                    @foreach($info['program_overrides'] as $override)
                                        <div class="rounded-lg border border-indigo-100 dark:border-indigo-900/40 bg-indigo-50/30 dark:bg-indigo-950/10 p-2.5 text-xs space-y-1">
                                            <div class="flex items-center justify-between">
                                                <span class="font-bold text-indigo-700 dark:text-indigo-300">{{ $override->trainingProgram?->name }}</span>
                                                <span class="rounded bg-indigo-100 text-indigo-800 dark:bg-indigo-900/60 dark:text-indigo-200 px-1.5 py-0.5 text-[10px] font-bold">Override v{{ $override->activeVersion?->version_number }}</span>
                                            </div>
                                            <div class="flex items-center justify-between pt-1">
                                                <button type="button"
                                                        data-document-preview="{{ route('admin.document-studio.preview', ['template' => $override, 'version_id' => $override->active_version_id]) }}"
                                                        class="text-indigo-600 dark:text-indigo-400 hover:underline text-[11px] font-medium"
                                                        style="min-height: 44px">
                                                    Preview
                                                </button>
                                                <form action="{{ route('admin.document-studio.start-draft', $override) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="min-h-[44px] px-2 text-indigo-600 dark:text-indigo-400 hover:underline text-[11px] font-semibold">Edit draft</button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

        </div>
    @else
        <!-- ========================================== -->
        <!-- VIEW 2: WORKING DRAFTS & ALL TEMPLATES     -->
        <!-- ========================================== -->
        <div class="space-y-6">
            <!-- Filters & Search (NO ENGINE FILTER) -->
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
                                   class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white pl-9 pr-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 min-h-[44px]" />
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <x-dashboard-icon name="magnifying-glass" class="w-4 h-4 text-gray-400" />
                            </div>
                        </div>
                    </div>

                    <!-- Kind Filter -->
                    <div>
                        <label for="kind" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Document Kind</label>
                        <select id="kind" name="kind" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 min-h-[44px]">
                            <option value="">All Document Kinds</option>
                            @foreach($kinds as $k)
                                <option value="{{ $k }}" @selected(request('kind') === $k)>{{ strtoupper($k) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Program Filter -->
                    <div>
                        <label for="program_id" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Program</label>
                        <select id="program_id" name="program_id" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 min-h-[44px]">
                            <option value="">All Programs</option>
                            @foreach($programs as $p)
                                <option value="{{ $p->id }}" @selected(request('program_id') == $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2">
                        <button type="submit" class="flex-1 px-4 py-2 text-sm font-medium rounded-lg text-white bg-gray-900 dark:bg-gray-700 hover:bg-black dark:hover:bg-gray-600 transition-colors min-h-[44px]">
                            Filter
                        </button>
                        @if(request()->hasAny(['search', 'kind', 'program_id']))
                            <a href="{{ route('admin.document-studio.index', ['view' => 'drafts']) }}" class="px-3 py-2 text-sm font-medium rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors min-h-[44px] flex items-center justify-center">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Templates Grid -->
            @if($templates->isEmpty())
                <div class="p-12 text-center bg-white dark:bg-gray-850 rounded-xl border border-gray-200 dark:border-gray-800">
                    <x-dashboard-icon name="award" class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" />
                    <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">No document templates found</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try adjusting your filters or create a new template to start designing.</p>
                    <div class="mt-5">
                        <a href="{{ route('admin.document-studio.create') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700">
                            <x-dashboard-icon name="pencil" class="w-4 h-4" />
                            New document
                        </a>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($templates as $tmpl)
                        <div class="bg-white dark:bg-gray-850 rounded-xl border border-gray-200 dark:border-gray-800 shadow-xs hover:border-indigo-300 dark:hover:border-indigo-700 transition-all flex flex-col justify-between overflow-hidden">
                            <div class="p-5">
                                <!-- Header with Badges -->
                                <div class="flex items-start justify-between gap-2 mb-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold
                                        @if($tmpl->document_kind === 'cotc') bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300
                                        @elseif($tmpl->document_kind === 'tor') bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300
                                        @elseif($tmpl->document_kind === 'soa') bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 @endif">
                                        {{ strtoupper($tmpl->document_kind) }}
                                    </span>

                                    <div class="flex items-center gap-1.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-stone-100 text-stone-600 dark:bg-stone-800 dark:text-stone-300">Draft</span>
                                        @if($tmpl->is_system_default)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300" title="System Baseline Template">
                                                Default
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Template Title -->
                                <h3 class="text-base font-bold text-gray-900 dark:text-white line-clamp-1 mb-1">
                                    <a href="{{ route('admin.document-studio.edit', $tmpl) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                        {{ $tmpl->name }}
                                    </a>
                                </h3>

                                <!-- Program applicability -->
                                @if($tmpl->trainingProgram)
                                    <div class="flex items-center gap-1 text-xs text-indigo-600 dark:text-indigo-400 font-medium mb-3">
                                        <x-dashboard-icon name="graduation-cap" class="w-3.5 h-3.5" />
                                        <span>{{ $tmpl->trainingProgram->name }}</span>
                                    </div>
                                @endif

                                <!-- Metadata -->
                                <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1 pt-2 border-t border-gray-100 dark:border-gray-800">
                                    @if($tmpl->is_active && $tmpl->activeVersion)
                                        <div class="flex justify-between items-center">
                                            <span>Published design:</span>
                                            <span>Version {{ $tmpl->activeVersion->version_number }}</span>
                                        </div>
                                    @endif
                                    <div class="flex justify-between items-center">
                                        <span>Draft Revision:</span>
                                        <span class="font-semibold text-gray-800 dark:text-gray-200">rev {{ $tmpl->draft_revision }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span>Last Updated:</span>
                                        <span>{{ $tmpl->updated_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Actions -->
                            <div class="bg-gray-50 dark:bg-gray-900/60 px-5 py-3 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between gap-2">
                                <div class="flex items-center gap-1">
                                    <button type="button" data-document-preview="{{ route('admin.document-studio.preview', ['template' => $tmpl, 'mode' => 'draft']) }}"
                                       class="p-1.5 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white rounded-md hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors"
                                       aria-label="Preview draft {{ $tmpl->name }}" title="Preview draft" style="min-width:44px;min-height:44px">
                                        <x-dashboard-icon name="eye" class="w-4 h-4" />
                                    </button>
                                    <a href="{{ route('admin.document-studio.export', $tmpl) }}"
                                       class="p-1.5 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white rounded-md hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors"
                                       title="Export Template JSON" style="min-width:44px;min-height:44px;display:flex;align-items:center;justify-content:center">
                                        <x-dashboard-icon name="download" class="w-4 h-4" />
                                    </a>
                                    <a href="{{ route('admin.document-studio.versions', $tmpl) }}"
                                       class="p-1.5 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white rounded-md hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors"
                                       title="Version History" style="min-width:44px;min-height:44px;display:flex;align-items:center;justify-content:center">
                                        <x-dashboard-icon name="clock" class="w-4 h-4" />
                                    </a>
                                </div>

                                <a href="{{ route('admin.document-studio.edit', $tmpl) }}"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-md text-white bg-indigo-600 hover:bg-indigo-700 transition-colors"
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
        <div class="bg-white dark:bg-gray-850 rounded-xl max-w-md w-full p-6 shadow-xl border border-gray-200 dark:border-gray-800">
            <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Import template</h3>
                <button type="button" onclick="document.getElementById('import-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-500">
                    <x-dashboard-icon name="xmark" class="w-5 h-5" />
                </button>
            </div>
            <form method="POST" action="{{ route('admin.document-studio.import') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Document template JSON</label>
                    <input type="file" name="template_file" accept=".json,application/json" required
                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-950 dark:file:text-indigo-300" />
                    <p class="mt-1 text-xs text-gray-400">Use a template exported from Document Studio. Quiz files belong in Upload quiz JSON.</p>
                </div>
                <div class="flex justify-end gap-2 pt-3">
                    <button type="button" onclick="document.getElementById('import-modal').classList.add('hidden')" class="px-3 py-1.5 text-xs font-medium rounded-lg text-gray-700 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 min-h-[44px]">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-1.5 text-xs font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 min-h-[44px]">
                        Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@include('admin.document-studio.preview')
@endsection
