<section class="assigned-requests-page">
    <div class="page-toolbar">
        <div>
            <h1>Assigned Pickup Requests</h1>
            <p>Route COL-RT-042 · Sector 7B · Today, April 24</p>
        </div>
        <div class="toolbar-actions">
            <button type="button" class="secondary-btn" data-toggle-filter>Filter Requests</button>
            <button type="button" class="primary-btn" data-daily-report>Print Daily Report</button>
        </div>
    </div>

    <section class="surface-card request-filter-card" data-filter-panel hidden>
        <div class="form-field">
            <label for="request-filter">Show Requests</label>
            <select id="request-filter" data-request-filter>
                <option value="all">All stops</option>
                <option value="flagged">Hazard flagged</option>
                <option value="pending">Pending pickup</option>
            </select>
        </div>
    </section>

    <div class="collector-summary-grid">
        <article class="collector-summary-card">
            <span>Total Stops</span><strong>48</strong><small>Planned for this shift</small>
        </article>
        <article class="collector-summary-card">
            <span>Picked Up</span><strong data-picked-up-count>0</strong>
            <div class="pickup-progress"><div data-pickup-progress></div></div>
        </article>
        <article class="collector-summary-card">
            <span>Pending</span><strong data-pending-count>4</strong><small>Assigned stops remaining</small>
        </article>
        <article class="collector-summary-card hazard">
            <span>Hazards</span><strong data-hazard-count>0</strong><small>Require officer review</small>
        </article>
    </div>

    <div class="request-workspace-grid">
        <section class="surface-card route-map-card">
            <div class="card-heading">
                <div>
                    <h2>Active Route Map</h2>
                    <p>Current collection route coverage.</p>
                </div>
                <span class="live-status">Live</span>
            </div>
            <div class="route-map">
                <img
                    src="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/images/map.jpeg"
                    alt="Active route from Kollupitiya to Wellawatta"
                >
            </div>
        </section>

        <section class="surface-card quick-status-card">
            <div class="card-heading">
                <div>
                    <h2>Quick Status</h2>
                    <p>Update or flag assigned stops.</p>
                </div>
            </div>

            <?php foreach ([
                ['id' => 'kol', 'code' => '#QA-KOL', 'zone' => 'Kollupitiya QA Zone'],
                ['id' => 'nar', 'code' => '#QA-NAR', 'zone' => 'Narahenpita QA Zone'],
                ['id' => 'raj', 'code' => '#QA-RAJ', 'zone' => 'Rajagiriya QA Zone'],
                ['id' => 'wel', 'code' => '#QA-WEL', 'zone' => 'Wellawatta QA Zone'],
            ] as $request): ?>
                <article class="quick-status-row" data-request-id="<?= $request['id'] ?>" data-request-row>
                    <div>
                        <strong><?= $request['code'] ?></strong>
                        <span><?= $request['zone'] ?></span>
                    </div>
                    <div class="quick-status-actions">
                        <button type="button" class="small-action-btn" data-send-update="<?= $request['id'] ?>">Update</button>
                        <button type="button" class="hazard-toggle" data-toggle-hazard="<?= $request['id'] ?>" aria-label="Toggle hazard">!</button>
                        <button type="button" class="clear-hazard" data-clear-hazard="<?= $request['id'] ?>" aria-label="Clear hazard">×</button>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    </div>

    <section class="surface-card quick-record-card">
        <div class="card-heading">
            <div>
                <h2>Quick Pickup Record</h2>
                <p>Save a weight and priority update for a request.</p>
            </div>
        </div>

        <form class="quick-record-form" data-quick-record-form>
            <div class="form-field">
                <label for="record-id">Request ID</label>
                <input id="record-id" name="id" type="text" required>
            </div>
            <div class="form-field">
                <label for="record-weight">Weight (kg)</label>
                <input id="record-weight" name="weight" type="number" min="0" step="0.1" required>
            </div>
            <div class="form-field">
                <label for="record-priority">Priority</label>
                <select id="record-priority" name="priority">
                    <option>Standard</option>
                    <option>Priority</option>
                </select>
            </div>
            <button type="submit" class="primary-btn">Save Record</button>
        </form>
        <p class="form-message" data-record-message aria-live="polite"></p>
    </section>
</section>
