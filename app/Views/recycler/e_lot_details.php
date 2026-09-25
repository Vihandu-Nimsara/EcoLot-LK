<?php
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$statuses = ['SUBMITTED' => 'Submitted', 'WINNING' => 'Won', 'REJECTED' => 'Lost', 'WITHDRAWN' => 'Withdrawn'];
$isAwardedView = ($currentPage ?? '') === 'awarded-e-lots';
$suggestedAmount = $suggestedAmount ?? RecyclerBidService::suggestedAmount($lot['highest_amount']);
$backPath = $isAwardedView ? 'awarded-e-lots' : ($lot['bid_id'] ? 'my-bids' : 'eligible-e-lots');
$backLabel = $isAwardedView ? 'Back to Awarded E-Lots' : ($lot['bid_id'] ? 'Back to My Bids' : 'Back to Eligible E-Lots');
$canPlace = (bool) $lot['can_bid'] && !$lot['bid_id'];
$canRevise = (bool) $lot['can_bid'] && $lot['bid_status'] === 'SUBMITTED';
$canWithdraw = (bool) $lot['can_withdraw'] && $lot['bid_status'] === 'SUBMITTED';
?>
<section class="workflow-page">
<div class="workflow-header"><div><h1><?= $isAwardedView ? 'Awarded E-Lot Details' : 'E-Lot Details' ?></h1><p><?= $isAwardedView ? 'Review award and Municipal Officer-recorded handover information.' : 'Review the waste composition, handling information and bidding deadline.' ?></p></div><div class="workflow-actions"><a class="secondary-workflow-btn" href="<?= $escape($basePath) ?>/recycler/<?= $backPath ?>"><?= $backLabel ?></a>
<?php if ($canPlace || $canRevise): ?><a class="primary-workflow-btn" href="#bid-form"><?= $canPlace ? 'Place Bid' : 'Revise Bid' ?></a><?php endif; ?></div></div>
<section class="workflow-card"><div class="workflow-section-header"><h2><?= $isAwardedView ? 'Lot Information' : 'E-Lot Summary' ?></h2></div><div class="detail-grid">
<?php foreach (['Lot Code' => $lot['lot_code'], 'Lot Title' => $lot['title'], 'Category' => $lot['category_name'], 'Status' => ucwords(strtolower(str_replace('_', ' ', $lot['lot_status']))), 'Bidding opens' => $lot['bidding_open_at'], 'Bidding closes' => $lot['bidding_close_at']] as $label => $value): ?>
<div class="detail-item"><span class="detail-label"><?= $escape($label) ?></span><?php if ($label === 'Status'): ?><span class="badge badge-<?= strtolower($lot['lot_status']) ?>"><?= $escape($value) ?></span><?php else: ?><strong class="detail-value"><?= $escape($value ?? 'Not set') ?></strong><?php endif; ?></div>
<?php endforeach; ?></div></section>
<section class="workflow-card bidding-information-card"><div class="workflow-section-header"><h2>Bidding Information</h2><p>Anonymous context from currently submitted bids. The Municipal Officer selects the winner.</p></div><div class="detail-grid">
<div class="detail-item"><span class="detail-label">Highest active bid</span><strong><?= $lot['highest_amount'] === null ? 'No bids yet' : 'Rs. ' . number_format((float) $lot['highest_amount'], 2) ?></strong></div>
<div class="detail-item"><span class="detail-label">Submitted bid count</span><strong><?= (int) $lot['active_count'] ?></strong></div>
<?php if ($lot['bid_id']): ?><div class="detail-item"><span class="detail-label">My bid · <span class="badge badge-<?= strtolower($lot['bid_status']) ?>"><?= $statuses[$lot['bid_status']] ?></span></span><strong>Rs. <?= number_format((float) $lot['bid_amount'], 2) ?></strong><span>Submitted <?= $escape($lot['submitted_at']) ?></span></div><?php endif; ?>
</div>
<?php if (!$canPlace && !$canRevise && !$canWithdraw): ?><p><?= $escape(match ($lot['bid_status']) {
    'WITHDRAWN' => 'This bid has already been withdrawn. It remains in your history and cannot be replaced.',
    'WINNING' => 'Your bid was selected by the Municipal Officer. It can no longer be changed.',
    'REJECTED' => 'Your bid was not selected. It can no longer be changed.',
    default => 'Bid actions are unavailable because bidding is closed or your current eligibility does not permit them.',
}) ?></p><?php endif; ?>
</section>
<section class="workflow-card">
<div class="workflow-section-header"><h2>Waste Summary</h2></div>
<div class="detail-grid">
<div class="detail-item"><span class="detail-label">Category</span><strong class="detail-value"><?= $escape($lot['category_name']) ?></strong></div>
<div class="detail-item"><span class="detail-label">Total Quantity</span><strong class="detail-value"><?= (int) array_sum(array_column($items, 'actual_quantity')) ?></strong></div>
<div class="detail-item"><span class="detail-label">Total Weight</span><strong class="detail-value"><?= number_format(array_sum(array_column($items, 'actual_weight_kg')), 3) ?> kg</strong></div>
</div></section>
<section class="workflow-card"><div class="workflow-section-header"><h2>Item Breakdown</h2><p>Risk reflects the assessment recorded when the pickup was requested.</p></div>
<?php if (!$items): ?><div class="empty-state">No item details recorded.</div><?php else: ?>
<div class="bid-table-wrapper"><table class="bid-table items-table"><thead><tr><th>Item</th><th>Actual Quantity</th><th>Actual Weight (kg)</th><th>Actual Condition</th><th>Request-time Risk</th></tr></thead><tbody>
<?php foreach ($items as $item): ?><tr><td data-label="Item"><?= $escape($item['item_name']) ?></td><td data-label="Actual Quantity"><?= (int) $item['actual_quantity'] ?></td><td data-label="Actual Weight (kg)"><?= $escape($item['actual_weight_kg']) ?></td><td data-label="Actual Condition"><?= $escape($item['actual_condition']) ?></td><td data-label="Request-time Risk"><?= $escape($item['applied_risk_level']) ?></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section>
<?php if ($canPlace || $canRevise || $canWithdraw): ?>
<section class="workflow-card"><div class="workflow-section-header"><h2>Manage Your Bid</h2></div>
<?php if ($canPlace || $canRevise): ?>
<form id="bid-form" class="bid-form" method="post" action="<?= $escape($basePath) ?>/recycler/<?= $canPlace ? 'e-lot/' . (int) $lot['e_lot_id'] . '/bid' : 'bid/' . (int) $lot['bid_id'] . '/update' ?>">
<input type="hidden" name="_csrf_token" value="<?= $escape(Csrf::token()) ?>">
<label class="dialog-field"><span><?= $canPlace ? 'Your bid amount (Rs.)' : 'New bid amount (Rs.)' ?></span><input name="bid_amount" type="number" min="100.00" max="999999999999.99" step="0.01" required value="<?= $escape($suggestedAmount ?? '100.00') ?>"></label>
<div class="bid-guidance"><p><strong>Minimum bid: Rs. 100.00</strong></p>
<p>Suggested bid: <?= $suggestedAmount === null ? 'Unavailable within the amount limit' : 'Rs. ' . $escape($suggestedAmount) ?></p>
<p>The suggestion is optional. Enter any amount of at least Rs. 100.00; you may bid below the highest amount<?= $canRevise ? ' or increase or decrease your current bid (unchanged amounts are not accepted)' : '' ?>.</p></div>
<div class="form-actions"><button class="primary-workflow-btn" type="submit"><?= $canPlace ? 'Place Bid' : 'Revise Bid' ?></button></div>
</form>
<?php endif; ?>
<?php if ($canWithdraw): ?>
<form class="withdraw-form" method="post" action="<?= $escape($basePath) ?>/recycler/bid/<?= (int) $lot['bid_id'] ?>/withdraw">
<input type="hidden" name="_csrf_token" value="<?= $escape(Csrf::token()) ?>">
<p>Withdrawal is permanent. You cannot place another bid on this E-Lot.</p><button class="danger-workflow-btn" type="submit">Withdraw Bid</button>
</form>
<?php endif; ?>
</section>
<?php endif; ?>
</section>
