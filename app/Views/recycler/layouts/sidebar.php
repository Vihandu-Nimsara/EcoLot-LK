<aside class="sidebar" id="recycler-sidebar" aria-label="Recycler navigation">
    <?php $baseUrl = htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>

    <div class="sidebar-logo">
        <div class="logo-box">
            <img src="<?= $baseUrl ?>/assets/images/ecolot-logo.png" alt="EcoLot LK Logo">
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="<?= $baseUrl ?>/recycler/dashboard"
        class="nav-item <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>">
        Dashboard
        </a>
        <a href="<?= $baseUrl ?>/recycler/eligible-e-lots"
        class="nav-item <?= ($currentPage ?? '') === 'eligible-e-lots' ? 'active' : '' ?>">
        Eligible E-Lots
        </a>
        <a href="<?= $baseUrl ?>/recycler/my-bids"
        class="nav-item <?= ($currentPage ?? '') === 'my-bids' ? 'active' : '' ?>">
        My Bids
        </a>
        <a href="<?= $baseUrl ?>/recycler/awarded-e-lots"
        class="nav-item <?= ($currentPage ?? '') === 'awarded-e-lots' ? 'active' : '' ?>">
        Awarded E-Lots
        </a>
        <a href="<?= $baseUrl ?>/recycler/profile"
        class="nav-item <?= ($currentPage ?? '') === 'profile' ? 'active' : '' ?>">
        My Profile
        </a>
        <a href="<?= $baseUrl ?>/recycler/reports"
        class="nav-item <?= ($currentPage ?? '') === 'reports' ? 'active' : '' ?>">
        Reports
        </a>
    </nav>

<div class="sidebar-bottom">

    <button type="button" class="logout-link" data-recycler-dialog="logout">
        Logout
    </button>

</div>

</aside>
