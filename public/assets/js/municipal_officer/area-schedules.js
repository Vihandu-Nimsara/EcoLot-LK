(() => {
    // Progressive enhancement only. PHP renders and persists all schedules.
    const dialog = document.querySelector('#create-schedule');
    const trigger = document.querySelector('.create-schedule-trigger');
    if (dialog && typeof dialog.showModal === 'function') {
        const openDialog = () => {
            dialog.showModal();
            document.body.classList.add('schedule-modal-open');
        };
        trigger?.addEventListener('click', (event) => {
            event.preventDefault();
            openDialog();
        });
        dialog.querySelectorAll('[data-close-schedule]').forEach((close) => {
            close.addEventListener('click', (event) => {
                event.preventDefault();
                dialog.close();
            });
        });
        dialog.addEventListener('close', () => {
            document.body.classList.remove('schedule-modal-open');
            trigger?.focus();
        });
        dialog.addEventListener('click', (event) => {
            const bounds = dialog.getBoundingClientRect();
            if (event.target === dialog && (event.clientX < bounds.left || event.clientX > bounds.right
                || event.clientY < bounds.top || event.clientY > bounds.bottom)) dialog.close();
        });
        if (dialog.open) {
            dialog.removeAttribute('open');
            openDialog();
        }
    }

    const filter = document.querySelector('#campaign-filter');
    filter?.addEventListener('change', () => {
        document.querySelectorAll('[data-campaign-id]').forEach((row) => {
            row.hidden = filter.value !== '' && row.dataset.campaignId !== filter.value;
        });
    });
})();
