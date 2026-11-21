(() => {
    'use strict';
    let submitting = false;
    document.querySelectorAll('[data-review-open]').forEach(trigger => {
        const dialog = document.querySelector(`#review-${trigger.dataset.reviewOpen.toLowerCase()}`);
        if (!dialog || typeof dialog.showModal !== 'function') return;
        const form = dialog.querySelector('[data-review-decision]');
        if (!form) return;
        trigger.disabled = trigger.dataset.eligible !== 'true';
        trigger.addEventListener('click', () => {
            if (!submitting && !dialog.open) dialog.showModal();
        });
        dialog.querySelector('[data-review-cancel]')?.addEventListener('click', () => dialog.close());
        dialog.addEventListener('close', () => trigger.focus());
        const note = form.elements.note;
        note.addEventListener('input', () => note.setCustomValidity(''));
        form.addEventListener('submit', event => {
            if (submitting) { event.preventDefault(); return; }
            if (form.elements.decision.value === 'REJECTED' && !note.value.trim()) {
                event.preventDefault();
                note.setCustomValidity('Enter a rejection reason.');
                note.reportValidity();
                return;
            }
            if (!form.reportValidity()) { event.preventDefault(); return; }
            submitting = true;
            form.setAttribute('aria-busy', 'true');
            document.querySelectorAll('[data-review-open], [data-review-decision] button').forEach(button => { button.disabled = true; });
            if (event.submitter) event.submitter.textContent = 'Saving…';
        });
    });
})();
