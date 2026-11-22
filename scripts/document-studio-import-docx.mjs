import fs from 'node:fs/promises';
import mammoth from 'mammoth';

const [input, output] = process.argv.slice(2);
if (!input || !output) throw new Error('DOCX input and JSON output are required.');
const images = [];
const result = await mammoth.convertToHtml({ buffer: await fs.readFile(input) }, {
    externalFileAccess: false,
    transformDocument: mammoth.transforms.paragraph(paragraph => ['center', 'right', 'justify'].includes(paragraph.alignment)
        ? { ...paragraph, styleId: `MCARE-${paragraph.alignment}`, styleName: `MCARE ${paragraph.alignment}` }
        : paragraph),
    styleMap: ['center', 'right', 'justify'].map(alignment => `p[style-name='MCARE ${alignment}'] => p.mcare-${alignment}:fresh`),
    convertImage: mammoth.images.imgElement(async image => {
        if (images.length >= 50) throw new Error('Too many DOCX images.');
        const contents = await image.read('base64');
        if (contents.length > 7 * 1024 * 1024) throw new Error('DOCX image is too large.');
        images.push({ mime: image.contentType, contents });
        return { src: `mcare-image-${images.length - 1}` };
    }),
});
if (result.value.length > 2 * 1024 * 1024) throw new Error('DOCX contents exceed the safety limit.');
const html = result.value.replace(/class="mcare-(center|right|justify)"/g, (_, alignment) => `style="text-align: ${alignment}"`);
await fs.writeFile(output, JSON.stringify({ html, images }));
