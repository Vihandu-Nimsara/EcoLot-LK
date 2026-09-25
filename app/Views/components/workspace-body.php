<div class="app-layout">
    <?php include $workspaceLayoutDirectory . '/sidebar.php'; ?>
    <?php if ($workspaceNavigationOverlay): ?>
        <button class="workspace-overlay" type="button" aria-label="Close navigation" data-nav-close></button>
    <?php endif; ?>

    <div class="main-content">
        <?php include $workspaceLayoutDirectory . '/header.php'; ?>
        <main class="page-content">
            <?= $content ?>
        </main>
    </div>
</div>
