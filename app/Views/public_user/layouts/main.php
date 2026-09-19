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
$themeVersion = (string) filemtime(dirname(__DIR__, 4) . '/public/assets/css/public_user/theme.css');
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
            href="<?= $assetBase ?>/css/<?= htmlspecialchars($pageStyle, ENT_QUOTES, 'UTF-8') ?>"
        >
    <?php endif; ?>

    <link rel="stylesheet" href="<?= $assetBase ?>/css/typography.css">
</head>
<body class="public-user-app">
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
        <script src="<?= $assetBase ?>/js/<?= htmlspecialchars($pageScript, ENT_QUOTES, 'UTF-8') ?>"></script>
    <?php endif; ?>
</body>
</html>
