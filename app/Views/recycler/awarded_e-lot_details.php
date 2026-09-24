<?php require __DIR__ . '/e_lot_details.php'; ?>
<section class="workflow-page"><section class="workflow-card"><h2>Handover Information</h2>
<p>The Municipal Officer records handover information.</p>
<div class="detail-grid"><div class="detail-item"><span class="detail-label">Handover Status</span><strong><?= $escape($handover['handover_status'] ?? 'Not recorded') ?></strong></div>
<div class="detail-item"><span class="detail-label">Handover Date</span><strong><?= $escape($handover['handover_date'] ?? 'Not recorded') ?></strong></div></div>
<a href="<?= $escape($basePath) ?>/recycler/awarded-e-lots">Back to Awarded E-Lots</a>
</section></section>
