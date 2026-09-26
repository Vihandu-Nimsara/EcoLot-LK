<?php
$collectorName = trim((string) (Auth::user()['name'] ?? '')) ?: 'Collector';
?>
<header class="top-header">
    <div class="header-left">
        <h1>Collector Workspace</h1>
        <p>Review assigned schedules and record accurate collection details.</p>
    </div>

    <div class="header-right">
        <div class="profile">
            <div class="avatar" data-collector-initials aria-hidden="true">C</div>
            <div class="profile-info">
                <span class="name" data-collector-name><?= htmlspecialchars($collectorName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                <span class="role">Collection Officer</span>
            </div>
        </div>
    </div>
</header>
