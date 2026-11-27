import { generate } from '@pdfme/generator';
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

let cachedBrowserFonts = null;

export async function loadBrowserFonts() {
    if (cachedBrowserFonts) return cachedBrowserFonts;
    const fontEntries = Object.entries(getBrowserPdfmeFonts());
    const font = {};
    await Promise.all(fontEntries.map(async ([key, cfg]) => {
        try {
            const res = await fetch(cfg.data, { credentials: 'same-origin' });
            if (res.ok) {
                font[key] = { data: new Uint8Array(await res.arrayBuffer()), ...(cfg.fallback ? { fallback: true } : {}) };
            }
        } catch {
            // Skip unavailable font cleanly
        }
    }));
    if (Object.keys(font).length > 0) {
        cachedBrowserFonts = font;
    }
    return font;
}

async function fetchAsDataUrl(url) {
    if (!url || String(url).startsWith('data:')) return String(url || '');
    try {
        const res = await fetch(url, { credentials: 'same-origin' });
        if (!res.ok) return '';
        const blob = await res.blob();
        return await new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onloadend = () => resolve(reader.result);
            reader.onerror = reject;
            reader.readAsDataURL(blob);
        });
    } catch {
        return '';
    }
}

async function convertToPngBytesIfNeeded(bytes, mime) {
    if (mime === 'image/png' || mime === 'image/jpeg') {
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
    const res = await fetch(url, { credentials: 'same-origin' });
    if (!res.ok) throw new Error('Could not load template background picture.');
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
    const embedded = normalized.isPng
        ? await doc.embedPng(normalized.bytes)
        : await doc.embedJpg(normalized.bytes);
    page.drawImage(embedded, { x: 0, y: 0, width: widthPt, height: heightPt });

    return new Uint8Array(await doc.save());
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
        const res = await fetch(url, { credentials: 'same-origin' });
        if (res.ok) basePdf = new Uint8Array(await res.arrayBuffer());
    }
    if (!basePdf && basePdfUrl) {
        const res = await fetch(basePdfUrl, { credentials: 'same-origin' });
        if (res.ok) basePdf = new Uint8Array(await res.arrayBuffer());
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
            const hasInlineToken = typeof rawContent === 'string' && /\{\{\w+\}\}/.test(rawContent);

            if (val === undefined && hasInlineToken) {
                let interpolated = rawContent;
                for (const [k, v] of Object.entries(values)) {
                    if (v !== undefined && v !== null && typeof v !== 'object') {
                        interpolated = interpolated.replaceAll(`{{${k}}}`, String(v));
                    }
                }
                val = interpolated;
            }
            if (val === undefined && (field.type === 'table' || !rawContent)) {
                if (values[field.name] !== undefined) val = values[field.name];
                else if (field.binding) {
                    const cleanKey = field.binding.replace(/[{}]/g, '').trim();
                    if (values[cleanKey] !== undefined) val = values[cleanKey];
                    else if (values[field.binding] !== undefined) val = values[field.binding];
                    else val = field.binding.replace(/\{\{(\w+)\}\}/g, (token, key) => values[key] === undefined || typeof values[key] === 'object' ? token : String(values[key]));
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

    const font = await loadBrowserFonts();
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
