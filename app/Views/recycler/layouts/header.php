<header class="top-header">

    <button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="recycler-sidebar" data-nav-toggle>
        <span aria-hidden="true">☰</span>
    </button>

    <div class="header-left">
        <h1>Recycler Workspace</h1>
        <p>Review eligible E-Lots, manage bids, and track awarded recycling work.</p>
    </div>

    <div class="header-right">
        <div class="profile">
            <div class="avatar" aria-hidden="true">R</div>
            <div class="profile-info">
                <span class="name"><?= htmlspecialchars((string) (Auth::user()['name'] ?? 'Recycler'), ENT_QUOTES, 'UTF-8') ?></span>
                <span class="role">Recycler</span>
            </div>
        </div>
    </div>

</header>
