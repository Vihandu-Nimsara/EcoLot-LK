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
        <a href="#" class="nav-item">Area Schedules</a>
        <a href="#" class="nav-item">Requests</a>
        <a href="#" class="nav-item">Routes</a>
        <a href="#" class="nav-item">Collection Records</a>
        <a href="#" class="nav-item">E-Lots</a>
        <a href="#" class="nav-item">Feedback</a>
        <a href="#" class="nav-item">Reports</a>
    </nav>

    <!-- Bottom User -->
    <div class="sidebar-bottom">
        <div class="user-name">QA Municipal Officer</div>
        <a href="#">Logout</a>
    </div>

</aside>