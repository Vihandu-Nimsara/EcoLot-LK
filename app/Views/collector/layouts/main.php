<?php
$assetBase = htmlspecialchars($basePath . '/assets', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> - Collector Portal</title>
    <link rel="stylesheet" href="<?= $assetBase ?>/css/collector/app.css">
</head>
<body class="collector-app">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <?= $content ?>

    <script src="<?= $assetBase ?>/js/collector/app.js"></script>
</body>
</html>
