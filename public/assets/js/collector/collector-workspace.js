const collectorWorkspaceData = {
    collector: {
        userId: 26,
        displayId: 'Collector #26',
        name: 'Collector User',
    },
    schedules: [
        {
            scheduleId: 3201,
            area: 'Kollupitiya',
            postalCode: '00300',
            collectionDate: '2026-08-16',
            status: 'IN_PROGRESS',
            vehicle: 'WP LC-4821',
            requests: [
                {
                    requestId: 1058,
                    pickupAddress: 'No. 45, Galle Road, Colombo 03',
                    requestStatus: 'APPROVED',
                    items: [
                        { requestItemId: 5001, itemName: 'Laptop Computer', category: 'Computers & Accessories', requestedQuantity: 2, estimatedWeightKg: 4.8 },
                        { requestItemId: 5002, itemName: 'LCD Monitor', category: 'Display Equipment', requestedQuantity: 1, estimatedWeightKg: 3.6 },
                    ],
                },
                {
                    requestId: 1061,
                    pickupAddress: 'No. 28, Duplication Road, Colombo 03',
                    requestStatus: 'APPROVED',
                    items: [
                        { requestItemId: 5003, itemName: 'Mobile Phone', category: 'Mobile Devices', requestedQuantity: 4, estimatedWeightKg: 0.8 },
                        { requestItemId: 5004, itemName: 'Wi-Fi Router', category: 'Network Equipment', requestedQuantity: 2, estimatedWeightKg: 0.7 },
                        { requestItemId: 5005, itemName: 'UPS Unit', category: 'Power Equipment', requestedQuantity: 1, estimatedWeightKg: 5.2 },
                    ],
                },
                {
                    requestId: 1064,
                    pickupAddress: '12/4, Sea View Avenue, Colombo 03',
                    requestStatus: 'APPROVED',
                    items: [
                        { requestItemId: 5006, itemName: 'Desktop Computer', category: 'Computers & Accessories', requestedQuantity: 1, estimatedWeightKg: 8.4 },
                        { requestItemId: 5007, itemName: 'Keyboard', category: 'Computers & Accessories', requestedQuantity: 2, estimatedWeightKg: 1.1 },
                    ],
                },
            ],
        },
        {
            scheduleId: 3204,
            area: 'Narahenpita',
            postalCode: '00500',
            collectionDate: '2026-08-18',
            status: 'ASSIGNED',
            vehicle: 'WP LM-7315',
            requests: [
                {
                    requestId: 1072,
                    pickupAddress: 'No. 18, Kirimandala Mawatha, Colombo 05',
                    requestStatus: 'APPROVED',
                    items: [
                        { requestItemId: 5008, itemName: 'Television', category: 'Display Equipment', requestedQuantity: 1, estimatedWeightKg: 9.5 },
                        { requestItemId: 5009, itemName: 'DVD Player', category: 'Home Electronics', requestedQuantity: 1, estimatedWeightKg: 1.8 },
                    ],
                },
                {
                    requestId: 1075,
                    pickupAddress: 'No. 67, Park Road, Colombo 05',
                    requestStatus: 'APPROVED',
                    items: [
                        { requestItemId: 5010, itemName: 'Printer', category: 'Office Equipment', requestedQuantity: 2, estimatedWeightKg: 11.0 },
                    ],
                },
            ],
        },
        {
            scheduleId: 3208,
            area: 'Rajagiriya',
            postalCode: '10100',
            collectionDate: '2026-08-25',
            status: 'ASSIGNED',
            vehicle: 'Vehicle pending',
            requests: [
                {
                    requestId: 1083,
                    pickupAddress: 'No. 22, Parliament Road, Rajagiriya',
                    requestStatus: 'APPROVED',
                    items: [
                        { requestItemId: 5011, itemName: 'Refrigerator', category: 'Large Appliances', requestedQuantity: 1, estimatedWeightKg: 42.0 },
                    ],
                },
                {
                    requestId: 1087,
                    pickupAddress: '14/2, Lake Drive, Rajagiriya',
                    requestStatus: 'APPROVED',
                    items: [
                        { requestItemId: 5012, itemName: 'Electric Fan', category: 'Small Appliances', requestedQuantity: 3, estimatedWeightKg: 8.7 },
                        { requestItemId: 5013, itemName: 'Electric Kettle', category: 'Small Appliances', requestedQuantity: 2, estimatedWeightKg: 2.1 },
                    ],
                },
                {
                    requestId: 1090,
                    pickupAddress: 'No. 91, Cotta Road, Borella',
                    requestStatus: 'APPROVED',
                    items: [
                        { requestItemId: 5014, itemName: 'Tablet Computer', category: 'Mobile Devices', requestedQuantity: 2, estimatedWeightKg: 1.3 },
                    ],
                },
                {
                    requestId: 1094,
                    pickupAddress: 'No. 36, Welikada Terrace, Rajagiriya',
                    requestStatus: 'APPROVED',
                    items: [
                        { requestItemId: 5015, itemName: 'Washing Machine', category: 'Large Appliances', requestedQuantity: 1, estimatedWeightKg: 58.0 },
                    ],
                },
            ],
        },
    ],
};

const collectorStorageKeys = {
    selectedSchedule: 'ecolot_collector_selected_schedule_id',
    collectionRecords: 'ecolot_collector_collection_records',
};

let activeRequestId = null;
let returnFocusElement = null;

function readJSONStorage(key, fallback) {
    try {
        const value = JSON.parse(localStorage.getItem(key));
        return value ?? fallback;
    } catch {
        return fallback;
    }
}

function writeJSONStorage(key, value) {
    localStorage.setItem(key, JSON.stringify(value));
}

function getCollectionRecords() {
    return readJSONStorage(collectorStorageKeys.collectionRecords, {});
}

function getSelectedScheduleId() {
    const savedId = Number(localStorage.getItem(collectorStorageKeys.selectedSchedule));
    const matchingSchedule = collectorWorkspaceData.schedules.find((schedule) => schedule.scheduleId === savedId);
    return matchingSchedule?.scheduleId ?? collectorWorkspaceData.schedules[0]?.scheduleId ?? null;
}

function setSelectedScheduleId(scheduleId) {
    localStorage.setItem(collectorStorageKeys.selectedSchedule, String(scheduleId));
}

function getSelectedSchedule() {
    const selectedId = getSelectedScheduleId();
    return collectorWorkspaceData.schedules.find((schedule) => schedule.scheduleId === selectedId) ?? null;
}

function findRequest(requestId) {
    for (const schedule of collectorWorkspaceData.schedules) {
        const request = schedule.requests.find((candidate) => candidate.requestId === requestId);
        if (request) return { schedule, request };
    }
    return null;
}

function formatScheduleId(scheduleId) {
    return `SCH-${String(scheduleId).padStart(5, '0')}`;
}

function formatRequestId(requestId) {
    return `REQ-${String(requestId).padStart(5, '0')}`;
}

function formatDate(dateValue) {
    return new Intl.DateTimeFormat('en-LK', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(`${dateValue}T00:00:00`));
}

function humanizeStatus(status) {
    return String(status)
        .toLowerCase()
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

function statusClass(status) {
    return `status-${String(status).toLowerCase().replaceAll('_', '-')}`;
}

function escapeHTML(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function roundWeight(value) {
    return Math.round((Number(value) + Number.EPSILON) * 1000) / 1000;
}

function setText(selector, value) {
    document.querySelectorAll(selector).forEach((element) => {
        element.textContent = value;
    });
}

function setBadge(element, status, label = humanizeStatus(status)) {
    if (!element) return;
    element.className = `status-badge ${statusClass(status)}`;
    element.textContent = label;
}

function updateCollectorIdentity() {
    const { name, displayId } = collectorWorkspaceData.collector;
    setText('[data-collector-name]', name);
    setText('[data-collector-id]', displayId);
    setText(
        '[data-collector-initials]',
        name.split(' ').map((part) => part[0]).join('').slice(0, 2).toUpperCase()
    );
}

function getScheduleProgress(schedule, records = getCollectionRecords()) {
    const confirmedRequests = schedule.requests.filter((request) => records[request.requestId]?.confirmed);
    const total = schedule.requests.length;
    const confirmed = confirmedRequests.length;
    const percentage = total === 0 ? 0 : Math.round((confirmed / total) * 100);
    const actualWeight = confirmedRequests.reduce((scheduleTotal, request) => {
        const requestWeight = records[request.requestId].items.reduce(
            (itemTotal, item) => itemTotal + Number(item.actualWeightKg || 0),
            0
        );
        return scheduleTotal + requestWeight;
    }, 0);

    return { total, confirmed, pending: Math.max(total - confirmed, 0), percentage, actualWeight: roundWeight(actualWeight) };
}

function renderScheduleCards() {
    const scheduleList = document.querySelector('[data-schedule-list]');
    if (!scheduleList) return;

    const schedules = collectorWorkspaceData.schedules;
    const selectedScheduleId = getSelectedScheduleId();
    const records = getCollectionRecords();

    setText('[data-schedule-count]', `${schedules.length} schedule${schedules.length === 1 ? '' : 's'}`);

    const nextSchedule = [...schedules].sort((a, b) => a.collectionDate.localeCompare(b.collectionDate))[0];
    setText('[data-next-collection]', nextSchedule ? `Next: ${formatDate(nextSchedule.collectionDate)}` : 'No upcoming collections');

    scheduleList.innerHTML = schedules.map((schedule) => {
        const progress = getScheduleProgress(schedule, records);
        const isSelected = schedule.scheduleId === selectedScheduleId;
        return `
            <button
                type="button"
                class="schedule-card${isSelected ? ' is-selected' : ''}"
                data-schedule-select="${schedule.scheduleId}"
                aria-pressed="${isSelected}"
            >
                <span class="schedule-card-top">
                    <span class="schedule-card-date">${escapeHTML(formatDate(schedule.collectionDate))}</span>
                    <span class="status-badge ${statusClass(schedule.status)}">${escapeHTML(humanizeStatus(schedule.status))}</span>
                </span>
                <h2>${escapeHTML(schedule.area)}</h2>
                <p>${escapeHTML(formatScheduleId(schedule.scheduleId))} · ${escapeHTML(schedule.postalCode)}</p>
                <span class="schedule-card-footer">
                    <span class="schedule-progress-copy-mini">${progress.confirmed} of ${progress.total} confirmed</span>
                    <span class="schedule-open-label">${isSelected ? 'Selected' : 'View schedule'} →</span>
                </span>
            </button>
        `;
    }).join('');
}

function renderRequestTable(schedule) {
    const tableBody = document.querySelector('[data-request-table-body]');
    if (!tableBody) return;

    const records = getCollectionRecords();
    setText('[data-selected-request-count]', `${schedule.requests.length} request${schedule.requests.length === 1 ? '' : 's'}`);

    tableBody.innerHTML = schedule.requests.map((request) => {
        const record = records[request.requestId];
        const status = record?.confirmed ? 'COMPLETED' : 'APPROVED';
        const label = record?.confirmed ? 'Confirmed' : 'Pending collection';

        return `
            <tr>
                <td><span class="request-reference">${escapeHTML(formatRequestId(request.requestId))}</span></td>
                <td class="request-address">${escapeHTML(request.pickupAddress)}</td>
                <td><span class="request-item-count">${request.items.length} item${request.items.length === 1 ? '' : 's'}</span></td>
                <td><span class="status-badge ${statusClass(status)}">${escapeHTML(label)}</span></td>
                <td class="table-action-cell">
                    <button type="button" class="secondary-btn view-detail-btn" data-view-request="${request.requestId}">View Details</button>
                </td>
            </tr>
        `;
    }).join('');
}

function renderScheduleSummary(schedule) {
    const progress = getScheduleProgress(schedule);

    setText('[data-summary-total]', progress.total);
    setText('[data-summary-confirmed]', progress.confirmed);
    setText('[data-summary-pending]', progress.pending);
    setText('[data-summary-weight]', progress.actualWeight.toFixed(1));
    setText('[data-summary-progress-label]', `${progress.percentage}% complete`);

    const progressBar = document.querySelector('[data-summary-progress-bar]');
    if (progressBar) progressBar.style.width = `${progress.percentage}%`;
}

function renderSelectedSchedule() {
    const schedule = getSelectedSchedule();
    const shell = document.querySelector('[data-selected-schedule-shell]');
    const emptyState = document.querySelector('[data-no-schedules]');

    if (!schedule) {
        if (shell) shell.hidden = true;
        if (emptyState) emptyState.hidden = false;
        return;
    }

    if (shell) shell.hidden = false;
    if (emptyState) emptyState.hidden = true;

    setText('[data-selected-schedule-title]', `${schedule.area} Collection`);
    setText('[data-selected-schedule-subtitle]', `${schedule.requests.length} approved requests assigned to this collection schedule.`);
    setText('[data-selected-schedule-id]', formatScheduleId(schedule.scheduleId));
    setText('[data-selected-schedule-date]', formatDate(schedule.collectionDate));
    setText('[data-selected-schedule-postal]', schedule.postalCode);
    setText('[data-selected-schedule-vehicle]', schedule.vehicle);
    setBadge(document.querySelector('[data-selected-schedule-status]'), schedule.status);

    renderRequestTable(schedule);
    renderScheduleSummary(schedule);
}

function renderWorkspace() {
    renderScheduleCards();
    renderSelectedSchedule();
}

function defaultItemValues(item, recordItem) {
    return {
        actualQuantity: recordItem?.actualQuantity ?? item.requestedQuantity,
        actualWeightKg: recordItem?.actualWeightKg ?? item.estimatedWeightKg,
        itemResult: recordItem?.itemResult ?? calculateItemResult(item.requestedQuantity, item.requestedQuantity, item.estimatedWeightKg),
    };
}

function calculateItemResult(requestedQuantity, actualQuantity, actualWeightKg) {
    if (actualQuantity === 0 && actualWeightKg === 0) return 'NOT_COLLECTED';
    if (actualQuantity < requestedQuantity) return 'PARTIAL';
    return 'COLLECTED';
}

function calculatePickupResult(items) {
    if (items.every((item) => item.itemResult === 'NOT_COLLECTED')) return 'NOT_COLLECTED';
    if (items.every((item) => item.itemResult === 'COLLECTED')) return 'COLLECTED';
    return 'PARTIAL';
}

function updateItemResultPreview(row) {
    const requestedQuantity = Number(row.dataset.requestedQuantity);
    const quantity = Number(row.querySelector('[data-actual-quantity]').value);
    const weight = Number(row.querySelector('[data-actual-weight]').value);
    const result = calculateItemResult(requestedQuantity, quantity, weight);
    setBadge(row.querySelector('[data-item-result]'), result);
}

function updateNoteCharacterCount() {
    const note = document.querySelector('[data-collector-request-note]');
    setText('[data-note-character-count]', note?.value.length ?? 0);
}

function populateRequestModal(schedule, request) {
    const records = getCollectionRecords();
    const record = records[request.requestId];
    const isConfirmed = Boolean(record?.confirmed);
    const estimatedWeight = request.items.reduce((total, item) => total + item.estimatedWeightKg, 0);

    setText('[data-modal-request-id]', formatRequestId(request.requestId));
    setText('[data-modal-request-address]', request.pickupAddress);
    setText('[data-modal-schedule-id]', formatScheduleId(schedule.scheduleId));
    setText('[data-modal-item-count]', `${request.items.length} item${request.items.length === 1 ? '' : 's'}`);
    setText('[data-modal-estimated-weight]', `${roundWeight(estimatedWeight).toFixed(1)} kg`);
    setText('[data-modal-pickup-result]', isConfirmed ? humanizeStatus(record.pickupResult) : 'Not confirmed');
    setBadge(
        document.querySelector('[data-modal-request-status]'),
        isConfirmed ? 'COMPLETED' : 'APPROVED',
        isConfirmed ? 'Confirmed' : 'Pending collection'
    );

    const itemBody = document.querySelector('[data-request-items-body]');
    itemBody.innerHTML = request.items.map((item) => {
        const recordItem = record?.items.find((candidate) => candidate.requestItemId === item.requestItemId);
        const values = defaultItemValues(item, recordItem);
        return `
            <tr data-collection-item-row data-request-item-id="${item.requestItemId}" data-requested-quantity="${item.requestedQuantity}">
                <td class="item-name-cell">
                    <strong>${escapeHTML(item.itemName)}</strong>
                    <span>${escapeHTML(item.category)}</span>
                </td>
                <td class="requested-value">
                    ${item.requestedQuantity} unit${item.requestedQuantity === 1 ? '' : 's'}
                    <span>${item.estimatedWeightKg.toFixed(1)} kg estimated</span>
                </td>
                <td>
                    <input
                        type="number"
                        class="collection-value-input"
                        min="0"
                        step="1"
                        inputmode="numeric"
                        aria-label="Actual quantity for ${escapeHTML(item.itemName)}"
                        value="${values.actualQuantity}"
                        data-actual-quantity
                        ${isConfirmed ? 'disabled' : ''}
                    >
                </td>
                <td>
                    <input
                        type="number"
                        class="collection-value-input"
                        min="0"
                        step="0.001"
                        inputmode="decimal"
                        aria-label="Actual weight in kilograms for ${escapeHTML(item.itemName)}"
                        value="${values.actualWeightKg}"
                        data-actual-weight
                        ${isConfirmed ? 'disabled' : ''}
                    >
                </td>
                <td><span class="status-badge ${statusClass(values.itemResult)}" data-item-result>${escapeHTML(humanizeStatus(values.itemResult))}</span></td>
            </tr>
        `;
    }).join('');

    const note = document.querySelector('[data-collector-request-note]');
    note.value = record?.collectorNote ?? '';
    note.disabled = isConfirmed;
    updateNoteCharacterCount();

    const confirmButton = document.querySelector('[data-confirm-request]');
    confirmButton.disabled = isConfirmed;
    confirmButton.textContent = isConfirmed ? 'Collection Confirmed' : 'Confirm Collection';

    const validation = document.querySelector('[data-request-validation]');
    validation.hidden = true;
    validation.classList.remove('is-success');
}

function openRequestModal(requestId, triggerElement) {
    const result = findRequest(requestId);
    const modal = document.querySelector('[data-request-modal]');
    if (!result || !modal) return;

    activeRequestId = requestId;
    returnFocusElement = triggerElement ?? null;
    populateRequestModal(result.schedule, result.request);
    modal.hidden = false;
    document.body.classList.add('modal-open');
    modal.querySelector('[data-close-request-modal]')?.focus();
}

function closeRequestModal() {
    const modal = document.querySelector('[data-request-modal]');
    if (!modal || modal.hidden) return;

    modal.hidden = true;
    document.body.classList.remove('modal-open');
    activeRequestId = null;
    returnFocusElement?.focus();
    returnFocusElement = null;
}

function collectItemValues() {
    return [...document.querySelectorAll('[data-collection-item-row]')].map((row) => {
        const requestedQuantity = Number(row.dataset.requestedQuantity);
        const actualQuantity = Number(row.querySelector('[data-actual-quantity]').value);
        const actualWeightKg = Number(row.querySelector('[data-actual-weight]').value);
        return {
            requestItemId: Number(row.dataset.requestItemId),
            requestedQuantity,
            actualQuantity,
            actualWeightKg: roundWeight(actualWeightKg),
            itemResult: calculateItemResult(requestedQuantity, actualQuantity, actualWeightKg),
        };
    });
}

function validateCollectionItems(items) {
    for (const item of items) {
        if (!Number.isInteger(item.actualQuantity) || item.actualQuantity < 0) {
            return 'Actual quantity must be a whole number of zero or more for every item.';
        }
        if (!Number.isFinite(item.actualWeightKg) || item.actualWeightKg < 0) {
            return 'Actual weight must be a valid value of zero or more for every item.';
        }
        const hasQuantity = item.actualQuantity > 0;
        const hasWeight = item.actualWeightKg > 0;
        if (hasQuantity !== hasWeight) {
            return 'Quantity and weight must both be zero for an item not collected, or both be greater than zero.';
        }
    }
    return null;
}

function confirmActiveRequest() {
    const result = findRequest(activeRequestId);
    if (!result) return;

    const items = collectItemValues();
    const validationMessage = validateCollectionItems(items);
    const validation = document.querySelector('[data-request-validation]');

    if (validationMessage) {
        validation.textContent = validationMessage;
        validation.classList.remove('is-success');
        validation.hidden = false;
        return;
    }

    const records = getCollectionRecords();
    records[result.request.requestId] = {
        confirmed: true,
        pickupResult: calculatePickupResult(items),
        collectorNote: document.querySelector('[data-collector-request-note]').value.trim(),
        collectedAt: new Date().toISOString(),
        items: items.map(({ requestedQuantity, ...item }) => item),
    };
    writeJSONStorage(collectorStorageKeys.collectionRecords, records);

    renderWorkspace();
    populateRequestModal(result.schedule, result.request);
    validation.textContent = 'Collection details confirmed. This request is now available in read-only view.';
    validation.classList.add('is-success');
    validation.hidden = false;
}

function bindCollectorWorkspaceEvents() {
    document.querySelector('[data-schedule-list]')?.addEventListener('click', (event) => {
        const scheduleButton = event.target.closest('[data-schedule-select]');
        if (!scheduleButton) return;

        setSelectedScheduleId(Number(scheduleButton.dataset.scheduleSelect));
        renderWorkspace();
        document.querySelector('[data-selected-schedule-shell]')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    document.querySelector('[data-request-table-body]')?.addEventListener('click', (event) => {
        const detailsButton = event.target.closest('[data-view-request]');
        if (!detailsButton) return;
        openRequestModal(Number(detailsButton.dataset.viewRequest), detailsButton);
    });

    document.querySelectorAll('[data-close-request-modal]').forEach((button) => {
        button.addEventListener('click', closeRequestModal);
    });

    document.querySelector('[data-request-modal]')?.addEventListener('click', (event) => {
        if (event.target === event.currentTarget) closeRequestModal();
    });

    document.querySelector('[data-request-items-body]')?.addEventListener('input', (event) => {
        const row = event.target.closest('[data-collection-item-row]');
        if (!row) return;
        updateItemResultPreview(row);
        document.querySelector('[data-request-validation]').hidden = true;
    });

    document.querySelector('[data-collector-request-note]')?.addEventListener('input', updateNoteCharacterCount);
    document.querySelector('[data-confirm-request]')?.addEventListener('click', confirmActiveRequest);
    document.querySelector('[data-print-summary]')?.addEventListener('click', () => window.print());

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeRequestModal();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    updateCollectorIdentity();

    if (!document.querySelector('[data-collector-page]')) return;
    renderWorkspace();
    bindCollectorWorkspaceEvents();
});
