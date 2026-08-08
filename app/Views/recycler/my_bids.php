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
            <div class="bid-filter"><label for="my-bids-filter">Bid period</label><select id="my-bids-filter"><option>All Bids</option><option>July 2026 Bids</option></select></div>
        </div>
        <p class="page-notice" data-page-notice tabindex="-1" hidden></p>
        <?php if ($bids === []): ?>
            <div class="empty-state">No bids are available.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="bids-table">
                    <thead><tr><th>Bid ID</th><th>E-Lot Code</th><th>E-Lot Title</th><th>Council</th><th>Category</th><th>Bid Amount</th><th>Bid Status</th><th>E-Lot Status</th><th>Bidding Period</th><th>Submitted At</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($bids as $bid): ?>
                        <tr>
                            <td>#<?= htmlspecialchars((string) $bid['id']) ?></td><td><?= htmlspecialchars($bid['code']) ?></td><td><?= htmlspecialchars($bid['title']) ?></td><td><?= htmlspecialchars($bid['council']) ?></td><td><?= htmlspecialchars($bid['category']) ?></td><td class="money-cell">Rs. <?= htmlspecialchars($bid['amount']) ?></td>
                            <td><span class="badge <?= htmlspecialchars($bid['bid_class']) ?>"><?= htmlspecialchars($bid['bid_status']) ?></span></td><td><span class="badge <?= htmlspecialchars($bid['lot_class']) ?>"><?= htmlspecialchars($bid['lot_status']) ?></span></td><td class="period-cell"><?= htmlspecialchars($bid['period']) ?></td><td class="date-cell"><?= htmlspecialchars($bid['submitted']) ?></td>
                            <td><div class="row-actions"><button class="btn-action" type="button" data-recycler-dialog="view-bid" data-elot-code="<?= htmlspecialchars($bid['code']) ?>" data-bid-amount="<?= htmlspecialchars($bid['amount']) ?>" data-bid-status="<?= htmlspecialchars($bid['bid_status']) ?>" data-submitted="<?= htmlspecialchars($bid['submitted']) ?>">View Details</button><?php if ($bid['editable']): ?><button class="btn-action" type="button" data-recycler-dialog="edit-bid" data-elot-code="<?= htmlspecialchars($bid['code']) ?>" data-bid-amount="<?= htmlspecialchars($bid['amount']) ?>">Edit Bid</button><button class="btn-action" type="button" data-recycler-dialog="withdraw-bid" data-elot-code="<?= htmlspecialchars($bid['code']) ?>">Withdraw</button><?php endif; ?></div></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</section>
