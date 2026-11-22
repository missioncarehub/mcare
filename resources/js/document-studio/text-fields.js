export function installTextFields(adapter, root) {
    if (!root) return () => {};
    const list = root.querySelector('[data-text-fields]'), feedback = root.querySelector('[data-text-feedback]');
    function refresh() {
        const selected = new Set([...list.selectedOptions].map(option => option.value));
        list.replaceChildren();
        adapter.designer.getTemplate().schemas.forEach((fields, page) => fields.forEach(field => {
            if (field.type !== 'text') return;
            const option = document.createElement('option');
            option.value = `${page}:${field.name}`; option.selected = selected.has(option.value);
            option.textContent = `Page ${page + 1}: ${String(field.content || field.name).slice(0, 70)}`;
            list.append(option);
        }));
    }
    root.addEventListener('click', event => {
        const action = event.target.closest('[data-text-action]')?.dataset.textAction;
        if (!action) return;
        const current = structuredClone(adapter.designer.getTemplate());
        const selected = [...list.selectedOptions].map(option => {
            const separator = option.value.indexOf(':'); const page = Number(option.value.slice(0, separator));
            return { page, field: current.schemas[page].find(field => field.name === option.value.slice(separator + 1)) };
        }).filter(item => item.field);
        try {
            if (!selected.length) throw new Error('Select a text field first.');
            if (action === 'bind') {
                const binding = root.querySelector('[data-text-binding]').value;
                if (!binding) throw new Error('Choose an MCARE field.');
                for (const { field } of selected) { field.content = binding; field.binding = binding; }
            } else if (action === 'merge') {
                if (selected.length < 2 || selected.some(item => item.page !== selected[0].page || item.field.rotate)) throw new Error('Select at least two unrotated fields on the same page.');
                const fields = selected.map(item => item.field).sort((a, b) => a.position.y - b.position.y || a.position.x - b.position.x);
                const left = Math.min(...fields.map(f => f.position.x)), top = Math.min(...fields.map(f => f.position.y));
                const merged = { ...fields[0], name: `merged_${Date.now()}`, binding: '', content: fields.map(f => f.content).join(' '),
                    position: { x: left, y: top }, width: Math.max(...fields.map(f => f.position.x + f.width)) - left,
                    height: Math.max(...fields.map(f => f.position.y + f.height)) - top };
                current.schemas[selected[0].page] = current.schemas[selected[0].page].filter(field => !fields.includes(field));
                current.schemas[selected[0].page].push(merged);
            } else if (action === 'split') {
                if (selected.length !== 1 || selected[0].field.rotate) throw new Error('Select one unrotated text field to split.');
                const { page, field } = selected[0], separator = root.querySelector('[data-text-separator]').value;
                const parts = String(field.content).split(separator || '\n').filter(Boolean);
                if (parts.length < 2 || parts.length > 20) throw new Error('The separator must split this text into 2–20 parts.');
                current.schemas[page] = current.schemas[page].filter(item => item !== field);
                let x = field.position.x;
                parts.forEach((part, index) => {
                    const width = field.width * part.length / parts.join('').length;
                    current.schemas[page].push({ ...field, name: `split_${Date.now()}_${index}`, binding: '', content: part,
                        position: { x, y: field.position.y }, width }); x += width;
                });
            }
            adapter.designer.updateTemplate(current); adapter.options.onChange?.(adapter.getState()); refresh();
            feedback.textContent = 'Text fields updated. Review their position and spacing.';
        } catch (error) { feedback.textContent = error.message; }
    });
    refresh(); return refresh;
}
