import { WordEditor } from '@raazkhnl/rk-editor-core';
import { notifyStudio } from './feedback.js';

export class RkWordAdapter {
    constructor(container, options = {}) {
        this.container = container;
        this.options = options;
        this.template = options.template || {};
    }

    async mount() {
        this.container.innerHTML = `<div class="rk-word-shell">
            <div class="rk-toolbar" aria-label="Document formatting">
                <button type="button" data-cmd="bold" aria-label="Bold"><b>B</b></button>
                <button type="button" data-cmd="italic" aria-label="Italic"><i>I</i></button>
                <button type="button" data-cmd="underline" aria-label="Underline"><u>U</u></button>
                <button type="button" data-align="left">Left</button>
                <button type="button" data-align="center">Center</button>
                <button type="button" data-align="right">Right</button>
                <button type="button" data-cmd="bulletList">Bullets</button>
                <button type="button" data-cmd="orderedList">Numbering</button>
                <button type="button" data-table>Table</button>
                <label class="rk-import">Import DOCX<input type="file" accept=".docx" hidden></label>
                <button type="button" data-export>Export DOCX</button>
            </div>
            <div class="rk-paper-scroll"><div class="rk-page"></div></div>
        </div>`;
        this.pageLayout = { pageSize: 'Custom', width: `${this.template.page?.width_mm || 210}mm`, height: `${this.template.page?.height_mm || 297}mm` };
        this.editor = new WordEditor({
            element: this.container.querySelector('.rk-page'),
            initialContent: this.template.html || this.template.content || this.template.body || '<p></p>',
            pageLayout: this.pageLayout,
            slashCommands: [],
            dragHandles: false,
            imageUploadHandler: async () => { throw new Error('Use the asset upload button to add an image.'); },
            onUpdate: (_, editor) => this.options.onChange?.({ ...this.getState(), html: editor.getHTML() }),
        });
        this.editor.instance.setOptions({ editorProps: {
            handlePaste: (_, event) => {
                const text = event.clipboardData?.getData('text/plain');
                if (text) this.editor.instance.chain().focus().insertContent({ type: 'text', text }).run();
                return true;
            },
            handleDrop: () => true,
        } });
        this.container.querySelectorAll('[data-cmd]').forEach(button => button.addEventListener('click', () => this.editor.format[button.dataset.cmd]()));
        this.container.querySelectorAll('[data-align]').forEach(button => button.addEventListener('click', () => this.editor.format.align(button.dataset.align)));
        this.container.querySelector('[data-table]').addEventListener('click', () => this.editor.format.insertTable({ rows: 3, cols: 3, withHeaderRow: true }));
        this.container.querySelector('[data-export]').addEventListener('click', () => this.exportDocx().catch(error => notifyStudio(error.message, 'error', 'Export failed')));
        this.container.querySelector('input[type=file]').addEventListener('change', async event => {
            const file = event.target.files[0];
            if (!file) return;
            const body = new FormData();
            body.append('file', file);
            try {
                const response = await fetch(this.options.endpoints.importDocx, {
                    method: 'POST', credentials: 'same-origin', body,
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' },
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || 'DOCX import failed.');
                this.editor.setDocument(result.html);
                this.options.onChange?.(this.getState());
            } catch (error) { notifyStudio(error.message, 'error', 'Import failed'); }
            finally { event.target.value = ''; }
        });
    }

    insertPlaceholder(token) {
        this.editor?.instance.chain().focus().insertContent({ type: 'text', text: token }).run();
    }

    addImage(url) { this.editor?.format.insertImage(url, 'Document image'); }

    async exportDocx(filename = 'MCARE-document.docx') {
        const blob = await this.editor.exporter.toBlob(this.editor.getJSON(), { filename, pageLayout: this.pageLayout });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.append(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    getState() {
        return { schema_version: '1.0', engine: 'rk_word',
            page: this.template.page || { size: 'a4', orientation: 'portrait', width_mm: 210, height_mm: 297 },
            html: this.editor?.getHTML() || '' };
    }

    destroy() { this.editor?.destroy(); this.container.replaceChildren(); }
}
