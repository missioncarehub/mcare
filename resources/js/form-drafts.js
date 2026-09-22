const formDraftStoragePrefix = 'mcare-form-draft:';
const formDraftSkipNames = new Set(['_token', '_method', 'password', 'password_confirmation', 'signature_data']);
const addressDatasetKeys = {
    region: 'addressRegion',
    province: 'addressProvince',
    city: 'addressCity',
    barangay: 'addressBarangay',
};

const shouldPersistFormField = (field) => {
    if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement)) {
        return false;
    }

    if (!field.name || field.disabled || field.readOnly) {
        return false;
    }

    if (formDraftSkipNames.has(field.name) || field.name.startsWith('_')) {
        return false;
    }

    if (field.type === 'password' || field.type === 'file' || field.type === 'hidden') {
        return false;
    }

    return true;
};

const readDraft = (storageKey) => {
    try {
        const raw = window.sessionStorage.getItem(storageKey);
        const parsed = raw ? JSON.parse(raw) : null;

        return parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : null;
    } catch (error) {
        return null;
    }
};

const writeDraft = (storageKey, values) => {
    try {
        const hasValue = Object.values(values).some((value) => value !== '' && value != null);
        if (!hasValue) {
            window.sessionStorage.removeItem(storageKey);
            return;
        }

        window.sessionStorage.setItem(storageKey, JSON.stringify(values));
    } catch (error) {
        // Private mode or a full session store should not break the form.
    }
};

const removeDraftKeys = (prefix) => {
    try {
        const keys = [];
        for (let index = 0; index < window.sessionStorage.length; index += 1) {
            const key = window.sessionStorage.key(index);
            if (key === prefix || key?.startsWith(`${prefix}.`)) {
                keys.push(key);
            }
        }
        keys.forEach((key) => window.sessionStorage.removeItem(key));
    } catch (error) {
        // Ignore storage failures while clearing completed drafts.
    }
};

const collectFormDraft = (form) => {
    const values = {};

    form.querySelectorAll('input, select, textarea').forEach((field) => {
        if (!shouldPersistFormField(field)) {
            return;
        }

        if (field.type === 'checkbox') {
            values[field.name] = field.checked ? (field.value || '1') : '';
            return;
        }

        if (field.type === 'radio') {
            if (field.checked) {
                values[field.name] = field.value;
            }
            return;
        }

        values[field.name] = field.value;
    });

    return values;
};

const applyFormDraft = (form, values) => {
    Object.entries(values).forEach(([name, value]) => {
        const fields = form.querySelectorAll(`[name="${CSS.escape(name)}"]`);
        fields.forEach((field) => {
            if (!shouldPersistFormField(field)) {
                return;
            }

            if (field.type === 'checkbox') {
                field.checked = Boolean(value);
                return;
            }

            if (field.type === 'radio') {
                field.checked = field.value === value;
                return;
            }

            const addressKey = addressDatasetKeys[field.dataset.addressField || ''];
            if (addressKey && typeof value === 'string') {
                form.dataset[addressKey] = value;
            }

            field.value = value ?? '';
        });
    });
};

const attachFormDraft = (form) => {
    const draftName = (form.dataset.formDraft || '').trim();
    if (!draftName) {
        return;
    }

    const storageKey = formDraftStoragePrefix + draftName;
    const saved = readDraft(storageKey);
    const hasServerOld = form.hasAttribute('data-form-draft-server-old');

    if (saved && !hasServerOld) {
        applyFormDraft(form, saved);
        form.dispatchEvent(new Event('input', { bubbles: true }));
        form.dispatchEvent(new Event('change', { bubbles: true }));
    }

    writeDraft(storageKey, collectFormDraft(form));

    let persistTimer = null;
    const persist = () => {
        window.clearTimeout(persistTimer);
        persistTimer = window.setTimeout(() => {
            writeDraft(storageKey, collectFormDraft(form));
        }, 120);
    };

    form.addEventListener('input', persist);
    form.addEventListener('change', persist);
    form.addEventListener('address-lookup-updated', persist);
    form.addEventListener('submit', () => {
        window.clearTimeout(persistTimer);
        writeDraft(storageKey, collectFormDraft(form));
    });
};

const clearCompletedFormDrafts = () => {
    document.querySelectorAll('[data-form-draft-clear]').forEach((node) => {
        const name = (node.dataset.formDraftClear || '').trim();
        if (name) {
            removeDraftKeys(formDraftStoragePrefix + name);
        }
    });
};

export const attachFormDrafts = () => {
    clearCompletedFormDrafts();
    document.querySelectorAll('[data-form-draft]').forEach(attachFormDraft);
};
