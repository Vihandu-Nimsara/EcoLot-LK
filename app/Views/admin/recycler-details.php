<?php
$recycler = $recycler ?? null;
$license = $license ?? null;

if ($recycler === null): ?>
    <section class="recycler-details-page">
        <div class="details-header">
            <div>
                <span class="details-eyebrow">Recycler Verification Details</span>
                <h1>Recycler not found</h1>
                <p>No recycler registration matches the requested ID.</p>
            </div>

            <a
                class="header-action-btn"
                href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/admin/recycler-verification"
            >
                Back to Verification
            </a>
        </div>
    </section>

    <?php return; ?>
<?php endif;

$companyName = (string) $recycler['company_name'];
$contactPerson = (string) $recycler['contact_person'];
$email = $recycler['email'] ?? 'Not provided';
$phone = (string) $recycler['phone'];
$address = (string) $recycler['business_address'];
$district = (string) $recycler['district'];
$recordStatus = strtoupper(
    (string) ($recycler['verification_status'] ?? 'PENDING')
);
$licenseNumber = $license['license_no'] ?? 'Not recorded';
$licenseExpiry = $license['license_expiry'] ?? 'Not recorded';
$licenseStatus = $license !== null 
? strtoupper((string) $license['license_status']) 
    : null;
$documentSubmitted = !empty($license['document_path']);


if ($recordStatus === 'VERIFIED') {
    $overallStatus = 'Verified';
    $statusClass = 'status-verified';
} elseif ($recordStatus === 'REJECTED') {
    $overallStatus = 'Rejected';
    $statusClass = 'status-rejected';
} else {
    $overallStatus = 'Pending Verification';
    $statusClass = 'status-pending';
}

if ($licenseStatus === 'VALID') {
    $licenseStatusLabel = 'Valid';
    $licenseStatusClass = 'status-verified';
} elseif ($licenseStatus === 'PENDING') {
    $licenseStatusLabel = 'Pending';
    $licenseStatusClass = 'status-pending';
} elseif ($licenseStatus !== null) {
    $licenseStatusLabel = ucfirst(strtolower($licenseStatus));
    $licenseStatusClass = 'status-rejected';
}
?>

<section class="recycler-details-page">

    <div class="details-header">
        <div>
            <span class="details-eyebrow">
                Recycler Verification Details
            </span>

            <h1>
                <?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?>
            </h1>

            <p>
                Review the Recycler registration and compliance information.
            </p>
        </div>

        <div class="details-header-actions">

            <span class="status-badge <?= $statusClass ?>">
                <?= htmlspecialchars($overallStatus, ENT_QUOTES, 'UTF-8') ?>
            </span>

            <a
                class="header-action-btn"
                href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/admin/recycler-verification"
            >
                Back to Verification
            </a>

        </div>
    </div>


    <!-- A. Company Information -->
    <section class="details-card">

        <div class="details-section-header">
            <span class="section-step">A</span>

            <div>
                <h2>Company Information</h2>
                <p>
                    Information submitted with the Recycler registration.
                </p>
            </div>
        </div>

        <div class="details-grid">

            <div class="details-item">
                <span class="details-label">Company Name</span>

                <strong class="details-value">
                    <?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?>
                </strong>
            </div>

            <div class="details-item">
                <span class="details-label">Contact Person</span>

                <strong class="details-value">
                    <?= htmlspecialchars($contactPerson, ENT_QUOTES, 'UTF-8') ?>
                </strong>
            </div>

            <div class="details-item">
                <span class="details-label">Email</span>

                <strong class="details-value">
                    <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>
                </strong>
            </div>

            <div class="details-item">
                <span class="details-label">Phone</span>

                <strong class="details-value">
                    <?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>
                </strong>
            </div>

            <div class="details-item">
                <span class="details-label">Business Address</span>

                <strong class="details-value">
                    <?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?>
                </strong>
            </div>

            <div class="details-item">
                <span class="details-label">District</span>

                <strong class="details-value">
                    <?= htmlspecialchars($district, ENT_QUOTES, 'UTF-8') ?>
                </strong>
            </div>

        </div>

    </section>


    <!-- B. license -->
    <section class="details-card">

        <div class="details-section-header">
            <span class="section-step">B</span>
            <h2>CEA license Details</h2>
        </div>
                <?php if ($license === null): ?>
                    <p>No license found.</p>
                <?php else: ?>
                    <div class="details-grid">
                        <div class="details-item">
                            <span class="details-label">License Number</span>
                            <strong class="details-value">
                                <?= htmlspecialchars($licenseNumber, ENT_QUOTES, 'UTF-8') ?>
                            </strong>
                        </div>
                        <div class="details-item">
                            <span class="details-label">Expiry Date</span>
                            <strong class="details-value">
                                <?= htmlspecialchars($licenseExpiry, ENT_QUOTES, 'UTF-8') ?>
                            </strong>
                        </div>
                        <div class="details-item">
                            <span class="details-label">Status</span>
                            <span class="status-badge <?= $licenseStatusClass ?>">
                                <?= htmlspecialchars($licenseStatusLabel, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                        <div class="details-item">
                            <span class="details-label">License Document</span>
                            <strong class="details-value">
                                <?= $documentSubmitted ? 'Document submitted' : "Not submitted" ?>
                            </strong>
                        </div>
                    </div>
                <?php endif; ?>
        </section>

        
    <!-- C. Capabilities -->
    <section class="details-card">

        <div class="details-section-header">
            <span class="section-step">C</span>

            <div>
                <h2>Waste-Handling Capabilities</h2>
                <p>
                    Review the Recycler's requested waste-handling capabilities.
                </p>
            </div>
        </div>

        <?php if (empty($capabilities)): ?>

            <p>No capabilities found.</p>

        <?php else: ?>

            <div class="data-table-wrapper">
                <table class="categories-table capabilities-table">
                    <thead>
                        <tr>
                            <th>Waste Category</th>
                            <th>High-Risk Handling</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($capabilities as $capability): ?>

                            <?php
                            $capabilityStatus = strtoupper(
                                (string) ($capability['capability_status'] ?? 'PENDING')
                            );

                            if ($capabilityStatus === 'APPROVED') {
                                $capabilityStatusLabel = 'Approved';
                                $capabilityStatusClass = 'status-verified';
                            } elseif ($capabilityStatus === 'PENDING') {
                                $capabilityStatusLabel = 'Pending';
                                $capabilityStatusClass = 'status-pending';
                            } else {
                                $capabilityStatusLabel = ucfirst(
                                    strtolower($capabilityStatus)
                                );
                                $capabilityStatusClass = 'status-rejected';
                            }
                            ?>

                            <tr>
                                <td>
                                    <?= htmlspecialchars(
                                        $capability['category_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= !empty($capability['can_handle_high_risk'])
                                        ? 'Yes'
                                        : 'No' ?>
                                </td>

                                <td>
                                    <span class="status-badge <?= $capabilityStatusClass ?>">
                                        <?= htmlspecialchars(
                                            $capabilityStatusLabel,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                </td>
                            </tr>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </section>

    <section class="details-card">
        <div class="details-section-header">
            <span class="section-step">D</span>

            <div>
                <h2>Authorized Activities</h2>
                <p>
                    Review the Recycler's requested authorized activities.
                </p>
            </div>
        </div>

        <?php if (empty($activities)): ?>

            <p>No activities found.</p>

        <?php else: ?>
            <div class="data-table-wrapper">
                <table class="categories-table">
                    <thead>
                        <tr>
                            <th>Activity</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activities as $activity): ?>
                            <?php
                            $activityStatus = strtoupper(
                                (string) ($activity['activity_status'] ?? 'PENDING')
                            );

                            if ($activityStatus === 'APPROVED') {
                                $activityStatusLabel = 'Approved';
                                $activityStatusClass = 'status-verified';
                            } elseif ($activityStatus === 'PENDING') {
                                $activityStatusLabel = 'Pending';
                                $activityStatusClass = 'status-pending';
                            } else {
                                $activityStatusLabel = ucfirst(
                                    strtolower($activityStatus)
                                );
                                $activityStatusClass = 'status-rejected';
                            }
                        ?>

                        <tr>
                            <td>
                                <?= htmlspecialchars(
                                    ucfirst(
                                        strtolower(
                                            str_replace('_', ' ', $activity['activity_type'])
                                        )
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>
                            <td>
                                <span class="status-badge <?= $activityStatusClass ?>">
                                    <?= htmlspecialchars(
                                        $activityStatusLabel,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>
                            </td>
                        </tr>
                    
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        
        <?php endif; ?>
    </section>


    <!-- D. Final Decision -->
    <section class="details-card final-decision-card">

        <div class="details-section-header">
            <span class="section-step">D</span>

            <div>
                <h2>Final Recycler Verification</h2>
                <p>
                    Final approval will be available after the required
                    compliance reviews are completed.
                </p>
            </div>
        </div>

    </section>

</section>