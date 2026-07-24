<?php
$collectorName = "Kasun Perera";
$collectorId = "COLLECTOR ID: 8821";
$routeId = "COL-RT-042 (Sector 7B)";

$stats = [
    ["label" => "TOTAL STOPS", "value" => 48, "note" => "Planned for current shift"],
    ["label" => "PICKED UP", "value" => 32, "note" => "", "progress" => 66],
    ["label" => "PENDING", "value" => 12, "note" => "Estimated time: 2h 15m"],
    ["label" => "MISSED", "value" => 4, "note" => "Requires re-assignment", "danger" => true],
];

$quickStatus = [
    ["code" => "#QA-KOL", "zone" => "Kollupitiya QA Zone"],
    ["code" => "#QA-NAR", "zone" => "Narahenpita QA Zone"],
    ["code" => "#QA-RAJ", "zone" => "Rajagiriya QA Zone"],
    ["code" => "#QA-WEL", "zone" => "Wellawatta QA Zone"],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Collector Dashboard - EcoLot LK</title>
    <link rel="stylesheet" href="/EcoLot-LK/public/assets/css/app.css">
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div><h2>EcoLot LK</h2><p>Municipal Portal</p></div>
        </div>
        <nav class="sidebar-nav">
            <a href="#" class="active">⬡ Dashboard</a>
            <a href="#">🚏 Routes</a>
            <a href="#">📋 My Requests</a>
        </nav>
        <div class="sidebar-user">
            <p class="user-name"><?= htmlspecialchars($collectorName) ?></p>
            <p class="user-id"><?= htmlspecialchars($collectorId) ?></p>
        </div>
    </aside>
    <main>
        <div class="page-header-row">
            <div>
                <h1>Collector Dashboard [CRUD Operations View]</h1>
                <p>Route ID: <strong><?= htmlspecialchars($routeId) ?></strong> · Today, <?= date('M j, Y') ?></p>
            </div>
            <div class="header-actions">
                <button class="btn-filter">☰ Filter</button>
                <button class="btn-report">◉ Daily Report</button>
            </div>
        </div>
        <div class="stats-row">
            <?php foreach ($stats as $stat): ?>
                <div class="stat-card <?= isset($stat['danger']) ? 'danger' : '' ?>">
                    <p class="stat-label"><?= htmlspecialchars($stat['label']) ?></p>
                    <h2 class="stat-value"><?= htmlspecialchars($stat['value']) ?></h2>
                    <?php if (!empty($stat['note'])): ?><p class="stat-note"><?= htmlspecialchars($stat['note']) ?></p><?php endif; ?>
                    <?php if (isset($stat['progress'])): ?><div class="progress-bar"><div class="progress-fill" style="width: <?= $stat['progress'] ?>%"></div></div><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="dashboard-grid">
            <div class="map-card">
                <div class="map-card-header"><span>Active Route Map</span><span class="live-badge">LIVE</span></div>
                <div class="map-placeholder">📍 Map view (Google Maps integration pending)</div>
            </div>
            <div class="quick-status-card">
                <h3>Quick Status</h3>
                <?php foreach ($quickStatus as $item): ?>
                    <div class="quick-status-row">
                        <div><p class="qs-code"><?= htmlspecialchars($item['code']) ?></p><p class="qs-zone"><?= htmlspecialchars($item['zone']) ?></p></div>
                        <div class="qs-actions"><button class="btn-update">Update</button><button class="icon-btn">⚑</button><button class="icon-btn">🗑</button></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="quick-record-card">
            <div class="qr-header"><span>Quick Record</span><span class="update-badge">UPDATE</span></div>
            <form class="qr-form">
                <input type="text" placeholder="ID">
                <input type="text" placeholder="Weight">
                <select><option>Standard</option><option>Priority</option></select>
                <button type="submit" class="btn-save">Save</button>
            </form>
        </div>
    </main>
</body>
</html>