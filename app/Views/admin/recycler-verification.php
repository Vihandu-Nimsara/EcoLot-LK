<section class="recycler-verification-page">

    <?php
    $currentFilter = strtoupper($_GET['status'] ?? 'PENDING');
    $allowedFilters = ['PENDING', 'VERIFIED', 'REJECTED', 'ALL'];

    if (!in_array($currentFilter, $allowedFilters, true)) {
        $currentFilter = 'PENDING';
    }

    $recyclerProfiles = $recyclerProfiles ?? $recyclers ?? [];
    ?>

    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->
    <div class="verification-header">

        <div>
            <span class="page-eyebrow">Administration</span>

            <h1>Recycler Verification</h1>

            <p>
                Review recycler registrations, licenses, and verification status.
            </p>
        </div>

        <div class="page-header-actions">
            <a href="/EcoLot-LK/public/admin/dashboard" class="header-action-btn">
                Dashboard
            </a>
        </div>

    </div>


    <!-- =====================================================
         MAIN CARD
    ====================================================== -->
    <section class="verification-card">

        <!-- Filter Tabs -->
        <div class="verification-filters">

            <a href="/EcoLot-LK/public/admin/recycler-verification?status=PENDING"
               class="filter-tab <?= $currentFilter === 'PENDING' ? 'active' : '' ?>">
                Pending
            </a>

            <a href="/EcoLot-LK/public/admin/recycler-verification?status=VERIFIED"
               class="filter-tab <?= $currentFilter === 'VERIFIED' ? 'active' : '' ?>">
                Verified
            </a>

            <a href="/EcoLot-LK/public/admin/recycler-verification?status=REJECTED"
               class="filter-tab <?= $currentFilter === 'REJECTED' ? 'active' : '' ?>">
                Rejected
            </a>

            <a href="/EcoLot-LK/public/admin/recycler-verification?status=ALL"
               class="filter-tab <?= $currentFilter === 'ALL' ? 'active' : '' ?>">
                All
            </a>

        </div>


        <div class="current-filter-text">
            Current filter:
            <span><?= htmlspecialchars($currentFilter) ?></span>
        </div>


        <?php if (!empty($recyclerProfiles)): ?>

            <div class="profiles-list">

                <?php foreach ($recyclerProfiles as $profile): ?>

                    <?php
                    $recyclerId = $profile['recycler_id'] ?? $profile['id'] ?? '';
                    $companyName = $profile['company_name'] ?? $profile['organization_name'] ?? 'Unknown Company';
                    $contactPerson = $profile['contact_person'] ?? $profile['owner_name'] ?? $profile['full_name'] ?? 'Not Provided';
                    $email = $profile['email'] ?? 'Not Provided';
                    $phone = $profile['phone'] ?? $profile['contact_number'] ?? 'Not Provided';
                    $district = $profile['district'] ?? 'Not Provided';
                    $licenseNo = $profile['license_no'] ?? $profile['license_number'] ?? 'Not Provided';
                    $licenseExpiry = $profile['license_expiry'] ?? $profile['license_expiry_date'] ?? 'Not Provided';
                    $submittedAt = $profile['submitted_at'] ?? $profile['created_at'] ?? 'Not Available';
                    $status = strtoupper($profile['status'] ?? 'PENDING');

                    $statusClass = match ($status) {
                        'VERIFIED' => 'status-verified',
                        'REJECTED' => 'status-rejected',
                        default => 'status-pending',
                    };

                    $statusLabel = ucwords(strtolower($status));
                    ?>

                    <article class="profile-card">

                        <div class="profile-card-top">

                            <div class="profile-main-info">
                                <h3><?= htmlspecialchars($companyName) ?></h3>

                                <p>
                                    Recycler ID:
                                    <strong>#<?= htmlspecialchars((string)$recyclerId) ?></strong>
                                </p>
                            </div>

                            <div class="profile-status-wrap">
                                <span class="status-badge <?= $statusClass ?>">
                                    <?= htmlspecialchars($statusLabel) ?>
                                </span>
                            </div>

                        </div>


                        <div class="profile-details-grid">

                            <div class="detail-item">
                                <span class="detail-label">Contact Person</span>
                                <span class="detail-value"><?= htmlspecialchars($contactPerson) ?></span>
                            </div>

                            <div class="detail-item">
                                <span class="detail-label">Email</span>
                                <span class="detail-value"><?= htmlspecialchars($email) ?></span>
                            </div>

                            <div class="detail-item">
                                <span class="detail-label">Phone</span>
                                <span class="detail-value"><?= htmlspecialchars($phone) ?></span>
                            </div>

                            <div class="detail-item">
                                <span class="detail-label">District</span>
                                <span class="detail-value"><?= htmlspecialchars($district) ?></span>
                            </div>

                            <div class="detail-item">
                                <span class="detail-label">License No</span>
                                <span class="detail-value"><?= htmlspecialchars($licenseNo) ?></span>
                            </div>

                            <div class="detail-item">
                                <span class="detail-label">License Expiry</span>
                                <span class="detail-value"><?= htmlspecialchars($licenseExpiry) ?></span>
                            </div>

                            <div class="detail-item detail-item-full">
                                <span class="detail-label">Submitted At</span>
                                <span class="detail-value"><?= htmlspecialchars($submittedAt) ?></span>
                            </div>

                        </div>


                        <div class="profile-actions">

                            <a href="/EcoLot-LK/public/admin/recycler-verification/view/<?= urlencode((string)$recyclerId) ?>"
                               class="action-btn secondary-btn-style">
                                View Details
                            </a>

                            <?php if ($status !== 'VERIFIED'): ?>
                                <a href="/EcoLot-LK/public/admin/recycler-verification/approve/<?= urlencode((string)$recyclerId) ?>"
                                   class="action-btn approve-btn">
                                    Approve
                                </a>
                            <?php endif; ?>

                            <?php if ($status !== 'REJECTED'): ?>
                                <a href="/EcoLot-LK/public/admin/recycler-verification/reject/<?= urlencode((string)$recyclerId) ?>"
                                   class="action-btn reject-btn">
                                    Reject
                                </a>
                            <?php endif; ?>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty-state-card">
                No recycler profiles found.
            </div>

        <?php endif; ?>

    </section>

</section>