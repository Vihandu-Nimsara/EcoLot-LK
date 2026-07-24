<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoLot LK - Public User</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Exo+2:ital,wght@0,100..900;1,100..900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- CSS Assets -->
    <link rel="stylesheet" href="/EcoLot-LK/public/assets/css/public_user/<?php echo $currentPage; ?>.css">
</head>
<body>

<div class="layout">
    <!-- Include Reusable Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>
    
    <!-- Main Dynamic Content -->
    <div class="content">
        <?= $content ?>
    </div>
</div>

</body>
</html>
