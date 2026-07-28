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
        <img src="/EcoLot-LK/public/assets/images/logo.jpeg" alt="EcoLot LK Logo">
        <div><h2>EcoLot LK</h2><p>MUNICIPAL PORTAL</p></div>
    </div>
    <nav class="sidebar-nav">
        <a href="/EcoLot-LK/app/Views/collector/dashboard.php" class="active">🚏 My Routes</a>
        <a href="/EcoLot-LK/app/Views/collector/crud_dashboard.php">📄 My Requests</a>
    </nav>
    <div class="sidebar-user">
        <p class="user-name">kasun perera</p>
        <p class="user-id">ID: LK-COL-082</p>
    </div>
    <a href="#" class="signout">↪ Sign Out</a>
</aside>

<main>
    <div class="page-header">
        <h1>Good Morning, kasun perea</h1>
        <p>Here are your assigned routes for today, April 24.</p>
    </div>

    <div class="section-row">
        <h2>Today's Assigned Routes</h2>
        <span class="updated-badge">Updated 5m ago</span>
    </div>

    <div class="route-card" data-id="kol" data-weight="18.4 kg" data-time="8:42 AM">
        <div class="route-info">
            <h3>Kollupitiya QA Zone <span class="badge">SCHEDULED</span> <span class="flag-icon" data-flagid="kol">🚩</span></h3>
            <p>📍 Galle Road - Liberty Plaza Sector</p>
        </div>
        <div class="route-actions">
            <button class="btn-confirm" onclick="confirmPickup(this)">✔ Confirm Pickup</button>
            <button class="btn-view" onclick="openDetails('kol')">👁 View Details</button>
        </div>
    </div>

    <div class="route-card" data-id="nar" data-weight="22.1 kg" data-time="9:15 AM">
        <div class="route-info">
            <h3>Narahenpita QA Zone <span class="badge">AFTERNOON</span> <span class="flag-icon" data-flagid="nar">🚩</span></h3>
            <p>📍 Kirimandala Mawatha - Hospital District</p>
        </div>
        <div class="route-actions">
            <button class="btn-confirm" onclick="confirmPickup(this)">✔ Confirm Pickup</button>
            <button class="btn-view" onclick="openDetails('nar')">👁 View Details</button>
        </div>
    </div>

    <div class="route-card" data-id="raj" data-weight="15.7 kg" data-time="10:03 AM">
        <div class="route-info">
            <h3>Rajagiriya QA Zone <span class="badge in-progress">IN PROGRESS</span> <span class="flag-icon" data-flagid="raj">🚩</span></h3>
            <p>📍 Parliament Road - Ethul Kotte Junction</p>
        </div>
        <div class="route-actions">
            <button class="btn-confirm" onclick="confirmPickup(this)">✔ Confirm Pickup</button>
            <button class="btn-view" onclick="openDetails('raj')">👁 View Details</button>
        </div>
    </div>

    <div class="route-card" data-id="wel" data-weight="9.9 kg" data-time="10:40 AM">
        <div class="route-info">
            <h3>Wellawatta QA Zone <span class="badge">SCHEDULED</span> <span class="flag-icon" data-flagid="wel">🚩</span></h3>
            <p>📍 W.A. Silva Mawatha - Canal Side</p>
        </div>
        <div class="route-actions">
            <button class="btn-confirm" onclick="confirmPickup(this)">✔ Confirm Pickup</button>
            <button class="btn-view" onclick="openDetails('wel')">👁 View Details</button>
        </div>
    </div>
</main>

<div class="urgent-notice">
    <h4>URGENT NOTICE</h4>
    <p>Road closure on Flower Road. Redirecting Sector B routes via Green Path.</p>
</div>

<div class="modal-overlay" id="detailsModal">
    <div class="modal-box">
        <button class="modal-close" onclick="closeDetails()">✕</button>
        <h3 id="modalZoneName"></h3>
        <p><strong>Weight of picked item:</strong> <span id="modalWeight"></span></p>
        <p><strong>Time:</strong> <span id="modalTime"></span></p>
        <button class="btn-hazard" onclick="markHazard()">⚠ Mark as Hazard</button>
    </div>
</div>

<script src="/EcoLot-LK/public/assets/js/app.js"></script>
</body>
</html>