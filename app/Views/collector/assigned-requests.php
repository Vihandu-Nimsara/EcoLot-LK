<section class="collector-workflow-page assigned-requests-page" data-collector-page="requests">
    <div class="page-toolbar">
        <div>
            <span class="page-eyebrow">Request tracking</span>
            <h1>Assigned Requests</h1>
            <p>Select a schedule first to view its collection progress and assigned pickup requests.</p>
        </div>
        <button type="button" class="secondary-btn print-report-btn" data-print-summary>Print Summary</button>
    </div>

    <section class="schedule-picker surface-card" aria-labelledby="schedule-picker-title">
        <div class="section-heading">
            <div>
                <h2 id="schedule-picker-title">Choose a Schedule</h2>
                <p>Only schedules assigned to this collector are available.</p>
            </div>
            <span class="request-count-label" data-schedule-count>0 schedules</span>
        </div>
        <div class="schedule-card-grid compact" data-schedule-list aria-label="Assigned schedules"></div>
    </section>

    <div data-selected-schedule-shell hidden>
        <section class="selected-request-summary surface-card">
            <div class="selected-schedule-heading">
                <div>
                    <span class="page-eyebrow">Schedule summary</span>
                    <h2 data-selected-schedule-title></h2>
                    <p data-selected-schedule-subtitle></p>
                </div>
                <span class="status-badge" data-selected-schedule-status></span>
            </div>

            <dl class="schedule-detail-grid summary-details">
                <div><dt>Schedule ID</dt><dd data-selected-schedule-id></dd></div>
                <div><dt>Collection date</dt><dd data-selected-schedule-date></dd></div>
                <div><dt>Postal code</dt><dd data-selected-schedule-postal></dd></div>
                <div><dt>Vehicle</dt><dd data-selected-schedule-vehicle></dd></div>
            </dl>

            <div class="collector-summary-grid">
                <article class="collector-summary-card">
                    <span>Total requests</span>
                    <strong data-summary-total>0</strong>
                    <small>Approved pickup stops</small>
                </article>
                <article class="collector-summary-card success">
                    <span>Confirmed</span>
                    <strong data-summary-confirmed>0</strong>
                    <small>Collection details completed</small>
                </article>
                <article class="collector-summary-card">
                    <span>Pending</span>
                    <strong data-summary-pending>0</strong>
                    <small>Requests still to collect</small>
                </article>
                <article class="collector-summary-card">
                    <span>Actual weight</span>
                    <strong><span data-summary-weight>0.0</span> <small>kg</small></strong>
                    <small>Confirmed item weight</small>
                </article>
            </div>

            <div class="schedule-progress" aria-label="Schedule completion progress">
                <div class="schedule-progress-copy">
                    <span>Collection progress</span>
                    <strong data-summary-progress-label>0% complete</strong>
                </div>
                <div class="progress-track"><div data-summary-progress-bar></div></div>
            </div>
        </section>

        <section class="surface-card request-list-card">
            <div class="section-heading request-section-heading">
                <div>
                    <h2>Requests in this Schedule</h2>
                    <p>Request IDs are used as the primary reference for each pickup.</p>
                </div>
                <span class="request-count-label" data-selected-request-count></span>
            </div>

            <div class="table-scroll">
                <table class="workflow-table request-table">
                    <thead>
                        <tr>
                            <th scope="col">Request ID</th>
                            <th scope="col">Pickup Address</th>
                            <th scope="col">Items</th>
                            <th scope="col">Collection Status</th>
                            <th scope="col" aria-label="Actions"></th>
                        </tr>
                    </thead>
                    <tbody data-request-table-body></tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="workflow-empty-state" data-no-schedules hidden>
        <strong>No schedules are assigned</strong>
        <p>There are no request groups available for this collector.</p>
    </div>
</section>

<?php include __DIR__ . '/partials/request-details-modal.php'; ?>
