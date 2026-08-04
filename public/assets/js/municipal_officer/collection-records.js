(() => {
    'use strict';

    const storageKey = 'ecolot_officer_collection_records_v1';
    const filterForm = document.querySelector('[data-records-filter-form]');
    const tableBody = document.querySelector('[data-records-table-body]');
    const tableWrapper = document.querySelector('.records-table-wrapper');
    const resultCount = document.querySelector('[data-record-result-count]');
    const emptyState = document.querySelector('[data-records-empty-state]');
    const dialog = document.querySelector('[data-record-dialog]');
    const reviewForm = document.querySelector('[data-record-review-form]');

    if (!filterForm || !tableBody || !dialog || !reviewForm) {
        return;
    }

    const rows = Array.from(tableBody.querySelectorAll('[data-record-id]'));
    const title = dialog.querySelector('[data-record-dialog-title]');
    const description = dialog.querySelector('[data-record-dialog-description]');
    const eyebrow = dialog.querySelector('[data-record-dialog-eyebrow]');
    const statusInput = reviewForm.elements.status;
    const noteInput = reviewForm.elements.note;
    const submitButton = dialog.querySelector('[data-record-submit]');
    const cancelButton = dialog.querySelector('.record-dialog-actions [data-close-record-dialog]');
    const formError = dialog.querySelector('[data-record-form-error]');
    const submissionsBody = dialog.querySelector('[data-record-submissions]');
    const submissionCount = dialog.querySelector('[data-submission-count]');
    const toast = document.querySelector('[data-record-toast]');
    const summary = {
        schedule: dialog.querySelector('[data-record-schedule]'),
        area: dialog.querySelector('[data-record-area]'),
        date: dialog.querySelector('[data-record-date]'),
        weight: dialog.querySelector('[data-record-weight]'),
    };

    const submissions = {
        'SCH-0004': [
            { request: 'REQ-1048', resident: 'Kasun Perera', weight: '12.50 kg', result: 'COLLECTED' },
            { request: 'REQ-1051', resident: 'Tharushi Silva', weight: '10.00 kg', result: 'PARTIAL' },
            { request: 'REQ-1054', resident: 'Dilan Fernando', weight: '9.00 kg', result: 'COLLECTED' },
        ],
        'SCH-0003': [
            { request: 'REQ-1039', resident: 'Amaya Jayasinghe', weight: '11.00 kg', result: 'COLLECTED' },
            { request: 'REQ-1042', resident: 'Ravindu Gunasekara', weight: '7.00 kg', result: 'COLLECTED' },
        ],
    };

    const defaultNotes = {
        'SCH-0003': 'Weights and collector submissions checked and verified.',
    };

    let activeRow = null;
    let lastFocusedElement = null;
    let toastTimer = null;

    const readSavedRecords = () => {
        try {
            const saved = JSON.parse(localStorage.getItem(storageKey) || '{}');
            return saved && typeof saved === 'object' && !Array.isArray(saved) ? saved : {};
        } catch (error) {
            return {};
        }
    };

    const saveRecords = (records) => {
        try {
            localStorage.setItem(storageKey, JSON.stringify(records));
        } catch (error) {
            // Keep the workflow usable for the current visit if storage is unavailable.
        }
    };

    const cellText = (row, index) => row.cells[index].textContent.trim();

    const setRowStatus = (row, status) => {
        const nextStatus = status === 'VERIFIED' ? 'VERIFIED' : 'PENDING';
        const badge = row.cells[5].querySelector('.verification-status');
        const actionButton = row.querySelector('.record-action-btn');

        badge.className = `verification-status ${nextStatus.toLowerCase()}`;
        badge.textContent = nextStatus;
        row.dataset.verificationStatus = nextStatus;

        if (nextStatus === 'VERIFIED') {
            actionButton.textContent = 'View';
            actionButton.className = 'record-action-btn secondary-action';
        } else {
            actionButton.textContent = 'Review';
            actionButton.className = 'record-action-btn primary-action';
        }
    };

    const applyFilters = () => {
        const campaign = filterForm.elements.campaign.value;
        const schedule = filterForm.elements.schedule.value;
        const status = filterForm.elements.verification_status.value;
        let visibleCount = 0;

        rows.forEach((row) => {
            const matches = (!campaign || row.dataset.campaign === campaign)
                && (!schedule || row.dataset.schedule === schedule)
                && (!status || row.dataset.verificationStatus === status);

            row.hidden = !matches;
            if (matches) {
                visibleCount += 1;
            }
        });

        resultCount.textContent = `${visibleCount} ${visibleCount === 1 ? 'record' : 'records'} shown`;
        emptyState.hidden = visibleCount !== 0;
        tableWrapper.hidden = visibleCount === 0;
    };

    const clearError = () => {
        formError.textContent = '';
        formError.hidden = true;
    };

    const renderSubmissions = (schedule) => {
        const items = submissions[schedule] || [];
        submissionsBody.replaceChildren();

        items.forEach((item) => {
            const row = document.createElement('tr');
            const requestCell = document.createElement('td');
            const residentCell = document.createElement('td');
            const weightCell = document.createElement('td');
            const resultCell = document.createElement('td');
            const resultBadge = document.createElement('span');

            requestCell.textContent = item.request;
            residentCell.textContent = item.resident;
            weightCell.textContent = item.weight;
            resultBadge.className = `submission-result ${item.result.toLowerCase()}`;
            resultBadge.textContent = item.result;
            resultCell.appendChild(resultBadge);
            row.append(requestCell, residentCell, weightCell, resultCell);
            submissionsBody.appendChild(row);
        });

        submissionCount.textContent = `${items.length} ${items.length === 1 ? 'submission' : 'submissions'}`;
    };

    const setFormReadOnly = (readOnly) => {
        statusInput.disabled = readOnly;
        noteInput.readOnly = readOnly;
        submitButton.hidden = readOnly;
        cancelButton.textContent = readOnly ? 'Close' : 'Cancel';
    };

    const openDialog = (row, trigger) => {
        const schedule = row.dataset.schedule;
        const status = row.dataset.verificationStatus;
        const saved = savedRecords[row.dataset.recordId] || {};
        const isView = status === 'VERIFIED';

        activeRow = row;
        lastFocusedElement = trigger;
        eyebrow.textContent = row.dataset.recordId;
        summary.schedule.textContent = schedule;
        summary.area.textContent = row.cells[0].querySelector('.table-subtext').textContent.trim();
        summary.date.textContent = cellText(row, 1);
        summary.weight.textContent = cellText(row, 4);
        statusInput.value = status;
        noteInput.value = saved.note || defaultNotes[schedule] || '';
        renderSubmissions(schedule);
        clearError();
        setFormReadOnly(isView);

        if (isView) {
            title.textContent = 'View Collection Records';
            description.textContent = 'Review the verified collector submissions and verification note.';
        } else {
            title.textContent = 'Review Collection Records';
            description.textContent = 'Check the submitted collection details before verification.';
            submitButton.textContent = 'Save Verification';
        }

        dialog.hidden = false;
        document.body.style.overflow = 'hidden';
        requestAnimationFrame(() => (isView ? cancelButton : statusInput).focus());
    };

    const closeDialog = () => {
        dialog.hidden = true;
        document.body.style.overflow = '';
        activeRow = null;
        lastFocusedElement?.focus();
    };

    const showToast = () => {
        if (!toast) {
            return;
        }

        window.clearTimeout(toastTimer);
        toast.hidden = false;
        toastTimer = window.setTimeout(() => {
            toast.hidden = true;
        }, 2800);
    };

    const savedRecords = readSavedRecords();
    rows.forEach((row) => {
        const saved = savedRecords[row.dataset.recordId];
        if (saved?.status) {
            setRowStatus(row, saved.status);
        }
    });

    filterForm.addEventListener('submit', (event) => {
        event.preventDefault();
        applyFilters();
    });

    filterForm.addEventListener('reset', () => {
        requestAnimationFrame(applyFilters);
    });

    tableBody.addEventListener('click', (event) => {
        const trigger = event.target.closest('.record-action-btn');
        if (!trigger) {
            return;
        }

        openDialog(trigger.closest('[data-record-id]'), trigger);
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog || event.target.closest('[data-close-record-dialog]')) {
            closeDialog();
        }
    });

    reviewForm.addEventListener('input', clearError);
    reviewForm.addEventListener('change', clearError);

    reviewForm.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!activeRow || statusInput.disabled) {
            return;
        }

        const status = statusInput.value;
        const note = noteInput.value.trim();

        if (status === 'VERIFIED' && note.length < 5) {
            formError.textContent = 'Add a short verification note before marking these records as verified.';
            formError.hidden = false;
            noteInput.focus();
            return;
        }

        setRowStatus(activeRow, status);
        savedRecords[activeRow.dataset.recordId] = { status, note };
        saveRecords(savedRecords);
        closeDialog();
        applyFilters();
        showToast();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !dialog.hidden) {
            closeDialog();
        }
    });

    applyFilters();
})();
