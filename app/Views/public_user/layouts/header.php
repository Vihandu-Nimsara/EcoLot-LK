<?php
$userName = trim((string) (Auth::user()['name'] ?? '')) ?: 'Public User';
$initials = '';
foreach (array_slice(preg_split('/\s+/', $userName) ?: [], 0, 2) as $part) {
    $initials .= mb_strtoupper(mb_substr($part, 0, 1));
}
?>

<header class="top-header">
    <div class="header-left">
        <h1>Public User Workspace</h1>
        <p>Schedule e-waste pickups, track requests, and manage your account.</p>
    </div>

    <div class="header-right">
        <div class="profile">
        <div class="avatar"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>            <div class="profile-info">
                 <span class="name"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></span>                
                 <span class="role">Public User</span>
            </div>
        </div>
    </div>
</header>
