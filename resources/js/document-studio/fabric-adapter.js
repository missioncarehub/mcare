import { Canvas, Textbox, Rect, Circle, FabricImage } from 'fabric';
import { CERTIFICATE_FONTS, CERTIFICATE_STYLE_PRESETS } from './fonts.js';

export class FabricAdapter {
    constructor(canvasElement, options = {}) {
        this.canvasElement = canvasElement;
        this.options = options;
        this.canvas = null;
        this.template = options.template || {};
        this.scale = 3.5; // Scale factor from mm to canvas screen pixels
    }

    async mount() {
        if (!this.canvasElement) return;

        // Preload self-hosted certificate fonts for HTML5 canvas rendering
        if (typeof document !== 'undefined' && document.fonts) {
            await Promise.allSettled(
                CERTIFICATE_FONTS.map(f => {
                    const face = new FontFace(f.key, `url(/fonts/certificate/${f.file})`);
                    document.fonts.add(face);
                    return face.load();
                })
            );
        }

        const page = this.template.page || { width_mm: 279.4, height_mm: 215.9 };
        const canvasW = (page.width_mm || 279.4) * this.scale;
        const canvasH = (page.height_mm || 215.9) * this.scale;

        this.canvas = new Canvas(this.canvasElement, {
            width: canvasW,
            height: canvasH,
            backgroundColor: '#ffffff',
            preserveObjectStacking: true,
        });

        // Saved coordinates use top-left anchors; Fabric 7 defaults to center.
        // Load background image if present
        const backgroundUrl = this.options.assets?.find(item => item.id === this.template.background_asset_id)?.url || this.template.background?.source;
        if (backgroundUrl) {
            try {
                const img = await FabricImage.fromURL(backgroundUrl);
                img.set({
                    originX: 'left',
                    originY: 'top',
                    left: 0,
                    top: 0,
                    scaleX: canvasW / img.width,
                    scaleY: canvasH / img.height,
                    selectable: false,
                    evented: false,
                });
                this.canvas.backgroundImage = img;
                this.canvas.renderAll();
            } catch (err) {
                console.warn('Could not load background image:', err);
            }
        }

        // Load existing elements
        const elements = this.template.elements || [];
        for (const el of elements) {
            if (el.type === 'text') {
                const textObj = new Textbox(el.text || el.binding || 'Text', {
                    originX: 'left',
                    originY: 'top',
                    left: (el.x ?? 10) * this.scale,
                    top: (el.y ?? 10) * this.scale,
                    width: (el.width || 60) * this.scale,
                    fontSize: (el.fontSize || 14) * (this.scale / (72 / 25.4)),
                    fontFamily: el.fontFamily || 'Times New Roman',
                    fontWeight: el.fontWeight || 'normal',
                    textAlign: el.textAlign || 'left',
                    fill: el.color || '#111827',
                    angle: el.angle || 0,
                });
                textObj.binding = el.binding || '';
                textObj.elementId = el.id || '';
                this.canvas.add(textObj);
            } else if (el.type === 'rect') {
                const rectObj = new Rect({
                    originX: 'left',
                    originY: 'top',
                    left: (el.x ?? 10) * this.scale,
                    top: (el.y ?? 10) * this.scale,
                    width: (el.width || 40) * this.scale,
                    height: (el.height || 40) * this.scale,
                    fill: el.fill || '#e5e7eb',
                    stroke: el.stroke || '#9ca3af',
                    strokeWidth: el.strokeWidth || 1,
                    angle: el.angle || 0,
                });
                rectObj.elementId = el.id || '';
                this.canvas.add(rectObj);
            } else if (el.type === 'circle') {
                const circleObj = new Circle({
                    originX: 'left',
                    originY: 'top',
                    left: (el.x ?? 10) * this.scale,
                    top: (el.y ?? 10) * this.scale,
                    radius: (el.radius || 20) * this.scale,
                    fill: el.fill || '#e5e7eb',
                    stroke: el.stroke || '#9ca3af',
                    strokeWidth: el.strokeWidth || 1,
                    angle: el.angle || 0,
                });
                circleObj.elementId = el.id || '';
                circleObj.set('scaleY', (el.radius_y || el.radius || 20) / (el.radius || 20));
                this.canvas.add(circleObj);
            } else if (el.type === 'qr') {
                const qrBox = new Rect({
                    originX: 'left',
                    originY: 'top',
                    left: (el.x ?? 10) * this.scale,
                    top: (el.y ?? 10) * this.scale,
                    width: (el.width || 30) * this.scale,
                    height: (el.height || 30) * this.scale,
                    fill: '#ffffff',
                    stroke: '#111827',
                    strokeWidth: 2,
                    angle: el.angle || 0,
                });
                qrBox.elementId = el.id || '';
                qrBox.binding = el.binding || '{{qr_code}}';
                qrBox.isQr = true;
                this.canvas.add(qrBox);
            } else if (el.type === 'image' && (el.src || el.url)) {
                try {
                    const img = await FabricImage.fromURL(el.src || el.url);
                    img.set({
                        originX: 'left',
                        originY: 'top',
                        left: (el.x ?? 10) * this.scale,
                        top: (el.y ?? 10) * this.scale,
                        angle: el.angle || 0,
                    });
                    if (el.width) img.scaleToWidth(el.width * this.scale);
                    if (el.height) img.scaleToHeight(el.height * this.scale);
                    img.elementId = el.id || '';
                    img.assetId = el.asset_id;
                    this.canvas.add(img);
                } catch (e) {
                    console.warn('Could not load canvas image:', e);
                }
            }
        }

        this.canvas.renderAll();
        this.options.onFieldsChange?.(this.getTextFields());

        // Bind modification events
        const triggerChange = () => {
            this.options.onFieldsChange?.(this.getTextFields());
            if (this.options.onChange) {
                this.options.onChange(this.getState());
            }
        };

        this.canvas.on('object:modified', triggerChange);
        this.canvas.on('object:added', triggerChange);
        this.canvas.on('object:removed', triggerChange);

        // Bind selection events for properties panel
        this.canvas.on('selection:created', (e) => this.handleSelection(e.selected[0]));
        this.canvas.on('selection:updated', (e) => this.handleSelection(e.selected[0]));
        this.canvas.on('selection:cleared', () => this.handleSelection(null));
    }

    handleSelection(obj) {
        if (this.options.onSelect) {
            if (!obj) {
                this.options.onSelect(null);
                return;
            }
            this.options.onSelect({
                type: obj.type,
                text: obj.text || '',
                binding: obj.binding || '',
                fontSize: Math.round(obj.fontSize / (this.scale / (72 / 25.4))),
                fontFamily: obj.fontFamily || 'sans-serif',
                fontWeight: obj.fontWeight || 'normal',
                textAlign: obj.textAlign || 'left',
                color: obj.fill || '#000000',
            });
        }
    }

    updateActiveObject(props = {}) {
        const active = this.canvas?.getActiveObject();
        if (!active) return;

        if (props.text !== undefined && active.set) active.set('text', props.text);
        if (props.binding !== undefined) active.binding = props.binding;
        if (props.fontSize !== undefined) active.set('fontSize', props.fontSize * (this.scale / (72 / 25.4)));
        if (props.fontFamily !== undefined) active.set('fontFamily', props.fontFamily);
        if (props.fontWeight !== undefined) active.set('fontWeight', props.fontWeight);
        if (props.textAlign !== undefined) active.set('textAlign', props.textAlign);
        if (props.color !== undefined) active.set('fill', props.color);

        this.canvas.renderAll();
        if (this.options.onChange) {
            this.options.onChange(this.getState());
        }
    }

    addText(text = 'New Text', binding = '') {
        if (!this.canvas) return;
        const textObj = new Textbox(binding ? binding : text, {
            originX: 'left',
            originY: 'top',
            left: 50,
            top: 50,
            width: 200,
            fontSize: 16 * (this.scale / (72 / 25.4)),
            fontFamily: 'Arial',
            fill: '#111827',
        });
        textObj.binding = binding;
        textObj.elementId = 'el_' + Date.now();
        this.canvas.add(textObj);
        this.canvas.setActiveObject(textObj);
        this.canvas.renderAll();
    }

    async addImage(url, assetId) {
        if (!this.canvas) return;
        try {
            const img = await FabricImage.fromURL(url);
            img.scaleToWidth(150);
            img.set({ originX: 'left', originY: 'top', left: 60, top: 60 });
            img.assetId = assetId;
            this.canvas.add(img);
            this.canvas.setActiveObject(img);
            this.canvas.renderAll();
        } catch (e) {
            throw new Error('The image could not be inserted. Please retry with a valid image.', { cause: e });
        }
    }

    addRect() {
        if (!this.canvas) return;
        const rect = new Rect({
            originX: 'left',
            originY: 'top',
            left: 50,
            top: 50,
            width: 40 * this.scale,
            height: 40 * this.scale,
            fill: '#f3f4f6',
            stroke: '#9ca3af',
            strokeWidth: 1,
        });
        rect.elementId = 'rect_' + Date.now();
        this.canvas.add(rect);
        this.canvas.setActiveObject(rect);
        this.canvas.renderAll();
    }

    addCircle() {
        if (!this.canvas) return;
        const circle = new Circle({
            originX: 'left',
            originY: 'top',
            left: 50,
            top: 50,
            radius: 20 * this.scale,
            fill: '#f3f4f6',
            stroke: '#9ca3af',
            strokeWidth: 1,
        });
        circle.elementId = 'circle_' + Date.now();
        this.canvas.add(circle);
        this.canvas.setActiveObject(circle);
        this.canvas.renderAll();
    }

    addQr(binding = '{{qr_code}}') {
        if (!this.canvas) return;
        const qrBox = new Rect({
            originX: 'left',
            originY: 'top',
            left: 50,
            top: 50,
            width: 30 * this.scale,
            height: 30 * this.scale,
            fill: '#ffffff',
            stroke: '#111827',
            strokeWidth: 2,
        });
        qrBox.elementId = 'qr_' + Date.now();
        qrBox.binding = binding;
        qrBox.isQr = true;
        this.canvas.add(qrBox);
        this.canvas.setActiveObject(qrBox);
        this.canvas.renderAll();
    }

    deleteSelected() {
        const active = this.canvas?.getActiveObject();
        if (active) {
            this.canvas.remove(active);
            this.canvas.discardActiveObject();
            this.canvas.renderAll();
        }
    }

    bringForward() {
        const active = this.canvas?.getActiveObject();
        if (active) {
            this.canvas.bringObjectForward(active);
            this.canvas.renderAll();
            this.options.onChange?.(this.getState());
        }
    }

    sendBackward() {
        const active = this.canvas?.getActiveObject();
        if (active) {
            this.canvas.sendObjectBackwards(active);
            this.canvas.renderAll();
            this.options.onChange?.(this.getState());
        }
    }

    getState() {
        if (!this.canvas) return this.template;

        const objects = this.canvas.getObjects();
        const elements = [];

        for (const obj of objects) {
            if (obj.type === 'textbox' || obj.type === 'text' || obj.type === 'i-text') {
                elements.push({
                    id: obj.elementId || `text_${elements.length}`,
                    type: 'text',
                    x: Math.round(obj.left / this.scale),
                    y: Math.round(obj.top / this.scale),
                    width: Math.round((obj.width * (obj.scaleX || 1)) / this.scale),
                    height: Math.round((obj.height * (obj.scaleY || 1)) / this.scale),
                    text: obj.text,
                    binding: obj.binding || '',
                    fontSize: Math.round((obj.fontSize || 14) / (this.scale / (72 / 25.4))),
                    fontFamily: obj.fontFamily || 'Times New Roman',
                    fontWeight: obj.fontWeight || 'normal',
                    textAlign: obj.textAlign || 'left',
                    color: obj.fill || '#111827',
                    angle: Math.round(obj.angle || 0),
                });
            } else if (obj.type === 'rect') {
                if (obj.isQr || obj.binding?.includes('qr_code')) {
                    elements.push({
                        id: obj.elementId || `qr_${elements.length}`,
                        type: 'qr',
                        x: Math.round(obj.left / this.scale),
                        y: Math.round(obj.top / this.scale),
                        width: Math.round((obj.width * (obj.scaleX || 1)) / this.scale),
                        height: Math.round((obj.height * (obj.scaleY || 1)) / this.scale),
                        binding: obj.binding || '{{qr_code}}',
                        angle: Math.round(obj.angle || 0),
                    });
                } else {
                    elements.push({
                        id: obj.elementId || `rect_${elements.length}`,
                        type: 'rect',
                        x: Math.round(obj.left / this.scale),
                        y: Math.round(obj.top / this.scale),
                        width: Math.round((obj.width * (obj.scaleX || 1)) / this.scale),
                        height: Math.round((obj.height * (obj.scaleY || 1)) / this.scale),
                        fill: obj.fill || '#f3f4f6',
                        stroke: obj.stroke || '#9ca3af',
                        strokeWidth: obj.strokeWidth || 1,
                        angle: Math.round(obj.angle || 0),
                    });
                }
            } else if (obj.type === 'circle') {
                elements.push({
                    id: obj.elementId || `circle_${elements.length}`,
                    type: 'circle',
                    x: Math.round(obj.left / this.scale),
                    y: Math.round(obj.top / this.scale),
                    radius: Math.round((obj.radius * (obj.scaleX || 1)) / this.scale),
                    radius_y: Math.round((obj.radius * (obj.scaleY || 1)) / this.scale),
                    fill: obj.fill || '#f3f4f6',
                    stroke: obj.stroke || '#9ca3af',
                    strokeWidth: obj.strokeWidth || 1,
                    angle: Math.round(obj.angle || 0),
                });
            } else if (obj.type === 'image') {
                const src = obj.getSrc ? obj.getSrc() : (obj._element?.src || '');
                elements.push({
                    id: obj.elementId || `img_${elements.length}`,
                    type: 'image',
                    x: Math.round(obj.left / this.scale),
                    y: Math.round(obj.top / this.scale),
                    width: Math.round((obj.width * (obj.scaleX || 1)) / this.scale),
                    height: Math.round((obj.height * (obj.scaleY || 1)) / this.scale),
                    src: src,
                    asset_id: obj.assetId,
                    angle: Math.round(obj.angle || 0),
                });
            }
        }

        const page = this.template.page || { width_mm: 279.4, height_mm: 215.9 };

        return {
            ...this.template,
            schema_version: '1.0',
            engine: 'fabric',
            page,
            background: this.template.background || null,
            elements,
        };
    }

    async setBackgroundImage(url, assetId) {
        if (!this.canvas) return;
        const canvasW = this.canvas.getWidth();
        const canvasH = this.canvas.getHeight();
        const img = await FabricImage.fromURL(url);
        img.set({
            originX: 'left',
            originY: 'top',
            left: 0,
            top: 0,
            scaleX: canvasW / img.width,
            scaleY: canvasH / img.height,
            selectable: false,
            evented: false,
        });
        this.template.background_asset_id = assetId;
        this.canvas.backgroundImage = img;
        this.canvas.renderAll();
        this.options.onChange?.(this.getState());
    }

    getTextFields() {
        if (!this.canvas) return [];
        const fields = [];
        this.canvas.getObjects().forEach((obj, idx) => {
            if (!['textbox', 'text', 'i-text'].includes(obj.type)) return;
            const id = obj.elementId || `text_${idx}`;
            obj.elementId = id;
            const cleanLabel = String(obj.binding || obj.text || id).replace(/\s+/g, ' ').slice(0, 34);
            fields.push({
                key: id,
                name: id,
                type: 'text',
                label: `${id} (${cleanLabel})`,
                fontName: obj.fontFamily || 'NotoSans',
                fontSize: Math.round((obj.fontSize || 14) / (this.scale / (72 / 25.4))),
                fontColor: obj.fill || '#111827',
                alignment: obj.textAlign || 'left',
            });
        });
        return fields;
    }

    updateFieldStyle(fieldKey, style = {}) {
        if (!this.canvas || !fieldKey) return false;
        const obj = this.canvas.getObjects().find(item => item.elementId === fieldKey);
        if (!obj) return false;
        if (style.fontName !== undefined) obj.set('fontFamily', style.fontName);
        if (style.fontSize !== undefined) obj.set('fontSize', Number(style.fontSize) * (this.scale / (72 / 25.4)));
        if (style.fontColor !== undefined) obj.set('fill', style.fontColor);
        if (style.alignment !== undefined) obj.set('textAlign', style.alignment);
        this.canvas.renderAll();
        this.options.onChange?.(this.getState());
        return true;
    }

    applyTypographyPreset(presetKey) {
        if (!this.canvas) return false;
        const preset = CERTIFICATE_STYLE_PRESETS[presetKey];
        if (!preset) return false;
        const roles = preset.roles;
        this.canvas.getObjects().forEach((obj) => {
            if (!['textbox', 'text', 'i-text'].includes(obj.type)) return;
            const key = `${obj.elementId || ''} ${obj.binding || ''}`.toLowerCase();
            let fontName = roles.default;
            if (key.includes('trainee_name') || key.includes('name_text')) fontName = roles.name;
            else if (key.includes('training_program') || key.includes('program_text')) fontName = roles.program;
            else if (key.includes('statement')) fontName = roles.statement;
            else if (key.includes('release_date') || key.includes('certificate_number') || key.includes('doc_number') || key.includes('role')) fontName = roles.meta;
            else if (key.includes('date')) fontName = roles.date;
            else if (key.includes('registrar') || key.includes('trainer') || key.includes('signatory')) fontName = roles.signatory;
            obj.set('fontFamily', fontName);
        });
        this.canvas.renderAll();
        this.options.onChange?.(this.getState());
        return true;
    }

    destroy() {
        if (this.canvas) {
            this.canvas.dispose();
            this.canvas = null;
        }
    }
}
