import * as pdfjsLib from 'pdfjs-dist';

export async function loadPdfJs() {
    if (typeof window !== 'undefined' && pdfjsLib.GlobalWorkerOptions && !pdfjsLib.GlobalWorkerOptions.workerSrc) {
        pdfjsLib.GlobalWorkerOptions.workerSrc = '/vendor/pdfjs/pdf.worker.min.js';
    }
    return pdfjsLib;
}
