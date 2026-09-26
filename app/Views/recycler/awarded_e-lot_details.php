<?php require __DIR__ . '/e_lot_details.php'; ?>
<section class="workflow-page"><section class="workflow-card"><div class="workflow-section-header"><h2>Handover</h2><p>Physical handover is recorded by the Municipal Officer.</p></div>
<div class="detail-grid"><div class="detail-item"><span class="detail-label">Handover Status</span><span class="badge badge-<?= strtolower($handover['handover_status'] ?? 'pending') ?>"><?= $escape(ucfirst(strtolower($handover['handover_status'] ?? 'Not recorded'))) ?></span></div>
<div class="detail-item"><span class="detail-label">Handover Date</span><strong><?= $escape($handover['handover_date'] ?? 'Not recorded') ?></strong></div><div class="detail-item"><span class="detail-label">Remarks</span><strong><?= $escape($handover['remarks'] ?? 'Not recorded') ?></strong></div></div>

</section></section>
