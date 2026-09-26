<?php
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$scheduleId = $schedule['schedule_id'];
$first = $schedule;
$rows = $requests;
?>
<section class="collector-workspace">
    <header class="collector-page-heading"><div><span class="collector-eyebrow">Collection workspace</span><h1>Schedule Workspace</h1>
        <p>Save individual drafts, then submit the whole schedule for Municipal Officer verification.</p></div><a class="collector-secondary-button" href="<?= $escape($basePath . '/collector/schedules') ?>">Back to Assigned Schedules</a></header>
    <?php if ($error): ?><p class="collector-feedback collector-feedback--error" role="alert"><?= $escape($error) ?></p><?php endif; ?>
    <?php if ($notice): ?><p class="collector-feedback collector-feedback--success" role="status"><?= $escape($notice) ?></p><?php endif; ?>
    <?php if (!$requests): ?><div class="collector-empty-state">No requests are currently assigned to you.</div><?php endif; ?>
    <section class="collector-table-card">
        <div class="collector-section-header"><div><h2>Schedule #<?= $escape($scheduleId) ?> · <?= $escape($first['area_name']) ?></h2>
            <p><?= $escape($first['collection_date']) ?> · <?= $escape(CollectionRecord::label($first['schedule_status'])) ?></p><p>Vehicle: <?= $escape(trim(($schedule['vehicle_number'] ?? 'Not assigned') . ' ' . ($schedule['vehicle_type'] ?? ''))) ?></p><p>Collection progress: <?= $escape($schedule['processed_count']) ?> / <?= $escape($schedule['request_count']) ?> active requests recorded</p></div>

        </div>
    </section>
    <section class="collector-table-card collector-detail-card"><h2>Assigned Requests</h2><div class="collector-table-wrap"><table class="collector-table"><thead><tr><th scope="col">Request ID</th><th scope="col">Public User / Pickup Address</th><th scope="col">Items</th><th scope="col">Record Status</th><th scope="col" class="collector-action-column">Action</th></tr></thead><tbody>
        <?php foreach ($rows as $request): $recordId = $request['collection_record_id']; $url = $basePath . ($recordId ? '/collector/collection-records/' . $recordId : '/collector/requests/' . $request['request_id']); ?>
        <tr><td>#<?= $escape($request['request_id']) ?></td><td><?= $escape($request['full_name']) ?><br><?= $escape($request['pickup_address']) ?></td>
            <td><?= $escape($request['item_count']) ?></td><td><span class="collector-status collector-status--<?= $escape(strtolower(str_replace(['_', ' '], '-', CollectionRecord::status($request) ?? 'not-recorded'))) ?>"><?= $escape(CollectionRecord::label(CollectionRecord::status($request))) ?></span></td><td><div class="collector-row-actions">
                <?php if (!$recordId && !CollectionRecord::editable($request) && in_array($request['risk_review_status'], ['PENDING', 'REJECTED'], true)): ?><span><?= $request['risk_review_status'] === 'PENDING' ? 'Awaiting Officer Review' : 'Officer Review Rejected' ?></span><?php else: ?>
                <a class="collector-secondary-button" href="<?= $escape($url) ?>"><?= $recordId ? 'View' : (CollectionRecord::editable($request) ? 'Record Collection' : 'View Request') ?></a><?php endif; ?>
                <?php if ($recordId && CollectionRecord::editable($request)): ?>
                <a class="collector-secondary-button" href="<?= $escape($url . '#collection-form') ?>">Edit Draft</a>
                <form class="collector-inline-form" method="post" action="<?= $escape($url . '/delete') ?>" data-confirm="Permanently delete this draft collection record?">
                    <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>"><button type="submit" class="collector-danger-button">Delete Draft</button>
                </form><?php endif; ?>
            </div></td></tr>
        <?php endforeach; ?></tbody></table></div>
        <p class="collector-record-readonly-note">Every active request needs a saved draft before submission. Requests awaiting risk approval cannot be collected.</p>
    </section>
    <section class="collector-table-card collector-detail-card"><div class="collector-section-header"><div><h2>Submit schedule</h2><p>Every active request must have a valid outcome and Officer clearance.</p></div>
            <?php if (in_array($first['schedule_status'], ['ASSIGNED', 'IN_PROGRESS'], true) && in_array($first['verification_status'], [null, 'DRAFT'], true)): ?>
            <form method="post" action="<?= $escape($basePath . '/collector/schedules/' . $scheduleId . '/submit') ?>" data-confirm="Submit every saved record in this schedule for verification? All records will become read-only.">
                <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>">
                <button class="collector-primary-button" type="submit" <?= !$canSubmit ? 'disabled' : '' ?>>Submit Schedule for Verification</button>
            </form>
            <?php else: ?><span class="collector-status collector-status--<?= $escape(strtolower(str_replace('_', '-', $first['verification_status'] === 'PENDING' ? 'SUBMITTED' : ($first['verification_status'] ?? 'not-recorded')))) ?>"><?= $escape(CollectionRecord::label($first['verification_status'])) ?></span><?php endif; ?>
        </div>
    </section>
</section>
