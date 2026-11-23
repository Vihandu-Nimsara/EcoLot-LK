(() => {
    'use strict';
    const feedbackDialog = document.querySelector('[data-feedback-dialog]');
    if (feedbackDialog && typeof feedbackDialog.showModal === 'function') {
        const dismiss = feedbackDialog.querySelector('[data-feedback-dismiss]');
        feedbackDialog.removeAttribute('open');
        feedbackDialog.showModal();
        dismiss.focus({preventScroll: true});
        const returnToList = event => {
            event.preventDefault();
            window.location.replace(dismiss.href);
        };
        dismiss.addEventListener('click', returnToList);
        feedbackDialog.addEventListener('cancel', returnToList);
    }
    const dialog = document.querySelector('#officer-management-confirm');
    const confirm = dialog?.querySelector('[data-management-confirm]');
    let pending = null;
    let approved = null;
    let submitting = false;
    let opener = null;
    const titles = {approve:'Verify E-Lot and Schedule Bidding', reject:'Reject E-Lot', reopen:'Reopen Bidding', award:'Select Winning Recycler', handover:'Save Handover'};
    document.querySelectorAll('form[data-officer-confirm]').forEach(form => {
        if (!dialog || !confirm || typeof dialog.showModal !== 'function' || typeof form.requestSubmit !== 'function') return;
        form.querySelectorAll('[data-confirm-trigger]').forEach(button => {
            button.disabled = button.dataset.eligible === 'false';
        });
        form.addEventListener('submit', event => {
            if (submitting) { event.preventDefault(); return; }
            if (approved === form) {
                approved = null;
                submitting = true;
                form.setAttribute('aria-busy', 'true');
                if (event.submitter) event.submitter.textContent = 'Saving…';
                // Submitter stays enabled until the browser constructs the POST body.
                setTimeout(() => form.querySelectorAll('button').forEach(button => { button.disabled = true; }), 0);
                return;
            }
            event.preventDefault();
            const trigger = event.submitter;
            const action = trigger?.name === 'action' ? trigger.value : form.elements.action?.value;
            const note = form.elements.note;
            if (note) {
                note.setCustomValidity(action === 'reject' && !note.value.trim() ? 'Enter a rejection reason.' : '');
                if (action === 'approve') {
                    for (const key of ['open','close']) {
                        form.elements[key].required = true;
                    }
                } else if (action === 'reject') {
                    for (const key of ['open','close']) form.elements[key].required = false;
                }
            }
            const response = form.elements.response;
            if (response) response.setCustomValidity(['RESOLVED','CLOSED'].includes(form.elements.status.value) && !response.value.trim() ? 'Enter an Officer response.' : '');
            if (!form.reportValidity() || dialog.open) return;
            pending = {form, trigger};
            opener = trigger;
            confirm.disabled = false;
            confirm.textContent = titles[action] || 'Save Feedback Update';
            dialog.querySelector('#management-confirm-title').textContent = `${confirm.textContent}?`;
            dialog.querySelector('#management-confirm-description').textContent = form.dataset.officerConfirm;
            dialog.showModal();
        });
        form.addEventListener('input', () => {
            form.elements.note?.setCustomValidity('');
            form.elements.response?.setCustomValidity('');
        });
        // Rejection must not inherit required bidding fields after a cancelled approval.
        form.querySelectorAll('button[name="action"]').forEach(button => button.addEventListener('click', () => {
            if (button.value === 'reject') {
                form.elements.open.required = false;
                form.elements.close.required = false;
            }
        }));
    });
    dialog?.querySelector('[data-management-cancel]')?.addEventListener('click', () => dialog.close());
    dialog?.addEventListener('close', () => { pending = null; opener?.focus(); });
    confirm?.addEventListener('click', () => {
        if (!pending || submitting) return;
        const {form, trigger} = pending;
        confirm.disabled = true;
        approved = form;
        dialog.close();
        form.requestSubmit(trigger || undefined);
    });
})();
