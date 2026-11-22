<dialog id="preview-modal" aria-labelledby="preview-title" class="studio-preview">
    <header>
        <h2 id="preview-title">Document preview <small>Sample data</small></h2>
        <div class="flex items-center gap-2">
            <a id="preview-direct-link" href="#" target="_blank" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-950 dark:text-indigo-300 transition-colors">
                Open in new tab ↗
            </a>
            <button type="button" data-preview-action="close" aria-label="Close preview" class="p-1 text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white rounded-lg">✕</button>
        </div>
    </header>
    <div class="studio-preview-toolbar" aria-label="Preview controls">
        <button type="button" data-preview-action="previous" aria-label="Previous page" disabled>Previous</button>
        <button type="button" data-preview-action="next" aria-label="Next page" disabled>Next</button>
        <button type="button" data-preview-action="out" aria-label="Zoom out" disabled>−</button>
        <button type="button" data-preview-action="in" aria-label="Zoom in" disabled>+</button>
        <button type="button" data-preview-action="fit" disabled>Fit width</button>
        <button type="button" data-preview-action="download" disabled>Download PDF</button>
    </div>
    <p data-preview-status role="status" aria-live="polite"></p>
    <div data-preview-body><canvas hidden aria-label="PDF page"></canvas></div>
</dialog>
