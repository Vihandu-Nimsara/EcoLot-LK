<main>
    <div class="page-header-row">
        <div>
            <h1>My Requests</h1>
            <p>Route ID: <strong>COL-RT-042 (Sector 7B)</strong> · Today, April 24</p>
        </div>
        <div class="header-actions">
            <button class="btn-filter" type="button" data-toggle-filter>☰ Filter</button>
            <button class="btn-report" type="button" data-daily-report>◉ Daily Report</button>
        </div>
    </div>

    <div class="filter-panel" data-filter-panel hidden>
        <label for="requestFilter">Show requests</label>
        <select id="requestFilter" data-request-filter>
            <option value="all">All stops</option>
            <option value="flagged">Hazard flagged</option>
            <option value="pending">Pending pickup</option>
        </select>
    </div>

    <div class="stats-row">
        <section class="stat-card"><p class="stat-label">TOTAL STOPS</p><h2 class="stat-value">48</h2><p class="stat-note">Planned for current shift</p></section>
        <section class="stat-card"><p class="stat-label">PICKED UP</p><h2 class="stat-value" data-picked-up-count>0</h2><div class="progress-bar"><div class="progress-fill" data-pickup-progress></div></div></section>
        <section class="stat-card"><p class="stat-label">PENDING</p><h2 class="stat-value" data-pending-count>4</h2><p class="stat-note">Assigned stops on this route</p></section>
        <section class="stat-card danger"><p class="stat-label">HAZARDS</p><h2 class="stat-value" data-hazard-count>0</h2><p class="stat-note">Requires officer review</p></section>
    </div>

    <div class="dashboard-grid">
        <section class="map-card">
            <div class="map-card-header"><span>Active Route Map</span><span class="live-badge">LIVE</span></div>
            <div class="map-placeholder">
                <img src="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/images/map.jpeg" alt="Active route from Kollupitiya to Wellawatta">
            </div>
        </section>

        <section class="quick-status-card">
            <h3>Quick Status</h3>
            <?php foreach ([
                ['id' => 'kol', 'code' => '#QA-KOL', 'zone' => 'Kollupitiya QA Zone'],
                ['id' => 'nar', 'code' => '#QA-NAR', 'zone' => 'Narahenpita QA Zone'],
                ['id' => 'raj', 'code' => '#QA-RAJ', 'zone' => 'Rajagiriya QA Zone'],
                ['id' => 'wel', 'code' => '#QA-WEL', 'zone' => 'Wellawatta QA Zone'],
            ] as $request): ?>
                <div class="quick-status-row" data-flagid="<?= $request['id'] ?>" data-request-row>
                    <div><p class="qs-code"><?= $request['code'] ?></p><p class="qs-zone"><?= $request['zone'] ?></p></div>
                    <div class="qs-actions">
                        <button class="btn-update" type="button" data-send-update="<?= $request['id'] ?>">Update</button>
                        <button class="icon-btn flag-btn" type="button" data-toggle-hazard="<?= $request['id'] ?>" aria-label="Toggle hazard">⚑</button>
                        <button class="icon-btn" type="button" data-clear-hazard="<?= $request['id'] ?>" aria-label="Clear hazard">🗑</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
    </div>

    <section class="quick-record-card">
        <div class="qr-header"><span>Quick Record</span><span class="update-badge">UPDATE</span></div>
        <form class="qr-form" data-quick-record-form>
            <label class="sr-only" for="recordId">Request ID</label><input id="recordId" name="id" type="text" placeholder="Request ID" required>
            <label class="sr-only" for="recordWeight">Weight</label><input id="recordWeight" name="weight" type="text" placeholder="Weight (kg)" required>
            <label class="sr-only" for="recordPriority">Priority</label><select id="recordPriority" name="priority"><option>Standard</option><option>Priority</option></select>
            <button type="submit" class="btn-save">Save</button>
        </form>
        <p class="form-message" data-record-message aria-live="polite"></p>
    </section>
</main>
