import { loadPdfJs } from '../pdf-loader.js';

// A PDF is fetched as private data, never navigated to or embedded in a frame.
export class StudioPreview {
    constructor(dialog) {
        this.dialog = dialog;
        this.inline = !dialog.showModal;
        this.status = dialog.querySelector('[data-preview-status]');
        this.canvas = dialog.querySelector('canvas');
        this.body = dialog.querySelector('[data-preview-body]');
        this.epoch = 0;
        dialog.addEventListener('close', () => { if (!dialog.open) this.release(); });
        dialog.addEventListener('click', event => {
            const action = event.target.closest('[data-preview-action]')?.dataset.previewAction;
            if (action === 'close') this.close();
            if (action === 'previous' && this.page > 1) { this.page--; this.render(); }
            if (action === 'next' && this.pdf && this.page < this.pdf.numPages) { this.page++; this.render(); }
            if (action === 'fit') { this.zoom = 1; this.render(); }
            if (action === 'in') { this.zoom = Math.min(3, this.zoom + .25); this.render(); }
            if (action === 'out') { this.zoom = Math.max(.25, this.zoom - .25); this.render(); }
            if (action === 'download' && this.blob) {
                const url = URL.createObjectURL(this.blob);
                const link = document.createElement('a');
                link.href = url; link.download = 'document-preview.pdf'; link.click();
                setTimeout(() => URL.revokeObjectURL(url), 1000);
            }
        });
        this.resize = new ResizeObserver(() => { if (this.pdf && (dialog.open || this.inline)) this.render(); });
        this.resize.observe(this.body);
    }

    controls(ready) {
        this.dialog.querySelectorAll('[data-preview-action]').forEach(button => {
            button.disabled = button.dataset.previewAction !== 'close' && !ready;
        });
        this.dialog.querySelector('[data-preview-action="previous"]').disabled = !ready || this.page <= 1;
        this.dialog.querySelector('[data-preview-action="next"]').disabled = !ready || this.page >= (this.pdf?.numPages ?? 0);
    }

    release() {
        this.epoch++;
        this.abort?.abort();
        clearTimeout(this.timeout);
        this.task?.cancel(); this.task = null;
        this.loading?.destroy().catch(() => {}); this.loading = null;
        this.pdf = null; this.blob = null;
        this.canvas.width = this.canvas.height = 0;
        this.canvas.hidden = true;
        this.controls(false);
    }

    close() { this.release(); if (this.inline) this.dialog.hidden = true; else this.dialog.close(); }

    async open(endpoint) {
        this.release();
        const epoch = this.epoch;
        this.page = 1; this.zoom = 1;
        this.status.textContent = 'Loading preview…';
        const directLink = document.getElementById('preview-direct-link');
        if (directLink) directLink.href = endpoint;
        if (this.inline) this.dialog.hidden = false;
        else if (!this.dialog.open) this.dialog.showModal();
        this.abort = new AbortController();
        this.timeout = setTimeout(() => this.abort?.abort(), 90000);
        try {
            const url = new URL(endpoint, location.href);
            if (url.origin !== location.origin) throw new Error('Preview must come from MCARE.');
            const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store', signal: this.abort.signal, headers: { Accept: 'application/pdf' } });
            if (response.redirected || response.status === 401 || response.status === 419) throw new Error('Your session expired. Sign in again, then retry.');
            if (response.status === 429) {
                const delay = Number(response.headers.get('Retry-After'));
                const wait = Number.isFinite(delay) && delay > 0 && delay <= 86400 ? ` in ${Math.ceil(delay)} seconds` : ' shortly';
                throw new Error(`Preview limit reached. Try again${wait}.`);
            }
            if (response.status === 422 && response.headers.get('content-type')?.toLowerCase().startsWith('application/json')) {
                const data = await response.json();
                throw new Error(typeof data.message === 'string' ? data.message.slice(0, 500) : 'This layout needs review before previewing.');
            }
            if (!response.ok) throw new Error(response.status === 403 ? 'You do not have permission to preview this document.' : 'Preview could not be generated. Please retry.');
            if (!response.headers.get('content-type')?.toLowerCase().startsWith('application/pdf')) throw new Error('The server did not return a PDF. Sign in again or retry.');
            const usedServerFallback = response.headers.get('x-mcare-preview-fallback') === 'fpdf';
            const limit = 50 * 1024 * 1024;
            const reader = response.body.getReader(); const chunks = []; let size = 0;
            while (true) {
                const { done, value } = await reader.read(); if (done) break;
                size += value.byteLength;
                if (size > limit) { await reader.cancel(); throw new Error('The preview exceeds 50 MB.'); }
                chunks.push(value);
            }
            if (epoch !== this.epoch) return;
            const blob = new Blob(chunks, { type: 'application/pdf' });
            const bytes = new Uint8Array(await blob.arrayBuffer());
            if (new TextDecoder().decode(bytes.slice(0, 5)) !== '%PDF-') throw new Error('The preview is not a valid PDF.');
            await this.loadPdfBytes(bytes, blob, epoch);
            return { usedServerFallback };
        } catch (error) {
            if (epoch !== this.epoch) return;
            this.release();
            this.status.textContent = error.name === 'AbortError' ? 'Preview timed out. Please retry.' : error.message;
            throw error;
        } finally { if (epoch === this.epoch) clearTimeout(this.timeout); }
    }

    async openBytes(bytes, label = 'Live template preview') {
        this.release();
        const epoch = this.epoch;
        this.page = 1; this.zoom = 1;
        this.status.textContent = `Rendering ${label}…`;
        if (this.inline) this.dialog.hidden = false;
        else if (!this.dialog.open) this.dialog.showModal();
        try {
            const pdfBytes = bytes instanceof Uint8Array ? bytes : new Uint8Array(bytes);
            if (new TextDecoder().decode(pdfBytes.slice(0, 5)) !== '%PDF-') throw new Error('Generated preview is not a valid PDF.');
            const blob = new Blob([pdfBytes], { type: 'application/pdf' });
            await this.loadPdfBytes(pdfBytes, blob, epoch);
        } catch (error) {
            if (epoch !== this.epoch) return;
            this.release();
            this.status.textContent = error.message || 'Could not display generated preview.';
            throw error;
        }
    }

    async loadPdfBytes(bytes, blob, epoch) {
        const pdfjs = await loadPdfJs();
        if (epoch !== this.epoch) return;
        const task = pdfjs.getDocument({ data: bytes, isEvalSupported: false, enableXfa: false });
        this.loading = task;
        const pdf = await task.promise;
        if (epoch !== this.epoch) { await task.destroy(); return; }
        if (pdf.numPages > 200) throw new Error('This preview exceeds 200 pages.');
        this.pdf = pdf; this.blob = blob;
        await this.render();
    }

    async render() {
        if (!this.pdf) return;
        const pdf = this.pdf, epoch = this.epoch, serial = this.renderSerial = (this.renderSerial ?? 0) + 1;
        const prior = this.task; prior?.cancel();
        try {
            await prior?.promise.catch(() => {});
            const page = await pdf.getPage(this.page);
            if (epoch !== this.epoch || serial !== this.renderSerial) return;
            const unit = page.getViewport({ scale: 1 });
            const scale = Math.max(.1, (this.body.clientWidth - 32) / unit.width) * this.zoom;
            const viewport = page.getViewport({ scale });
            const ratio = Math.min(devicePixelRatio || 1, 2, Math.sqrt(12000000 / (viewport.width * viewport.height)));
            this.canvas.width = Math.ceil(viewport.width * ratio); this.canvas.height = Math.ceil(viewport.height * ratio);
            this.canvas.style.width = `${viewport.width}px`; this.canvas.style.height = `${viewport.height}px`;
            this.canvas.hidden = false;
            this.task = page.render({ canvasContext: this.canvas.getContext('2d'), viewport, transform: [ratio, 0, 0, ratio, 0, 0] });
            await this.task.promise;
            if (epoch !== this.epoch || serial !== this.renderSerial) return;
            this.status.textContent = `Page ${this.page} of ${pdf.numPages} · ${Math.round(this.zoom * 100)}%`;
            this.controls(true);
        } catch (error) {
            if (error.name !== 'RenderingCancelledException' && epoch === this.epoch && serial === this.renderSerial) {
                this.canvas.hidden = true; this.controls(false); this.status.textContent = 'This PDF page could not be displayed. Close and retry.';
            }
        }
    }
}

let viewer;
export function previewDocument(endpoint) {
    viewer ??= new StudioPreview(document.getElementById('preview-modal'));
    return viewer.open(endpoint);
}
export function previewPdfBytes(bytes, label) {
    viewer ??= new StudioPreview(document.getElementById('preview-modal'));
    return viewer.openBytes(bytes, label);
}
document.addEventListener('click', event => {
    const button = event.target.closest('[data-document-preview]');
    if (button) { event.preventDefault(); previewDocument(button.dataset.documentPreview).catch(() => {}); }
});

const original = document.getElementById('studio-original');
if (original) {
    const comparison = new StudioPreview(original);
    document.getElementById('btn-compare-original')?.addEventListener('click', () => comparison.open(original.dataset.endpoint));
    window.addEventListener('pagehide', () => comparison.release(), { once: true });
}

async function renderLiveThumbnails() {
    const canvases = document.querySelectorAll('canvas[data-live-pdf-thumbnail]');
    if (!canvases.length) return;
    let pdfjs = null;
    try {
        pdfjs = await loadPdfJs();
    } catch {
        return;
    }

    for (const canvas of canvases) {
        const endpoint = canvas.dataset.livePdfThumbnail;
        const container = canvas.parentElement;
        const loader = container?.querySelector('[data-live-pdf-loading]');
        if (!endpoint || endpoint === '#') {
            if (loader) loader.textContent = 'No template preview available';
            continue;
        }
        try {
            const url = new URL(endpoint, location.href);
            if (url.origin !== location.origin) continue;
            const res = await fetch(url, { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/pdf' } });
            if (!res.ok || !res.headers.get('content-type')?.toLowerCase().startsWith('application/pdf')) {
                throw new Error('Unavailable');
            }
            const bytes = new Uint8Array(await res.arrayBuffer());
            const task = pdfjs.getDocument({ data: bytes, isEvalSupported: false, enableXfa: false });
            const pdf = await task.promise;
            const page = await pdf.getPage(1);
            const targetWidth = Math.max(container?.clientWidth || 380, 320);
            const unit = page.getViewport({ scale: 1 });
            const scale = (targetWidth / unit.width) * Math.min(window.devicePixelRatio || 1, 2);
            const viewport = page.getViewport({ scale });
            canvas.width = Math.ceil(viewport.width);
            canvas.height = Math.ceil(viewport.height);
            await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
            loader?.remove();
            await task.destroy();
        } catch {
            if (loader) {
                loader.innerHTML = '<span class="px-3 text-center">Click to open full PDF preview</span>';
            }
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => { renderLiveThumbnails(); });
} else {
    renderLiveThumbnails();
}
