(() => {
    'use strict';

    const ELOTS_KEY = 'ecolot_elots_v2';
    const VERIFIED_ITEMS_KEY = 'ecolot_verified_collection_items_v1';

    const seedVerifiedItems = [
        { id: 'VCI-001', recordItemId: 'CRI-201', requestId: 'REQ-00992', name: 'Laptop Computer', categoryId: 1, categoryName: 'Computers & IT Equipment', quantity: 3, actualWeightKg: 7.8, verifiedOn: '2026-08-14' },
        { id: 'VCI-002', recordItemId: 'CRI-202', requestId: 'REQ-00994', name: 'Desktop Monitor', categoryId: 2, categoryName: 'Displays & Monitors', quantity: 2, actualWeightKg: 9.4, verifiedOn: '2026-08-14' },
        { id: 'VCI-003', recordItemId: 'CRI-203', requestId: 'REQ-00995', name: 'Wi-Fi Router', categoryId: 3, categoryName: 'Networking Equipment', quantity: 7, actualWeightKg: 4.9, verifiedOn: '2026-08-15' },
        { id: 'VCI-004', recordItemId: 'CRI-204', requestId: 'REQ-00997', name: 'Network Switch', categoryId: 3, categoryName: 'Networking Equipment', quantity: 3, actualWeightKg: 5.7, verifiedOn: '2026-08-15' },
        { id: 'VCI-005', recordItemId: 'CRI-205', requestId: 'REQ-00999', name: 'Mobile Phone', categoryId: 4, categoryName: 'Mobile & Communication Devices', quantity: 12, actualWeightKg: 2.6, verifiedOn: '2026-08-16' },
        { id: 'VCI-006', recordItemId: 'CRI-206', requestId: 'REQ-01001', name: 'Lithium-ion Battery Pack', categoryId: 5, categoryName: 'Batteries', quantity: 6, actualWeightKg: 8.3, verifiedOn: '2026-08-16' }
    ];

    let activeLotId = null;
    let modalReturnFocus = null;

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const getJson = (key, fallback) => {
        try {
            const parsed = JSON.parse(localStorage.getItem(key));
            return parsed ?? fallback;
        } catch (error) {
            return fallback;
        }
    };

    const setJson = (key, value) => localStorage.setItem(key, JSON.stringify(value));

    const ensureVerifiedItems = () => {
        const existing = getJson(VERIFIED_ITEMS_KEY, null);
        if (!Array.isArray(existing) || !existing.length) {
            setJson(VERIFIED_ITEMS_KEY, seedVerifiedItems);
            return [...seedVerifiedItems];
        }
        return existing;
    };

    const normalizeLot = (raw, index) => {
        const id = raw.id || raw.eLotId || raw.elot_id || raw.elotCode || `EL-C-${String(index + 1).padStart(3, '0')}`;
        const status = raw.status || raw.lotStatus || raw.lot_status || 'DRAFT';
        const itemIds = Array.isArray(raw.itemIds)
            ? raw.itemIds
            : Array.isArray(raw.items)
                ? raw.items.map((item) => typeof item === 'string' ? item : (item.id || item.itemId || item.verifiedItemId)).filter(Boolean)
                : [];
        return {
            ...raw,
            id,
            name: raw.name || raw.title || raw.elotName || id,
            description: raw.description || '',
            categoryId: Number(raw.categoryId || raw.category_id || 0) || null,
            categoryName: raw.categoryName || raw.category || 'Unspecified',
            itemIds,
            status,
            createdAt: raw.createdAt || raw.created_at || new Date().toISOString(),
            submittedAt: raw.submittedAt || raw.submitted_at || null
        };
    };

    const getLots = () => {
        const raw = getJson(ELOTS_KEY, []);
        if (!Array.isArray(raw)) return [];
        return raw.map(normalizeLot);
    };

    const saveLots = (lots) => setJson(ELOTS_KEY, lots);
    const getVerifiedItems = () => ensureVerifiedItems();

    const formatDate = (value) => {
        if (!value) return '—';
        const date = new Date(value.includes('T') ? value : `${value}T00:00:00`);
        return new Intl.DateTimeFormat('en-LK', { month: 'short', day: 'numeric', year: 'numeric' }).format(date);
    };

    const statusLabel = (status) => ({
        DRAFT: 'Draft',
        PENDING_VERIFICATION: 'Pending Verification',
        REJECTED: 'Rejected',
        OPEN_FOR_BIDDING: 'Open for Bidding',
        AWARDED: 'Awarded',
        PROCESSING: 'Processing',
        COMPLETED: 'Completed',
        CANCELLED: 'Cancelled'
    }[status] || String(status).replaceAll('_', ' '));

    const statusClass = (status) => ({
        DRAFT: 'draft',
        PENDING_VERIFICATION: 'pending-verification',
        REJECTED: 'rejected',
        OPEN_FOR_BIDDING: 'open-for-bidding',
        AWARDED: 'awarded',
        PROCESSING: 'processing',
        COMPLETED: 'completed',
        CANCELLED: 'cancelled'
    }[status] || 'pending');

    const lotItems = (lot) => {
        const byId = new Map(getVerifiedItems().map((item) => [item.id, item]));
        return (lot.itemIds || []).map((id) => byId.get(id)).filter(Boolean);
    };

    const lotWeight = (lot) => lotItems(lot).reduce((sum, item) => sum + (Number(item.actualWeightKg) || 0), 0);

    const usedItemIds = (excludeLotId = null) => {
        const used = new Set();
        getLots().forEach((lot) => {
            if (lot.id === excludeLotId || lot.status === 'CANCELLED') return;
            (lot.itemIds || []).forEach((id) => used.add(id));
        });
        return used;
    };

    const showFeedback = (message) => {
        const box = document.getElementById('elot-feedback');
        if (!box) return;
        box.textContent = message;
        box.hidden = false;
        window.clearTimeout(box._hideTimer);
        box._hideTimer = window.setTimeout(() => { box.hidden = true; }, 3600);
    };

    const renderVerifiedItems = () => {
        const body = document.getElementById('collector-verified-item-rows');
        if (!body) return;
        const items = getVerifiedItems();
        const used = usedItemIds();
        const available = items.filter((item) => !used.has(item.id));

        body.innerHTML = items.map((item) => `
            <tr>
                <td><span class="collector-request-id">${escapeHtml(item.id)}</span><br><small>${escapeHtml(item.name)}</small></td>
                <td>${escapeHtml(item.categoryName)}</td>
                <td>${Number(item.quantity) || 0}</td>
                <td>${Number(item.actualWeightKg).toFixed(2)} kg</td>
                <td>${escapeHtml(formatDate(item.verifiedOn))}</td>
                <td>${used.has(item.id)
                    ? '<span class="collector-status collector-status--submitted">Allocated</span>'
                    : '<span class="collector-status collector-status--verified">Available</span>'}</td>
            </tr>
        `).join('');

        document.getElementById('elot-verified-item-count').textContent = String(available.length);
        document.getElementById('elot-available-weight').textContent = `${available.reduce((sum, item) => sum + Number(item.actualWeightKg || 0), 0).toFixed(2)} kg available`;
    };

    const renderSummary = () => {
        const lots = getLots();
        document.getElementById('elot-draft-count').textContent = String(lots.filter((lot) => lot.status === 'DRAFT').length);
        document.getElementById('elot-pending-count').textContent = String(lots.filter((lot) => lot.status === 'PENDING_VERIFICATION').length);
        document.getElementById('elot-active-count').textContent = String(lots.filter((lot) => ['OPEN_FOR_BIDDING', 'AWARDED', 'PROCESSING'].includes(lot.status)).length);
    };

    const lotActions = (lot) => {
        if (['DRAFT', 'REJECTED'].includes(lot.status)) {
            return `
                <div class="collector-row-actions">
                    <button type="button" class="collector-table-action" data-elot-view="${escapeHtml(lot.id)}">View</button>
                    <button type="button" class="collector-table-action" data-elot-edit="${escapeHtml(lot.id)}">Edit</button>
                    <button type="button" class="collector-table-action" data-elot-submit="${escapeHtml(lot.id)}">Submit</button>
                    <button type="button" class="collector-table-action" data-elot-delete="${escapeHtml(lot.id)}">Delete</button>
                </div>
            `;
        }
        return `<button type="button" class="collector-table-action" data-elot-view="${escapeHtml(lot.id)}">View Details</button>`;
    };

    const renderLots = () => {
        const body = document.getElementById('collector-elot-rows');
        if (!body) return;
        const filter = document.getElementById('collector-elot-status-filter')?.value || 'ALL';
        const lots = getLots().filter((lot) => filter === 'ALL' || lot.status === filter);
        body.innerHTML = lots.map((lot) => `
            <tr>
                <td><span class="collector-request-id">${escapeHtml(lot.id)}</span></td>
                <td>${escapeHtml(lot.name)}</td>
                <td>${escapeHtml(lot.categoryName)}</td>
                <td>${(lot.itemIds || []).length}</td>
                <td>${lotWeight(lot).toFixed(2)} kg</td>
                <td><span class="collector-status collector-status--${statusClass(lot.status)}">${escapeHtml(statusLabel(lot.status))}</span></td>
                <td>${lotActions(lot)}</td>
            </tr>
        `).join('');
        const empty = document.getElementById('collector-elot-empty');
        if (empty) empty.hidden = lots.length !== 0;
    };

    const renderAll = () => {
        if (!document.querySelector('[data-collector-page="elots"]')) return;
        renderVerifiedItems();
        renderSummary();
        renderLots();
    };

    const categories = () => {
        const map = new Map();
        getVerifiedItems().forEach((item) => map.set(String(item.categoryId), { id: item.categoryId, name: item.categoryName }));
        return [...map.values()].sort((a, b) => a.name.localeCompare(b.name));
    };

    const nextLotId = () => {
        const numbers = getLots().map((lot) => {
            const match = lot.id.match(/(\d+)$/);
            return match ? Number(match[1]) : 0;
        });
        return `EL-C-${String(Math.max(0, ...numbers) + 1).padStart(3, '0')}`;
    };

    const openFormModal = (mode = 'create', lotId = null, trigger = null) => {
        const modal = document.getElementById('collector-elot-modal');
        const body = document.getElementById('collector-elot-modal-body');
        const title = document.getElementById('collector-elot-modal-title');
        const description = document.getElementById('collector-elot-modal-description');
        if (!modal || !body) return;

        const existing = lotId ? getLots().find((lot) => lot.id === lotId) : null;
        const readOnly = mode === 'view';
        activeLotId = existing?.id || null;
        modalReturnFocus = trigger || document.activeElement;

        if (title) title.textContent = readOnly ? `E-Lot Details — ${existing?.id || ''}` : existing ? `Edit E-Lot — ${existing.id}` : 'Create E-Lot';
        if (description) description.textContent = readOnly
            ? 'Review the E-Lot composition and current lifecycle status.'
            : 'Choose one category and combine verified items into an E-Lot draft.';

        if (readOnly && existing) {
            const items = lotItems(existing);
            body.innerHTML = `
                <section class="collector-elot-details-panel">
                    <div class="collector-elot-detail-grid">
                        <div><span>E-Lot ID</span><strong>${escapeHtml(existing.id)}</strong></div>
                        <div><span>Status</span><strong>${escapeHtml(statusLabel(existing.status))}</strong></div>
                        <div><span>Category</span><strong>${escapeHtml(existing.categoryName)}</strong></div>
                        <div><span>Items</span><strong>${items.length}</strong></div>
                        <div><span>Total Weight</span><strong>${lotWeight(existing).toFixed(2)} kg</strong></div>
                        <div><span>Submitted</span><strong>${escapeHtml(existing.submittedAt ? formatDate(existing.submittedAt) : 'Not submitted')}</strong></div>
                    </div>
                    <div class="collector-elot-detail-list">
                        <span>Included Verified Items</span>
                        <ul>${items.map((item) => `<li>${escapeHtml(item.name)} — ${item.quantity} unit(s), ${Number(item.actualWeightKg).toFixed(2)} kg</li>`).join('') || '<li>No matching verified item data is available.</li>'}</ul>
                    </div>
                    ${existing.description ? `<div class="collector-elot-detail-list"><span>Description</span><p>${escapeHtml(existing.description)}</p></div>` : ''}
                    <div class="collector-modal-actions"><button type="button" class="collector-modal-button collector-modal-button--secondary" data-elot-modal-close>Close</button></div>
                </section>
            `;
        } else {
            const lot = existing || {
                id: nextLotId(),
                name: '',
                description: '',
                categoryId: categories()[0]?.id || null,
                categoryName: categories()[0]?.name || '',
                itemIds: [],
                status: 'DRAFT'
            };
            body.innerHTML = formHtml(lot);
            refreshFormItems();
        }

        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        window.setTimeout(() => modal.querySelector('.collector-modal__close')?.focus(), 0);
    };

    const formHtml = (lot) => `
        <div class="collector-elot-form-layout" data-elot-form data-elot-id="${escapeHtml(lot.id)}">
            <section class="collector-elot-form-panel">
                <label class="collector-elot-field">
                    <span>E-Lot ID</span>
                    <input type="text" value="${escapeHtml(lot.id)}" readonly>
                </label>
                <label class="collector-elot-field">
                    <span>Category *</span>
                    <select data-elot-category>
                        ${categories().map((category) => `<option value="${category.id}" ${Number(lot.categoryId) === Number(category.id) ? 'selected' : ''}>${escapeHtml(category.name)}</option>`).join('')}
                    </select>
                </label>
                <label class="collector-elot-field">
                    <span>E-Lot Name *</span>
                    <input type="text" maxlength="100" data-elot-name value="${escapeHtml(lot.name)}" placeholder="e.g. Verified Routers — August Batch">
                </label>
                <label class="collector-elot-field">
                    <span>Description</span>
                    <textarea maxlength="260" data-elot-description placeholder="Optional description">${escapeHtml(lot.description || '')}</textarea>
                </label>
            </section>

            <section class="collector-elot-items-panel">
                <div class="collector-record-block-heading">
                    <h3>Select Verified Items</h3>
                    <span class="collector-count-pill" data-elot-selection-count>0 selected</span>
                </div>
                <div class="collector-elot-item-list" data-elot-item-list></div>
                <div class="collector-elot-total">
                    <span>Total Selected Weight</span>
                    <strong data-elot-total>0.00 kg</strong>
                </div>
            </section>
        </div>
        <div class="collector-modal-actions">
            <button type="button" class="collector-modal-button collector-modal-button--secondary" data-elot-modal-close>Cancel</button>
            <button type="button" class="collector-modal-button collector-modal-button--primary" data-elot-save-draft>Save Draft</button>
        </div>
    `;

    const refreshFormItems = () => {
        const form = document.querySelector('[data-elot-form]');
        const list = form?.querySelector('[data-elot-item-list]');
        if (!form || !list) return;
        const lotId = form.dataset.elotId;
        const current = getLots().find((lot) => lot.id === lotId);
        const selected = new Set(current?.itemIds || []);
        list.querySelectorAll('[data-elot-item-check]:checked').forEach((checkbox) => selected.add(checkbox.value));

        const categoryId = Number(form.querySelector('[data-elot-category]')?.value || 0);
        const used = usedItemIds(lotId);
        const eligible = getVerifiedItems().filter((item) => Number(item.categoryId) === categoryId && !used.has(item.id));

        list.innerHTML = eligible.map((item) => `
            <label class="collector-elot-item-option">
                <input type="checkbox" value="${escapeHtml(item.id)}" data-elot-item-check ${selected.has(item.id) ? 'checked' : ''}>
                <span><strong>${escapeHtml(item.name)}</strong><span>${escapeHtml(item.id)} · ${item.quantity} unit(s) · ${escapeHtml(item.requestId)}</span></span>
                <b>${Number(item.actualWeightKg).toFixed(2)} kg</b>
            </label>
        `).join('') || '<div class="collector-empty-state"><strong>No available verified items</strong><span>Choose another category or free items from another draft.</span></div>';
        refreshFormTotals();
    };

    const refreshFormTotals = () => {
        const form = document.querySelector('[data-elot-form]');
        if (!form) return;
        const ids = [...form.querySelectorAll('[data-elot-item-check]:checked')].map((checkbox) => checkbox.value);
        const byId = new Map(getVerifiedItems().map((item) => [item.id, item]));
        const weight = ids.reduce((sum, id) => sum + Number(byId.get(id)?.actualWeightKg || 0), 0);
        const count = form.querySelector('[data-elot-selection-count]');
        const total = form.querySelector('[data-elot-total]');
        if (count) count.textContent = `${ids.length} selected`;
        if (total) total.textContent = `${weight.toFixed(2)} kg`;
    };

    const collectForm = () => {
        const form = document.querySelector('[data-elot-form]');
        if (!form) return { ok: false, error: 'E-Lot form is unavailable.' };
        const categoryId = Number(form.querySelector('[data-elot-category]')?.value || 0);
        const category = categories().find((entry) => Number(entry.id) === categoryId);
        const name = form.querySelector('[data-elot-name]')?.value?.trim() || '';
        const description = form.querySelector('[data-elot-description]')?.value?.trim() || '';
        const itemIds = [...form.querySelectorAll('[data-elot-item-check]:checked')].map((checkbox) => checkbox.value);
        if (!category) return { ok: false, error: 'Choose an E-Lot category.' };
        if (!name) return { ok: false, error: 'Enter an E-Lot name.' };
        if (!itemIds.length) return { ok: false, error: 'Select at least one verified item.' };
        return {
            ok: true,
            lot: {
                id: form.dataset.elotId,
                name,
                description,
                categoryId: category.id,
                categoryName: category.name,
                itemIds
            }
        };
    };

    const saveDraft = () => {
        const result = collectForm();
        if (!result.ok) {
            window.alert(result.error);
            return;
        }
        const lots = getLots();
        const index = lots.findIndex((lot) => lot.id === result.lot.id);
        const previous = index >= 0 ? lots[index] : {};
        const next = {
            ...previous,
            ...result.lot,
            status: previous.status === 'REJECTED' ? 'DRAFT' : (previous.status || 'DRAFT'),
            createdAt: previous.createdAt || new Date().toISOString(),
            updatedAt: new Date().toISOString(),
            submittedAt: null
        };
        if (index >= 0) lots[index] = next;
        else lots.push(next);
        saveLots(lots);
        showFeedback(`${next.id} draft saved.`);
        closeModal();
        renderAll();
    };

    const submitLot = (lotId) => {
        const lots = getLots();
        const index = lots.findIndex((lot) => lot.id === lotId);
        if (index < 0) return;
        if (!['DRAFT', 'REJECTED'].includes(lots[index].status)) return;
        if (!(lots[index].itemIds || []).length) {
            window.alert('This E-Lot has no verified items.');
            return;
        }
        if (!window.confirm(`Submit ${lotId} for Municipal Officer verification? It becomes read-only after submission.`)) return;
        lots[index] = { ...lots[index], status: 'PENDING_VERIFICATION', submittedAt: new Date().toISOString(), updatedAt: new Date().toISOString() };
        saveLots(lots);
        showFeedback(`${lotId} submitted for verification.`);
        renderAll();
    };

    const deleteLot = (lotId) => {
        const lots = getLots();
        const lot = lots.find((entry) => entry.id === lotId);
        if (!lot || !['DRAFT', 'REJECTED'].includes(lot.status)) return;
        if (!window.confirm(`Delete draft ${lotId}? Its verified items will return to the available pool.`)) return;
        saveLots(lots.filter((entry) => entry.id !== lotId));
        showFeedback(`${lotId} draft deleted.`);
        renderAll();
    };

    const closeModal = () => {
        const modal = document.getElementById('collector-elot-modal');
        if (!modal || modal.hidden) return;
        modal.hidden = true;
        activeLotId = null;
        document.body.style.overflow = '';
        if (modalReturnFocus instanceof HTMLElement) modalReturnFocus.focus();
        modalReturnFocus = null;
    };

    document.addEventListener('click', (event) => {
        if (event.target.closest('#collector-create-elot')) {
            openFormModal('create', null, event.target.closest('#collector-create-elot'));
            return;
        }
        if (event.target.closest('[data-elot-modal-close]')) {
            closeModal();
            return;
        }
        const view = event.target.closest('[data-elot-view]');
        if (view) {
            openFormModal('view', view.dataset.elotView, view);
            return;
        }
        const edit = event.target.closest('[data-elot-edit]');
        if (edit) {
            openFormModal('edit', edit.dataset.elotEdit, edit);
            return;
        }
        const submit = event.target.closest('[data-elot-submit]');
        if (submit) {
            submitLot(submit.dataset.elotSubmit);
            return;
        }
        const remove = event.target.closest('[data-elot-delete]');
        if (remove) {
            deleteLot(remove.dataset.ellotDelete || remove.dataset.elotDelete);
            return;
        }
        if (event.target.closest('[data-elot-save-draft]')) {
            saveDraft();
        }
    });

    document.addEventListener('change', (event) => {
        if (event.target.matches('#collector-elot-status-filter')) renderLots();
        if (event.target.matches('[data-elot-category]')) refreshFormItems();
        if (event.target.matches('[data-elot-item-check]')) refreshFormTotals();
    });

    document.addEventListener('keydown', (event) => {
        const modal = document.getElementById('collector-elot-modal');
        if (!modal || modal.hidden) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            closeModal();
            return;
        }
        if (event.key !== 'Tab') return;
        const focusable = [...modal.querySelectorAll('button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), a[href]')];
        if (!focusable.length) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });

    ensureVerifiedItems();
    renderAll();
})();