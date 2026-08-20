<?php $baseUrl = htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>
<aside class="sidebar" id="public-user-sidebar">
    <div class="sidebar-logo">
        <div class="logo-box">
            <img src="<?= $baseUrl ?>/assets/images/ecolot-logo.png" alt="EcoLot LK Logo">
        </div>
    </div>

    <nav class="sidebar-nav">
        <a
            href="<?= $baseUrl ?>/user/dashboard"
            class="nav-item <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>"
        >Dashboard</a>
        <a
            href="<?= $baseUrl ?>/user/new-request"
            class="nav-item <?= ($currentPage ?? '') === 'new-request' ? 'active' : '' ?>"
        >New Pickup Request</a>
        <a
            href="<?= $baseUrl ?>/user/my-requests"
            class="nav-item <?= ($currentPage ?? '') === 'my-requests' ? 'active' : '' ?>"
        >Pickup History</a>
        <a
            href="<?= $baseUrl ?>/user/feedback"
            class="nav-item <?= ($currentPage ?? '') === 'feedback' ? 'active' : '' ?>"
        >Feedback</a>
        <a
            href="<?= $baseUrl ?>/user/profile"
            class="nav-item <?= ($currentPage ?? '') === 'profile' ? 'active' : '' ?>"
        >Account Profile</a>
    </nav>

    <div class="sidebar-bottom">
        <a href="<?= $baseUrl ?>/logout" class="logout-link">Logout</a>
    </div>
</aside>
