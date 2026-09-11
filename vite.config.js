import { copyFileSync, mkdirSync } from 'node:fs';
import path from 'node:path';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

function copyPdfJsWorker() {
    const copy = () => {
        const source = path.resolve('node_modules/pdfjs-dist/build/pdf.worker.min.mjs');
        const destinationDir = path.resolve('public/vendor/pdfjs');
        mkdirSync(destinationDir, { recursive: true });
        copyFileSync(source, path.join(destinationDir, 'pdf.worker.min.js'));
    };

    return {
        name: 'copy-pdfjs-worker',
        buildStart: copy,
        configureServer: copy,
    };
}

export default defineConfig({
    plugins: [
        copyPdfJsWorker(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
