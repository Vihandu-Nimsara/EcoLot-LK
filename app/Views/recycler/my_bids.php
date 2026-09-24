<?php
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$statuses = ['SUBMITTED' => 'Submitted', 'WINNING' => 'Won', 'REJECTED' => 'Lost', 'WITHDRAWN' => 'Withdrawn'];
?>
<section class="my-bids-page"><section class="bid-card"><div class="bid-header"><div><h1>My Bids</h1><p>Your submitted bids and complete bid history.</p></div></div>
<form class="light-filter" data-client-filter data-rows="[data-bid-row]" data-empty="[data-bids-empty]" data-result="[data-bids-result]"><div class="light-filter-controls"><div class="filter-field"><label for="bid-search">Search bids</label><input id="bid-search" name="search" type="search" placeholder="Lot code, title or category"></div><div class="filter-field"><label for="bid-status">Status</label><select id="bid-status" name="status"><option value="">All Statuses</option><?php foreach ($statuses as $value => $label): ?><option value="<?= $value ?>"><?= $label ?></option><?php endforeach; ?></select></div></div></form>
<p class="filter-result" data-bids-result role="status" aria-live="polite"></p>
<?php if (!$bids): ?><div class="empty-state">You have not placed any bids.</div><?php else: ?>
<div class="bid-table-wrapper"><table class="bids-table"><thead><tr><th>E-Lot</th><th>Category</th><th>My Bid</th><th>Status</th><th>Submitted</th><th>Bidding Deadline</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($bids as $bid): ?><tr data-bid-row data-search="<?= $escape($bid['lot_code'] . ' ' . $bid['title'] . ' ' . $bid['category_name']) ?>" data-status="<?= $escape($bid['bid_status']) ?>">
<td data-label="E-Lot"><strong class="table-primary-text"><?= $escape($bid['lot_code']) ?></strong><span class="table-secondary-text"><?= $escape($bid['title']) ?></span></td>
<td data-label="Category"><?= $escape($bid['category_name']) ?></td>
<td data-label="My Bid"><strong>Rs. <?= number_format((float) $bid['bid_amount'], 2) ?></strong><?php if ($bid['bid_status'] === 'SUBMITTED'): ?><span class="table-secondary-text">Highest active: Rs. <?= number_format((float) $bid['highest_amount'], 2) ?></span><?php endif; ?></td>
<td data-label="Status"><span class="badge badge-<?= strtolower($bid['bid_status']) ?>"><?= $statuses[$bid['bid_status']] ?></span></td>
<td data-label="Submitted"><?= $escape($bid['submitted_at']) ?></td><td data-label="Bidding Deadline"><?= $escape($bid['bidding_close_at']) ?></td>
<td data-label="Actions"><div class="row-actions">
<a class="edit-btn secondary-row-action" href="<?= $escape($basePath) ?>/recycler/e-lot/<?= (int) $bid['e_lot_id'] ?>">View Details</a>
<?php if ($bid['can_revise']): ?>
<a class="edit-btn primary-row-action" href="<?= $escape($basePath) ?>/recycler/e-lot/<?= (int) $bid['e_lot_id'] ?>#bid-form">Revise Bid</a>
<?php endif; ?>
<?php if ($bid['can_withdraw']): ?>
<form method="post" action="<?= $escape($basePath) ?>/recycler/bid/<?= (int) $bid['bid_id'] ?>/withdraw">
<input type="hidden" name="_csrf_token" value="<?= $escape(Csrf::token()) ?>">
<button class="edit-btn secondary-row-action" type="submit">Withdraw Bid</button>
<span class="table-secondary-text">Permanent; you cannot bid again on this E-Lot.</span>
</form>
<?php endif; ?>
</div></td>
</tr><?php endforeach; ?></tbody></table></div><div class="filtered-empty-state" data-bids-empty hidden>No bids match these filters.</div><?php endif; ?></section></section>
