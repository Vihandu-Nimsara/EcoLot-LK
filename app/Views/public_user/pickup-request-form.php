<section class="pickup-request-page">
    <div class="page-toolbar">
        <div>
            <h1>New Pickup Request</h1>
            <p>Add each e-waste item below. When adding an item you'll be able to pick its category and check it against the accepted items list.</p>
        </div>
    </div>

    <?php if (!empty($errorMessage)): ?>
        <div class="form-alert form-alert-error" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (!empty($successMessage)): ?>
        <div class="form-alert form-alert-success" role="status"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form data-pickup-request-form method="post" action="<?= $basePath ?>/user/new-request">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <div class="pickup-form-sections">
            <section class="surface-card">
                <div class="card-heading">
                    <div>
                        <h2>Pickup Details</h2>
                        <p>Confirm where and when the items should be collected.</p>
                    </div>
                </div>

                <div class="form-field">
                    <?php if ($postalArea): ?><p>Postal area: <?= htmlspecialchars($postalArea['area_name'] . ' (' . $postalArea['postal_code'] . ')', ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                    <label for="collection-schedule">Available Collection Date</label>
                    <?php if (!empty($schedules)): ?>
                        <select id="collection-schedule" name="schedule_id" required>
                            <option value="">Select a collection date</option>
                            <?php foreach ($schedules as $schedule): ?>
                                <option value="<?= (int) $schedule['schedule_id'] ?>">
                                    <?= htmlspecialchars(date('l, d M Y', strtotime((string) $schedule['collection_date'])) . ' — Requests close ' . $schedule['request_cutoff_at'] . ' (Sri Lanka)', ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <select id="collection-schedule" disabled>
                            <option>No open collection dates for your area yet</option>
                        </select>
                        <p class="field-hint">Your municipal office hasn't opened a collection schedule for your area yet. Please check back later.</p>
                    <?php endif; ?>
                </div>
                <div class="form-field">
                    <label for="pickup-address">Pickup Address</label>
                    <textarea id="pickup-address" name="pickup_address" rows="2" readonly><?= htmlspecialchars($address ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </section>

            <section class="surface-card">
                <div class="card-heading">
                    <div>
                        <h2>E-waste Items</h2>
                        <p>Add every item you'd like collected, along with its condition and estimated weight.</p>
                    </div>
                </div>

                <div class="items-table-wrap">
                    <table class="data-table items-table" id="itemsTable" data-items-table>
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
                        <tbody data-items-table-body>
                            <!-- rows added dynamically via JS -->
                        </tbody>
                    </table>
                    <p class="empty-table-msg" data-empty-table-msg>No items added yet. Click &ldquo;Add an E-waste Item&rdquo; below to get started.</p>
                </div>

                <button type="button" class="secondary-btn add-item-btn" data-open-item-modal>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    Add an E-waste Item
                </button>
            </section>

            <?php if (empty($catalogue)): ?><p>No collectable items are currently available. Please contact your municipal office.</p><?php endif; ?>
            <?php if (trim($address ?? '') === ''): ?><p>Your pickup address is missing. Please complete your profile through the municipal office before submitting.</p><?php endif; ?>
            <p>Enter the combined estimated weight for all units in each row. Items requiring review will be checked by an officer.</p>
            <div class="form-actions">
                <a href="<?= $basePath ?>/user/dashboard" class="secondary-btn">Cancel</a>
                <button type="submit" class="primary-btn" <?= empty($schedules) || empty($catalogue) || trim($address ?? '') === '' ? 'disabled' : '' ?>>Submit Request</button>
            </div>
        </div>
    </form>

    <!-- Add E-waste Item modal -->
    <div class="modal-overlay" id="itemModal" data-item-modal hidden>
        <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="itemModalTitle">
            <div class="modal-header">
                <h3 id="itemModalTitle">Add an E-waste Item</h3>
                <button type="button" class="close-modal-btn" data-close-item-modal aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-field">
                    <label for="modal-category-select">Category</label>
                    <select id="modal-category-select" data-modal-category>
                        <option value="">Select category</option>
                        <?php foreach (array_keys($catalogue) as $category): ?><option><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field" style="margin-bottom:0;">
                    <label>Item</label>
                    <div class="modal-items-list" data-modal-items-list>
                        <p class="modal-placeholder">Select a category to see its items.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="secondary-btn" data-close-item-modal>Cancel</button>
                <button type="button" class="primary-btn" id="modalOkBtn" data-confirm-add-item disabled>OK</button>
            </div>
        </div>
    </div>
</section>
<script type="application/json" id="pickupCatalogue"><?= json_encode($catalogue ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script type="application/json" id="pickupDraft"><?= json_encode($draft ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
