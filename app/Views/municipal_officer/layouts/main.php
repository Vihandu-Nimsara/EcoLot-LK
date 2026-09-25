<?php
$assetBase = htmlspecialchars($basePath . '/assets', ENT_QUOTES, 'UTF-8');
$pageStyles = [
    'dashboard' => 'municipal_officer/dashboard.css',
    'campaigns' => 'municipal_officer/campaigns.css',
    'area-schedules' => 'municipal_officer/area-schedules.css',
    'flagged-requests' => 'municipal_officer/flagged-requests.css',
    'collection-assignments' => 'municipal_officer/collection-assignments.css',
    'collection-records' => 'municipal_officer/collection-records.css',
    'e-lots' => 'municipal_officer/elots.css',
    'feedback' => 'municipal_officer/feedback.css',
    'reports' => 'municipal_officer/reports.css',
];
$pageScripts = [
    'campaigns' => 'municipal_officer/campaigns.js',
    'area-schedules' => 'municipal_officer/area-schedules.js',
    'flagged-requests' => 'municipal_officer/flagged-requests.js',
    'collection-assignments' => 'municipal_officer/collection-assignments.js',
    'collection-records' => 'municipal_officer/collection-records.js',
    'e-lots' => 'municipal_officer/elots.js',
    'feedback' => 'municipal_officer/feedback.js',
    'reports' => 'municipal_officer/reports.js',
];
$pageStyle = $pageStyles[$currentPage ?? ''] ?? null;
$pageScript = $pageScripts[$currentPage ?? ''] ?? null;
$pageStyleVersion = $pageStyle !== null
    ? Asset::version('css/' . $pageStyle)
    : null;
$pageScriptVersion = $pageScript !== null
    ? Asset::version('js/' . $pageScript)
    : null;
$themeVersion = Asset::version('css/municipal_officer/theme.css');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> - Municipal Officer</title>

    <?php require dirname(__DIR__, 2) . '/components/workspace-head.php'; ?>

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
    <?php
    $workspaceLayoutDirectory = __DIR__;
    $workspaceNavigationOverlay = false;
    require dirname(__DIR__, 2) . '/components/workspace-body.php';
    ?>

    <?php if ($pageScript !== null): ?>
        <script
            src="<?= $assetBase ?>/js/<?= htmlspecialchars($pageScript, ENT_QUOTES, 'UTF-8') ?>?v=<?= $pageScriptVersion ?>"
        ></script>
    <?php endif; ?>
</body>

</html>
