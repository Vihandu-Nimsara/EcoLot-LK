<?php
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$scheduleUrl = $basePath . '/officer/area-schedules';
$details = [
    'Campaign Name' => $schedule['campaign_name'],
    'Campaign Month' => substr($schedule['campaign_month'], 0, 7),
    'Postal Code' => $schedule['postal_code'],
    'Area Name' => $schedule['area_name'],
    'Collection Date' => $schedule['collection_date'],
    'Request Cut-off' => $schedule['request_cutoff_at'],
    'Maximum Requests' => $schedule['request_capacity'],
    'Current Active Request Count' => $schedule['active_request_count'],
    'Remaining Capacity' => max(0, (int) $schedule['request_capacity'] - (int) $schedule['active_request_count']),
    'Status' => $schedule['schedule_status'],
    'Created By' => $schedule['created_by_name'],
    'Created At' => $schedule['created_at'],
];
?>
<section class="area-schedules-page">
    <div class="page-toolbar">
        <div><h1>Collection Schedule #<?= $escape($schedule['schedule_id']) ?></h1><p>Review schedule details and manage capacity. All times use Sri Lanka time.</p></div>
        <a class="secondary-btn" href="<?= $escape($scheduleUrl) ?>">Back to schedules</a>
    </div>
    <?php if ($errors !== []): ?>
        <div class="schedule-errors" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= $escape($error) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <section class="scheduled-areas-card">
        <h2>Schedule Details</h2>
        <dl class="schedule-details-grid">
            <?php foreach ($details as $label => $value): ?><div><dt><?= $escape($label) ?></dt><dd><?= $escape($value) ?></dd></div><?php endforeach; ?>
        </dl>
    </section>
    <section class="scheduled-areas-card schedule-editor">
        <h2>Edit Schedule</h2><p>Only maximum requests and permitted status changes can be saved.</p>
        <form class="schedule-create-form" method="post" action="<?= $escape($scheduleUrl . '/' . $schedule['schedule_id'] . '/update') ?>">
            <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>">
            <div class="schedule-form-grid">
                <div class="form-group">
                    <label for="edit-capacity">Maximum Requests</label>
                    <input type="number" id="edit-capacity" name="request_capacity" min="<?= $escape(max(1, (int) $schedule['active_request_count'])) ?>" max="4294967295" step="1" value="<?= $escape($old['request_capacity'] ?? $schedule['request_capacity']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="edit-status">Schedule Status</label>
                    <?php if (count($statuses) > 1): ?>
                        <select id="edit-status" name="schedule_status" required>
                            <?php foreach ($statuses as $status): ?><option value="<?= $escape($status) ?>" <?= ($old['schedule_status'] ?? $schedule['schedule_status']) === $status ? 'selected' : '' ?>><?= $escape($status) ?></option><?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input id="edit-status" value="<?= $escape($schedule['schedule_status']) ?>" readonly>
                        <input type="hidden" name="schedule_status" value="<?= $escape($schedule['schedule_status']) ?>">
                        <small>No manual status transition is available.</small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="schedule-dialog-actions"><button class="primary-btn" type="submit">Save Changes</button></div>
        </form>
    </section>
    <section class="scheduled-areas-card schedule-editor">
        <h2>Delete Unused Schedule</h2><p>Permanent deletion is allowed only when there have been no requests, assignments, or collection records.</p>
        <form method="post" action="<?= $escape($scheduleUrl . '/' . $schedule['schedule_id'] . '/delete') ?>">
            <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>">
            <button class="secondary-btn" type="submit">Permanently Delete Schedule</button>
        </form>
    </section>
</section>
