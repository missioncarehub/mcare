import { generate } from '@pdfme/generator';
import { svg } from '@pdfme/schemas';

export async function embedStudioImage(doc, bytes, width, height) {
    if (bytes[0] === 0xff && bytes[1] === 0xd8) return { object:await doc.embedJpg(bytes), isPage:false };
    if (bytes[0] === 0x89 && bytes[1] === 0x50) return { object:await doc.embedPng(bytes), isPage:false };
    if (bytes.toString('utf8').trimStart().startsWith('<svg')) {
        const w=width*25.4/72; const h=height*25.4/72;
        const pdf=await generate({ template:{ basePdf:{width:w,height:h,padding:[0,0,0,0]}, schemas:[[{ name:'asset',type:'svg',position:{x:0,y:0},width:w,height:h }]] }, inputs:[{asset:bytes.toString('utf8')}],plugins:{svg} });
        const [object]=await doc.embedPdf(pdf);
        return { object, isPage:true };
    }
    throw new Error('Unsupported image contents.');
}

export function drawStudioImage(page, embedded, options) {
    if (embedded.isPage) page.drawPage(embedded.object,options);
    else page.drawImage(embedded.object,options);
}
