(() => {
    'use strict';

    const storageKey = 'ecolot_officer_collection_assignments_v1';
    const filterForm = document.querySelector('[data-assignment-filter-form]');
    const tableBody = document.querySelector('[data-assignments-table-body]');
    const tableWrapper = document.querySelector('.routes-table-wrapper');
    const resultCount = document.querySelector('[data-assignment-result-count]');
    const emptyState = document.querySelector('[data-assignments-empty-state]');
    const dialog = document.querySelector('[data-assignment-dialog]');
    const manageForm = document.querySelector('[data-assignment-manage-form]');

    if (!filterForm || !tableBody || !dialog || !manageForm) return;

    const rows = Array.from(tableBody.querySelectorAll('[data-assignment-key]'));
    const title = dialog.querySelector('[data-assignment-dialog-title]');
    const description = dialog.querySelector('[data-assignment-dialog-description]');
    const eyebrow = dialog.querySelector('[data-assignment-dialog-eyebrow]');
    const collectorInput = manageForm.elements.collector;
    const vehicleInput = manageForm.elements.vehicle;
    const statusInput = manageForm.elements.status;
    const submitButton = dialog.querySelector('[data-assignment-submit]');
    const cancelButton = dialog.querySelector('.route-dialog-actions [data-close-assignment-dialog]');
    const formError = dialog.querySelector('[data-assignment-form-error]');
    const toast = document.querySelector('[data-assignment-toast]');
    const summary = {
        schedule: dialog.querySelector('[data-assignment-schedule]'),
        area: dialog.querySelector('[data-assignment-area]'),
        date: dialog.querySelector('[data-assignment-date]'),
        requests: dialog.querySelector('[data-assignment-requests]'),
    };

    let activeRow = null;
    let lastFocusedElement = null;
    let toastTimer = null;

    const statusLabels = {
        CLOSED: 'CLOSED',
        ASSIGNED: 'ASSIGNED',
        IN_PROGRESS: 'IN PROGRESS',
        COMPLETED: 'COMPLETED',
    };

    const readSavedAssignments = () => {
        try {
            const saved = JSON.parse(localStorage.getItem(storageKey) || '{}');
            return saved && typeof saved === 'object' && !Array.isArray(saved) ? saved : {};
        } catch (_) {
            return {};
        }
    };

    const saveAssignments = (assignments) => {
        try {
            localStorage.setItem(storageKey, JSON.stringify(assignments));
        } catch (_) {
            // Demo UI remains usable for the current visit if storage is unavailable.
        }
    };

    const cellText = (row, index) => row.cells[index].textContent.trim();

    const getRowState = (row) => ({
        collector: row.cells[4].querySelector('.not-assigned') ? '' : cellText(row, 4),
        vehicle: row.cells[5].querySelector('.not-assigned') ? '' : cellText(row, 5),
        status: row.dataset.scheduleStatus,
    });

    const setAssignmentCell = (cell, value) => {
        cell.replaceChildren();
        if (value) {
            cell.textContent = value;
            return;
        }
        const unassigned = document.createElement('span');
        unassigned.className = 'not-assigned';
        unassigned.textContent = 'Not Assigned';
        cell.appendChild(unassigned);
    };

    const setRowState = (row, state) => {
        const status = statusLabels[state.status] ? state.status : 'CLOSED';
        const collector = typeof state.collector === 'string' ? state.collector : '';
        const vehicle = typeof state.vehicle === 'string' ? state.vehicle : '';

        setAssignmentCell(row.cells[4], collector);
        setAssignmentCell(row.cells[5], vehicle);

        const badge = row.cells[6].querySelector('.route-status');
        const badgeClass = status === 'CLOSED' ? 'planned' : status.toLowerCase().replace('_', '-');
        badge.className = `route-status ${badgeClass}`;
        badge.textContent = statusLabels[status];
        row.dataset.scheduleStatus = status;

        const actionButton = row.querySelector('.route-action-btn');
        if (status === 'COMPLETED') {
            actionButton.textContent = 'View';
            actionButton.className = 'route-action-btn secondary-action';
        } else if (status === 'CLOSED' && (!collector || !vehicle)) {
            actionButton.textContent = 'Assign';
            actionButton.className = 'route-action-btn primary-action';
        } else {
            actionButton.textContent = 'Manage';
            actionButton.className = 'route-action-btn secondary-action';
        }
    };

    const applyFilters = () => {
        const campaign = filterForm.elements.campaign.value;
        const schedule = filterForm.elements.schedule.value;
        const status = filterForm.elements.schedule_status.value;
        let visibleCount = 0;

        rows.forEach((row) => {
            const matches = (!campaign || row.dataset.campaign === campaign)
                && (!schedule || row.dataset.schedule === schedule)
                && (!status || row.dataset.scheduleStatus === status);
            row.hidden = !matches;
            if (matches) visibleCount += 1;
        });

        resultCount.textContent = `${visibleCount} ${visibleCount === 1 ? 'schedule' : 'schedules'} shown`;
        emptyState.hidden = visibleCount !== 0;
        tableWrapper.hidden = visibleCount === 0;
    };

    const showError = (message, input) => {
        formError.textContent = message;
        formError.hidden = false;
        input?.focus();
    };
    const clearError = () => {
        formError.textContent = '';
        formError.hidden = true;
    };

    const setFormReadOnly = (readOnly) => {
        collectorInput.disabled = readOnly;
        vehicleInput.disabled = readOnly;
        statusInput.disabled = readOnly;
        submitButton.hidden = readOnly;
        cancelButton.textContent = readOnly ? 'Close' : 'Cancel';
    };

    const openDialog = (row, trigger) => {
        const state = getRowState(row);
        const isView = state.status === 'COMPLETED';
        const isAssign = !isView && trigger.textContent.trim() === 'Assign';

        activeRow = row;
        lastFocusedElement = trigger;
        eyebrow.textContent = row.dataset.schedule;
        summary.schedule.textContent = cellText(row, 0);
        summary.area.textContent = cellText(row, 1);
        summary.date.textContent = cellText(row, 2);
        summary.requests.textContent = cellText(row, 3);

        collectorInput.value = isAssign ? '' : state.collector;
        vehicleInput.value = isAssign ? '' : state.vehicle;
        statusInput.value = isAssign ? 'ASSIGNED' : state.status;
        clearError();
        setFormReadOnly(isView);

        if (isView) {
            title.textContent = 'View Assignment';
            description.textContent = 'Review the completed schedule assignment.';
        } else if (isAssign) {
            title.textContent = 'Assign Collector & Vehicle';
            description.textContent = 'Select one Collector and one Vehicle for this Area Collection Schedule.';
            submitButton.textContent = 'Create Assignment';
        } else {
            title.textContent = 'Manage Assignment';
            description.textContent = 'Review or update the Collector and Vehicle assigned to this schedule.';
            submitButton.textContent = 'Save Changes';
        }

        dialog.hidden = false;
        document.body.style.overflow = 'hidden';
        requestAnimationFrame(() => (isView ? cancelButton : collectorInput).focus());
    };

    const closeDialog = () => {
        dialog.hidden = true;
        document.body.style.overflow = '';
        activeRow = null;
        lastFocusedElement?.focus();
    };

    const showToast = () => {
        if (!toast) return;
        window.clearTimeout(toastTimer);
        toast.hidden = false;
        toastTimer = window.setTimeout(() => { toast.hidden = true; }, 2800);
    };

    const savedAssignments = readSavedAssignments();
    rows.forEach((row) => {
        const savedState = savedAssignments[row.dataset.assignmentKey];
        if (savedState) setRowState(row, savedState);
    });

    filterForm.addEventListener('submit', (event) => {
        event.preventDefault();
        applyFilters();
    });
    filterForm.addEventListener('reset', () => requestAnimationFrame(applyFilters));

    tableBody.addEventListener('click', (event) => {
        const trigger = event.target.closest('.route-action-btn');
        if (trigger) openDialog(trigger.closest('[data-assignment-key]'), trigger);
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog || event.target.closest('[data-close-assignment-dialog]')) closeDialog();
    });

    manageForm.addEventListener('input', clearError);
    manageForm.addEventListener('change', clearError);
    manageForm.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!activeRow || statusInput.disabled) return;

        const collector = collectorInput.value;
        const vehicle = vehicleInput.value;
        const status = statusInput.value;

        if ((collector && !vehicle) || (!collector && vehicle)) {
            showError('Select both a Collector and Vehicle, or leave both unassigned.', collector ? vehicleInput : collectorInput);
            return;
        }
        if (status !== 'CLOSED' && (!collector || !vehicle)) {
            showError('A Collector and Vehicle are required once the schedule is assigned.', !collector ? collectorInput : vehicleInput);
            return;
        }

        const state = { collector, vehicle, status };
        setRowState(activeRow, state);
        savedAssignments[activeRow.dataset.assignmentKey] = state;
        saveAssignments(savedAssignments);
        closeDialog();
        applyFilters();
        showToast();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !dialog.hidden) closeDialog();
    });

    applyFilters();
})();
