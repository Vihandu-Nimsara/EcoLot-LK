<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        EcoLot LK - Municipal Officer
    </title>


    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">


    <!-- Main CSS -->
    <link rel="stylesheet" href="/EcoLot-LK/public/assets/css/style.css">
    <link rel="stylesheet" href="/EcoLot-LK/public/assets/css/municipal_officer/campaigns.css">
    <link rel="stylesheet"
      href="/EcoLot-LK/public/assets/css/municipal_officer/area-schedules.css">
      <link rel="stylesheet"
      href="/EcoLot-LK/public/assets/css/municipal_officer/flagged-requests.css">

    <link rel="stylesheet"
      href="/EcoLot-LK/public/assets/css/municipal_officer/routes.css">
    <link rel="stylesheet"
      href="/EcoLot-LK/public/assets/css/municipal_officer/collection-records.css">
    <link rel="stylesheet"
      href="/EcoLot-LK/public/assets/css/municipal_officer/elots.css">
    <link rel="stylesheet"
      href="/EcoLot-LK/public/assets/css/municipal_officer/feedback.css">

    <!-- Sidebar CSS -->
    <link rel="stylesheet" href="/EcoLot-LK/public/assets/css/municipal_officer/sidebar.css">

    <link rel="stylesheet" href="/EcoLot-LK/public/assets/css/municipal_officer/header.css">

    <link rel="stylesheet" href="/EcoLot-LK/public/assets/css/municipal_officer/dashboard.css">


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