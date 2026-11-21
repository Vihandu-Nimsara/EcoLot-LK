
(() => {
    // Existing search functionality
    const search = document.querySelector('#assignment-search');
    const rows = [...document.querySelectorAll('[data-assignment-row]')];

    search?.addEventListener('input', () => {
        const keyword = search.value.trim().toLowerCase();

        rows.forEach(row => {
            row.hidden = !row.textContent.toLowerCase().includes(keyword);
        });

        const empty = document.querySelector('[data-assignment-empty]');

        if (empty) {
            empty.hidden = rows.some(row => !row.hidden);
        }
    });

    // Unassign confirmation modal
    const dialog = document.querySelector('#unassign-confirm-dialog');
    const scheduleName = document.querySelector('#unassign-schedule-name');
    const confirmButton = document.querySelector('#confirm-unassign');

    let pendingForm = null;
    let approvedForm = null;
    let submitting = false;
    let opener = null;

    document.querySelectorAll('form[data-unassign-form]').forEach(form => {
        const trigger = form.querySelector('[data-unassign-trigger]');
        if (trigger && dialog && scheduleName && confirmButton && typeof dialog.showModal === 'function'
            && typeof form.requestSubmit === 'function') trigger.disabled = false;
        form.addEventListener('submit', event => {
            if (submitting) { event.preventDefault(); return; }

            // Allow submission only after confirmation
            if (approvedForm === form) {
                approvedForm = null;
                submitting = true;
                if (trigger) trigger.disabled = true;
                return;
            }

            event.preventDefault();

            if (!dialog || typeof dialog.showModal !== 'function') {
                return;
            }

            if (dialog.open) return;
            pendingForm = form;
            opener = trigger;
            confirmButton.disabled = false;

            scheduleName.textContent =
                form.dataset.scheduleLabel || 'Selected schedule';

            dialog.showModal();
            document.body.classList.add('unassign-modal-open');
        });
    });

    // Cancel / close buttons
    dialog?.querySelectorAll('[data-unassign-close]').forEach(button => {
        button.addEventListener('click', () => {
            dialog.close();
        });
    });

    // Escape and other dialog close events
    dialog?.addEventListener('close', () => {
        document.body.classList.remove('unassign-modal-open');
        pendingForm = null;
        opener?.focus();
    });

    // Confirm unassignment
    confirmButton?.addEventListener('click', () => {
        if (!pendingForm) return;

        const form = pendingForm;

        confirmButton.disabled = true;

        approvedForm = form;

        dialog.close();

        // Submit the original POST form with CSRF token
        form.requestSubmit();
    });

})();
