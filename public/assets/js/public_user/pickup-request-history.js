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

let editRowCounter = 0;
let editSelectedItem = null;
let editSelectedCategory = null;

// ── Helpers ──────────────────────────────────────────
function openModal(modal)  { modal?.classList.add('active'); }
function closeModal(modal) { modal?.classList.remove('active'); }

function esc(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

// ── View modal ───────────────────────────────────────
function openViewModal(triggerEl) {
    const row = triggerEl.closest('tr');
    if (!row) return;

    document.getElementById('viewRequestId').textContent      = row.dataset.requestId    || '';
    document.getElementById('viewPostal').textContent         = row.dataset.postal        || '';
    document.getElementById('viewCollectionDate').textContent = row.dataset.collectionDate || '';
    document.getElementById('viewAddress').textContent        = row.dataset.address       || '';

    const status = row.dataset.status || 'Pending';
    const badgeClass = status === 'Completed' ? 'badge-completed'
                     : status === 'Cancelled' ? 'badge-cancelled'
                     : 'badge-pending';
    document.getElementById('viewStatus').innerHTML =
        `<span class="badge ${badgeClass}"><span class="dot"></span>${status}</span>`;

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
                <td>${esc(item.condition)}</td>
                <td>${item.note ? esc(item.note) : '<span style="color:#a0b0a8;">—</span>'}</td>`;
            tbody.appendChild(tr);
        });
    }
    openModal(document.getElementById('viewModal'));
}

// ── Edit modal ───────────────────────────────────────
function openEditModal(triggerEl) {
    const row = triggerEl.closest('tr');
    if (!row) return;

    document.getElementById('editRequestId').textContent = row.dataset.requestId || '';

    const postalEl = document.getElementById('editPostal');
    const addrEl   = document.getElementById('editAddress');
    const dateEl   = document.getElementById('editCollectionDate');
    if (postalEl) postalEl.value = row.dataset.postal   || '';
    if (addrEl)   addrEl.value   = row.dataset.address  || '';
    if (dateEl && row.dataset.collectionDate) {
        const d = new Date(row.dataset.collectionDate);
        if (!isNaN(d)) dateEl.value = d.toISOString().split('T')[0];
    }

    const tbody = document.getElementById('editItemsBody');
    tbody.innerHTML = '';
    editRowCounter = 0;
    let items = [];
    try { items = JSON.parse(row.dataset.items || '[]'); } catch(e) {}
    items.forEach(it => addEditRow(it.category, it.item, it.quantity, it.weight, it.condition, it.note));
    updateEditEmptyState();

    openModal(document.getElementById('editModal'));
}

function addEditRow(category, item, qty = 1, weight = '', condition = 'Working', note = '') {
    editRowCounter++;
    const i = editRowCounter;
    const tbody = document.getElementById('editItemsBody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td class="cell-readonly">${esc(category)}<input type="hidden" name="edit_items[${i}][category]" value="${esc(category)}"></td>
        <td class="cell-readonly">${esc(item)}<input type="hidden" name="edit_items[${i}][item]" value="${esc(item)}"></td>
        <td><input type="number" name="edit_items[${i}][quantity]" min="1" value="${esc(String(qty))}" required></td>
        <td><input type="number" name="edit_items[${i}][weight]" min="0" step="0.1" placeholder="e.g. 2.5" value="${weight ? esc(String(weight)) : ''}"></td>
        <td>
            <select name="edit_items[${i}][condition]">
                <option value="Working"${condition==='Working'?' selected':''}>Working</option>
                <option value="Damaged"${condition==='Damaged'?' selected':''}>Damaged</option>
            </select>
        </td>
        <td><input type="text" name="edit_items[${i}][note]" placeholder="Optional note" value="${note ? esc(note) : ''}"></td>
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

    // Category select in sub-modal
    document.getElementById('editModalCategory')?.addEventListener('change', renderEditModalItems);

    // Edit form submit
    document.getElementById('editRequestForm')?.addEventListener('submit', e => {
        e.preventDefault();
        alert('Prototype — changes not saved to database yet.');
        closeModal(editModal);
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
            const id = e.target.closest('[data-delete-request]').dataset.deleteRequest;
            const btn = document.getElementById('confirmDeleteBtn');
            if (btn) btn.href = `delete-request.php?id=${id}`;
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