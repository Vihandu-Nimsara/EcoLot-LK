<?php
$escape = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$open = array_values(array_filter($schedules, static fn ($s) => in_array($s['schedule_status'], ['ASSIGNED', 'IN_PROGRESS'], true)));
$remaining = array_sum(array_map(static fn ($s) => max(0, (int)$s['request_count'] - (int)$s['processed_count']), $open));
$saved = array_sum(array_column($schedules, 'processed_count'));
$pending = count(array_filter($schedules, static fn ($s) => $s['verification_status'] === 'PENDING'));
$next = $open[0] ?? null;
?>
<section class="collector-workspace">
<header class="collector-page-heading"><div><span class="collector-eyebrow">Collections</span><h1>Dashboard</h1><p>Manage each assignment in its own schedule workspace.</p></div><a class="collector-primary-button" href="<?= $escape($basePath . '/collector/schedules') ?>">Assigned Schedules</a></header>
<section class="collector-request-summary" aria-label="Collection summary">
<?php foreach (['Assigned / Upcoming Schedules' => count($open), 'Requests Remaining' => $remaining, 'Collection Records Saved' => $saved, 'Schedules Awaiting Verification' => $pending] as $label => $count): ?>
<article><span><?= $escape($label) ?></span><strong><?= $escape($count) ?></strong></article>
<?php endforeach; ?>
</section>
<section class="collector-table-card collector-detail-card"><h2>Next Assignment</h2>
<?php if ($next): ?><dl class="collector-details-grid"><div><dt>Date</dt><dd><?= $escape($next['collection_date']) ?></dd></div><div><dt>Area</dt><dd><?= $escape($next['area_name']) ?></dd></div><div><dt>Vehicle</dt><dd><?= $escape($next['vehicle_number'] ?? 'Not assigned') ?></dd></div><div><dt>Recorded requests</dt><dd><?= $escape($next['processed_count']) ?> / <?= $escape($next['request_count']) ?></dd></div></dl><a class="collector-primary-button" href="<?= $escape($basePath . '/collector/schedules/' . $next['schedule_id']) ?>">Open Schedule</a>
<?php else: ?><p class="collector-empty-state">No upcoming assignments. New schedules will appear here when assigned.</p><?php endif; ?>
</section></section>
