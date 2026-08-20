<section class="elots-page">
    <div class="page-toolbar">
        <div>
            <h1>E-Lot Verification & Bids</h1>
            <p>Verify collector-created E-Lots, open approved lots for bidding, and manage recycler awards.</p>
        </div>
        <div class="pending-summary"><strong data-pending-count>0</strong><span>Pending verification</span></div>
    </div>

    <section class="elot-filter-card compact-filter-card">
        <form class="elot-filter-form" data-elot-filter-form>
            <div class="elot-filter-grid">
                <div class="form-group">
                    <label for="elot-status">E-Lot Status</label>
                    <select id="elot-status" name="elot_status">
                        <option value="">All Statuses</option>
                        <option value="PENDING_VERIFICATION">Pending Verification</option>
                        <option value="REJECTED">Rejected</option>
                        <option value="OPEN_FOR_BIDDING">Open for Bidding</option>
                        <option value="AWARDED">Awarded</option>
                        <option value="COMPLETED">Completed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="">All Categories</option>
                        <option value="DOMESTIC">Domestic E-Waste</option>
                        <option value="OFFICE">Office E-Waste</option>
                        <option value="INDUSTRIAL">Industrial E-Waste</option>
                    </select>
                </div>
            </div>
            <div class="filter-actions">
                <button type="reset" class="secondary-btn">Clear</button>
                <button type="submit" class="primary-btn">Apply Filters</button>
            </div>
        </form>
    </section>

    <section class="elots-list-card">
        <div class="elots-list-heading">
            <div>
                <h2>Collector E-Lots</h2>
                <p>Only verified E-Lots become visible to eligible recyclers.</p>
                <span class="elot-result-count officer-result-count" data-elot-result-count role="status" aria-live="polite"></span>
            </div>
        </div>
        <div class="elots-table-wrapper">
            <table class="elots-table">
                <thead><tr><th>E-Lot</th><th>Collector</th><th>Category</th><th>Items</th><th>Weight</th><th>Created</th><th>Bidding Period</th><th>Bids</th><th>Status</th><th>Winner</th><th>Action</th></tr></thead>
                <tbody data-elots-table-body></tbody>
            </table>
        </div>
        <div class="elots-empty-state officer-empty-state" data-elots-empty-state hidden>No E-Lots match the selected filters.</div>
    </section>
</section>

<div class="elot-dialog officer-dialog" data-elot-dialog hidden>
    <section class="elot-dialog-card officer-dialog-card" role="dialog" aria-modal="true" aria-labelledby="elot-dialog-title">
        <div class="elot-dialog-header officer-dialog-header">
            <div>
                <span class="dialog-eyebrow" data-dialog-eyebrow>E-Lot review</span>
                <h2 id="elot-dialog-title" data-dialog-title>Verify E-Lot</h2>
                <p data-dialog-description>Review the collector's E-Lot before opening recycler bidding.</p>
            </div>
            <button type="button" class="elot-dialog-close officer-dialog-close" aria-label="Close E-Lot dialog" data-close-dialog>×</button>
        </div>

        <div class="elot-summary-grid">
            <div><span>Collector</span><strong data-summary-collector></strong></div>
            <div><span>Category</span><strong data-summary-category></strong></div>
            <div><span>Items</span><strong data-summary-items></strong></div>
            <div><span>Total Weight</span><strong data-summary-weight></strong></div>
        </div>

        <form class="elot-review-form" data-review-form hidden>
            <div class="elot-form-grid">
                <div class="form-group">
                    <label for="review-decision">Decision</label>
                    <select id="review-decision" name="decision">
                        <option value="APPROVE">Approve and Open Bidding</option>
                        <option value="REJECT">Reject E-Lot</option>
                    </select>
                </div>
                <div class="form-group bidding-field">
                    <label for="bidding-start">Bidding Opens</label>
                    <input id="bidding-start" name="start" type="date">
                </div>
                <div class="form-group bidding-field">
                    <label for="bidding-end">Bidding Closes</label>
                    <input id="bidding-end" name="end" type="date">
                </div>
                <div class="form-group elot-title-field">
                    <label for="verification-note">Verification Note</label>
                    <textarea id="verification-note" name="note" rows="3" maxlength="500" placeholder="Reason, conditions, or verification details"></textarea>
                </div>
            </div>
            <p class="elot-form-error officer-form-error" role="alert" data-review-error hidden></p>
            <div class="elot-dialog-actions officer-dialog-actions">
                <button type="button" class="secondary-btn" data-close-dialog>Cancel</button>
                <button type="submit" class="primary-btn" data-review-submit>Approve E-Lot</button>
            </div>
        </form>

        <form class="elot-manage-form" data-manage-form hidden>
            <div class="verification-note-card" data-note-card hidden><span>Officer Note</span><strong data-note-output></strong></div>
            <fieldset class="bid-selector" data-bid-selector hidden>
                <legend>Recycler Bids</legend>
                <div data-bid-options></div>
            </fieldset>
            <div class="elot-winner-summary" data-winner-summary hidden><span>Selected Recycler</span><strong data-winner-output></strong></div>
            <p class="elot-form-error officer-form-error" role="alert" data-manage-error hidden></p>
            <div class="elot-dialog-actions officer-dialog-actions">
                <button type="button" class="secondary-btn" data-close-dialog>Close</button>
                <button type="submit" class="primary-btn" data-manage-submit hidden>Save Changes</button>
            </div>
        </form>
    </section>
</div>

<div class="elot-toast officer-toast" role="status" aria-live="polite" data-elot-toast hidden>E-Lot updated.</div>
