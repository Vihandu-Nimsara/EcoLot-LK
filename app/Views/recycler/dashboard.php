<?php $escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?>
<section class="dashboard-page">
    <div class="stats-grid">
        <?php foreach ([
            ['Eligible Open E-Lots', $eligibleCount, 'eligible-e-lots', 'eligible_e-lot', '♻'],
            ['My Bids', $bidCount, 'my-bids', 'bids', '🙋‍♂️'],
            ['Awarded E-Lots', $awardedCount, 'awarded-e-lots', 'awarded', '🏆'],
            ['Awaiting Handover', $awaitingCount, 'awarded-e-lots', 'awaiting', '⏳'],
            ['Handed Over', $handedCount, 'awarded-e-lots', 'handed', '📦'],
            ['Submitted Bids', $submittedCount, 'my-bids', 'processing', '📨'],
            ['Withdrawn Bids', $withdrawnCount, 'my-bids', 'completed', '↩'],
        ] as [$label, $count, $path, $iconClass, $icon]): ?>
            <a class="stat-card stat-card-link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/recycler/<?= $path ?>">
                <div class="stat-icon <?= $iconClass ?>" aria-hidden="true"><?= $icon ?></div>
                <div class="stat-info"><span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span><h2><?= (int) $count ?></h2></div>
            </a>
        <?php endforeach; ?>
    </div>
    <section class="compliance-summary" aria-label="Compliance summary">
        <div><span>Verification</span><strong><?= $escape(ucfirst(strtolower($compliance['verification_status'] ?? 'Not recorded'))) ?></strong></div>
        <div><span>CEA Licence Status</span><strong><?= !empty($compliance['valid_license_expiry']) ? 'Valid until ' . $escape($compliance['valid_license_expiry']) : 'No current valid licence' ?></strong></div>
        <div><span>Approved Capabilities</span><strong><?= count(array_filter($capabilities, static fn (array $row): bool => $row['capability_status'] === 'APPROVED')) ?></strong></div>
        <a href="<?= $escape($basePath) ?>/recycler/profile">View My Profile</a>
    </section>
    <section class="schedule-section"><div class="capability-card">
        <div class="section-header"><div><h2>Waste-handling Capabilities</h2><p>EcoLot categories and capability statuses recorded for your recycler profile.</p></div></div>
        <?php if (!$capabilities): ?><div class="empty-state">No handling capabilities are recorded.</div><?php else: ?>
        <div class="capability-table-wrapper"><table class="capability-table">
            <thead><tr><th scope="col">Category</th><th scope="col">Status</th></tr></thead>
            <tbody><?php foreach ($capabilities as $capability): ?><tr>
                <td><?= $escape($capability['category_name']) ?></td>
                <td><span class="status <?= $capability['capability_status'] === 'APPROVED' ? 'op' : 'non-op' ?>"><?= $escape(ucfirst(strtolower($capability['capability_status']))) ?></span></td>
            </tr><?php endforeach; ?></tbody>
        </table></div><?php endif; ?>
    </div></section>
</section>
