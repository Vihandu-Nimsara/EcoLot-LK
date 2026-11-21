
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

    document.querySelectorAll('form[data-unassign-form]').forEach(form => {
        form.addEventListener('submit', event => {

            // Allow submission only after confirmation
            if (approvedForm === form) {
                approvedForm = null;
                return;
            }

            event.preventDefault();

            if (!dialog || typeof dialog.showModal !== 'function') {
                return;
            }

            pendingForm = form;

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
