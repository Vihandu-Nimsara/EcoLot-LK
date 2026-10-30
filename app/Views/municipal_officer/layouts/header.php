<?php
$officer = (new User())->find((int) Auth::id());
$profileEscape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
?>
<header class="top-header">

    <div class="header-left">
        <h1>Officer Workspace</h1>
        <p>Manage campaigns, schedules, requests, collection assignments, collections, and E-Lots.</p>
    </div>

    <div class="header-right">
        <details class="profile">
            <summary style="display:flex;align-items:center;gap:12px;cursor:pointer">
            <div class="avatar" aria-hidden="true">MO</div>
            <div class="profile-info">
                <span class="name"><?= $profileEscape($officer['full_name'] ?? Auth::user()['name'] ?? 'Municipal Officer') ?></span>
                <span class="role">Municipal Officer</span>
            </div>
            </summary>
            <div class="profile-details">
                <p>Municipal Officer</p>
                <p><?= $profileEscape($officer['mobile_number'] ?? '') ?></p>
                <form method="post" action="<?= $profileEscape($basePath) ?>/logout">
                    <input type="hidden" name="_csrf_token" value="<?= $profileEscape(Csrf::token()) ?>">
                    <button type="submit" class="secondary-btn">Logout</button>
                </form>
            </div>
        </details>
    </div>

</header>
