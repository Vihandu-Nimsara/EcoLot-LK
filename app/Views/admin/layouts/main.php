<?php
$assetBase = htmlspecialchars($basePath . '/assets', ENT_QUOTES, 'UTF-8');
$pageStyles = [
    'dashboard' => 'admin/dashboard.css',
    'users' => 'admin/users.css',
    'recycler-verification' => 'admin/recycler-verification.css',
    'categories-items' => 'admin/categories-items.css',
    'risk-rules' => 'admin/risk-rules.css',
    'reports' => 'admin/reports.css',
    'recycler-details' => 'admin/recycler-details.css'
];
$pageStyle = $pageStyles[$pageStylePage ?? $currentPage ?? ''] ?? null;
$themeVersion = (string) filemtime(dirname(__DIR__, 4) . '/public/assets/css/admin/theme.css');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> - Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Exo+2:wght@600;700&amp;family=Inter:wght@400;500;600&amp;display=swap"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="<?= $assetBase ?>/css/style.css">
    <link rel="stylesheet" href="<?= $assetBase ?>/css/sidebar.css">
    <link rel="stylesheet" href="<?= $assetBase ?>/css/header.css">

    <?php if ($pageStyle !== null): ?>
        <link
            rel="stylesheet"
            href="<?= $assetBase ?>/css/<?= htmlspecialchars($pageStyle, ENT_QUOTES, 'UTF-8') ?>"
        >
    <?php endif; ?>

    <link rel="stylesheet" href="<?= $assetBase ?>/css/typography.css">
    <link rel="stylesheet" href="<?= $assetBase ?>/css/admin/theme.css?v=<?= $themeVersion ?>">
</head>

<body class="admin-app">
    <div class="app-layout">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <button class="workspace-overlay" type="button" aria-label="Close navigation" data-nav-close></button>

        <div class="main-content">
            <?php include __DIR__ . '/header.php'; ?>

            <main class="page-content">
                <?= $content ?>
            </main>
        </div>
    </div>
    <?php include __DIR__ . '/action-dialog.php'; ?>
    <script src="<?= $assetBase ?>/js/admin/frontend-demo.js"></script>
</body>

</html>
