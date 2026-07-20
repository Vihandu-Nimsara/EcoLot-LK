<aside class="sidebar">

    <!-- Logo -->
    <div class="sidebar-logo">
        <div class="logo-box">
            <img src="/EcoLot-LK/public/assets/images/ecolot-logo.png" alt="EcoLot LK Logo">
        </div>
        <div>
            <h2>EcoLot LK</h2>
            <span>WORKSPACE</span>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <a href="/EcoLot-LK/public/officer/dashboard"
   class="nav-item <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>">
    Dashboard
</a>
        <a href="/EcoLot-LK/public/officer/campaigns"
   class="nav-item <?= ($currentPage ?? '') === 'campaigns' ? 'active' : '' ?>">
    Campaigns
</a>
        <a href="/EcoLot-LK/public/officer/area-schedules"
   class="nav-item <?= ($currentPage ?? '') === 'area-schedules' ? 'active' : '' ?>">
    Area Schedules
</a>
        <a href="/EcoLot-LK/public/officer/flagged-requests"
   class="nav-item <?= ($currentPage ?? '') === 'flagged-requests' ? 'active' : '' ?>">
    Flagged Requests
</a>
        <a href="/EcoLot-LK/public/officer/routes"
   class="nav-item <?= ($currentPage ?? '') === 'routes' ? 'active' : '' ?>">
    Routes
</a>
        <a href="/EcoLot-LK/public/officer/collection-records"
   class="nav-item <?= ($currentPage ?? '') === 'collection-records' ? 'active' : '' ?>">
    Collection Records
</a>
        <a href="/EcoLot-LK/public/officer/e-lots"
   class="nav-item <?= ($currentPage ?? '') === 'e-lots' ? 'active' : '' ?>">
    E-Lots
</a>
        <a href="/EcoLot-LK/public/officer/feedback"
   class="nav-item <?= ($currentPage ?? '') === 'feedback' ? 'active' : '' ?>">
    Feedback
</a>
        <a href="#" class="nav-item">Reports</a>
    </nav>

    <!-- Bottom User -->
    <div class="sidebar-bottom">

    <a href="#" class="logout-link">
        Logout
    </a>

</div>

</aside>