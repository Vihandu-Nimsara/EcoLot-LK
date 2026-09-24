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
<div class="workflow-header"><div><h1><?= $escape($lot['lot_code']) ?></h1><p><?= $escape($lot['title']) ?></p></div><a class="secondary-workflow-btn" href="<?= $escape($basePath) ?>/recycler/<?= $backPath ?>"><?= $backLabel ?></a></div>
<section class="workflow-card"><h2>Lot Details</h2><div class="detail-grid">
<?php foreach (['Category' => $lot['category_name'], 'Status' => ucwords(strtolower(str_replace('_', ' ', $lot['lot_status']))), 'Bidding opens' => $lot['bidding_open_at'], 'Bidding closes' => $lot['bidding_close_at']] as $label => $value): ?>
<div class="detail-item"><span class="detail-label"><?= $escape($label) ?></span><?php if ($label === 'Status'): ?><span class="badge badge-<?= strtolower($lot['lot_status']) ?>"><?= $escape($value) ?></span><?php else: ?><strong><?= $escape($value ?? 'Not set') ?></strong><?php endif; ?></div>
<?php endforeach; ?></div></section>
<section class="workflow-card"><h2>Collected Items</h2><p>Risk reflects the assessment recorded when the pickup was requested.</p>
<div class="bid-table-wrapper"><table class="bid-table"><thead><tr><th>Item</th><th>Actual Quantity</th><th>Actual Weight (kg)</th><th>Actual Condition</th><th>Request-time Risk</th></tr></thead><tbody>
<?php foreach ($items as $item): ?><tr><td data-label="Item"><?= $escape($item['item_name']) ?></td><td data-label="Actual Quantity"><?= (int) $item['actual_quantity'] ?></td><td data-label="Actual Weight (kg)"><?= $escape($item['actual_weight_kg']) ?></td><td data-label="Actual Condition"><?= $escape($item['actual_condition']) ?></td><td data-label="Request-time Risk"><?= $escape($item['applied_risk_level']) ?></td></tr><?php endforeach; ?>
<?php if (!$items): ?><tr><td colspan="5">No item details recorded.</td></tr><?php endif; ?>
</tbody></table></div></section>
<section class="workflow-card"><h2>Bidding</h2><div class="detail-grid">
<div class="detail-item"><span class="detail-label">Highest active bid</span><strong><?= $lot['highest_amount'] === null ? 'No bids yet' : 'Rs. ' . number_format((float) $lot['highest_amount'], 2) ?></strong></div>
<div class="detail-item"><span class="detail-label">Submitted bid count</span><strong><?= (int) $lot['active_count'] ?></strong></div>
<?php if ($lot['bid_id']): ?><div class="detail-item"><span class="detail-label">My bid · <span class="badge badge-<?= strtolower($lot['bid_status']) ?>"><?= $statuses[$lot['bid_status']] ?></span></span><strong>Rs. <?= number_format((float) $lot['bid_amount'], 2) ?></strong><span>Submitted <?= $escape($lot['submitted_at']) ?></span></div><?php endif; ?>
</div>
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
<?php if (!$canPlace && !$canRevise && !$canWithdraw): ?><p><?= $escape(match ($lot['bid_status']) {
    'WITHDRAWN' => 'This bid has already been withdrawn. It remains in your history and cannot be replaced.',
    'WINNING' => 'Your bid was selected by the Municipal Officer. It can no longer be changed.',
    'REJECTED' => 'Your bid was not selected. It can no longer be changed.',
    default => 'Bid actions are unavailable because bidding is closed or your current eligibility does not permit them.',
}) ?></p><?php endif; ?>
</section></section>
