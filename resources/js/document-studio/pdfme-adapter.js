import { installTextFields } from './text-fields.js';
import { Designer } from '@pdfme/ui';
import { text, image, signature, table, barcodes, line, rectangle, ellipse, svg } from '@pdfme/schemas';
import { normalizeStudioSchema } from './tables.js';

export class PdfmeAdapter {
    constructor(container, options = {}) {
        this.container = container;
        this.options = options;
        this.designer = null;
        this.template = options.template || {
            basePdf: { width: 210, height: 297, padding: [10, 10, 10, 10] },
            schemas: [[]],
        };
        this.plugins = {
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
    }

    async mount() {
        if (!this.container) return;

        // Normalize template structure for pdfme
        let basePdf = this.template.basePdf;
        if (this.template.base_pdf_asset_id) {
            const asset = this.options.assets?.find(item => item.id === this.template.base_pdf_asset_id);
            if (!asset) throw new Error('The base PDF is unavailable.');
            const response = await fetch(asset.url, { credentials: 'same-origin' });
            if (!response.ok) throw new Error('Could not load the base PDF.');
            basePdf = new Uint8Array(await response.arrayBuffer());
        }
        if (!basePdf && (this.template.base_pdf_url || this.template.basePdfUrl)) {
            try {
                const url = this.template.base_pdf_url || this.template.basePdfUrl;
                const response = await fetch(url, { credentials: 'same-origin' });
                if (response.ok) {
                    basePdf = new Uint8Array(await response.arrayBuffer());
                }
            } catch (err) {
                console.warn('Could not load base_pdf_url, falling back to dimensions', err);
            }
        }
        if (!basePdf) {
            const page = this.template.page || { width_mm: 210, height_mm: 297 };
            basePdf = {
                width: page.width_mm || 210,
                height: page.height_mm || 297,
                padding: [10, 10, 10, 10],
            };
        }

        let schemas = this.template.schemas;
        if (!schemas && this.template.elements) {
            schemas = [this.template.elements.map((el, idx) => ({
                name: el.id || el.binding || `field_${idx}`,
                type: el.type === 'qr' ? 'qrcode' : (el.type || 'text'),
                position: { x: el.x || 10, y: el.y || 10 },
                width: el.width || 60,
                height: el.height || 10,
                fontSize: el.fontSize || 12,
                alignment: el.textAlign || 'left',
                content: el.text || el.content || el.binding || '',
                binding: el.binding || '',
            }))];
        }
        if (!schemas || schemas.length === 0) {
            schemas = [[]];
        }

        const pdfmeTemplate = {
            basePdf,
            schemas: schemas.map(page => page.map(field => normalizeStudioSchema(field, this.plugins))),
        };

        this.designer = new Designer({
            domContainer: this.container,
            template: pdfmeTemplate,
            plugins: this.plugins,
            options: { sidebarOpen: false },
        });

        this.refreshTextFields = installTextFields(this, document.getElementById('pdfme-text-tools'));
        this.designer.onChangeTemplate((updated) => {
            this.refreshTextFields?.();
            if (this.options.onChange) {
                this.options.onChange(this.getState(updated));
            }
        });
    }

    getState(currentTemplate = null) {
        const tmpl = currentTemplate || (this.designer ? this.designer.getTemplate() : this.template);
        return {
            ...this.template,
            schema_version: '1.0',
            engine: 'pdfme',
            page: {
                width_mm: tmpl.basePdf?.width || this.template.page?.width_mm || 210,
                height_mm: tmpl.basePdf?.height || this.template.page?.height_mm || 297,
            },
            basePdf: this.template.base_pdf_asset_id ? undefined : tmpl.basePdf,
            base_pdf_asset_id: this.template.base_pdf_asset_id,
            schemas: tmpl.schemas,
        };
    }

    addField(field) {
        if (!this.designer) return;
        const current = this.designer.getTemplate();
        const schemas = current.schemas ? [...current.schemas] : [[]];
        const page0 = schemas[0] ? [...schemas[0]] : [];

        page0.push(normalizeStudioSchema({
            name: field.name || field.binding || `field_${page0.length + 1}`,
            type: field.type || 'text',
            position: { x: 20, y: 20 + (page0.length * 8) },
            width: field.width || 60,
            height: field.height || 10,
            fontSize: 12,
            alignment: 'left',
            content: field.binding || field.content || '',
            binding: field.binding || '',
        }, this.plugins));

        schemas[0] = page0;
        this.designer.updateTemplate({ ...current, schemas });
        this.options.onChange?.(this.getState());
    }

    async setBasePdf(asset) {
        const response = await fetch(asset.url, { credentials: 'same-origin' });
        if (!response.ok) throw new Error('Could not load the base PDF.');
        this.template.base_pdf_asset_id = asset.id;
        this.designer.updateTemplate({ ...this.designer.getTemplate(), basePdf: new Uint8Array(await response.arrayBuffer()) });
        this.options.onChange?.(this.getState());
    }

    addImage(url, assetId) {
        this.addField({ name: `image_${assetId}_${Date.now()}`, type: 'image', content: url, width: 40, height: 30 });
        const current = this.designer.getTemplate();
        current.schemas[0].at(-1).asset_id = assetId;
        this.designer.updateTemplate(current);
        this.options.onChange?.(this.getState());
    }

    destroy() {
        if (this.designer) {
            this.designer.destroy();
            this.designer = null;
        }
    }
}
