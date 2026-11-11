(() => {
    'use strict';
    const form = document.querySelector('[data-collection-review]');
    if (!form) return;
    form.addEventListener('submit', event => {
        const decision = event.submitter?.value;
        const note = form.elements.note;
        note.setCustomValidity('');
        if (decision === 'REJECTED' && !note.value.trim()) {
            event.preventDefault();
            note.setCustomValidity('Enter a rejection reason.');
            note.reportValidity();
            return;
        }
        if (!window.confirm(decision === 'REJECTED'
            ? 'Reject this submission? The saved reason will be visible to the Collector. This decision is final.'
            : 'Verify this submission after reviewing all requests and items? This decision is final.')) {
            event.preventDefault();
        }
    });
    form.elements.note.addEventListener('input', () => form.elements.note.setCustomValidity(''));
})();
