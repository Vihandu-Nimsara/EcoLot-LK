<aside class="sidebar">
    <?php $baseUrl = htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>

    <div class="sidebar-logo">
        <div class="logo-box">
            <img src="<?= $baseUrl ?>/assets/images/ecolot-logo.png" alt="EcoLot LK Logo">
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="<?= $baseUrl ?>/admin/dashboard"
        class="nav-item <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>">
        Dashboard
        </a>
        <a href="<?= $baseUrl ?>/admin/users"
        class="nav-item <?= ($currentPage ?? '') === 'users' ? 'active' : '' ?>">
        Users
        </a>
        <a href="<?= $baseUrl ?>/admin/recycler-verification"
        class="nav-item <?= ($currentPage ?? '') === 'recycler-verification' ? 'active' : '' ?>">
        Recycler Verification
        </a>
        <a href="<?= $baseUrl ?>/admin/categories-items"
        class="nav-item <?= ($currentPage ?? '') === 'categories-items' ? 'active' : '' ?>">
        Categories & Items
        </a>
        <a href="<?= $baseUrl ?>/admin/risk-rules"
        class="nav-item <?= ($currentPage ?? '') === 'risk-rules' ? 'active' : '' ?>">
        Risk Rules
        </a>
        <a href="<?= $baseUrl ?>/admin/reports"
        class="nav-item <?= ($currentPage ?? '') === 'reports' ? 'active' : '' ?>">
        Reports
        </a>
    </nav>

<div class="sidebar-bottom">

    <a href="#" class="logout-link">
        Logout
    </a>

</div>

</aside>
