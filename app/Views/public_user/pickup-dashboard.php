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
                >
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                    <span class="noti-badge"></span>
                </button>

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
            <div class="public-summary-icon total"><?= (int) $summary['total_requests'] ?></div>
            <div>
                <strong><?= (int) $summary['total_requests'] ?></strong>
                <span>Total Requests</span>
            </div>
        </article>
        <article class="public-summary-card">
            <div class="public-summary-icon completed"><?= (int) $summary['completed_requests'] ?></div>
            <div>
                <strong><?= (int) $summary['completed_requests'] ?></strong>
                <span>Completed Pickups</span>
            </div>
        </article>
        <article class="public-summary-card">
            <div class="public-summary-icon review"><?= (int) $summary['pending_requests'] ?></div>
            <div>
                <strong><?= (int) $summary['pending_requests'] ?></strong>
                <span>Pending Reviews</span>
            </div>
        </article>
        <article class="public-summary-card">
            <div class="public-summary-icon weight">kg</div>
            <div>
                <strong><?= number_format((float) $summary['recycled_weight_kg'], 1) ?> kg</strong>
                <span>Estimated Weight of Completed Pickups</span>
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
                    <?php if (empty($recentRequests)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center;padding:24px;color:#5f7268;">
                                No pickup requests submitted yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentRequests as $request): ?>
                            <tr>
                                <td><a href="<?= $basePath ?>/user/my-requests" class="text-link"><?= htmlspecialchars($request['code'], ENT_QUOTES, 'UTF-8') ?></a></td>
                                <td><?= htmlspecialchars($request['submitted_date'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($request['category_summary'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($request['total_weight_label'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) $request['total_quantity'] ?></td>
                                <td><?= htmlspecialchars($request['condition_summary'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="<?= htmlspecialchars($request['dashboard_badge_class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($request['status_label'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>