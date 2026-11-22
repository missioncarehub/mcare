import fs from 'node:fs/promises';
import { PDFDocument, PDFDict, PDFArray, PDFName, PDFRef } from 'pdf-lib';

const file = process.argv[2];
if (!file) throw new Error('A PDF file is required.');
const pdf = await PDFDocument.load(await fs.readFile(file), { throwOnInvalidObject:true });
if (pdf.isEncrypted || pdf.getPageCount() < 1 || pdf.getPageCount() > 20) throw new Error('Encrypted PDFs and more than 20 pages are unsupported.');
for (const page of pdf.getPages()) if (page.getWidth() > 2835 || page.getHeight() > 2835) throw new Error('PDF page dimensions exceed the safety limit.');
const forbidden = new Set(['javascript','js','launch','embeddedfiles','embeddedfile','richmedia','submitform','importdata','aa','openaction','xfa']);
const seen = new Set();
function inspect(value, depth = 0) {
    if (depth > 40) throw new Error('PDF object nesting exceeds the safety limit.');
    if (value instanceof PDFRef) {
        const key=value.toString(); if (seen.has(key)) return; seen.add(key);
        inspect(pdf.context.lookup(value),depth+1);
    } else if (value instanceof PDFName) {
        if (forbidden.has(value.decodeText().toLowerCase())) throw new Error('Active PDF content is unsupported.');
    } else if (value instanceof PDFDict) {
        for (const [key,item] of value.entries()) { inspect(key,depth+1); inspect(item,depth+1); }
    } else if (value instanceof PDFArray) {
        for (const item of value.asArray()) inspect(item,depth+1);
    } else if (value?.dict instanceof PDFDict) inspect(value.dict,depth+1);
}
for (const [ref,value] of pdf.context.enumerateIndirectObjects()) { seen.add(ref.toString()); inspect(value); }
console.log('PDF structure and active-content checks passed.');
