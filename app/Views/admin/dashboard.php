<section class="dashboard-page">

    <!-- =====================================================
         DASHBOARD INTRO
    ====================================================== -->
    <div class="page-toolbar dashboard-intro">

        <div>
            <h1>Welcome Back, Admin</h1>

            <p>
                System overview and latest administrative activity across EcoLot LK.
            </p>
        </div>

    </div>


    <?php if (!empty($dashboardUnavailable)): ?>
        <p role="alert" class="dashboard-data-notice">
            Dashboard statistics and activity are temporarily unavailable. Please try again later.
        </p>
    <?php endif; ?>

    <!-- =====================================================
         KEY METRICS + USER DISTRIBUTION
         Keep operational KPIs distinct from account role breakdown.
    ====================================================== -->
    <section class="dashboard-top-grid" aria-label="Key statistics and user distribution">
        <section class="schedule-form-card dashboard-stats-section" aria-label="Key statistics">
            <div class="stats-grid">

                <!-- Pending Recyclers -->
                <article class="stat-card">

                    <div
                        class="stat-icon pending"
                        aria-hidden="true"
                    >
                        ⏳
                    </div>

                    <div class="stat-info">
                        <span>Pending Recyclers</span>
                        <strong class="stat-value"><?= $dashboardStats === null ? "—" : number_format((int) $dashboardStats['pending_recyclers']) ?></strong>
                    </div>

                </article>

                <!-- Verified Recyclers -->
                <article class="stat-card">

                    <div
                        class="stat-icon verified"
                        aria-hidden="true"
                    >
                        ✅
                    </div>

                    <div class="stat-info">
                        <span>Verified Recyclers</span>
                        <strong class="stat-value"><?= $dashboardStats === null ? "—" : number_format((int) $dashboardStats['verified_recyclers']) ?></strong>
                    </div>

                </article>

                <!-- E-Waste Categories -->
                <article class="stat-card">

                    <div
                        class="stat-icon e-waste-categories"
                        aria-hidden="true"
                    >
                        🗂️
                    </div>

                    <div class="stat-info">
                        <span>E-Waste Categories</span>
                        <strong class="stat-value"><?= $dashboardStats === null ? "—" : number_format((int) $dashboardStats['e_waste_categories']) ?></strong>
                    </div>

                </article>

                <!-- E-Waste Items -->
                <article class="stat-card">

                    <div
                        class="stat-icon e-waste-items"
                        aria-hidden="true"
                    >
                        🔌
                    </div>

                    <div class="stat-info">
                        <span>E-Waste Items</span>
                        <strong class="stat-value"><?= $dashboardStats === null ? "—" : number_format((int) $dashboardStats['e_waste_items']) ?></strong>
                    </div>

                </article>

            </div>
        </section>

        <article class="dashboard-panel system-overview-panel" aria-labelledby="system-overview-heading">
            <div class="dashboard-panel-header">
                <h2 id="system-overview-heading">System Overview</h2>
                <p>Registered accounts by role, including inactive accounts.</p>
            </div>

            <?php if ($dashboardStats === null): ?>
                <p class="overview-empty">User-role breakdown is temporarily unavailable.</p>
            <?php else: ?>
                <?php
                    $overviewRoles = [
                        ['key' => 'admin_users', 'label' => 'Administrators', 'color' => '#125f44'],
                        ['key' => 'municipal_officers', 'label' => 'Municipal Officers', 'color' => '#229a68'],
                        ['key' => 'collectors', 'label' => 'Collectors', 'color' => '#55c58a'],
                        ['key' => 'recycler_users', 'label' => 'Recyclers', 'color' => '#85deb1'],
                        ['key' => 'public_users', 'label' => 'Public Users', 'color' => '#c6f1d9'],
                    ];
                    $overviewTotal = max(0, (int) $dashboardStats['total_users']);
                    $overviewSoFar = 0;
                    $overviewStops = [];
                    foreach ($overviewRoles as $role) {
                        $roleCount = max(0, (int) ($dashboardStats[$role['key']] ?? 0));
                        if ($roleCount > 0 && $overviewTotal > 0) {
                            $start = number_format($overviewSoFar * 100 / $overviewTotal, 3, '.', '');
                            $end = number_format(($overviewSoFar + $roleCount) * 100 / $overviewTotal, 3, '.', '');
                            $overviewStops[] = $role['color'] . ' ' . $start . '% ' . $end . '%';
                        }
                        $overviewSoFar += $roleCount;
                    }
                    $overviewStyle = $overviewTotal > 0
                        ? 'background: conic-gradient(' . implode(', ', $overviewStops) . ')'
                        : '';
                ?>
                <div class="overview-visual">
                    <div class="overview-donut<?= $overviewTotal === 0 ? ' overview-donut-empty' : '' ?>"
                         style="<?= htmlspecialchars($overviewStyle, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true">
                        <div class="overview-donut-core">
                            <strong><?= number_format($overviewTotal) ?></strong>
                            <span>Users</span>
                        </div>
                    </div>
                    <ul class="overview-legend" aria-label="User accounts by role">
                        <?php foreach ($overviewRoles as $role): ?>
                            <li>
                                <span class="overview-dot" style="background-color: <?= htmlspecialchars($role['color'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></span>
                                <span class="overview-role-label"><?= htmlspecialchars($role['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                <strong><?= number_format((int) ($dashboardStats[$role['key']] ?? 0)) ?></strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php if ($overviewTotal === 0): ?>
                    <p class="overview-empty">No user accounts are recorded yet.</p>
                <?php endif; ?>
            <?php endif; ?>
        </article>
    </section>


    <!-- =====================================================
         PENDING ACTIONS
    ====================================================== -->
    <section class="dashboard-panel pending-actions-panel" aria-labelledby="pending-actions-heading">
        <div class="dashboard-panel-header pending-actions-header">
            <div>
                <h2 id="pending-actions-heading">Pending Actions</h2>
                <p>Recycler registrations requiring follow-up.</p>
            </div>
        </div>
        <?php if ($dashboardStats === null): ?>
            <p class="pending-actions-empty">Pending applications are temporarily unavailable.</p>
        <?php elseif ((int) $dashboardStats['pending_recyclers'] > 0): ?>
            <?php $pendingCount = (int) $dashboardStats['pending_recyclers']; ?>
            <div class="pending-action-row">
                <span class="pending-action-icon" aria-hidden="true">♻️</span>
                <div class="pending-action-content">
                    <strong><?= number_format($pendingCount) ?> Recycler <?= $pendingCount === 1 ? 'application is' : 'applications are' ?> pending verification</strong>
                    <span>Review the pending registration queue and check each application's requirements.</span>
                </div>
                <a class="pending-action-button" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/admin/recycler-verification?status=PENDING">Open queue <span aria-hidden="true">→</span></a>
            </div>
        <?php else: ?>
            <p class="pending-actions-empty">No Recycler registrations are currently pending verification.</p>
        <?php endif; ?>
    </section>


    <!-- =====================================================
         QUICK ACTIONS + RECENT ACTIVITY
    ====================================================== -->
    <section class="dashboard-secondary-grid">


        <!-- =================================================
             QUICK ACTIONS
        ================================================== -->
        <article class="dashboard-panel quick-actions-panel">

            <div class="dashboard-panel-header">

                <div>
                    <h2>Quick Actions</h2>

                    <p>
                        Access the most frequently used administration tools.
                    </p>
                </div>

            </div>


            <div class="quick-actions-grid">

                <!-- Create User -->
                <a
                    href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/admin/users"
                    class="quick-action-item"
                >

                    <span
                        class="quick-action-icon action-user"
                        aria-hidden="true"
                    >
                        👤
                    </span>

                    <span class="quick-action-content">

                        <strong>Manage Users</strong>

                        <small>
                            Add and manage system user accounts.
                        </small>

                    </span>

                    <span
                        class="quick-action-arrow"
                        aria-hidden="true"
                    >
                        →
                    </span>

                </a>


                <!-- Recycler Applications -->
                <a
                    href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/admin/recycler-verification?status=PENDING"
                    class="quick-action-item"
                >

                    <span
                        class="quick-action-icon action-recycler"
                        aria-hidden="true"
                    >
                        ♻️
                    </span>

                    <span class="quick-action-content">

                        <strong>Review Recycler Applications</strong>

                        <small>
                            Review pending recycler registrations.
                        </small>

                    </span>

                    <span
                        class="quick-action-arrow"
                        aria-hidden="true"
                    >
                        →
                    </span>

                </a>


                <!-- Categories and Items -->
                <a
                    href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/admin/categories-items"
                    class="quick-action-item"
                >

                    <span
                        class="quick-action-icon action-categories"
                        aria-hidden="true"
                    >
                        🗂️
                    </span>

                    <span class="quick-action-content">

                        <strong>Manage Categories &amp; Items</strong>

                        <small>
                            Maintain the E-Waste item catalogue.
                        </small>

                    </span>

                    <span
                        class="quick-action-arrow"
                        aria-hidden="true"
                    >
                        →
                    </span>

                </a>


                <a
                    href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/admin/risk-rules"
                    class="quick-action-item"
                >
                    <span class="quick-action-icon action-risk" aria-hidden="true">⚠️</span>
                    <span class="quick-action-content"><strong>Manage Risk Rules</strong><small>Maintain collection classification guidance.</small></span>
                    <span class="quick-action-arrow" aria-hidden="true">→</span>
                </a>


                <!-- Reports -->
                <a
                    href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/admin/reports"
                    class="quick-action-item"
                >

                    <span
                        class="quick-action-icon action-reports"
                        aria-hidden="true"
                    >
                        📊
                    </span>

                    <span class="quick-action-content">

                        <strong>Open Reports</strong>

                        <small>
                            View system reports and analytics.
                        </small>

                    </span>

                    <span
                        class="quick-action-arrow"
                        aria-hidden="true"
                    >
                        →
                    </span>

                </a>

            </div>

        </article>


        <!-- =================================================
             RECENT ACTIVITY
        ================================================== -->
        <article class="dashboard-panel recent-activity-panel">

            <div class="dashboard-panel-header">

                <div>
                    <h2>Recent Activity</h2>

                    <p>
                        Latest administrative updates across EcoLot LK.
                    </p>
                </div>

            </div>


            <div class="activity-list">
                <?php if (empty($dashboardActivity)): ?>
                    <p class="activity-empty">
                        <?= !empty($dashboardUnavailable)
                            ? "Recent activity is temporarily unavailable."
                            : "No recent administrative activity has been recorded." ?>
                    </p>
                <?php else: ?>
                    <?php foreach ($dashboardActivity as $activity): ?>
                        <?php
                            $activityHref = $activity['href'] ?? null;
                            $activityHasLink = is_string($activityHref)
                                && preg_match('~^/admin/(?:users|recycler-verification(?:/[1-9][0-9]*)?)$~D', $activityHref) === 1;
                        ?>
                        <?php if ($activityHasLink): ?>
                            <a class="activity-item activity-link" href="<?= htmlspecialchars($basePath . $activityHref, ENT_QUOTES, 'UTF-8') ?>">
                        <?php else: ?>
                            <div class="activity-item">
                        <?php endif; ?>
                            <span class="activity-icon <?= htmlspecialchars($activity['class'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true">
                                <?= htmlspecialchars($activity['icon'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <div class="activity-content">
                                <strong><?= htmlspecialchars($activity['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <span>
                                    <?= htmlspecialchars($activity['group'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php if ($activity['time'] !== ''): ?>
                                        · <?= htmlspecialchars($activity['time'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <?php if ($activityHasLink): ?><span class="activity-arrow" aria-hidden="true">→</span><?php endif; ?>
                        <?php if ($activityHasLink): ?></a><?php else: ?></div><?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </article>



    </section>

</section>
