<?php $escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?>
<section class="awarded-e-lots-page"><section class="awarded-e-lot-card">
<div class="awarded-e-lot-header"><div><h1>My Awarded E-Lots</h1><p>Your winning bids, selected by the Municipal Officer.</p></div></div>
<form class="light-filter" data-client-filter data-rows="[data-awarded-row]" data-empty="[data-awarded-empty]" data-result="[data-awarded-result]"><div class="filter-field"><label for="awarded-search">Search E-Lots</label><input id="awarded-search" name="search" type="search" placeholder="Code, title or category"></div></form>
<p class="filter-result" data-awarded-result role="status" aria-live="polite"></p>
<?php if (!$awardedLots): ?><div class="empty-state">You have no winning bids.</div><?php else: ?>
<div class="table-responsive"><table class="awarded-table"><thead><tr><th>E-Lot</th><th>Category</th><th>Winning Bid</th><th>Lot Status</th><th>Handover</th><th>Action</th></tr></thead><tbody>
<?php foreach ($awardedLots as $lot): ?>
<tr data-awarded-row data-search="<?= $escape($lot['lot_code'] . ' ' . $lot['title'] . ' ' . $lot['category_name']) ?>">
<td data-label="E-Lot"><strong class="table-primary-text"><?= $escape($lot['lot_code']) ?></strong><span class="table-secondary-text"><?= $escape($lot['title']) ?></span></td>
<td data-label="Category"><?= $escape($lot['category_name']) ?></td><td data-label="Winning Bid">Rs. <?= number_format((float) $lot['bid_amount'], 2) ?></td>
<td data-label="Lot Status"><span class="badge badge-<?= strtolower($lot['lot_status']) ?>"><?= $escape(ucwords(strtolower(str_replace('_', ' ', $lot['lot_status'])))) ?></span></td><td data-label="Handover"><span class="badge badge-<?= strtolower($lot['handover_status'] ?? 'pending') ?>"><?= $escape(ucfirst(strtolower($lot['handover_status'] ?? 'Not recorded'))) ?></span></td>
<td data-label="Action"><a class="btn-action primary-row-action" href="<?= $escape($basePath) ?>/recycler/awarded-e-lot/<?= (int) $lot['e_lot_id'] ?>">View Details</a></td>
</tr><?php endforeach; ?></tbody></table></div><div class="filtered-empty-state" data-awarded-empty hidden>No awarded E-Lots match these filters.</div><?php endif; ?>
</section></section>
