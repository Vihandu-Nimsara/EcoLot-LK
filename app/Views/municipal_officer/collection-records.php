<?php
$escape = static fn ($value): string => htmlspecialchars($value === null ? 'Not recorded' : (string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<section class="collection-records-page">
    <div class="page-toolbar"><div><h1>Collection Records</h1><p>Review submissions for schedules you created. Collection decisions are saved to the database.</p></div></div>
    <?php foreach ($errors as $error): ?><p class="officer-form-error" role="alert"><?= $escape($error) ?></p><?php endforeach; ?>
    <?php if ($notice): ?><p role="status" class="collection-review-notice"><?= $escape($notice) ?></p><?php endif; ?>
    <section class="records-filter-card compact-filter-card">
        <form class="records-filter-form" method="get" action="<?= $escape($basePath . '/officer/collection-records') ?>">
            <div class="form-group"><label for="verification-status">Verification Status</label>
                <select id="verification-status" name="verification_status">
                    <?php foreach (['' => 'All submitted collections', 'PENDING' => 'Pending verification', 'VERIFIED' => 'Verified', 'REJECTED' => 'Rejected'] as $value => $label): ?>
                        <option value="<?= $escape($value) ?>" <?= $statusFilter === $value ? 'selected' : '' ?>><?= $escape($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-actions"><button type="submit" class="primary-btn">Apply Filters</button><a class="secondary-btn" href="<?= $escape($basePath . '/officer/collection-records') ?>">Clear</a></div>
        </form>
    </section>
    <section class="records-list-card">
        <div class="records-list-heading"><div><h2>Schedule Collection Summary</h2><span class="record-result-count officer-result-count"><?= count($collections) ?> records shown</span></div></div>
        <?php if (!$collections): ?><p class="officer-empty-state">No submitted collections match this status.</p><?php else: ?>
        <div class="records-table-wrapper"><table class="records-table"><thead><tr><th>Submission / Schedule</th><th>Area / Collection Date</th><th>Collector</th><th>Submitted</th><th>Requests</th><th>Status</th><th>Action</th></tr></thead><tbody>
        <?php foreach ($collections as $collection): ?>
            <tr><td>#<?= (int) $collection['schedule_collection_id'] ?><br>Schedule #<?= (int) $collection['schedule_id'] ?></td>
                <td><?= $escape($collection['area_name']) ?><br><?= $escape($collection['collection_date']) ?></td>
                <td><?= $escape($collection['collector_name']) ?></td><td><?= $escape($collection['submitted_at']) ?></td><td><?= (int) $collection['record_count'] ?></td>
                <td><span class="verification-status <?= $escape(strtolower($collection['verification_status'])) ?>"><?= $escape($collection['verification_status']) ?></span></td>
                <td><a class="record-action-btn secondary-btn" href="<?= $escape($basePath . '/officer/collection-records?' . http_build_query(['submission_id' => $collection['schedule_collection_id'], 'verification_status' => $statusFilter])) ?>">View Details</a></td></tr>
        <?php endforeach; ?>
        </tbody></table></div><?php endif; ?>
    </section>
    <?php if ($selectedCollection !== null): $selected = $selectedCollection; ?>
    <section class="records-list-card collection-review-details" id="collection-details">
        <h2>Collection Details · Submission #<?= (int) $selected['schedule_collection_id'] ?></h2>
        <div class="record-summary-grid">
            <?php foreach (['schedule_id' => 'Schedule', 'area_name' => 'Area', 'collection_date' => 'Collection Date', 'collector_name' => 'Collector', 'verification_status' => 'Status', 'submitted_at' => 'Submitted', 'reviewer_name' => 'Reviewing Officer', 'verified_at' => 'Reviewed at'] as $key => $label): ?>
                <div><span><?= $escape($label) ?></span><strong><?= $escape($selected[$key]) ?></strong></div>
            <?php endforeach; ?>
        </div>
        <p class="collection-note"><strong>Officer note:</strong> <?= $escape($selected['verification_note']) ?></p>
        <?php if (!$selected['records']): ?><p>No collection records were saved for this submission.</p><?php endif; ?>
        <?php foreach ($selected['records'] as $record): ?>
            <section class="record-submissions-section">
                <h3>Request #<?= (int) $record['request_id'] ?> · <?= $escape($record['resident_name']) ?></h3>
                <p><?= $escape($record['pickup_address']) ?></p>
                <p>Pickup: <strong><?= $escape($record['pickup_result']) ?></strong> · Attempted: <?= $escape($record['pickup_attempted_at']) ?></p>
                <p class="collection-note">Collector note: <?= $escape($record['collector_note']) ?></p>
                <?php if (!$record['items']): ?><p>No request items are available. This submission cannot be verified.</p><?php else: ?>
                <div class="record-submissions-table-wrapper"><table class="record-submissions-table"><thead><tr><th>Item / Category</th><th>Requested Qty</th><th>Actual Qty</th><th>Weight (kg)</th><th>Condition</th><th>Result</th><th>Risk</th><th>Notes</th></tr></thead><tbody>
                <?php foreach ($record['items'] as $item): ?>
                    <tr><td><?= $escape($item['item_name']) ?><br><?= $escape($item['category_name']) ?></td>
                    <?php foreach (['requested_quantity', 'actual_quantity', 'actual_weight_kg', 'actual_condition', 'item_result', 'actual_risk_level', 'notes'] as $field): ?><td><?= $escape($item[$field]) ?></td><?php endforeach; ?></tr>
                <?php endforeach; ?></tbody></table></div><?php endif; ?>
            </section>
        <?php endforeach; ?>
        <?php if ($selected['verification_status'] === 'PENDING'): ?>
        <form class="record-review-form" method="post" action="<?= $escape($basePath . '/officer/collection-records/' . $selected['schedule_collection_id'] . '/review') ?>" data-collection-review>
            <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>">
            <div class="form-group"><label for="verification-note">Officer note (required to reject; maximum 500 characters)</label>
                <textarea id="verification-note" name="note" maxlength="500" rows="3"></textarea></div>
            <div class="record-dialog-actions"><button class="primary-btn" type="submit" name="decision" value="VERIFIED">Verify</button><button class="secondary-btn" type="submit" name="decision" value="REJECTED">Reject</button></div>
        </form>
        <?php else: ?><p>This decision is final. Submitted collection records are read-only.</p><?php endif; ?>
    </section>
    <?php endif; ?>
</section>
