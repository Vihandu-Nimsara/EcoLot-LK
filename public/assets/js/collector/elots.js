(() => {
    'use strict';

    // ============================================================
    // CONFIGURATION
    // ============================================================

    const BASE = '/EcoLot-LK/public';

    const API = `${BASE}/collector/e-lots`;

    const SELECTORS = {
        page: '[data-collector-page="elots"]',
        modal: '#collector-elot-modal',
        modalBody: '#collector-elot-modal-body',
        modalTitle: '#collector-elot-modal-title',
        modalDescription: '#collector-elot-modal-description',
        verifiedRows: '#collector-verified-item-rows',
        lotRows: '#collector-elot-rows',
        lotEmpty: '#collector-elot-empty',
        statusFilter: '#collector-elot-status-filter',
        createButton: '#collector-create-elot'
    };

    // ============================================================
    // APPLICATION STATE
    // ============================================================

    let activeLotId = null;

    let activeLot = null;

    let selectedItems = new Set();

    let currentFormItems = [];

    let requestSequence = 0;

    let modalReturnFocus = null;

    let busy = false;

    // ============================================================
    // DATA FROM PHP
    // ============================================================

    const poolData = Array.isArray(window.COLLECTOR_POOL)
        ? window.COLLECTOR_POOL
        : [];

    const lotData = Array.isArray(window.COLLECTOR_LOTS)
        ? window.COLLECTOR_LOTS
        : [];

    const categoryData = Array.isArray(
        window.COLLECTOR_CATEGORIES
    )
        ? window.COLLECTOR_CATEGORIES
        : [];

    // ============================================================
    // GENERAL HELPERS
    // ============================================================

    const $ = selector => document.querySelector(selector);

    const escapeHtml = value => {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    };

    const number = value => {
        const result = Number(value);

        return Number.isFinite(result)
            ? result
            : 0;
    };

    const formatWeight = value => {
        return `${number(value).toFixed(2)} kg`;
    };

    const formatDate = value => {
        if (!value) {
            return '—';
        }

        const date = new Date(
            String(value).replace(' ', 'T')
        );

        if (Number.isNaN(date.getTime())) {
            return '—';
        }

        return new Intl.DateTimeFormat('en-LK', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        }).format(date);
    };

    const csrfToken = () => {
        return (
            $('[data-elot-csrf]')?.dataset.elotCsrf ||
            $('meta[name="csrf-token"]')
                ?.getAttribute('content') ||
            ''
        );
    };

    const statusLabel = status => {
        const labels = {
            DRAFT: 'Draft',
            PENDING_VERIFICATION: 'Pending Verification',
            REJECTED: 'Rejected',
            OPEN_FOR_BIDDING: 'Open for Bidding',
            AWARDED: 'Awarded',
            PROCESSING: 'Processing',
            COMPLETED: 'Completed',
            CANCELLED: 'Cancelled'
        };

        return labels[status] ||
            String(status || '').replaceAll('_', ' ');
    };

    const statusClass = status => {
        const classes = {
            DRAFT: 'draft',
            PENDING_VERIFICATION: 'pending',
            REJECTED: 'rejected',
            OPEN_FOR_BIDDING: 'approved',
            AWARDED: 'approved',
            PROCESSING: 'pending',
            COMPLETED: 'approved',
            CANCELLED: 'rejected'
        };

        return classes[status] || 'pending';
    };

    // ============================================================
    // NORMALIZE VERIFIED ITEMS
    // ============================================================

    const getVerifiedItems = () => {
        return poolData.map(item => {
            const allocatedLotId =
                item.allocated_lot_id ?? null;

            const allocated =
                number(item.is_allocated) === 1 ||
                (
                    allocatedLotId !== null &&
                    allocatedLotId !== ''
                );

            return {
                id: String(item.record_item_id),
                name: String(item.item_name || ''),
                categoryId: number(item.category_id),
                categoryName: String(
                    item.category_name || ''
                ),
                quantity: number(item.actual_quantity),
                weight: number(item.actual_weight_kg),
                verifiedAt: item.verified_at || '',
                allocated,
                allocatedLotId:
                    allocatedLotId === null
                        ? null
                        : String(allocatedLotId)
            };
        });
    };

    // ============================================================
    // NORMALIZE E-LOTS
    // ============================================================

    const getLots = () => {
        return lotData.map(lot => ({
            id: String(lot.e_lot_id),
            code: String(lot.lot_code || ''),
            title: String(lot.title || ''),
            categoryId: number(lot.category_id),
            categoryName: String(
                lot.category_name || ''
            ),
            status: String(lot.lot_status || 'DRAFT'),
            itemCount: number(lot.item_count),
            weight: number(lot.total_weight_kg),
            createdAt: lot.created_at || ''
        }));
    };

    // ============================================================
    // NORMALIZE CATEGORIES
    // ============================================================

    const getCategories = () => {
        return categoryData.map(category => ({
            id: number(category.category_id),
            name: String(category.category_name || '')
        }));
    };

    // ============================================================
    // VERIFIED ITEM AVAILABILITY
    // ============================================================

    const itemAvailability = item => {
        if (item.allocated) {
            return {
                label: 'Allocated',
                className: 'allocated'
            };
        }

        const modal = $(SELECTORS.modal);

        if (
            modal &&
            !modal.hidden &&
            selectedItems.has(item.id)
        ) {
            return {
                label: 'Selected',
                className: 'selected'
            };
        }

        return {
            label: 'Available',
            className: 'available'
        };
    };

    // ============================================================
    // RENDER VERIFIED ITEMS POOL
    // ============================================================

    const renderVerifiedItems = () => {
        const tbody = $(SELECTORS.verifiedRows);

        if (!tbody) {
            return;
        }

        const items = getVerifiedItems();

        if (items.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6">
                        <div class="collector-empty-state">
                            <strong>
                                No verified collection items
                            </strong>
                            <span>
                                Items will appear after Municipal
                                Officer verification.
                            </span>
                        </div>
                    </td>
                </tr>
            `;

            return;
        }

        tbody.innerHTML = items.map(item => {
            const availability = itemAvailability(item);

            return `
                <tr>
                    <td>
                        <span class="collector-request-id">
                            ${escapeHtml(item.name)}
                        </span>
                    </td>

                    <td>
                        ${escapeHtml(item.categoryName)}
                    </td>

                    <td>
                        ${escapeHtml(item.quantity)}
                    </td>

                    <td>
                        ${formatWeight(item.weight)}
                    </td>

                    <td>
                        ${formatDate(item.verifiedAt)}
                    </td>

                    <td>
                        <span
                            class="collector-status
                            collector-status--${availability.className}">
                            ${availability.label}
                        </span>
                    </td>
                </tr>
            `;
        }).join('');
    };

    // ============================================================
    // RENDER SUMMARY
    // ============================================================

    const renderSummary = () => {
        const items = getVerifiedItems();

        const lots = getLots();

        const available = items.filter(
            item => !item.allocated
        );

        const availableWeight = available.reduce(
            (total, item) => total + item.weight,
            0
        );

        const draftCount = lots.filter(
            lot => lot.status === 'DRAFT'
        ).length;

        const pendingCount = lots.filter(
            lot => lot.status === 'PENDING_VERIFICATION'
        ).length;

        const activeCount = lots.filter(
            lot => [
                'OPEN_FOR_BIDDING',
                'AWARDED',
                'PROCESSING'
            ].includes(lot.status)
        ).length;

        const availableCountElement = $(
            '#elot-verified-item-count'
        );

        const draftCountElement = $(
            '#elot-draft-count'
        );

        const pendingCountElement = $(
            '#elot-pending-count'
        );

        const activeCountElement = $(
            '#elot-active-count'
        );

        const weightElement = $(
            '#elot-available-weight'
        );

        if (availableCountElement) {
            availableCountElement.textContent =
                String(available.length);
        }

        if (draftCountElement) {
            draftCountElement.textContent =
                String(draftCount);
        }

        if (pendingCountElement) {
            pendingCountElement.textContent =
                String(pendingCount);
        }

        if (activeCountElement) {
            activeCountElement.textContent =
                String(activeCount);
        }

        if (weightElement) {
            weightElement.textContent =
                `${formatWeight(availableWeight)} available`;
        }
    };

    // ============================================================
    // E-LOT ACTION BUTTONS
    // ============================================================

    const renderLotActions = lot => {
        const editable = [
            'DRAFT',
            'REJECTED'
        ].includes(lot.status);

        if (!editable) {
            return `
                <div class="collector-row-actions">
                    <button
                        type="button"
                        class="collector-table-action"
                        data-elot-view="${escapeHtml(lot.id)}">
                        View
                    </button>
                </div>
            `;
        }

        return `
            <div class="collector-row-actions">

                <button
                    type="button"
                    class="collector-table-action"
                    data-elot-view="${escapeHtml(lot.id)}">
                    View
                </button>

                <button
                    type="button"
                    class="collector-table-action"
                    data-elot-edit="${escapeHtml(lot.id)}">
                    Edit
                </button>

                <button
                    type="button"
                    class="collector-table-action"
                    data-elot-submit="${escapeHtml(lot.id)}">
                    Submit
                </button>

                <button
                    type="button"
                    class="collector-danger-button"
                    data-elot-delete="${escapeHtml(lot.id)}">
                    Delete
                </button>

            </div>
        `;
    };

    // ============================================================
    // RENDER MY E-LOTS
    // ============================================================

    const renderLots = () => {
        const tbody = $(SELECTORS.lotRows);

        const empty = $(SELECTORS.lotEmpty);

        if (!tbody) {
            return;
        }

        const filter = $(SELECTORS.statusFilter)
            ?.value || 'ALL';

        const lots = getLots().filter(lot => {
            return filter === 'ALL' ||
                lot.status === filter;
        });

        tbody.innerHTML = lots.map(lot => `
            <tr>

                <td class="collector-elot-id-cell">
                    <span class="collector-request-id">
                        ${escapeHtml(lot.code || lot.id)}
                    </span>
                </td>

                <td>
                    ${escapeHtml(lot.title)}
                </td>

                <td>
                    ${escapeHtml(lot.categoryName)}
                </td>

                <td>
                    ${lot.itemCount}
                </td>

                <td>
                    ${formatWeight(lot.weight)}
                </td>

                <td>
                    <span
                        class="collector-status
                        collector-status--${statusClass(lot.status)}">
                        ${escapeHtml(statusLabel(lot.status))}
                    </span>
                </td>

                <td class="collector-action-column">
                    ${renderLotActions(lot)}
                </td>

            </tr>
        `).join('');

        if (empty) {
            empty.hidden = lots.length !== 0;
        }
    };

    // ============================================================
    // RENDER COMPLETE PAGE
    // ============================================================

    const renderAll = () => {
        if (!$(SELECTORS.page)) {
            return;
        }

        renderVerifiedItems();

        renderSummary();

        renderLots();
    };

    // ============================================================
    // MODAL HELPERS
    // ============================================================

    const getModal = () => $(SELECTORS.modal);

    const showModal = () => {
        const modal = getModal();

        if (!modal) {
            return;
        }

        modalReturnFocus = document.activeElement;

        modal.hidden = false;

        document.body.style.overflow = 'hidden';

        modal.querySelector(
            '.collector-modal__close'
        )?.focus();
    };

    const closeModal = () => {
        const modal = getModal();

        if (!modal || modal.hidden || busy) {
            return;
        }

        requestSequence++;

        modal.hidden = true;

        activeLotId = null;

        activeLot = null;

        selectedItems.clear();

        currentFormItems = [];

        const body = $(SELECTORS.modalBody);

        if (body) {
            body.innerHTML = '';
        }

        document.body.style.overflow = '';

        renderVerifiedItems();

        if (
            modalReturnFocus &&
            typeof modalReturnFocus.focus === 'function'
        ) {
            modalReturnFocus.focus();
        }

        modalReturnFocus = null;
    };

    // ============================================================
    // FORM HTML
    // ============================================================

    const buildForm = (lot = null) => {
        const isEdit = Boolean(lot);

        const selectedCategory = isEdit
            ? number(lot.category_id)
            : 0;

        const categoryOptions = getCategories()
            .map(category => {
                const selected =
                    category.id === selectedCategory
                        ? 'selected'
                        : '';

                return `
                    <option
                        value="${category.id}"
                        ${selected}>
                        ${escapeHtml(category.name)}
                    </option>
                `;
            }).join('');

        return `
            <form
                class="collector-elot-form-layout"
                data-elot-form
                data-elot-id="${isEdit
                ? escapeHtml(lot.e_lot_id)
                : 'new'}">

                <section class="collector-elot-form-panel">

                    <label class="collector-elot-field">

                        <span>E-Lot ID</span>

                        <input
                            type="text"
                            readonly
                            value="${escapeHtml(
                    isEdit
                        ? lot.lot_code
                        : (window.COLLECTOR_NEXT_LOT_CODE || '')
                )}">

                    </label>

                    <label class="collector-elot-field">

                        <span>E-Lot Category</span>

                        <select
                            data-elot-category
                            ${isEdit ? 'disabled' : ''}
                            required>

                            <option value="">
                                Select Category
                            </option>

                            ${categoryOptions}

                        </select>

                    </label>

                    <label class="collector-elot-field">

                        <span>E-Lot Name</span>

                        <input
                            type="text"
                            data-elot-name
                            maxlength="100"
                            required
                            placeholder="Enter E-Lot name"
                            value="${escapeHtml(
                    lot?.title || ''
                )}">

                    </label>

                </section>

                <section class="collector-elot-items-panel">

                    <div class="collector-record-block-heading">

                        <h3>Verified Items</h3>

                        <span
                            class="collector-count-pill"
                            data-elot-selection-count>
                            0 selected
                        </span>

                    </div>

                    <div
                        class="collector-elot-item-list"
                        data-elot-item-list>

                        <div class="collector-empty-state">
                            Choose a category to load
                            verified items.
                        </div>

                    </div>

                    <div class="collector-elot-total">

                        <span>Total Weight</span>

                        <strong data-elot-total>
                            0.00 kg
                        </strong>

                    </div>

                </section>

                <div class="collector-modal-actions">

                    <button
                        type="button"
                        class="collector-modal-button
                        collector-modal-button--secondary"
                        data-elot-modal-close>
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="collector-modal-button
                        collector-modal-button--primary"
                        data-elot-save-draft>
                        Save Draft
                    </button>

                </div>

            </form>
        `;
    };

    // ============================================================
    // UPDATE FORM TOTALS
    // ============================================================

    const refreshFormTotals = () => {
        const countElement = $(
            '[data-elot-selection-count]'
        );

        const totalElement = $(
            '[data-elot-total]'
        );

        const selected = currentFormItems.filter(
            item => selectedItems.has(
                String(item.record_item_id)
            )
        );

        const totalWeight = selected.reduce(
            (total, item) => {
                return total +
                    number(item.actual_weight_kg);
            },
            0
        );

        if (countElement) {
            countElement.textContent =
                `${selected.length} selected`;
        }

        if (totalElement) {
            totalElement.textContent =
                formatWeight(totalWeight);
        }
    };

    // ============================================================
    // RENDER VERIFIED ITEM CHECKBOXES
    // ============================================================

    const renderFormItems = items => {
        const container = $(
            '[data-elot-item-list]'
        );

        if (!container) {
            return;
        }

        if (!items.length) {
            container.innerHTML = `
                <div class="collector-empty-state">
                    <strong>
                        No available verified items
                    </strong>
                    <span>
                        This category currently has no
                        available verified collection items.
                    </span>
                </div>
            `;

            refreshFormTotals();

            return;
        }

        container.innerHTML = items.map(item => {
            const id = String(item.record_item_id);

            const checked = selectedItems.has(id)
                ? 'checked'
                : '';

            return `
                <label class="collector-elot-item-option">

                    <input
                        type="checkbox"
                        data-elot-item-check
                        value="${escapeHtml(id)}"
                        ${checked}>

                    <span>

                        <strong>
                            ${escapeHtml(item.item_name)}
                        </strong>

                        <small>
                            Quantity:
                            ${number(item.actual_quantity)}
                            |
                            Weight:
                            ${formatWeight(
                item.actual_weight_kg
            )}
                        </small>

                    </span>

                </label>
            `;
        }).join('');

        refreshFormTotals();
    };

    // ============================================================
    // FETCH JSON
    // ============================================================

    const getJson = async url => {
        const response = await fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json'
            }
        });

        const contentType =
            response.headers.get('content-type') || '';

        if (!contentType.includes('application/json')) {
            throw new Error(
                'The server returned an unexpected response.'
            );
        }

        const result = await response.json();

        if (!response.ok || result.success !== true) {
            throw new Error(
                result.message ||
                result.error ||
                'Unable to load data.'
            );
        }

        return result;
    };

    // ============================================================
    // LOAD AVAILABLE ITEMS FOR CATEGORY
    // ============================================================

    const refreshFormItems = async () => {
        const form = $('[data-elot-form]');

        if (!form) {
            return;
        }

        const categoryId = number(
            form.querySelector(
                '[data-elot-category]'
            )?.value
        );

        const container = $(
            '[data-elot-item-list]'
        );

        const sequence = ++requestSequence;

        currentFormItems = [];

        if (!categoryId) {
            selectedItems.clear();

            if (container) {
                container.innerHTML = `
                    <div class="collector-empty-state">
                        Choose a category first.
                    </div>
                `;
            }

            refreshFormTotals();

            renderVerifiedItems();

            return;
        }

        if (container) {
            container.innerHTML = `
                <div class="collector-empty-state">
                    Loading verified items...
                </div>
            `;
        }

        const params = new URLSearchParams();

        params.set(
            'category_id',
            String(categoryId)
        );

        if (activeLotId !== null) {
            params.set(
                'exclude_lot_id',
                String(activeLotId)
            );
        }

        try {
            const result = await getJson(
                `${API}/available-items?${params.toString()}`
            );

            if (sequence !== requestSequence) {
                return;
            }

            currentFormItems = Array.isArray(result.items)
                ? result.items
                : [];

            const allowedIds = new Set(
                currentFormItems.map(
                    item => String(item.record_item_id)
                )
            );

            selectedItems = new Set(
                [...selectedItems].filter(
                    id => allowedIds.has(id)
                )
            );

            renderFormItems(currentFormItems);

            renderVerifiedItems();

        } catch (error) {
            if (sequence !== requestSequence) {
                return;
            }

            currentFormItems = [];

            selectedItems.clear();

            if (container) {
                container.innerHTML = `
                    <div class="collector-empty-state">
                        ${escapeHtml(error.message)}
                    </div>
                `;
            }

            refreshFormTotals();

            renderVerifiedItems();
        }
    };

    // ============================================================
    // OPEN CREATE E-LOT MODAL
    // ============================================================

    const openCreateModal = () => {
        if (busy) {
            return;
        }

        activeLotId = null;

        activeLot = null;

        selectedItems.clear();

        currentFormItems = [];

        const title = $(SELECTORS.modalTitle);

        const description = $(
            SELECTORS.modalDescription
        );

        const body = $(SELECTORS.modalBody);

        if (title) {
            title.textContent = 'Create E-Lot';
        }

        if (description) {
            description.textContent =
                'Choose a category and select verified items to create an E-Lot draft.';
        }

        if (body) {
            body.innerHTML = buildForm();
        }

        showModal();

        renderVerifiedItems();
    };

    // ============================================================
    // OPEN EDIT E-LOT MODAL
    // ============================================================

    const openEditModal = async lotId => {
        if (busy) {
            return;
        }

        try {
            const result = await getJson(
                `${API}/${encodeURIComponent(lotId)}`
            );

            const lot = result.lot;

            if (!lot) {
                throw new Error(
                    'E-Lot details were not returned.'
                );
            }

            if (![
                'DRAFT',
                'REJECTED'
            ].includes(lot.lot_status)) {
                throw new Error(
                    'Only Draft or Rejected E-Lots can be edited.'
                );
            }

            activeLotId = String(lot.e_lot_id);

            activeLot = lot;

            selectedItems = new Set(
                (lot.item_ids || []).map(String)
            );

            currentFormItems = [];

            const title = $(SELECTORS.modalTitle);

            const description = $(
                SELECTORS.modalDescription
            );

            const body = $(SELECTORS.modalBody);

            if (title) {
                title.textContent = 'Edit E-Lot';
            }

            if (description) {
                description.textContent =
                    'Update the E-Lot name and verified items.';
            }

            if (body) {
                body.innerHTML = buildForm(lot);
            }

            showModal();

            await refreshFormItems();

        } catch (error) {
            console.error(error);

            alert(error.message);
        }
    };

    // ============================================================
    // VIEW E-LOT DETAILS
    // ============================================================

    const openViewModal = async lotId => {
        if (busy) {
            return;
        }

        try {
            const result = await getJson(
                `${API}/${encodeURIComponent(lotId)}`
            );

            const lot = result.lot;

            if (!lot) {
                throw new Error(
                    'E-Lot not found.'
                );
            }

            activeLotId = null;

            activeLot = null;

            selectedItems.clear();

            currentFormItems = [];

            const title = $(SELECTORS.modalTitle);

            const description = $(
                SELECTORS.modalDescription
            );

            const body = $(SELECTORS.modalBody);

            if (title) {
                title.textContent = 'E-Lot Details';
            }

            if (description) {
                description.textContent =
                    'View the saved E-Lot and its verified collection items.';
            }

            const items = Array.isArray(lot.items)
                ? lot.items
                : [];

            const itemRows = items.map(item => `
                <tr>
                    <td>
                        ${escapeHtml(item.item_name)}
                    </td>

                    <td>
                        ${number(item.actual_quantity)}
                    </td>

                    <td>
                        ${formatWeight(
                item.actual_weight_kg
            )}
                    </td>
                </tr>
            `).join('');

            if (body) {
                body.innerHTML = `
                    <section class="collector-elot-details-panel">

                        <div class="collector-elot-detail-grid">

                            <p>
                                <strong>E-Lot ID:</strong>
                                ${escapeHtml(lot.lot_code)}
                            </p>

                            <p>
                                <strong>Name:</strong>
                                ${escapeHtml(lot.title)}
                            </p>

                            <p>
                                <strong>Category:</strong>
                                ${escapeHtml(lot.category_name)}
                            </p>

                            <p>
                                <strong>Status:</strong>
                                ${escapeHtml(
                    statusLabel(lot.lot_status)
                )}
                            </p>

                            <p>
                                <strong>Total Weight:</strong>
                                ${formatWeight(
                    lot.total_weight_kg
                )}
                            </p>

                        </div>

                        <div class="collector-table-wrap">

                            <table class="collector-table">

                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Quantity</th>
                                        <th>Weight</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    ${itemRows || `
                                        <tr>
                                            <td colspan="3">
                                                No items found.
                                            </td>
                                        </tr>
                                    `}
                                </tbody>

                            </table>

                        </div>

                        <div class="collector-modal-actions">

                            <button
                                type="button"
                                class="collector-modal-button
                                collector-modal-button--secondary"
                                data-elot-modal-close>
                                Close
                            </button>

                        </div>

                    </section>
                `;
            }

            showModal();

        } catch (error) {
            console.error(error);

            alert(error.message);
        }
    };

    // ============================================================
    // POST ACTION
    // ============================================================

    const postAction = async (url, formData) => {
        const response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type':
                    'application/x-www-form-urlencoded;charset=UTF-8',
                'Accept': 'application/json'
            },
            body: formData.toString()
        });

        const contentType =
            response.headers.get('content-type') || '';

        if (!contentType.includes('application/json')) {
            throw new Error(
                'Unexpected server response. The operation was not confirmed.'
            );
        }

        let result;

        try {
            result = await response.json();
        } catch (error) {
            throw new Error(
                'Unable to read the server response.'
            );
        }

        if (!response.ok || result.success !== true) {
            throw new Error(
                result.message ||
                result.error ||
                'E-Lot operation failed.'
            );
        }

        return result;
    };

    // ============================================================
    // FORM VALIDATION
    // ============================================================

    const collectForm = () => {
        const form = $('[data-elot-form]');

        if (!form) {
            throw new Error(
                'E-Lot form is unavailable.'
            );
        }

        const title = form.querySelector(
            '[data-elot-name]'
        )?.value.trim() || '';

        const categoryId = number(
            form.querySelector(
                '[data-elot-category]'
            )?.value
        );

        const itemIds = [
            ...selectedItems
        ];

        if (!categoryId) {
            throw new Error(
                'Please select an E-Waste category.'
            );
        }

        if (!title) {
            throw new Error(
                'Please enter an E-Lot name.'
            );
        }

        if (title.length > 100) {
            throw new Error(
                'E-Lot name cannot exceed 100 characters.'
            );
        }

        if (itemIds.length === 0) {
            throw new Error(
                'Please select at least one verified item.'
            );
        }

        return {
            title,
            categoryId,
            itemIds
        };
    };

    // ============================================================
    // SAVE DRAFT
    // ============================================================

    const saveDraft = async () => {
        if (busy) {
            return;
        }

        let formValues;

        try {
            formValues = collectForm();
        } catch (error) {
            alert(error.message);

            return;
        }

        const button = $(
            '[data-elot-save-draft]'
        );

        const isEdit = activeLotId !== null;

        const url = isEdit
            ? `${API}/${encodeURIComponent(activeLotId)}/update`
            : API;

        const formData = new URLSearchParams();

        formData.append(
            'title',
            formValues.title
        );

        formData.append(
            'category_id',
            String(formValues.categoryId)
        );

        formValues.itemIds.forEach(id => {
            formData.append(
                'item_ids[]',
                String(id)
            );
        });

        formData.append(
            '_csrf_token',
            csrfToken()
        );

        busy = true;

        if (button) {
            button.disabled = true;

            button.textContent = 'Saving...';
        }

        try {
            const result = await postAction(
                url,
                formData
            );

            console.log(
                'E-Lot saved:',
                result.message
            );

            window.location.assign(API);

        } catch (error) {
            console.error(
                'Save Draft failed:',
                error
            );

            alert(error.message);

        } finally {
            busy = false;

            if (button) {
                button.disabled = false;

                button.textContent = 'Save Draft';
            }
        }
    };

    // ============================================================
    // SUBMIT E-LOT
    // ============================================================

    const submitLot = async lotId => {
        if (busy) {
            return;
        }

        const confirmed = window.confirm(
            'Submit this E-Lot for Municipal Officer verification?'
        );

        if (!confirmed) {
            return;
        }

        const formData = new URLSearchParams();

        formData.append(
            '_csrf_token',
            csrfToken()
        );

        busy = true;

        try {
            await postAction(
                `${API}/${encodeURIComponent(lotId)}/submit`,
                formData
            );

            window.location.assign(API);

        } catch (error) {
            console.error(
                'Submit E-Lot failed:',
                error
            );

            alert(error.message);

        } finally {
            busy = false;
        }
    };

    // ============================================================
    // DELETE E-LOT
    // ============================================================

    const deleteLot = async lotId => {
        if (busy) {
            return;
        }

        const confirmed = window.confirm(
            'Delete this E-Lot draft? Its allocated items will become available again.'
        );

        if (!confirmed) {
            return;
        }

        const formData = new URLSearchParams();

        formData.append(
            '_csrf_token',
            csrfToken()
        );

        busy = true;

        try {
            await postAction(
                `${API}/${encodeURIComponent(lotId)}/delete`,
                formData
            );

            window.location.assign(API);

        } catch (error) {
            console.error(
                'Delete E-Lot failed:',
                error
            );

            alert(error.message);

        } finally {
            busy = false;
        }
    };

    // ============================================================
    // CLICK EVENTS
    // ============================================================

    document.addEventListener('click', event => {
        const target = event.target;

        if (!(target instanceof Element)) {
            return;
        }

        const createButton = target.closest(
            SELECTORS.createButton
        );

        if (createButton) {
            event.preventDefault();

            openCreateModal();

            return;
        }

        const closeButton = target.closest(
            '[data-elot-modal-close]'
        );

        if (closeButton) {
            event.preventDefault();

            closeModal();

            return;
        }

        const viewButton = target.closest(
            '[data-elot-view]'
        );

        if (viewButton) {
            event.preventDefault();

            openViewModal(
                viewButton.dataset.elotView
            );

            return;
        }

        const editButton = target.closest(
            '[data-elot-edit]'
        );

        if (editButton) {
            event.preventDefault();

            openEditModal(
                editButton.dataset.elotEdit
            );

            return;
        }

        const submitButton = target.closest(
            '[data-elot-submit]'
        );

        if (submitButton) {
            event.preventDefault();

            submitLot(
                submitButton.dataset.elotSubmit
            );

            return;
        }

        const deleteButton = target.closest(
            '[data-elot-delete]'
        );

        if (deleteButton) {
            event.preventDefault();

            deleteLot(
                deleteButton.dataset.elotDelete
            );

            return;
        }
    });

    // ============================================================
    // FORM SUBMIT EVENT
    // ============================================================

    document.addEventListener('submit', event => {
        const form = event.target;

        if (!(form instanceof Element)) {
            return;
        }

        if (!form.matches('[data-elot-form]')) {
            return;
        }

        event.preventDefault();

        saveDraft();
    });

    // ============================================================
    // CHANGE EVENTS
    // ============================================================

    document.addEventListener('change', event => {
        const target = event.target;

        if (!(target instanceof Element)) {
            return;
        }

        if (target.matches(SELECTORS.statusFilter)) {
            renderLots();

            return;
        }

        if (target.matches('[data-elot-category]')) {
            selectedItems.clear();

            currentFormItems = [];

            refreshFormItems();

            renderVerifiedItems();

            return;
        }

        if (target.matches('[data-elot-item-check]')) {
            const itemId = String(target.value);

            if (target.checked) {
                selectedItems.add(itemId);
            } else {
                selectedItems.delete(itemId);
            }

            refreshFormTotals();

            renderVerifiedItems();

            return;
        }
    });

    // ============================================================
    // ESCAPE KEY
    // ============================================================

    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') {
            return;
        }

        const modal = getModal();

        if (modal && !modal.hidden) {
            event.preventDefault();

            closeModal();
        }
    });

    // ============================================================
    // INITIALIZATION
    // ============================================================

    const initialize = () => {
        if (!$(SELECTORS.page)) {
            return;
        }

        renderAll();

        console.log(
            'EcoLot-LK Collector E-Lot module initialized.'
        );
    };

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            initialize,
            { once: true }
        );
    } else {
        initialize();
    }

})();