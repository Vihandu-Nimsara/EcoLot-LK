<?php
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$statuses = ['SUBMITTED' => 'Submitted', 'WINNING' => 'Won', 'REJECTED' => 'Lost', 'WITHDRAWN' => 'Withdrawn'];
?>
<section class="workflow-page recycler-reports-page">
    <div class="workflow-header"><div><h1>Reports &amp; Activity</h1><p>Review your bid activity and Municipal Officer-recorded awards and handovers.</p></div></div>
    <div class="report-grid">
        <?php foreach (['Total Bids' => $bidCount, 'Submitted Bids' => $submittedCount,
            'Won Bids' => $wonCount, 'Lost Bids' => $rejectedCount, 'Awarded E-Lots' => $awardedCount,
            'Handed Over' => $handedCount, 'Withdrawn Bids' => $withdrawnCount, 'Awaiting Handover' => $awaitingCount] as $label => $count): ?>
        <article class="summary-card"><span><?= $escape($label) ?></span><strong><?= (int) $count ?></strong></article>
        <?php endforeach; ?>
    </div>
    <section class="workflow-card">
        <div class="workflow-section-header"><h2>Recent Bid Activity</h2><p>Your five most recently submitted bid records, with their current amounts and statuses. Revision and withdrawal history is not recorded here.</p></div>
        <?php if (!$recentBids): ?><div class="empty-state">You have not placed any bids.</div><?php else: ?>
        <div class="workflow-table-wrapper"><table class="workflow-table"><thead><tr><th scope="col">Submitted</th><th scope="col">E-Lot</th><th scope="col">Current Bid</th><th scope="col">Status</th></tr></thead><tbody>
        <?php foreach ($recentBids as $bid): ?><tr>
            <td><?= $escape($bid['submitted_at']) ?></td>
            <td><a href="<?= $escape($basePath) ?>/recycler/e-lot/<?= (int) $bid['e_lot_id'] ?>"><?= $escape($bid['lot_code']) ?></a><span class="table-secondary-text"><?= $escape($bid['title']) ?></span></td>
            <td>Rs. <?= number_format((float) $bid['bid_amount'], 2) ?></td>
            <td><span class="badge badge-<?= strtolower($bid['bid_status']) ?>"><?= $statuses[$bid['bid_status']] ?></span></td>
        </tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
        <div class="form-actions"><a class="secondary-workflow-btn" href="<?= $escape($basePath) ?>/recycler/my-bids">My Bid History</a></div>
    </section>
    <section class="workflow-card">
        <div class="workflow-section-header"><h2>Recent E-Lot Activity</h2><p>Your five most recent awards, ordered by award date, with current handover information. Awaiting handover counts include recorded pending and scheduled handovers.</p></div>
        <?php if (!$recentAwards): ?><div class="empty-state">You have no awarded E-Lots.</div><?php else: ?>
        <div class="workflow-table-wrapper"><table class="workflow-table"><thead><tr><th scope="col">Award Date</th><th scope="col">E-Lot</th><th scope="col">Handover Date</th><th scope="col">Handover Status</th></tr></thead><tbody>
        <?php foreach ($recentAwards as $award): ?><tr>
            <td><?= $escape($award['reviewed_at'] ?? 'Not recorded') ?></td>
            <td><a href="<?= $escape($basePath) ?>/recycler/awarded-e-lot/<?= (int) $award['e_lot_id'] ?>"><?= $escape($award['lot_code']) ?></a><span class="table-secondary-text"><?= $escape($award['title']) ?></span></td>
            <td><?= $escape($award['handover_date'] ?? 'Not recorded') ?></td>
            <td><span class="badge badge-<?= strtolower($award['handover_status'] ?? 'pending') ?>"><?= $escape(ucfirst(strtolower($award['handover_status'] ?? 'Not recorded'))) ?></span></td>
        </tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
        <div class="form-actions"><a class="secondary-workflow-btn" href="<?= $escape($basePath) ?>/recycler/awarded-e-lots">My Awarded E-Lots</a></div>
    </section>
</section>
