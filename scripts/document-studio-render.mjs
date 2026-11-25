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
import bwipjs from 'bwip-js';
import { renderWord } from './document-studio-word.mjs';
import { embedStudioImage, drawStudioImage } from './document-studio-image.mjs';

async function main() {
    const args = process.argv.slice(2);
    if (args.includes('--smoke-test')) {
        try {
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
async function getNodePdfmeFonts() {
    if (cachedNodeFonts) return cachedNodeFonts;
    const __dirname = path.dirname(fileURLToPath(import.meta.url));
    const fontsDir = path.resolve(__dirname, '../public/fonts/certificate');
    const fontMap = {};
    for (const font of CERTIFICATE_FONTS) {
        const fontPath = path.join(fontsDir, font.file);
        try {
            const buf = await fs.readFile(fontPath);
            fontMap[font.key] = {
                data: new Uint8Array(buf),
                ...(font.fallback ? { fallback: true } : {}),
            };
        } catch (err) {
            // Skip missing optional font file cleanly
        }
    }
    cachedNodeFonts = fontMap;
    return fontMap;
}

async function renderPdfme(payload) {
    const nodeFonts = await getNodePdfmeFonts();
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
            return normalized;
        })),
    };

    // Prepare inputs matching the schema names and bindings
    const values = payload.values || {};
    let inputRow = {};

    for (const page of template.schemas) {
        for (const field of page) {
            let val = undefined;
            if (['image', 'signature'].includes(field.type) && field.asset_id) {
                const file = payload.asset_paths?.[field.asset_id];
                if (!file) throw new Error('Image asset is unavailable.');
                const bytes = await fs.readFile(file);
                const mime = bytes[0] === 0xff ? 'image/jpeg' : file.endsWith('.svg') ? 'image/svg+xml' : 'image/png';
                val = `data:${mime};base64,${bytes.toString('base64')}`;
            }

            // 1. Direct match by field.name in values
            if (val === undefined && values[field.name] !== undefined) {
                val = values[field.name];
            }

            // 2. Match by binding token (e.g. {{trainee_name}} or trainee_name)
            if (val === undefined && field.binding) {
                const cleanKey = field.binding.replace(/[{}]/g, '').trim();
                if (values[cleanKey] !== undefined) {
                    val = values[cleanKey];
                } else if (values[field.binding] !== undefined) {
                    val = values[field.binding];
                } else {
                    val = field.binding.replace(/\{\{(\w+)\}\}/g, (token, key) => values[key] === undefined || typeof values[key] === 'object' ? token : String(values[key]));
                }
            }

            // 3. If field has static content or text with embedded tokens
            const rawContent = field.content || field.text;
            if (val === undefined && rawContent) {
                let interpolated = String(rawContent);
                for (const [k, v] of Object.entries(values)) {
                    if (v !== undefined && v !== null && typeof v !== 'object') {
                        interpolated = interpolated.replaceAll(`{{${k}}}`, String(v));
                    }
                }
                val = interpolated;
            }

            // 4. Default to static content if no input value provided (never blank out static text)
            if (val === undefined) {
                val = field.content || field.text || '';
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
            // pdfme rich text uses static-content mode; values have already been
            // resolved from Laravel's authoritative payload above.
            if (field.type === 'text' && field.readOnly && field.textFormat === 'inline-markdown') {
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
