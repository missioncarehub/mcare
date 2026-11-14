<script>
    const toast = document.getElementById('action-toast');

    function showActionToast(message) {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.remove('hidden');
        window.clearTimeout(window.mcareToastTimer);
        window.mcareToastTimer = window.setTimeout(() => toast.classList.add('hidden'), 2800);
    }

    window.showActionToast = showActionToast;

    document.querySelectorAll('[data-copy-enrollment-number]').forEach((button) => {
        button.addEventListener('click', async () => {
            const value = button.getAttribute('data-copy-value') || '';
            if (!value) return;

            try {
                await navigator.clipboard.writeText(value);
                showActionToast('Enrollment number copied.');
            } catch (error) {
                window.prompt('Copy this enrollment number', value);
            }
        });
    });

    document.querySelectorAll('[data-single-action]:not([data-payment-choice-form])').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.submitted === 'true') {
                event.preventDefault();
                showActionToast('Too many actions. Please wait for the current request to finish.');
                return;
            }

            form.dataset.submitted = 'true';
            form.querySelectorAll('[data-action-button]').forEach((actionButton) => {
                actionButton.disabled = true;
                actionButton.classList.add('cursor-not-allowed', 'opacity-70');
                actionButton.textContent = 'Continuing…';
            });
        });
    });
</script>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\enrollment\partials\payment-scripts.blade.php ENDPATH**/ ?>