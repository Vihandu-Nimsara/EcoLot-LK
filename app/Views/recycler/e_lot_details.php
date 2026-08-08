<?php
$lots = [
    'DEMO-LOT-001' => ['title' => 'Demo Open Lot - Laptops from Nugegoda', 'council' => 'Demo Colombo Metro Council', 'category' => 'Demo Consumer Electronics', 'items' => 1, 'quantity' => 14, 'weight' => '91.00 kg', 'deadline' => '2026-07-24 15:37:55', 'location' => 'Nugegoda Collection Centre', 'item' => 'Demo Laptops'],
    'DEMO-LOT-002' => ['title' => 'Demo Open Lot - Phones and Routers', 'council' => 'Demo Colombo Metro Council', 'category' => 'Demo Consumer Electronics', 'items' => 1, 'quantity' => 45, 'weight' => '18.00 kg', 'deadline' => '2026-07-20 15:37:55', 'location' => 'Colombo Collection Centre', 'item' => 'Demo Phones and Routers'],
    'DEMO-FIX-LOT-OPEN-001' => ['title' => 'DEMO-FIX Open Lot - Laptops and Monitors', 'council' => 'DEMO-FIX Recycler Dashboard Council', 'category' => 'DEMO-FIX Recycler Electronics', 'items' => 1, 'quantity' => 12, 'weight' => '84.00 kg', 'deadline' => '2026-07-24 16:03:03', 'location' => 'Council Collection Centre', 'item' => 'Laptops and Monitors'],
    'DEMO-FIX-LOT-OPEN-002' => ['title' => 'DEMO-FIX Open Lot - Mobile Phones and Routers', 'council' => 'DEMO-FIX Recycler Dashboard Council', 'category' => 'DEMO-FIX Recycler Electronics', 'items' => 1, 'quantity' => 50, 'weight' => '26.00 kg', 'deadline' => '2026-07-20 16:03:03', 'location' => 'Council Collection Centre', 'item' => 'Mobile Phones and Routers'],
];
$lotCode = (string) $eLotId;
$lot = $lots[$lotCode] ?? $lots['DEMO-LOT-001'];
?>
<section class="workflow-page">
    <div class="workflow-header">
        <div><h1>E-Lot Details</h1><p>Review the waste composition, handling information and bidding deadline.</p></div>
        <div class="workflow-actions"><a class="secondary-workflow-btn" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/recycler/eligible-e-lots">Back to Eligible E-Lots</a><button class="primary-workflow-btn" type="button" data-recycler-dialog="place-bid" data-elot-code="<?= htmlspecialchars($lotCode) ?>">Place Bid</button></div>
    </div>

    <section class="workflow-card">
        <div class="workflow-section-header"><h2>E-Lot Summary</h2></div>
        <div class="detail-grid">
            <div class="detail-item"><span class="detail-label">Lot Code</span><strong class="detail-value"><?= htmlspecialchars($lotCode) ?></strong></div>
            <div class="detail-item"><span class="detail-label">Lot Title</span><strong class="detail-value"><?= htmlspecialchars($lot['title']) ?></strong></div>
            <div class="detail-item"><span class="detail-label">Status</span><span class="badge badge-open">Open for Bidding</span></div>
            <div class="detail-item"><span class="detail-label">Council</span><strong class="detail-value"><?= htmlspecialchars($lot['council']) ?></strong></div>
            <div class="detail-item"><span class="detail-label">Bidding Deadline</span><strong class="detail-value"><?= htmlspecialchars($lot['deadline']) ?></strong></div>
            <div class="detail-item"><span class="detail-label">Handover Location</span><strong class="detail-value"><?= htmlspecialchars($lot['location']) ?></strong></div>
        </div>
    </section>

    <section class="workflow-card">
        <div class="workflow-section-header"><h2>Waste Summary</h2></div>
        <div class="detail-grid">
            <div class="detail-item"><span class="detail-label">Category</span><strong class="detail-value"><?= htmlspecialchars($lot['category']) ?></strong></div>
            <div class="detail-item"><span class="detail-label">Total Item Types</span><strong class="detail-value"><?= htmlspecialchars((string) $lot['items']) ?></strong></div>
            <div class="detail-item"><span class="detail-label">Total Quantity</span><strong class="detail-value"><?= htmlspecialchars((string) $lot['quantity']) ?></strong></div>
            <div class="detail-item"><span class="detail-label">Total Weight</span><strong class="detail-value"><?= htmlspecialchars($lot['weight']) ?></strong></div>
            <div class="detail-item"><span class="detail-label">Risk Level</span><span class="badge badge-completed">Low</span></div>
            <div class="detail-item"><span class="detail-label">Special Handling</span><strong class="detail-value">Not required</strong></div>
        </div>
    </section>

    <section class="workflow-card">
        <div class="workflow-section-header"><h2>Item Breakdown</h2></div>
        <div class="workflow-table-wrapper"><table class="workflow-table"><thead><tr><th>Item</th><th>Category</th><th>Quantity</th><th>Condition</th><th>Notes</th></tr></thead><tbody><tr><td><?= htmlspecialchars($lot['item']) ?></td><td><?= htmlspecialchars($lot['category']) ?></td><td><?= htmlspecialchars((string) $lot['quantity']) ?></td><td>Used</td><td>Collected and ready for council handover.</td></tr></tbody></table></div>
    </section>

    <section class="workflow-card">
        <div class="workflow-section-header"><h2>Recycler Eligibility</h2><p>This E-Lot matches your approved licence and <?= htmlspecialchars($lot['category']) ?> capability.</p></div>
        <span class="badge badge-completed">Eligible</span>
    </section>

</section>
