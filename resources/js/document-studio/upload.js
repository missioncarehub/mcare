import { loadPdfJs } from '../pdf-loader.js';

const $ = id => document.getElementById(id);
const status = $('conversion-status'), canvas = $('conversion-canvas');
const form = $('pdf-conversion-form'), fileInput = $('conversion-file');
let loading, pdf, pageNumber = 1, regions = [], viewport, renderTask, epoch = 0, serial = 0;
let converting = false;
const mm = 25.4 / 72;
const source = () => form.querySelector('[name="source"]:checked')?.value ?? 'upload';
const selectedFile = () => fileInput.files[0];
const isPdf = file => file && (file.type === 'application/pdf' || /\.pdf$/i.test(file.name));
const editablePdf = () => source() === 'upload' && isPdf(selectedFile()) && (form.querySelector('[name="pdf_mode"]:checked')?.value ?? 'editable') === 'editable';
function updateControls() {
    const upload = source() === 'upload', file = selectedFile();
    for (const panel of form.querySelectorAll('[data-source-panel]')) panel.hidden = panel.dataset.sourcePanel !== source();
    fileInput.disabled = !upload || converting;
    fileInput.required = upload;
    const options = $('pdf-options');
    if (options) options.hidden = !upload || !isPdf(file);
    for (const input of form.querySelectorAll('[name="pdf_mode"]')) input.disabled = !upload || !isPdf(file) || converting;
    for (const input of form.querySelectorAll('[name="source"], [name="document_kind"], [name="name"], [name="training_program_id"]')) input.disabled = converting || (input.name === 'source' && input.value === 'current' && form.elements.document_kind.value === 'general');
    $('conversion-review').hidden = !editablePdf() || !pdf;
    $('conversion-submit').disabled = converting || (upload && (!file || (isPdf(file) ? !pdf : !/\.(docx|png|jpe?g)$/i.test(file.name)) || file.size > 15 * 1024 * 1024));
    $('conversion-cancel').hidden = !converting || !editablePdf();
}
function updateRegions() {
    $('conversion-regions').value = JSON.stringify(regions);
    $('region-list').replaceChildren();
    regions.forEach((region, index) => {
        const item = document.createElement('li'), button = document.createElement('button');
        item.textContent = `Page ${region.page}: ${region.content} `;
        button.type = 'button'; button.textContent = 'Remove'; button.style.minHeight = '44px';
        button.addEventListener('click', () => { regions.splice(index, 1); updateRegions(); render(); });
        item.append(button); $('region-list').append(item);
    });
}
async function render() {
    if (!pdf) return;
    const current = ++serial, version = epoch, previous = renderTask;
    previous?.cancel(); await previous?.promise.catch(() => {});
    try {
        const page = await pdf.getPage(pageNumber);
        if (current !== serial || version !== epoch) return;
        viewport = page.getViewport({ scale: 1 });
        const scale = Math.min(1.5, 1800 / Math.max(viewport.width, viewport.height));
        const raster = page.getViewport({ scale });
        canvas.width = Math.ceil(raster.width); canvas.height = Math.ceil(raster.height);
        renderTask = page.render({ canvasContext: canvas.getContext('2d'), viewport: raster });
        await renderTask.promise;
        if (current !== serial || version !== epoch) return;
        const context = canvas.getContext('2d');
        for (const r of regions.filter(r => r.page === pageNumber)) {
            context.strokeStyle = '#4f46e5'; context.lineWidth = 2;
            context.strokeRect(r.x / mm * scale, r.y / mm * scale, r.width / mm * scale, r.height / mm * scale);
        }
        const text = await page.getTextContent();
        if (current !== serial || version !== epoch) return;
        status.textContent = editablePdf()
            ? (text.items.some(item => item.str?.trim()) ? 'Text detected. Review the editable text after import.' : 'No selectable text detected. Add manual replacement regions before importing.')
            : 'The PDF will remain a fixed background. Add editable fields in the editor.';
        $('conversion-page').textContent = `Page ${pageNumber} of ${pdf.numPages}`;
        $('conversion-previous').disabled = pageNumber === 1; $('conversion-next').disabled = pageNumber === pdf.numPages;
    } catch (error) { if (error.name !== 'RenderingCancelledException' && version === epoch) status.textContent = 'This PDF page could not be displayed.'; }
}
async function inspectFile() {
    const version = ++epoch; serial++; renderTask?.cancel(); await loading?.destroy().catch(() => {});
    if (version !== epoch) return;
    pdf = null; viewport = null; start = null; regions = []; updateRegions(); updateControls();
    if (source() !== 'upload') { status.textContent = ''; return; }
    const file = selectedFile();
    if (!file) { status.textContent = 'Choose a PDF, DOCX or image.'; return; }
    try {
        if (file.size > 15 * 1024 * 1024) throw new Error('Choose a file up to 15 MB.');
        if (!isPdf(file)) {
            if (!/\.(docx|png|jpe?g)$/i.test(file.name)) throw new Error('Choose a PDF, DOCX, PNG or JPG file.');
            status.textContent = /\.docx$/i.test(file.name) ? 'Open the document to review its text and formatting.' : 'The image will be a fixed background. Add editable fields in the editor.';
            return;
        }
        status.textContent = 'Reading PDF…';
        const bytes = new Uint8Array(await file.arrayBuffer()), pdfjs = await loadPdfJs();
        if (version !== epoch) return;
        const task = pdfjs.getDocument({ data: bytes, isEvalSupported: false, enableXfa: false, maxImageSize: 16000000 });
        loading = task;
        const document = await task.promise;
        if (version !== epoch) { await task.destroy(); return; }
        if (document.numPages > 20) throw new Error('Choose a PDF with at most 20 pages.');
        pdf = document; pageNumber = 1;
        await render();
    } catch (error) { if (version === epoch) { pdf = null; status.textContent = error.name === 'PasswordException' ? 'Encrypted PDFs are unsupported.' : error.message; } }
    finally { if (version === epoch) updateControls(); }
}
fileInput.addEventListener('change', inspectFile);
for (const input of form.querySelectorAll('[name="source"]')) input.addEventListener('change', inspectFile);
for (const input of form.querySelectorAll('[name="pdf_mode"]')) input.addEventListener('change', () => {
    updateControls();
    if (pdf) render();
});
form.elements.document_kind.addEventListener('change', () => {
    const current = form.querySelector('[name="source"][value="current"]');
    if (current) {
        current.disabled = form.elements.document_kind.value === 'general';
        if (current.disabled && current.checked) { form.querySelector('[name="source"][value="blank"]').checked = true; inspectFile(); }
    }
});
$('conversion-previous').addEventListener('click', () => { if (pageNumber > 1) { pageNumber--; render(); } });
$('conversion-next').addEventListener('click', () => { if (pdf && pageNumber < pdf.numPages) { pageNumber++; render(); } });
$('region-binding').addEventListener('change', event => { if (event.target.value) $('region-content').value = `{{${event.target.value.replace(/^\{\{|\}\}$/g, '')}}}`; });
let start;
const point = event => { const r = canvas.getBoundingClientRect(); return { x: Math.max(0, Math.min(1, (event.clientX - r.left) / r.width)) * viewport.width * mm, y: Math.max(0, Math.min(1, (event.clientY - r.top) / r.height)) * viewport.height * mm }; };
canvas.addEventListener('pointerdown', event => { if (!viewport) return; start = point(event); canvas.setPointerCapture(event.pointerId); });
canvas.addEventListener('pointerup', event => {
    if (!start) return; const end = point(event);
    for (const [key, value] of Object.entries({ x: Math.min(start.x, end.x), y: Math.min(start.y, end.y), width: Math.abs(start.x - end.x), height: Math.abs(start.y - end.y) })) $('region-' + key).value = value.toFixed(1);
    start = null; $('region-content').focus();
});
canvas.addEventListener('pointercancel', () => { start = null; });
$('region-add').addEventListener('click', () => {
    if (!pdf || !viewport) return;
    const region = { page: pageNumber, color: $('region-color').value, content: $('region-content').value.trim() };
    for (const key of ['x', 'y', 'width', 'height']) region[key] = Number($('region-' + key).value);
    if (regions.length >= 100 || !region.content || region.x < 0 || region.y < 0 || region.width <= 0 || region.height <= 0 || region.x + region.width > viewport.width * mm || region.y + region.height > viewport.height * mm) { status.textContent = 'Enter text and a region within the current page (maximum 100 regions).'; return; }
    regions.push(region); updateRegions(); render();
});
$('pdf-conversion-form').addEventListener('submit', async event => {
    event.preventDefault(); if (converting) return;
    const form = event.currentTarget, body = new FormData(form);
    if (!editablePdf()) body.set('regions', '[]');
    const isConversion = editablePdf();
    converting = true; updateControls();
    status.textContent = isConversion ? 'Converting document… This may take up to 90 seconds.' : 'Opening draft…';
    try {
        const response = await fetch(form.action, { method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json' }, body });
        if (response.redirected && new URL(response.url).origin === location.origin && new URL(response.url).pathname.endsWith('/edit')) {
            form.dispatchEvent(new Event('admin-form-clean'));
            location.assign(response.url); return;
        }
        const data = await response.json();
        status.textContent = Object.values(data.errors ?? {}).flat()[0] || data.message || 'The draft could not be opened. Review the form and retry.';
    } catch { status.textContent = 'The connection failed. Check the document library before retrying.'; }
    finally {
        converting = false; updateControls();
        form.elements.document_kind.dispatchEvent(new Event('change'));
        form.elements.operation.value = crypto.randomUUID();
    }
});
updateControls();
form.elements.document_kind.dispatchEvent(new Event('change'));
$('conversion-cancel').addEventListener('click', async () => {
    const form = $('pdf-conversion-form');
    try {
        const response = await fetch(form.dataset.cancelUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': form.elements._token.value }, body: JSON.stringify({ operation: form.elements.operation.value }) });
        if (!response.ok) throw new Error();
        status.textContent = 'Cancellation requested. Waiting for the server to finish cleanup…';
    } catch { status.textContent = 'Cancellation could not be confirmed. Check the document library before retrying.'; }
});
