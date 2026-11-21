<?php
$escape = static fn ($value): string => htmlspecialchars($value === null || $value === '' ? 'Not recorded' : (string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$labels = ScheduleCollection::REVIEW_LABELS;
$reviewUrl = $basePath . '/officer/collection-records';
$link = static fn (array $changes = []): string => $reviewUrl . '?' . http_build_query(array_replace($reviewFilters, $changes));
$emptyMessages = ['PENDING' => 'No collections are awaiting review.', 'VERIFIED' => 'No verified collections found.', 'REJECTED' => 'No rejected collections found.', '' => 'No submitted collections found.'];
?>
<section class="collection-records-page">
    <div class="page-toolbar"><div><h1>Collection Reviews</h1><p>Review submitted e-waste collections, inspect pickup details, and verify or reject collection submissions.</p></div></div>
    <?php foreach ($errors as $error): ?><p class="officer-form-error" role="alert"><?= $escape($error) ?></p><?php endforeach; ?>
    <?php if ($notice): ?><p role="status" class="collection-review-notice"><?= $escape($notice) ?></p><?php endif; ?>
    <nav class="review-tabs" aria-label="Collection review status">
        <?php foreach ($labels + ['' => 'All Reviews'] as $value => $label): ?>
            <a class="secondary-btn" href="<?= $escape($link(['verification_status' => $value, 'page' => 1])) ?>" <?= $statusFilter === $value ? 'aria-current="page"' : '' ?>><?= $escape($label) ?> <span><?= $value === '' ? array_sum($reviewQueue['counts']) : $reviewQueue['counts'][$value] ?></span></a>
        <?php endforeach; ?>
    </nav>
    <p class="review-help">Tab counts include all submitted collections for schedules you created, before search filters. Collector drafts are excluded.</p>
    <section class="records-filter-card compact-filter-card">
        <form class="records-filter-form review-filter-form" method="get" action="<?= $escape($reviewUrl) ?>">
            <input type="hidden" name="verification_status" value="<?= htmlspecialchars($statusFilter, ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-group"><label for="review-search">Search area, Collector, SCH-ID or submission #ID</label><input type="search" id="review-search" name="q" maxlength="100" value="<?= htmlspecialchars($reviewFilters['q'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"></div>
            <div class="form-group"><label for="review-sort">Sort by</label><select id="review-sort" name="sort">
                <?php foreach (['newest'=>'Newest submission','oldest'=>'Oldest submission','collection_date'=>'Latest collection date','identifier'=>'Highest submission ID'] as $value=>$label): ?><option value="<?= $escape($value) ?>" <?= $reviewFilters['sort'] === $value ? 'selected' : '' ?>><?= $escape($label) ?></option><?php endforeach; ?>
            </select></div>
            <div class="filter-actions"><button type="submit" class="primary-btn">Apply Filters</button><a class="secondary-btn" href="<?= $escape($reviewUrl) ?>">Clear</a></div>
        </form>
    </section>
    <section class="records-list-card">
        <div class="records-list-heading"><div><h2>Collection Submissions</h2><span class="record-result-count officer-result-count"><?= count($collections) ?> shown · <?= (int) $reviewQueue['total'] ?> matching submissions</span></div></div>
        <?php if (!$collections): ?><p class="officer-empty-state"><?= $escape($reviewFilters['q'] === '' ? $emptyMessages[$statusFilter] : 'No submissions match your search. Try another area, Collector or identifier.') ?></p><?php else: ?>
        <div class="records-table-wrapper"><table class="records-table"><thead><tr><th>Submission / Schedule</th><th>Area / Collection Date</th><th>Collector</th><th>Submitted At</th><th>Requests</th><th>Review Status</th><th>Action</th></tr></thead><tbody>
        <?php foreach ($collections as $collection): ?>
            <tr data-review-row><td>#<?= (int) $collection['schedule_collection_id'] ?><br>SCH-<?= (int) $collection['schedule_id'] ?></td>
                <td><?= $escape($collection['area_name']) ?><br><?= $escape($collection['collection_date']) ?></td>
                <td><?= $escape($collection['collector_name']) ?></td><td><?= $escape($collection['submitted_at']) ?></td><td><?= (int) $collection['record_count'] ?></td>
                <td><span class="verification-status <?= $escape(strtolower($collection['verification_status'])) ?>" data-status="<?= $escape($collection['verification_status']) ?>"><?= $escape($labels[$collection['verification_status']]) ?></span>
                    <?php if ($collection['verification_status'] !== 'PENDING'): ?><small class="review-metadata"><?= $escape($collection['reviewer_name']) ?><br><?= $escape($collection['verified_at']) ?></small><?php endif; ?></td>
                <td><a class="record-action-btn secondary-btn" href="<?= $escape($link(['submission_id' => $collection['schedule_collection_id']])) ?>#collection-details"><?= $collection['verification_status'] === 'PENDING' ? 'Review Submission' : 'View Details' ?></a></td></tr>
        <?php endforeach; ?>
        </tbody></table></div><?php endif; ?>
        <nav class="review-pagination" aria-label="Submission pages">
            <?php if ($reviewQueue['page'] > 1): ?><a class="secondary-btn" href="<?= $escape($link(['page' => $reviewQueue['page'] - 1])) ?>">Previous</a><?php endif; ?>
            <span>Page <?= (int) $reviewQueue['page'] ?> of <?= (int) $reviewQueue['pages'] ?></span>
            <?php if ($reviewQueue['page'] < $reviewQueue['pages']): ?><a class="secondary-btn" href="<?= $escape($link(['page' => $reviewQueue['page'] + 1])) ?>">Next</a><?php endif; ?>
        </nav>
    </section>
    <?php if ($selectedCollection !== null): $selected = $selectedCollection; $overview = $selected['overview']; ?>
    <section class="records-list-card collection-review-details" id="collection-details" aria-labelledby="review-details-title">
        <div class="records-list-heading"><h2 id="review-details-title">Collection Review — SCH-<?= (int) $selected['schedule_id'] ?></h2><a class="secondary-btn" href="<?= $escape($link()) ?>">Back to submissions</a></div>
        <h3>Submission Summary</h3>
        <div class="record-summary-grid">
            <?php foreach (['schedule_collection_id' => 'Submission ID', 'schedule_id' => 'Schedule ID', 'area_name' => 'Area', 'collection_date' => 'Collection Date', 'collector_name' => 'Submitting Collector', 'assigned_collector_name' => 'Currently Assigned Collector', 'schedule_status' => 'Schedule Status', 'submitted_at' => 'Submitted At'] as $key => $label): ?>
                <div><span><?= $escape($label) ?></span><strong><?= $escape($selected[$key]) ?></strong></div>
            <?php endforeach; ?>
            <div><span>Review Status</span><strong><?= $escape($labels[$selected['verification_status']]) ?></strong></div>
        </div>
        <?php if ($selected['integrity_error']): ?><p class="officer-form-error" role="alert">Data integrity concern: <?= $escape($selected['integrity_error']) ?> Collection values have not been changed.</p><?php endif; ?>
        <?php if ($selected['verification_status'] === 'PENDING' && $selected['schedule_status'] !== 'COLLECTION_SUBMITTED'): ?><p class="officer-form-error" role="alert">The schedule and submission statuses are inconsistent. Review decisions are unavailable until this is investigated.</p><?php endif; ?>
        <h3 class="review-section-title">Collection Overview</h3>
        <div class="record-summary-grid" data-review-overview>
            <?php foreach (['requests'=>'Saved request records','COLLECTED'=>'Collected requests','PARTIAL'=>'Partially collected requests','NOT_COLLECTED'=>'Not-collected requests','actual_quantity'=>'Actual collected quantity','actual_weight_kg'=>'Recorded collected weight (kg)'] as $key=>$label): ?><div><span><?= $escape($label) ?></span><strong data-total="<?= $escape($key) ?>"><?= $escape($overview[$key]) ?></strong></div><?php endforeach; ?>
        </div>
        <?php if ($overview['missing_weights']): ?><p role="alert">Recorded weight is incomplete: <?= (int) $overview['missing_weights'] ?> collected item entries have no measured weight.</p><?php endif; ?>
        <?php if (!$selected['records']): ?><p class="officer-form-error">No collection records were saved. This submission cannot be verified.</p><?php endif; ?>
        <?php foreach ($selected['records'] as $record): ?>
            <section class="record-submissions-section" data-review-request="<?= (int) $record['request_id'] ?>">
                <h3>Request #<?= (int) $record['request_id'] ?> · <?= $escape($record['resident_name']) ?></h3>
                <p><?= $escape($record['pickup_address']) ?></p>
                <p>Pickup result: <strong><?= $escape($record['pickup_result']) ?></strong> · Attempted: <?= $escape($record['pickup_attempted_at']) ?></p>
                <p class="collection-note"><strong>Collector note:</strong> <?= $escape($record['collector_note']) ?></p>
                <?php if (!$record['items']): ?><p class="officer-form-error">No request items are available. This submission cannot be verified.</p><?php else: ?>
                <div class="record-submissions-table-wrapper"><table class="record-submissions-table"><caption>Collected items for Request #<?= (int) $record['request_id'] ?></caption><thead><tr><th>Item / Category</th><th>Requested Qty</th><th>Actual Qty</th><th>Actual Weight (kg)</th><th>Actual Condition</th><th>Item Result</th><th>Actual Risk</th><th>Notes</th></tr></thead><tbody>
                <?php foreach ($record['items'] as $item): ?>
                    <tr><td>#<?= (int) $item['request_item_id'] ?> <?= $escape($item['item_name']) ?><br><?= $escape($item['category_name']) ?>
                        <?php if ($item['record_item_id'] === null): ?><br><strong>Missing actual collection data</strong><?php endif; ?>
                        <?php if ($item['item_request_id'] != $record['request_id']): ?><br><strong>Item belongs to another request; review required</strong><?php endif; ?></td>
                    <?php foreach (['requested_quantity', 'actual_quantity', 'actual_weight_kg', 'actual_condition', 'item_result', 'actual_risk_level', 'notes'] as $field): ?><td><?= $escape($item[$field]) ?></td><?php endforeach; ?></tr>
                <?php endforeach; ?></tbody></table></div><?php endif; ?>
            </section>
        <?php endforeach; ?>
        <h3 class="review-section-title">Review Decision</h3>
        <?php if ($selected['verification_status'] !== 'PENDING'): ?>
            <div class="record-summary-grid"><?php foreach (['reviewer_name'=>'Reviewing Officer','verified_at'=>'Reviewed At'] as $key=>$label): ?><div><span><?= $escape($label) ?></span><strong><?= $escape($selected[$key]) ?></strong></div><?php endforeach; ?></div>
            <p class="collection-note"><strong><?= $selected['verification_status'] === 'REJECTED' ? 'Rejection reason' : 'Officer note' ?>:</strong> <?= $escape($selected['verification_note']) ?></p>
            <p>This decision is final. Submitted collection records are read-only.</p>
        <?php else: ?>
            <p>Verify only after reviewing all requests and items. Rejection requires a reason and preserves the submitted records.</p>
            <noscript><p>Enable JavaScript to open a review confirmation dialog.</p></noscript>
            <div class="review-decision-actions">
                <?php foreach (['REJECTED'=>'Reject Collection','VERIFIED'=>'Verify Collection'] as $decision=>$label): ?>
                    <button type="button" class="<?= $decision === 'VERIFIED' ? 'primary-btn' : 'secondary-btn' ?>" data-review-open="<?= $escape($decision) ?>" data-eligible="<?= ($decision === 'VERIFIED' ? $selected['can_verify'] : $selected['can_reject']) ? 'true' : 'false' ?>" disabled><?= $label ?></button>
                <?php endforeach; ?>
            </div>
            <?php foreach (['VERIFIED'=>'Verify Collection','REJECTED'=>'Reject Collection'] as $decision=>$label): ?>
            <dialog class="collection-review-dialog" id="review-<?= $escape(strtolower($decision)) ?>" aria-labelledby="title-<?= $escape($decision) ?>" aria-describedby="description-<?= $escape($decision) ?>">
                <div class="officer-dialog-card">
                    <div class="officer-dialog-header"><div><span class="dialog-eyebrow">SCH-<?= (int) $selected['schedule_id'] ?> · Submission #<?= (int) $selected['schedule_collection_id'] ?></span><h2 id="title-<?= $escape($decision) ?>"><?= $label ?>?</h2><p id="description-<?= $escape($decision) ?>"><?= $decision === 'VERIFIED' ? 'This decision will be recorded under your Officer account. Verify only after checking every request and item.' : 'Explain why this collection is rejected. The reason will be visible to the Collector. This decision is final.' ?></p></div></div>
                    <form method="post" action="<?= $escape($reviewUrl . '/' . $selected['schedule_collection_id'] . '/review?' . http_build_query($reviewFilters)) ?>" data-review-decision>
                        <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>"><input type="hidden" name="decision" value="<?= $escape($decision) ?>">
                        <div class="form-group"><label for="note-<?= $escape($decision) ?>"><?= $decision === 'REJECTED' ? 'Rejection reason (required)' : 'Officer note (optional)' ?> · maximum 500 characters</label><textarea id="note-<?= $escape($decision) ?>" name="note" rows="4" maxlength="500" <?= $decision === 'REJECTED' ? 'required' : '' ?>><?= htmlspecialchars($reviewNote, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea></div>
                        <div class="officer-dialog-actions"><button type="button" class="secondary-btn" data-review-cancel autofocus>Cancel</button><button type="submit" class="<?= $decision === 'VERIFIED' ? 'primary-btn' : 'review-danger-btn' ?>"><?= $label ?></button></div>
                    </form>
                </div>
            </dialog>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</section>
