export const studioTables = {
    soa_ledger: { keys: ['date', 'or_number', 'amount', 'balance'], head: ['Date Paid', 'OR / Invoice No.', 'Amount Paid (PHP)', 'Balance (PHP)'], widths: [17.4, 28.3, 27.2, 27.1] },
    tor_competencies: { keys: ['code', 'title', 'hours', 'grade', 'status'], head: ['Code', 'Competency', 'Hours', 'Grade', 'Status'], widths: [16, 43, 10, 10, 21] },
    soa_assessments: { keys: ['item', 'amount'], head: ['Assessment', 'Amount'], widths: [70, 30] },
    soa_payments: { keys: ['date', 'particulars', 'or_number', 'amount', 'status'], head: ['Date', 'Particulars', 'Receipt', 'Amount', 'Status'], widths: [18, 28, 18, 18, 18] },
};

export function tableBinding(field) {
    return [field.binding, field.name, field.content].find(value => typeof value === 'string' && studioTables[value.replace(/[{}]/g, '').trim()])?.replace(/[{}]/g, '').trim();
}

export function normalizeStudioSchema(field, plugins) {
    const defaults = plugins[field.type]?.propPanel?.defaultSchema || {};
    const schema = { ...structuredClone(defaults), ...field };
    // Laravel converts empty request strings to null; pdfme requires string content.
    schema.content = field.content ?? defaults.content ?? '';
    const definition = studioTables[tableBinding(field)];
    if (field.type === 'table' && definition) {
        schema.head = field.head?.length === definition.keys.length ? field.head : definition.head;
        schema.headWidthPercentages = field.headWidthPercentages?.length === definition.keys.length ? field.headWidthPercentages : definition.widths;
        schema.content = /^\s*\[/.test(field.content || '') ? field.content : '[]';
        schema.bodyStyles = { ...defaults.bodyStyles, fontSize: 9, padding: { top: 2, bottom: 2, left: 2, right: 2 }, ...field.bodyStyles };
        schema.headStyles = { ...defaults.headStyles, fontSize: 9, padding: { top: 2, bottom: 2, left: 2, right: 2 }, ...field.headStyles };
    }
    return schema;
}
