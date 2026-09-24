<?php
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$statuses = ['SUBMITTED' => 'Submitted', 'WINNING' => 'Won', 'REJECTED' => 'Lost', 'WITHDRAWN' => 'Withdrawn'];
$canPlace = (bool) $lot['can_bid'] && !$lot['bid_id'];
$canRevise = (bool) $lot['can_bid'] && $lot['bid_status'] === 'SUBMITTED';
$canWithdraw = (bool) $lot['can_withdraw'] && $lot['bid_status'] === 'SUBMITTED';
?>
<section class="workflow-page">
<div class="workflow-header"><div><h1><?= $escape($lot['lot_code']) ?></h1><p><?= $escape($lot['title']) ?></p></div><a class="secondary-workflow-btn" href="<?= $escape($basePath) ?>/recycler/eligible-e-lots">Eligible E-Lots</a></div>
<section class="workflow-card"><h2>Lot Details</h2><div class="detail-grid">
<?php foreach (['Category' => $lot['category_name'], 'Status' => str_replace('_', ' ', $lot['lot_status']), 'Bidding opens' => $lot['bidding_open_at'], 'Bidding closes' => $lot['bidding_close_at']] as $label => $value): ?>
<div class="detail-item"><span class="detail-label"><?= $escape($label) ?></span><strong><?= $escape($value ?? 'Not set') ?></strong></div>
<?php endforeach; ?></div></section>
<section class="workflow-card"><h2>Collected Items</h2><p>Risk reflects the assessment recorded when the pickup was requested.</p>
<div class="bid-table-wrapper"><table class="bid-table"><thead><tr><th>Item</th><th>Actual Quantity</th><th>Actual Weight (kg)</th><th>Actual Condition</th><th>Request-time Risk</th></tr></thead><tbody>
<?php foreach ($items as $item): ?><tr><td data-label="Item"><?= $escape($item['item_name']) ?></td><td data-label="Actual Quantity"><?= (int) $item['actual_quantity'] ?></td><td data-label="Actual Weight (kg)"><?= $escape($item['actual_weight_kg']) ?></td><td data-label="Actual Condition"><?= $escape($item['actual_condition']) ?></td><td data-label="Request-time Risk"><?= $escape($item['applied_risk_level']) ?></td></tr><?php endforeach; ?>
<?php if (!$items): ?><tr><td colspan="5">No item details recorded.</td></tr><?php endif; ?>
</tbody></table></div></section>
<section class="workflow-card"><h2>Bidding</h2><div class="detail-grid">
<div class="detail-item"><span class="detail-label">Highest active bid</span><strong><?= $lot['highest_amount'] === null ? 'No bids yet' : 'Rs. ' . number_format((float) $lot['highest_amount'], 2) ?></strong></div>
<div class="detail-item"><span class="detail-label">Submitted bid count</span><strong><?= (int) $lot['active_count'] ?></strong></div>
<?php if ($lot['bid_id']): ?><div class="detail-item"><span class="detail-label">My bid · <?= $statuses[$lot['bid_status']] ?></span><strong>Rs. <?= number_format((float) $lot['bid_amount'], 2) ?></strong><span>Submitted <?= $escape($lot['submitted_at']) ?></span></div><?php endif; ?>
</div>
<?php if ($canPlace || $canRevise): ?>
<form method="post" action="<?= $escape($basePath) ?>/recycler/<?= $canPlace ? 'e-lot/' . (int) $lot['e_lot_id'] . '/bid' : 'bid/' . (int) $lot['bid_id'] . '/update' ?>">
<input type="hidden" name="_csrf_token" value="<?= $escape(Csrf::token()) ?>">
<label class="dialog-field"><span><?= $canPlace ? 'Your bid amount (Rs.)' : 'New bid amount (Rs.)' ?></span><input name="bid_amount" type="number" min="0.01" max="999999999999.99" step="0.01" required value="<?= $canRevise ? $escape($lot['bid_amount']) : '' ?>"></label>
<p><?= $canPlace ? 'Enter a positive amount.' : 'Your revised amount must exceed your current bid.' ?> Your offer does not need to exceed the highest active bid.</p>
<div class="form-actions"><button class="primary-workflow-btn" type="submit"><?= $canPlace ? 'Place Bid' : 'Revise Bid' ?></button></div>
</form>
<?php endif; ?>
<?php if ($canWithdraw): ?>
<form method="post" action="<?= $escape($basePath) ?>/recycler/bid/<?= (int) $lot['bid_id'] ?>/withdraw">
<input type="hidden" name="_csrf_token" value="<?= $escape(Csrf::token()) ?>">
<p>Withdrawal is permanent. You cannot place another bid on this E-Lot.</p><button class="secondary-workflow-btn" type="submit">Withdraw Bid</button>
</form>
<?php endif; ?>
<?php if (!$canPlace && !$canRevise && !$canWithdraw): ?><p>No bid actions are currently available. Withdrawn bids remain in your history and cannot be replaced.</p><?php endif; ?>
<a href="<?= $escape($basePath) ?>/recycler/my-bids">My Bids history</a>
</section></section>
