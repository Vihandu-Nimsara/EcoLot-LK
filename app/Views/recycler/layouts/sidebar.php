<aside class="sidebar">
    <?php $baseUrl = htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>

    <!-- Logo -->
    <div class="sidebar-logo">
        <div class="logo-box">
            <img src="<?= $baseUrl ?>/assets/images/ecolot-logo.png" alt="EcoLot LK Logo">
        </div>
    </div>

    <!-- Navigation -->
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
    </nav>

    <!-- Bottom User -->
<div class="sidebar-bottom">

    <a href="#" class="logout-link">
        Logout
    </a>

</div>

</aside>
