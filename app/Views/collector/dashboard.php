<?php
// <?php
// Dummy data for now — will be replaced by real DB data later
$collectorName = "kasun perea";
$collectorId = "ID: LK-COL-082";

$routes = [
    ["zone" => "Kollupitiya QA Zone", "status" => "SCHEDULED", "location" => "Galle Road - Liberty Plaza Sector"],
    ["zone" => "Narahenpita QA Zone", "status" => "AFTERNOON", "location" => "Kirimandala Mawatha - Hospital District"],
    ["zone" => "Rajagiriya QA Zone", "status" => "IN PROGRESS", "location" => "Parliament Road - Ethul Kotte Junction"],
    ["zone" => "Wellawatta QA Zone", "status" => "SCHEDULED", "location" => "W.A. Silva Mawatha - Canal Side"],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Routes - EcoLot LK</title>
    <link rel="stylesheet" href="/EcoLot-LK/public/assets/css/app.css">
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-logo">
            <div>
                <h2>EcoLot LK</h2>
                <p>MUNICIPAL PORTAL</p>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="#">📊 Dashboard</a>
            <a href="#" class="active">🚏 My Routes</a>
            <a href="#">📄 My Requests</a>
        </nav>
        <div class="sidebar-user">
            <p class="user-name"><?= htmlspecialchars($collectorName) ?></p>
            <p class="user-id"><?= htmlspecialchars($collectorId) ?></p>
        </div>
        <a href="#" class="signout">↪ Sign Out</a>
    </aside>

    <main>
        <div class="page-header">
            <h1>Good Morning, <?= htmlspecialchars($collectorName) ?></h1>
            <p>Here are your assigned routes for today, <?= date('F j') ?>.</p>
        </div>

        <div class="section-row">
            <h2>Today's Assigned Routes</h2>
            <span class="updated-badge">Updated 5m ago</span>
        </div>

        <?php foreach ($routes as $route): ?>
            <div class="route-card">
                <div class="route-info">
                    <h3>
                        <?= htmlspecialchars($route['zone']) ?>
                        <span class="badge <?= $route['status'] === 'IN PROGRESS' ? 'in-progress' : '' ?>">
                            <?= htmlspecialchars($route['status']) ?>
                        </span>
                    </h3>
                    <p>📍 <?= htmlspecialchars($route['location']) ?></p>
                </div>
                <div class="route-actions">
                    <button class="btn-confirm">✔ Confirm Pickup</button>
                    <button class="btn-view">👁 View Details</button>
                </div>
            </div>
        <?php endforeach; ?>
    </main>

    <div class="urgent-notice">
        <h4>URGENT NOTICE</h4>
        <p>Road closure on Flower Road. Redirecting Sector B routes via Green Path.</p>
    </div>

</body>
</html>Collector dashboard placeholder.
