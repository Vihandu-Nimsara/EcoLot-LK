<?php
$assetBase = htmlspecialchars($basePath . '/assets', ENT_QUOTES, 'UTF-8');
$pageStyles = [
    'dashboard' => 'collector/assigned-routes.css',
    'my-requests' => 'collector/assigned-requests.css',
    'initial-request' => 'collector/initial-request.css',
    'e-lots' => 'collector/elots.css',
];
$pageScripts = [
    'e-lots' => 'collector/elots.js',
];
$pageStyle = $pageStyles[$currentPage ?? ''] ?? null;
$pageScript = $pageScripts[$currentPage ?? ''] ?? null;
$themeVersion = (string) filemtime(dirname(__DIR__, 4) . '/public/assets/css/collector/theme.css');
$workspaceScriptVersion = (string) filemtime(dirname(__DIR__, 4) . '/public/assets/js/collector/collector-workspace.js');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> - Collector</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Exo+2:wght@600;700&amp;family=Inter:wght@400;500;600&amp;display=swap"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="<?= $assetBase ?>/css/style.css">
    <link rel="stylesheet" href="<?= $assetBase ?>/css/sidebar.css">
    <link rel="stylesheet" href="<?= $assetBase ?>/css/header.css">
    <link rel="stylesheet" href="<?= $assetBase ?>/css/collector/theme.css?v=<?= $themeVersion ?>">

    <?php if ($pageStyle !== null): ?>
        <link
            rel="stylesheet"
            href="<?= $assetBase ?>/css/<?= htmlspecialchars($pageStyle, ENT_QUOTES, 'UTF-8') ?>"
        >
    <?php endif; ?>

    <link rel="stylesheet" href="<?= $assetBase ?>/css/typography.css">
</head>
<body class="collector-app">
    <div class="app-layout">
        <?php include __DIR__ . '/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/header.php'; ?>

            <main class="page-content">
                <?= $content ?>
            </main>
        </div>
    </div>

    <script src="<?= $assetBase ?>/js/collector/collector-workspace.js?v=<?= $workspaceScriptVersion ?>"></script>
    <?php if ($pageScript !== null): ?>
        <script src="<?= $assetBase ?>/js/<?= htmlspecialchars($pageScript, ENT_QUOTES, 'UTF-8') ?>"></script>
    <?php endif; ?>
</body>
</html>
