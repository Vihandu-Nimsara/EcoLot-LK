<?php
$assetBase = htmlspecialchars($basePath . '/assets', ENT_QUOTES, 'UTF-8');
$pageStyles = [
    'dashboard' => 'recycler/dashboard.css',
    'eligible-e-lots' => 'recycler/eligible_e-lots.css',
    'my-bids' => 'recycler/my_bids.css',
    'awarded-e-lots' => 'recycler/awarded_e-lots.css',
    'profile' => 'recycler/workflow.css',
    'reports' => 'recycler/workflow.css',
];
$workflowPages = ['eligible-e-lots', 'my-bids', 'awarded-e-lots'];
$secondaryPageStyle = in_array($currentPage ?? '', $workflowPages, true)
    ? 'recycler/workflow.css'
    : null;
$pageStyle = $pageStyles[$currentPage ?? ''] ?? null;
$themeVersion = (string) filemtime(dirname(__DIR__, 4) . '/public/assets/css/recycler/theme.css');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> - Recycler</title>

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

    <?php if ($secondaryPageStyle !== null): ?>
        <link rel="stylesheet" href="<?= $assetBase ?>/css/<?= htmlspecialchars($secondaryPageStyle, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>

    <link rel="stylesheet" href="<?= $assetBase ?>/css/typography.css">
    <link rel="stylesheet" href="<?= $assetBase ?>/css/recycler/theme.css?v=<?= $themeVersion ?>">
</head>

<body class="recycler-app">
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
    <script src="<?= $assetBase ?>/js/recycler/frontend-demo.js"></script>
</body>

</html>
