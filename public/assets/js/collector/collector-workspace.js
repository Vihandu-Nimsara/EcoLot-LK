const collectorsData = {
    "Ram/2025/6": {
        name: "Ramesh Fernando",
        postal: "10500", zone: "Kollupitiya QA Zone", routes: [
            { id: "ram-r1", address: "No 23, Alfred Place, Kollupitiya", weight: "18 kg" },
            { id: "ram-r2", address: "No 45, Sir Ernest De Silva Mawatha, Kollupitiya", weight: "15 kg" },
            { id: "ram-r3", address: "No 12, Justice Akbar Mawatha, Kollupitiya", weight: "12 kg" },
            { id: "ram-r4", address: "No 67, Galle Road, Kollupitiya", weight: "20 kg" },
        ]
    },
    "Kal/2024/6": {
        name: "Kasun Perera",
        postal: "10600", zone: "Narahenpita QA Zone", routes: [
            { id: "kal-r1", address: "No 18, Elvitigala Mawatha, Narahenpita", weight: "14 kg" },
            { id: "kal-r2", address: "No 34, Kirula Road, Narahenpita", weight: "11 kg" },
            { id: "kal-r3", address: "No 9, Poorwarama Road, Narahenpita", weight: "17 kg" },
            { id: "kal-r4", address: "No 52, Nawala Road, Narahenpita", weight: "9 kg" },
        ]
    },
    "Kas/2026/6": {
        name: "Kasuni Silva",
        postal: "10800", zone: "Rajagiriya QA Zone", routes: [
            { id: "kas-r1", address: "No 79/6, Vincent Joseph Mawatha, Rajagiriya", weight: "16 kg" },
            { id: "kas-r2", address: "No 22, Nawala Road, Rajagiriya", weight: "13 kg" },
            { id: "kas-r3", address: "No 60/2, Koswatte Road, Rajagiriya", weight: "19 kg" },
            { id: "kas-r4", address: "No 15, Kotte Road, Rajagiriya", weight: "10 kg" },
        ]
    },
    "Pri/2026/4": {
        name: "Priyantha Jayasuriya",
        postal: "11000", zone: "Wellawatta QA Zone", routes: [
            { id: "pri-r1", address: "No 46, Galle Road, Wellawatta", weight: "8 kg" },
            { id: "pri-r2", address: "No 23, Station Road, Wellawatta", weight: "21 kg" },
            { id: "pri-r3", address: "No 32, W.A. Silva Mawatha, Wellawatta", weight: "7 kg" },
            { id: "pri-r4", address: "No 50, Milward Mawatha, Wellawatta", weight: "15 kg" },
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
document.addEventListener('DOMContentLoaded', () => {
    updateCollectorIdentity();
    initInitialRequestForm();
    initAssignedRoutesScheduleFlow();
    initElotsPage();
});

/* ==================== Assigned Routes: Schedule → Requests → Records CRUD Flow ==================== */

// Allowed item categories (used by the mock request data below).
const ITEM_CATEGORIES = [
    'Computers & IT',
    'Laptops',
    'Mobile & Communication',
    'Home Appliances',
    'Consumer Electronics',
    'Batteries & Accessories',
];
const scheduleData = [
    {
        id: 'SCH-001', zone: 'Kollupitiya', date: 'May 20, 2025', time: '8:00 AM – 12:00 PM',
        requests: [
            { id: 'REQ-0001', address: 'No 23, Alfred Place, Kollupitiya', categories: ['Consumer Electronics'], quantity: '5 pcs' },
            { id: 'REQ-0002', address: 'No 45, Sir Ernest De Silva Mawatha, Kollupitiya', categories: ['Laptops', 'Batteries & Accessories'], quantity: '10 pcs' },
            { id: 'REQ-0003', address: 'No 12, Justice Akbar Mawatha, Kollupitiya', categories: ['Home Appliances'], quantity: '8 pcs' },
            { id: 'REQ-0004', address: 'No 67, Galle Road, Kollupitiya', categories: ['Mobile & Communication'], quantity: '3 pcs' },
        ]
    },
    {
        id: 'SCH-002', zone: 'Narahenpita', date: 'May 21, 2025', time: '8:00 AM – 12:00 PM',
        requests: [
            { id: 'REQ-0005', address: 'No 18, Elvitigala Mawatha, Narahenpita', categories: ['Computers & IT'], quantity: '6 pcs' },
            { id: 'REQ-0006', address: 'No 34, Kirula Road, Narahenpita', categories: ['Consumer Electronics'], quantity: '4 pcs' },
            { id: 'REQ-0007', address: 'No 9, Poorwarama Road, Narahenpita', categories: ['Batteries & Accessories'], quantity: '12 pcs' },
            { id: 'REQ-0008', address: 'No 52, Nawala Road, Narahenpita', categories: ['Home Appliances'], quantity: '2 pcs' },
        ]
    },
    {
        id: 'SCH-003', zone: 'Rajagiriya', date: 'May 22, 2025', time: '8:00 AM – 12:00 PM',
        requests: [
            { id: 'REQ-0009', address: 'No 79/6, Vincent Joseph Mawatha, Rajagiriya', categories: ['Laptops'], quantity: '7 pcs' },
            { id: 'REQ-0010', address: 'No 22, Nawala Road, Rajagiriya', categories: ['Mobile & Communication'], quantity: '9 pcs' },
            { id: 'REQ-0011', address: 'No 60/2, Koswatte Road, Rajagiriya', categories: ['Computers & IT'], quantity: '5 pcs' },
            { id: 'REQ-0012', address: 'No 15, Kotte Road, Rajagiriya', categories: ['Consumer Electronics'], quantity: '3 pcs' },
        ]
    },
    {
        id: 'SCH-004', zone: 'Wellawatta', date: 'May 23, 2025', time: '8:00 AM – 12:00 PM',
        requests: [
            { id: 'REQ-0013', address: 'No 46, Galle Road, Wellawatta', categories: ['Consumer Electronics'], quantity: '8 pcs' },
            { id: 'REQ-0014', address: 'No 23, Station Road, Wellawatta', categories: ['Home Appliances'], quantity: '7 pcs' },
            { id: 'REQ-0015', address: 'No 32, W.A. Silva Mawatha, Wellawatta', categories: ['Laptops', 'Batteries & Accessories'], quantity: '15 pcs' },
            { id: 'REQ-0016', address: 'No 50, Milward Mawatha, Wellawatta', categories: ['Mobile & Communication'], quantity: '4 pcs' },
        ]
    },
];
let activeScheduleId = null;
let activeRequestId = null;
let activeModalMode = 'edit'; // 'edit' | 'view'
function findSchedule(id) {
    return scheduleData.find((s) => s.id === id);
}
function findRequest(scheduleId, requestId) {
    const sched = findSchedule(scheduleId);
    return sched ? sched.requests.find((r) => r.id === requestId) : null;
}
function getCollectionRecords() {
    return JSON.parse(localStorage.getItem('ecolot_collection_records') || '{}');
}
function saveCollectionRecords(records) {
    localStorage.setItem('ecolot_collection_records', JSON.stringify(records));
}
function renderScheduleCards() {
    const grid = document.querySelector('[data-schedule-grid]');
    if (!grid) return;
    grid.innerHTML = scheduleData.map((sched) => `
        <article class="schedule-card">
            <h3>Schedule #${sched.id}</h3>
            <p class="schedule-zone">${sched.zone}</p>
            <p class="schedule-meta">📅 ${sched.date}</p>
            <p class="schedule-meta">🕐 ${sched.time}</p>
            <span class="schedule-badge">Assigned</span>
            <button type="button" class="primary-btn full-width" data-open-schedule="${sched.id}">View Requests</button>
        </article>
    `).join('');
    grid.querySelectorAll('[data-open-schedule]').forEach((btn) => {
        btn.addEventListener('click', () => openScheduleRequests(btn.dataset.openSchedule));
    });
}
function openScheduleRequests(scheduleId) {
    activeScheduleId = scheduleId;
    const sched = findSchedule(scheduleId);
    if (!sched) return;
    document.querySelector('[data-view="schedules"]').hidden = true;
    document.querySelector('[data-view="requests"]').hidden = false;
    document.querySelector('[data-schedule-title]').textContent = `Schedule #${sched.id} · ${sched.zone}`;
    document.querySelector('[data-schedule-subline]').textContent = `${sched.date} · ${sched.time}`;
    renderScheduleWorkspace();
}
function renderScheduleWorkspace() {
    renderRequestsTable();
    renderDraftRecords();
    renderSubmittedRecords();
    updateScheduleSummary();
    updateVerificationCard();
    updateZoneVerifiedPool();
}
function renderCategoryPills(categories) {
    return categories.map((cat) => `<span class="category-pill">${cat}</span>`).join('');
}
function renderRequestsTable() {
    const sched = findSchedule(activeScheduleId);
    if (!sched) return;
    const tbody = document.querySelector('[data-requests-body]');
    const records = getCollectionRecords();
    tbody.innerHTML = sched.requests.map((req) => {
        const record = records[req.id];
        const status = record ? record.status : 'pending';
        const verifiedTag = (status === 'submitted' && record.verified) ? '<span class="verified-check">✔ Verified</span>' : '';
        return `
            <tr>
                <td>${req.id}</td>
                <td>${req.address}</td>
                <td>${renderCategoryPills(req.categories)}</td>
                <td>${req.quantity}</td>
                <td><span class="status-badge status-${status}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>${verifiedTag}</td>
                <td class="action-cell"><button type="button" class="icon-action-btn" data-view-record="${req.id}" title="View / fill collection record">👁️</button></td>
            </tr>
        `;
    }).join('');
    tbody.querySelectorAll('[data-view-record]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const requestId = btn.dataset.viewRecord;
            const records = getCollectionRecords();
            const status = records[requestId]?.status;
            openRecordModal(requestId, status === 'submitted' ? 'view' : 'edit');
        });
    });
}
function renderDraftRecords() {
    const sched = findSchedule(activeScheduleId);
    if (!sched) return;
    const records = getCollectionRecords();
    const listEl = document.querySelector('[data-draft-records-list]');
    const draftRequests = sched.requests.filter((req) => records[req.id]?.status === 'draft');
    listEl.innerHTML = draftRequests.length === 0
        ? '<p class="record-list-empty">No draft records yet. Fill a request from the table above to start one.</p>'
        : draftRequests.map((req) => {
            const record = records[req.id];
            return `
                <div class="record-card">
                    <div class="record-card-main">
                        <strong>${req.id} · ${req.address}</strong>
                        <p>${record.actualQuantity} pcs · ${record.totalWeight} kg · ${record.actualCondition}</p>
                    </div>
                    <div class="record-card-actions">
                        <button type="button" class="small-action-btn" data-edit-draft="${req.id}">Edit</button>
                        <button type="button" class="submit-record-btn" data-submit-draft="${req.id}">Submit</button>
                    </div>
                </div>
            `;
        }).join('');
    listEl.querySelectorAll('[data-edit-draft]').forEach((btn) => {
        btn.addEventListener('click', () => openRecordModal(btn.dataset.editDraft, 'edit'));
    });
    listEl.querySelectorAll('[data-submit-draft]').forEach((btn) => {
        btn.addEventListener('click', () => submitRecord(btn.dataset.submitDraft));
    });
}
function renderSubmittedRecords() {
    const sched = findSchedule(activeScheduleId);
    if (!sched) return;
    const records = getCollectionRecords();
    const listEl = document.querySelector('[data-submitted-records-list]');
    const submittedRequests = sched.requests.filter((req) => records[req.id]?.status === 'submitted');
    listEl.innerHTML = submittedRequests.length === 0
        ? '<p class="record-list-empty">No submitted records yet.</p>'
        : submittedRequests.map((req) => {
            const record = records[req.id];
            const verifiedTag = record.verified ? '<span class="verified-check">✔ Verified</span>' : '';
            return `
                <div class="record-card">
                    <div class="record-card-main">
                        <strong>${req.id} · ${req.address} ${verifiedTag}</strong>
                        <p>${record.actualQuantity} pcs · ${record.totalWeight} kg · ${record.actualCondition}</p>
                    </div>
                    <div class="record-card-actions">
                        <button type="button" class="icon-action-btn" data-view-submitted="${req.id}" title="View">👁️</button>
                        <button type="button" class="delete-record-btn" data-delete-submitted="${req.id}" title="Move back to draft">Delete</button>
                    </div>
                </div>
            `;
        }).join('');
    listEl.querySelectorAll('[data-view-submitted]').forEach((btn) => {
        btn.addEventListener('click', () => openRecordModal(btn.dataset.viewSubmitted, 'view'));
    });
    listEl.querySelectorAll('[data-delete-submitted]').forEach((btn) => {
        btn.addEventListener('click', () => deleteRecord(btn.dataset.deleteSubmitted));
    });
}
function openRecordModal(requestId, mode) {
    activeRequestId = requestId;
    activeModalMode = mode || 'edit';
    const req = findRequest(activeScheduleId, requestId);
    if (!req) return;
    const records = getCollectionRecords();
    const record = records[requestId] || {};
    document.querySelector('[data-record-request-id]').textContent = req.id;
    document.querySelector('[data-record-address]').textContent = req.address;
    document.querySelector('[data-record-categories]').textContent = req.categories.join(', ');
    document.querySelector('[data-record-req-qty]').textContent = req.quantity;
    const qtyInput = document.querySelector('[data-record-actual-qty]');
    const weightInput = document.querySelector('[data-record-actual-weight]');
    const conditionSelect = document.querySelector('[data-record-condition]');
    const itemNoteInput = document.querySelector('[data-record-item-note]');
    const collectionNoteInput = document.querySelector('[data-record-collection-note]');
    const actionsRow = document.querySelector('[data-record-modal-actions]');
    qtyInput.value = record.actualQuantity || '';
    weightInput.value = record.actualWeight || '';
    conditionSelect.value = record.actualCondition || '';
    itemNoteInput.value = record.itemNote || '';
    collectionNoteInput.value = record.collectionNote || '';
    updateTotalWeightDisplay();
    const isViewOnly = activeModalMode === 'view';
    [qtyInput, weightInput, conditionSelect, itemNoteInput, collectionNoteInput].forEach((el) => {
        el.disabled = isViewOnly;
    });
    actionsRow.hidden = isViewOnly;
    document.querySelector('[data-record-modal]').hidden = false;
}
function updateTotalWeightDisplay() {
    const weight = parseFloat(document.querySelector('[data-record-actual-weight]').value) || 0;
    document.querySelector('[data-record-total-weight]').textContent = weight.toFixed(2) + ' kg';
}
function closeRecordModal() {
    document.querySelector('[data-record-modal]').hidden = true;
    activeRequestId = null;
}
function saveDraftRecord() {
    if (!activeRequestId) return;
    const actualQuantity = document.querySelector('[data-record-actual-qty]').value.trim();
    const actualWeight = document.querySelector('[data-record-actual-weight]').value.trim();
    const actualCondition = document.querySelector('[data-record-condition]').value;
    const itemNote = document.querySelector('[data-record-item-note]').value.trim();
    const collectionNote = document.querySelector('[data-record-collection-note]').value.trim();
    if (!actualQuantity || !actualWeight || !actualCondition || !collectionNote) {
        alert('Please fill in Actual Quantity, Actual Weight, Actual Condition, and Collection Note.');
        return;
    }
    const records = getCollectionRecords();
    records[activeRequestId] = {
        scheduleId: activeScheduleId,
        status: 'draft',
        actualQuantity, actualWeight, actualCondition, itemNote, collectionNote,
        totalWeight: parseFloat(actualWeight).toFixed(2),
        verified: false,
    };
    saveCollectionRecords(records);
    closeRecordModal();
    renderScheduleWorkspace();
}
function submitRecord(requestId) {
    const records = getCollectionRecords();
    if (!records[requestId]) return;
    records[requestId].status = 'submitted';
    records[requestId].verified = Math.random() < 0.75;
    saveCollectionRecords(records);
    renderScheduleWorkspace();
}
function deleteRecord(requestId) {
    if (!confirm('Move this submitted record back to draft? You can review and re-submit it from the Draft section.')) return;
    const records = getCollectionRecords();
    if (!records[requestId]) return;
    records[requestId].status = 'draft';
    records[requestId].verified = false;
    saveCollectionRecords(records);
    renderScheduleWorkspace();
}
function updateScheduleSummary() {
    const sched = findSchedule(activeScheduleId);
    if (!sched) return;
    const records = getCollectionRecords();
    let submitted = 0, draft = 0, pending = 0;
    sched.requests.forEach((req) => {
        const status = records[req.id]?.status || 'pending';
        if (status === 'submitted') submitted++;
        else if (status === 'draft') draft++;
        else if (status === 'pending') pending++;
    });
    document.querySelector('[data-sum-total]').textContent = sched.requests.length;
    document.querySelector('[data-sum-submitted]').textContent = submitted;
    document.querySelector('[data-sum-draft]').textContent = draft;
    document.querySelector('[data-sum-pending]').textContent = pending;
}
function updateVerificationCard() {
    const sched = findSchedule(activeScheduleId);
    if (!sched) return;
    const records = getCollectionRecords();
    const submittedEntries = sched.requests
        .map((req) => records[req.id])
        .filter((record) => record?.status === 'submitted');
    const card = document.querySelector('[data-verification-card]');
    if (submittedEntries.length === 0) {
        card.hidden = true;
        return;
    }
    const verifiedCount = submittedEntries.filter((record) => record.verified).length;
    card.hidden = false;
    document.querySelector('[data-verified-count]').textContent = verifiedCount;
    document.querySelector('[data-submitted-count-label]').textContent = submittedEntries.length;
}
function updateZoneVerifiedPool() {
    const sched = findSchedule(activeScheduleId);
    if (!sched) return;
    const records = getCollectionRecords();
    let totalQuantity = 0;
    let totalWeight = 0;
    const categorySet = new Set();
    sched.requests.forEach((req) => {
        const record = records[req.id];
        if (record?.status === 'submitted' && record.verified) {
            totalQuantity += parseInt(record.actualQuantity, 10) || 0;
            totalWeight += parseFloat(record.totalWeight) || 0;
            req.categories.forEach((cat) => categorySet.add(cat));
        }
    });
    document.querySelector('[data-pool-total-quantity]').textContent = totalQuantity;
    document.querySelector('[data-pool-total-weight]').textContent = totalWeight.toFixed(2) + ' kg';
    document.querySelector('[data-pool-categories]').textContent = categorySet.size;
}
function initAssignedRoutesScheduleFlow() {
    const grid = document.querySelector('[data-schedule-grid]');
    if (!grid) return;
    renderScheduleCards();
    document.querySelector('[data-back-to-schedules]')?.addEventListener('click', () => {
        document.querySelector('[data-view="requests"]').hidden = true;
        document.querySelector('[data-view="schedules"]').hidden = false;
        activeScheduleId = null;
    });
    document.querySelector('[data-close-record-modal]')?.addEventListener('click', closeRecordModal);
    document.querySelector('[data-record-modal]')?.addEventListener('click', (event) => {
        if (event.target === event.currentTarget) closeRecordModal();
    });
    document.querySelector('[data-record-actual-weight]')?.addEventListener('input', updateTotalWeightDisplay);
    document.querySelector('[data-save-draft-record]')?.addEventListener('click', saveDraftRecord);
    document.querySelector('[data-scroll-to-draft]')?.addEventListener('click', () => {
        document.querySelector('[data-draft-section]')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    document.querySelector('[data-go-to-elots]')?.addEventListener('click', () => {
        window.location.href = window.location.origin + '/EcoLot-LK/public/collector/my-elots';
    });
}

/* ==================== My E-Lots CRUD flow ==================== */

function buildRequestCatalog() {
    const catalog = {};
    scheduleData.forEach((sched) => {
        sched.requests.forEach((req) => {
            catalog[req.id] = {
                requestId: req.id,
                address: req.address,
                categories: req.categories,
                zone: sched.zone,
                requestedQuantity: req.quantity,
            };
        });
    });
    return catalog;
}
const REQUEST_CATALOG = buildRequestCatalog();
const ELOTS_KEY = 'ecolot_my_elots';

function getElots() {
    try { return JSON.parse(localStorage.getItem(ELOTS_KEY) || '{}'); }
    catch { return {}; }
}
function saveElots(elots) {
    localStorage.setItem(ELOTS_KEY, JSON.stringify(elots));
}

let activeElotMode = 'create';
let activeElotId = null;

function getUsedElotItemIds(elots, excludeElotId) {
    const used = new Set();
    Object.values(elots).forEach((elot) => {
        if (excludeElotId && elot.id === excludeElotId) return;
        (elot.itemIds || []).forEach((id) => used.add(id));
    });
    return used;
}
function resolveElotItem(requestId) {
    const catalog = REQUEST_CATALOG[requestId];
    const record = getCollectionRecords()[requestId];
    if (!catalog || !record) return null;
    return {
        requestId,
        address: catalog.address,
        categories: catalog.categories,
        zone: catalog.zone,
        actualQuantity: parseInt(record.actualQuantity, 10) || 0,
        totalWeight: parseFloat(record.totalWeight) || 0,
        verified: !!record.verified,
        status: record.status,
    };
}
function computeVerifiedElotPool(excludeElotId) {
    const records = getCollectionRecords();
    const used = getUsedElotItemIds(getElots(), excludeElotId);
    return Object.keys(REQUEST_CATALOG)
        .filter((requestId) => {
            const record = records[requestId];
            return record && record.status === 'submitted' && record.verified === true && !used.has(requestId);
        })
        .map(resolveElotItem)
        .filter(Boolean)
        .sort((a, b) => a.requestId.localeCompare(b.requestId));
}
function generateElotId(elots) {
    const nums = Object.keys(elots)
        .map((id) => parseInt(id.split('-')[1], 10))
        .filter((n) => !Number.isNaN(n));
    const next = (nums.length ? Math.max(...nums) : 0) + 1;
    return 'EL-' + String(next).padStart(4, '0');
}

function renderVerifiedPoolTable() {
    const poolBody = document.querySelector('[data-verified-pool-body]');
    if (!poolBody) return;
    const pool = computeVerifiedElotPool();
    poolBody.innerHTML = pool.map((item) => `
        <tr>
            <td>${item.requestId}</td>
            <td>${item.actualQuantity} pcs</td>
            <td>${item.totalWeight.toFixed(2)} kg</td>
            <td>${item.categories.join(', ')}</td>
            <td><span class="status-badge status-submitted">Verified</span></td>
        </tr>
    `).join('');
    const poolEmpty = document.querySelector('[data-verified-pool-empty]');
    if (poolEmpty) poolEmpty.hidden = pool.length !== 0;
}
function renderDraftElotsTable() {
    const draftBody = document.querySelector('[data-draft-elots-body]');
    if (!draftBody) return;
    const elots = getElots();
    const drafts = Object.values(elots)
        .filter((elot) => elot.status === 'draft')
        .sort((a, b) => a.id.localeCompare(b.id));
    draftBody.innerHTML = drafts.map((elot) => `
        <tr>
            <td>${elot.id}</td>
            <td>${elot.name}</td>
            <td>${elot.category}</td>
            <td>${elot.totalWeight.toFixed(2)} kg</td>
            <td><span class="status-badge status-draft">Draft</span></td>
            <td class="action-cell">
                <button type="button" class="small-action-btn" data-edit-elot="${elot.id}">Edit</button>
                <button type="button" class="submit-record-btn" data-submit-elot="${elot.id}">Submit</button>
            </td>
        </tr>
    `).join('');
    const draftEmpty = document.querySelector('[data-draft-elots-empty]');
    if (draftEmpty) draftEmpty.hidden = drafts.length !== 0;

    draftBody.querySelectorAll('[data-edit-elot]').forEach((btn) => {
        btn.addEventListener('click', () => openElotModal('edit', btn.dataset.editElot));
    });
    draftBody.querySelectorAll('[data-submit-elot]').forEach((btn) => {
        btn.addEventListener('click', () => submitElot(btn.dataset.submitElot));
    });
}
function renderSubmittedElotsTable() {
    const submittedBody = document.querySelector('[data-submitted-elots-body]');
    if (!submittedBody) return;
    const elots = getElots();
    const submitted = Object.values(elots)
        .filter((elot) => elot.status === 'submitted')
        .sort((a, b) => a.id.localeCompare(b.id));
    submittedBody.innerHTML = submitted.map((elot) => {
        const verifiedTag = elot.verified
            ? '<span class="verified-check">✔ Verified</span>'
            : '<span class="not-verified-check">✖ Not Verified</span>';
        return `
            <tr>
                <td>${elot.id}</td>
                <td>${elot.name}</td>
                <td>${elot.category}</td>
                <td>${elot.totalWeight.toFixed(2)} kg</td>
                <td><span class="status-badge status-submitted">Submitted</span>${verifiedTag}</td>
                <td class="action-cell">
                    <button type="button" class="icon-action-btn" data-view-elot="${elot.id}" title="View">👁️</button>
                    <button type="button" class="delete-record-btn" data-delete-elot="${elot.id}" title="Move back to draft">Delete</button>
                </td>
            </tr>
        `;
    }).join('');
    const submittedEmpty = document.querySelector('[data-submitted-elots-empty]');
    if (submittedEmpty) submittedEmpty.hidden = submitted.length !== 0;

    submittedBody.querySelectorAll('[data-view-elot]').forEach((btn) => {
        btn.addEventListener('click', () => openElotModal('view', btn.dataset.viewElot));
    });
    submittedBody.querySelectorAll('[data-delete-elot]').forEach((btn) => {
        btn.addEventListener('click', () => deleteElot(btn.dataset.deleteElot));
    });
}
function updateElotsSummary() {
    if (!document.querySelector('[data-sum-total-elots]')) return;
    const elots = Object.values(getElots());
    const draftCount = elots.filter((e) => e.status === 'draft').length;
    const submittedCount = elots.filter((e) => e.status === 'submitted').length;
    document.querySelector('[data-sum-total-elots]').textContent = elots.length;
    document.querySelector('[data-sum-draft-elots]').textContent = draftCount;
    document.querySelector('[data-sum-submitted-elots]').textContent = submittedCount;
    document.querySelector('[data-sum-available-items]').textContent = computeVerifiedElotPool().length;
}
function renderElotsWorkspace() {
    renderVerifiedPoolTable();
    renderDraftElotsTable();
    renderSubmittedElotsTable();
    updateElotsSummary();
}

function renderElotItemChecklist(availableItems, checkedIds, disabled) {
    const itemOptions = document.querySelector('[data-elot-item-options]');
    const itemEmpty = document.querySelector('[data-elot-item-empty]');
    if (!itemOptions) return;
    if (availableItems.length === 0) {
        itemOptions.innerHTML = '';
        if (itemEmpty) itemEmpty.hidden = false;
        return;
    }
    if (itemEmpty) itemEmpty.hidden = true;
    itemOptions.innerHTML = availableItems.map((item) => `
        <label class="verified-item">
            <input type="checkbox" data-item-checkbox value="${item.requestId}"
                data-weight="${item.totalWeight}"
                ${checkedIds.has(item.requestId) ? 'checked' : ''}
                ${disabled ? 'disabled' : ''}>
            <span class="verified-item-info">
                <strong>${item.requestId} · ${item.address}</strong>
                <span>${item.categories.join(', ')} — ${item.actualQuantity} pcs / ${item.totalWeight.toFixed(2)} kg</span>
            </span>
        </label>
    `).join('');
    if (!disabled) {
        itemOptions.querySelectorAll('[data-item-checkbox]').forEach((cb) => {
            cb.addEventListener('change', updateSelectedElotWeightDisplay);
        });
    }
}
function updateSelectedElotWeightDisplay() {
    const itemOptions = document.querySelector('[data-elot-item-options]');
    const selectedWeightEl = document.querySelector('[data-elot-selected-weight]');
    if (!itemOptions || !selectedWeightEl) return;
    let total = 0;
    itemOptions.querySelectorAll('[data-item-checkbox]:checked').forEach((cb) => {
        total += parseFloat(cb.dataset.weight) || 0;
    });
    selectedWeightEl.textContent = total.toFixed(2) + ' kg';
}
function openElotModal(mode, elotId) {
    activeElotMode = mode;
    activeElotId = elotId || null;

    const formError = document.querySelector('[data-elot-form-error]');
    if (formError) { formError.hidden = true; formError.textContent = ''; }

    const elot = elotId ? getElots()[elotId] : null;

    const modalTitle = document.querySelector('[data-elot-modal-title]');
    if (modalTitle) {
        modalTitle.textContent = mode === 'create' ? 'Create E-Lot'
            : mode === 'edit' ? 'Edit E-Lot Draft'
                : 'View E-Lot';
    }

    const categoryInput = document.querySelector('[data-elot-category]');
    const nameInput = document.querySelector('[data-elot-name]');
    const descriptionInput = document.querySelector('[data-elot-description]');
    categoryInput.value = elot ? elot.category : '';
    nameInput.value = elot ? elot.name : '';
    descriptionInput.value = elot ? (elot.description || '') : '';

    const pool = computeVerifiedElotPool(elotId || null);
    const ownItems = elot ? elot.itemIds.map(resolveElotItem).filter(Boolean) : [];
    const combined = [...pool];
    ownItems.forEach((item) => {
        if (!combined.some((i) => i.requestId === item.requestId)) combined.push(item);
    });
    combined.sort((a, b) => a.requestId.localeCompare(b.requestId));

    const checkedIds = new Set(elot ? elot.itemIds : []);
    const isView = mode === 'view';

    renderElotItemChecklist(combined, checkedIds, isView);
    updateSelectedElotWeightDisplay();

    [categoryInput, nameInput, descriptionInput].forEach((el) => { el.disabled = isView; });
    const actionsRow = document.querySelector('[data-elot-modal-actions]');
    if (actionsRow) actionsRow.hidden = isView;
    const saveBtn = document.querySelector('[data-save-elot-draft]');
    if (saveBtn) saveBtn.textContent = mode === 'edit' ? 'Save Draft' : 'Create E-Lot (Draft)';

    document.querySelector('[data-elot-modal]').hidden = false;
}
function closeElotModal() {
    document.querySelector('[data-elot-modal]').hidden = true;
    activeElotId = null;
    activeElotMode = 'create';
}
function saveElot() {
    if (activeElotMode === 'view') return;

    const categoryInput = document.querySelector('[data-elot-category]');
    const nameInput = document.querySelector('[data-elot-name]');
    const descriptionInput = document.querySelector('[data-elot-description]');
    const itemOptions = document.querySelector('[data-elot-item-options]');
    const formError = document.querySelector('[data-elot-form-error]');

    const category = categoryInput.value.trim();
    const name = nameInput.value.trim();
    const description = descriptionInput.value.trim();
    const checkedIds = Array.from(itemOptions.querySelectorAll('[data-item-checkbox]:checked')).map((cb) => cb.value);

    if (!category || !name || checkedIds.length === 0) {
        if (formError) {
            formError.textContent = 'Please enter an E-Lot Category, an E-Lot Name, and select at least one verified item.';
            formError.hidden = false;
        }
        return;
    }

    const items = checkedIds.map(resolveElotItem).filter(Boolean);
    const totalQuantity = items.reduce((sum, item) => sum + item.actualQuantity, 0);
    const totalWeight = items.reduce((sum, item) => sum + item.totalWeight, 0);

    const elots = getElots();
    const now = new Date().toISOString();

    if (activeElotMode === 'edit' && activeElotId && elots[activeElotId]) {
        elots[activeElotId] = {
            ...elots[activeElotId],
            name, category, description,
            itemIds: checkedIds,
            totalQuantity, totalWeight,
            updatedAt: now,
        };
    } else {
        const id = generateElotId(elots);
        elots[id] = {
            id, name, category, description,
            itemIds: checkedIds,
            totalQuantity, totalWeight,
            status: 'draft',
            verified: null,
            createdAt: now,
            updatedAt: now,
            submittedAt: null,
        };
    }

    saveElots(elots);
    closeElotModal();
    renderElotsWorkspace();
}
function submitElot(elotId) {
    const elots = getElots();
    if (!elots[elotId]) return;
    elots[elotId].status = 'submitted';
    elots[elotId].verified = Math.random() < 0.75;
    elots[elotId].submittedAt = new Date().toISOString();
    saveElots(elots);
    renderElotsWorkspace();
}
function deleteElot(elotId) {
    if (!confirm('Move this submitted E-Lot back to draft? You can review and re-submit it from the Draft section.')) return;
    const elots = getElots();
    if (!elots[elotId]) return;
    elots[elotId].status = 'draft';
    elots[elotId].verified = null;
    saveElots(elots);
    renderElotsWorkspace();
}
function initElotsPage() {
    const poolBody = document.querySelector('[data-verified-pool-body]');
    if (!poolBody) return;

    renderElotsWorkspace();

    document.querySelector('[data-open-create-elot]')?.addEventListener('click', () => openElotModal('create', null));
    document.querySelectorAll('[data-close-elot-modal]').forEach((btn) => {
        btn.addEventListener('click', closeElotModal);
    });
    document.querySelector('[data-elot-modal]')?.addEventListener('click', (event) => {
        if (event.target === event.currentTarget) closeElotModal();
    });
    document.querySelector('[data-save-elot-draft]')?.addEventListener('click', saveElot);
}