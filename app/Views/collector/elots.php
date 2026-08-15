<section class="collector-elots-page">
    <div class="page-toolbar">
        <div>
            <h1>My E-Lots</h1>
            <p>Create E-Lots from verified collected items and follow the municipal officer's decision.</p>
        </div>
        <div class="toolbar-actions">
            <button type="button" class="secondary-btn" data-open-item-pool>Verified Item Pool</button>
            <button type="button" class="primary-btn" data-open-create-elot>Create E-Lot</button>
        </div>
    </div>

    <div class="elot-summary-cards" aria-label="E-Lot summary">
        <article class="elot-summary-card"><span>Total E-Lots</span><strong data-total-count>0</strong><small>Created by this collector</small></article>
        <article class="elot-summary-card pending"><span>Pending Verification</span><strong data-pending-count>0</strong><small>Waiting for officer review</small></article>
        <article class="elot-summary-card open"><span>Open for Bidding</span><strong data-open-count>0</strong><small>Available to recyclers</small></article>
        <article class="elot-summary-card available"><span>Available Items</span><strong data-available-count>0</strong><small>Verified and not yet grouped</small></article>
    </div>

    <section class="surface-card elots-card">
        <div class="card-heading">
            <div><h2>Created E-Lots</h2><p>Submitted E-Lots cannot receive bids until an officer approves them.</p></div>
        </div>

        <form class="elot-filters" data-elot-filters>
            <label>Search
                <input name="search" type="search" placeholder="Lot code or title">
            </label>
            <label>Status
                <select name="status">
                    <option value="">All statuses</option>
                    <option value="PENDING_VERIFICATION">Pending verification</option>
                    <option value="REJECTED">Rejected</option>
                    <option value="OPEN_FOR_BIDDING">Open for bidding</option>
                    <option value="AWARDED">Awarded</option>
                    <option value="COMPLETED">Completed</option>
                </select>
            </label>
            <button type="reset" class="secondary-btn">Clear</button>
        </form>

        <div class="elots-table-wrapper">
            <table class="elots-table">
                <thead><tr><th>E-Lot</th><th>Category</th><th>Items</th><th>Total Weight</th><th>Created</th><th>Status</th><th>Officer Note</th></tr></thead>
                <tbody data-elots-body></tbody>
            </table>
        </div>
        <div class="elots-empty" data-elots-empty hidden>No E-Lots match the selected filters.</div>
    </section>
</section>

<div class="elot-dialog" data-elot-dialog hidden>
    <section class="elot-dialog-card" role="dialog" aria-modal="true" aria-labelledby="collector-elot-dialog-title">
        <div class="elot-dialog-header">
            <div>
                <span data-dialog-eyebrow>New E-Lot</span>
                <h2 id="collector-elot-dialog-title" data-dialog-title>Create E-Lot</h2>
                <p data-dialog-description>Select verified items from one category and submit them for officer verification.</p>
            </div>
            <button type="button" class="dialog-close" aria-label="Close E-Lot dialog" data-close-dialog>×</button>
        </div>

        <form data-create-form>
            <div class="elot-form-grid">
                <label class="title-field">E-Lot Title<input name="title" type="text" maxlength="80" placeholder="e.g. Office Equipment Lot" required></label>
                <label>Category
                    <select name="category" required>
                        <option value="">Select category</option>
                        <option value="DOMESTIC">Domestic E-Waste</option>
                        <option value="OFFICE">Office E-Waste</option>
                        <option value="INDUSTRIAL">Industrial E-Waste</option>
                    </select>
                </label>
            </div>
            <fieldset class="verified-item-selector">
                <legend>Verified Items</legend>
                <p>Select one or more available items from the chosen category.</p>
                <div data-item-options></div>
                <div class="item-selector-empty" data-item-selector-empty hidden>No available verified items in this category.</div>
            </fieldset>
            <p class="elot-form-error" role="alert" data-create-error hidden></p>
            <div class="dialog-actions">
                <button type="button" class="secondary-btn" data-close-dialog>Cancel</button>
                <button type="submit" class="primary-btn">Submit for Verification</button>
            </div>
        </form>

        <section data-item-pool hidden>
            <div class="item-pool-table-wrapper">
                <table class="item-pool-table">
                    <thead><tr><th>Item ID</th><th>Item</th><th>Category</th><th>Weight</th><th>Availability</th></tr></thead>
                    <tbody data-item-pool-body></tbody>
                </table>
            </div>
            <div class="dialog-actions"><button type="button" class="primary-btn" data-close-dialog>Done</button></div>
        </section>
    </section>
</div>

<div class="elot-toast" role="status" aria-live="polite" data-elot-toast hidden>E-Lot submitted for officer verification.</div>
