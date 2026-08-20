<?php
declare(strict_types=1);
?>

<section class="collector-workspace" data-collector-page="requests">
    <header class="collector-page-heading collector-page-heading--requests">
        <div>
            <span class="collector-eyebrow">Collection workspace</span>
            <h1>Assigned Requests</h1>
            <p>Review requests across your assigned schedules and continue any draft collection records.</p>
        </div>

        <button id="collector-print-summary" class="collector-secondary-button" type="button">Print Summary</button>
    </header>

    <div id="collector-feedback" class="collector-feedback" role="status" aria-live="polite" hidden></div>

    <section class="collector-request-summary" aria-label="Assigned request summary">
        <article>
            <span>Total Requests</span>
            <strong id="collector-summary-total">0</strong>
        </article>
        <article>
            <span>Draft Records</span>
            <strong id="collector-summary-drafts">0</strong>
        </article>
        <article>
            <span>Submitted Records</span>
            <strong id="collector-summary-submitted">0</strong>
        </article>
        <article>
            <span>Recorded Weight</span>
            <strong id="collector-summary-weight">0.00 kg</strong>
        </article>
    </section>

    <section class="collector-filter-card" aria-label="Request filters">
        <label class="collector-filter-field">
            <span>Schedule</span>
            <select id="collector-request-schedule-filter">
                <option value="ALL">All assigned schedules</option>
            </select>
        </label>

        <label class="collector-filter-field">
            <span>Record Status</span>
            <select id="collector-request-status-filter">
                <option value="ALL">All statuses</option>
                <option value="PENDING_COLLECTION">Pending Collection</option>
                <option value="DRAFT">Draft</option>
                <option value="SUBMITTED">Submitted</option>
                <option value="VERIFIED">Verified</option>
            </select>
        </label>

        <label class="collector-filter-field collector-filter-field--search">
            <span>Search</span>
            <input id="collector-request-search" type="search" placeholder="Request ID or pickup address">
        </label>
    </section>

    <section class="collector-table-card" aria-labelledby="assigned-request-table-title">
        <div class="collector-section-header">
            <div>
                <h2 id="assigned-request-table-title">Collection Requests</h2>
                <p>Original request information stays read-only. Collection records can be edited only while they remain drafts.</p>
            </div>
            <span id="collector-request-result-count" class="collector-count-pill">0 requests</span>
        </div>

        <div class="collector-table-wrap">
            <table class="collector-table">
                <thead>
                    <tr>
                        <th scope="col">Request ID</th>
                        <th scope="col">Schedule</th>
                        <th scope="col">Pickup Address</th>
                        <th scope="col">Items</th>
                        <th scope="col">Record Status</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody id="collector-all-request-rows"></tbody>
            </table>
        </div>

        <div id="collector-request-filter-empty" class="collector-empty-state" hidden>
            <strong>No matching requests</strong>
            <span>Try changing the schedule, status, or search filter.</span>
        </div>
    </section>
</section>

<?php require __DIR__ . '/partials/request-details-modal.php'; ?>