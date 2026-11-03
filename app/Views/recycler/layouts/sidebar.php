<?php $recyclerIcon = require __DIR__ . '/icon.php'; ?>
<aside class="sidebar" id="recycler-sidebar" aria-label="Recycler navigation">
    <?php $baseUrl = htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>

    <div class="sidebar-logo">
        <div class="logo-box">
            <img src="<?= $baseUrl ?>/assets/images/ecolot-logo.png" alt="EcoLot LK Logo">
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="<?= $baseUrl ?>/recycler/dashboard"
        class="nav-item <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>" <?= ($currentPage ?? '') === 'dashboard' ? 'aria-current="page"' : '' ?>>
        <span>Dashboard</span>
        </a>
        <a href="<?= $baseUrl ?>/recycler/eligible-e-lots"
        class="nav-item <?= ($currentPage ?? '') === 'eligible-e-lots' ? 'active' : '' ?>" <?= ($currentPage ?? '') === 'eligible-e-lots' ? 'aria-current="page"' : '' ?>>
        <span>Eligible E-Lots</span>
        </a>
        <a href="<?= $baseUrl ?>/recycler/my-bids"
        class="nav-item <?= ($currentPage ?? '') === 'my-bids' ? 'active' : '' ?>" <?= ($currentPage ?? '') === 'my-bids' ? 'aria-current="page"' : '' ?>>
        <span>My Bids</span>
        </a>
        <a href="<?= $baseUrl ?>/recycler/awarded-e-lots"
        class="nav-item <?= ($currentPage ?? '') === 'awarded-e-lots' ? 'active' : '' ?>" <?= ($currentPage ?? '') === 'awarded-e-lots' ? 'aria-current="page"' : '' ?>>
        <span>Awarded E-Lots</span>
        </a>
        <a href="<?= $baseUrl ?>/recycler/profile"
        class="nav-item <?= ($currentPage ?? '') === 'profile' ? 'active' : '' ?>" <?= ($currentPage ?? '') === 'profile' ? 'aria-current="page"' : '' ?>>
        <span>My Profile</span>
        </a>
        <a href="<?= $baseUrl ?>/recycler/reports"
        class="nav-item <?= ($currentPage ?? '') === 'reports' ? 'active' : '' ?>" <?= ($currentPage ?? '') === 'reports' ? 'aria-current="page"' : '' ?>>
        <span>Reports</span>
        </a>
    </nav>

<div class="sidebar-bottom">
    <?php require dirname(__DIR__, 2) . '/components/logout.php'; ?>
</div>

</aside>
