const collectorsData = {
    "Ram/2025/6": {
        name: "Ramesh Fernando",
        postal: "10500", zone: "Kollupitiya QA Zone", routes: [
            { id: "ram-r1", address: "No 23, Alfred Road", weight: "18 kg" },
            { id: "ram-r2", address: "No 28, Market Road", weight: "15 kg" },
            { id: "ram-r3", address: "No 29, Market Road", weight: "12 kg" },
            { id: "ram-r4", address: "No 42, Prince Road", weight: "20 kg" },
        ]
    },
    "Kal/2024/6": {
        name: "Kasun Perera",
        postal: "10600", zone: "Narahenpita QA Zone", routes: [
            { id: "kal-r1", address: "No 10, Sinha Patumaga", weight: "14 kg" },
            { id: "kal-r2", address: "No 16, Sinha Patumaga", weight: "11 kg" },
            { id: "kal-r3", address: "No 23, Janatha Mawatha", weight: "17 kg" },
            { id: "kal-r4", address: "No 32, Deweni Patumaga", weight: "9 kg" },
        ]
    },
    "Kas/2026/6": {
        name: "Kasuni Silva",
        postal: "10800", zone: "Rajagiriya QA Zone", routes: [
            { id: "kas-r1", address: "79/6, Lgxa Road", weight: "16 kg" },
            { id: "kas-r2", address: "81/2, Lgxa Road", weight: "13 kg" },
            { id: "kas-r3", address: "60/2, Divya Road", weight: "19 kg" },
            { id: "kas-r4", address: "60/8, Divya Road", weight: "10 kg" },
        ]
    },
    "Pri/2026/4": {
        name: "Priyantha Jayasuriya",
        postal: "11000", zone: "Wellawatta QA Zone", routes: [
            { id: "pri-r1", address: "No 23, Mukandiram Road", weight: "8 kg" },
            { id: "pri-r2", address: "No 46, Shiva Road", weight: "21 kg" },
            { id: "pri-r3", address: "No 50, Shiva Road", weight: "7 kg" },
            { id: "pri-r4", address: "No 32, Grace Road", weight: "15 kg" },
        ]
    },
};

const collectionDates = {
    "2026-08-16": "August 16, 2026",
    "2026-08-18": "August 18, 2026",
};

function getSelection() {
    try { return JSON.parse(localStorage.getItem('ecolot_collector_selection')); }
    catch { return null; }
}

function getAllRecords() {
    return JSON.parse(localStorage.getItem('ecolot_route_records') || '{}');
}

function saveRouteRecord(routeId, record) {
    const all = getAllRecords();
    if (!all[routeId]) all[routeId] = [];
    all[routeId].push(record);
    localStorage.setItem('ecolot_route_records', JSON.stringify(all));
}

function deleteRouteRecordByIndex(routeId, index) {
    const all = getAllRecords();
    if (!all[routeId]) return;
    all[routeId].splice(index, 1);
    localStorage.setItem('ecolot_route_records', JSON.stringify(all));
}

function updateCollectorIdentity() {
    const selection = getSelection();
    const collector = selection ? collectorsData[selection.collectorId] : null;

    const name = collector ? collector.name : 'Collector';
    const idLabel = selection ? selection.collectorId : '—';

    document.querySelectorAll('[data-collector-name]').forEach((el) => { el.textContent = name; });
    document.querySelectorAll('[data-collector-id]').forEach((el) => { el.textContent = idLabel; });
    document.querySelectorAll('[data-collector-initials]').forEach((el) => {
        el.textContent = name
            .split(' ')
            .map((part) => part[0])
            .join('')
            .slice(0, 2)
            .toUpperCase();
    });
}

function initInitialRequestForm() {
    const collectorSelect = document.getElementById('collectorId');
    if (!collectorSelect) return;

    const postalInput = document.getElementById('postalCode');
    const dateSelect = document.getElementById('collectionDate');
    const submitBtn = document.getElementById('submitRequestBtn');

    Object.keys(collectorsData).forEach((id) => {
        const opt = document.createElement('option');
        opt.value = id;
        opt.textContent = id;
        collectorSelect.appendChild(opt);
    });

    Object.entries(collectionDates).forEach(([value, label]) => {
        const opt = document.createElement('option');
        opt.value = value;
        opt.textContent = label;
        dateSelect.appendChild(opt);
    });

    collectorSelect.addEventListener('change', () => {
        const data = collectorsData[collectorSelect.value];
        postalInput.value = data ? data.postal : '';
    });

    submitBtn.addEventListener('click', () => {
        if (!collectorSelect.value || !dateSelect.value) {
            alert('Please select a Collector ID and a Collection Date.');
            return;
        }
        localStorage.setItem('ecolot_collector_selection', JSON.stringify({
            collectorId: collectorSelect.value,
            postalCode: postalInput.value,
            date: dateSelect.value,
        }));
        window.location.href = window.location.origin + '/EcoLot-LK/public/collector/dashboard';
    });
}

let currentModalRouteId = null;
let currentModalCollector = null;

function renderFilteredRoutes() {
    const container = document.getElementById('routesContainer');
    if (!container) return;

    const selection = getSelection();
    if (!selection || !collectorsData[selection.collectorId]) {
        window.location.href = window.location.origin + '/EcoLot-LK/public/collector/initial-request';
        return;
    }

    const collector = collectorsData[selection.collectorId];
    currentModalCollector = { ...collector, selection };

    const zoneTitle = document.getElementById('zoneTitle');
    const dateLine = document.getElementById('routeDateLine');
    if (zoneTitle) zoneTitle.textContent = collector.zone;
    if (dateLine) dateLine.textContent = 'Assigned routes for ' + (collectionDates[selection.date] || selection.date) + ' · Collector ID: ' + selection.collectorId;

    container.innerHTML = collector.routes.map((route) => `
        <article class="assigned-route-card" data-route-id="${route.id}" data-route-address="${route.address}" data-route-weight="${route.weight}">
            <div class="route-summary">
                <div class="route-title-row">
                    <h2>${route.address}</h2>
                    <span class="hazard-indicator" data-hazard-indicator="${route.id}" aria-label="Hazard flagged">!</span>
                </div>
                <p>${collector.zone}</p>
            </div>
            <div class="route-actions">
                <button type="button" class="primary-btn" data-confirm-pickup="${route.id}">Confirm Pickup</button>
                <button type="button" class="secondary-btn" data-view-route="${route.id}">View Details</button>
            </div>
        </article>
    `).join('');

    attachRouteCardListeners();
    renderCollectorState();
}

function attachRouteCardListeners() {
    document.querySelectorAll('[data-confirm-pickup]').forEach((button) => {
        button.addEventListener('click', () => {
            updatePickup(button.dataset.confirmPickup, true);
            renderCollectorState();
        });
    });
    document.querySelectorAll('[data-view-route]').forEach((button) => {
        button.addEventListener('click', () => openRouteDetailsExpanded(button.dataset.viewRoute));
    });
}

function openRouteDetailsExpanded(id) {
    const routeCard = document.querySelector(`[data-route-id="${id}"]`);
    if (!routeCard || !currentModalCollector) return;

    currentModalRouteId = id;
    selectedRouteId = id;

    const route = currentModalCollector.routes.find((r) => r.id === id);
    document.querySelector('[data-route-zone]').textContent = route.address;
    document.querySelector('[data-modal-collector-id]').textContent = currentModalCollector.selection.collectorId;
    document.querySelector('[data-modal-postal]').textContent = currentModalCollector.selection.postalCode;
    document.querySelector('[data-modal-zone-name]').textContent = currentModalCollector.zone;
    document.querySelector('[data-route-modal-weight-label]').textContent = route.address;
    document.querySelector('[data-route-modal-weight]').textContent = route.weight;
    document.getElementById('weightUpdateInput').value = '';

    renderSavedRecordsTable(id, route.address);
    setRouteModalOpen(true);
}

function renderSavedRecordsTable(routeId, address) {
    const all = getAllRecords();
    const records = all[routeId] || [];
    const tbody = document.querySelector('[data-saved-records-body]');
    if (!tbody) return;

    tbody.innerHTML = records.length === 0
        ? '<tr><td colspan="4" style="color:#999;">No records saved yet.</td></tr>'
        : records.map((r, index) => `
            <tr>
                <td>${address}</td>
                <td>${r.weight}</td>
                <td>${r.hazard ? 'Hazard' : 'Not a hazard'}</td>
                <td><button type="button" class="delete-record-btn" data-delete-index="${index}">Delete</button></td>
            </tr>
        `).join('');
}

const collectorStorageKeys = {
    hazards: 'ecolot_collector_flags',
    pickups: 'ecolot_collector_pickups',
};

let selectedRouteId = null;

function readCollectorState(key) {
    try {
        return JSON.parse(localStorage.getItem(key) || '{}');
    } catch {
        return {};
    }
}

function writeCollectorState(key, value) {
    localStorage.setItem(key, JSON.stringify(value));
}

function updateHazard(id, isHazardous) {
    const hazards = readCollectorState(collectorStorageKeys.hazards);
    hazards[id] = isHazardous;
    writeCollectorState(collectorStorageKeys.hazards, hazards);
}

function updatePickup(id, isPickedUp) {
    const pickups = readCollectorState(collectorStorageKeys.pickups);
    pickups[id] = isPickedUp;
    writeCollectorState(collectorStorageKeys.pickups, pickups);
}

function renderCollectorState() {
    const hazards = readCollectorState(collectorStorageKeys.hazards);
    const pickups = readCollectorState(collectorStorageKeys.pickups);

    const selection = getSelection();
    const collector = selection ? collectorsData[selection.collectorId] : null;
    const currentRouteIds = collector ? collector.routes.map((r) => r.id) : [];
    const routeStopCount = currentRouteIds.length || 4;

    const completedPickupIds = currentRouteIds.filter((id) => pickups[id]);
    const hazardCountForCollector = currentRouteIds.filter((id) => hazards[id]).length;

    document.querySelectorAll('[data-hazard-indicator]').forEach((indicator) => {
        indicator.classList.toggle('active', Boolean(hazards[indicator.dataset.hazardIndicator]));
    });

    document.querySelectorAll('[data-toggle-hazard]').forEach((button) => {
        button.classList.toggle('active', Boolean(hazards[button.dataset.toggleHazard]));
    });

    document.querySelectorAll('[data-confirm-pickup]').forEach((button) => {
        const isPickedUp = Boolean(pickups[button.dataset.confirmPickup]);
        button.textContent = isPickedUp ? 'Picked Up' : 'Confirm Pickup';
        button.disabled = isPickedUp;
    });

    const pickedUpCount = document.querySelector('[data-picked-up-count]');
    const pendingCount = document.querySelector('[data-pending-count]');
    const hazardCount = document.querySelector('[data-hazard-count]');
    const progress = document.querySelector('[data-pickup-progress]');

    if (pickedUpCount) pickedUpCount.textContent = completedPickupIds.length;
    if (pendingCount) pendingCount.textContent = Math.max(routeStopCount - completedPickupIds.length, 0);
    if (hazardCount) hazardCount.textContent = hazardCountForCollector;
    if (progress) progress.style.width = `${(completedPickupIds.length / routeStopCount) * 100}%`;
}

function setRouteModalOpen(isOpen) {
    const modal = document.querySelector('[data-route-modal]');
    if (modal) modal.hidden = !isOpen;
}

document.addEventListener('DOMContentLoaded', () => {
    updateCollectorIdentity();
    initInitialRequestForm();
    renderFilteredRoutes();
    renderQuickStatus();
    initRequestNotes();

    document.querySelector('[data-save-record]')?.addEventListener('click', () => {
        if (!currentModalRouteId) return;
        const weightValue = document.getElementById('weightUpdateInput').value.trim();
        if (!weightValue) { alert('Please enter a weight before saving.'); return; }

        const hazards = readCollectorState(collectorStorageKeys.hazards);
        saveRouteRecord(currentModalRouteId, { weight: weightValue, hazard: Boolean(hazards[currentModalRouteId]) });
        document.getElementById('weightUpdateInput').value = '';

        const route = currentModalCollector.routes.find((r) => r.id === currentModalRouteId);
        renderSavedRecordsTable(currentModalRouteId, route.address);
        alert('Record saved.');
    });

    document.querySelector('[data-saved-records-body]')?.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-delete-index]');
        if (!btn || !currentModalRouteId) return;

        const index = Number(btn.dataset.deleteIndex);
        deleteRouteRecordByIndex(currentModalRouteId, index);

        const route = currentModalCollector.routes.find((r) => r.id === currentModalRouteId);
        renderSavedRecordsTable(currentModalRouteId, route.address);
    });

    renderCollectorState();

    document.querySelector('[data-close-route-modal]')?.addEventListener('click', () => {
        setRouteModalOpen(false);
    });

    document.querySelector('[data-route-modal]')?.addEventListener('click', (event) => {
        if (event.target === event.currentTarget) setRouteModalOpen(false);
    });

    document.querySelector('[data-mark-hazard]')?.addEventListener('click', () => {
        if (!selectedRouteId) return;
        updateHazard(selectedRouteId, true);
        renderCollectorState();
        setRouteModalOpen(false);
    });

    document.querySelector('[data-toggle-filter]')?.addEventListener('click', () => {
        const panel = document.querySelector('[data-filter-panel]');
        if (panel) panel.hidden = !panel.hidden;
    });

    document.querySelector('[data-request-filter]')?.addEventListener('change', (event) => {
        const hazards = readCollectorState(collectorStorageKeys.hazards);
        const pickups = readCollectorState(collectorStorageKeys.pickups);

        document.querySelectorAll('[data-request-row]').forEach((row) => {
            const id = row.dataset.requestId;
            const shouldShow = event.target.value === 'all'
                || (event.target.value === 'flagged' && hazards[id])
                || (event.target.value === 'pending' && !pickups[id]);
            row.hidden = !shouldShow;
        });
    });

    document.querySelector('[data-daily-report]')?.addEventListener('click', () => window.print());
});

function renderQuickStatus() {
    const container = document.getElementById('quickStatusContainer');
    if (!container) return;

    const selection = getSelection();
    if (!selection || !collectorsData[selection.collectorId]) {
        window.location.href = window.location.origin + '/EcoLot-LK/public/collector/initial-request';
        return;
    }
    const collector = collectorsData[selection.collectorId];

    const sublineEl = document.getElementById('requestsSubline');
    if (sublineEl) sublineEl.textContent = collector.zone + ' · Collector ID: ' + selection.collectorId + ' · ' + (collectionDates[selection.date] || selection.date);

    container.innerHTML = collector.routes.map((route) => `
        <article class="quick-status-row" data-request-id="${route.id}" data-request-row>
            <div><strong>${route.address}</strong><span>${collector.zone}</span></div>
            <div class="quick-status-actions">
                <button type="button" class="small-action-btn" data-send-update="${route.id}">Update</button>
                <button type="button" class="hazard-toggle" data-toggle-hazard="${route.id}" aria-label="Toggle hazard">🚩</button>
                <button type="button" class="clear-hazard" data-clear-hazard="${route.id}" aria-label="Clear hazard">🗑️</button>
            </div>
        </article>
    `).join('');

    document.querySelectorAll('[data-toggle-hazard]').forEach((button) => {
        button.addEventListener('click', () => {
            const id = button.dataset.toggleHazard;
            const hazards = readCollectorState(collectorStorageKeys.hazards);
            updateHazard(id, !hazards[id]);
            renderCollectorState();
        });
    });

    document.querySelectorAll('[data-clear-hazard], [data-send-update]').forEach((button) => {
        button.addEventListener('click', () => {
            const id = button.dataset.clearHazard || button.dataset.sendUpdate;
            updateHazard(id, false);
            renderCollectorState();
            if (button.dataset.sendUpdate) {
                alert('Mistake!! That is not a hazard.');
            }
        });
    });

    renderCollectorState();
}

/* ---------- Request Notes ---------- */

function getSavedNote() {
    try { return JSON.parse(localStorage.getItem('ecolot_general_note') || 'null'); }
    catch { return null; }
}

function saveGeneralNote(text) {
    localStorage.setItem('ecolot_general_note', JSON.stringify({ text }));
}

function deleteGeneralNote() {
    localStorage.removeItem('ecolot_general_note');
}

function loadGeneralNote() {
    const note = getSavedNote();

    const textarea = document.querySelector('[data-note-text]');
    const editBtn = document.querySelector('[data-edit-note]');
    const deleteBtn = document.querySelector('[data-delete-note]');
    const saveBtn = document.querySelector('[data-save-note]');
    if (!textarea || !editBtn || !deleteBtn || !saveBtn) return;

    if (note && note.text) {
        textarea.value = note.text;
        textarea.readOnly = true;
        editBtn.hidden = false;
        deleteBtn.hidden = false;
        saveBtn.hidden = true;
    } else {
        textarea.value = '';
        textarea.readOnly = false;
        editBtn.hidden = true;
        deleteBtn.hidden = true;
        saveBtn.hidden = false;
        saveBtn.textContent = 'Save Note';
    }
}

function commitNote() {
    const textArea = document.querySelector('[data-note-text]');
    const text = textArea.value.trim();
    if (!text) { alert('Please write a note before saving.'); return; }

    saveGeneralNote(text);
    loadGeneralNote();
}

function initRequestNotes() {
    const textarea = document.querySelector('[data-note-text]');
    if (!textarea) return;

    loadGeneralNote();

    document.querySelector('[data-save-note]')?.addEventListener('click', () => {
        commitNote();
    });

    textarea.addEventListener('keydown', (event) => {
        if (textarea.readOnly) return;
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            commitNote();
        }
    });

    document.querySelector('[data-edit-note]')?.addEventListener('click', () => {
        const editBtn = document.querySelector('[data-edit-note]');
        const deleteBtn = document.querySelector('[data-delete-note]');
        const saveBtn = document.querySelector('[data-save-note]');

        textarea.readOnly = false;
        textarea.focus();
        editBtn.hidden = true;
        deleteBtn.hidden = true;
        saveBtn.hidden = false;
        saveBtn.textContent = 'Update Note';
    });

    document.querySelector('[data-delete-note]')?.addEventListener('click', () => {
        deleteGeneralNote();
        loadGeneralNote();
    });
}
