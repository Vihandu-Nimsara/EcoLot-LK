(() => {
    'use strict';

    const storageKey = 'ecolot_elots_v2';
    const categoryLabels = { DOMESTIC: 'Domestic E-Waste', OFFICE: 'Office E-Waste', INDUSTRIAL: 'Industrial E-Waste' };
    const collectorNames = {
        'Ram/2025/6': 'Ramesh Fernando', 'Kal/2024/6': 'Kasun Perera',
        'Kas/2026/6': 'Kasuni Silva', 'Pri/2026/4': 'Priyantha Jayasuriya',
    };
    const verifiedItems = [
        { id: 'ITM-201', name: 'LED Monitor', category: 'DOMESTIC', weight: 4.5 },
        { id: 'ITM-202', name: 'Rice Cooker', category: 'DOMESTIC', weight: 1 },
        { id: 'ITM-203', name: 'Laser Printer', category: 'OFFICE', weight: 5 },
        { id: 'ITM-204', name: 'UPS Unit', category: 'OFFICE', weight: 4 },
        { id: 'ITM-205', name: 'Control Unit', category: 'INDUSTRIAL', weight: 10 },
        { id: 'ITM-206', name: 'Desktop Computer', category: 'OFFICE', weight: 7.2 },
        { id: 'ITM-207', name: 'Electric Kettle', category: 'DOMESTIC', weight: 1.8 },
        { id: 'ITM-208', name: 'Industrial Relay Unit', category: 'INDUSTRIAL', weight: 6.4 },
        { id: 'ITM-209', name: 'LCD Television', category: 'DOMESTIC', weight: 5.6 },
        { id: 'ITM-210', name: 'Network Switch', category: 'OFFICE', weight: 2.3 },
        { id: 'ITM-211', name: 'Motor Controller', category: 'INDUSTRIAL', weight: 8.1 },
        { id: 'ITM-212', name: 'Desktop Scanner', category: 'OFFICE', weight: 3.2 },
    ];
    const seedLots = [
        { code: 'EL-001', title: 'Domestic Appliances Lot', collector: 'Ramesh Fernando', collectorId: 'Ram/2025/6', category: 'DOMESTIC', itemIds: ['ITM-201', 'ITM-202'], weight: 5.5, created: '2026-08-08', status: 'OPEN_FOR_BIDDING', officerNote: 'Verified and opened for recycler bidding.', period: '09 Aug – 16 Aug 2026', bids: 2, winner: '' },
        { code: 'EL-002', title: 'Office Equipment Lot', collector: 'Ramesh Fernando', collectorId: 'Ram/2025/6', category: 'OFFICE', itemIds: ['ITM-203', 'ITM-204'], weight: 9, created: '2026-08-14', status: 'PENDING_VERIFICATION', officerNote: '', period: '', bids: 0, winner: '' },
    ];

    const body = document.querySelector('[data-elots-body]');
    const dialog = document.querySelector('[data-elot-dialog]');
    if (!body || !dialog) return;

    const filters = document.querySelector('[data-elot-filters]');
    const empty = document.querySelector('[data-elots-empty]');
    const tableWrapper = document.querySelector('.elots-table-wrapper');
    const createForm = dialog.querySelector('[data-create-form]');
    const itemPool = dialog.querySelector('[data-item-pool]');
    const itemOptions = dialog.querySelector('[data-item-options]');
    const itemPoolBody = dialog.querySelector('[data-item-pool-body]');
    const selectorEmpty = dialog.querySelector('[data-item-selector-empty]');
    const createError = dialog.querySelector('[data-create-error]');
    const toast = document.querySelector('[data-elot-toast]');
    let lastFocused = null;
    let toastTimer = null;

    const getCollector = () => {
        try {
            const selection = JSON.parse(localStorage.getItem('ecolot_collector_selection') || 'null');
            const id = selection?.collectorId || 'Ram/2025/6';
            return { id, name: collectorNames[id] || 'Collector' };
        } catch (ignored) {
            return { id: 'Ram/2025/6', name: 'Ramesh Fernando' };
        }
    };
    const readState = () => {
        try {
            const saved = JSON.parse(localStorage.getItem(storageKey) || 'null');
            if (saved && Array.isArray(saved.lots)) {
                saved.usedItemIds = Array.isArray(saved.usedItemIds) ? saved.usedItemIds : [];
                return saved;
            }
        } catch (ignored) {}
        const initial = { lots: seedLots, usedItemIds: seedLots.flatMap((lot) => lot.itemIds) };
        localStorage.setItem(storageKey, JSON.stringify(initial));
        return initial;
    };
    const state = readState();
    const saveState = () => localStorage.setItem(storageKey, JSON.stringify(state));
    const makeCell = (text) => { const cell = document.createElement('td'); cell.textContent = text; return cell; };

    const renderSummary = (lots) => {
        document.querySelector('[data-total-count]').textContent = String(lots.length);
        document.querySelector('[data-pending-count]').textContent = String(lots.filter((lot) => lot.status === 'PENDING_VERIFICATION').length);
        document.querySelector('[data-open-count]').textContent = String(lots.filter((lot) => lot.status === 'OPEN_FOR_BIDDING').length);
        document.querySelector('[data-available-count]').textContent = String(verifiedItems.filter((item) => !state.usedItemIds.includes(item.id)).length);
    };
    const renderLots = () => {
        const collector = getCollector();
        const allLots = state.lots.filter((lot) => lot.collectorId === collector.id);
        const search = filters.elements.search.value.trim().toLowerCase();
        const status = filters.elements.status.value;
        const shown = allLots.filter((lot) => (!status || lot.status === status)
            && (!search || `${lot.code} ${lot.title}`.toLowerCase().includes(search)));

        body.replaceChildren();
        shown.forEach((lot) => {
            const row = document.createElement('tr');
            const identityCell = document.createElement('td');
            const code = document.createElement('strong'); code.className = 'lot-code'; code.textContent = lot.code;
            const title = document.createElement('span'); title.className = 'lot-title'; title.textContent = lot.title;
            identityCell.append(code, title);
            const statusCell = document.createElement('td');
            const badge = document.createElement('span');
            badge.className = `elot-status ${lot.status.toLowerCase().replaceAll('_', '-')}`;
            badge.textContent = lot.status.replaceAll('_', ' ');
            statusCell.appendChild(badge);
            const noteCell = makeCell(lot.officerNote || 'Awaiting review');
            if (!lot.officerNote) noteCell.className = 'officer-note-empty';
            row.append(identityCell, makeCell(categoryLabels[lot.category]), makeCell(String(lot.itemIds.length)), makeCell(`${Number(lot.weight).toFixed(2)} kg`), makeCell(lot.created), statusCell, noteCell);
            body.appendChild(row);
        });
        renderSummary(allLots);
        empty.hidden = shown.length !== 0;
        tableWrapper.hidden = shown.length === 0;
    };

    const renderPool = () => {
        itemPoolBody.replaceChildren();
        verifiedItems.forEach((item) => {
            const used = state.usedItemIds.includes(item.id);
            const row = document.createElement('tr');
            const availability = makeCell(used ? 'Added to E-Lot' : 'Available');
            availability.className = used ? 'item-used' : 'item-available';
            row.append(makeCell(item.id), makeCell(item.name), makeCell(categoryLabels[item.category]), makeCell(`${item.weight.toFixed(2)} kg`), availability);
            itemPoolBody.appendChild(row);
        });
    };
    const renderItemOptions = () => {
        const category = createForm.elements.category.value;
        const availableItems = verifiedItems.filter((item) => !state.usedItemIds.includes(item.id) && (!category || item.category === category));
        itemOptions.replaceChildren();
        availableItems.forEach((item) => {
            const label = document.createElement('label'); label.className = 'verified-item';
            const checkbox = document.createElement('input'); checkbox.type = 'checkbox'; checkbox.name = 'items'; checkbox.value = item.id;
            const name = document.createElement('strong'); name.textContent = `${item.id} — ${item.name}`;
            const weight = document.createElement('span'); weight.textContent = `${item.weight.toFixed(2)} kg`;
            label.append(checkbox, name, weight); itemOptions.appendChild(label);
        });
        selectorEmpty.hidden = availableItems.length !== 0;
    };
    const showSection = (section, trigger) => {
        lastFocused = trigger;
        createForm.hidden = section !== createForm;
        itemPool.hidden = section !== itemPool;
        dialog.querySelector('[data-dialog-eyebrow]').textContent = section === createForm ? 'New E-Lot' : 'Verified inventory';
        dialog.querySelector('[data-dialog-title]').textContent = section === createForm ? 'Create E-Lot' : 'Verified Item Pool';
        dialog.querySelector('[data-dialog-description]').textContent = section === createForm
            ? 'Select verified items from one category and submit them for officer verification.'
            : 'Each verified collection item can be included in only one E-Lot.';
        dialog.hidden = false;
        document.body.style.overflow = 'hidden';
    };
    const closeDialog = () => { dialog.hidden = true; document.body.style.overflow = ''; lastFocused?.focus(); };
    const showToast = () => {
        window.clearTimeout(toastTimer); toast.hidden = false;
        toastTimer = window.setTimeout(() => { toast.hidden = true; }, 2800);
    };

    document.querySelector('[data-open-create-elot]').addEventListener('click', (event) => {
        createForm.reset(); createError.hidden = true; renderItemOptions();
        showSection(createForm, event.currentTarget); createForm.elements.title.focus();
    });
    document.querySelector('[data-open-item-pool]').addEventListener('click', (event) => {
        renderPool(); showSection(itemPool, event.currentTarget);
    });
    createForm.elements.category.addEventListener('change', renderItemOptions);
    createForm.addEventListener('input', () => { createError.hidden = true; });
    createForm.addEventListener('submit', (event) => {
        event.preventDefault();
        const title = createForm.elements.title.value.trim();
        const category = createForm.elements.category.value;
        const itemIds = Array.from(createForm.querySelectorAll('input[name="items"]:checked')).map((input) => input.value);
        const selectedItems = verifiedItems.filter((item) => itemIds.includes(item.id));
        if (title.length < 3) {
            createError.textContent = 'Enter a clear E-Lot title.'; createError.hidden = false; createForm.elements.title.focus(); return;
        }
        if (!category || selectedItems.length === 0 || selectedItems.some((item) => item.category !== category)) {
            createError.textContent = 'Select a category and at least one available verified item from that category.'; createError.hidden = false; return;
        }
        const nextNumber = Math.max(0, ...state.lots.map((lot) => Number(String(lot.code).replace('EL-', '')) || 0)) + 1;
        const collector = getCollector();
        state.lots.unshift({
            code: `EL-${String(nextNumber).padStart(3, '0')}`, title, collector: collector.name, collectorId: collector.id,
            category, itemIds, weight: selectedItems.reduce((total, item) => total + item.weight, 0),
            created: new Date().toISOString().slice(0, 10), status: 'PENDING_VERIFICATION', officerNote: '', period: '', bids: 0, winner: '',
        });
        state.usedItemIds.push(...itemIds);
        saveState(); closeDialog(); renderLots(); showToast();
    });
    filters.addEventListener('input', renderLots);
    filters.addEventListener('reset', () => requestAnimationFrame(renderLots));
    dialog.addEventListener('click', (event) => { if (event.target === dialog || event.target.closest('[data-close-dialog]')) closeDialog(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !dialog.hidden) closeDialog(); });
    window.addEventListener('storage', (event) => { if (event.key === storageKey) window.location.reload(); });
    renderLots();
})();
