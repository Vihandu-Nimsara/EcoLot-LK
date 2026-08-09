<?php
$bids = [
    ['id' => 8, 'code' => 'DEMO-FIX-LOT-OPEN-001', 'title' => 'DEMO-FIX Open Lot - Laptops and Monitors', 'council' => 'DEMO-FIX Recycler Dashboard Council', 'category' => 'DEMO-FIX Recycler Electronics', 'amount' => '94,500.00', 'bid_status' => 'Submitted', 'bid_class' => 'badge-submitted', 'lot_status' => 'Open for Bidding', 'lot_class' => 'badge-open', 'period' => '2026-07-09 → 2026-07-24', 'submitted' => '2026-07-10', 'editable' => true],
    ['id' => 9, 'code' => 'DEMO-FIX-LOT-OPEN-002', 'title' => 'DEMO-FIX Open Lot - Mobile Phones and Routers', 'council' => 'DEMO-FIX Recycler Dashboard Council', 'category' => 'DEMO-FIX Recycler Electronics', 'amount' => '38,200.00', 'bid_status' => 'Submitted', 'bid_class' => 'badge-submitted', 'lot_status' => 'Open for Bidding', 'lot_class' => 'badge-open', 'period' => '2026-07-08 → 2026-07-20', 'submitted' => '2026-07-10', 'editable' => true],
    ['id' => 10, 'code' => 'DEMO-FIX-LOT-AWARDED-001', 'title' => 'DEMO-FIX Awarded Lot - Printers and Circuit Boards', 'council' => 'DEMO-FIX Recycler Dashboard Council', 'category' => 'DEMO-FIX Recycler Electronics', 'amount' => '132,000.00', 'bid_status' => 'Won', 'bid_class' => 'badge-winning', 'lot_status' => 'Awarded', 'lot_class' => 'badge-awarded', 'period' => '2026-06-20 → 2026-06-28', 'submitted' => '2026-07-10', 'editable' => false],
    ['id' => 11, 'code' => 'DEMO-FIX-LOT-PROCESSING-001', 'title' => 'DEMO-FIX Processing Lot - Lithium Batteries', 'council' => 'DEMO-FIX Recycler Dashboard Council', 'category' => 'DEMO-FIX Recycler Batteries', 'amount' => '88,000.00', 'bid_status' => 'Won', 'bid_class' => 'badge-winning', 'lot_status' => 'Processing', 'lot_class' => 'badge-processing', 'period' => '2026-06-15 → 2026-06-22', 'submitted' => '2026-07-10', 'editable' => false],
    ['id' => 12, 'code' => 'DEMO-FIX-LOT-COMPLETED-001', 'title' => 'DEMO-FIX Completed Lot - Routers', 'council' => 'DEMO-FIX Recycler Dashboard Council', 'category' => 'DEMO-FIX Recycler Electronics', 'amount' => '29,500.00', 'bid_status' => 'Won', 'bid_class' => 'badge-winning', 'lot_status' => 'Completed', 'lot_class' => 'badge-completed', 'period' => '2026-05-31 → 2026-06-08', 'submitted' => '2026-07-10', 'editable' => false],
    ['id' => 3, 'code' => 'DEMO-LOT-002', 'title' => 'Demo Open Lot - Phones and Routers', 'council' => 'Demo Colombo Metro Council', 'category' => 'Demo Consumer Electronics', 'amount' => '48,200.00', 'bid_status' => 'Submitted', 'bid_class' => 'badge-submitted', 'lot_status' => 'Open for Bidding', 'lot_class' => 'badge-open', 'period' => '2026-07-08 → 2026-07-20', 'submitted' => '2026-07-10', 'editable' => true],
    ['id' => 4, 'code' => 'DEMO-LOT-003', 'title' => 'Demo Awarded Lot - Printers and Monitors', 'council' => 'Demo Colombo Metro Council', 'category' => 'Demo Consumer Electronics', 'amount' => '126,000.00', 'bid_status' => 'Won', 'bid_class' => 'badge-winning', 'lot_status' => 'Awarded', 'lot_class' => 'badge-awarded', 'period' => '2026-06-20 → 2026-06-28', 'submitted' => '2026-07-10', 'editable' => false],
    ['id' => 6, 'code' => 'DEMO-LOT-004', 'title' => 'Demo Processing Lot - Lithium Batteries', 'council' => 'Demo Colombo Metro Council', 'category' => 'Demo Battery and Circuit Boards', 'amount' => '99,000.00', 'bid_status' => 'Won', 'bid_class' => 'badge-winning', 'lot_status' => 'Processing', 'lot_class' => 'badge-processing', 'period' => '2026-06-14 → 2026-06-22', 'submitted' => '2026-07-10', 'editable' => false],
];
?>
<section class="my-bids-page">
    <section class="bid-card">
        <div class="bid-header">
            <div><h2>My Bids</h2><p>Track every bid submitted by your company.</p></div>
        </div>
        <form class="light-filter" data-client-filter data-rows="[data-bid-row]" data-empty="[data-bids-filter-empty]" data-result="[data-bids-filter-result]"><div class="quick-filters" role="group" aria-label="Filter bids by status"><button class="quick-filter" type="button" aria-pressed="true" data-filter-name="status" data-filter-value="">All</button><button class="quick-filter" type="button" aria-pressed="false" data-filter-name="status" data-filter-value="Submitted">Submitted</button><button class="quick-filter" type="button" aria-pressed="false" data-filter-name="status" data-filter-value="Won">Won</button><button class="quick-filter" type="button" aria-pressed="false" data-filter-name="status" data-filter-value="Lost">Lost</button><button class="quick-filter" type="button" aria-pressed="false" data-filter-name="status" data-filter-value="Withdrawn">Withdrawn</button></div><div class="light-filter-controls"><div class="filter-field"><label for="my-bids-search">Search bids</label><input id="my-bids-search" name="search" type="search" placeholder="E-Lot code"></div></div></form>
        <p class="filter-result" data-bids-filter-result role="status" aria-live="polite"></p>
        <p class="page-notice" data-page-notice tabindex="-1" hidden></p>
        <?php if ($bids === []): ?>
            <div class="empty-state">No bids are available.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="bids-table">
                    <thead><tr><th>Bid ID</th><th>E-Lot Code</th><th>E-Lot Title</th><th>Council</th><th>Category</th><th>Bid Amount</th><th>Bid Status</th><th>E-Lot Status</th><th>Bidding Period</th><th>Submitted At</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($bids as $bid): ?>
                        <tr data-bid-row data-search="<?= htmlspecialchars($bid['code']) ?>" data-status="<?= htmlspecialchars($bid['bid_status']) ?>">
                            <td data-label="Bid ID">#<?= htmlspecialchars((string) $bid['id']) ?></td><td data-label="Lot Code"><?= htmlspecialchars($bid['code']) ?></td><td data-label="Title"><?= htmlspecialchars($bid['title']) ?></td><td data-label="Council"><?= htmlspecialchars($bid['council']) ?></td><td data-label="Category"><?= htmlspecialchars($bid['category']) ?></td><td data-label="Amount" class="money-cell">Rs. <?= htmlspecialchars($bid['amount']) ?></td>
                            <td data-label="Bid Status"><span class="badge <?= htmlspecialchars($bid['bid_class']) ?>"><?= htmlspecialchars($bid['bid_status']) ?></span></td><td data-label="E-Lot Status"><span class="badge <?= htmlspecialchars($bid['lot_class']) ?>"><?= htmlspecialchars($bid['lot_status']) ?></span></td><td data-label="Bidding Period" class="period-cell"><?= htmlspecialchars($bid['period']) ?></td><td data-label="Submitted" class="date-cell"><?= htmlspecialchars($bid['submitted']) ?></td>
                            <td data-label="Actions"><div class="row-actions"><button class="btn-action" type="button" data-recycler-dialog="view-bid" data-elot-code="<?= htmlspecialchars($bid['code']) ?>" data-bid-amount="<?= htmlspecialchars($bid['amount']) ?>" data-bid-status="<?= htmlspecialchars($bid['bid_status']) ?>" data-submitted="<?= htmlspecialchars($bid['submitted']) ?>" data-deadline="<?= htmlspecialchars(explode(' → ', $bid['period'])[1] ?? '') ?>" data-remarks="Collection and compliant processing included.">View Bid</button><?php if ($bid['editable']): ?><button class="btn-action" type="button" data-recycler-dialog="edit-bid" data-elot-code="<?= htmlspecialchars($bid['code']) ?>" data-bid-amount="<?= htmlspecialchars($bid['amount']) ?>" data-remarks="Collection and compliant processing included.">Edit Bid</button><button class="btn-action" type="button" data-recycler-dialog="withdraw-bid" data-elot-code="<?= htmlspecialchars($bid['code']) ?>">Withdraw Bid</button><?php endif; ?></div></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="filtered-empty-state" data-bids-filter-empty hidden>No bids match the selected filters.</div>
        <?php endif; ?>
    </section>
</section>
