<section class="collector-workflow-page assigned-schedules-page" data-collector-page="schedules">
    <div class="page-toolbar">
        <div>
            <span class="page-eyebrow">Collection workspace</span>
            <h1>Assigned Schedules</h1>
            <p>Select a schedule to review its collection requests and record the items collected at each stop.</p>
        </div>
        <div class="toolbar-context" aria-label="Collector assignment summary">
            <span data-schedule-count>0 schedules</span>
            <strong data-next-collection>Loading assignments...</strong>
        </div>
    </div>

    <div class="schedule-card-grid" data-schedule-list aria-label="Assigned schedules"></div>

    <section class="surface-card selected-schedule-panel" data-selected-schedule-shell hidden>
        <div class="selected-schedule-heading">
            <div>
                <span class="page-eyebrow">Selected schedule</span>
                <h2 data-selected-schedule-title></h2>
                <p data-selected-schedule-subtitle></p>
            </div>
            <span class="status-badge" data-selected-schedule-status></span>
        </div>

        <dl class="schedule-detail-grid">
            <div><dt>Schedule ID</dt><dd data-selected-schedule-id></dd></div>
            <div><dt>Collection date</dt><dd data-selected-schedule-date></dd></div>
            <div><dt>Postal code</dt><dd data-selected-schedule-postal></dd></div>
            <div><dt>Vehicle</dt><dd data-selected-schedule-vehicle></dd></div>
        </dl>

        <div class="section-heading request-section-heading">
            <div>
                <h3>Collection Requests</h3>
                <p>Open a request to verify quantities, weights, and the collection note.</p>
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

    <div class="workflow-empty-state" data-no-schedules hidden>
        <strong>No schedules are assigned</strong>
        <p>Your assigned collection schedules will appear here when an officer completes the assignment.</p>
    </div>
</section>

<?php include __DIR__ . '/partials/request-details-modal.php'; ?>
