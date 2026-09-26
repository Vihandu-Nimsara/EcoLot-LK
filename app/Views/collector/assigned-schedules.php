<?php $escape = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
<section class="collector-workspace">
<header class="collector-page-heading"><div><span class="collector-eyebrow">Collections</span><h1>Assigned Schedules</h1><p>Choose an assignment to record its collection outcomes.</p></div><a class="collector-secondary-button" href="<?= $escape($basePath . '/collector/dashboard') ?>">Back to Dashboard</a></header>
<?php if ($error): ?><p class="collector-feedback collector-feedback--error" role="alert"><?= $escape($error) ?></p><?php endif; ?>
<?php if ($notice): ?><p class="collector-feedback collector-feedback--success" role="status"><?= $escape($notice) ?></p><?php endif; ?>
<?php if (!$schedules): ?><div class="collector-empty-state">No schedules are currently assigned to you.</div><?php endif; ?>
<div class="collector-final-schedules">
<?php foreach ($schedules as $schedule): $open = in_array($schedule['schedule_status'], ['ASSIGNED', 'IN_PROGRESS'], true); ?>
<section class="collector-table-card collector-detail-card">
<div class="collector-section-header"><div><span>Schedule #<?= $escape($schedule['schedule_id']) ?></span><h2><?= $escape($schedule['area_name']) ?></h2></div><span class="collector-status collector-status--<?= $escape(strtolower(str_replace('_', '-', $schedule['schedule_status']))) ?>"><?= $escape(CollectionRecord::label($schedule['schedule_status'])) ?></span></div>
<dl class="collector-details-grid"><div><dt>Collection date</dt><dd><?= $escape($schedule['collection_date']) ?></dd></div><div><dt>Vehicle</dt><dd><?= $escape($schedule['vehicle_number'] ?? 'Not assigned') ?> <?= $escape($schedule['vehicle_type'] ?? '') ?></dd></div><div><dt>Total active requests</dt><dd><?= $escape($schedule['request_count']) ?></dd></div><div><dt>Recorded / Remaining</dt><dd><?= $escape($schedule['processed_count']) ?> / <?= $escape(max(0, $schedule['request_count'] - $schedule['processed_count'])) ?></dd></div></dl>
<label>Collection progress <progress max="<?= max(1, (int)$schedule['request_count']) ?>" value="<?= (int)$schedule['processed_count'] ?>"></progress></label>
<a class="collector-primary-button" href="<?= $escape($basePath . '/collector/schedules/' . $schedule['schedule_id']) ?>"><?= !$open ? 'View Schedule' : ($schedule['processed_count'] ? 'Continue Schedule' : 'Open Schedule') ?></a>
</section><?php endforeach; ?></div>
<p class="collector-record-readonly-note">Cancelled and rejected requests do not require a collection record.</p>
</section>
