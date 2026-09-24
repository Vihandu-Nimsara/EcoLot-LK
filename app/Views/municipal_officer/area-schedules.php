<?php
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$scheduleUrl = $basePath . '/officer/area-schedules';
$filterCampaigns = [];
foreach ($schedules as $schedule) {
    $filterCampaigns[$schedule['campaign_id']] = $schedule['campaign_name'] . ' — ' . substr($schedule['campaign_month'], 0, 7);
}
?>
<section class="area-schedules-page">
    <div class="page-toolbar">
        <div><h1>Area Collection Schedules</h1><p>Assign collection dates and capacity limits for postal-code areas.</p></div>
        <div class="toolbar-actions"><a href="<?= $escape($scheduleUrl . '?create=1#create-schedule') ?>" class="primary-btn create-schedule-trigger">+ Create Schedule</a></div>
    </div>
    <?php if ($notice): ?><p class="schedule-notice" role="status"><?= $escape($notice) ?></p><?php endif; ?>
    <section class="scheduled-areas-card">
        <div class="scheduled-areas-header">
            <div><h2>Scheduled Area Dates</h2><p>View and manage collection schedules.</p></div>
            <div class="campaign-filter">
                <label for="campaign-filter">Campaign</label>
                <select id="campaign-filter">
                    <option value="">All Campaigns</option>
                    <?php foreach ($filterCampaigns as $id => $label): ?>
                        <option value="<?= $escape($id) ?>"><?= $escape($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="schedule-table-wrapper">
            <table class="schedule-table">
                <thead><tr><th>Campaign</th><th>Postal Code Area</th><th>Collection Date</th><th>Request Cut-off</th><th>Requests / Maximum</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($schedules as $schedule): ?>
                        <tr data-campaign-id="<?= $escape($schedule['campaign_id']) ?>">
                            <td><?= $escape($schedule['campaign_name']) ?><br><?= $escape(substr($schedule['campaign_month'], 0, 7)) ?></td>
                            <td><?= $escape($schedule['area_name']) ?><br><?= $escape($schedule['postal_code']) ?></td>
                            <td><?= $escape($schedule['collection_date']) ?></td>
                            <td><?= $escape($schedule['request_cutoff_at']) ?></td>
                            <td><?= $escape($schedule['active_request_count']) ?> / <?= $escape($schedule['request_capacity']) ?></td>
                            <td><span class="status <?= $escape(strtolower($schedule['schedule_status'])) ?>"><?= $escape($schedule['schedule_status']) ?></span><?php if ($intake = AreaCollectionSchedule::intakeLabel($schedule)): ?><br><small><?= $escape($intake) ?></small><?php endif; ?></td>
                            <td><a class="edit-btn" href="<?= $escape($scheduleUrl . '/' . $schedule['schedule_id']) ?>">View / Edit</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($schedules === []): ?><tr><td colspan="7">No collection schedules yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <dialog class="schedule-create-dialog schedule-dialog-card officer-dialog-card" id="create-schedule" aria-labelledby="create-title" <?= $showCreate ? 'open' : '' ?>>
        <div class="officer-dialog-header">
            <h2 id="create-title">Create Schedule</h2>
            <a href="<?= $escape($scheduleUrl) ?>" class="officer-dialog-close" data-close-schedule aria-label="Close create schedule">×</a>
        </div>
        <p>New schedules start as PLANNED. Dates and area cannot be changed after creation. Times use Sri Lanka time.</p>
        <?php if ($errors !== []): ?>
            <div class="schedule-errors" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= $escape($error) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form class="schedule-create-form" method="post" action="<?= $escape($scheduleUrl) ?>">
            <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>">
            <div class="form-group">
                <label for="schedule-campaign">Monthly Campaign</label>
                <select id="schedule-campaign" name="campaign_id" required>
                    <option value="">Select an open campaign</option>
                    <?php foreach ($campaigns as $campaign): ?>
                        <option value="<?= $escape($campaign['campaign_id']) ?>" <?= (string) ($old['campaign_id'] ?? '') === (string) $campaign['campaign_id'] ? 'selected' : '' ?>><?= $escape($campaign['campaign_name'] . ' — ' . substr($campaign['campaign_month'], 0, 7)) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($campaigns === []): ?><small>No open campaigns are available.</small><?php endif; ?>
            </div>
            <div class="schedule-form-grid">
                <div class="form-group">
                    <label for="schedule-area">Postal Code Area</label>
                    <select id="schedule-area" name="postal_area_id" required>
                        <option value="">Select an active area</option>
                        <?php foreach ($areas as $area): ?>
                            <option value="<?= $escape($area['postal_area_id']) ?>" <?= (string) ($old['postal_area_id'] ?? '') === (string) $area['postal_area_id'] ? 'selected' : '' ?>><?= $escape($area['area_name'] . ' — ' . $area['postal_code']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="schedule-capacity">Maximum Requests</label>
                    <input type="number" id="schedule-capacity" name="request_capacity" min="1" max="4294967295" step="1" value="<?= $escape($old['request_capacity'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="schedule-cutoff">Request Cut-off Date</label>
                    <input type="date" id="schedule-cutoff" name="request_cutoff_date" value="<?= $escape($old['request_cutoff_date'] ?? '') ?>" required>
                    <small>Requests close at 23:59:59 on this date.</small>
                </div>
                <div class="form-group">
                    <label for="schedule-collection">Collection Date</label>
                    <input type="date" id="schedule-collection" name="collection_date" value="<?= $escape($old['collection_date'] ?? '') ?>" required>
                </div>
            </div>
            <div class="schedule-dialog-actions officer-dialog-actions">
                <a class="secondary-btn" data-close-schedule href="<?= $escape($scheduleUrl) ?>">Cancel</a>
                <button type="submit" class="primary-btn">Create Schedule</button>
            </div>
        </form>
    </dialog>
</section>
