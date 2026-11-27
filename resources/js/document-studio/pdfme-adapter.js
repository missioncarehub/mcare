import { installTextFields } from './text-fields.js';
import { Designer } from '@pdfme/ui';
import { generate } from '@pdfme/generator';
import { text, image, signature, table, barcodes, line, rectangle, ellipse, svg } from '@pdfme/schemas';
import { normalizeStudioSchema, studioTables, tableBinding } from './tables.js';
import { getBrowserPdfmeFonts, CERTIFICATE_STYLE_PRESETS } from './fonts.js';

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
                ...(el.fontName ? { fontName: el.fontName } : {}),
                ...(el.fontColor ? { fontColor: el.fontColor } : {}),
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
            options: {
                sidebarOpen: true,
                font: getBrowserPdfmeFonts(),
            },
        });

        this.refreshTextFields = installTextFields(this, document.getElementById('pdfme-text-tools'));
        this.installFieldLabels();
        this.options.onFieldsChange?.(this.getTextFields());
        this.designer.onChangeTemplate((updated) => {
            this.refreshTextFields?.();
            this.syncFieldLabels();
            this.options.onFieldsChange?.(this.getTextFields(updated));
            if (this.options.onChange) {
                this.options.onChange(this.getState(updated));
            }
        });
    }

    formatFieldLabel(name = '') {
        const trimmed = String(name || '').trim();
        if (!trimmed) return '';
        if (trimmed.startsWith('template_bg_')) return 'Background Artwork';
        return trimmed.charAt(0).toUpperCase() + trimmed.slice(1);
    }

    syncFieldLabels() {
        if (!this.container) return;
        const boxes = this.container.querySelectorAll('.selectable[title]');
        boxes.forEach((box) => {
            const rawName = box.getAttribute('title') || '';
            const label = this.formatFieldLabel(rawName);
            if (!label) {
                box.removeAttribute('data-studio-label');
                box.removeAttribute('data-label-inside');
                return;
            }
            if (box.dataset.studioLabel !== label) {
                box.dataset.studioLabel = label;
            }
            const topPx = Number.parseFloat(box.style.top || '999');
            const isFullBg = rawName.startsWith('template_bg_');
            if (isFullBg || topPx < 20) {
                box.dataset.labelInside = 'true';
            } else {
                box.removeAttribute('data-label-inside');
            }
        });
    }

    installFieldLabels() {
        const scheduleSync = () => {
            window.requestAnimationFrame(() => this.syncFieldLabels());
        };
        scheduleSync();
        setTimeout(scheduleSync, 150);
        setTimeout(scheduleSync, 500);

        this.labelObserver?.disconnect();
        this.labelObserver = new MutationObserver(() => scheduleSync());
        this.labelObserver.observe(this.container, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['title', 'style'],
        });

        const toggleBtn = document.getElementById('btn-toggle-field-labels');
        if (toggleBtn && !toggleBtn.dataset.boundLabelToggle) {
            toggleBtn.dataset.boundLabelToggle = '1';
            toggleBtn.addEventListener('click', () => {
                const enabled = this.container.classList.toggle('show-field-labels');
                toggleBtn.setAttribute('aria-pressed', enabled ? 'true' : 'false');
            });
        }
    }

    getState(currentTemplate = null) {
        const tmpl = currentTemplate || (this.designer ? this.designer.getTemplate() : this.template);
        const hasExternalBase = Boolean(this.template.base_pdf_asset_id || this.template.base_pdf_url);
        const isBinaryBase = tmpl.basePdf instanceof Uint8Array || ArrayBuffer.isView(tmpl.basePdf);
        const cleanBasePdf = hasExternalBase || isBinaryBase
            ? undefined
            : (tmpl.basePdf && typeof tmpl.basePdf === 'object' && typeof tmpl.basePdf.width === 'number'
                ? tmpl.basePdf
                : {
                    width: this.template.page?.width_mm || 210,
                    height: this.template.page?.height_mm || 297,
                    padding: [10, 10, 10, 10],
                });

        return {
            ...this.template,
            schema_version: '1.0',
            engine: 'pdfme',
            page: {
                width_mm: (!isBinaryBase && tmpl.basePdf?.width) || this.template.page?.width_mm || 210,
                height_mm: (!isBinaryBase && tmpl.basePdf?.height) || this.template.page?.height_mm || 297,
            },
            basePdf: cleanBasePdf,
            base_pdf_asset_id: this.template.base_pdf_asset_id || undefined,
            base_pdf_url: this.template.base_pdf_asset_id ? undefined : (this.template.base_pdf_url || undefined),
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
        this.template.base_pdf_url = undefined;
        this.designer.updateTemplate({ ...this.designer.getTemplate(), basePdf: new Uint8Array(await response.arrayBuffer()) });
        this.options.onChange?.(this.getState());
    }

    async setBackgroundImage(url, assetId) {
        if (!this.designer) return;
        const current = structuredClone(this.designer.getTemplate());
        const width = this.template.page?.width_mm || (this.template.document_kind === 'cotc' ? 279.4 : 210);
        const height = this.template.page?.height_mm || (this.template.document_kind === 'cotc' ? 215.9 : 297);

        this.template.base_pdf_asset_id = undefined;
        this.template.base_pdf_url = undefined;

        const cleanBasePdf = { width, height, padding: [0, 0, 0, 0] };
        const schemas = current.schemas && current.schemas.length > 0 ? [...current.schemas] : [[]];
        const page0 = (schemas[0] || []).filter(field => !String(field.name || '').startsWith('template_bg_'));

        page0.unshift(normalizeStudioSchema({
            name: `template_bg_${assetId}`,
            type: 'image',
            position: { x: 0, y: 0 },
            width,
            height,
            content: url,
            asset_id: assetId,
            readOnly: true,
        }, this.plugins));

        schemas[0] = page0;
        const updated = { ...current, basePdf: cleanBasePdf, schemas };
        this.designer.updateTemplate(updated);
        this.options.onChange?.(this.getState(updated));
        this.options.onFieldsChange?.(this.getTextFields(updated));
    }

    getTextFields(currentTemplate = null) {
        const tmpl = currentTemplate || (this.designer ? this.designer.getTemplate() : this.template);
        const fields = [];
        (tmpl.schemas || []).forEach((page, pageIndex) => {
            (page || []).forEach((field) => {
                if (field.type !== 'text' && field.type !== 'table') return;
                const rawLabel = field.binding || field.content || field.name;
                const cleanLabel = String(rawLabel).replace(/\s+/g, ' ').slice(0, 34);
                fields.push({
                    key: `${pageIndex}:${field.name}`,
                    pageIndex,
                    name: field.name,
                    type: field.type,
                    label: `${field.name} (${cleanLabel})`,
                    fontName: field.fontName || field.headStyles?.fontName || 'NotoSans',
                    fontSize: field.fontSize || field.bodyStyles?.fontSize || 12,
                    fontColor: field.fontColor || '#111827',
                    alignment: field.alignment || 'left',
                });
            });
        });
        return fields;
    }

    updateFieldStyle(fieldKey, style = {}) {
        if (!this.designer || !fieldKey) return false;
        const sep = fieldKey.indexOf(':');
        if (sep === -1) return false;
        const pageIndex = Number(fieldKey.slice(0, sep));
        const fieldName = fieldKey.slice(sep + 1);

        const current = structuredClone(this.designer.getTemplate());
        const page = current.schemas?.[pageIndex];
        if (!page) return false;
        const field = page.find(item => item.name === fieldName);
        if (!field) return false;

        if (field.type === 'table') {
            if (style.fontSize !== undefined) {
                field.bodyStyles = { ...(field.bodyStyles || {}), fontSize: Number(style.fontSize) };
                field.headStyles = { ...(field.headStyles || {}), fontSize: Number(style.fontSize) };
            }
            if (style.fontColor !== undefined) {
                field.bodyStyles = { ...(field.bodyStyles || {}), fontColor: style.fontColor };
            }
        } else {
            if (style.fontName !== undefined) field.fontName = style.fontName;
            if (style.fontSize !== undefined) field.fontSize = Number(style.fontSize);
            if (style.fontColor !== undefined) field.fontColor = style.fontColor;
            if (style.alignment !== undefined) field.alignment = style.alignment;
        }

        this.designer.updateTemplate(current);
        this.options.onChange?.(this.getState(current));
        return true;
    }

    addImage(url, assetId) {
        this.addField({ name: `image_${assetId}_${Date.now()}`, type: 'image', content: url, width: 40, height: 30 });
        const current = this.designer.getTemplate();
        current.schemas[0].at(-1).asset_id = assetId;
        this.designer.updateTemplate(current);
        this.options.onChange?.(this.getState());
    }

    applyTypographyPreset(presetKey) {
        if (!this.designer) return false;
        const preset = CERTIFICATE_STYLE_PRESETS[presetKey];
        if (!preset) return false;

        const current = structuredClone(this.designer.getTemplate());
        const roles = preset.roles;

        current.schemas = (current.schemas || []).map((page) =>
            page.map((field) => {
                if (field.type !== 'text') return field;
                const key = `${field.name || ''} ${field.binding || ''}`.toLowerCase();
                let fontName = roles.default;
                if (key.includes('trainee_name') || key.includes('name_text')) {
                    fontName = roles.name;
                } else if (key.includes('training_program') || key.includes('program_text')) {
                    fontName = roles.program;
                } else if (key.includes('statement')) {
                    fontName = roles.statement;
                } else if (key.includes('release_date') || key.includes('certificate_number') || key.includes('doc_number') || key.includes('role')) {
                    fontName = roles.meta;
                } else if (key.includes('date')) {
                    fontName = roles.date;
                } else if (key.includes('registrar') || key.includes('trainer') || key.includes('signatory')) {
                    fontName = roles.signatory;
                }
                return { ...field, fontName };
            })
        );

        this.designer.updateTemplate(current);
        this.options.onChange?.(this.getState(current));
        return true;
    }

    async renderPreviewPdf(values = {}) {
        const current = structuredClone(this.designer ? this.designer.getTemplate() : this.template);
        let basePdf = current.basePdf;
        if (basePdf instanceof Uint8Array) {
            basePdf = new Uint8Array(basePdf);
        } else if (basePdf instanceof ArrayBuffer) {
            basePdf = new Uint8Array(basePdf.slice(0));
        }
        if (!basePdf && this.template.base_pdf_asset_id) {
            const asset = this.options.assets?.find(item => item.id === this.template.base_pdf_asset_id);
            if (asset?.url) {
                const res = await fetch(asset.url, { credentials: 'same-origin' });
                if (res.ok) basePdf = new Uint8Array(await res.arrayBuffer());
            }
        }
        if (!basePdf && this.template.base_pdf_url) {
            const res = await fetch(this.template.base_pdf_url, { credentials: 'same-origin' });
            if (res.ok) basePdf = new Uint8Array(await res.arrayBuffer());
        }
        if (!basePdf) {
            const page = this.template.page || { width_mm: 279.4, height_mm: 215.9 };
            basePdf = { width: page.width_mm || 279.4, height: page.height_mm || 215.9, padding: [10, 10, 10, 10] };
        }

        const schemas = (current.schemas || [[]]).map(page => page.map(field => normalizeStudioSchema(field, this.plugins)));
        const inputRow = {};

        for (const page of schemas) {
            for (const field of page) {
                let val = undefined;
                if (['image', 'signature'].includes(field.type)) {
                    const src = field.content || field.src || field.url || '';
                    if (src && !src.startsWith('data:')) {
                        try {
                            const imgRes = await fetch(src, { credentials: 'same-origin' });
                            if (imgRes.ok) {
                                const blob = await imgRes.blob();
                                val = await new Promise((resolve, reject) => {
                                    const reader = new FileReader();
                                    reader.onloadend = () => resolve(reader.result);
                                    reader.onerror = reject;
                                    reader.readAsDataURL(blob);
                                });
                            }
                        } catch {
                            val = '';
                        }
                    } else {
                        val = src;
                    }
                }
                const rawContent = field.content ?? field.text;
                const hasInlineToken = typeof rawContent === 'string' && /\{\{\w+\}\}/.test(rawContent);

                if (val === undefined && hasInlineToken) {
                    let interpolated = rawContent;
                    for (const [k, v] of Object.entries(values)) {
                        if (v !== undefined && v !== null && typeof v !== 'object') {
                            interpolated = interpolated.replaceAll(`{{${k}}}`, String(v));
                        }
                    }
                    val = interpolated;
                }
                if (val === undefined && (field.type === 'table' || !rawContent)) {
                    if (values[field.name] !== undefined) val = values[field.name];
                    else if (field.binding) {
                        const cleanKey = field.binding.replace(/[{}]/g, '').trim();
                        if (values[cleanKey] !== undefined) val = values[cleanKey];
                        else if (values[field.binding] !== undefined) val = values[field.binding];
                        else val = field.binding.replace(/\{\{(\w+)\}\}/g, (token, key) => values[key] === undefined || typeof values[key] === 'object' ? token : String(values[key]));
                    }
                }
                if (val === undefined && typeof rawContent === 'string' && rawContent !== '') {
                    val = rawContent;
                }
                if (val === undefined && values[field.name] !== undefined) {
                    val = values[field.name];
                }
                if (val === undefined) val = '';

                if (field.type === 'table' && Array.isArray(val)) {
                    if (val.length > 0 && typeof val[0] === 'object' && !Array.isArray(val[0])) {
                        const keys = studioTables[tableBinding(field)]?.keys || Object.keys(val[0]);
                        val = JSON.stringify(val.map(row => keys.map(k => String(row[k] ?? ''))));
                    } else {
                        val = JSON.stringify(val);
                    }
                } else if (typeof val !== 'string') {
                    val = String(val);
                }

                inputRow[field.name] = val;
                if (field.readOnly) {
                    field.content = val;
                }
            }
        }

        const fontEntries = Object.entries(getBrowserPdfmeFonts());
        const font = {};
        await Promise.all(fontEntries.map(async ([key, cfg]) => {
            try {
                const res = await fetch(cfg.data, { credentials: 'same-origin' });
                if (res.ok) {
                    font[key] = { data: new Uint8Array(await res.arrayBuffer()), ...(cfg.fallback ? { fallback: true } : {}) };
                }
            } catch {
                // Skip unavailable font cleanly
            }
        }));

        for (const page of schemas) {
            for (const field of page) {
                if (field.fontName && !font[field.fontName]) {
                    field.fontName = font.NotoSans ? 'NotoSans' : undefined;
                }
                if (field.type === 'table') {
                    if (field.headStyles?.fontName && !font[field.headStyles.fontName]) {
                        field.headStyles.fontName = font.NotoSans ? 'NotoSans' : undefined;
                    }
                    if (field.bodyStyles?.fontName && !font[field.bodyStyles.fontName]) {
                        field.bodyStyles.fontName = font.NotoSans ? 'NotoSans' : undefined;
                    }
                }
            }
        }

        return generate({
            template: { basePdf, schemas },
            inputs: [inputRow],
            plugins: this.plugins,
            options: Object.keys(font).length ? { font } : undefined,
        });
    }

    destroy() {
        this.labelObserver?.disconnect();
        this.labelObserver = null;
        if (this.designer) {
            this.designer.destroy();
            this.designer = null;
        }
    }
}
