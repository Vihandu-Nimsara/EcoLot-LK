document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-pickup-request-form]');

    form?.addEventListener('submit', (event) => {
        const tbody = document.querySelector('[data-items-table-body]');
        if (!tbody || tbody.children.length === 0) {
            event.preventDefault();
            alert('Add at least one e-waste item before submitting.');
        }
    });

    initItemsTable();
    const draft = JSON.parse(document.getElementById('pickupDraft').textContent || '{}');
    if (draft.request_id == null) {
        document.querySelector('[name="schedule_id"]')?.querySelectorAll('option').forEach(option => {
            option.selected = option.value === String(draft.schedule_id || '');
        });
        Object.values(draft.items || {}).forEach(item => {
            if (!item || typeof item !== 'object') return;
            addItemRow(item.category || '', item.item || '');
            const row = document.querySelector('[data-items-table-body]').lastElementChild;
            ['quantity', 'weight', 'condition', 'note'].forEach(key => {
                const input = row.querySelector(`[name$="[${key}]"]`);
                if (input && typeof item[key] !== 'object') input.value = item[key] ?? '';
            });
        });
    }
    const categorySelect = document.querySelector('[data-modal-category]');
    categorySelect.innerHTML = '<option value="">Select category</option>' + Object.keys(EWASTE_ITEMS_BY_CATEGORY).map(category => `<option>${escapeHtml(category)}</option>`).join('');
});

/* =========================================================
   E-waste items table + "Add an E-waste Item" modal
   ========================================================= */

// Category -> list of items.
const EWASTE_ITEMS_BY_CATEGORY = JSON.parse(document.getElementById('pickupCatalogue').textContent || '{}');

let modalSelectedItem = null;
let itemRowCounter = 0;
let itemModalTrigger = null;

function initItemsTable() {
    const modal = document.querySelector('[data-item-modal]');
    const openBtn = document.querySelector('[data-open-item-modal]');
    const categorySelect = document.querySelector('[data-modal-category]');
    const okBtn = document.querySelector('[data-confirm-add-item]');

    if (!openBtn) {
        console.error('[pickup-request-form.js] "Add an E-waste Item" button not found — check that pickup-request-form.php includes data-open-item-modal.');
        return;
    }
    if (!modal) {
        console.error('[pickup-request-form.js] Item modal not found — check that pickup-request-form.php includes data-item-modal.');
        return;
    }

    updateEmptyTableState();

    openBtn.addEventListener('click', () => openItemModal());

    modal.querySelectorAll('[data-close-item-modal]').forEach((btn) => {
        btn.addEventListener('click', () => closeItemModal());
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeItemModal();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) closeItemModal();
        if (event.key === 'Tab' && !modal.hidden) {
            const nodes = [...modal.querySelectorAll('button:not(:disabled), select, input')];
            const target = event.shiftKey && document.activeElement === nodes[0] ? nodes.at(-1)
                : !event.shiftKey && document.activeElement === nodes.at(-1) ? nodes[0] : null;
            if (target) { event.preventDefault(); target.focus(); }
        }
    });

    categorySelect?.addEventListener('change', () => renderModalItems());
    okBtn?.addEventListener('click', () => confirmAddItem());

    document.querySelector('[data-items-table-body]')
        ?.addEventListener('click', (event) => {
            const deleteBtn = event.target.closest('[data-delete-item-row]');
            if (deleteBtn) deleteItemRow(deleteBtn);
        });
}

function openItemModal() {
    const modal = document.querySelector('[data-item-modal]');
    const categorySelect = document.querySelector('[data-modal-category]');
    const listEl = document.querySelector('[data-modal-items-list]');
    const okBtn = document.querySelector('[data-confirm-add-item]');

    if (!modal) return;

    categorySelect.value = '';
    modalSelectedItem = null;
    listEl.innerHTML = '<p class="modal-placeholder">Select a category to see its items.</p>';
    okBtn.disabled = true;

    itemModalTrigger = document.activeElement;
    modal.hidden = false;
    modal.style.display = 'flex';
    categorySelect.focus();
}

function closeItemModal() {
    const modal = document.querySelector('[data-item-modal]');
    if (!modal) return;
    modal.hidden = true;
    modal.style.display = 'none';
    itemModalTrigger?.focus();
}

function renderModalItems() {
    const category = document.querySelector('[data-modal-category]').value;
    const listEl = document.querySelector('[data-modal-items-list]');
    const okBtn = document.querySelector('[data-confirm-add-item]');

    modalSelectedItem = null;
    okBtn.disabled = true;

    if (!category) {
        listEl.innerHTML = '<p class="modal-placeholder">Select a category to see its items.</p>';
        return;
    }

    const items = EWASTE_ITEMS_BY_CATEGORY[category] || [];
    if (items.length === 0) {
        listEl.innerHTML = '<p class="modal-placeholder">No items listed for this category.</p>';
        return;
    }

    listEl.innerHTML = items.map((item, idx) => {
        const optId = `modalItem_${idx}`;
        return `
            <label class="modal-item-option" for="${optId}">
                <input type="radio" name="modalItemChoice" id="${optId}" value="${escapeHtml(item)}">
                <span>${escapeHtml(item)}</span>
            </label>
        `;
    }).join('');

    listEl.querySelectorAll('input[type="radio"]').forEach((radio) => {
        radio.addEventListener('change', () => selectModalItem(radio));
    });
}

function selectModalItem(radioInput) {
    modalSelectedItem = radioInput.value;
    document.querySelectorAll('.modal-item-option').forEach((opt) => {
        opt.classList.remove('selected');
    });
    radioInput.closest('.modal-item-option').classList.add('selected');
    document.querySelector('[data-confirm-add-item]').disabled = false;
}

function confirmAddItem() {
    const category = document.querySelector('[data-modal-category]').value;
    if (!category || !modalSelectedItem) return;
    addItemRow(category, modalSelectedItem);
    closeItemModal();
}

function addItemRow(category, item) {
    itemRowCounter += 1;
    const rowIndex = itemRowCounter;
    const tbody = document.querySelector('[data-items-table-body]');

    const tr = document.createElement('tr');
    tr.dataset.rowId = rowIndex;
    tr.innerHTML = `
        <td class="cell-readonly">${escapeHtml(category)}
            <input type="hidden" name="items[${rowIndex}][category]" value="${escapeHtml(category)}"></td>
        <td class="cell-readonly">${escapeHtml(item)}
            <input type="hidden" name="items[${rowIndex}][item]" value="${escapeHtml(item)}"></td>
        <td><input type="number" min="1" value="1" required name="items[${rowIndex}][quantity]"></td>
        <td><input type="number" min="0.001" max="9999999.999" step="0.001" placeholder="e.g. 2.5" required name="items[${rowIndex}][weight]"></td>
        <td>
            <select required name="items[${rowIndex}][condition]">
                <option value="WORKING">Working</option>
                <option value="DAMAGED">Damaged</option>
                <option value="UNKNOWN">Unknown</option>
            </select>
        </td>
        <td><input type="text" maxlength="500" placeholder="Optional note" name="items[${rowIndex}][note]"></td>
        <td class="col-delete">
            <button type="button" class="row-delete-btn" data-delete-item-row aria-label="Remove item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    updateEmptyTableState();
}

function deleteItemRow(button) {
    const row = button.closest('tr');
    if (row) row.remove();
    updateEmptyTableState();
}

function updateEmptyTableState() {
    const tbody = document.querySelector('[data-items-table-body]');
    const emptyMsg = document.querySelector('[data-empty-table-msg]');
    const table = document.querySelector('[data-items-table]');
    if (!tbody || !emptyMsg || !table) return;

    const hasRows = tbody.children.length > 0;
    emptyMsg.hidden = hasRows;
    table.style.display = hasRows ? '' : 'none';
}

function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[char]));
}