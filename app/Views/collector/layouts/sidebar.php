<?php $baseUrl = htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>
<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="<?= $baseUrl ?>/assets/images/ecolot-logo.png" alt="EcoLot LK Logo">
        <div><h2>EcoLot LK</h2><p>MUNICIPAL PORTAL</p></div>
    </div>
    <nav class="sidebar-nav">
        <a href="<?= $baseUrl ?>/collector/dashboard" class="<?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>">🚏 My Routes</a>
        <a href="<?= $baseUrl ?>/collector/my-requests" class="<?= ($currentPage ?? '') === 'my-requests' ? 'active' : '' ?>">📄 My Requests</a>
    </nav>
    <div class="sidebar-user">
        <p class="user-name">Kasun Perera</p>
        <p class="user-id">ID: LK-COL-082</p>
    </div>
    <a href="<?= $baseUrl ?>/login" class="signout">↪ Sign Out</a>
</aside>
