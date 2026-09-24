@props(['module', 'viewerUrl'])

@php($previewKind = $module->previewKind())

<div {{ $attributes->class(['lms-module-preview-frame overflow-hidden rounded-xl border border-stone-200 bg-stone-950']) }} data-module-file-preview data-preview-kind="{{ $previewKind }}">
    @if($previewKind === 'video')
        <div class="flex h-full items-center justify-center bg-black p-3">
            <video class="max-h-full max-w-full object-contain" controls controlsList="nodownload noremoteplayback" disablePictureInPicture preload="metadata">
                <source src="{{ $viewerUrl }}" type="{{ $module->mime_type }}">
                Your browser cannot play this video.
            </video>
        </div>
    @elseif($previewKind === 'audio')
        <div class="grid h-full place-items-center bg-white p-6 sm:p-8">
            <div class="w-full max-w-2xl text-center">
                <x-dashboard-icon name="volume-2" class="mx-auto h-10 w-10 text-purple-600" />
                <p class="mt-4 break-words font-bold text-stone-950">{{ $module->original_file_name }}</p>
                <audio class="mt-5 w-full" controls preload="metadata"><source src="{{ $viewerUrl }}" type="{{ $module->mime_type }}"></audio>
            </div>
        </div>
    @elseif($previewKind === 'image')
        <div class="flex h-full items-center justify-center overflow-auto bg-stone-950 p-4">
            <img src="{{ $viewerUrl }}" alt="{{ $module->title }}" class="max-h-full max-w-full object-contain">
        </div>
    @elseif($previewKind === 'pdf')
        <div class="flex h-full min-h-0 flex-col bg-slate-900" data-pdf-canvas-viewer data-pdf-url="{{ $viewerUrl }}" data-pdf-fit-mode="page">
            <div class="z-20 flex flex-wrap items-center justify-between gap-3 border-b border-slate-700 bg-slate-900 px-3 py-2.5 text-xs text-white sm:px-4">
                <div class="flex items-center gap-2">
                    <button type="button" class="rounded bg-slate-800 px-3 py-1.5 font-bold hover:bg-slate-700 disabled:opacity-40" data-pdf-prev disabled title="Previous page">&larr; Prev</button>
                    <span class="font-medium text-slate-300">Page <span data-pdf-current-page>1</span> of <span data-pdf-total-pages>-</span></span>
                    <button type="button" class="rounded bg-slate-800 px-3 py-1.5 font-bold hover:bg-slate-700 disabled:opacity-40" data-pdf-next disabled title="Next page">Next &rarr;</button>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="rounded bg-slate-800 px-2.5 py-1.5 font-bold hover:bg-slate-700" data-pdf-zoom-out title="Zoom out">&minus;</button>
                    <span class="min-w-12 text-center font-medium text-slate-300" data-pdf-zoom-level>Fit</span>
                    <button type="button" class="rounded bg-slate-800 px-2.5 py-1.5 font-bold hover:bg-slate-700" data-pdf-zoom-in title="Zoom in">+</button>
                    <button type="button" class="rounded bg-slate-800 px-2.5 py-1.5 font-bold text-slate-200 hover:bg-slate-700" data-pdf-fit-width title="Fit the full page">Fit Page</button>
                </div>
            </div>
            <div class="relative min-h-0 flex-1 overflow-auto p-3 sm:p-4" data-pdf-canvas-container>
                <div data-pdf-scroll-sizer>
                    <div class="relative inline-block shadow-2xl" data-pdf-page-wrapper>
                        <canvas class="block bg-white shadow-md" data-pdf-canvas></canvas>
                    </div>
                </div>
                <div class="absolute inset-0 flex items-center justify-center bg-slate-950/85 text-white" data-pdf-loading>
                    <div class="flex items-center gap-3">
                        <span class="h-5 w-5 animate-spin rounded-full border-2 border-purple-400 border-t-transparent"></span>
                        <span class="text-sm font-semibold">Fitting document preview...</span>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="grid h-full place-items-center bg-white p-8 text-center text-stone-700">
            <div>
                <x-dashboard-icon name="file-text" class="mx-auto h-10 w-10 text-purple-600" />
                <p class="mt-4 font-bold">{{ $module->fileTypeLabel() }}</p>
                <p class="mt-2 text-sm text-stone-500">This file type opens in its native application or a new browser tab.</p>
            </div>
        </div>
    @endif
</div>
