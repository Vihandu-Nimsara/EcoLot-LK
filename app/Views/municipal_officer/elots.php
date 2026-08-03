<section class="elots-page">

    <div class="page-toolbar">

        <div>
            <h1>E-Lot & Bid Review</h1>

            <p>
                Create E-Lots from verified collection items and manage recycler bidding.
            </p>
        </div>

        <div class="toolbar-actions">

            <button
                type="button"
                class="primary-btn"
                data-open-create-elot>
                Create E-Lot
            </button>

            <button
                type="button"
                class="secondary-btn"
                data-open-item-pool>
                Verified Item Pool
            </button>

        </div>

    </div>

    <section class="elot-filter-card compact-filter-card">

        <form class="elot-filter-form" data-elot-filter-form>

            <div class="elot-filter-grid">

                <div class="form-group">

                    <label for="elot-status">
                        E-Lot Status
                    </label>

                    <select id="elot-status" name="elot_status">

                        <option value="">
                            All Statuses
                        </option>

                        <option value="OPEN_FOR_BIDDING">
                            Open for Bidding
                        </option>

                        <option value="AWARDED">
                            Awarded
                        </option>

                        <option value="COMPLETED">
                            Completed
                        </option>

                    </select>

                </div>

                <div class="form-group">

                    <label for="category">
                        Category
                    </label>

                    <select id="category" name="category">

                        <option value="">
                            All Categories
                        </option>

                        <option value="DOMESTIC">
                            Domestic E-Waste
                        </option>

                        <option value="OFFICE">
                            Office E-Waste
                        </option>

                        <option value="INDUSTRIAL">
                            Industrial E-Waste
                        </option>

                    </select>

                </div>

            </div>

            <div class="filter-actions">

                <button
                    type="reset"
                    class="secondary-btn">
                    Clear
                </button>

                <button
                    type="submit"
                    class="primary-btn">
                    Apply Filters
                </button>

            </div>

        </form>

    </section>

    <section class="elots-list-card">

        <div class="elots-list-heading">

            <div>

                <h2>E-Lots</h2>

                <p>
                    Review bidding progress and manage awarded and completed E-Lots.
                </p>

                <span class="elot-result-count officer-result-count" data-elot-result-count role="status" aria-live="polite">3 E-Lots shown</span>

            </div>

        </div>

        <div class="elots-table-wrapper">

            <table class="elots-table">

                <thead>

                    <tr>
                        <th>E-Lot Code</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Items</th>
                        <th>Total Weight</th>
                        <th>Bidding Period</th>
                        <th>Bids</th>
                        <th>Status</th>
                        <th>Winner</th>
                        <th>Action</th>
                    </tr>

                </thead>

                <tbody data-elots-table-body>

                    <tr
                        data-elot-code="EL-001"
                        data-elot-title="Domestic E-Waste Lot"
                        data-category="DOMESTIC"
                        data-elot-status="OPEN_FOR_BIDDING"
                    >

                        <td>
                            EL-001
                        </td>

                        <td>
                            Domestic E-Waste Lot
                        </td>

                        <td>
                            Domestic E-Waste
                        </td>

                        <td>
                            2
                        </td>

                        <td>
                            5.50 kg
                        </td>

                        <td>
                            03 Aug – 09 Aug 2026
                        </td>

                        <td>
                            2
                        </td>

                        <td>

                            <span class="elot-status open">
                                OPEN FOR BIDDING
                            </span>

                        </td>

                        <td>

                            <span class="not-assigned">
                                Not Selected
                            </span>

                        </td>

                        <td>

                            <button
                                type="button"
                                class="elot-action-btn primary-action">
                                Review Bids
                            </button>

                        </td>

                    </tr>

                    <tr
                        data-elot-code="EL-002"
                        data-elot-title="Office E-Waste Lot"
                        data-category="OFFICE"
                        data-elot-status="AWARDED"
                    >

                        <td>
                            EL-002
                        </td>

                        <td>
                            Office E-Waste Lot
                        </td>

                        <td>
                            Office E-Waste
                        </td>

                        <td>
                            2
                        </td>

                        <td>
                            9.00 kg
                        </td>

                        <td>
                            06 Jul – 09 Jul 2026
                        </td>

                        <td>
                            2
                        </td>

                        <td>

                            <span class="elot-status awarded">
                                AWARDED
                            </span>

                        </td>

                        <td>
                            GreenCycle Lanka (Pvt) Ltd
                        </td>

                        <td>

                            <button
                                type="button"
                                class="elot-action-btn secondary-action">
                                Manage
                            </button>

                        </td>

                    </tr>

                    <tr
                        data-elot-code="EL-003"
                        data-elot-title="Industrial E-Waste Lot"
                        data-category="INDUSTRIAL"
                        data-elot-status="COMPLETED"
                    >

                        <td>
                            EL-003
                        </td>

                        <td>
                            Industrial E-Waste Lot
                        </td>

                        <td>
                            Industrial E-Waste
                        </td>

                        <td>
                            1
                        </td>

                        <td>
                            10.00 kg
                        </td>

                        <td>
                            06 Jul – 09 Jul 2026
                        </td>

                        <td>
                            0
                        </td>

                        <td>

                            <span class="elot-status completed">
                                COMPLETED
                            </span>

                        </td>

                        <td>
                            Ceylon Circular Metals (Pvt) Ltd
                        </td>

                        <td>

                            <button
                                type="button"
                                class="elot-action-btn secondary-action">
                                View
                            </button>

                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

        <div class="elots-empty-state officer-empty-state" data-elots-empty-state hidden>
            No E-Lots match the selected filters.
        </div>

    </section>

</section>

<div class="elot-dialog officer-dialog" data-elot-dialog hidden>
    <section class="elot-dialog-card officer-dialog-card" role="dialog" aria-modal="true" aria-labelledby="elot-dialog-title">
        <div class="elot-dialog-header officer-dialog-header">
            <div>
                <span class="dialog-eyebrow" data-elot-dialog-eyebrow>E-Lot workflow</span>
                <h2 id="elot-dialog-title" data-elot-dialog-title>Create E-Lot</h2>
                <p data-elot-dialog-description>Select verified items and set the recycler bidding period.</p>
            </div>
            <button type="button" class="elot-dialog-close officer-dialog-close" aria-label="Close E-Lot dialog" data-close-elot-dialog>×</button>
        </div>

        <form class="elot-create-form" data-elot-create-form hidden>
            <div class="elot-form-grid">
                <div class="form-group elot-title-field">
                    <label for="new-elot-title">E-Lot Title</label>
                    <input id="new-elot-title" name="title" type="text" maxlength="80" required>
                </div>
                <div class="form-group">
                    <label for="new-elot-category">Category</label>
                    <select id="new-elot-category" name="category" required>
                        <option value="">Select category</option>
                        <option value="DOMESTIC">Domestic E-Waste</option>
                        <option value="OFFICE">Office E-Waste</option>
                        <option value="INDUSTRIAL">Industrial E-Waste</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="new-elot-start">Bidding Opens</label>
                    <input id="new-elot-start" name="start" type="date" required>
                </div>
                <div class="form-group">
                    <label for="new-elot-end">Bidding Closes</label>
                    <input id="new-elot-end" name="end" type="date" required>
                </div>
            </div>

            <fieldset class="verified-item-selector">
                <legend>Verified Items</legend>
                <p>Select items from the same category as this E-Lot.</p>
                <div data-create-item-options></div>
            </fieldset>

            <p class="elot-form-error officer-form-error" role="alert" data-create-elot-error hidden></p>
            <div class="elot-dialog-actions officer-dialog-actions">
                <button type="button" class="secondary-btn" data-close-elot-dialog>Cancel</button>
                <button type="submit" class="primary-btn">Create E-Lot</button>
            </div>
        </form>

        <section class="verified-pool-section" data-verified-pool-section hidden>
            <div class="verified-pool-table-wrapper">
                <table class="verified-pool-table">
                    <thead><tr><th>Item ID</th><th>Item</th><th>Category</th><th>Weight</th><th>Availability</th></tr></thead>
                    <tbody data-verified-pool-body></tbody>
                </table>
            </div>
            <div class="elot-dialog-actions officer-dialog-actions">
                <button type="button" class="primary-btn" data-close-elot-dialog>Done</button>
            </div>
        </section>

        <form class="elot-manage-form" data-elot-manage-form hidden>
            <div class="elot-summary-grid">
                <div><span>Category</span><strong data-dialog-elot-category></strong></div>
                <div><span>Items</span><strong data-dialog-elot-items></strong></div>
                <div><span>Total Weight</span><strong data-dialog-elot-weight></strong></div>
                <div><span>Bidding Period</span><strong data-dialog-elot-period></strong></div>
            </div>

            <fieldset class="bid-selector" data-bid-selector>
                <legend>Recycler Bids</legend>
                <div data-bid-options></div>
            </fieldset>

            <div class="form-group" data-manage-status-field hidden>
                <label for="manage-elot-status">E-Lot Status</label>
                <select id="manage-elot-status" name="status">
                    <option value="AWARDED">AWARDED</option>
                    <option value="COMPLETED">COMPLETED</option>
                </select>
            </div>

            <div class="elot-winner-summary" data-elot-winner-summary hidden>
                <span>Selected Recycler</span>
                <strong data-dialog-elot-winner></strong>
            </div>

            <p class="elot-form-error officer-form-error" role="alert" data-manage-elot-error hidden></p>
            <div class="elot-dialog-actions officer-dialog-actions">
                <button type="button" class="secondary-btn" data-close-elot-dialog>Cancel</button>
                <button type="submit" class="primary-btn" data-manage-elot-submit>Save Changes</button>
            </div>
        </form>
    </section>
</div>

<div class="elot-toast officer-toast" role="status" aria-live="polite" data-elot-toast hidden>E-Lot changes saved in this browser.</div>
