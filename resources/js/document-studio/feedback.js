export function notifyStudio(message, type = 'info', title = 'Document Studio') {
    const detail = { message: String(message).slice(0, 1000), type, title };
    if (window.Livewire?.dispatchTo) {
        window.Livewire.dispatchTo('document-studio-feedback', 'studio-feedback', detail);
    } else if (typeof window.dispatchEvent === 'function' && typeof CustomEvent === 'function') {
        window.dispatchEvent(new CustomEvent('dashboard-toast', { detail: { ...detail, duration: 8000 } }));
    }
}
