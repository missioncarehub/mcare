<dialog id="preview-modal" aria-labelledby="preview-title" class="studio-preview">
    <header>
        <h2 id="preview-title">Document preview <small>Sample data</small></h2>
        <button type="button" data-preview-action="close" aria-label="Close preview">✕</button>
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
