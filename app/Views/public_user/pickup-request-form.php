<section class="pickup-request-page">
    <div class="page-toolbar">
        <div>
            <h1>New Pickup Request</h1>
            <p>Add each e-waste item below. When adding an item you'll be able to pick its category and check it against the accepted items list.</p>
        </div>
    </div>

    <form data-pickup-request-form>
        <div class="pickup-form-sections">
            <section class="surface-card">
                <div class="card-heading">
                    <div>
                        <h2>Pickup Details</h2>
                        <p>Confirm where and when the items should be collected.</p>
                    </div>
                </div>

                <div class="form-field">
                    <label for="postal-code">Postal Code Area</label>
                    <input id="postal-code" name="postal_code" type="text" value="Pannipitiya (10230)" readonly>
                </div>
                <div class="form-field">
                    <label for="collection-date">Available Collection Date</label>
                    <input id="collection-date" name="collection_date" type="date" required>
                </div>
                <div class="form-field">
                    <label for="pickup-address">Pickup Address</label>
                    <textarea id="pickup-address" name="pickup_address" rows="2" readonly><?= htmlspecialchars($user_address ?? '123 Main Street, Pannipitiya') ?></textarea>
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
                                <th>Estimated Weight (kg)</th>
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

            <div class="form-actions">
                <a href="<?= $basePath ?>/user/dashboard" class="secondary-btn">Cancel</a>
                <button type="submit" class="primary-btn">Submit Request</button>
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
                        <option value="Domestic E-Waste">Domestic E-Waste</option>
                        <option value="Automobile E-Waste">Automobile E-Waste</option>
                        <option value="Office E-Waste">Office E-Waste</option>
                        <option value="Industrial E-Waste">Industrial E-Waste</option>
                        <option value="Medical E-Waste">Medical E-Waste</option>
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