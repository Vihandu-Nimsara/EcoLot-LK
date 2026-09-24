<div class="content">
    <p class="breadcrumb"><a href="<?= $basePath ?>/user/dashboard">Dashboard</a> &nbsp;›&nbsp; My Requests</p>
    <h1 class="title">My Requests</h1>
    <p class="subtitle">View and track all your pickup requests.</p>

    <?php if (!empty($errorMessage)): ?>
        <div class="form-alert form-alert-error" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (!empty($successMessage)): ?>
        <div class="form-alert form-alert-success" role="status"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="tabs">
            <button type="button" class="tab active">All</button>
            <button type="button" class="tab">Pending</button>
            <button type="button" class="tab">Completed</button>
            <button type="button" class="tab">Cancelled</button>
            <button type="button" class="tab">Rejected</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Estimated Total Weight</th>
                    <th>Quantity</th>
                    <th>Condition</th>
                    <th>Status</th>
                    <th style="text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center;padding:24px;color:#5f7268;">
                            You haven't submitted any pickup requests yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $request): ?>
                        <tr data-request-id="<?= htmlspecialchars($request['code'], ENT_QUOTES, 'UTF-8') ?>"
                            data-request-pk="<?= (int) $request['request_id'] ?>"
                            data-schedule-id="<?= (int) $request['schedule_id'] ?>"
                            data-editable="<?= $request['is_editable'] ? '1' : '0' ?>"
                            data-status="<?= htmlspecialchars($request['status_label'], ENT_QUOTES, 'UTF-8') ?>"
                            data-submitted-date="<?= htmlspecialchars($request['submitted_date'], ENT_QUOTES, 'UTF-8') ?>"
                            data-collection-date="<?= htmlspecialchars($request['collection_date_iso'], ENT_QUOTES, 'UTF-8') ?>"
                            data-collection-date-label="<?= htmlspecialchars($request['collection_date'], ENT_QUOTES, 'UTF-8') ?>"
                            data-postal="<?= htmlspecialchars($request['postal_label'], ENT_QUOTES, 'UTF-8') ?>"
                            data-address="<?= htmlspecialchars($request['address'], ENT_QUOTES, 'UTF-8') ?>"
                            data-items='<?= htmlspecialchars($request['items_json'], ENT_QUOTES, 'UTF-8') ?>'>
                            <td><a href="javascript:void(0)" class="view-link" data-view-btn><?= htmlspecialchars($request['code'], ENT_QUOTES, 'UTF-8') ?></a></td>
                            <td><?= htmlspecialchars($request['submitted_date'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($request['category_summary'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($request['total_weight_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int) $request['total_quantity'] ?></td>
                            <td><?= htmlspecialchars($request['condition_summary'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge <?= htmlspecialchars($request['history_badge_class'], ENT_QUOTES, 'UTF-8') ?>"><span class="dot"></span><?= htmlspecialchars($request['status_label'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td>
                                <div class="action-buttons">
                                    <button type="button" class="action-btn view-btn" title="View Request" data-view-btn>
                                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                    <?php if ($request['is_editable']): ?>
                                        <button type="button" class="action-btn edit-btn" title="Edit Request" data-edit-request>
                                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </button>
                                        <button type="button" class="action-btn delete-btn" title="Cancel Request" data-delete-request>
                                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                        </button>
                                    <?php else: ?>
                                        <span class="action-btn action-btn-disabled" title="This request can no longer be edited">
                                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.9" y1="4.9" x2="19.1" y2="19.1"/></svg>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="pagination">
            <span data-request-count>Showing <?= empty($requests) ? 0 : 1 ?> to <?= count($requests) ?> of <?= count($requests) ?> requests</span>

        </div>
    </div>
</div>

<!-- ═══ View Request Modal ═══ -->
<div id="viewModal" class="modal-overlay">
    <div class="view-modal-box">

        <!-- Header -->
        <div class="view-modal-header">
            <div>
                <h3 class="view-modal-title">Pickup Request Details</h3>
                <span id="viewRequestId" class="view-req-badge"></span>
            </div>
            <button type="button" class="view-modal-close" data-close-view-modal>&times;</button>
        </div>

        <!-- Section 1: Pickup Details -->
        <div class="view-section">
            <div class="view-section-heading">
                <span class="view-section-number">1</span>
                Pickup Details
            </div>
            <div class="view-fields-row">
                <div class="view-field">
                    <label>Postal Code Area</label>
                    <p id="viewPostal"></p>
                </div>
                <div class="view-field">
                    <label>Available Collection Date</label>
                    <p id="viewCollectionDate"></p>
                </div>
                <div class="view-field">
                    <label>Status</label>
                    <div id="viewStatus"></div>
                </div>
            </div>
            <div class="view-field view-field-full">
                <label>Pickup Address</label>
                <p id="viewAddress"></p>
            </div>
        </div>

        <!-- Section 2: E-waste Items -->
        <div class="view-section">
            <div class="view-section-heading">
                <span class="view-section-number">2</span>
                E-waste Items
            </div>
            <div class="view-items-wrap">
                <table class="view-items-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Total Row Weight (kg)</th>
                            <th>Condition</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody id="viewItemsBody"></tbody>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="view-modal-footer">
            <button type="button" class="btn-modal btn-cancel" data-close-view-modal>Close</button>
        </div>
    </div>
</div>

<!-- ═══ Delete Confirmation Modal ═══ -->
<div id="deleteModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-icon">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#c0392b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <h3 class="modal-title">Cancel Request?</h3>
        <p class="modal-desc">Cancel this pickup request? Its details will remain in your history.</p>
        <div class="modal-actions">
            <button type="button" class="btn-modal btn-cancel" data-close-delete-modal>Keep Request</button>
            <button type="button" id="confirmDeleteBtn" class="btn-modal btn-confirm-delete">Cancel Request</button>        </div>
    </div>
</div>

<form id="deleteRequestForm" method="post" action="" style="display:none;">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
</form>


<!-- ═══ Edit Request Modal ═══ -->
<div id="editModal" class="modal-overlay">
    <div class="view-modal-box">
        <div class="view-modal-header">
            <div>
                <h3 class="view-modal-title">Edit Pickup Request</h3>
                <span id="editRequestId" class="view-req-badge"></span>
            </div>
            <button type="button" class="view-modal-close" data-close-edit-modal>&times;</button>
        </div>

        <form id="editRequestForm" method="post" action="">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <!-- Section 1: Pickup Details -->
            <div class="view-section">
                <div class="view-section-heading">
                    <span class="view-section-number">1</span>
                    Pickup Details
                </div>
                <div class="view-fields-row">
                    <div class="view-field">
                        <label>Postal Code Area</label>
                        <input type="text" class="form-input-readonly" id="editPostal" readonly>                    </div>
                    <div class="view-field edit-collection-date-field">
                        <label for="editCollectionDate">Available Collection Date</label>
                        <select class="form-input-editable" id="editCollectionDate" name="schedule_id" aria-describedby="editScheduleHint" required></select>
                        <small id="editScheduleHint" role="status"></small>
                    </div>
                </div>
                <div class="view-field view-field-full" style="margin-top:0;">
                    <label>Pickup Address</label>
                    <textarea class="form-input-readonly" id="editAddress" rows="2" readonly></textarea>                </div>
            </div>

            <!-- Section 2: E-waste Items (editable table) -->
            <div class="view-section">
                <div class="view-section-heading">
                    <span class="view-section-number">2</span>
                    E-waste Items
                </div>

                <div class="view-items-wrap" style="margin-bottom:14px;">
                    <table class="view-items-table edit-items-table" id="editItemsTable">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Total Row Weight (kg)</th>
                                <th>Condition</th>
                                <th>Note</th>
                                <th class="col-delete"></th>
                            </tr>
                        </thead>
                        <tbody id="editItemsBody">
                            <!-- rows populated by JS from row data -->
                        </tbody>
                    </table>
                    <p id="editEmptyMsg" class="edit-empty-msg">No items yet. Click "Add an E-waste Item" below.</p>
                </div>

                <!-- Add item button -->
                <button type="button" class="edit-add-item-btn" data-open-edit-item-modal>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    Add an E-waste Item
                </button>
            </div>

            <!-- Footer -->
            <div class="view-modal-footer">
                <button type="button" class="btn-modal btn-cancel" data-close-edit-modal>Cancel</button>
                <button type="submit" class="btn-modal btn-save">Save Changes</button>
            </div>

        </form>
    </div>
</div>

<!-- ═══ Edit Modal: Add Item sub-modal ═══ -->
<div id="editItemModal" class="modal-overlay" style="z-index:100000;">
    <div class="modal-box" role="dialog" aria-modal="true">
        <div class="view-modal-header" style="padding:16px 20px 14px;">
            <h3 style="margin:0;font-size:16px;">Add an E-waste Item</h3>
            <button type="button" class="view-modal-close" data-close-edit-item-modal>&times;</button>
        </div>
        <div style="padding:16px 20px;">
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--public-muted);margin-bottom:6px;">Category</label>
                <select id="editModalCategory" style="width:100%;padding:9px 10px;border:1px solid var(--public-border);border-radius:8px;font-size:14px;">
                    <option value="">Select category</option>
                    <?php foreach (array_keys($catalogue) as $category): ?><option><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--public-muted);margin-bottom:6px;">Item</label>
                <div id="editModalItemsList" class="modal-items-list">
                    <p class="modal-placeholder">Select a category to see its items.</p>
                </div>
            </div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px;padding:14px 20px;border-top:1px solid var(--public-border);">
            <button type="button" class="btn-modal btn-cancel" style="flex:0 0 auto;min-width:90px;" data-close-edit-item-modal>Cancel</button>
            <button type="button" class="btn-modal btn-save" style="flex:0 0 auto;min-width:90px;" id="editModalOkBtn" disabled data-confirm-edit-item>OK</button>
        </div>
    </div>
</div>
<script type="application/json" id="scheduleOptionsData"><?= $scheduleOptionsJson ?? '[]' ?></script>
<script type="application/json" id="pickupCatalogue"><?= json_encode($catalogue ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script type="application/json" id="pickupDraft"><?= json_encode($draft ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
