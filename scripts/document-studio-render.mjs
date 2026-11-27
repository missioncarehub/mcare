#!/usr/bin/env node
import { paginateSourceLayout } from './document-studio-source-layout.mjs';

import fs from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { normalizeStudioSchema, studioTables, tableBinding } from '../resources/js/document-studio/tables.js';
import { CERTIFICATE_FONTS } from '../resources/js/document-studio/fonts.js';
import path from 'node:path';
import { generate } from '@pdfme/generator';
import { text, image, signature, table, barcodes, line, rectangle, ellipse, svg } from '@pdfme/schemas';
import { PDFDocument, rgb, StandardFonts, degrees, pushGraphicsState, popGraphicsState, translate, rotateRadians } from 'pdf-lib';
import { embedStudioImage, drawStudioImage } from './document-studio-image.mjs';

async function main() {
    const args = process.argv.slice(2);
    if (args.includes('--smoke-test')) {
        try {
            const { renderWord } = await import('./document-studio-word.mjs');
            const pdfmeBytes = await renderPdfme({
                page: { width_mm: 210, height_mm: 297 },
                elements: [{ id: 'smoke', type: 'text', content: 'Smoke Test' }],
                values: { smoke: 'Smoke Test' },
            });
            const wordBytes = await renderWord({
                page: { width_mm: 210, height_mm: 297 },
                html: '<p>Smoke Test Word</p>',
                values: {},
            });
            const fabricBytes = await renderFabric({
                page: { width_mm: 210, height_mm: 297 },
                elements: [{ id: 'fsmoke', type: 'text', text: 'Smoke Test Fabric' }],
                values: {},
            });
            if (pdfmeBytes.length > 0 && wordBytes.length > 0 && fabricBytes.length > 0) {
                console.log('Document Studio renderer smoke test PASSED: All 3 engines generated valid PDFs.');
                process.exit(0);
            }
        } catch (err) {
            console.error('Document Studio renderer smoke test FAILED:', err);
            process.exit(1);
        }
    }

    let inputPath = null;
    let outputPath = null;

    for (let i = 0; i < args.length; i++) {
        if (args[i] === '--input' && args[i + 1]) {
            inputPath = args[i + 1];
            i++;
        } else if (args[i] === '--output' && args[i + 1]) {
            outputPath = args[i + 1];
            i++;
        }
    }

    if (!inputPath || !outputPath) {
        console.error('Usage: node scripts/document-studio-render.mjs --input <path> --output <path>');
        process.exit(1);
    }

    const payloadRaw = await fs.readFile(inputPath, 'utf-8');
    const payload = JSON.parse(payloadRaw);

    const engine = payload.engine || 'pdfme';
    let pdfBytes = null;

    if (engine === 'fabric' || engine === 'canvas') {
        pdfBytes = await renderFabric(payload);
    } else if (engine === 'rk_word') {
        const { renderWord } = await import('./document-studio-word.mjs');
        pdfBytes = await renderWord(payload);
    } else {
        pdfBytes = await renderPdfme(payload);
    }

    if (payload.preview) {
        const preview = await PDFDocument.load(pdfBytes);
        const font = await preview.embedFont(StandardFonts.HelveticaBold);
        for (const page of preview.getPages()) page.drawText('PREVIEW - NOT FOR ISSUANCE', { x: 24, y: page.getHeight()/3, size:24, font, color:rgb(.5,.5,.5), opacity:.25, rotate:degrees(30) });
        pdfBytes = await preview.save();
    }
    await fs.writeFile(outputPath, Buffer.from(pdfBytes));
    process.exit(0);
}

let cachedNodeFonts = null;
function extractNodeFontKeys(schemas = [], elements = []) {
    const keys = new Set(['NotoSans']);
    for (const page of schemas || []) {
        for (const field of page || []) {
            if (!field || typeof field !== 'object') continue;
            if (field.fontName) keys.add(field.fontName);
            if (field.headStyles?.fontName) keys.add(field.headStyles.fontName);
            if (field.bodyStyles?.fontName) keys.add(field.bodyStyles.fontName);
        }
    }
    for (const el of elements || []) {
        if (!el || typeof el !== 'object') continue;
        if (el.fontName) keys.add(el.fontName);
    }
    return keys;
}

async function getNodePdfmeFonts(requiredKeys = null) {
    if (!cachedNodeFonts) cachedNodeFonts = {};
    const __dirname = path.dirname(fileURLToPath(import.meta.url));
    const fontsDir = path.resolve(__dirname, '../public/fonts/certificate');
    const wanted = requiredKeys && requiredKeys.size > 0
        ? CERTIFICATE_FONTS.filter(f => f.fallback || requiredKeys.has(f.key))
        : CERTIFICATE_FONTS;

    await Promise.all(wanted.map(async (font) => {
        if (cachedNodeFonts[font.key]) return;
        const fontPath = path.join(fontsDir, font.file);
        try {
            const buf = await fs.readFile(fontPath);
            cachedNodeFonts[font.key] = {
                data: new Uint8Array(buf),
                ...(font.fallback ? { fallback: true } : {}),
            };
        } catch {
            // Skip missing optional font file cleanly
        }
    }));

    const fontMap = {};
    for (const font of wanted) {
        if (cachedNodeFonts[font.key]) fontMap[font.key] = cachedNodeFonts[font.key];
    }
    return fontMap;
}

function interpolateStudioTokens(text, values = {}) {
    return String(text).replace(/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/g, (match, key) => {
        const value = values[key];
        return value !== undefined && value !== null && typeof value !== 'object' ? String(value) : match;
    });
}

async function renderPdfme(payload) {
    const nodeFonts = await getNodePdfmeFonts(extractNodeFontKeys(payload.schemas, payload.elements));
    const plugins = {
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

    const pageSettings = payload.page || {
        width_mm: 210,
        height_mm: 297,
    };

    let basePdf = payload.basePdfVerifiedPath ? new Uint8Array(await fs.readFile(payload.basePdfVerifiedPath)) : payload.basePdf;
    if (!basePdf && payload.base_pdf_path) {
        try {
            basePdf = new Uint8Array(await fs.readFile(payload.base_pdf_path));
        } catch (e) {}
    }
    if (!basePdf) {
        if (payload.basePdfVerifiedPath) {
            const buf = await fs.readFile(payload.basePdfVerifiedPath);
            basePdf = new Uint8Array(buf);
        } else {
            basePdf = {
                width: pageSettings.width_mm || 210,
                height: pageSettings.height_mm || 297,
                padding: [10, 10, 10, 10],
            };
        }
    }

    // Convert Studio elements to pdfme schemas if necessary
    let schemas = payload.schemas;
    let legacyBgFile = null;
    if (Array.isArray(schemas?.[0])) {
        const bgField = schemas[0].find(f => String(f?.name || '').startsWith('template_bg_'));
        if (bgField) {
            let bgAssetId = bgField.asset_id;
            if (!bgAssetId && typeof bgField.content === 'string') {
                const m = bgField.content.match(/\/admin\/document-studio\/assets\/(\d+)$/);
                if (m) bgAssetId = Number(m[1]);
            }
            if (bgAssetId && payload.asset_paths?.[bgAssetId]) {
                legacyBgFile = payload.asset_paths[bgAssetId];
            }
            schemas = [schemas[0].filter(f => !String(f?.name || '').startsWith('template_bg_')), ...schemas.slice(1)];
        }
    }

    const bgFile = payload.backgroundVerifiedPath || legacyBgFile;
    if (bgFile) {
        const mmToPt = 72 / 25.4;
        const ptW = Number(pageSettings.width_mm || 279.4) * mmToPt;
        const ptH = Number(pageSettings.height_mm || 215.9) * mmToPt;
        const doc = await PDFDocument.create();
        const page = doc.addPage([ptW, ptH]);
        const bytes = await fs.readFile(bgFile);
        const embedded = await embedStudioImage(doc, bytes, ptW, ptH);
        drawStudioImage(page, embedded, { x: 0, y: 0, width: ptW, height: ptH });
        basePdf = new Uint8Array(await doc.save());
    }

    if (!schemas && payload.elements) {
        const pageSchema = [];
        for (const el of payload.elements) {
            pageSchema.push({
                name: el.id || el.binding || `field_${pageSchema.length}`,
                type: el.type === 'qr' ? 'qrcode' : (el.type || 'text'),
                position: { x: el.x || 0, y: el.y || 0 },
                width: el.width || 50,
                height: el.height || 10,
                fontSize: el.fontSize || 12,
                ...(el.fontName ? { fontName: el.fontName } : {}),
                ...(el.fontColor ? { fontColor: el.fontColor } : {}),
                alignment: el.textAlign || 'left',
                binding: el.binding || '',
                content: el.text || el.content || '',
                ...(el.schemaOptions || {}),
                asset_id: el.asset_id,
                fromLegacyElements: true,
            });
        }
        schemas = [pageSchema];
    }

    let template = {
        basePdf,
        schemas: (schemas || [[]]).map(page => page.map(field => {
            const normalized = normalizeStudioSchema(field, plugins);
            if (normalized.fontName && !nodeFonts[normalized.fontName]) {
                normalized.fontName = 'NotoSans';
            }
            if (normalized.type === 'table') {
                if (normalized.headStyles?.fontName && !nodeFonts[normalized.headStyles.fontName]) {
                    normalized.headStyles.fontName = 'NotoSans';
                }
                if (normalized.bodyStyles?.fontName && !nodeFonts[normalized.bodyStyles.fontName]) {
                    normalized.bodyStyles.fontName = 'NotoSans';
                }
            }
            return normalized;
        })),
    };

    // Prepare inputs matching the schema names and bindings
    const values = payload.values || {};
    let inputRow = {};

    for (const page of template.schemas) {
        for (const field of page) {
            let val = undefined;
            if (['image', 'signature'].includes(field.type)) {
                let assetId = field.asset_id;
                if (!assetId && typeof field.content === 'string') {
                    const m = field.content.match(/\/admin\/document-studio\/assets\/(\d+)$/);
                    if (m) assetId = Number(m[1]);
                }
                if (assetId) {
                    const file = payload.asset_paths?.[assetId];
                    if (!file) throw new Error('Image asset is unavailable.');
                    const bytes = await fs.readFile(file);
                    const mime = bytes[0] === 0xff ? 'image/jpeg' : file.endsWith('.svg') ? 'image/svg+xml' : 'image/png';
                    val = `data:${mime};base64,${bytes.toString('base64')}`;
                }
            }

            const rawContent = field.content ?? field.text;
            const hasInlineToken = typeof rawContent === 'string' && /\{\{\s*[a-zA-Z0-9_]+\s*\}\}/.test(rawContent);

            // 1. If content itself contains {{tokens}} (e.g. edited directly in PDFme Designer), interpolate it verbatim
            if (val === undefined && hasInlineToken) {
                val = interpolateStudioTokens(rawContent, values);
            }

            // 2. For table fields or empty/legacy-bound fields, resolve via field.name or field.binding
            if (val === undefined && (field.type === 'table' || !rawContent || field.fromLegacyElements)) {
                if (values[field.name] !== undefined) {
                    val = values[field.name];
                } else if (field.binding) {
                    const cleanKey = field.binding.replace(/[{}]/g, '').trim();
                    if (values[cleanKey] !== undefined) {
                        val = values[cleanKey];
                    } else if (values[field.binding] !== undefined) {
                        val = values[field.binding];
                    } else {
                        val = interpolateStudioTokens(field.binding, values);
                    }
                }
            }

            // 3. Static text typed by user (preserve exact text, never clobber user edits with values[field.name])
            if (val === undefined && typeof rawContent === 'string' && rawContent !== '') {
                val = rawContent;
            }

            // 4. Final fallback to values[field.name] or binding
            if (val === undefined && values[field.name] !== undefined) {
                val = values[field.name];
            }
            if (val === undefined) {
                val = '';
            }

            // Tables in pdfme need JSON string of 2D array or structured data
            if (field.type === 'table' && Array.isArray(val)) {
                if (val.length > 0 && typeof val[0] === 'object' && !Array.isArray(val[0])) {
                    const keys = studioTables[tableBinding(field)]?.keys || Object.keys(val[0]);
                    const rows = val.map(row => keys.map(k => String(row[k] ?? '')));
                    val = JSON.stringify(rows);
                } else {
                    val = JSON.stringify(val);
                }
            } else if (typeof val !== 'string') {
                val = String(val);
            }

            inputRow[field.name] = val;
            // ReadOnly fields in @pdfme/generator read schema.content directly instead of inputs[0][name].
            if (field.readOnly) {
                field.content = val;
            }
        }
    }

    if (payload.source_layout) {
        const paginated = await paginateSourceLayout(template, inputRow);
        template = paginated.template; inputRow = paginated.input;
    }

    const generated = await generate({
        template,
        inputs: [inputRow],
        plugins,
        options: Object.keys(nodeFonts).length > 0 ? { font: nodeFonts } : undefined,
    });

    return generated;
}

function parsePdfColor(hex) {
    if (!hex || typeof hex !== 'string') return rgb(0, 0, 0);
    const clean = hex.replace('#', '').trim();
    if (clean.length === 3) {
        const r = parseInt(clean[0] + clean[0], 16) / 255;
        const g = parseInt(clean[1] + clean[1], 16) / 255;
        const b = parseInt(clean[2] + clean[2], 16) / 255;
        return rgb(r, g, b);
    }
    if (clean.length === 6) {
        const r = parseInt(clean.substring(0, 2), 16) / 255;
        const g = parseInt(clean.substring(2, 4), 16) / 255;
        const b = parseInt(clean.substring(4, 6), 16) / 255;
        return rgb(r, g, b);
    }
    return rgb(0.1, 0.1, 0.1);
}

async function renderFabric(payload) {
    const mm = 72 / 25.4;
    const ptW = (payload.page?.width_mm || 279.4) * mm;
    const ptH = (payload.page?.height_mm || 215.9) * mm;
    const doc = await PDFDocument.create();
    const page = doc.addPage([ptW, ptH]);
    const fonts = {
        regular: await doc.embedFont(StandardFonts.Helvetica),
        bold: await doc.embedFont(StandardFonts.HelveticaBold),
        times: await doc.embedFont(StandardFonts.TimesRoman),
        timesBold: await doc.embedFont(StandardFonts.TimesRomanBold),
    };
    if (payload.backgroundVerifiedPath) {
        const embedded = await embedStudioImage(doc, await fs.readFile(payload.backgroundVerifiedPath), ptW, ptH);
        drawStudioImage(page, embedded, { x:0, y:0, width:ptW, height:ptH });
    }
    const textValue = source => String(source || '').replace(/\{\{(\w+)\}\}/g, (token,key) =>
        payload.values?.[key] === undefined || typeof payload.values[key] === 'object' ? token : String(payload.values[key]));
    for (const el of payload.elements || []) {
        page.pushOperators(pushGraphicsState(), translate((el.x || 0)*mm, ptH-(el.y || 0)*mm), rotateRadians(-(el.angle || 0)*Math.PI/180));
        const w=(el.width || 30)*mm; const h=(el.height || 30)*mm;
        if (el.type === 'text') {
            const isTimes=/times/i.test(el.fontFamily || '');
            const font=isTimes ? (el.fontWeight==='bold' ? fonts.timesBold : fonts.times) : (el.fontWeight==='bold' ? fonts.bold : fonts.regular);
            const size=el.fontSize || 12;
            const lines=[];
            for(const paragraph of textValue(el.text || el.content || el.binding).split('\n')) {
                let current='';
                for(const character of paragraph) {
                    if(current && font.widthOfTextAtSize(current+character,size)>w) { lines.push(current); current=''; }
                    current+=character;
                }
                lines.push(current);
            }
            for(const [index,line] of lines.entries()) {
                const lineWidth=font.widthOfTextAtSize(line,size);
                const x=el.textAlign==='center' ? (w-lineWidth)/2 : el.textAlign==='right' ? w-lineWidth : 0;
                page.drawText(line,{x,y:-size-index*size*1.16,size,font,color:parsePdfColor(el.color || '#111827')});
            }
        } else if (['rect','rectangle'].includes(el.type)) {
            page.drawRectangle({x:0,y:-h,width:w,height:h,color:el.fill==='transparent' ? undefined : parsePdfColor(el.fill || '#f3f4f6'),borderColor:parsePdfColor(el.stroke || '#9ca3af'),borderWidth:(el.strokeWidth ?? 1)*mm/3.5});
        } else if (el.type === 'circle') {
            const rx=(el.radius || 20)*mm; const ry=(el.radius_y || el.radius || 20)*mm;
            page.drawEllipse({x:rx,y:-ry,xScale:rx,yScale:ry,color:parsePdfColor(el.fill || '#f3f4f6'),borderColor:parsePdfColor(el.stroke || '#9ca3af'),borderWidth:(el.strokeWidth ?? 1)*mm/3.5});
        } else if (['qr','qrcode'].includes(el.type)) {
            const { default: bwipjs } = await import('bwip-js');
            const bytes=await bwipjs.toBuffer({bcid:'qrcode',text:textValue(el.binding || el.text || '{{qr_code}}'),scale:3});
            drawStudioImage(page,await embedStudioImage(doc,bytes,w,h),{x:0,y:-h,width:w,height:h});
        } else if (el.type === 'image') {
            let bytes;
            const match=el.src?.match(/\/admin\/document-studio\/assets\/(\d+)$/);
            const file=payload.asset_paths?.[el.asset_id || match?.[1]];
            if(file) bytes=await fs.readFile(file);
            else if(/^data:image\/(png|jpeg);base64,/.test(el.src || '')) bytes=Buffer.from(el.src.split(',')[1],'base64');
            else throw new Error('Canvas images require verified assets.');
            drawStudioImage(page,await embedStudioImage(doc,bytes,w,h),{x:0,y:-h,width:w,height:h});
        } else throw new Error('Unsupported canvas element.');
        page.pushOperators(popGraphicsState());
    }
    return doc.save();
}


main().catch(err => {
    console.error(err);
    process.exit(1);
});
