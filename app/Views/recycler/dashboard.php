<section class="dashboard-page"><div class="stats-grid">
<?php foreach (['Eligible Open E-Lots' => [$eligibleCount, 'eligible-e-lots'], 'My Bids' => [$bidCount, 'my-bids'], 'Won Bids' => [$wonCount, 'awarded-e-lots']] as $label => [$count, $path]): ?>
<article class="stat-card"><div class="stat-info"><span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span><h2><?= (int) $count ?></h2><a class="secondary-workflow-btn" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/recycler/<?= $path ?>">View</a></div></article>
<?php endforeach; ?>
</div><section class="compliance-summary"><p>Eligible E-Lots reflect your current verification, licence and category permissions. The Municipal Officer selects winning bids and records handovers.</p></section></section>
