<?php
$lotCode = (string) $eLotId;
$isProcessing = str_contains($lotCode, 'PROCESSING') || $lotCode === 'DEMO-LOT-004';
$isCompleted = str_contains($lotCode, 'COMPLETED');
$status = $isCompleted ? 'COMPLETED' : ($isProcessing ? 'PROCESSING' : 'AWARDED');
$statusClass = $isCompleted ? 'badge-completed' : ($isProcessing ? 'badge-processing' : 'badge-awarded');
?>
<section class="workflow-page">
    <div class="workflow-header">
        <div><h1>Awarded E-Lot Details</h1><p>Review award, handover and recycling progress for this E-Lot.</p></div>
        <div class="workflow-actions"><a class="secondary-workflow-btn" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/recycler/awarded-e-lots">Back to Awarded E-Lots</a></div>
    </div>

    <p class="page-notice" data-page-notice tabindex="-1" hidden></p>

    <section class="workflow-card">
        <div class="workflow-section-header"><h2>Lot Information</h2></div>
        <div class="detail-grid">
            <div class="detail-item"><span class="detail-label">Lot Code</span><strong class="detail-value"><?= htmlspecialchars($lotCode) ?></strong></div>
            <div class="detail-item"><span class="detail-label">Category</span><strong class="detail-value"><?= $isProcessing ? 'DEMO-FIX Recycler Batteries' : 'DEMO-FIX Recycler Electronics' ?></strong></div>
            <div class="detail-item"><span class="detail-label">Current Status</span><span class="badge <?= $statusClass ?>"><?= htmlspecialchars(ucwords(strtolower($status))) ?></span></div>
            <div class="detail-item"><span class="detail-label">Quantity / Weight</span><strong class="detail-value"><?= $isProcessing ? '16 items / 64.00 kg' : '9 items / 118.00 kg' ?></strong></div>
            <div class="detail-item"><span class="detail-label">Winning Bid</span><strong class="detail-value"><?= $isProcessing ? 'Rs. 88,000.00' : 'Rs. 132,000.00' ?></strong></div>
            <div class="detail-item"><span class="detail-label">Award Date</span><strong class="detail-value">2026-07-10</strong></div>
        </div>
    </section>

    <section class="workflow-card">
        <div class="workflow-section-header"><h2>Lifecycle</h2><p>Frontend representation of the operational stages required after an award.</p></div>
        <div class="lifecycle" aria-label="Awarded E-Lot lifecycle">
            <div class="lifecycle-step complete">1. Awarded</div>
            <div class="lifecycle-step <?= ($isProcessing || $isCompleted) ? 'complete' : 'current' ?>">2. Handed Over</div>
            <div class="lifecycle-step <?= $isCompleted ? 'complete' : ($isProcessing ? 'current' : '') ?>">3. Processing</div>
            <div class="lifecycle-step <?= $isCompleted ? 'current' : '' ?>">4. Completed</div>
        </div>
    </section>

    <section class="workflow-card">
        <div class="workflow-section-header"><h2>Handover</h2></div>
        <div class="detail-grid">
            <div class="detail-item"><span class="detail-label">Handover Status</span><strong class="detail-value"><?= ($isProcessing || $isCompleted) ? 'Handed Over' : 'Awaiting Confirmation' ?></strong></div>
            <div class="detail-item"><span class="detail-label">Handover Date</span><strong class="detail-value"><?= ($isProcessing || $isCompleted) ? '2026-07-12' : 'Not recorded' ?></strong></div>
            <div class="detail-item"><span class="detail-label">Remarks</span><strong class="detail-value"><?= ($isProcessing || $isCompleted) ? 'Received from council collection centre.' : 'Awaiting recycler confirmation.' ?></strong></div>
        </div>
    </section>

    <section class="workflow-card">
        <div class="workflow-section-header"><h2>Processing</h2></div>
        <div class="detail-grid"><div class="detail-item"><span class="detail-label">Processing Status</span><strong class="detail-value"><?= $isCompleted ? 'Completed' : ($isProcessing ? 'In Progress' : 'Not Started') ?></strong></div></div>
        <?php if (!$isCompleted): ?>
            <div class="form-actions">
                <button class="primary-workflow-btn" type="button" data-recycler-dialog="<?= $isProcessing ? 'complete-processing' : 'handover-update' ?>" data-elot-code="<?= htmlspecialchars($lotCode) ?>"><?= $isProcessing ? 'Mark Completed' : 'Confirm / Update Handover' ?></button>
                <?php if (!$isProcessing): ?><button class="secondary-workflow-btn" type="button" data-recycler-dialog="start-processing" data-elot-code="<?= htmlspecialchars($lotCode) ?>">Start Processing</button><?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</section>
