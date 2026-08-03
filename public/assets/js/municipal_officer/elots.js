(() => {
    'use strict';

    const storageKey = 'ecolot_officer_elots_v1';
    const filterForm = document.querySelector('[data-elot-filter-form]');
    const tableBody = document.querySelector('[data-elots-table-body]');
    const dialog = document.querySelector('[data-elot-dialog]');

    if (!filterForm || !tableBody || !dialog) {
        return;
    }

    const tableWrapper = document.querySelector('.elots-table-wrapper');
    const resultCount = document.querySelector('[data-elot-result-count]');
    const emptyState = document.querySelector('[data-elots-empty-state]');
    const createTrigger = document.querySelector('[data-open-create-elot]');
    const poolTrigger = document.querySelector('[data-open-item-pool]');
    const createForm = dialog.querySelector('[data-elot-create-form]');
    const poolSection = dialog.querySelector('[data-verified-pool-section]');
    const manageForm = dialog.querySelector('[data-elot-manage-form]');
    const dialogTitle = dialog.querySelector('[data-elot-dialog-title]');
    const dialogDescription = dialog.querySelector('[data-elot-dialog-description]');
    const dialogEyebrow = dialog.querySelector('[data-elot-dialog-eyebrow]');
    const createError = dialog.querySelector('[data-create-elot-error]');
    const manageError = dialog.querySelector('[data-manage-elot-error]');
    const itemOptions = dialog.querySelector('[data-create-item-options]');
    const poolBody = dialog.querySelector('[data-verified-pool-body]');
    const bidSelector = dialog.querySelector('[data-bid-selector]');
    const bidOptions = dialog.querySelector('[data-bid-options]');
    const manageStatusField = dialog.querySelector('[data-manage-status-field]');
    const manageStatus = manageForm.elements.status;
    const manageSubmit = dialog.querySelector('[data-manage-elot-submit]');
    const winnerSummary = dialog.querySelector('[data-elot-winner-summary]');
    const winnerOutput = dialog.querySelector('[data-dialog-elot-winner]');
    const toast = document.querySelector('[data-elot-toast]');
    const summary = {
        category: dialog.querySelector('[data-dialog-elot-category]'),
        items: dialog.querySelector('[data-dialog-elot-items]'),
        weight: dialog.querySelector('[data-dialog-elot-weight]'),
        period: dialog.querySelector('[data-dialog-elot-period]'),
    };

    const categoryLabels = {
        DOMESTIC: 'Domestic E-Waste',
        OFFICE: 'Office E-Waste',
        INDUSTRIAL: 'Industrial E-Waste',
    };

    const verifiedItems = [
        { id: 'ITM-201', name: 'LED Monitor', category: 'DOMESTIC', weight: 4.5 },
        { id: 'ITM-202', name: 'Rice Cooker', category: 'DOMESTIC', weight: 1 },
        { id: 'ITM-203', name: 'Laser Printer', category: 'OFFICE', weight: 5 },
        { id: 'ITM-204', name: 'UPS Unit', category: 'OFFICE', weight: 4 },
        { id: 'ITM-205', name: 'Control Unit', category: 'INDUSTRIAL', weight: 10 },
    ];

    const bids = {
        'EL-001': [
            { recycler: 'GreenCycle Lanka (Pvt) Ltd', amount: 'LKR 42,500' },
            { recycler: 'Eco Renew Solutions (Pvt) Ltd', amount: 'LKR 39,750' },
        ],
    };

    let activeRow = null;
    let lastFocusedElement = null;
    let toastTimer = null;

    const readState = () => {
        try {
            const value = JSON.parse(localStorage.getItem(storageKey) || '{}');
            return {
                created: Array.isArray(value.created) ? value.created : [],
                updates: value.updates && typeof value.updates === 'object' ? value.updates : {},
                usedItems: Array.isArray(value.usedItems) ? value.usedItems : [],
            };
        } catch (error) {
            return { created: [], updates: {}, usedItems: [] };
        }
    };

    const state = readState();

    const persist = () => {
        try {
            localStorage.setItem(storageKey, JSON.stringify(state));
        } catch (error) {
            // Continue without persistence when browser storage is unavailable.
        }
    };

    const formatDate = (value) => new Intl.DateTimeFormat('en-GB', {
        day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC',
    }).format(new Date(`${value}T00:00:00Z`));

    const createCell = (text) => {
        const cell = document.createElement('td');
        cell.textContent = text;
        return cell;
    };

    const buildRow = (lot) => {
        const row = document.createElement('tr');
        row.dataset.elotCode = lot.code;
        row.dataset.elotTitle = lot.title;
        row.dataset.category = lot.category;
        row.dataset.elotStatus = lot.status;
        row.append(
            createCell(lot.code),
            createCell(lot.title),
            createCell(categoryLabels[lot.category]),
            createCell(String(lot.items)),
            createCell(`${Number(lot.weight).toFixed(2)} kg`),
            createCell(lot.period),
            createCell(String(lot.bids || 0)),
        );

        const statusCell = document.createElement('td');
        const statusBadge = document.createElement('span');
        statusBadge.className = 'elot-status open';
        statusCell.appendChild(statusBadge);
        row.appendChild(statusCell);

        const winnerCell = document.createElement('td');
        winnerCell.innerHTML = '<span class="not-assigned">Not Selected</span>';
        row.appendChild(winnerCell);

        const actionCell = document.createElement('td');
        const action = document.createElement('button');
        action.type = 'button';
        action.className = 'elot-action-btn primary-action';
        actionCell.appendChild(action);
        row.appendChild(actionCell);
        setRowState(row, lot.status, lot.winner || '');
        return row;
    };

    const setRowState = (row, status, winner = '') => {
        const normalizedStatus = ['OPEN_FOR_BIDDING', 'AWARDED', 'COMPLETED'].includes(status)
            ? status : 'OPEN_FOR_BIDDING';
        const badge = row.cells[7].querySelector('.elot-status');
        const winnerCell = row.cells[8];
        const action = row.querySelector('.elot-action-btn');

        row.dataset.elotStatus = normalizedStatus;
        badge.textContent = normalizedStatus.replaceAll('_', ' ');
        badge.className = `elot-status ${normalizedStatus === 'OPEN_FOR_BIDDING' ? 'open' : normalizedStatus.toLowerCase()}`;
        winnerCell.replaceChildren();
        if (winner) {
            winnerCell.textContent = winner;
        } else {
            const missing = document.createElement('span');
            missing.className = 'not-assigned';
            missing.textContent = 'Not Selected';
            winnerCell.appendChild(missing);
        }

        if (normalizedStatus === 'OPEN_FOR_BIDDING') {
            action.textContent = 'Review Bids';
            action.className = 'elot-action-btn primary-action';
        } else if (normalizedStatus === 'AWARDED') {
            action.textContent = 'Manage';
            action.className = 'elot-action-btn secondary-action';
        } else {
            action.textContent = 'View';
            action.className = 'elot-action-btn secondary-action';
        }
    };

    state.created.forEach((lot) => tableBody.appendChild(buildRow(lot)));
    Object.entries(state.updates).forEach(([code, update]) => {
        const row = Array.from(tableBody.rows).find((item) => item.dataset.elotCode === code);
        if (row) {
            setRowState(row, update.status, update.winner);
        }
    });

    const getRows = () => Array.from(tableBody.querySelectorAll('[data-elot-code]'));

    const applyFilters = () => {
        const status = filterForm.elements.elot_status.value;
        const category = filterForm.elements.category.value;
        let count = 0;

        getRows().forEach((row) => {
            const matches = (!status || row.dataset.elotStatus === status)
                && (!category || row.dataset.category === category);
            row.hidden = !matches;
            count += matches ? 1 : 0;
        });

        resultCount.textContent = `${count} ${count === 1 ? 'E-Lot' : 'E-Lots'} shown`;
        emptyState.hidden = count !== 0;
        tableWrapper.hidden = count === 0;
    };

    const closeDialog = () => {
        dialog.hidden = true;
        document.body.style.overflow = '';
        activeRow = null;
        lastFocusedElement?.focus();
    };

    const showSection = (section) => {
        createForm.hidden = section !== createForm;
        poolSection.hidden = section !== poolSection;
        manageForm.hidden = section !== manageForm;
        dialog.hidden = false;
        document.body.style.overflow = 'hidden';
    };

    const showToast = () => {
        window.clearTimeout(toastTimer);
        toast.hidden = false;
        toastTimer = window.setTimeout(() => { toast.hidden = true; }, 2800);
    };

    const renderItems = () => {
        itemOptions.replaceChildren();
        poolBody.replaceChildren();

        verifiedItems.forEach((item) => {
            const used = state.usedItems.includes(item.id);
            const label = document.createElement('label');
            label.className = 'verified-item-option';
            label.dataset.category = item.category;
            label.innerHTML = `<input type="checkbox" name="items" value="${item.id}" ${used ? 'disabled' : ''}><strong>${item.id} — ${item.name}</strong><span>${item.weight.toFixed(2)} kg</span>`;
            itemOptions.appendChild(label);

            const row = document.createElement('tr');
            [item.id, item.name, categoryLabels[item.category], `${item.weight.toFixed(2)} kg`].forEach((text) => row.appendChild(createCell(text)));
            const availability = createCell(used ? 'Added to E-Lot' : 'Available');
            availability.className = used ? '' : 'pool-availability';
            row.appendChild(availability);
            poolBody.appendChild(row);
        });
    };

    const filterCreateItems = () => {
        const category = createForm.elements.category.value;
        itemOptions.querySelectorAll('.verified-item-option').forEach((option) => {
            option.hidden = Boolean(category) && option.dataset.category !== category;
            if (option.hidden) {
                option.querySelector('input').checked = false;
            }
        });
    };

    createTrigger.addEventListener('click', () => {
        lastFocusedElement = createTrigger;
        dialogEyebrow.textContent = 'New E-Lot';
        dialogTitle.textContent = 'Create E-Lot';
        dialogDescription.textContent = 'Select verified items and set the recycler bidding period.';
        createForm.reset();
        createForm.elements.start.value = '2026-08-04';
        createForm.elements.end.value = '2026-08-09';
        createError.hidden = true;
        renderItems();
        filterCreateItems();
        showSection(createForm);
        createForm.elements.title.focus();
    });

    poolTrigger.addEventListener('click', () => {
        lastFocusedElement = poolTrigger;
        dialogEyebrow.textContent = 'Verified inventory';
        dialogTitle.textContent = 'Verified Item Pool';
        dialogDescription.textContent = 'Items can be included in one E-Lot while they are available.';
        renderItems();
        showSection(poolSection);
    });

    createForm.elements.category.addEventListener('change', filterCreateItems);
    createForm.addEventListener('input', () => { createError.hidden = true; });
    createForm.addEventListener('submit', (event) => {
        event.preventDefault();
        const selectedIds = Array.from(createForm.querySelectorAll('input[name="items"]:checked')).map((input) => input.value);
        const selectedItems = verifiedItems.filter((item) => selectedIds.includes(item.id));
        const category = createForm.elements.category.value;

        if (createForm.elements.title.value.trim().length < 3) {
            createError.textContent = 'Enter a clear E-Lot title.';
            createError.hidden = false;
            createForm.elements.title.focus();
            return;
        }

        if (!selectedItems.length) {
            createError.textContent = 'Select at least one available verified item.';
            createError.hidden = false;
            return;
        }
        if (selectedItems.some((item) => item.category !== category)) {
            createError.textContent = 'All selected items must match the E-Lot category.';
            createError.hidden = false;
            return;
        }
        if (createForm.elements.end.value <= createForm.elements.start.value) {
            createError.textContent = 'The bidding closing date must be after the opening date.';
            createError.hidden = false;
            createForm.elements.end.focus();
            return;
        }

        const nextNumber = Math.max(3, ...getRows().map((row) => Number(row.dataset.elotCode.replace('EL-', '')) || 0)) + 1;
        const lot = {
            code: `EL-${String(nextNumber).padStart(3, '0')}`,
            title: createForm.elements.title.value.trim(),
            category,
            items: selectedItems.length,
            weight: selectedItems.reduce((total, item) => total + item.weight, 0),
            period: `${formatDate(createForm.elements.start.value)} – ${formatDate(createForm.elements.end.value)}`,
            bids: 0,
            status: 'OPEN_FOR_BIDDING',
            winner: '',
        };
        state.created.push(lot);
        state.usedItems.push(...selectedIds);
        tableBody.appendChild(buildRow(lot));
        persist();
        closeDialog();
        applyFilters();
        showToast();
    });

    const populateManageSummary = (row) => {
        summary.category.textContent = row.cells[2].textContent.trim();
        summary.items.textContent = row.cells[3].textContent.trim();
        summary.weight.textContent = row.cells[4].textContent.trim();
        summary.period.textContent = row.cells[5].textContent.trim();
    };

    tableBody.addEventListener('click', (event) => {
        const trigger = event.target.closest('.elot-action-btn');
        if (!trigger) return;
        const row = trigger.closest('[data-elot-code]');
        const status = row.dataset.elotStatus;
        const lotBids = bids[row.dataset.elotCode] || [];
        activeRow = row;
        lastFocusedElement = trigger;
        dialogEyebrow.textContent = row.dataset.elotCode;
        populateManageSummary(row);
        bidOptions.replaceChildren();
        manageError.hidden = true;
        winnerSummary.hidden = true;
        manageStatusField.hidden = true;
        bidSelector.hidden = true;
        manageSubmit.hidden = false;
        manageSubmit.disabled = false;

        if (status === 'OPEN_FOR_BIDDING') {
            dialogTitle.textContent = 'Review Recycler Bids';
            dialogDescription.textContent = lotBids.length ? 'Select the winning recycler bid for this E-Lot.' : 'No recycler bids have been submitted for this E-Lot yet.';
            bidSelector.hidden = false;
            lotBids.forEach((bid, index) => {
                const label = document.createElement('label');
                label.className = 'bid-option';
                label.innerHTML = `<input type="radio" name="winner" value="${bid.recycler}"><strong>${bid.recycler}</strong><span>${bid.amount}</span>`;
                bidOptions.appendChild(label);
                if (index === 0) label.querySelector('input').checked = true;
            });
            if (!lotBids.length) {
                bidOptions.textContent = 'Waiting for recycler bids.';
                manageSubmit.hidden = true;
            } else {
                manageSubmit.textContent = 'Award E-Lot';
            }
        } else {
            const winner = row.cells[8].textContent.trim();
            winnerOutput.textContent = winner;
            winnerSummary.hidden = false;
            manageStatusField.hidden = status === 'COMPLETED';
            manageStatus.value = status === 'COMPLETED' ? 'COMPLETED' : 'AWARDED';
            dialogTitle.textContent = status === 'COMPLETED' ? 'View E-Lot' : 'Manage Awarded E-Lot';
            dialogDescription.textContent = status === 'COMPLETED' ? 'Review the completed E-Lot and selected recycler.' : 'Update the awarded E-Lot when recycler handover is complete.';
            manageSubmit.hidden = status === 'COMPLETED';
            manageSubmit.textContent = 'Save Changes';
        }
        const cancel = manageForm.querySelector('[data-close-elot-dialog]');
        cancel.textContent = status === 'COMPLETED' || (status === 'OPEN_FOR_BIDDING' && !lotBids.length)
            ? 'Close' : 'Cancel';
        showSection(manageForm);
    });

    manageForm.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!activeRow) return;
        let status = activeRow.dataset.elotStatus;
        let winner = activeRow.cells[8].textContent.trim();

        if (status === 'OPEN_FOR_BIDDING') {
            const selectedBid = manageForm.querySelector('input[name="winner"]:checked');
            if (!selectedBid) {
                manageError.textContent = 'Select a recycler bid before awarding this E-Lot.';
                manageError.hidden = false;
                return;
            }
            status = 'AWARDED';
            winner = selectedBid.value;
        } else {
            status = manageStatus.value;
        }

        setRowState(activeRow, status, winner);
        state.updates[activeRow.dataset.elotCode] = { status, winner };
        persist();
        closeDialog();
        applyFilters();
        showToast();
    });

    filterForm.addEventListener('submit', (event) => { event.preventDefault(); applyFilters(); });
    filterForm.addEventListener('reset', () => requestAnimationFrame(applyFilters));
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog || event.target.closest('[data-close-elot-dialog]')) closeDialog();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !dialog.hidden) closeDialog();
    });

    applyFilters();
})();
