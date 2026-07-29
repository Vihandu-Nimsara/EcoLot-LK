<?php
$assetBase = htmlspecialchars($basePath . '/assets', ENT_QUOTES, 'UTF-8');
$pageStyles = [
    'dashboard' => 'public_user/dashboard.css',
    'my-requests' => 'public_user/my-requests.css',
    'new-request' => 'public_user/new-request.css',
    'feedback' => 'public_user/feedback.css',
    'profile' => 'public_user/profile.css',
];
$pageScripts = [
    'dashboard' => 'public_user/dashboard.js',
    'my-requests' => 'public_user/my-requests.js',
    'new-request' => 'public_user/new-request.js',
    'feedback' => 'public_user/feedback.js',
];
$pageStyle = $pageStyles[$currentPage ?? ''] ?? null;
$pageScript = $pageScripts[$currentPage ?? ''] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> - Customer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <?php if ($pageStyle !== null): ?>
        <link rel="stylesheet" href="<?= $assetBase ?>/css/<?= htmlspecialchars($pageStyle, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
</head>
<body>
    <div class="layout">
        <?php include __DIR__ . '/sidebar.php'; ?>

        <?= $content ?>
    </div>

    <?php if ($pageScript !== null): ?>
        <script src="<?= $assetBase ?>/js/<?= htmlspecialchars($pageScript, ENT_QUOTES, 'UTF-8') ?>"></script>
    <?php endif; ?>
</body>
</html>
