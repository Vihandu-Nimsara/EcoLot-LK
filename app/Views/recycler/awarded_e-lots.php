<<<<<<< ours
<?php $awardedLots = [
 ['code'=>'DEMO-FIX-LOT-AWARDED-001','title'=>'Awarded Lot - Printers and Circuit Boards','category'=>'Demo Consumer Electronics','qty'=>9,'weight'=>'118.00 kg','amount'=>'132,000.00','status'=>'AWARDED','class'=>'badge-awarded'],
 ['code'=>'DEMO-FIX-LOT-PROCESSING-001','title'=>'Processing Lot - Lithium Batteries','category'=>'Demo Battery and Circuit Boards','qty'=>16,'weight'=>'64.00 kg','amount'=>'88,000.00','status'=>'PROCESSING','class'=>'badge-processing'],
 ['code'=>'DEMO-FIX-LOT-COMPLETED-001','title'=>'Completed Lot - Routers','category'=>'Demo Consumer Electronics','qty'=>24,'weight'=>'18.00 kg','amount'=>'29,500.00','status'=>'COMPLETED','class'=>'badge-completed'],
 ['code'=>'DEMO-LOT-003','title'=>'Demo Awarded Lot - Printers and Monitors','category'=>'Demo Consumer Electronics','qty'=>8,'weight'=>'110.00 kg','amount'=>'126,000.00','status'=>'AWARDED','class'=>'badge-awarded'],
 ['code'=>'DEMO-LOT-004','title'=>'Demo Processing Lot - Lithium Batteries','category'=>'Demo Battery and Circuit Boards','qty'=>18,'weight'=>'62.00 kg','amount'=>'99,000.00','status'=>'PROCESSING','class'=>'badge-processing'],
]; ?>
<section class="awarded-e-lots-page"><section class="awarded-e-lot-card"><div class="awarded-e-lot-header"><div><h2>My Awarded E-Lots</h2><p>Track handover and processing progress for E-Lots awarded to your company.</p></div></div>
<form class="light-filter" data-client-filter data-rows="[data-awarded-row]" data-empty="[data-awarded-empty]" data-result="[data-awarded-result]"><div class="quick-filters" role="group" aria-label="Filter awarded E-Lots by lifecycle status"><button class="quick-filter" type="button" aria-pressed="true" data-filter-name="status" data-filter-value="">All</button><button class="quick-filter" type="button" aria-pressed="false" data-filter-name="status" data-filter-value="AWARDED">Awarded</button><button class="quick-filter" type="button" aria-pressed="false" data-filter-name="status" data-filter-value="PROCESSING">Processing</button><button class="quick-filter" type="button" aria-pressed="false" data-filter-name="status" data-filter-value="COMPLETED">Completed</button></div><div class="light-filter-controls"><div class="filter-field"><label for="awarded-search">Search E-Lots</label><input id="awarded-search" name="search" type="search" placeholder="Lot code or category"></div></div></form><p class="filter-result" data-awarded-result role="status" aria-live="polite"></p>
<div class="table-responsive"><table class="awarded-table"><thead><tr><th>Lot Code</th><th>Title</th><th>Category</th><th>Qty</th><th>Weight</th><th>Winning Bid</th><th>Status</th><th>Action</th></tr></thead><tbody><?php foreach($awardedLots as $lot): ?><tr data-awarded-row data-search="<?= htmlspecialchars($lot['code'].' '.$lot['category']) ?>" data-status="<?= $lot['status'] ?>"><td data-label="Lot Code"><?= htmlspecialchars($lot['code']) ?></td><td data-label="Title"><?= htmlspecialchars($lot['title']) ?></td><td data-label="Category"><?= htmlspecialchars($lot['category']) ?></td><td data-label="Quantity"><?= $lot['qty'] ?></td><td data-label="Weight"><?= $lot['weight'] ?></td><td data-label="Winning Bid">Rs. <?= $lot['amount'] ?></td><td data-label="Status"><span class="badge <?= $lot['class'] ?>"><?= ucwords(strtolower($lot['status'])) ?></span></td><td data-label="Action"><a class="btn-action" href="<?= htmlspecialchars($basePath) ?>/recycler/awarded-e-lot/<?= rawurlencode($lot['code']) ?>">View Details</a></td></tr><?php endforeach; ?></tbody></table></div><div class="filtered-empty-state" data-awarded-empty hidden>No awarded E-Lots match the selected filters.</div></section></section>
=======
<?php
$awardedLots = [
    ['code'=>'DEMO-FIX-LOT-AWARDED-001','title'=>'Awarded Lot - Printers and Circuit Boards','category'=>'Demo Consumer Electronics','qty'=>9,'weight'=>'118.00 kg','amount'=>'132,000.00','status'=>'AWARDED','class'=>'badge-awarded'],
    ['code'=>'DEMO-FIX-LOT-PROCESSING-001','title'=>'Processing Lot - Lithium Batteries','category'=>'Demo Battery and Circuit Boards','qty'=>16,'weight'=>'64.00 kg','amount'=>'88,000.00','status'=>'PROCESSING','class'=>'badge-processing'],
    ['code'=>'DEMO-FIX-LOT-COMPLETED-001','title'=>'Completed Lot - Routers','category'=>'Demo Consumer Electronics','qty'=>24,'weight'=>'18.00 kg','amount'=>'29,500.00','status'=>'COMPLETED','class'=>'badge-completed'],
    ['code'=>'DEMO-LOT-003','title'=>'Demo Awarded Lot - Printers and Monitors','category'=>'Demo Consumer Electronics','qty'=>8,'weight'=>'110.00 kg','amount'=>'126,000.00','status'=>'AWARDED','class'=>'badge-awarded'],
    ['code'=>'DEMO-LOT-004','title'=>'Demo Processing Lot - Lithium Batteries','category'=>'Demo Battery and Circuit Boards','qty'=>18,'weight'=>'62.00 kg','amount'=>'99,000.00','status'=>'PROCESSING','class'=>'badge-processing'],
];
?>
<section class="awarded-e-lots-page">
    <section class="awarded-e-lot-card">
        <div class="awarded-e-lot-header">
            <div>
                <h2>My Awarded E-Lots</h2>
                <p>Track handover and processing progress for E-Lots awarded to your company.</p>
            </div>
        </div>

        <form class="light-filter" data-client-filter data-rows="[data-awarded-row]" data-empty="[data-awarded-empty]" data-result="[data-awarded-result]">
            <div class="quick-filters" role="group" aria-label="Filter awarded E-Lots by lifecycle status">
                <button class="quick-filter" type="button" aria-pressed="true" data-filter-name="status" data-filter-value="">All</button>
                <button class="quick-filter" type="button" aria-pressed="false" data-filter-name="status" data-filter-value="AWARDED">Awarded</button>
                <button class="quick-filter" type="button" aria-pressed="false" data-filter-name="status" data-filter-value="PROCESSING">Processing</button>
                <button class="quick-filter" type="button" aria-pressed="false" data-filter-name="status" data-filter-value="COMPLETED">Completed</button>
            </div>
            <div class="light-filter-controls">
                <div class="filter-field">
                    <label for="awarded-search">Search E-Lots</label>
                    <input id="awarded-search" name="search" type="search" placeholder="Code, title or category">
                </div>
            </div>
        </form>

        <p class="filter-result" data-awarded-result role="status" aria-live="polite"></p>

        <?php if ($awardedLots === []): ?>
            <div class="empty-state">No awarded E-Lots are available.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="awarded-table">
                    <thead>
                        <tr><th>E-Lot</th><th>Category</th><th>Waste</th><th>Winning Bid</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($awardedLots as $lot): ?>
                        <tr data-awarded-row data-search="<?= htmlspecialchars($lot['code'].' '.$lot['title'].' '.$lot['category']) ?>" data-status="<?= $lot['status'] ?>">
                            <td data-label="E-Lot"><strong class="table-primary-text"><?= htmlspecialchars($lot['code']) ?></strong><span class="table-secondary-text"><?= htmlspecialchars($lot['title']) ?></span></td>
                            <td data-label="Category"><?= htmlspecialchars($lot['category']) ?></td>
                            <td data-label="Waste"><strong><?= $lot['qty'] ?> items</strong><span class="table-secondary-text"><?= $lot['weight'] ?></span></td>
                            <td data-label="Winning Bid">Rs. <?= $lot['amount'] ?></td>
                            <td data-label="Status"><span class="badge <?= $lot['class'] ?>"><?= ucwords(strtolower($lot['status'])) ?></span></td>
                            <td data-label="Action"><a class="btn-action primary-row-action" href="<?= htmlspecialchars($basePath) ?>/recycler/awarded-e-lot/<?= rawurlencode($lot['code']) ?>">View Details</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="filtered-empty-state" data-awarded-empty hidden>No awarded E-Lots match the selected filters.</div>
        <?php endif; ?>
    </section>
</section>
>>>>>>> theirs
