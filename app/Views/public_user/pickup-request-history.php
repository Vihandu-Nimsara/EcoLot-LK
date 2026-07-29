<section class="pickup-history-page">
    <div class="page-toolbar">
        <div>
            <h1>Pickup Request History</h1>
            <p>View and track all your submitted e-waste pickup requests.</p>
        </div>
        <a href="<?= $basePath ?>/user/new-request" class="primary-btn">New Pickup Request</a>
    </div>

    <section class="surface-card">
        <div class="request-filter-tabs" role="tablist" aria-label="Request status">
            <button type="button" class="request-filter-tab active">All</button>
            <button type="button" class="request-filter-tab">Pending</button>
            <button type="button" class="request-filter-tab">Completed</button>
            <button type="button" class="request-filter-tab">Cancelled</button>
        </div>

        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Estimated Weight</th>
                        <th>Quantity</th>
                        <th>Condition</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <button
                                type="button"
                                class="request-id-link"
                                data-view-request="REQ-2024-00012"
                            >REQ-2024-00012</button>
                        </td>
                        <td>May 20, 2024</td>
                        <td>IT Equipment</td>
                        <td>8.5 kg</td>
                        <td>3</td>
                        <td>Working</td>
                        <td><span class="status-badge pending">Pending</span></td>
                        <td>
                            <div class="request-actions">
                                <button type="button" class="icon-action view" data-view-request="REQ-2024-00012">View</button>
                                <button type="button" class="icon-action edit" data-view-request="REQ-2024-00012">Edit</button>
                                <button type="button" class="icon-action delete" data-delete-request="REQ-2024-00012">Delete</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="pagination-bar">
            <span>Showing 1 to 1 of 12 requests</span>
            <div class="pagination-controls">
                <button type="button" aria-label="Previous page">‹</button>
                <button type="button" class="active">1</button>
                <button type="button">2</button>
                <button type="button">3</button>
                <button type="button" aria-label="Next page">›</button>
            </div>
        </div>
    </section>
</section>

<div class="request-modal-overlay" data-delete-modal hidden>
    <section class="request-modal-card confirmation-modal" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title">
        <div class="danger-symbol">!</div>
        <h2 id="delete-modal-title">Delete Request?</h2>
        <p>This prototype does not delete data. Confirming will only close this message.</p>
        <div class="modal-actions">
            <button type="button" class="secondary-btn" data-close-delete-modal>Cancel</button>
            <button type="button" class="danger-btn" data-confirm-delete>Delete</button>
        </div>
    </section>
</div>

<div class="request-modal-overlay" data-details-modal hidden>
    <section class="request-modal-card details-modal" role="dialog" aria-modal="true" aria-labelledby="details-modal-title">
        <div class="modal-heading">
            <div>
                <h2 id="details-modal-title">Request Details</h2>
                <span class="request-code" data-request-code>REQ-2024-00012</span>
            </div>
            <button type="button" class="modal-close" aria-label="Close details" data-close-details-modal>×</button>
        </div>

        <div class="request-details-grid">
            <div class="request-detail"><span>Status</span><strong><span class="status-badge pending">Pending</span></strong></div>
            <div class="request-detail"><span>Submitted Date</span><strong>May 20, 2024</strong></div>
            <div class="request-detail"><span>Collection Date</span><strong>May 25, 2024</strong></div>
            <div class="request-detail"><span>Postal Code Area</span><strong>10230 (Maharagama)</strong></div>
            <div class="request-detail full-width"><span>Pickup Address</span><strong>No. 45, High Level Road, Maharagama</strong></div>
            <div class="request-detail"><span>Category</span><strong>IT &amp; Telecommunication Equipment</strong></div>
            <div class="request-detail"><span>Estimated Weight</span><strong>8.5 kg</strong></div>
            <div class="request-detail"><span>Quantity</span><strong>3 items</strong></div>
            <div class="request-detail"><span>Condition</span><strong>Working / Repairable</strong></div>
            <div class="request-detail full-width"><span>Description</span><strong>2 old laptops and 1 monitor with power cables.</strong></div>
        </div>

        <div class="modal-actions">
            <button type="button" class="secondary-btn" data-close-details-modal>Close</button>
        </div>
    </section>
</div>
