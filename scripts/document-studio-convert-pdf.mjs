import fs from 'node:fs/promises';
import path from 'node:path';
import { createRequire } from 'node:module';
import { pathToFileURL } from 'node:url';
import { createCanvas } from '@napi-rs/canvas';
import { PDFDocument } from 'pdf-lib';
import { getDocument, OPS, Util, AnnotationMode } from 'pdfjs-dist/legacy/build/pdf.mjs';

const require = createRequire(import.meta.url);
const library = path.dirname(require.resolve('pdfjs-dist/package.json'));
const mm = 25.4 / 72;
const omitText = new Set([OPS.showText, OPS.showSpacedText, OPS.nextLineShowText, OPS.nextLineSetSpacingShowText]);

/** Trusted CLI paths only. PHP owns uploads, validation, authorization and timeout. */
export async function convertPdf(input, directory, regions = []) {
    const bytes = await fs.readFile(input);
    if (bytes.length > 15 * 1024 * 1024) throw new Error('PDF exceeds 15 MB.');
    if (!Array.isArray(regions) || regions.length > 100) throw new Error('Too many manual regions.');
    const task = getDocument({ data: new Uint8Array(bytes), isEvalSupported: false, enableXfa: false,
        maxImageSize: 16000000, stopAtErrors: true,
        standardFontDataUrl: library + '/standard_fonts/', cMapUrl: library + '/cmaps/', cMapPacked: true,
        wasmUrl: library + '/wasm/', useSystemFonts: false });
    const pdf = await task.promise;
    try {
        if (regions.some(region => !Number.isInteger(region.page) || region.page < 1 || region.page > pdf.numPages)) throw new Error('Manual region page does not exist.');
        if (pdf.numPages < 1 || pdf.numPages > 20) throw new Error('Use a PDF with 1–20 pages.');
        const background = await PDFDocument.create();
        const schemas = [], pages = [], warnings = new Set(['Fonts are substituted. Review text spacing and line breaks.', 'Artwork is a locked raster background; original text is rebuilt as editable fields.']);
        let count = 0;
        for (let number = 1; number <= pdf.numPages; number++) {
            const page = await pdf.getPage(number);
            const viewport = page.getViewport({ scale: 1 });
            if (viewport.width < 56 || viewport.height < 56 || viewport.width > 2835 || viewport.height > 2835) throw new Error('Unsupported page dimensions.');
            const list = await page.getOperatorList({ annotationMode: AnnotationMode.DISABLE });
            if (list.fnArray.length > 100000) throw new Error('PDF page is too complex.');
            const paints = [], stack = []; let fill = '#000000';
            for (let index = 0; index < list.fnArray.length; index++) {
                const op = list.fnArray[index], args = list.argsArray[index];
                if (op === OPS.save) stack.push(fill);
                if (op === OPS.restore) fill = stack.pop() ?? '#000000';
                if (op === OPS.setFillRGBColor && /^#[0-9a-f]{6}$/i.test(args?.[0])) fill = args[0];
                if (op === OPS.showText) {
                    const text = (args?.[0] ?? []).map(glyph => glyph?.unicode ?? '').join('');
                    paints.push({ text: text.replace(/\s/g, ''), color: fill });
                }
            }
            const content = await page.getTextContent();
            if (content.items.length > 3000) throw new Error('PDF page contains too many text fragments.');
            const fields = [];
            for (const item of content.items) {
                if (!item.str?.trim()) continue;
                if (++count > 500) throw new Error('Conversion supports at most 500 text fields.');
                const paint = paints.find(paint => paint.text.includes(item.str.replace(/\s/g, '')));
                if (paint) {
                    const run = item.str.replace(/\s/g, '');
                    paint.text = paint.text.slice(paint.text.indexOf(run) + run.length);
                }
                const t = Util.transform(viewport.transform, item.transform);
                const fontSize = Math.hypot(t[2], t[3]);
                const style = content.styles[item.fontName] ?? {};
                const ascent = (style.ascent ?? .8) * fontSize;
                const angle = Math.atan2(t[1], t[0]);
                // pdfme rotates about the field centre; compensate for that origin.
                const width = Math.max(2, item.width), height = Math.max(2, fontSize * 1.25);
                const ox = t[4] + Math.sin(angle) * ascent, oy = t[5] - Math.cos(angle) * ascent;
                const cx = ox + Math.cos(angle) * width / 2 - Math.sin(angle) * height / 2;
                const cy = oy + Math.sin(angle) * width / 2 + Math.cos(angle) * height / 2;
                fields.push({ name: `text_${number}_${fields.length + 1}`, type: 'text',
                    position: { x: (cx - width / 2) * mm, y: (cy - height / 2) * mm },
                    width: width * mm + .8, height: height * mm, fontSize,
                    rotate: ((angle * 180 / Math.PI) + 360) % 360,
                    content: item.str, alignment: 'left', verticalAlignment: 'top', lineHeight: 1,
                    fontColor: paint?.color ?? '#000000', characterSpacing: 0 });
            }
            const annotations = await page.getAnnotations();
            for (const a of annotations) {
                if (a.subtype !== 'Widget') { warnings.add('Annotations and links are excluded.'); continue; }
                if (a.fieldType !== 'Tx' || typeof a.fieldValue !== 'string') { warnings.add('Non-text form controls are excluded.'); continue; }
                if (++count > 500) throw new Error('Conversion supports at most 500 fields.');
                const rect = [...viewport.convertToViewportPoint(a.rect[0], a.rect[1]), ...viewport.convertToViewportPoint(a.rect[2], a.rect[3])];
                fields.push({ name: `form_${number}_${fields.length + 1}`, type: 'text', content: a.fieldValue,
                    position: { x: Math.min(rect[0], rect[2]) * mm, y: Math.min(rect[1], rect[3]) * mm },
                    width: Math.abs(rect[2] - rect[0]) * mm, height: Math.abs(rect[3] - rect[1]) * mm, fontSize: 12 });
            }
            const scale = Math.min(2, 3000 / Math.max(viewport.width, viewport.height), Math.sqrt(6000000 / (viewport.width * viewport.height)));
            const raster = page.getViewport({ scale });
            const canvas = createCanvas(Math.ceil(raster.width), Math.ceil(raster.height));
            const context = canvas.getContext('2d');
            await page.render({ canvasContext: context, viewport: raster, annotationMode: AnnotationMode.DISABLE,
                operationsFilter: index => !omitText.has(list.fnArray[index]) }).promise;
            for (const region of regions.filter(r => r.page === number)) {
                const { x, y, width, height } = region;
                if (![x, y, width, height].every(Number.isFinite) || x < 0 || y < 0 || width <= 0 || height <= 0 || x + width > viewport.width * mm + .01 || y + height > viewport.height * mm + .01 || !/^#[0-9a-f]{6}$/i.test(region.color)) throw new Error('Invalid manual region.');
                // Erase pixels in a derived raster, not a white PDF overlay over recoverable source text.
                context.fillStyle = region.color;
                context.fillRect(x / mm * scale, y / mm * scale, width / mm * scale, height / mm * scale);
                fields.push({ name: `region_${number}_${fields.length + 1}`, type: 'text',
                    position: { x, y }, width, height, fontSize: 12, content: String(region.content ?? '').slice(0, 2000) });
                if (++count > 500) throw new Error('Conversion supports at most 500 fields.');
            }
            if (!fields.length) throw new Error(`Page ${number} has no editable text. Select at least one manual region on that page before converting.`);
            const png = canvas.toBuffer('image/png');
            if (png.length > 5 * 1024 * 1024) throw new Error('Page artwork exceeds 5 MB.');
            const embedded = await background.embedPng(png);
            const target = background.addPage([viewport.width, viewport.height]);
            target.drawImage(embedded, { x: 0, y: 0, width: viewport.width, height: viewport.height });
            schemas.push(fields); pages.push({ width_mm: viewport.width * mm, height_mm: viewport.height * mm, text_fields: fields.length });
            page.cleanup();
        }
        if (regions.length) warnings.add('Manual regions erase selected pixels only. Inspect every page for remaining personal information before reuse.');
        const clean = await background.save();
        if (clean.length > 15 * 1024 * 1024) throw new Error('Converted artwork exceeds 15 MB.');
        await fs.writeFile(path.join(directory, 'background.pdf'), clean);
        const result = { state: { schema_version: '1.0', engine: 'pdfme', page: pages[0], schemas },
            report: { version: 1, pages, editable_fields: count, manual_regions: regions.length, warnings: [...warnings], background: 'Text-free raster; scans retain pixels outside selected regions.' } };
        await fs.writeFile(path.join(directory, 'result.json'), JSON.stringify(result));
        return result;
    } finally { await task.destroy(); }
}

if (process.argv[1] && import.meta.url === pathToFileURL(path.resolve(process.argv[1])).href) {
    try { await convertPdf(process.argv[2], process.argv[3], JSON.parse(await fs.readFile(process.argv[4], 'utf8'))); }
    catch (error) { console.error(error.message); process.exitCode = 1; }
}
