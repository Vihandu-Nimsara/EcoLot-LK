<section class="dashboard-page">

    <!-- =====================================================
         DASHBOARD INTRO
    ====================================================== -->
    <div class="page-toolbar dashboard-intro">

        <div>
            <h1>Welcome Back, Admin</h1>

            <p>
                Here’s what’s happening across EcoLot LK today.
            </p>
        </div>

    </div>


    <!-- =====================================================
         SYSTEM STATISTICS
    ====================================================== -->
    <section
        class="schedule-form-card dashboard-stats-section"
        aria-label="System statistics"
    >

        <div class="stats-grid">

            <!-- Total Users -->
            <article class="stat-card">

                <div
                    class="stat-icon total-users"
                    aria-hidden="true"
                >
                    👥
                </div>

                <div class="stat-info">
                    <span>Total Users</span>
                    <strong class="stat-value">4</strong>
                </div>

            </article>


            <!-- Municipal Officers -->
            <article class="stat-card">

                <div
                    class="stat-icon municipal-officers"
                    aria-hidden="true"
                >
                    🏛️
                </div>

                <div class="stat-info">
                    <span>Municipal Officers</span>
                    <strong class="stat-value">1</strong>
                </div>

            </article>


            <!-- Collectors -->
            <article class="stat-card">

                <div
                    class="stat-icon collectors"
                    aria-hidden="true"
                >
                    🚛
                </div>

                <div class="stat-info">
                    <span>Collectors</span>
                    <strong class="stat-value">1</strong>
                </div>

            </article>


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
                    <strong class="stat-value">0</strong>
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
                    <strong class="stat-value">1</strong>
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
                    <strong class="stat-value">6</strong>
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
                    <strong class="stat-value">50</strong>
                </div>

            </article>


            <!-- Risk Rules -->
            <article class="stat-card">

                <div
                    class="stat-icon risk-rules"
                    aria-hidden="true"
                >
                    ⚠️
                </div>

                <div class="stat-info">
                    <span>Risk Rules</span>
                    <strong class="stat-value">50</strong>
                </div>

            </article>

        </div>

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
                    href="/EcoLot-LK/public/admin/users"
                    class="quick-action-item"
                >

                    <span
                        class="quick-action-icon action-user"
                        aria-hidden="true"
                    >
                        👤
                    </span>

                    <span class="quick-action-content">

                        <strong>Create User</strong>

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
                    href="/EcoLot-LK/public/admin/recycler-verification?status=PENDING"
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
                    href="/EcoLot-LK/public/admin/categories-items"
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
                    href="/EcoLot-LK/public/admin/reports"
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

                <!-- Activity 01 -->
                <div class="activity-item">

                    <span
                        class="activity-icon activity-success"
                        aria-hidden="true"
                    >
                        ✓
                    </span>

                    <div class="activity-content">

                        <strong>
                            Recycler “GreenCycle Lanka” verified
                        </strong>

                        <span>
                            Recycler Verification
                        </span>

                    </div>

                </div>


                <!-- Activity 02 -->
                <div class="activity-item">

                    <span
                        class="activity-icon activity-warning"
                        aria-hidden="true"
                    >
                        ⚠
                    </span>

                    <div class="activity-content">

                        <strong>
                            Risk rule #12 updated
                        </strong>

                        <span>
                            Risk Rules Management
                        </span>

                    </div>

                </div>


                <!-- Activity 03 -->
                <div class="activity-item">

                    <span
                        class="activity-icon activity-info"
                        aria-hidden="true"
                    >
                        +
                    </span>

                    <div class="activity-content">

                        <strong>
                            New user account created
                        </strong>

                        <span>
                            User Management
                        </span>

                    </div>

                </div>


                <!-- Activity 04 -->
                <div class="activity-item">

                    <span
                        class="activity-icon activity-purple"
                        aria-hidden="true"
                    >
                        ↗
                    </span>

                    <div class="activity-content">

                        <strong>
                            E-Waste item “Laptop Battery” added
                        </strong>

                        <span>
                            Category &amp; Item Management
                        </span>

                    </div>

                </div>

            </div>

        </article>

    </section>

</section>
