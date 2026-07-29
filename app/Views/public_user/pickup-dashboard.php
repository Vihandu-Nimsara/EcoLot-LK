<section class="public-dashboard-page">
    <div class="page-toolbar">
        <div>
            <h1>Pickup Dashboard</h1>
            <p>Monitor your disposal milestones and upcoming recycling collections.</p>
        </div>

        <div class="dashboard-actions">
            <a href="<?= $basePath ?>/user/new-request" class="primary-btn">
                New Pickup Request
            </a>

            <div class="notification-control">
                <button
                    type="button"
                    class="notification-toggle"
                    aria-label="Show notifications"
                    aria-expanded="false"
                    data-notification-toggle
                >●</button>

                <div class="notification-menu" data-notification-menu hidden>
                    <h2>Notifications</h2>
                    <article>
                        <p>Your request <strong>REQ-2024-00011</strong> has been scheduled.</p>
                        <time>2 hours ago</time>
                    </article>
                    <article>
                        <p>Pickup verified for <strong>REQ-2024-00010</strong>. Thank you!</p>
                        <time>Yesterday</time>
                    </article>
                </div>
            </div>
        </div>
    </div>

    <div class="public-summary-grid">
        <article class="public-summary-card">
            <div class="public-summary-icon total">12</div>
            <div>
                <strong>12</strong>
                <span>Total Requests</span>
            </div>
        </article>
        <article class="public-summary-card">
            <div class="public-summary-icon completed">7</div>
            <div>
                <strong>7</strong>
                <span>Completed Pickups</span>
            </div>
        </article>
        <article class="public-summary-card">
            <div class="public-summary-icon review">2</div>
            <div>
                <strong>2</strong>
                <span>Pending Reviews</span>
            </div>
        </article>
        <article class="public-summary-card">
            <div class="public-summary-icon weight">kg</div>
            <div>
                <strong>41.5 kg</strong>
                <span>Recycled Weight</span>
            </div>
        </article>
    </div>

    <section class="surface-card latest-requests-card">
        <div class="card-heading">
            <div>
                <h2>Latest Pickup Requests</h2>
                <p>Your most recently submitted pickup requests.</p>
            </div>
            <a href="<?= $basePath ?>/user/my-requests" class="text-link">View all requests</a>
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
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><a href="<?= $basePath ?>/user/my-requests" class="text-link">REQ-2024-00012</a></td>
                        <td>May 20, 2024</td>
                        <td>IT Equipment</td>
                        <td>8.5 kg</td>
                        <td>3</td>
                        <td>Working</td>
                        <td><span class="status-badge pending">Pending</span></td>
                    </tr>
                    <tr>
                        <td><a href="<?= $basePath ?>/user/my-requests" class="text-link">REQ-2024-00011</a></td>
                        <td>May 18, 2024</td>
                        <td>Small Appliances</td>
                        <td>12 kg</td>
                        <td>1</td>
                        <td>Working</td>
                        <td><span class="status-badge completed">Completed</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</section>
