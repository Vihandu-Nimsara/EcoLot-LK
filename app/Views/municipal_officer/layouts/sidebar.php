<aside class="sidebar" id="officer-sidebar">
    <?php $baseUrl = htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>

    <!-- Logo -->
    <div class="sidebar-logo">
        <div class="logo-box">
            <img src="<?= $baseUrl ?>/assets/images/ecolot-logo.png" alt="EcoLot LK Logo">
        </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <a href="<?= $baseUrl ?>/officer/dashboard"
   class="nav-item <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>">
    Dashboard
</a>
        <a href="<?= $baseUrl ?>/officer/campaigns"
   class="nav-item <?= ($currentPage ?? '') === 'campaigns' ? 'active' : '' ?>">
    Campaigns
</a>
        <a href="<?= $baseUrl ?>/officer/area-schedules"
   class="nav-item <?= ($currentPage ?? '') === 'area-schedules' ? 'active' : '' ?>">
    Area Schedules
</a>
        <a href="<?= $baseUrl ?>/officer/flagged-requests"
   class="nav-item <?= ($currentPage ?? '') === 'flagged-requests' ? 'active' : '' ?>">
    Flagged Requests
</a>
        <a href="<?= $baseUrl ?>/officer/routes"
   class="nav-item <?= ($currentPage ?? '') === 'routes' ? 'active' : '' ?>">
    Routes
</a>
        <a href="<?= $baseUrl ?>/officer/collection-records"
   class="nav-item <?= ($currentPage ?? '') === 'collection-records' ? 'active' : '' ?>">
    Collection Records
</a>
        <a href="<?= $baseUrl ?>/officer/e-lots"
   class="nav-item <?= ($currentPage ?? '') === 'e-lots' ? 'active' : '' ?>">
    E-Lots
</a>
        <a href="<?= $baseUrl ?>/officer/feedback"
   class="nav-item <?= ($currentPage ?? '') === 'feedback' ? 'active' : '' ?>">
    Feedback
</a>
        <a href="<?= $baseUrl ?>/officer/reports"
   class="nav-item <?= ($currentPage ?? '') === 'reports' ? 'active' : '' ?>">
    Reports
</a>

</nav>

    <!-- Bottom User -->
    <div class="sidebar-bottom">

    <a href="#" class="logout-link">
        Logout
    </a>

</div>

</aside>
