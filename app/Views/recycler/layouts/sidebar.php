<aside class="sidebar">

    <!-- Logo -->
    <div class="sidebar-logo">
        <div class="logo-box">
            <img src="/EcoLot-LK/public/assets/images/ecolot-logo.png" alt="EcoLot LK Logo">
        </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <a href="/EcoLot-LK/public/recycler/dashboard"
        class="nav-item <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>">
        Dashboard
        </a>
        <a href="/EcoLot-LK/public/recycler/eligible_e-lots"
        class="nav-item <?= ($currentPage ?? '') === 'eligible_e-lots' ? 'active' : '' ?>">
        Eligible E-Lots
        </a>
        <a href="/EcoLot-LK/public/recycler/my_bids"
        class="nav-item <?= ($currentPage ?? '') === 'my_bids' ? 'active' : '' ?>">
        My Bids
        </a>
        <a href="/EcoLot-LK/public/recycler/awarded_e-lots"
        class="nav-item <?= ($currentPage ?? '') === 'awarded_e-lots' ? 'active' : '' ?>">
        Awarded E-Lots
        </a>
    </nav>

    <!-- Bottom User -->
<div class="sidebar-bottom">

    <a href="#" class="logout-link">
        Logout
    </a>

</div>

</aside>