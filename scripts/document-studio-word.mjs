import fs from 'node:fs/promises';
import { parseFragment } from 'parse5';
import { PDFDocument, StandardFonts, rgb } from 'pdf-lib';
import { studioTables } from '../resources/js/document-studio/tables.js';
import { embedStudioImage, drawStudioImage } from './document-studio-image.mjs';

// Supported flowing layout: paragraphs, inline formatting, lists, tables and
// authenticated raster assets. HTML is sanitized by Laravel before this call.
export async function renderWord(payload) {
    const width = (payload.page?.width_mm || 210) * 72 / 25.4;
    const height = (payload.page?.height_mm || 297) * 72 / 25.4;
    const margin = 54;
    const doc = await PDFDocument.create();
    const fonts = {};
    for (const [key, name] of Object.entries({ regular: StandardFonts.Helvetica, bold: StandardFonts.HelveticaBold, italic: StandardFonts.HelveticaOblique, both: StandardFonts.HelveticaBoldOblique })) fonts[key] = await doc.embedFont(name);
    let page = doc.addPage([width, height]);
    let y = height - margin;
    const ensure = space => {
        if (space > height - margin * 2) throw new Error('A Word layout block is too large for the page.');
        if (y - space < margin) { page = doc.addPage([width, height]); y = height - margin; }
    };
    const attr = (node, name) => node.attrs?.find(item => item.name === name)?.value || '';
    const style = node => Object.fromEntries(attr(node, 'style').split(';').filter(item => item.includes(':')).map(item => { const i = item.indexOf(':'); return [item.slice(0, i).trim(), item.slice(i + 1).trim()]; }));
    const color = value => /^#[a-f0-9]{6}$/i.test(value || '') ? rgb(...[1,3,5].map(i => parseInt(value.slice(i,i+2),16)/255)) : rgb(.07,.09,.12);
    const interpolate = text => text.replace(/\{\{(\w+)\}\}/g, (_, key) => {
        const value = payload.values?.[key];
        return value === undefined || typeof value === 'object' ? `{{${key}}}` : String(value);
    });
    function runs(node, format = {}, output = []) {
        if (node.nodeName === '#text') { output.push({ text: interpolate(node.value), ...format }); return output; }
        if (node.tagName === 'br') { output.push({ text: '\n', ...format }); return output; }
        const css = style(node);
        const next = { ...format,
            bold: format.bold || ['strong','b','th'].includes(node.tagName) || css['font-weight'] === 'bold',
            italic: format.italic || ['em','i'].includes(node.tagName) || css['font-style'] === 'italic',
            underline: format.underline || node.tagName === 'u' || css['text-decoration']?.includes('underline'),
            color: css.color || format.color,
            size: Math.min(72, Math.max(6, parseFloat(css['font-size']) || format.size || 11)),
        };
        for (const child of node.childNodes || []) runs(child, next, output);
        return output;
    }
    function linesFor(items, available) {
        const lines = [[]]; let used = 0;
        for (const run of items) {
            const font = fonts[run.bold && run.italic ? 'both' : run.bold ? 'bold' : run.italic ? 'italic' : 'regular'];
            // Split overlong words as well as normal whitespace, retaining styled runs.
            for (const piece of run.text.split(/(\s+)/).filter(Boolean)) {
                if (piece.includes('\n')) { lines.push([]); used = 0; if (/^\s+$/.test(piece)) continue; }
                let pending = '';
                for (const char of piece) {
                    const w = font.widthOfTextAtSize(char, run.size || 11);
                    if (used + w > available && used > 0) {
                        if (pending) lines.at(-1).push({ ...run, text: pending, font });
                        pending = ''; lines.push([]); used = 0;
                    }
                    pending += char; used += w;
                }
                if (pending) lines.at(-1).push({ ...run, text: pending, font });
            }
        }
        return lines;
    }
    function drawLines(lines, x, available, alignment, top, lineHeight) {
        let baseline = top - lineHeight;
        for (const line of lines) {
            const lineWidth = line.reduce((sum, run) => sum + run.font.widthOfTextAtSize(run.text, run.size || 11), 0);
            let cursor = x + (alignment === 'center' ? (available-lineWidth)/2 : alignment === 'right' ? available-lineWidth : 0);
            for (const run of line) {
                const w = run.font.widthOfTextAtSize(run.text, run.size || 11);
                page.drawText(run.text, { x: cursor, y: baseline, font: run.font, size: run.size || 11, color: color(run.color) });
                if (run.underline) page.drawLine({ start: { x:cursor, y:baseline-2 }, end: { x:cursor+w, y:baseline-2 }, thickness:.5, color:color(run.color) });
                cursor += w;
            }
            baseline -= lineHeight;
        }
    }
    async function block(node, prefix = '') {
        if (node.tagName === 'p') {
            const text = runs(node).map(run => run.text).join('').trim();
            const key = text.match(/^\{\{(\w+)\}\}$/)?.[1];
            const definition = studioTables[key];
            if (definition && Array.isArray(payload.values?.[key])) {
                const cell = (tag, text) => ({ tagName: tag, childNodes: [{ nodeName: '#text', value: String(text ?? '') }] });
                await block({ tagName: 'table', childNodes: [
                    { tagName: 'tr', childNodes: definition.head.map(text => cell('th', text)) },
                    ...payload.values[key].map(row => ({ tagName: 'tr', childNodes: definition.keys.map(column => cell('td', row[column])) })),
                ] });
                return;
            }
        }
        if (node.tagName === 'table') {
            const rows = [];
            const findRows = n => { if (n.tagName === 'tr') rows.push(n); else for (const c of n.childNodes || []) findRows(c); };
            findRows(node);
            for (const row of rows) {
                const cells = row.childNodes.filter(cell => ['td','th'].includes(cell.tagName));
                if (!cells.length) continue;
                if (cells.some(cell => Number(attr(cell,'rowspan') || 1) > 1)) throw new Error('Word PDF does not support merged vertical table cells.');
                const columns = cells.reduce((sum, cell) => sum + Number(attr(cell,'colspan') || 1), 0);
                const unitWidth = (width - margin * 2) / columns;
                const layouts = cells.map(cell => { const w = unitWidth * Number(attr(cell,'colspan') || 1); return { cell, w, lines:linesFor(runs(cell), w-12) }; });
                const rowHeight = Math.max(...layouts.map(cell => cell.lines.length * 16 + 12));
                ensure(rowHeight);
                let x = margin;
                for (const cell of layouts) {
                    page.drawRectangle({ x, y:y-rowHeight, width:cell.w, height:rowHeight, borderWidth:.5, borderColor:rgb(.4,.4,.4), color:cell.cell.tagName === 'th' ? rgb(.94,.95,.97) : rgb(1,1,1) });
                    drawLines(cell.lines,x+6,cell.w-12,style(cell.cell)['text-align'],y-4,16);
                    x += cell.w;
                }
                y -= rowHeight;
            }
            y -= 8;
        } else if (node.tagName === 'img') {
            const match = attr(node,'src').match(/\/admin\/document-studio\/assets\/(\d+)$/);
            const file = match && payload.asset_paths?.[match[1]];
            if (!file) throw new Error('Word image requires a verified asset.');
            const bytes = await fs.readFile(file);
            const embedded = await embedStudioImage(doc, bytes, 300, 150);
            const image = embedded.object;
            const w = Math.min(width-margin*2, Number(attr(node,'width')) * .75 || image.width * .75);
            const h = image.height / image.width * w;
            ensure(h+8); drawStudioImage(page, embedded, { x:margin, y:y-h, width:w, height:h }); y -= h+8;
        } else if (['ul','ol'].includes(node.tagName)) {
            let i = 0;
            for (const child of node.childNodes || []) if (child.tagName === 'li') await block(child, node.tagName === 'ol' ? `${++i}. ` : '- ');
        } else if (['p','h1','h2','h3','h4','li','blockquote'].includes(node.tagName) || node.nodeName === '#text') {
            const size = /^h[1-4]$/.test(node.tagName || '') ? 24-Number(node.tagName[1])*3 : 11;
            const items = runs(node,{ size, bold: /^h/.test(node.tagName || '') });
            if (prefix) items.unshift({ text:prefix, size });
            const lines = linesFor(items,width-margin*2);
            const lineHeight = Math.max(size*1.5,...items.map(run => (run.size || 11)*1.5));
            for (const line of lines) { ensure(lineHeight); drawLines([line],margin,width-margin*2,style(node)['text-align'],y,lineHeight); y -= lineHeight; }
            y -= 8;
            // Images inside paragraphs must retain their position in document flow.
            for (const child of node.childNodes || []) if (child.tagName === 'img') await block(child);
        } else {
            for (const child of node.childNodes || []) await block(child);
        }
    }
    const root = parseFragment(payload.html || payload.content || '');
    for (const child of root.childNodes) await block(child);
    return doc.save();
}
