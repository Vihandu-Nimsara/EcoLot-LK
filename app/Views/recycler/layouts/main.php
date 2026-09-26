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
$themeVersion = Asset::version('css/recycler/theme.css');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> - Recycler</title>

    <?php require dirname(__DIR__, 2) . '/components/workspace-head.php'; ?>

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
    <?php
    $workspaceLayoutDirectory = __DIR__;
    $workspaceNavigationOverlay = true;
    require dirname(__DIR__, 2) . '/components/workspace-body.php';
    ?>
    <?php include __DIR__ . '/action-dialog.php'; ?>
    <script src="<?= $assetBase ?>/js/recycler/frontend-demo.js"></script>
</body>

</html>
