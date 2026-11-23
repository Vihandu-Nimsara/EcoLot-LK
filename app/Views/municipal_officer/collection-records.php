<?php
$escape = static fn ($value): string => htmlspecialchars($value === null || $value === '' ? 'Not recorded' : (string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$labels = ScheduleCollection::REVIEW_LABELS;
$reviewUrl = $basePath . '/officer/collection-records';
$link = static fn (array $changes = []): string => $reviewUrl . '?' . http_build_query(array_replace($reviewFilters, $changes));
$emptyMessages = ['PENDING' => 'No collections are awaiting review.', 'VERIFIED' => 'No verified collections found.', 'REJECTED' => 'No rejected collections found.', '' => 'No submitted collections found.'];
?>
<section class="collection-records-page">
    <?php foreach ($errors as $error): ?><p class="officer-form-error" role="alert"><?= $escape($error) ?></p><?php endforeach; ?>
    <?php if ($notice): ?><p role="status" class="collection-review-notice"><?= $escape($notice) ?></p><?php endif; ?>
    <?php if ($selectedCollection === null): ?>
    <div class="page-toolbar"><div><h1>Collection Reviews</h1></div></div>
    <section class="records-filter-card compact-filter-card">
        <form class="records-filter-form" method="get" action="<?= $escape($reviewUrl) ?>">
            <div class="records-filter-grid">
                <div class="form-group"><label for="review-search">Search</label><input type="search" id="review-search" name="q" placeholder="Area, Collector, SCH-10, #5" maxlength="100" value="<?= htmlspecialchars($reviewFilters['q'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"></div>
                <div class="form-group"><label for="review-status">Status</label><select id="review-status" name="verification_status">
                    <?php foreach (['' => 'All statuses'] + $labels as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $statusFilter === $value ? 'selected' : '' ?>><?= $escape($label) ?></option>
                    <?php endforeach; ?>
                </select></div>
            </div>
            <div class="filter-actions"><button type="submit" class="primary-btn">Apply Filters</button><a class="secondary-btn" href="<?= $escape($reviewUrl) ?>">Clear</a></div>
        </form>
    </section>
    <section class="records-list-card">
        <div class="records-list-heading"><div><h2>Collection Submissions</h2><span class="record-result-count officer-result-count"><?= count($collections) ?> shown · <?= (int) $reviewQueue['total'] ?> matching submissions</span></div></div>
        <?php if (!$collections): ?><p class="officer-empty-state"><?= $escape($reviewFilters['q'] === '' ? $emptyMessages[$statusFilter] : 'No submissions match your search. Try another area, Collector or identifier.') ?></p><?php else: ?>
        <div class="records-table-wrapper officer-scan-wrapper"><table class="records-table officer-scan-table" role="table">
            <thead role="rowgroup"><tr role="row"><th role="columnheader" scope="col">Schedule</th><th role="columnheader" scope="col">Area</th><th role="columnheader" scope="col">Collector</th><th role="columnheader" scope="col">Requests</th><th role="columnheader" scope="col">Review Status</th><th role="columnheader" scope="col">Action</th></tr></thead>
            <tbody role="rowgroup">
            <?php foreach ($collections as $collection): ?>
                <tr role="row" data-review-row>
                    <td role="cell" class="scan-primary"><strong>SCH-<?= (int) $collection['schedule_id'] ?></strong><small class="review-table-secondary">Submission #<?= (int) $collection['schedule_collection_id'] ?></small></td>
                    <td role="cell"><strong><?= $escape($collection['area_name']) ?></strong><small class="review-table-secondary">Pickup <?= $escape($collection['collection_date']) ?></small></td>
                    <td role="cell"><?= $escape($collection['collector_name']) ?><small class="review-table-secondary">Submitted <?= $escape($collection['submitted_at']) ?></small></td>
                    <td role="cell"><?= (int) $collection['record_count'] ?> <span class="scan-mobile-label"><?= (int) $collection['record_count'] === 1 ? 'request' : 'requests' ?></span></td>
                    <td role="cell" class="scan-status">
                        <span class="verification-status <?= $escape(strtolower($collection['verification_status'])) ?>" data-status="<?= $escape($collection['verification_status']) ?>"><?= $escape($labels[$collection['verification_status']]) ?></span>
                    </td>
                    <td role="cell" class="scan-action"><a class="record-action-btn secondary-btn" href="<?= $escape($link(['submission_id' => $collection['schedule_collection_id']])) ?>#collection-details"><?= $collection['verification_status'] === 'PENDING' ? 'Review Submission' : 'View Details' ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div><?php endif; ?>
        <?php if ($reviewQueue['pages'] > 1): ?>
            <nav class="review-pagination" aria-label="Submission pages">
                <?php if ($reviewQueue['page'] > 1): ?><a class="secondary-btn" href="<?= $escape($link(['page' => $reviewQueue['page'] - 1])) ?>">Previous</a><?php endif; ?>
                <span>Page <?= (int) $reviewQueue['page'] ?> of <?= (int) $reviewQueue['pages'] ?></span>
                <?php if ($reviewQueue['page'] < $reviewQueue['pages']): ?><a class="secondary-btn" href="<?= $escape($link(['page' => $reviewQueue['page'] + 1])) ?>">Next</a><?php endif; ?>
            </nav>
        <?php endif; ?>
    </section>
    <?php else: $selected = $selectedCollection; $overview = $selected['overview']; ?>
    <section class="records-list-card collection-review-details" id="collection-details" aria-labelledby="review-details-title">
        <div class="records-list-heading review-details-heading">
            <div>
                <h1 id="review-details-title">Collection Review · SCH-<?= (int) $selected['schedule_id'] ?></h1>
                <p class="review-metadata">Submission #<?= (int) $selected['schedule_collection_id'] ?> · <?= $escape($selected['area_name']) ?> · <?= $escape($selected['collection_date']) ?></p>
            </div>
            <a class="primary-btn review-back-link" href="<?= $escape($link()) ?>"><span aria-hidden="true">←</span> Back to submissions</a>
        </div>
        <p><span class="verification-status <?= $escape(strtolower($selected['verification_status'])) ?>"><?= $escape($labels[$selected['verification_status']]) ?></span> <?php if ($selected['verification_status'] === 'VERIFIED'): ?><span class="review-metadata-inline">Schedule completed</span><?php endif; ?></p>
        <p class="review-metadata">Submitted by <?= $escape($selected['collector_name']) ?> · <?= $escape($selected['submitted_at']) ?></p>
        <?php if ($selected['assigned_collector_name'] !== $selected['collector_name']): ?>
            <p class="review-metadata">Assigned Collector · <?= $escape($selected['assigned_collector_name']) ?></p>
        <?php endif; ?>
        <?php if ($selected['verification_status'] !== 'PENDING'): ?>
            <p class="review-metadata">Reviewed by <?= $escape($selected['reviewer_name']) ?> · <?= $escape($selected['verified_at']) ?></p>
            <?php if ($selected['verification_note'] !== null && $selected['verification_note'] !== ''): ?>
                <p class="collection-note"><strong><?= $selected['verification_status'] === 'REJECTED' ? 'Rejection reason' : 'Officer note' ?>:</strong> <?= $escape($selected['verification_note']) ?></p>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($selected['integrity_error']): ?><p class="officer-form-error" role="alert">Data integrity concern: <?= $escape($selected['integrity_error']) ?> Collection values have not been changed.</p><?php endif; ?>
        <?php if ($selected['verification_status'] === 'PENDING' && $selected['schedule_status'] !== 'COLLECTION_SUBMITTED'): ?><p class="officer-form-error" role="alert">The schedule and submission statuses are inconsistent. Review decisions are unavailable until this is investigated.</p><?php endif; ?>
        <h2 class="review-section-title">Collection Overview</h2>
        <div class="record-summary-grid" data-review-overview>
            <?php foreach (['requests'=>'Total requests','COLLECTED'=>'Collected','PARTIAL'=>'Partially collected','NOT_COLLECTED'=>'Not collected','actual_quantity'=>'Units collected','actual_weight_kg'=>'Weight collected (kg)'] as $key=>$label): ?><div><span><?= $escape($label) ?></span><strong data-total="<?= $escape($key) ?>"><?= $escape($overview[$key]) ?></strong></div><?php endforeach; ?>
        </div>
        <?php if ($overview['missing_weights']): ?><p role="alert">Recorded weight is incomplete: <?= (int) $overview['missing_weights'] ?> collected item entries have no measured weight.</p><?php endif; ?>
        <?php if (!$selected['records']): ?><p class="officer-form-error">No collection records were saved. This submission cannot be verified.</p><?php endif; ?>
        <h2 class="review-section-title">Pickup Requests</h2>
        <?php foreach ($selected['records'] as $record): ?>
            <details class="record-submissions-section" data-review-request="<?= (int) $record['request_id'] ?>">
                <summary>Request #<?= (int) $record['request_id'] ?> · <?= $escape($record['resident_name']) ?> · <?= $escape(ucfirst(strtolower(str_replace('_', ' ', $record['pickup_result'])))) ?></summary>
                <div class="review-request-content">
                <p><?= $escape($record['pickup_address']) ?></p>
                <p>Attempted: <?= $escape($record['pickup_attempted_at']) ?></p>
                <p class="collection-note"><strong>Collector note:</strong> <?= $escape($record['collector_note']) ?></p>
                <?php if (!$record['items']): ?><p class="officer-form-error">No request items are available. This submission cannot be verified.</p><?php else: ?>
                <div class="record-submissions-table-wrapper">
                    <table class="record-submissions-table" aria-label="Items for Request #<?= (int) $record['request_id'] ?>">
                        <thead>
                            <tr><th scope="col">Item</th><th scope="col">Qty collected</th><th scope="col">Weight (kg)</th><th scope="col">Outcome</th><th scope="col">Condition &amp; risk</th><th scope="col">Notes</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($record['items'] as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?= $escape($item['item_name']) ?></strong>
                                        <small class="review-table-secondary"><?= $escape($item['category_name']) ?> · #<?= (int) $item['request_item_id'] ?></small>
                                        <?php if ($item['record_item_id'] === null): ?><strong class="review-table-warning">Missing actual collection data</strong><?php endif; ?>
                                        <?php if ($item['item_request_id'] != $record['request_id']): ?><strong class="review-table-warning">Item belongs to another request</strong><?php endif; ?>
                                    </td>
                                    <td><strong><?= $escape($item['actual_quantity']) ?></strong><small class="review-table-secondary">of <?= $escape($item['requested_quantity']) ?> requested</small></td>
                                    <td><?= $escape($item['actual_weight_kg']) ?></td>
                                    <td><?= $escape($item['item_result'] === null ? null : ucfirst(strtolower(str_replace('_', ' ', $item['item_result'])))) ?></td>
                                    <td>
                                        <?= $escape($item['actual_condition'] === null ? null : ucfirst(strtolower($item['actual_condition']))) ?>
                                        <?php if ($item['actual_risk_level'] !== null): ?><small class="review-table-secondary"><?= $escape(ucfirst(strtolower($item['actual_risk_level']))) ?> risk</small><?php endif; ?>
                                    </td>
                                    <td><?= $item['notes'] === null || $item['notes'] === '' ? '—' : $escape($item['notes']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                </div>
            </details>
        <?php endforeach; ?>
        <?php if ($selected['verification_status'] === 'PENDING'): ?>
            <h2 class="review-section-title">Review Decision</h2>
            <p>Verify only after reviewing all requests and items. Reject only when the submission should be closed. The Collector will see your reason.</p>
            <noscript><p>Enable JavaScript to open a review confirmation dialog.</p></noscript>
            <div class="review-decision-actions">
                <?php foreach (['REJECTED'=>'Reject Collection','VERIFIED'=>'Verify Collection'] as $decision=>$label): ?>
                    <button type="button" class="<?= $decision === 'VERIFIED' ? 'primary-btn' : 'secondary-btn' ?>" data-review-open="<?= $escape($decision) ?>" data-eligible="<?= ($decision === 'VERIFIED' ? $selected['can_verify'] : $selected['can_reject']) ? 'true' : 'false' ?>" disabled><?= $label ?></button>
                <?php endforeach; ?>
            </div>
            <?php foreach (['VERIFIED'=>'Verify Collection','REJECTED'=>'Reject Collection'] as $decision=>$label): ?>
            <dialog class="collection-review-dialog" id="review-<?= $escape(strtolower($decision)) ?>" aria-labelledby="title-<?= $escape($decision) ?>" aria-describedby="description-<?= $escape($decision) ?>">
                <div class="officer-dialog-card">
                    <div class="officer-dialog-header"><div><span class="dialog-eyebrow">SCH-<?= (int) $selected['schedule_id'] ?> · Submission #<?= (int) $selected['schedule_collection_id'] ?></span><h2 id="title-<?= $escape($decision) ?>"><?= $label ?>?</h2><p id="description-<?= $escape($decision) ?>"><?= $decision === 'VERIFIED' ? 'This decision will be recorded under your Officer account. Verify only after checking every request and item.' : 'Explain why this collection is rejected. The reason will be visible to the Collector. This is final. The Collector cannot revise or resubmit this collection.' ?></p></div></div>
                    <form method="post" action="<?= $escape($reviewUrl . '/' . $selected['schedule_collection_id'] . '/review?' . http_build_query($reviewFilters)) ?>" data-review-decision>
                        <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>"><input type="hidden" name="decision" value="<?= $escape($decision) ?>">
                        <div class="form-group"><label for="note-<?= $escape($decision) ?>"><?= $decision === 'REJECTED' ? 'Rejection reason (required)' : 'Officer note (optional)' ?> · maximum 500 characters</label><textarea id="note-<?= $escape($decision) ?>" name="note" rows="4" maxlength="500" <?= $decision === 'REJECTED' ? 'required' : '' ?>><?= htmlspecialchars($reviewNote, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea></div>
                        <?php if ($decision === 'VERIFIED'): ?>
                            <label class="review-acknowledgement">
                                <input type="checkbox" name="review_acknowledged" value="1" required>
                                <span>I reviewed all <?= (int) $overview['requests'] ?> pickup <?= (int) $overview['requests'] === 1 ? 'request' : 'requests' ?> and their items.</span>
                            </label>
                        <?php endif; ?>
                        <div class="officer-dialog-actions"><button type="button" class="secondary-btn" data-review-cancel autofocus>Cancel</button><button type="submit" class="<?= $decision === 'VERIFIED' ? 'primary-btn' : 'review-danger-btn' ?>"><?= $label ?></button></div>
                    </form>
                </div>
            </dialog>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</section>
