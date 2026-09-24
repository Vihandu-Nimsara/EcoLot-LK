<?php
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$statuses = ['SUBMITTED' => 'Submitted', 'WINNING' => 'Won', 'REJECTED' => 'Lost', 'WITHDRAWN' => 'Withdrawn'];
?>
<section class="eligible-bid-page"><section class="bid-card">
<div class="bid-header"><div><h1>Eligible Open E-Lots</h1><p>E-Lots currently matching your verified account, licence and approved handling capabilities.</p></div></div>
<form class="light-filter" data-client-filter data-rows="[data-eligible-row]" data-empty="[data-eligible-filter-empty]" data-result="[data-eligible-result]">
<div class="light-filter-controls"><div class="filter-field"><label for="eligible-search">Search E-Lots</label><input id="eligible-search" name="search" type="search" placeholder="Lot code, title or category"></div>
<div class="filter-field"><label for="eligible-category">Category</label><select id="eligible-category" name="category"><option value="">All Categories</option><?php foreach (array_unique(array_column($lots, 'category_name')) as $category): ?><option><?= $escape($category) ?></option><?php endforeach; ?></select></div></div></form>
<p class="filter-result" data-eligible-result role="status" aria-live="polite"></p>
<?php if (!$lots): ?><div class="empty-state">No eligible E-Lots are available.</div><?php else: ?>
<div class="bid-table-wrapper"><table class="bid-table"><thead><tr><th>E-Lot</th><th>Category</th><th>Bidding Deadline</th><th>Active Bids</th><th>My Bid</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($lots as $lot): ?>
<tr data-eligible-row data-search="<?= $escape($lot['lot_code'] . ' ' . $lot['title'] . ' ' . $lot['category_name']) ?>" data-category="<?= $escape($lot['category_name']) ?>">
<td data-label="E-Lot"><strong class="table-primary-text"><?= $escape($lot['lot_code']) ?></strong><span class="table-secondary-text"><?= $escape($lot['title']) ?></span></td>
<td data-label="Category"><?= $escape($lot['category_name']) ?></td>
<td data-label="Bidding Deadline"><time class="table-date" datetime="<?= $escape(str_replace(' ', 'T', $lot['bidding_close_at'])) ?>"><?= $escape(substr($lot['bidding_close_at'], 0, 10)) ?><span class="table-secondary-text"><?= $escape(substr($lot['bidding_close_at'], 11, 5)) ?></span></time></td>
<td data-label="Active Bids"><strong><?= (int) $lot['active_count'] ?> submitted</strong><span class="table-secondary-text">Highest: <?= $lot['highest_amount'] === null ? 'No bids yet' : 'Rs. ' . number_format((float) $lot['highest_amount'], 2) ?></span></td>
<td data-label="My Bid"><?php if ($lot['bid_id']): ?><span class="badge badge-<?= strtolower($lot['bid_status']) ?>"><?= $statuses[$lot['bid_status']] ?></span><span class="table-secondary-text">Rs. <?= number_format((float) $lot['bid_amount'], 2) ?></span><?php else: ?>Not bid yet<?php endif; ?></td>
<td data-label="Actions"><a class="edit-btn primary-row-action" href="<?= $escape($basePath) ?>/recycler/e-lot/<?= (int) $lot['e_lot_id'] ?>">View Details</a></td>
</tr><?php endforeach; ?>
</tbody></table></div><div class="filtered-empty-state" data-eligible-filter-empty hidden>No E-Lots match these filters.</div><?php endif; ?>
</section></section>
