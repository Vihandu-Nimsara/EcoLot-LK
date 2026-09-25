<section class="dashboard-page">
    <div class="stats-grid">
        <?php foreach ([
            ['Eligible Open E-Lots', $eligibleCount, 'eligible-e-lots', 'eligible_e-lot', '♻'],
            ['My Bids', $bidCount, 'my-bids', 'bids', '🙋‍♂️'],
            ['Awarded E-Lots', $wonCount, 'awarded-e-lots', 'awarded', '🏆'],
        ] as [$label, $count, $path, $iconClass, $icon]): ?>
            <a class="stat-card stat-card-link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/recycler/<?= $path ?>">
                <div class="stat-icon <?= $iconClass ?>" aria-hidden="true"><?= $icon ?></div>
                <div class="stat-info"><span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span><h2><?= (int) $count ?></h2></div>
            </a>
        <?php endforeach; ?>
    </div>
    <section class="compliance-summary" aria-label="Eligibility information">
        <p>Eligible E-Lots reflect your current verification, licence and category permissions. The Municipal Officer selects winning bids and records handovers.</p>
        <a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/recycler/profile">View My Profile</a>
    </section>
</section>
