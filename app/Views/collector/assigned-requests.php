<section class="collector-elots-page">
    <div class="page-toolbar">
        <div>
            <h1>My E-Lots</h1>
            <p>Create E-Lots from your verified collected items and track the municipal officer's decision.</p>
        </div>
    </div>

    <div class="collector-summary-grid elot-summary-grid">
        <article class="collector-summary-card">
            <span>Total E-Lots</span>
            <strong data-sum-total-elots>0</strong>
        </article>
        <article class="collector-summary-card">
            <span>Draft</span>
            <strong data-sum-draft-elots>0</strong>
        </article>
        <article class="collector-summary-card">
            <span>Submitted</span>
            <strong data-sum-submitted-elots>0</strong>
        </article>
        <article class="collector-summary-card">
            <span>Available Verified Items</span>
            <strong data-sum-available-items>0</strong>
        </article>
    </div>

    <section class="surface-card verified-pool-table-card">
        <div class="card-heading card-heading-row">
            <div>
                <h2>Verified Items Pool</h2>
                <p>Items a municipal officer has already verified and that are ready to be grouped into an E-Lot.</p>
            </div>
            <button type="button" class="primary-btn" data-open-create-elot>Create E-Lot</button>
        </div>
        <div class="table-wrapper">
            <table class="requests-table verified-items-table">
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Total Quantity</th>
                        <th>Total Weight</th>
                        <th>Category</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody data-verified-pool-body></tbody>
            </table>
        </div>
        <div class="record-list-empty" data-verified-pool-empty hidden>No verified items available right now. Verified items appear here once a municipal officer verifies your submitted collection records on the Assigned Routes page.</div>
    </section>

    <section class="record-section" data-draft-elots-section>
        <div class="record-section-header">
            <h3>My E-Lots — Draft</h3>
            <p>Draft E-Lots stay here until you submit them for officer verification.</p>
        </div>
        <div class="table-wrapper">
            <table class="requests-table elots-mini-table">
                <thead>
                    <tr>
                        <th>E-Lot ID</th>
                        <th>E-Lot Name</th>
                        <th>Category</th>
                        <th>Total Weight</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody data-draft-elots-body></tbody>
            </table>
        </div>
        <div class="record-list-empty" data-draft-elots-empty hidden>No draft E-Lots yet. Create one from the Verified Items Pool above.</div>
    </section>

    <section class="record-section" data-submitted-elots-section>
        <div class="record-section-header">
            <h3>My E-Lots — Submitted</h3>
            <p>Submitted E-Lots stay visible here — nothing is removed unless you delete it.</p>
        </div>
        <div class="table-wrapper">
            <table class="requests-table elots-mini-table">
                <thead>
                    <tr>
                        <th>E-Lot ID</th>
                        <th>E-Lot Name</th>
                        <th>Category</th>
                        <th>Total Weight</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody data-submitted-elots-body></tbody>
            </table>
        </div>
        <div class="record-list-empty" data-submitted-elots-empty hidden>No submitted E-Lots yet.</div>
    </section>
</section>

<div class="collector-modal-overlay" data-elot-modal hidden>
    <section class="collector-modal-card modal-card-wide" role="dialog" aria-modal="true" aria-labelledby="elot-modal-title">
        <button type="button" class="modal-close" data-close-elot-modal aria-label="Close">×</button>
        <h2 id="elot-modal-title" data-elot-modal-title>Create E-Lot</h2>

        <div class="form-field">
            <label>E-Lot Category *</label>
            <input type="text" data-elot-category placeholder="e.g. Plastic, Paper, Consumer Electronics">
        </div>
        <div class="form-field">
            <label>E-Lot Name *</label>
            <input type="text" data-elot-name placeholder="e.g. Plastic PET Bottles – May 20">
        </div>
        <div class="form-field">
            <label>Description (Optional)</label>
            <textarea rows="2" data-elot-description placeholder="Collected PET bottles from verified items."></textarea>
        </div>

        <fieldset class="verified-item-selector">
            <legend>Select Verified Items</legend>
            <div data-elot-item-options></div>
            <div class="item-selector-empty" data-elot-item-empty hidden>No verified items available to add.</div>
        </fieldset>

        <div class="total-weight-box">
            <span>Total Selected Weight</span>
            <strong data-elot-selected-weight>0.00 kg</strong>
        </div>

        <p class="elot-form-error" role="alert" data-elot-form-error hidden></p>

        <div class="modal-actions-row" data-elot-modal-actions>
            <button type="button" class="secondary-btn" data-close-elot-modal>Cancel</button>
            <button type="button" class="primary-btn" data-save-elot-draft>Create E-Lot (Draft)</button>
        </div>
    </section>
</div>