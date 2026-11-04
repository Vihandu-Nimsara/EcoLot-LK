<?php $escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
<section class="dashboard-page">

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon campaign">📅</div>
            <div class="stat-info">
                <span>Open Campaigns (Current / Future)</span>
                <h2><?= (int) $stats['campaigns'] ?></h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon request">📥</div>
            <div class="stat-info">
                <span>Requests Awaiting Risk Review</span>
                <h2><?= (int) $stats['reviews'] ?></h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon route">🚚</div>
            <div class="stat-info">
                <span>Schedules Ready for Assignment</span>
                <h2><?= (int) $stats['assignments'] ?></h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon verify">✓</div>
            <div class="stat-info">
                <span>Collections Awaiting Verification</span>
                <h2><?= (int) $stats['verifications'] ?></h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon lot">♻</div>
            <div class="stat-info">
                <span>E-Lots Accepting Bids</span>
            <h2><?= (int) $stats['lots'] ?></h2>
            </div>
        </div>
    </div>

    <section class="schedule-section">
        <div class="section-header">
            <div>
                <h2>Upcoming Collection Schedules</h2>
                <p>Next 10 upcoming schedules, earliest first. Cancelled and finished schedules are excluded.</p>
            </div>
            <a
                href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/officer/area-schedules"
                class="primary-btn dashboard-schedule-link"
            >
                Manage Schedules
                <span aria-hidden="true">→</span>
            </a>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Schedule ID</th>
                        <th>Campaign</th>
                        <th>Postal Code</th>
                        <th>Area</th>
                        <th>Collection Date</th>
                        <th>Requests</th>
                        <th>Capacity</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $schedule): ?>
                    <tr>
                        <td><a href="<?= $escape($basePath) ?>/officer/area-schedules/<?= (int) $schedule['schedule_id'] ?>">SCH-<?= (int) $schedule['schedule_id'] ?></a></td>
                        <td><?= $escape($schedule['campaign_name']) ?></td>
                        <td><?= $escape($schedule['postal_code']) ?></td>
                        <td><?= $escape($schedule['area_name']) ?></td>
                        <td><?= $escape($schedule['collection_date']) ?></td>
                        <td><?= (int) $schedule['request_count'] ?></td>
                        <td><?= (int) $schedule['request_capacity'] ?></td>
                        <td><span class="table-status" data-status="<?= $escape($schedule['schedule_status']) ?>"><?= $escape(str_replace('_', ' ', $schedule['schedule_status'])) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$schedules): ?><tr><td colspan="8">No upcoming collection schedules.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</section>
