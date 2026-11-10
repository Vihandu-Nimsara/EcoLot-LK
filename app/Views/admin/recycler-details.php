<?php
$successMessage = $_SESSION['success'] ?? null;
$errorMessage = $_SESSION['error'] ?? null;

unset($_SESSION['success'], $_SESSION['error']);
?>

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
$isPending = $recordStatus ==='PENDING';
$isRejected = $recordStatus === 'REJECTED';

$hasLicense = $license !== null;

$licenseIsValid = $hasLicense && $licenseStatus === 'VALID';
$licenseNotExpired =
            $hasLicense &&
            !empty($license['license_expiry']) &&
            $license['license_expiry'] >= date('Y-m-d');

$approvedCapabilityCount = 0;
$pendingCapabilityCount = 0;

foreach ($capabilities as $capability) {
    $capabilityStatus = strtoupper(
        (string) ($capability['capability_status'] ?? '')
    );

    if ($capabilityStatus === 'APPROVED') {
        $approvedCapabilityCount++;
    } elseif ($capabilityStatus === 'PENDING') {
        $pendingCapabilityCount++;
    }
}

$approvedActivityCount = 0;
$pendingActivityCount = 0;

foreach ($activities as $activity) {
    $activityStatus = strtoupper(
        (string) ($activity['activity_status'] ?? '')
    );

    if ($activityStatus === 'APPROVED') {
        $approvedActivityCount++;
    } elseif ($activityStatus === 'PENDING') {
        $pendingActivityCount++;
    }
}

$canVerify =
    $isPending &&
    $licenseIsValid &&
    $licenseNotExpired &&
    $approvedCapabilityCount > 0 &&
    $pendingCapabilityCount === 0 &&
    $approvedActivityCount > 0 &&
    $pendingActivityCount === 0;


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

<?php if ($successMessage !== null): ?>
    <div class="alert alert-success" role="status">
        <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($errorMessage !== null): ?>
    <div class="alert alert-error" role="alert">
        <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

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

                            <?php if ($documentSubmitted): ?>
                                <a
                                    class="header-action-btn"
                                    href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/admin/recycler-verification/<?= (int) $recycler['recycler_id'] ?>/license-document"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    View Document
                                </a>
                            <?php else: ?>
                                <strong class="details-value">
                                    <?= $documentSubmitted ? 'Document submitted' : "Not submitted" ?>
                                </strong>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($isPending && $licenseStatus === 'PENDING'): ?>

                        <div class="final-review-actions">

                            <!-- Approve License -->

                            <form
                                class="verification-approve-form"
                                method="POST"
                                action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/admin/recycler-verification/<?= (int) $recycler['recycler_id'] ?>/license-review">

                                <input
                                    type="hidden"
                                    name="_csrf_token"
                                    value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                                <input
                                    type="hidden"
                                    name="license_id"
                                    value="<?= (int) $license['license_id'] ?>"
                                >

                                <input type="hidden"
                                       name="decision"
                                       value="APPROVE"
                                >

                                <button type="submit"
                                        class="action-btn approve-btn"
                                        <?= ($documentSubmitted && $licenseNotExpired) ? '' : 'disabled' ?>
                                >
                                    Approve License
                                </button>
                            </form>

                            <!-- Reject License -->

                            <form
                                method="POST"
                                action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/admin/recycler-verification/<?= (int) $recycler['recycler_id'] ?>/license-review"
                            >
                                <input
                                    type="hidden"
                                    name="_csrf_token"
                                    value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
                                >

                                <input
                                    type="hidden"
                                    name="license_id"
                                    value="<?= (int) $license['license_id'] ?>"
                                >

                                <input
                                    type="hidden"
                                    name="decision"
                                    value="REJECT"
                                >

                                <label for="license_review_note">
                                    License Rejection Reason
                                </label>

                                <textarea
                                    id="license_review_note"
                                    name="review_note"
                                    rows="3"
                                    maxlength="500"
                                    required
                                ></textarea>

                                <button type="submit" class="action-btn reject-btn">
                                    Reject License
                                </button>
                            </form>

                        </div>
                <?php endif; ?>
            <?php endif; ?>
    </section>


    <?php if (!empty($pendingLicenseUpdate)): ?>

        <section class="details-card license-update-review-card">

            <div class="details-section-header">
                <div>
                    <h2>Pending CEA Licence Update</h2>
                    <p>
                        The recycler has submitted updated
                        licence information for Administrator review.
                    </p>
                </div>
            </div>

            <div class="details-grid">

                <div class="details-item">
                    <span class="details-label">
                        Requested Licence Number
                    </span>

                    <strong class="details-value">
                        <?= htmlspecialchars(
                            (string) $pendingLicenseUpdate[
                                'requested_license_number'
                            ],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </strong>
                </div>

                <div class="details-item">
                    <span class="details-label">
                        Requested Expiry Date
                    </span>

                    <strong class="details-value">
                        <?= htmlspecialchars(
                            (string) $pendingLicenseUpdate[
                                'requested_expiry_date'
                            ],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </strong>
                </div>

                <div class="details-item">
                    <span class="details-label">
                        Submitted At
                    </span>

                    <strong class="details-value">
                        <?= htmlspecialchars(
                            (string) $pendingLicenseUpdate[
                                'submitted_at'
                            ],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </strong>
                </div>

                <div class="details-item">
                    <span class="details-label">
                        Review Status
                    </span>

                    <span class="status-badge status-pending">
                        Pending Review
                    </span>
                </div>

            </div>


            <?php
            $compareCurrentNumber = (string) ($license['license_no'] ?? '');
            $compareCurrentExpiry = (string) ($license['license_expiry'] ?? '');
            $compareRequestedNumber = (string) $pendingLicenseUpdate['requested_license_number'];
            $compareRequestedExpiry = (string) $pendingLicenseUpdate['requested_expiry_date'];

            if ($compareCurrentNumber === '') {
                $updateType = 'New Licence';
                $updateImpact = 'If approved, a new VALID licence will be created.';
            } elseif ($compareCurrentNumber === $compareRequestedNumber) {
                $updateType = 'Licence Renewal';
                $updateImpact = 'If approved, the existing licence will be renewed and its previous details preserved in revision history.';
            } else {
                $updateType = 'Licence Replacement';
                $updateImpact = 'If approved, the existing licence will become SUPERSEDED and a new VALID licence will be created.';
            }
            ?>

            <div class="license-update-impact" role="note">
                <strong><?= htmlspecialchars($updateType, ENT_QUOTES, 'UTF-8') ?></strong>
                <p><?= htmlspecialchars($updateImpact, ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <div class="license-update-comparison" aria-label="Licence details comparison">
                <div class="license-update-compare-panel">
                    <h3>Current Licence</h3>
                    <span class="details-label">Licence Number</span>
                    <strong>
                        <?= htmlspecialchars($compareCurrentNumber !== '' ? $compareCurrentNumber : 'Not recorded', ENT_QUOTES, 'UTF-8') ?>
                    </strong>
                    <span class="details-label">Expiry Date</span>
                    <strong>
                        <?= htmlspecialchars($compareCurrentExpiry !== '' ? $compareCurrentExpiry : 'Not recorded', ENT_QUOTES, 'UTF-8') ?>
                    </strong>
                </div>

                <div class="license-update-compare-panel license-update-compare-new">
                    <h3>Requested Licence</h3>
                    <span class="details-label">Licence Number</span>
                    <strong>
                        <?= htmlspecialchars($compareRequestedNumber, ENT_QUOTES, 'UTF-8') ?>
                    </strong>
                    <span class="details-label">Expiry Date</span>
                    <strong>
                        <?= htmlspecialchars($compareRequestedExpiry, ENT_QUOTES, 'UTF-8') ?>
                    </strong>
                </div>
            </div>

            <div class="license-update-document-action">
                <a
                    href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/admin/recycler-verification/<?= (int) $recycler['recycler_id'] ?>/license-update/<?= (int) $pendingLicenseUpdate['request_id'] ?>/document"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    View Submitted CEA Licence PDF
                </a>
            </div>

            <p class="page-notice">
                The currently approved licence remains
                unchanged until this request is approved.
            </p>

            <?php
            $licenseUpdateReviewUrl =
                $basePath .
                '/admin/recycler-verification/' .
                (int) $recycler['recycler_id'] .
                '/license-update/' .
                (int) $pendingLicenseUpdate['request_id'] .
                '/review';
            ?>

            <div class="final-review-actions">

                <!-- Approve Licence Update -->
                <form
                    method="POST"
                    action="<?= htmlspecialchars(
                        $licenseUpdateReviewUrl,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    onsubmit="return confirm('Approve this CEA licence update? The approved licence details will change.');"
                >
                    <input
                        type="hidden"
                        name="_csrf_token"
                        value="<?= htmlspecialchars(
                            $csrfToken,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                    <input
                        type="hidden"
                        name="decision"
                        value="APPROVE"
                    >

                    <button
                        type="submit"
                        class="action-btn approve-btn"
                    >
                        Approve Licence Update
                    </button>
                </form>

                <!-- Reject Licence Update -->
                <form
                    method="POST"
                    action="<?= htmlspecialchars(
                        $licenseUpdateReviewUrl,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
                    <input
                        type="hidden"
                        name="_csrf_token"
                        value="<?= htmlspecialchars(
                            $csrfToken,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                    <input
                        type="hidden"
                        name="decision"
                        value="REJECT"
                    >

                    <label for="license_update_rejection_note">
                        Licence Update Rejection Reason
                    </label>

                    <textarea
                        id="license_update_rejection_note"
                        name="review_note"
                        rows="3"
                        maxlength="500"
                        placeholder="Explain why this licence update is rejected..."
                        required
                    ></textarea>

                    <button
                        type="submit"
                        class="action-btn reject-btn"
                    >
                        Reject Licence Update
                    </button>
                </form>

            </div>

        </section>

    <?php endif; ?>


    <?php require __DIR__ . '/recycler-licence-coverage.php'; ?>

    <section class="details-card capability-change-requests">
        <div class="details-section-header"><div>
            <h2>Capability Change Requests</h2>
            <p>Post-registration changes for <?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?>. Approval applies the recorded proposal only when current capability data still matches its snapshot.</p>
        </div></div>
        <?php if (empty($capabilityRequests)): ?>
            <p>No capability change requests are recorded.</p>
        <?php else: ?>
            <?php foreach ($capabilityRequests as $change): ?>
                <details class="capability-change-request" <?= $change['request_status'] === 'PENDING' ? 'open' : '' ?>>
                    <summary><?= htmlspecialchars($change['category_name'] . ' — ' . $change['request_type'] . ' — ' . $change['request_status'], ENT_QUOTES, 'UTF-8') ?></summary>
                    <dl class="details-grid">
                        <div><dt>Previous capability status</dt><dd><?= htmlspecialchars($change['previous_capability_status'] ?? 'No capability', ENT_QUOTES, 'UTF-8') ?></dd></div>
                        <div><dt>Previous high-risk permission</dt><dd><?= $change['previous_can_handle_high_risk'] === null ? 'Not applicable' : ((int) $change['previous_can_handle_high_risk'] === 1 ? 'Yes' : 'No') ?></dd></div>
                        <div><dt>Proposed status</dt><dd><?= $change['request_type'] === 'DEACTIVATE' ? 'INACTIVE' : 'APPROVED' ?></dd></div>
                        <div><dt>Requested high-risk permission</dt><dd><?= $change['requested_can_handle_high_risk'] === null ? 'Unchanged' : ((int) $change['requested_can_handle_high_risk'] === 1 ? 'Yes' : 'No') ?></dd></div>
                        <div><dt>Request reason</dt><dd><?= htmlspecialchars($change['request_reason'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                        <div><dt>Submitted</dt><dd><?= htmlspecialchars($change['submitted_at'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                        <?php if ($change['reviewed_at'] !== null): ?>
                            <div><dt>Reviewed</dt><dd><?= htmlspecialchars($change['reviewed_at'], ENT_QUOTES, 'UTF-8') ?> by Administrator #<?= (int) $change['reviewed_by_admin_user_id'] ?></dd></div>
                            <div><dt>Review note</dt><dd><?= htmlspecialchars($change['review_note'] ?? 'No additional comments.', ENT_QUOTES, 'UTF-8') ?></dd></div>
                        <?php endif; ?>
                    </dl>
                    <?php if ($change['request_status'] === 'PENDING'): ?>
                        <p>Permission reductions are blocked while this category has unresolved bids or unfinished winning handovers. No existing bid or handover will be rewritten.</p>
                        <div class="final-review-actions">
                            <form method="post" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/admin/recycler-verification/<?= (int) $recycler['recycler_id'] ?>/capability-change/<?= (int) $change['request_id'] ?>/review" class="verification-reject-form">
                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="decision" value="APPROVE">
                                <label class="form-group">Approval note (optional)<textarea name="review_note" rows="2" maxlength="500"></textarea></label>
                                <label><input type="checkbox" required> I have reviewed the previous and proposed permissions and confirm this change.</label>
                                <button type="submit" class="action-btn approve-btn">Approve Capability Change</button>
                            </form>
                            <form method="post" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/admin/recycler-verification/<?= (int) $recycler['recycler_id'] ?>/capability-change/<?= (int) $change['request_id'] ?>/review" class="verification-reject-form">
                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="decision" value="REJECT">
                                <label class="form-group">Rejection reason<textarea name="review_note" rows="2" minlength="3" maxlength="500" required></textarea></label>
                                <button type="submit" class="action-btn reject-btn">Reject Capability Change</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </details>
            <?php endforeach; ?>
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
                            <th>Admin Review</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($capabilities as $capability): ?>

                            <?php
                            $categoryId = (int) $capability['category_id'];

                            $capabilityStatus = strtoupper(
                                (string) ($capability['capability_status'] ?? 'PENDING')
                            );

                            $canReviewCapability = $isPending
                                && $capabilityStatus === 'PENDING';

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
                                <td class="capability-review-cell">
                                    <?php if ($canReviewCapability): ?>

                                        <!-- Approve Capability -->
                                        <form
                                            method="POST"
                                            action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/admin/recycler-verification/<?= (int) $recycler['recycler_id'] ?>/capability-review"
                                        >
                                            <input
                                                type="hidden"
                                                name="_csrf_token"
                                                value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="category_id"
                                                value="<?= $categoryId ?>"
                                            >

                                            <input type="hidden" name="decision" value="APPROVE">

                                            <button type="submit" class="action-btn approve-btn">
                                                Approve
                                            </button>
                                        </form>

                                        <!-- Reject Capability -->
                                        <form
                                            method="POST"
                                            action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/admin/recycler-verification/<?= (int) $recycler['recycler_id'] ?>/capability-review"
                                        >
                                            <input
                                                type="hidden"
                                                name="_csrf_token"
                                                value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="category_id"
                                                value="<?= $categoryId ?>"
                                            >

                                            <input type="hidden" name="decision" value="REJECT">

                                            <label for="capability_note_<?= $categoryId ?>">
                                                Rejection Reason
                                            </label>

                                            <textarea
                                                id="capability_note_<?= $categoryId ?>"
                                                name="review_note"
                                                rows="2"
                                                maxlength="500"
                                                required
                                            ></textarea>

                                            <button type="submit" class="action-btn reject-btn">
                                                Reject
                                            </button>
                                        </form>

                                    <?php else: ?>
                                        <span>Review unavailable</span>
                                    <?php endif; ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </section>
    
    <!-- D. Authorized Activities -->
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
                <table class="categories-table activities-table">
                    <thead>
                        <tr>
                            <th>Activity</th>
                            <th>Status</th>
                            <th>Admin Review</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($activities as $activity): ?>
                            <?php
                            $activityId = (int) (
                                $activity['activity_id'] ?? 0
                            );

                            $activityStatus = strtoupper(
                                (string) ($activity['activity_status'] ?? '')
                            );

                            $canReviewActivity =
                                $isPending &&
                                $activityId > 0 &&
                                $activityStatus === 'PENDING';

                            if ($activityStatus === 'APPROVED') {
                                $activityStatusLabel = 'Approved';
                                $activityStatusClass = 'status-verified';

                            } elseif ($activityStatus === 'PENDING') {
                                $activityStatusLabel = 'Pending';
                                $activityStatusClass = 'status-pending';

                            } else {
                                $activityStatusLabel = $activityStatus !== ''
                                    ? ucfirst(strtolower($activityStatus))
                                    : 'Unknown';

                                $activityStatusClass = 'status-rejected';
                            }
                            ?>

                            <tr>
                                <td>
                                    <?= htmlspecialchars(
                                        ucfirst(
                                            strtolower(
                                                str_replace(
                                                    '_',
                                                    ' ',
                                                    (string) $activity['activity_type']
                                                )
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

                                <td class="capability-review-cell">
                                    <?php if ($canReviewActivity): ?>

                                        <!-- Approve Activity -->
                                        <form
                                            method="POST"
                                            action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/admin/recycler-verification/<?= (int) $recycler['recycler_id'] ?>/activity-review"
                                        >
                                            <input
                                                type="hidden"
                                                name="_csrf_token"
                                                value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="activity_id"
                                                value="<?= $activityId ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="decision"
                                                value="APPROVE"
                                            >

                                            <button
                                                type="submit"
                                                class="action-btn approve-btn"
                                            >
                                                Approve
                                            </button>
                                        </form>

                                        <!-- Reject Activity -->
                                        <form
                                            method="POST"
                                            action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/admin/recycler-verification/<?= (int) $recycler['recycler_id'] ?>/activity-review"
                                        >
                                            <input
                                                type="hidden"
                                                name="_csrf_token"
                                                value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="activity_id"
                                                value="<?= $activityId ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="decision"
                                                value="REJECT"
                                            >

                                            <label for="activity_note_<?= $activityId ?>">
                                                Rejection Reason
                                            </label>

                                            <textarea
                                                id="activity_note_<?= $activityId ?>"
                                                name="review_note"
                                                rows="2"
                                                maxlength="500"
                                                required
                                            ></textarea>

                                            <button
                                                type="submit"
                                                class="action-btn reject-btn"
                                            >
                                                Reject
                                            </button>
                                        </form>

                                    <?php else: ?>
                                        <span>Review unavailable</span>
                                    <?php endif; ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </section>



    <!-- E. Final Decision -->
    <section class="details-card final-decision-card">

        <div class="details-section-header">
            <span class="section-step">E</span>

            <div>
                <h2>Final Recycler Verification</h2>
                <p>
                    Final approval will be available after the required
                    compliance reviews are completed.
                </p>
            </div>
        </div>
        <ul class="verification-checklist">
            <li class="requirement-met">
                Company information submitted
            </li>
            <li class="<?= $licenseIsValid
                ? 'requirement-met'
                : 'requirement-unmet' ?>">
                CEA license is valid
            </li>
            <li class="<?= $licenseNotExpired
                ? 'requirement-met'
                : 'requirement-unmet' ?>">
                Licence is not expired
            </li>
            <li class="<?= $approvedCapabilityCount > 0
                ? 'requirement-met'
                : 'requirement-unmet' ?>">
                At least one capability approved
            </li>
            <li class="<?= $pendingCapabilityCount === 0
                ? 'requirement-met'
                : 'requirement-unmet' ?>">
                All capability reviews completed
            </li>

            <li class="<?= $approvedActivityCount > 0
                ? 'requirement-met'
                : 'requirement-unmet' ?>">
                At least one authorized activity approved
            </li>

            <li class="<?= $pendingActivityCount === 0
                ? 'requirement-met'
                : 'requirement-unmet' ?>">
                All authorized activity reviews completed
            </li>

        </ul>
        <div class="final-review-actions">
            <form
                class="verification-approve-form"
                method="POST"
                action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/admin/recycler-verification/<?= (int) $recycler['recycler_id'] ?>/review">

                <input
                    type="hidden"
                    name="_csrf_token"
                    value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                <input type="hidden"
                       name="decision"
                       value="APPROVE"
                >

                <button type="submit"
                        class="action-btn approve-btn"
                        <?= $canVerify ? '' : 'disabled' ?>
                >
                    Verify Recycler
                </button>
            </form>

            <form
                class="verification-reject-form"
                method="POST"
                action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/admin/recycler-verification/<?= (int) $recycler['recycler_id'] ?>/review">

                <input
                    type="hidden"
                    name="_csrf_token"
                    value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                <input type="hidden"
                       name="decision"
                       value="REJECT"
                >

                <label for="rejection_reason">
                    Rejection Reason
                </label>
                <textarea
                    name="rejection_reason"
                    id="rejection_reason"
                    maxlength="500"
                    required
                    <?= $isPending ? '' : 'disabled' ?>
                ></textarea>
                <button type="submit"
                        class="action-btn reject-btn"
                        <?= $isPending ? '' : 'disabled' ?>
                >
                    Reject Recycler
                </button>
            </form>
        </div>
    </section>

</section>
