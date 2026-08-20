<?php $baseUrl = htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>
<aside class="sidebar" id="collector-sidebar">
    <div class="sidebar-logo">
        <div class="logo-box">
            <img src="<?= $baseUrl ?>/assets/images/ecolot-logo.png" alt="EcoLot LK Logo">
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="<?= $baseUrl ?>/collector/dashboard" class="nav-item <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>">Assigned Schedules</a>
        <a href="<?= $baseUrl ?>/collector/my-requests" class="nav-item <?= ($currentPage ?? '') === 'my-requests' ? 'active' : '' ?>">Assigned Requests</a>
        <a href="<?= $baseUrl ?>/collector/e-lots" class="nav-item <?= ($currentPage ?? '') === 'e-lots' ? 'active' : '' ?>">My E-Lots</a>
    </nav>

    <div class="sidebar-bottom">
        <a href="<?= $baseUrl ?>/login" class="logout-link">Logout</a>
    </div>
</aside>
