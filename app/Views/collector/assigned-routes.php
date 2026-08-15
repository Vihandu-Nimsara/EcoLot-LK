<section class="assigned-routes-page">
    <div class="page-toolbar">
        <div>
            <h1 id="zoneTitle">Today's Assigned Routes</h1>
            <p id="routeDateLine">Review scheduled collection zones and confirm completed pickups.</p>
        </div>
        <span class="updated-status">Updated 5 minutes ago</span>
    </div>

    <div class="route-list" id="routesContainer"></div>

    <aside class="urgent-notice" aria-live="polite">
        <strong>Urgent Notice</strong>
        <p>Road closure on Flower Road. Sector B routes are redirected via Green Path.</p>
    </aside>
</section>

<div class="collector-modal-overlay" data-route-modal hidden>
    <section class="collector-modal-card modal-card-wide" role="dialog" aria-modal="true" aria-labelledby="route-modal-title">
        <button type="button" class="modal-close" aria-label="Close route details" data-close-route-modal>×</button>
        <h2 id="route-modal-title" data-route-zone></h2>

        <div class="modal-detail-grid">
            <div><span>Collector ID</span><strong data-modal-collector-id></strong></div>
            <div><span>Postal Code</span><strong data-modal-postal></strong></div>
            <div><span>Zone</span><strong data-modal-zone-name></strong></div>
            <div><span>Pickup Route</span><strong data-route-modal-weight-label></strong></div>
            <div><span>Weight</span><strong data-route-modal-weight></strong></div>
        </div>

        <div class="collector-use-divider">Collector Use Only</div>

        <div class="collector-use-row">
            <div class="form-field-inline">
                <label for="weightUpdateInput">Weight Update</label>
                <input type="text" id="weightUpdateInput" class="weight-update-input" placeholder="e.g. 18 kg">
            </div>
            <button type="button" class="danger-btn hazard-toggle-btn" data-mark-hazard>Hazard</button>
        </div>

        <button type="button" class="primary-btn full-width" data-save-record>Save Record</button>

        <table class="saved-records-table">
            <thead><tr><th>Route</th><th>Weight</th><th>Hazard or Not</th><th>Actions</th></tr></thead>
            <tbody data-saved-records-body></tbody>
        </table>
    </section>
</div>