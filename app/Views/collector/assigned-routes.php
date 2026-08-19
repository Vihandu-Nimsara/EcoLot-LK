<section class="assigned-routes-page">
    <div class="page-toolbar">
        <div>
            <h1>Assigned Schedules</h1>
            <p>Review scheduled zone collections and manage collection records.</p>
        </div>
    </div>

    <!-- SCHEDULES VIEW -->
    <div data-view="schedules">
        <div class="schedule-grid" data-schedule-grid></div>
    </div>

    <!-- REQUESTS TABLE VIEW -->
    <div data-view="requests" hidden>
        <button type="button" class="secondary-btn back-btn" data-back-to-schedules>← Back to Schedules</button>

        <div class="requests-table-header">
            <h2 data-schedule-title></h2>
            <p data-schedule-subline></p>
        </div>

        <!-- Master requests table -->
        <div class="surface-card table-wrapper">
            <table class="requests-table">
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Address</th>
                        <th>Item Categories</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody data-requests-body></tbody>
            </table>
        </div>

        <!-- Counts -->
        <div class="collector-summary-grid schedule-summary-grid">
            <article class="collector-summary-card">
                <span>Total Requests</span>
                <strong data-sum-total>0</strong>
            </article>
            <article class="collector-summary-card">
                <span>Submitted</span>
                <strong data-sum-submitted>0</strong>
            </article>
            <article class="collector-summary-card draft-card" data-scroll-to-draft role="button" tabindex="0">
                <span>Draft</span>
                <strong data-sum-draft>0</strong>
                <small>Jump to drafts ↓</small>
            </article>
            <article class="collector-summary-card">
                <span>Pending</span>
                <strong data-sum-pending>0</strong>
            </article>
        </div>

        <!-- Draft Records (persistent, on-page) -->
        <section class="record-section" data-draft-section>
            <div class="record-section-header">
                <h3>Draft Collection Records</h3>
                <p>Records saved as draft stay here until you submit them.</p>
            </div>
            <div class="record-list" data-draft-records-list></div>
        </section>

        <!-- Submitted Records (persistent, on-page) -->
        <section class="record-section" data-submitted-section>
            <div class="record-section-header">
                <h3>Submitted Collection Records</h3>
                <p>Submitted records stay visible here — nothing is removed unless you delete it.</p>
            </div>
            <div class="record-list" data-submitted-records-list></div>
        </section>

        <!-- Verification status (auto-simulated, collector cannot verify) -->
        <section class="verification-card" data-verification-card hidden>
            <div class="verification-card-text">
                <h3>Verification Status</h3>
                <p>Verified <strong data-verified-count>0</strong> out of <strong data-submitted-count-label>0</strong> submitted requests.</p>
                <small>Verification is carried out by the municipal officer — this updates automatically.</small>
            </div>
        </section>

        <!-- Zone Verified Pool -->
        <section class="surface-card verified-pool-card">
            <div class="card-heading">
                <div><h2>Verified Items — This Zone</h2><p>Available for E-Lot creation.</p></div>
            </div>
            <div class="verified-pool-grid">
                <div class="verified-pool-stat"><span>Verified Quantity</span><strong data-pool-total-quantity>0</strong></div>
                <div class="verified-pool-stat"><span>Verified Weight</span><strong data-pool-total-weight>0 kg</strong></div>
                <div class="verified-pool-stat"><span>Categories</span><strong data-pool-categories>0</strong></div>
                <button type="button" class="primary-btn" data-go-to-elots>Create My E-Lot</button>
            </div>
        </section>
    </div>
</section>

<!-- COLLECTION RECORD MODAL (used for fill / edit / view) -->
<div class="collector-modal-overlay" data-record-modal hidden>
    <section class="collector-modal-card modal-card-wide" role="dialog" aria-modal="true">
        <button type="button" class="modal-close" data-close-record-modal>×</button>
        <h2>Collection Record — <span data-record-request-id></span></h2>

        <div class="record-readonly-block">
            <h3>Original Request (Read-only)</h3>
            <div class="modal-detail-grid">
                <div><span>Address</span><strong data-record-address></strong></div>
                <div><span>Item Category</span><strong data-record-categories></strong></div>
                <div><span>Requested Quantity</span><strong data-record-req-qty></strong></div>
            </div>
            <p class="requested-weight-note">Actual weight is not requested up front — please weigh the items on-site and enter the result below.</p>
        </div>

        <div class="record-editable-block">
            <h3>Collection Record</h3>
            <div class="record-form-grid">
                <div class="form-field">
                    <label>Actual Quantity *</label>
                    <input type="number" min="0" data-record-actual-qty placeholder="pcs">
                </div>
                <div class="form-field">
                    <label>Actual Weight (kg) *</label>
                    <input type="number" min="0" step="0.01" data-record-actual-weight placeholder="kg">
                </div>
                <div class="form-field">
                    <label>Actual Condition *</label>
                    <select data-record-condition>
                        <option value="">Select condition</option>
                        <option>Excellent</option>
                        <option>Good</option>
                        <option>Fair</option>
                        <option>Poor</option>
                        <option>Damaged</option>
                    </select>
                </div>
            </div>

            <div class="form-field">
                <label>Item Note</label>
                <textarea rows="2" data-record-item-note placeholder="Any item-specific note..."></textarea>
            </div>

            <div class="form-field">
                <label>Collection Note *</label>
                <textarea rows="2" data-record-collection-note placeholder="Notes about the collection..."></textarea>
            </div>

            <div class="total-weight-box">
                <span>Total Weight (Auto)</span>
                <strong data-record-total-weight>0.00 kg</strong>
            </div>
        </div>

        <div class="modal-actions-row" data-record-modal-actions>
            <button type="button" class="primary-btn" data-save-draft-record>Save Draft</button>
        </div>
    </section>
</div>