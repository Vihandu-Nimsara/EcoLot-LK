(() => {
    'use strict';

    const storageKey = 'ecolot_officer_routes_v3';
    const filterForm = document.querySelector('[data-route-filter-form]');
    const tableBody = document.querySelector('[data-routes-table-body]');
    const tableWrapper = document.querySelector('.routes-table-wrapper');
    const resultCount = document.querySelector('[data-route-result-count]');
    const emptyState = document.querySelector('[data-routes-empty-state]');
    const dialog = document.querySelector('[data-route-dialog]');
    const manageForm = document.querySelector('[data-route-manage-form]');

    if (!filterForm || !tableBody || !dialog || !manageForm) {
        return;
    }

    const rows = Array.from(tableBody.querySelectorAll('[data-route-id]'));
    const title = dialog.querySelector('[data-route-dialog-title]');
    const description = dialog.querySelector('[data-route-dialog-description]');
    const eyebrow = dialog.querySelector('[data-route-dialog-eyebrow]');
    const collectorInput = manageForm.elements.collector;
    const vehicleInput = manageForm.elements.vehicle;
    const statusInput = manageForm.elements.status;
    const submitButton = dialog.querySelector('[data-route-submit]');
    const cancelButton = dialog.querySelector('.route-dialog-actions [data-close-route-dialog]');
    const formError = dialog.querySelector('[data-route-form-error]');
    const toast = document.querySelector('[data-route-toast]');
    const summary = {
        schedule: dialog.querySelector('[data-route-schedule]'),
        area: dialog.querySelector('[data-route-area]'),
        date: dialog.querySelector('[data-route-date]'),
        stops: dialog.querySelector('[data-route-stops]'),
    };

    let activeRow = null;
    let lastFocusedElement = null;
    let toastTimer = null;

    const statusLabels = {
        PLANNED: 'PLANNED',
        ASSIGNED: 'ASSIGNED',
        IN_PROGRESS: 'IN PROGRESS',
        COMPLETED: 'COMPLETED',
    };

    const readSavedRoutes = () => {
        try {
            const saved = JSON.parse(localStorage.getItem(storageKey) || '{}');
            return saved && typeof saved === 'object' && !Array.isArray(saved) ? saved : {};
        } catch (error) {
            return {};
        }
    };

    const saveRoutes = (routes) => {
        try {
            localStorage.setItem(storageKey, JSON.stringify(routes));
        } catch (error) {
            // The page still works for the current visit when storage is unavailable.
        }
    };

    const cellText = (row, index) => row.cells[index].textContent.trim();

    const getRowState = (row) => {
        const collector = row.cells[5].querySelector('.not-assigned') ? '' : cellText(row, 5);
        const vehicle = row.cells[6].querySelector('.not-assigned') ? '' : cellText(row, 6);

        return {
            collector,
            vehicle,
            status: row.dataset.routeStatus,
        };
    };

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
        const status = statusLabels[state.status] ? state.status : 'PLANNED';
        const collector = typeof state.collector === 'string' ? state.collector : '';
        const vehicle = typeof state.vehicle === 'string' ? state.vehicle : '';

        setAssignmentCell(row.cells[5], collector);
        setAssignmentCell(row.cells[6], vehicle);

        const badge = row.cells[7].querySelector('.route-status');
        badge.className = `route-status ${status.toLowerCase().replace('_', '-')}`;
        badge.textContent = statusLabels[status];
        row.dataset.routeStatus = status;

        const actionButton = row.querySelector('.route-action-btn');
        if (status === 'COMPLETED') {
            actionButton.textContent = 'View';
            actionButton.className = 'route-action-btn secondary-action';
        } else if (status === 'PLANNED' && (!collector || !vehicle)) {
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
        const status = filterForm.elements.route_status.value;
        let visibleCount = 0;

        rows.forEach((row) => {
            const matches = (!campaign || row.dataset.campaign === campaign)
                && (!schedule || row.dataset.schedule === schedule)
                && (!status || row.dataset.routeStatus === status);

            row.hidden = !matches;
            if (matches) {
                visibleCount += 1;
            }
        });

        resultCount.textContent = `${visibleCount} ${visibleCount === 1 ? 'route' : 'routes'} shown`;
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
        eyebrow.textContent = row.dataset.routeId;
        summary.schedule.textContent = cellText(row, 1);
        summary.area.textContent = cellText(row, 2);
        summary.date.textContent = cellText(row, 3);
        summary.stops.textContent = cellText(row, 4);

        collectorInput.value = isAssign ? '' : state.collector;
        vehicleInput.value = isAssign ? '' : state.vehicle;
        statusInput.value = isAssign ? 'ASSIGNED' : state.status;
        clearError();
        setFormReadOnly(isView);

        if (isView) {
            title.textContent = 'View Route';
            description.textContent = 'Review the completed route assignment and collection details.';
        } else if (isAssign) {
            title.textContent = 'Assign Route';
            description.textContent = 'Select a collector and vehicle to prepare this route for collection.';
            submitButton.textContent = 'Assign Route';
        } else {
            title.textContent = 'Manage Route';
            description.textContent = 'Update the assigned resources or change the route progress.';
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
        if (!toast) {
            return;
        }

        window.clearTimeout(toastTimer);
        toast.hidden = false;
        toastTimer = window.setTimeout(() => {
            toast.hidden = true;
        }, 2800);
    };

    const savedRoutes = readSavedRoutes();
    rows.forEach((row) => {
        const savedState = savedRoutes[row.dataset.routeId];
        if (savedState) {
            setRowState(row, savedState);
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
        const trigger = event.target.closest('.route-action-btn');
        if (!trigger) {
            return;
        }

        openDialog(trigger.closest('[data-route-id]'), trigger);
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog || event.target.closest('[data-close-route-dialog]')) {
            closeDialog();
        }
    });

    manageForm.addEventListener('input', clearError);
    manageForm.addEventListener('change', clearError);

    manageForm.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!activeRow || statusInput.disabled) {
            return;
        }

        const collector = collectorInput.value;
        const vehicle = vehicleInput.value;
        const status = statusInput.value;

        if ((collector && !vehicle) || (!collector && vehicle)) {
            showError('Select both a collector and vehicle, or leave both unassigned.', collector ? vehicleInput : collectorInput);
            return;
        }

        if (status !== 'PLANNED' && (!collector || !vehicle)) {
            showError('A collector and vehicle are required for this route status.', !collector ? collectorInput : vehicleInput);
            return;
        }

        const state = { collector, vehicle, status };
        setRowState(activeRow, state);
        savedRoutes[activeRow.dataset.routeId] = state;
        saveRoutes(savedRoutes);
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
