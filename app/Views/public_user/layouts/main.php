<?php
$assetBase = htmlspecialchars($basePath . '/assets', ENT_QUOTES, 'UTF-8');
$pageStyles = [
    'dashboard' => 'public_user/pickup-dashboard.css',
    'my-requests' => 'public_user/pickup-request-history.css',
    'new-request' => 'public_user/pickup-request-form.css',
    'feedback' => 'public_user/feedback-form.css',
    'profile' => 'public_user/account-profile.css',
];
$pageScripts = [
    'dashboard' => 'public_user/pickup-dashboard.js',
    'my-requests' => 'public_user/pickup-request-history.js',
    'new-request' => 'public_user/pickup-request-form.js',
    'feedback' => 'public_user/feedback-form.js',
];
$pageStyle = $pageStyles[$currentPage ?? ''] ?? null;
$pageScript = $pageScripts[$currentPage ?? ''] ?? null;
$themeVersion = Asset::version('css/public_user/theme.css');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> - Public User</title>

    <?php require dirname(__DIR__, 2) . '/components/workspace-head.php'; ?>
    <link rel="stylesheet" href="<?= $assetBase ?>/css/public_user/theme.css?v=<?= $themeVersion ?>">

    <?php if ($pageStyle !== null): ?>
        <link
            rel="stylesheet"
            href="<?= $assetBase ?>/css/<?= htmlspecialchars($pageStyle, ENT_QUOTES, 'UTF-8') ?>?v=<?= Asset::version('css/' . $pageStyle) ?>"
        >
    <?php endif; ?>

    <link rel="stylesheet" href="<?= $assetBase ?>/css/typography.css">
</head>
<body class="public-user-app">
    <?php
    $workspaceLayoutDirectory = __DIR__;
    $workspaceNavigationOverlay = false;
    require dirname(__DIR__, 2) . '/components/workspace-body.php';
    ?>

    <?php if ($pageScript !== null): ?>
        <script src="<?= $assetBase ?>/js/<?= htmlspecialchars($pageScript, ENT_QUOTES, 'UTF-8') ?>?v=<?= Asset::version('js/' . $pageScript) ?>"></script>
    <?php endif; ?>
</body>
</html>
