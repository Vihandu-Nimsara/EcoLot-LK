<?php
$assetBase = htmlspecialchars($basePath . '/assets', ENT_QUOTES, 'UTF-8');
$pageStyles = [
    'dashboard' => 'collector/collection-workflow.css',
    'schedules' => 'collector/collection-workflow.css',
    'e-lots' => 'collector/elots.css',
];
$pageScripts = [
    'e-lots' => 'collector/elots.js',
];
$pageStyle = $pageStyles[$currentPage ?? ''] ?? null;
$pageScript = $pageScripts[$currentPage ?? ''] ?? null;
$themeVersion = Asset::version('css/collector/theme.css');
$workspaceScriptVersion = Asset::version('js/collector/collector-workspace.js');
$pageStyleVersion = $pageStyle !== null
    ? Asset::version('css/' . $pageStyle)
    : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> - Collector</title>

    <?php require dirname(__DIR__, 2) . '/components/workspace-head.php'; ?>
    <link rel="stylesheet" href="<?= $assetBase ?>/css/collector/theme.css?v=<?= $themeVersion ?>">

    <?php if ($pageStyle !== null): ?>
        <link
            rel="stylesheet"
            href="<?= $assetBase ?>/css/<?= htmlspecialchars($pageStyle, ENT_QUOTES, 'UTF-8') ?>?v=<?= $pageStyleVersion ?>"
        >
    <?php endif; ?>

    <link rel="stylesheet" href="<?= $assetBase ?>/css/typography.css">
</head>
<body class="collector-app">
    <?php
    $workspaceLayoutDirectory = __DIR__;
    $workspaceNavigationOverlay = false;
    require dirname(__DIR__, 2) . '/components/workspace-body.php';
    ?>

    <script src="<?= $assetBase ?>/js/collector/collector-workspace.js?v=<?= $workspaceScriptVersion ?>"></script>
    <?php if ($pageScript !== null): ?>
        <script src="<?= $assetBase ?>/js/<?= htmlspecialchars($pageScript, ENT_QUOTES, 'UTF-8') ?>"></script>
    <?php endif; ?>
</body>
</html>
