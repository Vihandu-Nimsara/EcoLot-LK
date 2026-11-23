<?php
$escape = static fn ($value): string => htmlspecialchars($value === null ? 'Not recorded' : (string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$url = $basePath . '/officer/e-lots';
$link = static fn (array $changes = []): string => $url . '?' . http_build_query(array_replace($filters, $changes));
$formValue = static fn (string $key, string $fallback = ''): string =>
    isset($formInput[$key]) && is_string($formInput[$key]) ? $formInput[$key] : $fallback;
?>
<section class="elots-page">
<?php foreach ($errors as $error): ?><p class="officer-form-error" role="alert"><?= $escape($error) ?></p><?php endforeach; ?>
<?php if ($notice): ?><p class="module-notice" role="status"><?= $escape($notice) ?></p><?php endif; ?>

<?php if ($selected === null): ?>
<div class="page-toolbar"><div><h1>E-Lot Management</h1></div></div>
<section class="elots-list-card compact-filter-card">
    <form method="get" action="<?= $escape($url) ?>" class="officer-module-filters">
        <div class="form-group"><label for="module-status">Status</label><select id="module-status" name="status">
            <option value="">All statuses</option>
            <?php foreach (OfficerELot::LABELS as $status => $label): ?><option value="<?= $escape($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= $escape($label) ?></option><?php endforeach; ?>
        </select></div>
        <div class="form-group"><label for="module-category">Category</label><select id="module-category" name="category">
            <option value="">All categories</option>
            <?php foreach ($categories as $category): ?><option value="<?= (int) $category['category_id'] ?>" <?= $filters['category'] == $category['category_id'] ? 'selected' : '' ?>><?= $escape($category['category_name']) ?></option><?php endforeach; ?>
        </select></div>
        <div class="filter-actions"><button class="primary-btn" type="submit">Apply Filters</button><a class="secondary-btn" href="<?= $escape($url) ?>">Clear</a></div>
    </form>
</section>
<section class="elots-list-card officer-elot-results">
    <h2>Collector E-Lots</h2>
    <p class="officer-result-count"><?= (int) $listing['total'] ?> matching E-Lots</p>
    <?php if (!$listing['rows']): ?>
        <p class="officer-empty-state">No accessible E-Lots match these filters.</p>
    <?php else: ?>
        <div class="elots-table-wrapper officer-scan-wrapper"><table class="elots-table officer-scan-table" role="table">
            <thead role="rowgroup"><tr role="row"><th role="columnheader" scope="col">E-Lot</th><th role="columnheader" scope="col">Collector / Category</th><th role="columnheader" scope="col">Inventory</th><th role="columnheader" scope="col">Status</th><th role="columnheader" scope="col">Action</th></tr></thead>
            <tbody role="rowgroup">
            <?php foreach ($listing['rows'] as $lot): ?>
                <tr role="row">
                    <td role="cell" class="scan-primary"><strong><?= $escape($lot['title']) ?></strong><small class="elot-secondary-text"><?= $escape($lot['lot_code']) ?></small></td>
                    <td role="cell"><?= $escape($lot['collector_name']) ?><small class="elot-secondary-text"><?= $escape($lot['category_name']) ?></small></td>
                    <td role="cell"><?= $escape($lot['total_quantity']) ?> units · <?= $escape($lot['total_weight']) ?> kg<small class="elot-secondary-text"><?= (int) $lot['item_count'] ?> source items</small></td>
                    <td role="cell" class="scan-status"><span data-status="<?= $escape($lot['lot_status']) ?>" class="elot-status <?= $escape(strtolower(str_replace('_', '-', $lot['lot_status']))) ?>"><?= $escape(OfficerELot::LABELS[$lot['lot_status']]) ?></span>
                        <?php if ($lot['bidding_open_at'] !== null): ?><small class="elot-secondary-text"><?= $escape(OfficerELot::biddingPhase($lot)) ?> · <?= (int) $lot['bid_count'] ?> bids</small><?php endif; ?>
                    </td>
                    <td role="cell" class="scan-action"><a class="secondary-btn" href="<?= $escape($link(['id' => $lot['e_lot_id']])) ?>"><?= $lot['lot_status'] === 'PENDING_VERIFICATION' ? 'Review E-Lot' : 'View Details' ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
    <?php if ($listing['pages'] > 1): ?>
        <nav class="module-pagination" aria-label="E-Lot pages">
            <?php if ($filters['page'] > 1): ?><a class="secondary-btn" href="<?= $escape($link(['page' => $filters['page'] - 1])) ?>">Previous</a><?php endif; ?>
            <span>Page <?= (int) $filters['page'] ?> of <?= (int) $listing['pages'] ?></span>
            <?php if ($filters['page'] < $listing['pages']): ?><a class="secondary-btn" href="<?= $escape($link(['page' => $filters['page'] + 1])) ?>">Next</a><?php endif; ?>
        </nav>
    <?php endif; ?>
</section>
<?php else:
    $lot = $selected;
    $showWinnerSelection = $lot['can_award'] && (bool) array_filter(
        $lot['bids'],
        static fn (array $bid): bool => $bid['bid_status'] === 'SUBMITTED' && $bid['eligible']
    );
?>
<section id="module-details" class="officer-module-details officer-elot-details">
<section class="elots-list-card">
    <header class="officer-elot-heading">
        <div>
            <h1><?= $escape($lot['title']) ?></h1>
            <p class="elot-secondary-text"><?= $escape($lot['lot_code']) ?> · <?= $escape($lot['category_name']) ?></p>
        </div>
        <a class="primary-btn officer-elot-back" href="<?= $escape($link()) ?>"><span aria-hidden="true">←</span> Back to E-Lots</a>
    </header>
    <p><span class="elot-status <?= $escape(strtolower(str_replace('_', '-', $lot['lot_status']))) ?>" data-status="<?= $escape($lot['lot_status']) ?>"><?= $escape(OfficerELot::LABELS[$lot['lot_status']]) ?></span></p>
    <p class="elot-secondary-text">Created by <?= $escape($lot['collector_name']) ?> · <?= $escape($lot['created_at']) ?></p>
    <div class="elot-summary-grid<?= $lot['lot_status'] === 'OPEN_FOR_BIDDING' ? ' has-bid-count' : '' ?>">
        <div><span>Total units</span><strong><?= $escape($lot['total_quantity']) ?></strong></div>
        <div><span>Total weight (kg)</span><strong><?= $escape($lot['total_weight']) ?></strong></div>
        <?php if ($lot['lot_status'] === 'OPEN_FOR_BIDDING'): ?><div><span>Submitted bids</span><strong><?= (int) $lot['bid_count'] ?></strong></div><?php endif; ?>
    </div>
    <?php if ($lot['bidding_open_at'] !== null || $lot['bidding_close_at'] !== null): ?>
        <div class="elot-bidding-window">
            <?php if ($lot['lot_status'] === 'OPEN_FOR_BIDDING'): ?><strong><?= $escape(OfficerELot::biddingPhase($lot)) ?></strong><?php endif; ?>
            <span>Opens <?= $escape($lot['bidding_open_at']) ?></span>
            <span>Closes <?= $escape($lot['bidding_close_at']) ?></span>
        </div>
        <?php if ($lot['lot_status'] === 'OPEN_FOR_BIDDING' && $lot['bidding_close_at'] !== null && $lot['now'] >= $lot['bidding_close_at'] && !$lot['bid_count']): ?>
            <p class="elot-secondary-text">No submitted bids to select.</p>
        <?php elseif ($lot['can_award'] && !$showWinnerSelection): ?>
            <p class="elot-secondary-text">No eligible bids to select.</p>
        <?php endif; ?>
    <?php endif; ?>
    <?php if ($lot['reviewer_name'] !== null): ?><p class="elot-secondary-text">Reviewed by <?= $escape($lot['reviewer_name']) ?> · <?= $escape($lot['verified_at']) ?></p><?php endif; ?>
    <?php if ($lot['verification_note'] !== null && $lot['verification_note'] !== ''): ?><p class="module-message"><strong>Officer note</strong> <?= $escape($lot['verification_note']) ?></p><?php endif; ?>
    <?php if ($lot['source_error']): ?><p class="officer-form-error" role="alert"><?= $escape($lot['source_error']) ?></p><?php endif; ?>
</section>
<?php $actionUrl=$basePath.'/officer/e-lots/'.$lot['e_lot_id'].'/manage?'.http_build_query($filters); ?>
<?php if ($lot['lot_status']==='PENDING_VERIFICATION'): ?>
<section class="elots-list-card elot-action-card">
<h2>Review this E-Lot</h2>
<p class="elot-secondary-text">Check the source inventory below. Set a bidding window to verify the lot, or enter a reason to reject it.</p>
<form method="post" action="<?= $escape($actionUrl) ?>" class="elot-review-form" data-officer-confirm="Save this review decision for <?= $escape($lot['lot_code']) ?>? Verification schedules the bidding window; rejection preserves its source inventory.">
<input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>">
<div class="form-group"><label for="lot-note">Officer note (required for rejection)</label><textarea id="lot-note" name="note" maxlength="500" rows="3"><?= $escape($formValue('note')) ?></textarea></div>
<div class="elot-form-grid"><div class="form-group"><label for="lot-open">Bidding opens (Sri Lanka time)</label><input type="datetime-local" step="1" id="lot-open" name="open" value="<?= $escape($formValue('open')) ?>"></div><div class="form-group"><label for="lot-close">Bidding closes (Sri Lanka time)</label><input type="datetime-local" step="1" id="lot-close" name="close" value="<?= $escape($formValue('close')) ?>"></div></div>
<div class="filter-actions"><button class="primary-btn" name="action" value="approve" data-confirm-trigger data-eligible="<?= !$lot['source_error'] && !$lot['has_bid_history']?'true':'false' ?>" disabled>Verify &amp; Schedule Bidding</button><button class="secondary-btn" name="action" value="reject" data-confirm-trigger data-eligible="<?= !$lot['has_bid_history']?'true':'false' ?>" disabled>Reject E-Lot</button></div></form>
<?php if ($lot['has_bid_history']): ?><p class="module-lock-reason">Existing bids prevent another review decision.</p><?php endif; ?>
</section>
<?php endif; ?>
<noscript>Enable JavaScript to confirm management decisions.</noscript>
<?php if ($lot['can_reopen']): ?>
<section class="elots-list-card elot-action-card">
    <h2>Reopen bidding</h2>
    <p class="elot-secondary-text">Schedule a new bidding window for this E-Lot.</p>
    <form method="post" action="<?= $escape($actionUrl) ?>" class="elot-review-form" data-officer-confirm="Save a new bidding window for <?= $escape($lot['lot_code']) ?>? The E-Lot and its bid history will be kept.">
        <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>">
        <input type="hidden" name="action" value="reopen">
        <div class="elot-form-grid">
            <div class="form-group"><label for="reopen-open">Bidding opens (Sri Lanka time)</label><input id="reopen-open" type="datetime-local" name="open" step="1" value="<?= $escape($formValue('open')) ?>" required></div>
            <div class="form-group"><label for="reopen-close">Bidding closes (Sri Lanka time)</label><input id="reopen-close" type="datetime-local" name="close" step="1" value="<?= $escape($formValue('close')) ?>" required></div>
        </div>
        <div class="form-group"><label for="reopen-note">Officer note (optional)</label><textarea id="reopen-note" name="note" maxlength="500" rows="2"><?= $escape($formValue('note')) ?></textarea></div>
        <div class="filter-actions"><button class="primary-btn" data-confirm-trigger disabled>Reopen Bidding</button></div>
    </form>
</section>
<?php endif; ?>
<?php if ($lot['handover']): $handover=$lot['handover']; ?><section class="elots-list-card elot-action-card"><h2>Digital handover</h2><p>Winning Recycler: <?= $escape($handover['company_name']) ?> · Bid #<?= (int)$handover['winning_bid_id'] ?></p><p><?= $escape($handover['handover_status']) ?> · <?= $escape($handover['handover_date']) ?> · Officer: <?= $escape($handover['officer_name']) ?></p><p class="module-message"><?= $escape($handover['remarks']) ?></p>
<?php if ($lot['lot_status']==='AWARDED' && in_array($handover['handover_status'],['PENDING','SCHEDULED'],true)): ?><form method="post" action="<?= $escape($actionUrl) ?>" class="elot-manage-form" data-officer-confirm="Save handover for <?= $escape($lot['lot_code']) ?> and winning Recycler <?= $escape($handover['company_name']) ?>?">
<input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>"><input type="hidden" name="action" value="handover"><input type="hidden" name="expected_handover" value="<?= $escape(OfficerELot::handoverVersion($handover)) ?>">
<div class="form-group"><label for="handover-status">Handover status</label><select id="handover-status" name="handover_status"><option value="SCHEDULED" <?= $formValue('handover_status','SCHEDULED')==='SCHEDULED'?'selected':'' ?>>Scheduled</option><option value="COMPLETED" <?= $formValue('handover_status')==='COMPLETED'?'selected':'' ?>>Completed</option></select></div>
<div class="form-group"><label for="handover-date">Handover date/time (Sri Lanka time)</label><input id="handover-date" type="datetime-local" step="1" name="date" value="<?= $escape($formValue('date',$handover['handover_date']?str_replace(' ','T',$handover['handover_date']):'')) ?>" required></div>
<div class="form-group"><label for="handover-note">Remarks</label><textarea id="handover-note" name="note" maxlength="500"><?= $escape($formValue('note',$handover['remarks']??'')) ?></textarea></div><button class="primary-btn" data-confirm-trigger disabled>Save Handover</button></form><?php endif; ?></section><?php endif; ?>
<?php if ($lot['bids']): ?>
    <?php if ($showWinnerSelection): ?>
        <section class="elots-list-card elot-action-card"><h2>Select a winning bid</h2>
        <p class="elot-secondary-text">Bidding has closed. Choose an eligible Recycler to proceed to handover.</p>
    <?php else: ?>
        <details class="elots-list-card elot-supporting-card">
        <summary>Recycler bids <small><?= count($lot['bids']) ?></small></summary>
    <?php endif; ?>
        <div class="elots-table-wrapper">
            <table class="elots-table">
                <thead><tr>
                    <th scope="col">Recycler</th><th scope="col">Amount (LKR)</th><th scope="col">Status</th>
                    <?php if ($showWinnerSelection): ?><th scope="col">Action</th><?php endif; ?>
                </tr></thead>
                <tbody>
                <?php foreach ($lot['bids'] as $bid): ?>
                    <tr>
                        <td><strong><?= $escape($bid['company_name']) ?></strong><small class="elot-secondary-text">Bid #<?= (int) $bid['bid_id'] ?> · <?= $escape($bid['submitted_at']) ?></small></td>
                        <td><?= $escape($bid['bid_amount']) ?></td>
                        <td><?= $escape(ucfirst(strtolower($bid['bid_status']))) ?>
                            <?php if ($bid['bid_status'] === 'SUBMITTED'): ?><small class="elot-secondary-text"><?= $bid['eligible'] ? 'Eligible' : 'Not currently eligible' ?></small>
                            <?php elseif ($bid['reviewed_at'] !== null): ?><small class="elot-secondary-text">Reviewed <?= $escape($bid['reviewed_at']) ?></small><?php endif; ?>
                        </td>
                        <?php if ($showWinnerSelection): ?>
                            <td>
                                <?php if ($bid['bid_status'] === 'SUBMITTED' && $bid['eligible']): ?>
                                    <form method="post" action="<?= $escape($actionUrl) ?>" data-officer-confirm="Award <?= $escape($lot['lot_code']) ?> to <?= $escape($bid['company_name']) ?> using bid #<?= (int) $bid['bid_id'] ?> (LKR <?= $escape($bid['bid_amount']) ?>)? This decision is final.">
                                        <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>">
                                        <input type="hidden" name="bid_id" value="<?= (int) $bid['bid_id'] ?>">
                                        <button class="primary-btn" name="action" value="award" data-confirm-trigger disabled>Select Winner</button>
                                    </form>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php if ($showWinnerSelection): ?></section><?php else: ?></details><?php endif; ?>
<?php endif; ?>
<details class="elots-list-card elot-supporting-card">
    <summary>Source inventory <small><?= count($lot['items']) ?> items</small></summary>
    <?php if (!$lot['items']): ?><p class="officer-empty-state">No source items recorded.</p><?php else: ?>
    <div class="elots-table-wrapper"><table class="elots-table">
        <thead><tr><th scope="col">Item</th><th scope="col">Collected</th><th scope="col">Condition / Risk</th><th scope="col">Collection source</th></tr></thead>
        <tbody>
        <?php foreach ($lot['items'] as $item): ?>
            <tr>
                <td><strong><?= $escape($item['item_name']) ?></strong><small class="elot-secondary-text"><?= $escape(ucfirst(strtolower(str_replace('_', ' ', $item['item_result'] ?? '')))) ?></small></td>
                <td><strong><?= $escape($item['actual_quantity']) ?></strong> of <?= $escape($item['requested_quantity']) ?> units<small class="elot-secondary-text"><?= $escape($item['actual_weight_kg']) ?> kg</small></td>
                <td><?= $escape($item['actual_condition']) ?><small class="elot-secondary-text"><?= $escape($item['actual_risk_level']) ?> risk</small></td>
                <td><?= $escape($item['area_name']) ?><small class="elot-secondary-text">SCH-<?= (int) $item['schedule_id'] ?> · <?= $escape($item['collection_date']) ?></small>
                    <details class="elot-source-details">
                        <summary>Traceability</summary>
                        <dl>
                            <dt>Submission</dt><dd>#<?= (int) $item['schedule_collection_id'] ?> · <?= $escape($item['verification_status']) ?></dd>
                            <dt>Collection record</dt><dd>#<?= (int) $item['collection_record_id'] ?></dd>
                            <dt>Request / Item</dt><dd>#<?= (int) $item['request_id'] ?> / #<?= (int) $item['request_item_id'] ?></dd>
                            <dt>Pickup result</dt><dd><?= $escape($item['pickup_result']) ?></dd>
                            <dt>Requested risk</dt><dd><?= $escape($item['applied_risk_level']) ?></dd>
                            <?php if ($item['notes'] !== null && $item['notes'] !== ''): ?><dt>Item notes</dt><dd><?= $escape($item['notes']) ?></dd><?php endif; ?>
                        </dl>
                    </details>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</details>
<?php if ($lot['history']): ?>
    <details class="elots-list-card elot-supporting-card">
        <summary>Management history</summary>
        <?php $history=$lot['history']; $showHistoryHeading=false; require __DIR__.'/module-history.php'; ?>
    </details>
<?php endif; ?>
</section>
<?php require __DIR__.'/module-confirmation.php'; ?>
<?php endif; ?>
</section>
