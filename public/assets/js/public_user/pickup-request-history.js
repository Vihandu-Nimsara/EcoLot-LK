document.addEventListener('DOMContentLoaded', () => {
    const detailsModal = document.querySelector('[data-details-modal]');
    const deleteModal = document.querySelector('[data-delete-modal]');
    const requestCode = document.querySelector('[data-request-code]');
    let pendingDeleteId = null;

    const setModalOpen = (modal, isOpen) => {
        if (!modal) return;
        modal.hidden = !isOpen;
    };

    document.querySelectorAll('[data-view-request]').forEach((button) => {
        button.addEventListener('click', () => {
            if (requestCode) requestCode.textContent = button.dataset.viewRequest;
            setModalOpen(detailsModal, true);
        });
    });

    document.querySelectorAll('[data-delete-request]').forEach((button) => {
        button.addEventListener('click', () => {
            pendingDeleteId = button.dataset.deleteRequest;
            setModalOpen(deleteModal, true);
        });
    });

    document.querySelectorAll('[data-close-details-modal]').forEach((button) => {
        button.addEventListener('click', () => setModalOpen(detailsModal, false));
    });

    document.querySelector('[data-close-delete-modal]')?.addEventListener('click', () => {
        pendingDeleteId = null;
        setModalOpen(deleteModal, false);
    });

    document.querySelector('[data-confirm-delete]')?.addEventListener('click', () => {
        pendingDeleteId = null;
        setModalOpen(deleteModal, false);
    });

    [detailsModal, deleteModal].forEach((modal) => {
        modal?.addEventListener('click', (event) => {
            if (event.target === modal) setModalOpen(modal, false);
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        setModalOpen(detailsModal, false);
        setModalOpen(deleteModal, false);
        pendingDeleteId = null;
    });
});
