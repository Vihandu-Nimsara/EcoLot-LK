
<?php
$escape = static fn ($value): string =>
    htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$statusClass = static fn (string $status): string => match ($status) {
    'VERIFIED', 'WINNING', 'COMPLETED' => 'positive',
    'SUBMITTED', 'SCHEDULED' => 'info',
    'PENDING' => 'warning',
    'REJECTED', 'REVOKED', 'EXPIRED', 'SUSPENDED' => 'negative',
    default => 'neutral',
};
$renderDate = static function ($value) use ($escape): string {
    if (!$value) return '<span class="date-missing">Not recorded</span>';
    try {
        $date = new DateTimeImmutable((string) $value, new DateTimeZone('Asia/Colombo'));
        return '<time datetime="' . $escape($date->format('c')) . '" title="' . $escape($value) . '"><span>'
            . $escape($date->format('Y-m-d')) . '</span><span class="time-part">' . $escape($date->format('H:i')) . '</span></time>';
    } catch (Exception $error) {
        return '<span>' . $escape($value) . '</span>';
    }
};
// Compliance information
$verificationStatus = strtoupper(
    (string) ($compliance['verification_status'] ?? '')
);

$validLicenseExpiry = $compliance['valid_license_expiry'] ?? null;

$approvedCapabilities = count(array_filter(
    $capabilities,
    static fn (array $row): bool =>
        $row['capability_status'] === 'APPROVED'
));

$approvedActivities = count(array_filter(
    $activities,
    static fn (array $row): bool =>
        $row['activity_status'] === 'APPROVED'
));

// Dashboard alerts
$alerts = [];

if ($verificationStatus !== 'VERIFIED') {
    $alerts[] = match ($verificationStatus) {
        'REJECTED' => 'Recycler verification was rejected. Review your compliance information.',
        'SUSPENDED' => 'Recycler verification is suspended.',
        'PENDING' => 'Recycler verification is awaiting review.',
        default => 'Recycler verification is not recorded.',
    };
}

if (empty($validLicenseExpiry)) {
    $alerts[] = 'No currently valid CEA licence is recorded.';
} else {
    $expiryTimestamp = (new DateTimeImmutable((string) $validLicenseExpiry, new DateTimeZone('Asia/Colombo')))->getTimestamp();
    $todayTimestamp = (new DateTimeImmutable('today', new DateTimeZone('Asia/Colombo')))->getTimestamp();

    if (
        $expiryTimestamp !== false &&
        $todayTimestamp !== false
    ) {
        $daysRemaining = (int) floor(
            ($expiryTimestamp - $todayTimestamp) / 86400
        );

        if ($daysRemaining < 0) {
            $alerts[] = 'Your CEA licence has expired.';
        } elseif ($daysRemaining <= 30) {
            $alerts[] = 'Your CEA licence expires within 30 days.';
        }
    }
}

if ($awaitingCount > 0) {
    $alerts[] = $awaitingCount .
        ' awarded E-Lot(s) are awaiting handover.';
}
?>

<section class="dashboard-page">

    <!-- 1. Dashboard KPI Cards -->
    <div class="stats-grid">

        <?php foreach ([
            [
                'Eligible E-Lots',
                $eligibleCount,
                'eligible-e-lots',
                'eligible_e-lot',
                '♻️'
            ],
            [
                'Submitted Bids',
                $submittedCount,
                'my-bids',
                'bids',
                '⚒️'
            ],
            [
                'Awarded E-Lots',
                $awardedCount,
                'awarded-e-lots',
                'awarded',
                '🏆'
            ],
            [
                'Awaiting Handover',
                $awaitingCount,
                'awarded-e-lots',
                'awaiting',
                '🚚'
            ]
        ] as [$label, $count, $path, $iconClass, $icon]): ?>

            <a
                class="stat-card stat-card-link"
                href="<?= $escape($basePath) ?>/recycler/<?= $escape($path) ?>"
            >
                <div
                    class="stat-icon <?= $escape($iconClass) ?>"
                    aria-hidden="true"
                >
                    <?= $escape($icon) ?>
                </div>

                <div class="stat-info">
                    <span><?= $escape($label) ?></span>
                    <h2><?= (int) $count ?></h2>
                </div>

                <span class="stat-card-arrow" aria-hidden="true">
                    <span class="chevron-symbol" aria-hidden="true">›</span>
                </span>
            </a>

        <?php endforeach; ?>

    </div>


    <!-- 2. Compliance Overview + Alerts -->
    <div class="dashboard-overview-grid">

        <!-- Compliance Overview -->
        <section class="dashboard-panel">

            <div class="dashboard-panel-header">
                <div>
                    <h2><span class="section-icon section-icon-shield" aria-hidden="true">🛡️</span>Compliance Overview</h2>
                    <p>Your authorization and compliance status.</p>
                </div>

                <span class="status <?= $statusClass($verificationStatus) ?>">
                    <?= $escape(
                        ucfirst(
                            strtolower($verificationStatus ?: 'Unknown')
                        )
                    ) ?>
                </span>
            </div>

            <div class="compliance-row">
                <span><span class="compliance-symbol" aria-hidden="true">📄</span> CEA Licence</span>

                <strong>
                    <?= $validLicenseExpiry
                        ? 'Valid until ' . $escape($validLicenseExpiry)
                        : 'No current valid licence' ?>
                </strong>
            </div>

            <div class="compliance-row">
                <span><span class="compliance-symbol" aria-hidden="true">📚</span> Handling Capabilities</span>

                <strong>
                    <?= $approvedCapabilities ?> Approved
                </strong>
            </div>

            <div class="compliance-row">
                <span><span class="compliance-symbol" aria-hidden="true">⚙️</span> Authorized Activities</span>

                <strong>
                    <?= $approvedActivities ?> Approved
                </strong>
            </div>

            <a
                class="dashboard-panel-link"
                href="<?= $escape($basePath) ?>/recycler/profile"
            >
                View Compliance Details <span class="link-symbol" aria-hidden="true">→</span>
            </a>

        </section>


        <!-- Alerts and Updates -->
        <section class="dashboard-panel">

            <div class="dashboard-panel-header">
                <div>
                    <h2><span class="section-icon section-icon-bell" aria-hidden="true">🔔</span>Alerts &amp; Updates</h2>
                    <p>Items that may need your attention.</p>
                </div>
            </div>

            <?php if (empty($alerts)): ?>

                <div class="dashboard-alert-success">
                    <strong><span class="check-symbol" aria-hidden="true">✓</span> All set!</strong>

                    <p>
                        No current compliance warnings or
                        pending handovers were detected.
                    </p>
                </div>

            <?php else: ?>

                <ul class="dashboard-alert-list">

                    <?php foreach ($alerts as $alert): ?>
                        <li>
                            <?= $escape($alert) ?>
                        </li>
                    <?php endforeach; ?>

                </ul>

            <?php endif; ?>

        </section>

    </div>

    <!-- 5. Recent Bids + Recent Awards -->
    <div class="dashboard-recent-grid">

        <!-- Recent Bids -->
        <section class="schedule-section">

            <div class="capability-card">

                <div class="section-header">
                    <div>
                        <h2><span class="section-icon section-icon-activity" aria-hidden="true">📋</span>Recent Bids</h2>

                        <p>
                            Your five most recently submitted bid records.
                        </p>
                    </div>

                    <a href="<?= $escape($basePath) ?>/recycler/my-bids">
                        View All Bids <span class="link-symbol" aria-hidden="true">→</span>
                    </a>
                </div>

                <?php if (empty($recentBids)): ?>

                    <div class="empty-state">
                        You have not submitted any bids yet.
                    </div>

                <?php else: ?>

                    <div class="capability-table-wrapper" tabindex="0" role="region" aria-label="Recent bids table">
                        <table class="capability-table"><caption class="sr-only">Recent bid submissions</caption>

                            <thead>
                                <tr>
                                    <th scope="col">E-Lot</th>
                                    <th scope="col">Bid Amount</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Submitted At</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($recentBids as $bid): ?>
                                    <?php
                                    $bidStatus = strtoupper(
                                        (string) ($bid['bid_status'] ?? '')
                                    );

                                    $bidStatusLabel = ucfirst(
                                        strtolower($bidStatus)
                                    );
                                    ?>

                                    <tr>
                                        <td>
                                            <strong>
                                                <?= $escape($bid['lot_code']) ?>
                                            </strong>

                                            <details class="lot-title">
                                                <summary title="Expand the full E-Lot title"><span class="sr-only">E-Lot title (expand for full text): </span><?= $escape($bid['title']) ?></summary>
                                            </details>
                                        </td>

                                        <td class="amount">
                                            <span class="currency">LKR</span> <?= number_format(
                                                (float) $bid['bid_amount'],
                                                2
                                            ) ?>
                                        </td>

                                        <td>
                                            <span class="status <?= $statusClass($bidStatus) ?>">
                                                <?= $escape($bidStatusLabel) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= $renderDate($bid['submitted_at'] ?? null) ?>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>
                    </div>

                <?php endif; ?>

            </div>

        </section>


        <!-- Recent Awards -->
        <section class="schedule-section">

            <div class="capability-card">

                <div class="section-header">
                    <div>
                        <h2><span class="section-icon section-icon-trophy" aria-hidden="true">🏆</span>Recent Awards</h2>

                        <p>
                            Your latest awarded E-Lots
                            and handover progress.
                        </p>
                    </div>

                    <a href="<?= $escape($basePath) ?>/recycler/awarded-e-lots">
                        View All Awards <span class="link-symbol" aria-hidden="true">→</span>
                    </a>
                </div>

                <?php if (empty($recentAwards)): ?>

                    <div class="empty-state">
                        No awarded E-Lots recorded yet.
                    </div>

                <?php else: ?>

                    <div class="capability-table-wrapper" tabindex="0" role="region" aria-label="Recent awards table">
                        <table class="capability-table"><caption class="sr-only">Recent awards and handover status</caption>

                            <thead>
                                <tr>
                                    <th scope="col">E-Lot</th>
                                    <th scope="col">Winning Bid</th>
                                    <th scope="col">Handover Status</th>
                                    <th scope="col">Awarded At</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($recentAwards as $award): ?>
                                    <?php
                                    $handoverStatus = strtoupper(
                                        (string) (
                                            $award['handover_status'] ?? ''
                                        )
                                    );

                                    $handoverLabel = $handoverStatus !== ''
                                        ? ucfirst(
                                            strtolower(
                                                str_replace(
                                                    '_',
                                                    ' ',
                                                    $handoverStatus
                                                )
                                            )
                                        )
                                        : 'Not recorded';
                                    ?>

                                    <tr>
                                        <td>
                                            <a href="<?= $escape($basePath) ?>/recycler/awarded-e-lot/<?= (int) $award['e_lot_id'] ?>">
                                                <strong>
                                                    <?= $escape(
                                                        $award['lot_code']
                                                    ) ?>
                                                </strong>
                                            </a>

                                            <div>
                                                <?= $escape($award['title']) ?>
                                            </div>
                                        </td>

                                        <td class="amount">
                                            <span class="currency">LKR</span> <?= number_format(
                                                (float) $award['bid_amount'],
                                                2
                                            ) ?>
                                        </td>

                                        <td>
                                            <span class="status <?= $statusClass($handoverStatus) ?>">
                                                <?= $escape($handoverLabel) ?>
                                            </span>

                                            <?php if (!empty($award['handover_date'])): ?>
                                                <div>
                                                    <?= $escape(
                                                        $award['handover_date']
                                                    ) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?= $renderDate($award['reviewed_at'] ?? null) ?>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>
                    </div>

                <?php endif; ?>

            </div>

        </section>

    </div>

    <!-- 6. Dashboard Quick Actions -->
    <section class="dashboard-quick-actions">

        <div class="quick-actions-header">
            <div>
                <h2><span class="section-icon section-icon-bolt" aria-hidden="true">⚡</span>Quick Actions</h2>
                <p>
                    Quickly access your recycling operations.
                </p>
            </div>
        </div>

        <div class="quick-actions-grid">

            <a
                href="<?= $escape($basePath) ?>/recycler/eligible-e-lots"
                class="quick-action-link quick-action-eligible"
            >
                <span class="quick-action-icon" aria-hidden="true">
                    ♻️
                </span>
                <span>Find Eligible E-Lots</span>
            </a>

            <a
                href="<?= $escape($basePath) ?>/recycler/my-bids"
                class="quick-action-link quick-action-bids"
            >
                <span class="quick-action-icon" aria-hidden="true">
                    ⚒️
                </span>
                <span>View My Bids</span>
            </a>

            <a
                href="<?= $escape($basePath) ?>/recycler/awarded-e-lots"
                class="quick-action-link quick-action-awards"
            >
                <span class="quick-action-icon" aria-hidden="true">
                    🏆
                </span>
                <span>My Awards</span>
            </a>

            <a
                href="<?= $escape($basePath) ?>/recycler/profile"
                class="quick-action-link quick-action-profile"
            >
                <span class="quick-action-icon" aria-hidden="true">
                    👤
                </span>
                <span>My Profile</span>
            </a>

        </div>

    </section>
</section>
