/* ═══════════════════════════════════════════════════
   pickup-request-history.js
   ═══════════════════════════════════════════════════ */

const EWASTE_ITEMS = {
    'Domestic E-Waste':   ['LCD TVs / Monitors','LED lamps','Computer hardware','Radios, DVD players','Electric ovens / Microwave ovens','Fans / Hair dryers','Electronic exercise equipment','Mobile phones / Laptops / Chargers','Bluetooth speakers / Earbuds','Cameras / CCTV equipment'],
    'Automobile E-Waste': ['Dashboard electronics','LED headlights','Hybrid batteries / EV batteries','Motors / Alternators','Switches','Sensors','Cables','Relays','Heaters'],
    'Office E-Waste':     ['Photocopy machines','UPS units / UPS batteries','Printers / Scanners','Projectors / Speakers','Access control equipment (fingerprint machines)','Network equipment','Telephone / Fax / Intercom equipment','Barcode readers / POS machines'],
    'Industrial E-Waste': ['Inverters / VFDs','CNC machines','Elevator electronic components','Sign board displays','Air conditioners','Automation equipment','Power supply units','Vending machine hardware','Solar power equipment'],
    'Medical E-Waste':    ['Ventilators / Insulin pumps','Hearing aids / Electric wheelchairs','Oximeters / Electronic thermometers','Ultrasound machines and probes','Centrifuges / Spectrophotometers','Electronic medical record systems','Glucometers / Weight scales'],
};

const SCHEDULE_OPTIONS = loadScheduleOptions();

let editRowCounter = 0;
let editSelectedItem = null;
let editSelectedCategory = null;

// ── Helpers ──────────────────────────────────────────
function openModal(modal)  { modal?.classList.add('active'); }
function closeModal(modal) { modal?.classList.remove('active'); }

function esc(str) {
    return String(str ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[char]));
}

function titleCase(str) {
    const s = String(str ?? '');
    return s.length === 0 ? s : s.charAt(0).toUpperCase() + s.slice(1).toLowerCase();
}

function loadScheduleOptions() {
    const dataEl = document.getElementById('scheduleOptionsData');
    if (!dataEl) return [];
    try {
        return JSON.parse(dataEl.textContent || '[]');
    } catch (error) {
        console.error('[pickup-request-history.js] Could not parse schedule options data.', error);
        return [];
    }
}

// ── View modal ───────────────────────────────────────
function openViewModal(triggerEl) {
    const row = triggerEl.closest('tr');
    if (!row) return;

    document.getElementById('viewRequestId').textContent      = row.dataset.requestId    || '';
    document.getElementById('viewPostal').textContent         = row.dataset.postal        || '';
    document.getElementById('viewCollectionDate').textContent = row.dataset.collectionDateLabel || '';    
    document.getElementById('viewAddress').textContent        = row.dataset.address       || '';

    const status = row.dataset.status || 'Pending';
    const badgeClass = status === 'Completed' ? 'badge-completed'
                     : status === 'Cancelled' ? 'badge-cancelled'
                     : 'badge-pending';
    document.getElementById('viewStatus').innerHTML =
        `<span class="badge ${badgeClass}"><span class="dot"></span>${esc(status)}</span>`;

    const tbody = document.getElementById('viewItemsBody');
    tbody.innerHTML = '';
    let items = [];
    try { items = JSON.parse(row.dataset.items || '[]'); } catch(e) {}

    if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;color:#5f7268;">No items found.</td></tr>';
    } else {
        items.forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${esc(item.category)}</td>
                <td>${esc(item.item)}</td>
                <td>${esc(String(item.quantity))}</td>
                <td>${esc(String(item.weight))}</td>
                <td>${esc(titleCase(item.condition))}</td>                
                <td>${item.note ? esc(item.note) : '<span style="color:#a0b0a8;">—</span>'}</td>`;
            tbody.appendChild(tr);
        });
    }
    openModal(document.getElementById('viewModal'));
}

// ── Edit modal ───────────────────────────────────────
function populateEditScheduleSelect(row) {
    const select = document.getElementById('editCollectionDate');
    select.innerHTML = '';

    const currentScheduleId = parseInt(row.dataset.scheduleId, 10);
    const seen = new Set();

    // Always include the request's own current date first, so it's kept
    // selected by default even if it wouldn't otherwise show as "open".
    if (!Number.isNaN(currentScheduleId)) {
        const opt = document.createElement('option');
        opt.value = String(currentScheduleId);
        opt.textContent = row.dataset.collectionDateLabel || 'Current date';
        opt.selected = true;
        select.appendChild(opt);
        seen.add(currentScheduleId);
    }

    SCHEDULE_OPTIONS.forEach(schedule => {
        if (seen.has(schedule.schedule_id)) return;
        const opt = document.createElement('option');
        opt.value = String(schedule.schedule_id);
        opt.textContent = schedule.label;
        select.appendChild(opt);
        seen.add(schedule.schedule_id);
    });
}

function openEditModal(triggerEl) {
    const row = triggerEl.closest('tr');
    if (!row) return;

    document.getElementById('editRequestId').textContent = row.dataset.requestId || '';
    document.getElementById('editPostal').value = row.dataset.postal || '';
    document.getElementById('editAddress').value = row.dataset.address || '';

    populateEditScheduleSelect(row);

    const form = document.getElementById('editRequestForm');
    form.action = `${window.location.pathname.replace(/\/$/, '')}/${row.dataset.requestPk}/update`;

    const tbody = document.getElementById('editItemsBody');
    tbody.innerHTML = '';
    editRowCounter = 0;
    let items = [];
    try { items = JSON.parse(row.dataset.items || '[]'); } catch(e) {}
    items.forEach(it => addEditRow(it.category, it.item, it.quantity, it.weight, it.condition, it.note));
    updateEditEmptyState();

    openModal(document.getElementById('editModal'));
}

function addEditRow(category, item, qty = 1, weight = '', condition = 'WORKING', note = '') {
    editRowCounter++;
    const i = editRowCounter;
    const tbody = document.getElementById('editItemsBody');
    const conditionValue = String(condition || 'WORKING').toUpperCase();
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td class="cell-readonly">${esc(category)}<input type="hidden" name="items[${i}][category]" value="${esc(category)}"></td>
        <td class="cell-readonly">${esc(item)}<input type="hidden" name="items[${i}][item]" value="${esc(item)}"></td>
        <td><input type="number" name="items[${i}][quantity]" min="1" value="${esc(String(qty))}" required></td>
        <td><input type="number" name="items[${i}][weight]" min="0.1" step="0.1" placeholder="e.g. 2.5" value="${weight ? esc(String(weight)) : ''}" required></td>
        <td>
            <select name="items[${i}][condition]">
                <option value="WORKING"${conditionValue==='WORKING'?' selected':''}>Working</option>
                <option value="DAMAGED"${conditionValue==='DAMAGED'?' selected':''}>Damaged</option>
                <option value="UNKNOWN"${conditionValue==='UNKNOWN'?' selected':''}>Unknown</option>
            </select>
        </td>
        <td><input type="text" name="items[${i}][note]" placeholder="Optional note" value="${note ? esc(note) : ''}"></td>
        <td class="col-delete">
            <button type="button" class="edit-row-delete" data-delete-edit-row aria-label="Remove">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
            </button>
        </td>`;
    tbody.appendChild(tr);
    updateEditEmptyState();
}

function updateEditEmptyState() {
    const tbody = document.getElementById('editItemsBody');
    const msg   = document.getElementById('editEmptyMsg');
    const table = document.getElementById('editItemsTable');
    if (!tbody || !msg || !table) return;
    const hasRows = tbody.children.length > 0;
    msg.style.display   = hasRows ? 'none' : '';
    table.style.display = hasRows ? '' : 'none';
}

// ── Add-item sub-modal (inside edit modal) ───────────
function openEditItemModal() {
    document.getElementById('editModalCategory').value = '';
    editSelectedItem = null;
    editSelectedCategory = null;
    document.getElementById('editModalItemsList').innerHTML = '<p class="modal-placeholder">Select a category to see its items.</p>';
    document.getElementById('editModalOkBtn').disabled = true;
    openModal(document.getElementById('editItemModal'));
}

function renderEditModalItems() {
    const cat  = document.getElementById('editModalCategory').value;
    const list = document.getElementById('editModalItemsList');
    const ok   = document.getElementById('editModalOkBtn');
    editSelectedItem = null;
    editSelectedCategory = cat;
    ok.disabled = true;

    if (!cat) { list.innerHTML = '<p class="modal-placeholder">Select a category to see its items.</p>'; return; }

    const items = EWASTE_ITEMS[cat] || [];
    list.innerHTML = items.map((item, idx) => `
        <label class="modal-item-option" for="editOpt_${idx}">
            <input type="radio" name="editItemChoice" id="editOpt_${idx}" value="${esc(item)}">
            <span>${esc(item)}</span>
        </label>`).join('');

    list.querySelectorAll('input[type="radio"]').forEach(r => {
        r.addEventListener('change', () => {
            editSelectedItem = r.value;
            list.querySelectorAll('.modal-item-option').forEach(o => o.classList.remove('selected'));
            r.closest('.modal-item-option').classList.add('selected');
            ok.disabled = false;
        });
    });
}

// ── Single DOMContentLoaded — all event wiring ───────
document.addEventListener('DOMContentLoaded', () => {
    const viewModal     = document.getElementById('viewModal');
    const deleteModal   = document.getElementById('deleteModal');
    const editModal     = document.getElementById('editModal');
    const editItemModal = document.getElementById('editItemModal');
    const deleteForm    = document.getElementById('deleteRequestForm');

    // Category select in sub-modal
    document.getElementById('editModalCategory')?.addEventListener('change', renderEditModalItems);

        // Edit form submit — real POST, blocked only if there are no items
    document.getElementById('editRequestForm')?.addEventListener('submit', e => {
        const tbody = document.getElementById('editItemsBody');
        if (!tbody || tbody.children.length === 0) {
            e.preventDefault();
            alert('Add at least one e-waste item before saving.');
        }
    });

    // Confirm delete — real POST via the hidden form
    document.getElementById('confirmDeleteBtn')?.addEventListener('click', () => {
        if (deleteForm.action) deleteForm.submit();
    });

    // Global click delegation
    document.addEventListener('click', e => {

        // ── View button / request ID link ──
        if (e.target.closest('[data-view-btn]')) {
            openViewModal(e.target.closest('[data-view-btn]'));
            return;
        }

        // ── Edit button ──
        if (e.target.closest('[data-edit-request]')) {
            openEditModal(e.target.closest('[data-edit-request]'));
            return;
        }

        // ── Delete button ──
        if (e.target.closest('[data-delete-request]')) {
            const row = e.target.closest('tr');
            const requestPk = row?.dataset.requestPk;
            deleteForm.action = `${window.location.pathname.replace(/\/$/, '')}/${requestPk}/delete`;
            openModal(deleteModal);
            return;
        }

        // ── Open add-item sub-modal ──
        if (e.target.closest('[data-open-edit-item-modal]')) {
            openEditItemModal();
            return;
        }

        // ── Confirm add item ──
        if (e.target.closest('[data-confirm-edit-item]')) {
            if (editSelectedCategory && editSelectedItem) {
                addEditRow(editSelectedCategory, editSelectedItem);
                closeModal(editItemModal);
            }
            return;
        }

        // ── Delete row in edit table ──
        if (e.target.closest('[data-delete-edit-row]')) {
            e.target.closest('tr').remove();
            updateEditEmptyState();
            return;
        }

        // ── Close buttons / backdrop clicks ──
        if (e.target.closest('[data-close-view-modal]')      || e.target === viewModal)     closeModal(viewModal);
        if (e.target.closest('[data-close-delete-modal]')    || e.target === deleteModal)   closeModal(deleteModal);
        if (e.target.closest('[data-close-edit-modal]')      || e.target === editModal)     closeModal(editModal);
        if (e.target.closest('[data-close-edit-item-modal]') || e.target === editItemModal) closeModal(editItemModal);
    });

    // Escape key
    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        closeModal(viewModal);
        closeModal(deleteModal);
        closeModal(editModal);
        closeModal(editItemModal);
    });
});