<?php
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$schedules = [];
foreach ($requests as $request) $schedules[$request['schedule_id']][] = $request;
?>
<section class="collector-workspace">
    <header class="collector-page-heading"><div><span class="collector-eyebrow">Collection workspace</span><h1>Assigned Requests</h1>
        <p>Save individual drafts, then submit the whole schedule for Municipal Officer verification.</p></div></header>
    <?php if ($error): ?><p class="collector-feedback" role="alert"><?= $escape($error) ?></p><?php endif; ?>
    <?php if ($notice): ?><p class="collector-feedback" role="status"><?= $escape($notice) ?></p><?php endif; ?>
    <?php if (!$requests): ?><div class="collector-empty-state">No requests are currently assigned to you.</div><?php endif; ?>
    <?php foreach ($schedules as $scheduleId => $rows): $first = $rows[0]; ?>
    <section class="collector-table-card">
        <div class="collector-section-header"><div><h2>Schedule #<?= $escape($scheduleId) ?> · <?= $escape($first['area_name']) ?></h2>
            <p><?= $escape($first['collection_date']) ?> · <?= $escape($first['schedule_status']) ?></p></div>
            <?php if (in_array($first['schedule_status'], ['ASSIGNED', 'IN_PROGRESS'], true) && in_array($first['verification_status'], [null, 'DRAFT'], true)): ?>
            <form method="post" action="<?= $escape($basePath . '/collector/schedules/' . $scheduleId . '/submit') ?>" data-confirm="Submit every saved record in this schedule for verification? All records will become read-only.">
                <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>">
                <button class="collector-primary-button" type="submit">Submit Schedule for Verification</button>
            </form>
            <?php else: ?><span><?= $escape($first['verification_status'] === 'PENDING' ? 'SUBMITTED' : $first['verification_status']) ?></span><?php endif; ?>
        </div>
        <div class="collector-table-wrap"><table class="collector-table"><thead><tr><th>Request ID</th><th>Public User / Pickup Address</th><th>Items</th><th>Record Status</th><th>Action</th></tr></thead><tbody>
        <?php foreach ($rows as $request): $recordId = $request['collection_record_id']; $url = $basePath . ($recordId ? '/collector/collection-records/' . $recordId : '/collector/my-requests/' . $request['request_id']); ?>
        <tr><td>#<?= $escape($request['request_id']) ?></td><td><?= $escape($request['full_name']) ?><br><?= $escape($request['pickup_address']) ?></td>
            <td><?= $escape($request['item_count']) ?></td><td><?= $escape(CollectionRecord::status($request)) ?></td><td>
                <a class="collector-secondary-button" href="<?= $escape($url) ?>"><?= $recordId ? 'View' : (CollectionRecord::editable($request) ? 'Record Collection' : 'View Request') ?></a>
                <?php if ($recordId && CollectionRecord::editable($request)): ?>
                <a class="collector-secondary-button" href="<?= $escape($url . '#collection-form') ?>">Edit</a>
                <form class="collector-inline-form" method="post" action="<?= $escape($url . '/delete') ?>" data-confirm="Permanently delete this draft collection record?">
                    <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>"><button type="submit" class="collector-secondary-button">Delete</button>
                </form><?php endif; ?>
            </td></tr>
        <?php endforeach; ?></tbody></table></div>
        <p class="collector-record-readonly-note">Every active request needs a saved draft before submission. Requests awaiting risk approval cannot be collected.</p>
    </section>
    <?php endforeach; ?>
</section>
