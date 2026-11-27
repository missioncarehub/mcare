import { PDFDocument } from 'pdf-lib';
import { text, image, signature, table, barcodes, line, rectangle, ellipse, svg } from '@pdfme/schemas';
import { normalizeStudioSchema, studioTables, tableBinding } from './tables.js';
import { getBrowserPdfmeFonts } from './fonts.js';

export const PDFME_PLUGINS = {
    text,
    image,
    signature,
    table,
    line,
    rectangle,
    ellipse,
    svg,
    ...barcodes,
};

let generatorModulePromise = null;
function loadPdfmeGenerator() {
    if (!generatorModulePromise) {
        generatorModulePromise = import('@pdfme/generator');
    }
    return generatorModulePromise;
}

export function warmPdfmePreviewRuntime() {
    loadPdfmeGenerator().catch(() => {});
    import('../pdf-loader.js').then(m => m.loadPdfJs()).catch(() => {});
}

export function extractTemplateFontKeys(schemas = []) {
    const keys = new Set(['NotoSans']);
    (schemas || []).forEach((page) => {
        (page || []).forEach((field) => {
            if (!field || typeof field !== 'object') return;
            if (field.fontName) keys.add(field.fontName);
            if (field.headStyles?.fontName) keys.add(field.headStyles.fontName);
            if (field.bodyStyles?.fontName) keys.add(field.bodyStyles.fontName);
        });
    });
    return [...keys];
}

const fontDataCache = new Map();
const fontPromiseCache = new Map();
const dataUrlCache = new Map();
const dataUrlPromiseCache = new Map();
const binaryCache = new Map();
const binaryPromiseCache = new Map();
const basePdfBytesCache = new Map();

export async function fetchBinaryCached(url) {
    if (!url) return null;
    const key = String(url);
    if (binaryCache.has(key)) {
        return new Uint8Array(binaryCache.get(key));
    }
    if (!binaryPromiseCache.has(key)) {
        binaryPromiseCache.set(key, (async () => {
            const res = await fetch(key, { credentials: 'same-origin' });
            if (!res.ok) throw new Error(`Could not load binary resource (HTTP ${res.status}).`);
            const bytes = new Uint8Array(await res.arrayBuffer());
            binaryCache.set(key, bytes);
            return bytes;
        })().catch((err) => {
            binaryPromiseCache.delete(key);
            throw err;
        }));
    }
    const bytes = await binaryPromiseCache.get(key);
    return bytes ? new Uint8Array(bytes) : null;
}

export async function loadBrowserFonts(onlyKeys = null) {
    const allConfigs = getBrowserPdfmeFonts();
    const targetKeys = Array.isArray(onlyKeys) && onlyKeys.length > 0
        ? [...new Set(['NotoSans', ...onlyKeys])].filter(k => allConfigs[k])
        : Object.keys(allConfigs);

    await Promise.all(targetKeys.map(async (key) => {
        if (fontDataCache.has(key)) return;
        if (!fontPromiseCache.has(key)) {
            const cfg = allConfigs[key];
            fontPromiseCache.set(key, (async () => {
                try {
                    const res = await fetch(cfg.data, { credentials: 'same-origin' });
                    if (res.ok) {
                        fontDataCache.set(key, {
                            data: new Uint8Array(await res.arrayBuffer()),
                            ...(cfg.fallback ? { fallback: true } : {}),
                        });
                    }
                } catch {
                    // Skip unavailable font cleanly
                }
            })());
        }
        await fontPromiseCache.get(key);
    }));

    const font = {};
    for (const [key, entry] of fontDataCache.entries()) {
        font[key] = entry;
    }
    return font;
}

async function fetchAsDataUrl(url) {
    if (!url || String(url).startsWith('data:')) return String(url || '');
    const key = String(url);
    if (dataUrlCache.has(key)) return dataUrlCache.get(key);
    if (!dataUrlPromiseCache.has(key)) {
        dataUrlPromiseCache.set(key, (async () => {
            try {
                const res = await fetch(key, { credentials: 'same-origin' });
                if (!res.ok) return '';
                const blob = await res.blob();
                const dataUrl = await new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onloadend = () => resolve(reader.result);
                    reader.onerror = reject;
                    reader.readAsDataURL(blob);
                });
                if (dataUrl) dataUrlCache.set(key, dataUrl);
                return dataUrl;
            } catch {
                return '';
            }
        })().finally(() => {
            dataUrlPromiseCache.delete(key);
        }));
    }
    return dataUrlPromiseCache.get(key);
}

async function convertToPngBytesIfNeeded(bytes, mime, forceCanvas = false) {
    if (!forceCanvas && (mime === 'image/png' || mime === 'image/jpeg')) {
        return { bytes, isPng: mime === 'image/png' };
    }
    const blob = new Blob([bytes], { type: mime || 'image/png' });
    const imgUrl = URL.createObjectURL(blob);
    try {
        const img = await new Promise((resolve, reject) => {
            const el = new Image();
            el.onload = () => resolve(el);
            el.onerror = reject;
            el.src = imgUrl;
        });
        const canvas = document.createElement('canvas');
        canvas.width = img.naturalWidth || img.width || 1600;
        canvas.height = img.naturalHeight || img.height || 1200;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0);
        const pngBlob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
        return { bytes: new Uint8Array(await pngBlob.arrayBuffer()), isPng: true };
    } finally {
        URL.revokeObjectURL(imgUrl);
    }
}

export async function buildBasePdfFromImageUrl(url, widthMm = 279.4, heightMm = 215.9) {
    if (!url) return null;
    let fetchUrl = String(url);
    try {
        const parsed = new URL(fetchUrl, location.href);
        if (parsed.pathname.startsWith('/admin/document-studio/assets/')) {
            fetchUrl = parsed.pathname + parsed.search;
        }
    } catch {
        // Keep original URL string cleanly
    }

    const cacheKey = `${fetchUrl}|${widthMm}x${heightMm}`;
    if (basePdfBytesCache.has(cacheKey)) {
        return new Uint8Array(basePdfBytesCache.get(cacheKey));
    }

    const res = await fetch(fetchUrl, { credentials: 'same-origin' });
    if (!res.ok) throw new Error(`Could not load template background picture (HTTP ${res.status}).`);
    const rawBytes = new Uint8Array(await res.arrayBuffer());
    const contentType = (res.headers.get('content-type') || '').toLowerCase();
    const isJpeg = contentType.includes('jpeg') || contentType.includes('jpg') || (rawBytes[0] === 0xff && rawBytes[1] === 0xd8);
    const isPng = contentType.includes('png') || (rawBytes[0] === 0x89 && rawBytes[1] === 0x50);
    const mime = isJpeg ? 'image/jpeg' : (isPng ? 'image/png' : (contentType || 'image/png'));

    const normalized = await convertToPngBytesIfNeeded(rawBytes, mime);
    const ptPerMm = 72 / 25.4;
    const widthPt = Number(widthMm) * ptPerMm;
    const heightPt = Number(heightMm) * ptPerMm;

    const doc = await PDFDocument.create();
    const page = doc.addPage([widthPt, heightPt]);
    let embedded;
    try {
        embedded = normalized.isPng
            ? await doc.embedPng(normalized.bytes)
            : await doc.embedJpg(normalized.bytes);
    } catch {
        // Re-encode non-standard/CMYK JPEG or interlaced PNG via browser canvas
        const safePng = await convertToPngBytesIfNeeded(rawBytes, mime, true);
        embedded = await doc.embedPng(safePng.bytes);
    }
    page.drawImage(embedded, { x: 0, y: 0, width: widthPt, height: heightPt });

    const pdfBytes = new Uint8Array(await doc.save());
    basePdfBytesCache.set(cacheKey, pdfBytes);
    return new Uint8Array(pdfBytes);
}

function interpolateStudioTokens(text, values = {}) {
    return String(text).replace(/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/g, (match, key) => {
        const value = values[key];
        return value !== undefined && value !== null && typeof value !== 'object' ? String(value) : match;
    });
}

export async function renderPdfmeTemplateToBytes({ templateState = {}, designerTemplate = null, values = {}, assets = [], plugins = PDFME_PLUGINS } = {}) {
    const source = designerTemplate ? structuredClone(designerTemplate) : structuredClone(templateState);
    let basePdf = source.basePdf;
    if (basePdf instanceof Uint8Array) {
        basePdf = new Uint8Array(basePdf);
    } else if (basePdf instanceof ArrayBuffer) {
        basePdf = new Uint8Array(basePdf.slice(0));
    }

    let rawSchemas = source.schemas;
    let legacyBgUrl = null;
    if (Array.isArray(rawSchemas?.[0])) {
        const bgField = rawSchemas[0].find(f => String(f?.name || '').startsWith('template_bg_'));
        if (bgField) {
            const matched = bgField.asset_id ? assets.find(item => Number(item.id) === Number(bgField.asset_id)) : null;
            legacyBgUrl = matched?.url || bgField.content || (bgField.asset_id ? `/admin/document-studio/assets/${bgField.asset_id}` : null);
            rawSchemas = [rawSchemas[0].filter(f => !String(f?.name || '').startsWith('template_bg_')), ...rawSchemas.slice(1)];
        }
    }

    const pageWidthMm = templateState.page?.width_mm || (templateState.document_kind === 'cotc' ? 279.4 : 210);
    const pageHeightMm = templateState.page?.height_mm || (templateState.document_kind === 'cotc' ? 215.9 : 297);
    const bgAssetId = templateState.background_asset_id || source.background_asset_id;
    const basePdfAssetId = templateState.base_pdf_asset_id || source.base_pdf_asset_id;
    const basePdfUrl = templateState.base_pdf_url || templateState.basePdfUrl || source.base_pdf_url;

    if (!(basePdf instanceof Uint8Array) && (bgAssetId || legacyBgUrl)) {
        const matchedBg = bgAssetId ? assets.find(item => Number(item.id) === Number(bgAssetId)) : null;
        const bgUrl = matchedBg?.url || (bgAssetId ? `/admin/document-studio/assets/${bgAssetId}` : legacyBgUrl);
        if (bgUrl) {
            basePdf = await buildBasePdfFromImageUrl(bgUrl, pageWidthMm, pageHeightMm);
        }
    }
    if (!basePdf && basePdfAssetId) {
        const matched = assets.find(item => Number(item.id) === Number(basePdfAssetId));
        const url = matched?.url || `/admin/document-studio/assets/${basePdfAssetId}`;
        try {
            basePdf = await fetchBinaryCached(url);
        } catch {
            // Fall through
        }
    }
    if (!basePdf && basePdfUrl) {
        try {
            basePdf = await fetchBinaryCached(basePdfUrl);
        } catch {
            // Fall through
        }
    }
    if (!basePdf) {
        basePdf = { width: pageWidthMm, height: pageHeightMm, padding: [10, 10, 10, 10] };
    }
    if (!rawSchemas && templateState.elements) {
        rawSchemas = [templateState.elements.map((el, idx) => ({
            name: el.id || el.binding || `field_${idx}`,
            type: el.type === 'qr' ? 'qrcode' : (el.type || 'text'),
            position: { x: el.x || 10, y: el.y || 10 },
            width: el.width || 60,
            height: el.height || 10,
            fontSize: el.fontSize || 12,
            ...(el.fontName ? { fontName: el.fontName } : {}),
            ...(el.fontColor ? { fontColor: el.fontColor } : {}),
            alignment: el.textAlign || 'left',
            content: el.text || el.content || el.binding || '',
            binding: el.binding || '',
            ...(el.asset_id ? { asset_id: el.asset_id } : {}),
        }))];
    }

    const schemas = (rawSchemas && rawSchemas.length > 0 ? rawSchemas : [[]]).map(page =>
        page.map(field => normalizeStudioSchema(field, plugins))
    );
    const inputRow = {};

    for (const page of schemas) {
        for (const field of page) {
            let val = undefined;
            if (['image', 'signature'].includes(field.type)) {
                const assetId = field.asset_id;
                const matched = assetId ? assets.find(item => Number(item.id) === Number(assetId)) : null;
                const src = matched?.url || field.content || field.src || field.url || (assetId ? `/admin/document-studio/assets/${assetId}` : '');
                val = await fetchAsDataUrl(src);
            }

            const rawContent = field.content ?? field.text;
            const hasInlineToken = typeof rawContent === 'string' && /\{\{\s*[a-zA-Z0-9_]+\s*\}\}/.test(rawContent);

            if (val === undefined && hasInlineToken) {
                val = interpolateStudioTokens(rawContent, values);
            }
            if (val === undefined && (field.type === 'table' || !rawContent)) {
                if (values[field.name] !== undefined) val = values[field.name];
                else if (field.binding) {
                    const cleanKey = field.binding.replace(/[{}]/g, '').trim();
                    if (values[cleanKey] !== undefined) val = values[cleanKey];
                    else if (values[field.binding] !== undefined) val = values[field.binding];
                    else val = interpolateStudioTokens(field.binding, values);
                }
            }
            if (val === undefined && typeof rawContent === 'string' && rawContent !== '') {
                val = rawContent;
            }
            if (val === undefined && values[field.name] !== undefined) {
                val = values[field.name];
            }
            if (val === undefined) val = '';

            if (field.type === 'table' && Array.isArray(val)) {
                if (val.length > 0 && typeof val[0] === 'object' && !Array.isArray(val[0])) {
                    const keys = studioTables[tableBinding(field)]?.keys || Object.keys(val[0]);
                    val = JSON.stringify(val.map(row => keys.map(k => String(row[k] ?? ''))));
                } else {
                    val = JSON.stringify(val);
                }
            } else if (typeof val !== 'string') {
                val = String(val);
            }

            inputRow[field.name] = val;
            if (field.readOnly) {
                field.content = val;
            }
        }
    }

    const neededKeys = extractTemplateFontKeys(schemas);
    const [{ generate }, font] = await Promise.all([
        loadPdfmeGenerator(),
        loadBrowserFonts(neededKeys),
    ]);
    for (const page of schemas) {
        for (const field of page) {
            if (field.fontName && !font[field.fontName]) {
                field.fontName = font.NotoSans ? 'NotoSans' : undefined;
            }
            if (field.type === 'table') {
                if (field.headStyles?.fontName && !font[field.headStyles.fontName]) {
                    field.headStyles.fontName = font.NotoSans ? 'NotoSans' : undefined;
                }
                if (field.bodyStyles?.fontName && !font[field.bodyStyles.fontName]) {
                    field.bodyStyles.fontName = font.NotoSans ? 'NotoSans' : undefined;
                }
            }
        }
    }

    return generate({
        template: { basePdf, schemas },
        inputs: [inputRow],
        plugins,
        options: Object.keys(font).length ? { font } : undefined,
    });
}
