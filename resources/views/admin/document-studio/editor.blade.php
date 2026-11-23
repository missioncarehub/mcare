@extends('admin.layouts.app')

@section('title', 'Editor — '.$template->name)

@push('styles')
    @vite(['resources/css/document-studio.css'])
@endpush

@push('scripts')
    @vite(['resources/js/document-studio/studio.js'])
@endpush

@section('content')
@php($sourcePdf = $template->assets->firstWhere('type', \App\Models\DocumentAsset::TYPE_PDF_SOURCE))
@if($template->conversion_report)
    <details class="mb-3 rounded-lg border p-3 text-sm">
        <summary class="cursor-pointer min-h-[44px]">Conversion review: {{ $template->conversion_report['editable_fields'] ?? 0 }} editable fields</summary>
        @foreach($template->conversion_report['warnings'] ?? [] as $warning)<p>{{ $warning }}</p>@endforeach
    </details>
@endif
@if(isset($audit))
    <details data-studio-audit class="mb-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-850 p-4 text-xs shadow-xs" @if(!($audit['is_ready_for_issuance'] ?? true)) open @endif>
        <summary class="cursor-pointer min-h-[44px] flex items-center justify-between font-bold text-gray-900 dark:text-white">
            <div class="flex items-center gap-2">
                <span data-audit-badge class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold {{ ($audit['is_ready_for_issuance'] ?? false) ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' }}">
                    {{ ($audit['is_ready_for_issuance'] ?? false) ? 'Ready for Official Issuance' : 'Needs Staff Binding Review' }}
                </span>
                <span>Template Readiness & Dynamic Binding Audit</span>
            </div>
            <span data-audit-summary class="text-gray-500 font-normal">{{ $audit['summary'] ?? '' }}</span>
        </summary>
        <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-800 grid sm:grid-cols-3 gap-4">
            <div>
                <p class="font-bold text-gray-700 dark:text-gray-300 mb-1">Page Layout</p>
                <p class="text-gray-600 dark:text-gray-400">Size: {{ strtoupper($audit['page_dimensions']['size'] ?? 'Letter') }} ({{ $audit['page_dimensions']['orientation'] ?? 'portrait' }})</p>
                <p class="text-gray-600 dark:text-gray-400">Dimensions: {{ $audit['page_dimensions']['width_mm'] ?? 0 }} × {{ $audit['page_dimensions']['height_mm'] ?? 0 }} mm</p>
                <p data-audit-fields class="text-gray-600 dark:text-gray-400">Editable Fields: {{ $audit['editable_field_count'] ?? 0 }}</p>
            </div>
            <div data-audit-bindings>
                <p class="font-bold text-gray-700 dark:text-gray-300 mb-1">Dynamic Bindings ({{ count($audit['recognized_tokens'] ?? []) }})</p>
                @if(!empty($audit['recognized_tokens']))
                    <ul class="space-y-0.5 text-gray-600 dark:text-gray-400">
                        @foreach(array_slice($audit['recognized_tokens'], 0, 5) as $token)
                            <li><span class="font-mono text-indigo-600 dark:text-indigo-400 font-semibold">{{ $token['token'] }}</span>: {{ $token['label'] }}</li>
                        @endforeach
                        @if(count($audit['recognized_tokens']) > 5)
                            <li class="text-gray-400">+{{ count($audit['recognized_tokens']) - 5 }} more bindings</li>
                        @endif
                    </ul>
                @else
                    <p class="text-amber-600 dark:text-amber-400">No dynamic placeholders mapped yet.</p>
                @endif
            </div>
            <div data-audit-checklist>
                <p class="font-bold text-gray-700 dark:text-gray-300 mb-1">Audit Checklist</p>
                @if(!empty($audit['warnings']))
                    <ul class="space-y-1 text-amber-700 dark:text-amber-300">
                        @foreach($audit['warnings'] as $warning)
                            <li class="flex items-start gap-1.5">
                                <span class="font-bold">•</span>
                                <span>{{ $warning }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-emerald-600 dark:text-emerald-400 font-medium">✓ All required tokens and structural tables verified.</p>
                @endif
            </div>
        </div>
    </details>
@endif
<div class="studio-workspace flex flex-col w-full max-w-full overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
    <!-- Top Action Bar -->
    <header class="min-h-14 bg-white dark:bg-gray-850 border-b border-gray-200 dark:border-gray-800 px-3 sm:px-4 py-2 flex flex-wrap items-center justify-between gap-2 shrink-0 z-20">
        <!-- Left: Back & Title -->
        <div class="flex items-center gap-2 sm:gap-3 min-w-0">
            <a href="{{ route('admin.document-studio.index') }}"
               class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center p-2 text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
               title="Back to Template Library">
                <x-dashboard-icon name="arrow-left" class="w-5 h-5" />
            </a>

            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <h1 class="text-sm font-bold text-gray-900 dark:text-white truncate">
                        {{ $template->name }}
                    </h1>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-semibold
                        @if($template->document_kind === 'cotc') bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300
                        @elseif($template->document_kind === 'tor') bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300
                        @elseif($template->document_kind === 'soa') bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300
                        @else bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 @endif">
                        {{ strtoupper($template->document_kind) }}
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Draft</span>
                </div>
            </div>

            <!-- Mobile tools toggle -->
            <button type="button"
                    id="btn-toggle-tools"
                    aria-label="Tools" aria-controls="tools-sidebar" aria-expanded="false"
                    class="lg:hidden min-h-[44px] min-w-[44px] inline-flex items-center justify-center p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800"
                    title="Toggle Tools & Bindings">
                <x-dashboard-icon name="layer-group" class="w-4 h-4" />
            </button>

            <!-- Mobile properties toggle -->
            @if($template->engine === 'fabric')
            <button type="button"
                    id="btn-toggle-props"
                    aria-label="Properties" aria-controls="properties-panel" aria-expanded="false"
                    class="xl:hidden min-h-[44px] min-w-[44px] inline-flex items-center justify-center p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800"
                    title="Toggle Element Properties">
                <x-dashboard-icon name="sliders" class="w-4 h-4" />
            </button>
            @endif
        </div>

        <!-- Middle: Save Status Pill -->
        <div class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-full bg-gray-50 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-300">
            <span id="save-status-dot" class="w-2 h-2 rounded-full bg-emerald-500"></span>
            <span id="save-status-label">Saved (rev {{ $template->draft_revision }})</span>
        </div>

        <!-- Right: Actions -->
        <div class="flex items-center gap-1.5 sm:gap-2">
            <!-- Manual Save Button -->
            <button type="button"
                    id="btn-save-draft"
                    class="min-h-[44px] inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-lg text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700 dark:hover:bg-gray-700 transition-colors shadow-xs"
                    title="Save Draft (Ctrl+S)">
                <x-dashboard-icon name="check" class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                <span>Save</span>
            </button>

            <!-- Preview Button -->
            @if($sourcePdf)
                <button type="button" id="btn-compare-original" class="min-h-[44px] px-3 text-xs border rounded-lg">Compare original</button>
            @endif
            <button type="button"
                    id="btn-preview"
                    class="min-h-[44px] min-w-[44px] inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-lg text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700 dark:hover:bg-gray-700 transition-colors shadow-xs">
                <x-dashboard-icon name="eye" class="w-4 h-4 text-indigo-500" />
                <span class="hidden sm:inline">Preview</span>
            </button>

            <!-- Export Template -->
            <a href="{{ route('admin.document-studio.export', $template) }}"
               class="min-h-[44px] min-w-[44px] p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors hidden sm:inline-flex items-center justify-center"
               title="Export Template JSON">
                <x-dashboard-icon name="download" class="w-4 h-4" />
            </a>

            <!-- Versions Link -->
            <a href="{{ route('admin.document-studio.versions', $template) }}"
               class="min-h-[44px] inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-lg text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
               title="Version History">
                <x-dashboard-icon name="clock" class="w-4 h-4" />
                <span id="active-version-number" class="hidden sm:inline">{{ $template->activeVersion ? 'v'.$template->activeVersion->version_number : 'Draft' }}</span>
            </a>

            <!-- Publish Version Button -->
            <button type="button"
                    id="btn-publish-modal"
                    class="min-h-[44px] inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs">
                <x-dashboard-icon name="circle-check" class="w-4 h-4" />
                <span>Publish</span>
            </button>
        </div>
    </header>

    <!-- Studio Body: Left Sidebar + Center Workspace + Right Properties Panel -->
    <div class="flex-1 flex overflow-hidden relative">
        <!-- Left Sidebar: Tools & Placeholders -->
        <aside id="tools-sidebar" class="hidden lg:flex w-72 bg-white dark:bg-gray-850 border-r border-gray-200 dark:border-gray-800 flex-col shrink-0 z-10">
            <div class="p-3 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <h2 class="text-xs font-bold tracking-wide text-gray-500 dark:text-gray-400 uppercase">
                    Tools & Bindings
                </h2>
                <button type="button"
                        id="btn-asset-library"
                        class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1 font-medium">
                    <x-dashboard-icon name="cloud-arrow-up" class="w-3.5 h-3.5" />
                    Upload
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-3 space-y-5">
                <!-- Architecture / Concept info -->
                <div class="p-2.5 rounded-lg bg-indigo-50/50 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-900/40 text-[11px] text-gray-600 dark:text-gray-300 space-y-1">
                    <p>Converted text is editable. Artwork may remain a locked background; review it before reusing the design.</p>
                    @if($template->is_system_default && ($template->activeVersion?->version_number ?? 1) === 1)
                        <p>This is an editable starting layout. Preview it before publishing a new version. Existing documents keep their saved design.</p>
                    @endif
                </div>

                <!-- Add Element Actions -->
                <div class="space-y-1.5">
                    <span class="text-[11px] font-semibold text-gray-400 uppercase">Insert Elements</span>
                    <div class="grid grid-cols-2 gap-1.5">
                        @if($template->engine === 'fabric')
                            <button type="button" id="btn-add-rectangle" class="p-2 rounded-lg border border-gray-200 dark:border-gray-700 text-xs">Rectangle</button>
                            <button type="button" id="btn-add-circle" class="p-2 rounded-lg border border-gray-200 dark:border-gray-700 text-xs">Circle</button>
                        @endif
                        <button type="button" id="btn-add-text" class="flex items-center gap-1.5 p-2 rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-xs font-medium text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 transition-colors">
                            <x-dashboard-icon name="pencil" class="w-3.5 h-3.5 text-indigo-500" />
                            Text Box
                        </button>
                        <button type="button" onclick="document.getElementById('asset-modal').classList.remove('hidden')" class="flex items-center gap-1.5 p-2 rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-xs font-medium text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 transition-colors">
                            <x-dashboard-icon name="file-text" class="w-3.5 h-3.5 text-emerald-500" />
                            Image / Logo
                        </button>
                    </div>
                </div>

                <!-- Dynamic Placeholders List -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold text-gray-400 uppercase">MCARE Dynamic Bindings</span>
                        <span class="text-[10px] text-gray-400">Click to add</span>
                    </div>

                    <div class="space-y-1.5">
                        @foreach($placeholders as $key => $placeholder)
                            <button type="button"
                                    class="btn-insert-placeholder studio-chip w-full text-left p-2 rounded-lg bg-gray-50 dark:bg-gray-800/80 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 border border-gray-200 dark:border-gray-700/80 hover:border-indigo-300 dark:hover:border-indigo-700 text-xs group"
                                    data-token="{{ $placeholder['token'] }}"
                                    data-type="{{ $placeholder['type'] }}"
                                    data-key="{{ $key }}">
                                <div class="flex items-center justify-between">
                                    <span class="font-mono font-semibold text-indigo-600 dark:text-indigo-400 group-hover:text-indigo-700">
                                        {{ $placeholder['token'] }}
                                    </span>
                                    <span class="text-[10px] text-gray-400 uppercase font-mono">{{ $placeholder['type'] }}</span>
                                </div>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-1">
                                    {{ $placeholder['label'] }}
                                </p>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Table bindings (TOR / SOA) -->
                @if(in_array($template->document_kind, ['tor', 'soa'], true))
                    <div class="space-y-2 pt-2 border-t border-gray-200 dark:border-gray-800">
                        <span class="text-[11px] font-semibold text-gray-400 uppercase">Multi-Row Tables</span>
                        @if($template->document_kind === 'tor')
                            <div class="p-2.5 rounded-lg bg-blue-50/50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900/40 text-xs space-y-1">
                                <span class="font-bold text-blue-800 dark:text-blue-300">Competencies Table</span>
                                <p class="text-[11px] text-gray-600 dark:text-gray-400">
                                    Repeater binding for Unit Code, Unit Title, Hours, Grade, and Competency status.
                                </p>
                            </div>
                        @elseif($template->document_kind === 'soa')
                            <div class="p-2.5 rounded-lg bg-purple-50/50 dark:bg-purple-950/30 border border-purple-200 dark:purple-900/40 text-xs space-y-1">
                                <span class="font-bold text-purple-800 dark:text-purple-300">Payment Ledger & Fees</span>
                                <p class="text-[11px] text-gray-600 dark:text-gray-400">
                                    Repeater tables for verified payments, OR numbers, and tuition breakdown.
                                </p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @if($template->engine === 'pdfme')
            <details id="pdfme-text-tools" class="p-3 border-t text-sm">
                <summary class="min-h-[44px] cursor-pointer">Text fields</summary>
                <label class="block">Select fields<select multiple data-text-fields aria-label="Text fields" class="block w-full rounded-lg min-h-[120px]"></select></label>
                <label class="block mt-2">MCARE field<select data-text-binding class="block w-full min-h-[44px] rounded-lg"><option value="">Choose field</option>@foreach($placeholders as $definition)@if(($definition['type'] ?? '') !== 'table')<option value="{{ $definition['token'] }}">{{ $definition['label'] }}</option>@endif @endforeach</select></label>
                <button type="button" data-text-action="bind" class="min-h-[44px] px-2 border rounded-lg">Bind selected</button>
                <button type="button" data-text-action="merge" class="min-h-[44px] px-2 border rounded-lg">Merge selected</button>
                <label class="block mt-2">Split at<input data-text-separator value=" " class="block w-full min-h-[44px] rounded-lg" aria-label="Text separator" /></label>
                <button type="button" data-text-action="split" class="min-h-[44px] px-2 border rounded-lg">Split field</button>
                <p data-text-feedback role="status" aria-live="polite"></p>
            </details>
            @endif
        </aside>

        <!-- Center Workspace: Canvas / Editor Viewport -->
        <main class="flex-1 min-w-0 studio-canvas-container overflow-auto p-3 sm:p-6 relative">
            @if($sourcePdf)
                <section id="studio-original" class="studio-preview studio-original" hidden data-endpoint="{{ route('admin.document-studio.assets.stream', $sourcePdf) }}" aria-label="Original PDF">
                    <header><h2>Original PDF</h2><button type="button" data-preview-action="close" aria-label="Close original">✕</button></header>
                    <div class="studio-preview-toolbar">
                        <button type="button" data-preview-action="previous" disabled>Previous</button>
                        <button type="button" data-preview-action="next" disabled>Next</button>
                        <button type="button" data-preview-action="fit" disabled>Fit width</button>
                    </div>
                    <p data-preview-status role="status" aria-live="polite"></p>
                    <div data-preview-body><canvas hidden aria-label="Original PDF page"></canvas></div>
                </section>
            @endif
            @if($template->engine === 'fabric')
                <!-- Fabric Interactive Canvas -->
                <div class="studio-paper-shadow w-max mx-auto rounded overflow-hidden bg-white">
                    <canvas id="fabric-canvas"></canvas>
                </div>
            @else
                <!-- pdfme / rk_word Container -->
                <div id="studio-editor-viewport" class="w-full h-full min-h-[850px]"></div>
            @endif
        </main>

        <!-- Right Sidebar: Properties & Page Settings (Used predominantly in Fabric free-layout mode) -->
        @if($template->engine === 'fabric')
        <aside id="properties-panel" class="hidden xl:flex w-64 bg-white dark:bg-gray-850 border-l border-gray-200 dark:border-gray-800 flex-col shrink-0 z-10">
            <div class="p-3 border-b border-gray-200 dark:border-gray-800">
                <h2 class="text-xs font-bold tracking-wide text-gray-500 dark:text-gray-400 uppercase">
                    Element Properties
                </h2>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-4 text-xs">
                <div id="no-selection-notice" class="text-center py-8 text-gray-400">
                    <p>Select an element on the canvas to configure typography, color, and bindings.</p>
                </div>

                <div id="selection-controls" class="hidden space-y-4">
                    <!-- Text / Content -->
                    <div>
                        <label for="prop-text" class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Text Content</label>
                        <textarea id="prop-text" rows="2" class="w-full text-xs rounded border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white p-2 focus:ring-1 focus:ring-indigo-500"></textarea>
                    </div>

                    <!-- Font Size & Color -->
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label for="prop-font-size" class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Font Size (pt)</label>
                            <input type="number" id="prop-font-size" min="6" max="72" value="14" class="w-full text-xs rounded border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white p-1.5 focus:ring-1 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label for="prop-color" class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Color</label>
                            <input type="color" id="prop-color" value="#111827" class="w-full h-8 rounded border-gray-300 dark:border-gray-700 cursor-pointer" />
                        </div>
                    </div>

                    <!-- Alignment & Layers -->
                    <div class="pt-2 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between gap-2">
                        <button type="button" id="btn-delete-element" class="px-2.5 py-1.5 rounded text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40 text-xs font-semibold">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </aside>
        @endif
    </div>
</div>

@include('admin.document-studio.preview')
<livewire:document-studio-feedback />

<!-- Publish Version Modal -->
<div id="publish-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-850 rounded-xl max-w-md w-full p-6 shadow-xl border border-gray-200 dark:border-gray-800">
        <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-800">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Publish Immutable Version</h3>
            <button type="button" id="btn-cancel-publish" class="text-gray-400 hover:text-gray-500">
                <x-dashboard-icon name="check" class="w-5 h-5" />
            </button>
        </div>
        <form id="form-publish" class="mt-4 space-y-4">
            <div>
                <label for="publish-changelog" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Release Changelog / Notes
                </label>
                <textarea id="publish-changelog"
                          rows="3"
                          placeholder="e.g. Updated signatories, adjusted trainee name positioning, verified QR code."
                          class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white p-2.5 focus:ring-2 focus:ring-indigo-500"></textarea>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" id="publish-make-active" checked class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                <label for="publish-make-active" class="text-xs text-gray-700 dark:text-gray-300">Set as active production version immediately</label>
            </div>
            <div class="flex justify-end gap-2 pt-3">
                <button type="button" onclick="document.getElementById('publish-modal').classList.add('hidden')" class="px-3 py-1.5 text-xs font-medium rounded-lg text-gray-700 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-1.5 text-xs font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700">
                    Publish Version
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Asset Library / Upload Modal -->
<div id="asset-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-850 rounded-xl max-w-lg w-full p-6 shadow-xl border border-gray-200 dark:border-gray-800">
        <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-800">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Upload Asset</h3>
            <button type="button" id="btn-close-assets" class="text-gray-400 hover:text-gray-500">
                <x-dashboard-icon name="check" class="w-5 h-5" />
            </button>
        </div>
        <form id="form-upload-asset" class="mt-4 space-y-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Asset Type</label>
                <select id="asset-type-select" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white py-2 px-3">
                    <option value="logo">Official Logo</option>
                    <option value="signature">Signatory Signature</option>
                    <option value="image">Decorative Image / Seal</option>
                    @if($capabilities['can_import_pdf'])<option value="pdf_base">Base PDF Background</option>@endif
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Choose File</label>
                <input type="file" id="asset-file-input" required class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-950 dark:file:text-indigo-300" />
            </div>
            <div class="flex justify-end gap-2 pt-3">
                <button type="button" onclick="document.getElementById('asset-modal').classList.add('hidden')" class="px-3 py-1.5 text-xs font-medium rounded-lg text-gray-700 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-1.5 text-xs font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700">
                    Upload & Insert
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Injected Configuration -->
<script>
    window.DOCUMENT_STUDIO_CONFIG = {
        templateId: {{ $template->id }},
        templateName: @json($template->name),
        documentKind: @json($template->document_kind),
        engine: @json($template->engine),
        draftRevision: {{ $template->draft_revision }},
        draftState: @json($template->draft_state ?? []),
        placeholders: @json($placeholders),
        samplePayload: @json($samplePayload),
        capabilities: @json($capabilities),
        assets: @json($assetUrls),
        endpoints: {
            saveDraft: @json(route('admin.document-studio.draft.save', $template)),
            publish: @json(route('admin.document-studio.publish', $template)),
            preview: @json(route('admin.document-studio.preview', ['template' => $template, 'mode' => 'draft'])),
            export: @json(route('admin.document-studio.export', $template)),
            uploadAsset: @json(route('admin.document-studio.assets.upload')),
            importDocx: @json(route('admin.document-studio.import-docx', $template)),
        }
    };
</script>
@endsection
