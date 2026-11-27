import { previewDocument, previewPdfBytes } from './preview.js';
import { notifyStudio } from './feedback.js';
import { CERTIFICATE_FONTS } from './fonts.js';
class DocumentStudioApp {
    constructor() {
        this.config = window.DOCUMENT_STUDIO_CONFIG || {};
        this.adapter = null;
        this.draftState = this.config.draftState || {};
        this.draftRevision = this.config.draftRevision || 1;
        this.isDirty = false;
        this.isSaving = false;
        this.autosaveTimer = null;

        this.init();
    }

    async init() {
        this.bindUiEvents();
        try {
            await this.mountEngine();
            this.updateSaveStatus('saved');
        } catch (err) {
            console.error('Document Studio mount failed:', err);
            notifyStudio(err.message || 'Could not load the document designer. Refresh or retry.', 'error', 'Editor failed to load');
        }
    }

    async mountEngine() {
        const engine = this.config.engine || 'pdfme';
        const options = {
            template: this.draftState,
            onChange: (updatedState) => this.handleStateChange(updatedState),
            onSelect: (selectedProps) => this.handleObjectSelection(selectedProps),
            onFieldsChange: (fields) => this.syncPdfmeFontEditor(fields),
            assets: this.config.assets || [],
            endpoints: this.config.endpoints,
        };

        if (engine === 'fabric') {
            const canvasEl = document.getElementById('fabric-canvas');
            if (!canvasEl) {
                console.warn('Fabric canvas element #fabric-canvas not found');
                return;
            }
            const { FabricAdapter } = await import('./fabric-adapter.js');
            this.adapter = new FabricAdapter(canvasEl, options);
        } else {
            const container = document.getElementById('studio-editor-viewport');
            if (!container) {
                console.warn('Editor viewport #studio-editor-viewport not found');
                return;
            }
            if (engine === 'rk_word') {
                const { RkWordAdapter } = await import('./rkword-adapter.js');
                this.adapter = new RkWordAdapter(container, options);
            } else {
                // Default: pdfme
                const { PdfmeAdapter } = await import('./pdfme-adapter.js');
                this.adapter = new PdfmeAdapter(container, options);
            }
        }

        await this.adapter.mount();
    }

    handleStateChange(newState) {
        this.draftState = newState;
        this.isDirty = true;
        this.updateSaveStatus('dirty');

        // Debounce autosave
        clearTimeout(this.autosaveTimer);
        this.autosaveTimer = setTimeout(() => {
            if (!this.isPublishing) {
                this.saveDraft(false).catch((err) => {
                    notifyStudio(err.message || 'Autosave failed', 'error', 'Autosave error');
                });
            }
        }, 1800);
    }

    handleObjectSelection(props) {
        const propPanel = document.getElementById('properties-panel');
        if (!propPanel) return;

        if (!props) {
            document.getElementById('no-selection-notice')?.classList.remove('hidden');
            document.getElementById('selection-controls')?.classList.add('hidden');
            return;
        }

        document.getElementById('no-selection-notice')?.classList.add('hidden');
        document.getElementById('selection-controls')?.classList.remove('hidden');

        // Populate fields
        const textInput = document.getElementById('prop-text');
        if (textInput) textInput.value = props.text || '';

        const sizeInput = document.getElementById('prop-font-size');
        if (sizeInput) sizeInput.value = props.fontSize || 14;

        const colorInput = document.getElementById('prop-color');
        if (colorInput) colorInput.value = props.color || '#000000';
    }

    async saveDraft(force = false) {
        if (this.savingPromise) {
            await this.savingPromise;
            if (!force && !this.isDirty) return;
        }
        if (force && this.adapter?.getState) {
            this.draftState = this.adapter.getState();
            this.isDirty = true;
        }
        if (!this.isDirty && !force) {
            return Promise.resolve();
        }

        this.isSaving = true;
        this.updateSaveStatus('saving');

        this.savingPromise = (async () => {
            const snapshotState = JSON.parse(JSON.stringify(this.adapter?.getState ? this.adapter.getState() : this.draftState));
            this.draftState = snapshotState;
            const snapshotRevision = this.draftRevision;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const payloadBody = JSON.stringify({
                    draft_state: snapshotState,
                    draft_revision: snapshotRevision,
                });
                const headers = {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                };

                let response = await fetch(this.config.endpoints.saveDraft, {
                    method: 'PATCH',
                    credentials: 'same-origin',
                    headers,
                    body: payloadBody,
                });

                // Shared hosts/WAFs that block PATCH accept our explicit POST fallback on the same route
                if (response.status === 405) {
                    response = await fetch(this.config.endpoints.saveDraft, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers,
                        body: payloadBody,
                    });
                }

                const contentType = response.headers.get('content-type') || '';
                const data = contentType.includes('application/json')
                    ? await response.json()
                    : { message: `Server returned HTTP ${response.status}` };

                if (!response.ok) {
                    if (response.status === 409 || data.errors?.revision) {
                        const conflictMsg = data.errors?.revision?.[0] || data.message || 'Another session updated this template. Reload before saving again.';
                        notifyStudio(conflictMsg, 'error', 'Draft changed elsewhere');
                        this.updateSaveStatus('conflict', 'Conflict — reload needed');
                        throw new Error(conflictMsg);
                    }
                    if (response.status === 419) {
                        throw new Error('Your session expired. Refresh the page and sign in again.');
                    }
                    const firstValidation = data.errors ? Object.values(data.errors).flat()[0] : null;
                    throw new Error(firstValidation || data.message || 'Save failed');
                }

                this.draftRevision = data.draft_revision;
                if (data.audit) {
                    this.updateAuditBanner(data.audit);
                }
                const savedTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                if (JSON.stringify(this.adapter?.getState ? this.adapter.getState() : this.draftState) === JSON.stringify(snapshotState)) {
                    this.isDirty = false;
                    this.updateSaveStatus('saved', `Saved ${savedTime} (rev ${this.draftRevision})`);
                } else {
                    this.updateSaveStatus('dirty');
                }

                return data;
            } catch (err) {
                console.error('Autosave error:', err);
                if (this.saveStatus !== 'conflict') {
                    this.updateSaveStatus('error', err.message ? `Save failed: ${err.message.slice(0, 40)}` : 'Save failed');
                }
                throw err;
            } finally {
                this.isSaving = false;
                this.savingPromise = null;
            }
        })();

        return this.savingPromise;
    }

    async publishVersion(changelog, makeActive = true) {
        if (this.isPublishing) return;
        this.isPublishing = true;
        clearTimeout(this.autosaveTimer);
        try {
        if (this.savingPromise) {
            await this.savingPromise;
        }
        // Publish the visible snapshot under one server revision lock. Keep
        // unsaved changes marked dirty until the server confirms success.
        const snapshotState = JSON.parse(JSON.stringify(this.draftState));
        const snapshotRevision = this.draftRevision;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch(this.config.endpoints.publish, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                },
                body: JSON.stringify({
                    changelog,
                    make_active: makeActive,
                    draft_state: snapshotState,
                    draft_revision: snapshotRevision,
                }),
            });

            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'Publishing failed');
            }
            this.draftRevision = data.draft_revision;
            this.isDirty = JSON.stringify(this.draftState) !== JSON.stringify(snapshotState);
            this.updateSaveStatus(this.isDirty ? 'dirty' : 'saved');
            const versionLabel = document.getElementById('active-version-number');
            if (makeActive && versionLabel) versionLabel.textContent = `v${data.version.version_number}`;
            document.getElementById('publish-modal')?.classList.add('hidden');
            notifyStudio(`Version ${data.version.version_number} is published.${makeActive ? ' It is now the active design.' : ''}`, 'success', 'Version published');
            return data;
        } catch (err) {
            this.isDirty = true;
            this.updateSaveStatus('error');
            throw err;
        } finally {
            this.isPublishing = false;
            if (this.isDirty) this.autosaveTimer = setTimeout(() => this.saveDraft().catch(() => {}), 2500);
        }
    }

    updateSaveStatus(status, customLabel = null) {
        this.saveStatus = status;
        const label = document.getElementById('save-status-label');
        const dot = document.getElementById('save-status-dot');
        if (!label || !dot) return;

        dot.className = 'w-2 h-2 rounded-full';

        switch (status) {
            case 'saved':
                dot.classList.add('bg-emerald-500');
                label.textContent = customLabel || `Saved (rev ${this.draftRevision})`;
                break;
            case 'saving':
                dot.classList.add('bg-amber-500', 'animate-pulse');
                label.textContent = 'Saving changes...';
                break;
            case 'dirty':
                dot.classList.add('bg-amber-400');
                label.textContent = 'Unsaved changes';
                break;
            case 'conflict':
            case 'error':
                dot.classList.add('bg-red-500');
                label.textContent = customLabel || 'Save error / conflict';
                break;
        }
    }

    bindUiEvents() {
        // Manual save button
        document.getElementById('btn-save-draft')?.addEventListener('click', async (e) => {
            e.preventDefault();
            if (this.isPublishing) return;
            clearTimeout(this.autosaveTimer);
            try {
                await this.saveDraft(true);
                notifyStudio(`Draft saved cleanly (revision ${this.draftRevision}).`, 'success', 'Draft Saved');
            } catch (err) {
                notifyStudio(err.message || 'Could not save draft.', 'error', 'Save Failed');
            }
        });

        // Keyboard shortcut: Ctrl+S / Cmd+S
        window.addEventListener('keydown', async (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                if (this.isPublishing) return;
                clearTimeout(this.autosaveTimer);
                try {
                    await this.saveDraft(true);
                    notifyStudio(`Draft saved cleanly (revision ${this.draftRevision}).`, 'success', 'Draft Saved');
                } catch (err) {
                    notifyStudio(err.message || 'Could not save draft.', 'error', 'Save Failed');
                }
            }
        });

        // Placeholders click to insert
        document.querySelectorAll('.btn-insert-placeholder').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const token = btn.getAttribute('data-token');
                const key = btn.getAttribute('data-key');
                if (btn.dataset.type === 'qr' && this.adapter?.addQr) {
                    this.adapter.addQr(token);
                } else if (btn.dataset.type === 'qr' && this.adapter?.addField) {
                    this.adapter.addField({ name: key, binding: token, type: 'qrcode', width: 25, height: 25 });
                } else if (btn.dataset.type === 'table' && this.adapter?.addField) {
                    this.adapter.addField({ name: key, binding: token, type: 'table', width: 180, height: 25 });
                } else if (this.adapter?.insertPlaceholder) {
                    this.adapter.insertPlaceholder(token);
                } else if (this.adapter?.addText) {
                    this.adapter.addText(token, token);
                } else if (this.adapter?.addField) {
                    this.adapter.addField({ name: key, binding: token, type: 'text' });
                }
            });
        });

        // Add text element button
        document.getElementById('btn-add-text')?.addEventListener('click', () => {
            if (this.adapter?.addText) {
                this.adapter.addText('New Text Box');
            } else if (this.adapter?.addField) {
                this.adapter.addField({ name: `field_${Date.now()}`, type: 'text' });
            }
        });
        document.getElementById('btn-add-rectangle')?.addEventListener('click', () => this.adapter?.addRect?.());
        document.getElementById('btn-add-circle')?.addEventListener('click', () => this.adapter?.addCircle?.());

        // Delete selected element
        document.getElementById('btn-delete-element')?.addEventListener('click', () => {
            this.adapter?.deleteSelected?.();
        });

        // Certificate typography presets (pdfme)
        document.querySelectorAll('.btn-typography-preset').forEach(btn => {
            btn.addEventListener('click', () => {
                const preset = btn.getAttribute('data-typography-preset');
                if (this.adapter?.applyTypographyPreset?.(preset)) {
                    this.syncPdfmeFontEditor(this.adapter.getTextFields?.() || []);
                    notifyStudio('Certificate font pairing applied. Save or Preview to inspect the PDF.', 'success', 'Typography updated');
                }
            });
        });

        this.bindPdfmeFontEditor();

        // Property panel live inputs
        document.getElementById('prop-text')?.addEventListener('input', (e) => {
            this.adapter?.updateActiveObject?.({ text: e.target.value });
        });
        document.getElementById('prop-font-size')?.addEventListener('input', (e) => {
            this.adapter?.updateActiveObject?.({ fontSize: Number(e.target.value) });
        });
        document.getElementById('prop-color')?.addEventListener('input', (e) => {
            this.adapter?.updateActiveObject?.({ color: e.target.value });
        });

        // Preview button
        document.getElementById('btn-preview')?.addEventListener('click', async () => {
            clearTimeout(this.autosaveTimer);
            try {
                if (!this.isPublishing && (this.isDirty || this.savingPromise)) {
                    await this.saveDraft(true);
                }
            } catch (err) {
                notifyStudio('Could not save latest draft before preview: ' + (err.message || 'Save failed'), 'error', 'Preview unavailable');
                return;
            }

            // Inside PDFme editor, render directly from the active designer template in memory first
            // so the preview is guaranteed to mirror what is currently on screen.
            if (this.config.engine === 'pdfme' && this.adapter?.renderPreviewPdf) {
                try {
                    const bytes = await this.adapter.renderPreviewPdf(this.config.samplePayload || {});
                    await previewPdfBytes(bytes, 'Live PDFme Editor Preview');
                    return;
                } catch (clientErr) {
                    console.warn('Direct PDFme preview fallback to server renderer:', clientErr);
                }
            }

            try {
                const result = await previewDocument(this.config.endpoints.preview);
                if (result?.usedServerFallback && this.adapter?.renderPreviewPdf) {
                    const bytes = await this.adapter.renderPreviewPdf(this.config.samplePayload || {});
                    await previewPdfBytes(bytes, 'PDFme Certificate Preview');
                }
            } catch (serverErr) {
                if (this.adapter?.renderPreviewPdf) {
                    try {
                        const bytes = await this.adapter.renderPreviewPdf(this.config.samplePayload || {});
                        await previewPdfBytes(bytes, 'Live Browser PDFme Preview');
                        return;
                    } catch (clientErr) {
                        notifyStudio('Could not preview: ' + (clientErr.message || serverErr.message || 'Preview failed'), 'error', 'Preview unavailable');
                        return;
                    }
                }
                notifyStudio('Could not preview: ' + (serverErr.message || 'Preview failed'), 'error', 'Preview unavailable');
            }
        });

        // Mobile tools toggle
        const drawers = [['btn-toggle-tools', 'tools-sidebar', 'left-0'], ['btn-toggle-props', 'properties-panel', 'right-0']];
        const setDrawer = (drawer, open) => {
            const [buttonId, panelId, side] = drawer;
            const panel = document.getElementById(panelId);
            if (!panel) return;
            panel.classList.toggle('hidden', !open);
            for (const className of ['flex', 'absolute', 'inset-y-0', side, 'z-30', 'shadow-2xl']) panel.classList.toggle(className, open);
            document.getElementById(buttonId)?.setAttribute('aria-expanded', String(open));
        };
        for (const drawer of drawers) document.getElementById(drawer[0])?.addEventListener('click', () => {
            const open = document.getElementById(drawer[0]).getAttribute('aria-expanded') !== 'true';
            for (const other of drawers) setDrawer(other, other === drawer && open);
        });
        window.addEventListener('keydown', event => { if (event.key === 'Escape') drawers.forEach(drawer => setDrawer(drawer, false)); });
        window.addEventListener('resize', () => drawers.forEach(drawer => setDrawer(drawer, false)));

        // Publish version modal
        document.getElementById('btn-publish-modal')?.addEventListener('click', () => {
            document.getElementById('publish-modal')?.classList.remove('hidden');
        });

        document.getElementById('btn-cancel-publish')?.addEventListener('click', () => {
            document.getElementById('publish-modal')?.classList.add('hidden');
        });

        document.getElementById('form-publish')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const changelog = document.getElementById('publish-changelog')?.value || '';
            const makeActive = document.getElementById('publish-make-active')?.checked ?? true;
            try {
                await this.publishVersion(changelog, makeActive);
            } catch (err) {
                notifyStudio(err.message, 'error', 'Publication failed');
            }
        });

        // Assets modal
        document.getElementById('btn-asset-library')?.addEventListener('click', () => {
            document.getElementById('asset-modal')?.classList.remove('hidden');
        });
        document.getElementById('btn-close-assets')?.addEventListener('click', () => {
            document.getElementById('asset-modal')?.classList.add('hidden');
        });

        // Asset upload form
        document.getElementById('form-upload-asset')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fileInput = document.getElementById('asset-file-input');
            const typeSelect = document.getElementById('asset-type-select');
            if (!fileInput?.files[0]) return;
            const selectedType = typeSelect.value;
            const isTemplatePicture = selectedType === 'template_picture';
            const method = selectedType === 'pdf_base'
                ? 'setBasePdf'
                : (isTemplatePicture ? 'setBackgroundImage' : 'addImage');
            if (typeof this.adapter?.[method] !== 'function') {
                notifyStudio('This editor cannot insert that file type. Use an image or a compatible document file.', 'info', 'File not supported');
                return;
            }

            const formData = new FormData();
            formData.append('file', fileInput.files[0]);
            formData.append('type', isTemplatePicture ? 'image' : selectedType);
            formData.append('document_template_id', this.config.templateId);

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const res = await fetch(this.config.endpoints.uploadAsset, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || '',
                    },
                    body: formData,
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Asset upload failed');

                if (data.asset?.url) {
                    this.config.assets = [...(this.config.assets || []), data.asset];
                    this.adapter?.options?.assets?.push?.(data.asset);
                    if (selectedType === 'pdf_base') {
                        await this.adapter.setBasePdf(data.asset);
                    } else if (isTemplatePicture) {
                        await this.adapter.setBackgroundImage(data.asset.url, data.asset.id);
                        notifyStudio('Template background picture applied.', 'success', 'Template Picture Updated');
                    } else if (this.adapter?.addImage) {
                        await this.adapter.addImage(data.asset.url, data.asset.id);
                    }
                }
                document.getElementById('asset-modal')?.classList.add('hidden');
                fileInput.value = '';
            } catch (err) {
                notifyStudio('Upload failed: ' + err.message, 'error', 'Upload failed');
            }
        });
    }

    updateAuditBanner(audit) {
        if (!audit) return;
        const banner = document.querySelector('[data-studio-audit]');
        if (!banner) return;

        const badge = banner.querySelector('[data-audit-badge]');
        if (badge) {
            badge.className = `inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold ${audit.is_ready_for_issuance ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300'}`;
            badge.textContent = audit.is_ready_for_issuance ? 'Ready for Official Issuance' : 'Needs Staff Binding Review';
        }

        const summary = banner.querySelector('[data-audit-summary]');
        if (summary) summary.textContent = audit.summary || '';

        const fields = banner.querySelector('[data-audit-fields]');
        if (fields) fields.textContent = `Editable Fields: ${audit.editable_field_count ?? 0}`;

        const bindings = banner.querySelector('[data-audit-bindings]');
        if (bindings) {
            const count = audit.recognized_tokens?.length ?? 0;
            const heading = bindings.querySelector('p');
            if (heading) heading.textContent = `Dynamic Bindings (${count})`;
            let ul = bindings.querySelector('ul');
            if (count > 0) {
                if (!ul) {
                    ul = document.createElement('ul');
                    ul.className = 'space-y-0.5 text-gray-600 dark:text-gray-400';
                    bindings.replaceChildren(heading, ul);
                }
                const items = (audit.recognized_tokens || []).slice(0, 5).map(tok => {
                    const li = document.createElement('li');
                    li.innerHTML = `<span class="font-mono text-indigo-600 dark:text-indigo-400 font-semibold">${tok.token}</span>: ${tok.label}`;
                    return li;
                });
                if (count > 5) {
                    const more = document.createElement('li');
                    more.className = 'text-gray-400';
                    more.textContent = `+${count - 5} more bindings`;
                    items.push(more);
                }
                ul.replaceChildren(...items);
            } else {
                const empty = document.createElement('p');
                empty.className = 'text-amber-600 dark:text-amber-400';
                empty.textContent = 'No dynamic placeholders mapped yet.';
                bindings.replaceChildren(heading, empty);
            }
        }

        const checklist = banner.querySelector('[data-audit-checklist]');
        if (checklist) {
            const heading = checklist.querySelector('p');
            if (audit.warnings?.length) {
                const ul = document.createElement('ul');
                ul.className = 'space-y-1 text-amber-700 dark:text-amber-300';
                audit.warnings.forEach(w => {
                    const li = document.createElement('li');
                    li.className = 'flex items-start gap-1.5';
                    li.innerHTML = `<span class="font-bold">•</span><span>${w}</span>`;
                    ul.appendChild(li);
                });
                checklist.replaceChildren(heading, ul);
            } else {
                const ok = document.createElement('p');
                ok.className = 'text-emerald-600 dark:text-emerald-400 font-medium';
                ok.textContent = '✓ All required tokens and structural tables verified.';
                checklist.replaceChildren(heading, ok);
            }
        }
    }

    bindPdfmeFontEditor() {
        const fieldSelect = document.getElementById('pdfme-font-field');
        const familySelect = document.getElementById('pdfme-font-family');
        const sizeInput = document.getElementById('pdfme-font-size');
        const colorInput = document.getElementById('pdfme-font-color');
        if (!fieldSelect || !familySelect) return;

        const groups = {
            script: 'Calligraphy / Script',
            serif: 'Formal / Classic Serif',
            sans: 'Clean Sans-Serif',
        };
        familySelect.replaceChildren();
        for (const [cat, label] of Object.entries(groups)) {
            const optgroup = document.createElement('optgroup');
            optgroup.label = label;
            CERTIFICATE_FONTS.filter(f => f.category === cat).forEach(f => {
                const opt = document.createElement('option');
                opt.value = f.key;
                opt.textContent = f.label;
                optgroup.append(opt);
            });
            familySelect.append(optgroup);
        }

        const syncControlsFromSelected = () => {
            const fields = this.adapter?.getTextFields?.() || [];
            const active = fields.find(f => f.key === fieldSelect.value);
            if (!active) return;
            familySelect.value = active.fontName || 'NotoSans';
            if (sizeInput) sizeInput.value = active.fontSize || 12;
            if (colorInput && /^#[0-9a-fA-F]{6}$/.test(active.fontColor || '')) {
                colorInput.value = active.fontColor;
            }
        };

        fieldSelect.addEventListener('change', syncControlsFromSelected);
        familySelect.addEventListener('change', () => {
            this.adapter?.updateFieldStyle?.(fieldSelect.value, { fontName: familySelect.value });
        });
        sizeInput?.addEventListener('input', () => {
            const val = Number(sizeInput.value);
            if (val >= 6 && val <= 96) {
                this.adapter?.updateFieldStyle?.(fieldSelect.value, { fontSize: val });
            }
        });
        colorInput?.addEventListener('input', () => {
            this.adapter?.updateFieldStyle?.(fieldSelect.value, { fontColor: colorInput.value });
        });
        document.querySelectorAll('[data-pdfme-align]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.adapter?.updateFieldStyle?.(fieldSelect.value, { alignment: btn.dataset.pdfmeAlign });
            });
        });
    }

    syncPdfmeFontEditor(fields) {
        const fieldSelect = document.getElementById('pdfme-font-field');
        const familySelect = document.getElementById('pdfme-font-family');
        const sizeInput = document.getElementById('pdfme-font-size');
        const colorInput = document.getElementById('pdfme-font-color');
        if (!fieldSelect) return;

        const previous = fieldSelect.value;
        fieldSelect.replaceChildren();
        (fields || []).forEach(f => {
            const opt = document.createElement('option');
            opt.value = f.key;
            opt.textContent = f.label;
            fieldSelect.append(opt);
        });
        if (previous && (fields || []).some(f => f.key === previous)) {
            fieldSelect.value = previous;
        }
        const active = (fields || []).find(f => f.key === fieldSelect.value);
        if (active) {
            if (familySelect) familySelect.value = active.fontName || 'NotoSans';
            if (sizeInput) sizeInput.value = active.fontSize || 12;
            if (colorInput && /^#[0-9a-fA-F]{6}$/.test(active.fontColor || '')) {
                colorInput.value = active.fontColor;
            }
        }
    }
}

const bootStudioApp = () => {
    if (document.getElementById('studio-editor-viewport') || document.getElementById('fabric-canvas')) {
        window.studioApp = new DocumentStudioApp();
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootStudioApp);
} else {
    bootStudioApp();
}
