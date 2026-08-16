(() => {
    'use strict';

    const storageKey = 'ecolot_elots_v2';
    const categoryLabels = { DOMESTIC: 'Domestic E-Waste', OFFICE: 'Office E-Waste', INDUSTRIAL: 'Industrial E-Waste' };
    const seedLots = [
        { code: 'EL-001', title: 'Domestic E-Waste Lot', collector: 'Ramesh Fernando', collectorId: 'Ram/2025/6', category: 'DOMESTIC', itemIds: ['ITM-201', 'ITM-202'], weight: 5.5, created: '2026-08-02', status: 'OPEN_FOR_BIDDING', officerNote: 'Verified and opened for bidding.', period: '03 Aug – 09 Aug 2026', bids: 2, winner: '' },
        { code: 'EL-002', title: 'Office E-Waste Lot', collector: 'Ramesh Fernando', collectorId: 'Ram/2025/6', category: 'OFFICE', itemIds: ['ITM-203', 'ITM-204'], weight: 9, created: '2026-07-05', status: 'AWARDED', officerNote: 'Verified.', period: '06 Jul – 09 Jul 2026', bids: 2, winner: 'GreenCycle Lanka (Pvt) Ltd' },
        { code: 'EL-003', title: 'Industrial E-Waste Lot', collector: 'Ramesh Fernando', collectorId: 'Ram/2025/6', category: 'INDUSTRIAL', itemIds: ['ITM-205'], weight: 10, created: '2026-07-05', status: 'COMPLETED', officerNote: 'Verified.', period: '06 Jul – 09 Jul 2026', bids: 1, winner: 'Ceylon Circular Metals (Pvt) Ltd' },
    ];
    const bids = {
        'EL-001': [
            { recycler: 'GreenCycle Lanka (Pvt) Ltd', amount: 'LKR 42,500' },
            { recycler: 'Eco Renew Solutions (Pvt) Ltd', amount: 'LKR 39,750' },
        ],
    };

    const filterForm = document.querySelector('[data-elot-filter-form]');
    const body = document.querySelector('[data-elots-table-body]');
    const dialog = document.querySelector('[data-elot-dialog]');
    if (!filterForm || !body || !dialog) return;

    const tableWrapper = document.querySelector('.elots-table-wrapper');
    const empty = document.querySelector('[data-elots-empty-state]');
    const count = document.querySelector('[data-elot-result-count]');
    const pendingCount = document.querySelector('[data-pending-count]');
    const reviewForm = dialog.querySelector('[data-review-form]');
    const manageForm = dialog.querySelector('[data-manage-form]');
    const reviewError = dialog.querySelector('[data-review-error]');
    const manageError = dialog.querySelector('[data-manage-error]');
    const manageSubmit = dialog.querySelector('[data-manage-submit]');
    const bidSelector = dialog.querySelector('[data-bid-selector]');
    const bidOptions = dialog.querySelector('[data-bid-options]');
    const winnerSummary = dialog.querySelector('[data-winner-summary]');
    const toast = document.querySelector('[data-elot-toast]');
    let activeLot = null;
    let lastFocused = null;
    let toastTimer = null;

    const readState = () => {
        try {
            const saved = JSON.parse(localStorage.getItem(storageKey) || 'null');
            if (saved && Array.isArray(saved.lots)) return saved;
        } catch (ignored) {}
        const initial = { lots: seedLots, usedItemIds: ['ITM-201', 'ITM-202', 'ITM-203', 'ITM-204', 'ITM-205'] };
        localStorage.setItem(storageKey, JSON.stringify(initial));
        return initial;
    };
    const state = readState();
    const save = () => localStorage.setItem(storageKey, JSON.stringify(state));
    const cell = (text) => { const td = document.createElement('td'); td.textContent = text; return td; };
    const statusClass = (status) => status.toLowerCase().replaceAll('_', '-');
    const actionLabel = (status) => {
        if (status === 'PENDING_VERIFICATION') return 'Verify';
        if (status === 'REJECTED') return 'Review Again';
        if (status === 'OPEN_FOR_BIDDING') return 'Review Bids';
        if (status === 'AWARDED') return 'Manage';
        return 'View';
    };

    const render = () => {
        const status = filterForm.elements.elot_status.value;
        const category = filterForm.elements.category.value;
        const lots = state.lots.filter((lot) => (!status || lot.status === status) && (!category || lot.category === category));
        body.replaceChildren();
        lots.forEach((lot) => {
            const row = document.createElement('tr');
            row.dataset.code = lot.code;
            const lotCell = document.createElement('td');
            const code = document.createElement('strong');
            code.textContent = lot.code;
            const title = document.createElement('span');
            title.className = 'table-secondary-text';
            title.textContent = lot.title;
            lotCell.append(code, title);
            const statusCell = document.createElement('td');
            const badge = document.createElement('span');
            badge.className = `elot-status ${statusClass(lot.status)}`;
            badge.textContent = lot.status.replaceAll('_', ' ');
            statusCell.appendChild(badge);
            const winnerCell = cell(lot.winner || '');
            if (!lot.winner) {
                const missing = document.createElement('span');
                missing.className = 'not-assigned';
                missing.textContent = 'Not selected';
                winnerCell.appendChild(missing);
            }
            const actionCell = document.createElement('td');
            const action = document.createElement('button');
            action.type = 'button';
            action.className = `elot-action-btn ${['PENDING_VERIFICATION', 'OPEN_FOR_BIDDING'].includes(lot.status) ? 'primary-action' : 'secondary-action'}`;
            action.textContent = actionLabel(lot.status);
            actionCell.appendChild(action);
            row.append(lotCell, cell(lot.collector), cell(categoryLabels[lot.category]), cell(String(lot.itemIds.length)), cell(`${Number(lot.weight).toFixed(2)} kg`), cell(lot.created), cell(lot.period || 'Not opened'), cell(String(lot.bids || 0)), statusCell, winnerCell, actionCell);
            body.appendChild(row);
        });
        count.textContent = `${lots.length} ${lots.length === 1 ? 'E-Lot' : 'E-Lots'} shown`;
        pendingCount.textContent = String(state.lots.filter((lot) => lot.status === 'PENDING_VERIFICATION').length);
        empty.hidden = lots.length !== 0;
        tableWrapper.hidden = lots.length === 0;
    };

    const formatDate = (value) => new Intl.DateTimeFormat('en-GB', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC' }).format(new Date(`${value}T00:00:00Z`));
    const setDialogSummary = (lot) => {
        dialog.querySelector('[data-dialog-eyebrow]').textContent = lot.code;
        dialog.querySelector('[data-summary-collector]').textContent = lot.collector;
        dialog.querySelector('[data-summary-category]').textContent = categoryLabels[lot.category];
        dialog.querySelector('[data-summary-items]').textContent = String(lot.itemIds.length);
        dialog.querySelector('[data-summary-weight]').textContent = `${Number(lot.weight).toFixed(2)} kg`;
    };
    const showDialog = () => { dialog.hidden = false; document.body.style.overflow = 'hidden'; };
    const closeDialog = () => { dialog.hidden = true; document.body.style.overflow = ''; activeLot = null; lastFocused?.focus(); };
    const showToast = (message) => {
        window.clearTimeout(toastTimer);
        toast.textContent = message;
        toast.hidden = false;
        toastTimer = window.setTimeout(() => { toast.hidden = true; }, 2800);
    };
    const toggleBiddingFields = () => {
        const approving = reviewForm.elements.decision.value === 'APPROVE';
        reviewForm.querySelectorAll('.bidding-field').forEach((field) => { field.hidden = !approving; });
        dialog.querySelector('[data-review-submit]').textContent = approving ? 'Approve E-Lot' : 'Reject E-Lot';
    };

    body.addEventListener('click', (event) => {
        const button = event.target.closest('.elot-action-btn');
        if (!button) return;
        activeLot = state.lots.find((lot) => lot.code === button.closest('tr').dataset.code);
        if (!activeLot) return;
        lastFocused = button;
        setDialogSummary(activeLot);
        reviewForm.hidden = true;
        manageForm.hidden = true;
        reviewError.hidden = true;
        manageError.hidden = true;
        winnerSummary.hidden = true;
        bidSelector.hidden = true;
        manageSubmit.hidden = true;

        if (['PENDING_VERIFICATION', 'REJECTED'].includes(activeLot.status)) {
            reviewForm.hidden = false;
            reviewForm.reset();
            reviewForm.elements.note.value = activeLot.officerNote || '';
            dialog.querySelector('[data-dialog-title]').textContent = activeLot.status === 'REJECTED' ? 'Review Rejected E-Lot' : 'Verify Collector E-Lot';
            dialog.querySelector('[data-dialog-description]').textContent = 'Approve the E-Lot and set its bidding window, or return it to the collector with a reason.';
            toggleBiddingFields();
        } else {
            manageForm.hidden = false;
            const lotBids = bids[activeLot.code] || [];
            const noteCard = dialog.querySelector('[data-note-card]');
            noteCard.hidden = !activeLot.officerNote;
            dialog.querySelector('[data-note-output]').textContent = activeLot.officerNote || '';
            if (activeLot.status === 'OPEN_FOR_BIDDING') {
                dialog.querySelector('[data-dialog-title]').textContent = 'Review Recycler Bids';
                dialog.querySelector('[data-dialog-description]').textContent = lotBids.length ? 'Select the winning recycler bid.' : 'No recycler bids have been submitted yet.';
                bidSelector.hidden = false;
                bidOptions.replaceChildren();
                lotBids.forEach((bid, index) => {
                    const label = document.createElement('label');
                    label.className = 'bid-option';
                    const radio = document.createElement('input');
                    radio.type = 'radio'; radio.name = 'winner'; radio.value = bid.recycler; radio.checked = index === 0;
                    const name = document.createElement('strong'); name.textContent = bid.recycler;
                    const amount = document.createElement('span'); amount.textContent = bid.amount;
                    label.append(radio, name, amount); bidOptions.appendChild(label);
                });
                if (!lotBids.length) bidOptions.textContent = 'Waiting for recycler bids.';
                manageSubmit.hidden = lotBids.length === 0;
                manageSubmit.textContent = 'Award E-Lot';
            } else {
                winnerSummary.hidden = false;
                dialog.querySelector('[data-winner-output]').textContent = activeLot.winner || 'Not selected';
                dialog.querySelector('[data-dialog-title]').textContent = activeLot.status === 'COMPLETED' ? 'View Completed E-Lot' : 'Manage Awarded E-Lot';
                dialog.querySelector('[data-dialog-description]').textContent = activeLot.status === 'COMPLETED' ? 'Review the completed handover details.' : 'Mark the E-Lot complete after recycler handover.';
                manageSubmit.hidden = activeLot.status === 'COMPLETED';
                manageSubmit.textContent = 'Mark Completed';
            }
        }
        showDialog();
    });

    reviewForm.elements.decision.addEventListener('change', toggleBiddingFields);
    reviewForm.addEventListener('input', () => { reviewError.hidden = true; });
    reviewForm.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!activeLot) return;
        const decision = reviewForm.elements.decision.value;
        const note = reviewForm.elements.note.value.trim();
        if (decision === 'REJECT' && note.length < 3) {
            reviewError.textContent = 'Add a clear reason before rejecting the E-Lot.';
            reviewError.hidden = false;
            return;
        }
        if (decision === 'APPROVE') {
            const start = reviewForm.elements.start.value;
            const end = reviewForm.elements.end.value;
            if (!start || !end || end <= start) {
                reviewError.textContent = 'Choose a valid bidding window with a closing date after the opening date.';
                reviewError.hidden = false;
                return;
            }
            activeLot.status = 'OPEN_FOR_BIDDING';
            activeLot.period = `${formatDate(start)} – ${formatDate(end)}`;
            activeLot.officerNote = note || 'Verified and opened for bidding.';
        } else {
            activeLot.status = 'REJECTED';
            activeLot.period = '';
            activeLot.officerNote = note;
        }
        save();
        closeDialog();
        render();
        showToast(decision === 'APPROVE' ? 'E-Lot approved and opened for bidding.' : 'E-Lot returned to the collector.');
    });

    manageForm.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!activeLot) return;
        if (activeLot.status === 'OPEN_FOR_BIDDING') {
            const selected = manageForm.querySelector('input[name="winner"]:checked');
            if (!selected) {
                manageError.textContent = 'Select a recycler bid before awarding this E-Lot.';
                manageError.hidden = false;
                return;
            }
            activeLot.status = 'AWARDED';
            activeLot.winner = selected.value;
        } else if (activeLot.status === 'AWARDED') {
            activeLot.status = 'COMPLETED';
        }
        save();
        closeDialog();
        render();
        showToast('E-Lot updated.');
    });
    filterForm.addEventListener('submit', (event) => { event.preventDefault(); render(); });
    filterForm.addEventListener('reset', () => requestAnimationFrame(render));
    dialog.addEventListener('click', (event) => { if (event.target === dialog || event.target.closest('[data-close-dialog]')) closeDialog(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !dialog.hidden) closeDialog(); });
    window.addEventListener('storage', (event) => { if (event.key === storageKey) window.location.reload(); });
    render();
})();
