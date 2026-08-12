(() => {
    'use strict';

    const storageKey = 'ecolot_officer_feedback_v1';
    const tabsContainer = document.querySelector('.feedback-status-tabs');
    const tableBody = document.querySelector('[data-feedback-table-body]');
    const dialog = document.querySelector('[data-feedback-dialog]');
    const reviewForm = document.querySelector('[data-feedback-review-form]');

    if (!tabsContainer || !tableBody || !dialog || !reviewForm) {
        return;
    }

    const tabs = Array.from(tabsContainer.querySelectorAll('[data-feedback-status]'));
    const rows = Array.from(tableBody.querySelectorAll('[data-feedback-id]'));
    const tableWrapper = document.querySelector('.feedback-table-wrapper');
    const resultCount = document.querySelector('[data-feedback-result-count]');
    const emptyState = document.querySelector('[data-feedback-empty-state]');
    const title = dialog.querySelector('[data-feedback-dialog-title]');
    const description = dialog.querySelector('[data-feedback-dialog-description]');
    const eyebrow = dialog.querySelector('[data-feedback-dialog-eyebrow]');
    const statusInput = reviewForm.elements.status;
    const responseInput = reviewForm.elements.response;
    const submitButton = dialog.querySelector('[data-feedback-submit]');
    const cancelButton = dialog.querySelector('.feedback-dialog-actions [data-close-feedback-dialog]');
    const formError = dialog.querySelector('[data-feedback-form-error]');
    const requestPreview = dialog.querySelector('[data-related-request-preview]');
    const toast = document.querySelector('[data-feedback-toast]');
    const outputs = {
        user: dialog.querySelector('[data-dialog-feedback-user]'),
        request: dialog.querySelector('[data-dialog-feedback-request]'),
        date: dialog.querySelector('[data-dialog-feedback-date]'),
        subject: dialog.querySelector('[data-dialog-feedback-subject]'),
        message: dialog.querySelector('[data-dialog-feedback-message]'),
        previewId: dialog.querySelector('[data-preview-request-id]'),
        previewSchedule: dialog.querySelector('[data-preview-schedule]'),
        previewStatus: dialog.querySelector('[data-preview-request-status]'),
    };

    const requestDetails = {
        'REQ-1036': { schedule: 'SCH-0003 — Kollupitiya', status: 'MISSED COLLECTION' },
        'REQ-1040': { schedule: 'SCH-0004 — Rajagiriya', status: 'COLLECTED' },
        'REQ-1043': { schedule: 'SCH-0004 — Rajagiriya', status: 'COLLECTED' },
        'REQ-1044': { schedule: 'SCH-0004 — Rajagiriya', status: 'COLLECTED' },
    };

    const defaultResponses = {
        'FB-003': 'The assigned collection team was contacted and the pickup issue was resolved.',
        'FB-004': 'Feedback reviewed and recorded. No further action is required.',
    };

    let activeStatus = '';
    let activeRow = null;
    let lastFocusedElement = null;
    let toastTimer = null;

    const readState = () => {
        try {
            const value = JSON.parse(localStorage.getItem(storageKey) || '{}');
            return value && typeof value === 'object' && !Array.isArray(value) ? value : {};
        } catch (error) {
            return {};
        }
    };

    const state = readState();
    const persist = () => {
        try {
            localStorage.setItem(storageKey, JSON.stringify(state));
        } catch (error) {
            // Continue without persistence when storage is unavailable.
        }
    };

    const statusLabels = {
        OPEN: 'OPEN', IN_REVIEW: 'IN REVIEW', RESOLVED: 'RESOLVED', CLOSED: 'CLOSED',
    };

    const setRowStatus = (row, status) => {
        const nextStatus = statusLabels[status] ? status : 'OPEN';
        const badge = row.querySelector('.feedback-status');
        const action = row.querySelector('.feedback-action-btn');
        row.dataset.feedbackStatus = nextStatus;
        badge.className = `feedback-status ${nextStatus.toLowerCase().replace('_', '-')}`;
        badge.textContent = statusLabels[nextStatus];

        if (nextStatus === 'OPEN') {
            action.textContent = 'Review';
            action.className = 'feedback-action-btn primary-action';
        } else if (nextStatus === 'IN_REVIEW') {
            action.textContent = 'Continue';
            action.className = 'feedback-action-btn secondary-action';
        } else {
            action.textContent = 'View';
            action.className = 'feedback-action-btn secondary-action';
        }
    };

    rows.forEach((row) => {
        if (state[row.dataset.feedbackId]?.status) {
            setRowStatus(row, state[row.dataset.feedbackId].status);
        }
    });

    const updateCounts = () => {
        tabs.forEach((tab) => {
            const status = tab.dataset.feedbackStatus;
            const count = status ? rows.filter((row) => row.dataset.feedbackStatus === status).length : rows.length;
            tab.querySelector('[data-feedback-count]').textContent = count;
        });
    };

    const applyFilter = () => {
        let visible = 0;
        rows.forEach((row) => {
            const show = !activeStatus || row.dataset.feedbackStatus === activeStatus;
            row.hidden = !show;
            visible += show ? 1 : 0;
        });
        resultCount.textContent = `${visible} ${visible === 1 ? 'record' : 'records'} shown`;
        emptyState.hidden = visible !== 0;
        tableWrapper.hidden = visible === 0;
        updateCounts();
    };

    const setReadOnly = (readOnly) => {
        statusInput.disabled = readOnly;
        responseInput.readOnly = readOnly;
        submitButton.hidden = readOnly;
        cancelButton.textContent = readOnly ? 'Close' : 'Cancel';
    };

    const openDialog = (row, trigger, showRequestPreview = false) => {
        const status = row.dataset.feedbackStatus;
        const isView = status === 'RESOLVED' || status === 'CLOSED';
        const requestId = row.querySelector('.request-link').textContent.trim();
        const saved = state[row.dataset.feedbackId] || {};
        const request = requestDetails[requestId] || { schedule: 'Not available', status: 'Not available' };

        activeRow = row;
        lastFocusedElement = trigger;
        eyebrow.textContent = row.dataset.feedbackId;
        outputs.user.textContent = row.querySelector('.user-name').textContent.trim();
        outputs.request.textContent = requestId;
        outputs.date.textContent = row.cells[4].textContent.replace(/\s+/g, ' ').trim();
        outputs.subject.textContent = row.cells[3].textContent.trim();
        outputs.message.textContent = row.dataset.message;
        outputs.previewId.textContent = requestId;
        outputs.previewSchedule.textContent = request.schedule;
        outputs.previewStatus.textContent = request.status;
        requestPreview.hidden = !showRequestPreview;
        statusInput.value = status;
        responseInput.value = saved.response || defaultResponses[row.dataset.feedbackId] || '';
        formError.hidden = true;
        setReadOnly(isView);

        title.textContent = isView ? 'View Feedback' : status === 'OPEN' ? 'Review Feedback' : 'Continue Feedback Review';
        description.textContent = showRequestPreview
            ? 'Review the related pickup request together with this feedback record.'
            : isView ? 'Review the completed officer response and final status.' : 'Review the public submission and record an officer response.';
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
        window.clearTimeout(toastTimer);
        toast.hidden = false;
        toastTimer = window.setTimeout(() => { toast.hidden = true; }, 2800);
    };

    tabsContainer.addEventListener('click', (event) => {
        const tab = event.target.closest('[data-feedback-status]');
        if (!tab) return;
        activeStatus = tab.dataset.feedbackStatus;
        tabs.forEach((item) => {
            const isActive = item === tab;
            item.classList.toggle('active', isActive);
            item.setAttribute('aria-pressed', String(isActive));
        });
        applyFilter();
    });

    tableBody.addEventListener('click', (event) => {
        const requestLink = event.target.closest('[data-related-request]');
        const action = event.target.closest('.feedback-action-btn');
        if (!requestLink && !action) return;
        event.preventDefault();
        const trigger = requestLink || action;
        openDialog(trigger.closest('[data-feedback-id]'), trigger, Boolean(requestLink));
    });

    reviewForm.addEventListener('input', () => { formError.hidden = true; });
    reviewForm.addEventListener('change', () => { formError.hidden = true; });
    reviewForm.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!activeRow || statusInput.disabled) return;
        const status = statusInput.value;
        const response = responseInput.value.trim();
        if (['RESOLVED', 'CLOSED'].includes(status) && response.length < 5) {
            formError.textContent = 'Add an officer response before resolving or closing this feedback.';
            formError.hidden = false;
            responseInput.focus();
            return;
        }
        state[activeRow.dataset.feedbackId] = { status, response };
        setRowStatus(activeRow, status);
        persist();
        closeDialog();
        applyFilter();
        showToast();
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog || event.target.closest('[data-close-feedback-dialog]')) closeDialog();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !dialog.hidden) closeDialog();
    });

    applyFilter();
})();
