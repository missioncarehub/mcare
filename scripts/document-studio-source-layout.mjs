import { PDFDocument, rgb } from 'pdf-lib';
import { getDynamicHeightsForTable } from '@pdfme/schemas';

// Fixed source coordinates must not move when a neighbouring table has fewer rows.
// Measure rows with the same pdfme table engine, then repeat the source page for overflow.
export async function paginateSourceLayout(template, input, options = {}) {
    const result = [], values = {}, cache = new Map();
    for (const fields of template.schemas) {
        const tables = fields.filter(field => field.type === 'table');
        if (tables.length > 1) throw new Error('Existing designs support one repeating table per source page.');
        const table = tables[0];
        let groups = [null];
        if (table) {
            const rows = JSON.parse(input[table.name] || '[]');
            if (!Array.isArray(rows) || rows.length > 1000) throw new Error('The table exceeds 1,000 rows.');
            const heights = await getDynamicHeightsForTable(JSON.stringify(rows), { schema: { ...table, repeatHead: false }, basePdf: template.basePdf, options, _cache: cache });
            const header = heights[0], limit = Math.min(table.layout_slot_height || table.height, template.basePdf.height - table.position.y - 10);
            groups = []; let current = [], used = header;
            for (let i = 0; i < rows.length; i++) {
                const height = heights[i + 1];
                if (!Number.isFinite(height) || header + height > limit) throw new Error('A table row is too tall for its available area. Widen the column or shorten the text.');
                if (used + height > limit && current.length) { groups.push(current); current = []; used = header; }
                current.push(rows[i]); used += height;
            }
            if (current.length || !groups.length) groups.push(current);
        }
        for (const group of groups) {
            if (result.length >= 20) throw new Error('The generated document exceeds 20 pages.');
            const page = fields.map(field => {
                const name = `${field.name}_page_${result.length + 1}`;
                values[name] = field === table ? JSON.stringify(group) : input[field.name];
                return { ...field, name, repeatHead: false };
            });
            result.push(page);
        }
    }
    const base = await PDFDocument.create();
    for (const fields of result) {
        const page = base.addPage([template.basePdf.width * 72 / 25.4, template.basePdf.height * 72 / 25.4]);
        page.drawRectangle({ x: 0, y: 0, width: page.getWidth(), height: page.getHeight(), color: rgb(1, 1, 1) });
    }
    return { template: { basePdf: await base.save(), schemas: result }, input: values };
}
