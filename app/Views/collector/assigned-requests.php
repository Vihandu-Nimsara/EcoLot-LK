<section class="assigned-requests-page">
    <div class="page-toolbar">
        <div>
            <h1 id="requestsTitle">Assigned Pickup Requests</h1>
            <p id="requestsSubline">Loading...</p>
        </div>
        <div class="toolbar-actions">
            <button type="button" class="secondary-btn" data-toggle-filter>Filter Requests</button>
            <button type="button" class="primary-btn" data-daily-report>Print Daily Report</button>
        </div>
    </div>

    <div class="collector-summary-grid">
        <article class="collector-summary-card"><span>Total Stops</span><strong id="statTotal">4</strong><small>Planned for this shift</small></article>
        <article class="collector-summary-card"><span>Picked Up</span><strong data-picked-up-count>0</strong><div class="pickup-progress"><div data-pickup-progress></div></div></article>
        <article class="collector-summary-card"><span>Pending</span><strong data-pending-count>4</strong><small>Assigned stops remaining</small></article>
        <article class="collector-summary-card hazard"><span>Hazards</span><strong data-hazard-count>0</strong><small>Require officer review</small></article>
    </div>

    <div class="request-workspace-grid">
        <section class="surface-card route-map-card">
            <div class="card-heading">
                <div><h2>Active Route Map</h2><p>Current collection route coverage.</p></div>
                <span class="live-status">Live</span>
            </div>
            <div class="route-map">
                <img src="/EcoLot-LK/public/assets/images/map.jpeg" alt="Active route map">
            </div>
        </section>

        <section class="surface-card quick-status-card">
            <div class="card-heading"><div><h2>Quick Status</h2><p>Update or flag assigned stops.</p></div></div>
            <div id="quickStatusContainer"></div>
        </section>
    </div>

    <section class="surface-card notes-card">
        <div class="card-heading"><div><h2>Request Notes</h2><p>Add, view, edit, or delete a note for this shift.</p></div></div>

        <label class="note-label" for="noteTextInput">Note</label>
        <div class="note-box" style="width:100%; box-sizing:border-box;">
            <textarea
                id="noteTextInput"
                data-note-text
                placeholder="Write a note about this stop..."
                style="display:block; width:100%; min-width:100%; max-width:100%; height:260px; box-sizing:border-box; border:1px solid #B8D6C3; border-radius:12px; background:#F8FBF9; padding:18px; margin:0; font-family:'Inter',sans-serif; font-size:15px; line-height:1.6; resize:vertical; color:#1f2a24;"
            ></textarea>
        </div>

        <div class="note-bottom-row" style="display:flex; align-items:center; gap:10px; margin-top:16px;">
            <button type="button" class="primary-btn save-note-btn" data-save-note>Save Note</button>
            <button type="button" class="note-edit-btn" data-edit-note hidden>Edit</button>
            <button type="button" class="note-delete-btn" data-delete-note hidden>Delete</button>
        </div>
    </section>
</section>