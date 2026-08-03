<?php
$assetBase = htmlspecialchars($basePath . '/assets', ENT_QUOTES, 'UTF-8');
$pageStyles = [
    'dashboard' => 'municipal_officer/dashboard.css',
    'campaigns' => 'municipal_officer/campaigns.css',
    'area-schedules' => 'municipal_officer/area-schedules.css',
    'flagged-requests' => 'municipal_officer/flagged-requests.css',
    'routes' => 'municipal_officer/routes.css',
    'collection-records' => 'municipal_officer/collection-records.css',
    'e-lots' => 'municipal_officer/elots.css',
    'feedback' => 'municipal_officer/feedback.css',
    'reports' => 'municipal_officer/reports.css',
];
$pageScripts = [
    'campaigns' => 'municipal_officer/campaigns.js',
    'area-schedules' => 'municipal_officer/area-schedules.js',
    'flagged-requests' => 'municipal_officer/flagged-requests.js',
    'routes' => 'municipal_officer/routes.js',
    'collection-records' => 'municipal_officer/collection-records.js',
    'e-lots' => 'municipal_officer/elots.js',
    'feedback' => 'municipal_officer/feedback.js',
    'reports' => 'municipal_officer/reports.js',
];
$pageStyle = $pageStyles[$currentPage ?? ''] ?? null;
$pageScript = $pageScripts[$currentPage ?? ''] ?? null;
$pageStyleVersion = $pageStyle !== null
    ? (string) filemtime(dirname(__DIR__, 4) . '/public/assets/css/' . $pageStyle)
    : null;
$pageScriptVersion = $pageScript !== null
    ? (string) filemtime(dirname(__DIR__, 4) . '/public/assets/js/' . $pageScript)
    : null;
$themeVersion = (string) filemtime(dirname(__DIR__, 4) . '/public/assets/css/municipal_officer/theme.css');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> - Municipal Officer</title>

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
            href="<?= $assetBase ?>/css/<?= htmlspecialchars($pageStyle, ENT_QUOTES, 'UTF-8') ?>?v=<?= $pageStyleVersion ?>"
        >
    <?php endif; ?>

    <link rel="stylesheet" href="<?= $assetBase ?>/css/typography.css">
    <link rel="stylesheet" href="<?= $assetBase ?>/css/municipal_officer/theme.css?v=<?= $themeVersion ?>">
</head>

<body class="officer-app">
    <div class="app-layout">
        <?php include __DIR__ . '/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/header.php'; ?>

            <main class="page-content">
                <?= $content ?>
            </main>
        </div>
    </div>

    <?php if ($pageScript !== null): ?>
        <script
            src="<?= $assetBase ?>/js/<?= htmlspecialchars($pageScript, ENT_QUOTES, 'UTF-8') ?>?v=<?= $pageScriptVersion ?>"
        ></script>
    <?php endif; ?>
</body>

</html>
