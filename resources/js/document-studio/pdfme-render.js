import { generate } from '@pdfme/generator';
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

export async function renderPdfmeTemplateToBytes({ templateState = {}, designerTemplate = null, values = {}, assets = [], plugins = PDFME_PLUGINS } = {}) {
    const source = designerTemplate ? structuredClone(designerTemplate) : structuredClone(templateState);
    let basePdf = source.basePdf;
    if (basePdf instanceof Uint8Array) {
        basePdf = new Uint8Array(basePdf);
    } else if (basePdf instanceof ArrayBuffer) {
        basePdf = new Uint8Array(basePdf.slice(0));
    }

    const basePdfAssetId = templateState.base_pdf_asset_id || source.base_pdf_asset_id;
    const basePdfUrl = templateState.base_pdf_url || templateState.basePdfUrl || source.base_pdf_url;

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
        const page = templateState.page || { width_mm: 279.4, height_mm: 215.9 };
        basePdf = { width: page.width_mm || 279.4, height: page.height_mm || 215.9, padding: [10, 10, 10, 10] };
    }

    let rawSchemas = source.schemas;
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
