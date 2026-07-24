<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>EcoLot LK - Recycler</title>


    <!-- Fonts -->
    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Exo+2:wght@600;700&family=Inter:wght@400;500;600&display=swap"
        rel="stylesheet"
    >


    <!-- Global CSS -->
    <link
        rel="stylesheet"
        href="/EcoLot-LK/public/assets/css/style.css"
    >


    <!-- Shared Recycler CSS -->
    <link
        rel="stylesheet"
        href="/EcoLot-LK/public/assets/css/sidebar.css"
    >

    <link
        rel="stylesheet"
        href="/EcoLot-LK/public/assets/css/header.css"
    >


    <!-- Page Specific CSS -->

    <?php if (($currentPage ?? '') === 'dashboard'): ?>

        <link
            rel="stylesheet"
            href="/EcoLot-LK/public/assets/css/recycler/dashboard.css"
        >

    <?php elseif (($currentPage ?? '') === 'eligible_e-lots'): ?>

        <link
            rel="stylesheet"
            href="/EcoLot-LK/public/assets/css/recycler/eligible_e-lots.css"
        >

    <?php elseif (($currentPage ?? '') === 'my_bids'): ?>

        <link
            rel="stylesheet"
            href="/EcoLot-LK/public/assets/css/recycler/my_bids.css"
        >

    <?php elseif (($currentPage ?? '') === 'awarded_e-lots'): ?>

        <link
            rel="stylesheet"
            href="/EcoLot-LK/public/assets/css/recycler/awarded_e-lots.css"
        >

    <?php endif; ?>

</head>


<body>

<div class="app-layout">

    <?php include __DIR__ . "/sidebar.php"; ?>


    <div class="main-content">

        <?php include __DIR__ . "/header.php"; ?>


        <main class="page-content">

            <?= $content ?>

        </main>

    </div>

</div>

</body>

</html>