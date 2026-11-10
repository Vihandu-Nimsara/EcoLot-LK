<?php
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$verification = $compliance['verification_status'] ?? null;
$capabilityOptions = $capabilityOptions ?? [];
$capabilityRequests = $capabilityRequests ?? [];
$capabilityFormInput = $capabilityFormInput ?? [];
$capabilityOptionsById = array_column($capabilityOptions, null, 'category_id');
$licenceCoverage = $licenceCoverage ?? [];
$licenceConflicts = $licenceConflicts ?? [];
$hasCurrentCoverage = false;
foreach ($licenceCoverage as $recordedScope) {
    if ($recordedScope['verification_status'] === 'VERIFIED' && (int) $recordedScope['current_document'] === 1 && $recordedScope['superseded_at'] === null) {
        $hasCurrentCoverage = true;
        break;
    }
}
?>
<link rel="stylesheet" href="<?= $escape($basePath) ?>/assets/css/recycler/profile.css?v=<?= Asset::version('css/recycler/profile.css') ?>">
<section class="workflow-page recycler-profile-page">
    <div class="workflow-header">
        <div>
            <h1>My Profile &amp; Compliance</h1>
            <p>Review your recorded company information, EcoLot verification, CEA license status, and handling capabilities.</p>
        </div>
    </div>
    <p class="page-notice">Some company and compliance changes require Administrator review.</p>

    <?php if (!empty($profileSuccess)): ?>
        <p class="page-notice" role="status">
            <?= $escape($profileSuccess) ?>
        </p>
    <?php endif; ?>

    <?php if (!empty($profileError)): ?>
        <p class="page-notice" role="alert">
            <?= $escape($profileError) ?>
        </p>
    <?php endif; ?>

    <div class="profile-layout">
    <div class="profile-column profile-company-column">
    <section class="workflow-card profile-company">
        <div class="workflow-section-header"><h2>Company Information</h2></div>
        <div class="detail-grid"> 
        <?php
        $companyFields = [
            'Company Name' => 'company_name',
            'Contact Person' => 'full_name',
            'Business Email' => 'email',
            'Phone' => 'mobile_number',
            'Business Address' => 'business_address',
            'District' => 'district',
        ];
        ?>

        <?php foreach ($companyFields as $label => $field): ?>
            <div class="detail-item">
                <span class="detail-label">
                    <?= $escape($label) ?>
                </span>

                <?php if ($field === 'full_name'): ?>
                    <details class="profile-contact-editor">
                        <summary>
                            <strong class="detail-value">
                                <?= $escape($profile['full_name'] ?? 'Not recorded') ?>
                            </strong>

                            <span class="profile-edit-closed">Edit</span>
                            <span class="profile-edit-open">Close</span>
                        </summary>

                        <form
                            class="profile-contact-form"
                            method="post"
                            action="<?= $escape($basePath) ?>/recycler/profile/contact"
                        >
                            <input
                                type="hidden"
                                name="_csrf_token"
                                value="<?= $escape($csrfToken ?? '') ?>"
                            >

                            <label for="recycler-contact-name">
                                Contact Person
                            </label>

                            <input
                                id="recycler-contact-name"
                                type="text"
                                name="full_name"
                                value="<?= $escape($profile['full_name'] ?? '') ?>"
                                minlength="2"
                                maxlength="120"
                                autocomplete="name"
                                required
                            >

                            <div class="form-actions">
                                <button type="reset" class="secondary-workflow-btn">
                                    Reset
                                </button>

                                <button type="submit" class="dialog-primary-btn">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </details>
                <?php elseif ($field === 'mobile_number'): ?>
                    <details class="profile-contact-editor profile-phone-editor" <?= !empty($phoneChange) || !empty($phoneEditorOpen) ? 'open' : '' ?>>
                        <summary>
                            <strong class="detail-value"><?= $escape($profile['mobile_number'] ?? 'Not recorded') ?></strong>
                            <span class="profile-edit-closed">Edit</span>
                            <span class="profile-edit-open">Close</span>
                        </summary>
                        <?php if (!empty($phoneChange)): ?>
                            <p class="dialog-helper">Verification code sent to <?= $escape($phoneChange['masked_mobile']) ?>. Your current number remains active until verification succeeds.</p>
                            <p class="dialog-helper">Code expires: <?= $escape($phoneChange['expires_at']) ?> (Asia/Colombo).</p>
                            <?php if (!$phoneChange['can_verify']): ?>
                                <p class="page-notice">This code cannot currently be used. Resend a code or cancel and start again.</p>
                            <?php endif; ?>
                            <form class="profile-contact-form" method="post" action="<?= $escape($basePath) ?>/recycler/profile/phone/verify">
                                <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
                                <label for="recycler-phone-otp">Six-digit verification code</label>
                                <input id="recycler-phone-otp" name="otp" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" minlength="6" maxlength="6" required>
                                <div class="form-actions"><button class="dialog-primary-btn" type="submit" <?= !$phoneChange['can_verify'] ? 'disabled' : '' ?>>Verify &amp; Save</button></div>
                            </form>
                            <div class="profile-phone-actions">
                                <form method="post" action="<?= $escape($basePath) ?>/recycler/profile/phone/resend">
                                    <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
                                    <button class="secondary-workflow-btn" type="submit" data-phone-resend data-cooldown="<?= (int) $phoneChange['retry_after'] ?>">Resend Code</button>
                                </form>
                                <form method="post" action="<?= $escape($basePath) ?>/recycler/profile/phone/cancel">
                                    <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
                                    <button class="secondary-workflow-btn" type="submit">Cancel Change</button>
                                </form>
                            </div>
                            <p class="dialog-helper" data-phone-cooldown aria-live="polite"><?= $phoneChange['retry_after'] > 0 ? 'Resend available in ' . (int) $phoneChange['retry_after'] . ' seconds.' : 'You can request a new code.' ?></p>
                        <?php else: ?>
                            <form class="profile-contact-form" method="post" action="<?= $escape($basePath) ?>/recycler/profile/phone/request">
                                <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
                                <p class="dialog-helper">Confirm your password and verify the new number before changing your login number.</p>
                                <label for="recycler-new-mobile">New Mobile Number</label>
                                <input id="recycler-new-mobile" name="proposed_mobile_number" type="tel" inputmode="tel" autocomplete="tel" maxlength="30" required>
                                <label for="recycler-phone-password">Current Password</label>
                                <input id="recycler-phone-password" name="current_password" type="password" autocomplete="current-password" required>
                                <div class="form-actions"><button class="dialog-primary-btn" type="submit">Send Verification Code</button></div>
                            </form>
                        <?php endif; ?>
                    </details>
                <?php else: ?>
                    <strong class="detail-value">
                        <?= $escape(
                            ($profile[$field] ?? '') !== ''
                                ? $profile[$field]
                                : 'Not recorded'
                        ) ?>
                    </strong>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

            <div class="detail-item"><span class="detail-label">Verification Status</span><span class="badge <?= $verification === 'VERIFIED' ? 'badge-completed' : ($verification === 'REJECTED' ? 'badge-rejected' : 'badge-pending') ?>"><?= $escape(ucfirst(strtolower($verification ?? 'Not recorded'))) ?></span></div>
        </div>
    </section>

    <section class="workflow-card profile-capabilities">
        <div class="workflow-section-header"><h2>Recycling Capabilities</h2><p>Approved categories affect eligibility; all capability changes require Administrator review.</p></div>
        <?php if (!$capabilities): ?><div class="empty-state">No handling capabilities are recorded.</div><?php else: ?>
        <div class="workflow-table-wrapper"><table class="workflow-table">
            <thead><tr><th scope="col">Waste Category</th><th scope="col">Capability Status</th><th scope="col">Action</th></tr></thead>
            <tbody><?php foreach ($capabilities as $capability): ?><tr>
                <td data-label="Waste Category"><?= $escape($capability['category_name']) ?></td>
                <td data-label="Capability Status"><span class="badge <?= $capability['capability_status'] === 'APPROVED' ? 'badge-completed' : (in_array($capability['capability_status'], ['SUSPENDED', 'REJECTED'], true) ? 'badge-rejected' : 'badge-pending') ?>"><?= $escape(ucfirst(strtolower($capability['capability_status']))) ?></span><span class="dialog-helper">High-risk: <?= !empty($capability['can_handle_high_risk']) ? 'Yes' : 'No' ?></span></td>
                <td data-label="Action"><?php if ($capability['capability_status'] === 'PENDING'): ?><span class="muted-action">Awaiting review</span><?php elseif (isset($capabilityOptionsById[$capability['category_id'] ?? 0])): ?><button class="btn-action" type="button" data-profile-open="capability" data-category-id="<?= (int) $capability['category_id'] ?>">Request Change</button><?php else: ?><span class="muted-action">No change available</span><?php endif; ?></td>
            </tr><?php endforeach; ?></tbody>
        </table></div><?php endif; ?>
        <div class="form-actions"><button class="secondary-workflow-btn" type="button" data-profile-open="capability" <?= !$capabilityOptions ? 'disabled' : '' ?> <?= $capabilityFormInput ? 'data-profile-auto-open' : '' ?>>Request Capability Change</button></div>
        <p class="page-notice">Requests require Administrator approval. Existing permissions remain unchanged until approval. Permission reductions cannot be approved while this category has unresolved bids or unfinished winning handovers.</p>
        <?php if ($capabilityRequests): ?>
            <details class="profile-capability-history" <?= in_array('PENDING', array_column($capabilityRequests, 'request_status'), true) ? 'open' : '' ?>>
                <summary>Recent Capability Requests</summary>
                <?php foreach ($capabilityRequests as $request): ?>
                    <article class="profile-capability-request">
                        <h3><?= $escape($request['category_name']) ?> — <?= $escape(ucfirst(strtolower($request['request_type']))) ?></h3>
                        <span class="badge <?= $request['request_status'] === 'APPROVED' ? 'badge-completed' : ($request['request_status'] === 'REJECTED' ? 'badge-rejected' : 'badge-pending') ?>"><?= $escape(ucfirst(strtolower($request['request_status']))) ?></span>
                        <p>Previous: <?= $escape($request['previous_capability_status'] ?? 'No capability') ?>; high-risk <?= $request['previous_can_handle_high_risk'] === null ? 'Not applicable' : ((int) $request['previous_can_handle_high_risk'] === 1 ? 'Yes' : 'No') ?>.</p>
                        <p>Requested: <?= $request['request_type'] === 'DEACTIVATE' ? 'Inactive' : 'Approved' ?>; high-risk <?= $request['requested_can_handle_high_risk'] === null ? 'Unchanged' : ((int) $request['requested_can_handle_high_risk'] === 1 ? 'Yes' : 'No') ?>.</p>
                        <p>Reason: <?= $escape($request['request_reason']) ?></p>
                        <p>Submitted: <?= $escape($request['submitted_at']) ?></p>
                        <?php if ($request['reviewed_at'] !== null): ?><p>Reviewed: <?= $escape($request['reviewed_at']) ?>. <?= $escape($request['review_note'] ?? 'Approved without additional comments.') ?></p><?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </details>
        <?php endif; ?>
    </section>
    </div>

    <?php
    $today = (new DateTimeImmutable(
        'today',
        new DateTimeZone('Asia/Colombo')
    ))->format('Y-m-d');

    $tomorrow = (new DateTimeImmutable(
        'tomorrow',
        new DateTimeZone('Asia/Colombo')
    ))->format('Y-m-d');

    $isVerified = $verification === 'VERIFIED';
    $hasPendingLicenseUpdate = !empty($pendingLicenseUpdate);

    $licenseExpiry = $currentLicense['expiry_date'] ?? null;

    $licenseIsExpired = $licenseExpiry !== null
        && $licenseExpiry < $today;
    ?>

    <div class="profile-column profile-compliance-column">
    <section class="workflow-card profile-licence">

        <div class="workflow-section-header">
            <h2>CEA Licence</h2>
            <p>
                Licence updates require Administrator approval.
                Your existing approved record remains unchanged
                while a request is under review.
            </p>
        </div>

        <div class="detail-grid">

            <div class="detail-item">
                <span class="detail-label">
                    Current Licence Number
                </span>

                <strong class="detail-value">
                    <?= $escape(
                        $currentLicense['license_number']
                        ?? 'No approved licence recorded'
                    ) ?>
                </strong>
            </div>

            <div class="detail-item">
                <span class="detail-label">
                    Current Licence Expiry
                </span>

                <strong class="detail-value">
                    <?= $escape(
                        $licenseExpiry ?? 'Not recorded'
                    ) ?>
                </strong>
            </div>

            <div class="detail-item">
                <span class="detail-label">
                    Current Licence Status
                </span>

                <?php if ($currentLicense === null): ?>

                    <span class="badge badge-pending">
                        Not recorded
                    </span>

                <?php elseif ($licenseIsExpired): ?>

                    <span class="badge badge-rejected">
                        Expired
                    </span>

                <?php else: ?>

                    <span class="badge badge-completed">
                        Valid
                    </span>

                <?php endif; ?>
            </div>

        </div>

        <?php if ($hasPendingLicenseUpdate): ?>

            <div class="profile-license-request-status">

                <h3>Licence Update Awaiting Review</h3>

                <p>
                    Your latest licence update request has been
                    submitted and is awaiting Administrator review.
                </p>

                <div class="detail-grid">

                    <div class="detail-item">
                        <span class="detail-label">
                            Requested Licence Number
                        </span>

                        <strong class="detail-value">
                            <?= $escape(
                                $pendingLicenseUpdate['requested_license_number']
                            ) ?>
                        </strong>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">
                            Requested Expiry Date
                        </span>

                        <strong class="detail-value">
                            <?= $escape(
                                $pendingLicenseUpdate['requested_expiry_date']
                            ) ?>
                        </strong>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">
                            Request Status
                        </span>

                        <span class="badge badge-pending">
                            Pending Review
                        </span>
                    </div>

                </div>

                <p class="page-notice">
                    Another licence update cannot be submitted
                    until this request has been reviewed.
                </p>

            </div>

        <?php elseif ($isVerified): ?>

            <details class="profile-license-update">

                <summary class="secondary-workflow-btn profile-license-toggle">
                    Update CEA Licence
                </summary>

                <p>
                    Renew an existing CEA licence or submit a new
                    licence number. Upload the updated licence PDF
                    for Administrator review.
                </p>

                <form
                    class="profile-license-form"
                    method="post"
                    action="<?= $escape($basePath) ?>/recycler/profile/license-update"
                    enctype="multipart/form-data"
                >

                    <input
                        type="hidden"
                        name="_csrf_token"
                        value="<?= $escape($csrfToken ?? '') ?>"
                    >

                    <label class="dialog-field">
                        <span>Updated Licence Number</span>

                        <input
                            type="text"
                            name="requested_license_number"
                            value="<?= $escape(
                                $currentLicense['license_number'] ?? ''
                            ) ?>"
                            maxlength="100"
                            required
                            autocomplete="off"
                        >

                        <span class="dialog-helper">
                            You may keep your existing licence number
                            if the licence has been renewed.
                        </span>
                    </label>

                    <label class="dialog-field">
                        <span>New Expiry Date</span>

                        <input
                            type="date"
                            name="requested_expiry_date"
                            min="<?= $escape($tomorrow) ?>"
                            required
                        >
                    </label>

                    <label class="dialog-field">
                        <span>Updated CEA Licence PDF</span>

                        <input
                            type="file"
                            name="license_document"
                            accept=".pdf,application/pdf"
                            required
                        >

                        <span class="dialog-helper">
                            PDF only. Maximum file size: 5 MB.
                        </span>
                    </label>

                    <p class="page-notice">
                        Submission does not immediately replace
                        your current approved licence.
                        Administrator approval is required.
                    </p>

                    <div class="form-actions">
                        <button
                            class="secondary-workflow-btn"
                            type="submit"
                        >
                            Submit for Admin Review
                        </button>
                    </div>

                </form>

            </details>

        <?php else: ?>

            <p class="page-notice">
                Licence updates are available to verified recyclers.
            </p>

        <?php endif; ?>

    </section>

    <section class="workflow-card profile-compliance">
        <div class="workflow-section-header"><h2>Compliance Overview</h2><p>Recorded verification and approvals. Licence validity does not establish category or activity coverage.</p></div>
        <?php if (!$hasCurrentCoverage): ?><p class="page-notice">Licence scope verification required. Existing records are preserved, but new bids and expanded authorizations require Administrator verification of current category, activity and high-risk evidence. You do not need to register again.</p><?php endif; ?>
        <?php if ($licenceConflicts): ?><p class="page-notice">Some existing bids or awards need authorization review. They have not been cancelled or rewritten. Contact the Administrator or Municipal Officer.</p><?php endif; ?>
        <details class="profile-capability-history"><summary>Licence-linked scope and feedback</summary>
            <?php foreach ($licenceCoverage as $scope): ?><article class="profile-capability-request">
                <h3><?= $escape($scope['category_name']) ?> — <?= $escape(ucfirst(strtolower($scope['activity_type']))) ?></h3>
                <p><?= $escape($scope['verification_status']) ?><?= (int)$scope['current_document']!==1 || $scope['superseded_at']!==null ? ' — Historical; re-verification required' : '' ?>; high-risk <?= (int)$scope['covers_high_risk']===1 && $scope['verification_status']==='VERIFIED' && (int)$scope['current_document']===1 && $scope['superseded_at']===null ? 'Explicitly covered' : 'Not currently established' ?>.</p>
                <p>Licence <?= $escape($scope['license_number']) ?>, document version <?= (int)$scope['document_version'] ?>. <?= $escape($scope['review_note']??'Awaiting evidence review') ?></p>
            </article><?php endforeach; ?>
        </details>
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Recycler Verification</span>
                <span class="badge <?= $verification === 'VERIFIED' ? 'badge-completed' : ($verification === 'REJECTED' ? 'badge-rejected' : 'badge-pending') ?>"><?= $escape(ucfirst(strtolower($verification ?? 'Not recorded'))) ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">CEA Licence</span>
                <span class="badge <?= !empty($compliance['valid_license_expiry']) ? 'badge-completed' : 'badge-pending' ?>"><?= !empty($compliance['valid_license_expiry']) ? 'Valid until ' . $escape($compliance['valid_license_expiry']) : 'No current valid licence' ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Handling Capabilities</span>
                <strong class="detail-value"><?= count(array_filter($capabilities, static fn (array $row): bool => $row['capability_status'] === 'APPROVED')) ?> Approved</strong>
            </div>
            <div class="detail-item">
                <span class="detail-label">Authorized Activities</span>
                <strong class="detail-value"><?= count(array_filter($activities, static fn (array $row): bool => $row['activity_status'] === 'APPROVED')) ?> Approved</strong>
            </div>
        </div>
    </section>

    <section class="workflow-card profile-activities">
        <div class="workflow-section-header"><h2>Authorized Activities</h2><p>Recorded activities and their approval statuses.</p></div>
        <?php if (!$activities): ?>
            <div class="empty-state">No authorized activities are recorded.</div>
        <?php else: ?>
            <div class="detail-grid">
                <?php foreach ($activities as $activity): ?>
                    <div class="detail-item">
                        <span class="detail-label"><?= $escape(ucwords(strtolower(str_replace('_', ' ', $activity['activity_type'])))) ?></span>
                        <span class="badge <?= $activity['activity_status'] === 'APPROVED' ? 'badge-completed' : (in_array($activity['activity_status'], ['REJECTED', 'SUSPENDED', 'REVOKED'], true) ? 'badge-rejected' : 'badge-pending') ?>"><?= $escape(ucfirst(strtolower($activity['activity_status']))) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    </div>
    </div>
</section>

<template data-profile-template="capability">
    <p class="dialog-context-note">Capability changes require Administrator approval and current licence scope verification before affecting eligibility. ADD, UPDATE and reactivation may require supporting licence evidence, not necessarily a new licence. Suspended or rejected capabilities cannot be reactivated here.</p>
    <label class="dialog-field"><span>Waste Category</span><select name="category_id" data-profile-category data-selected="<?= (int) ($capabilityFormInput['category_id'] ?? 0) ?>" required>
        <option value="">Select category</option>
        <?php foreach ($capabilityOptions as $option): ?>
            <option value="<?= (int) $option['category_id'] ?>" data-actions="<?= $escape(implode(',', $option['actions'])) ?>" data-status="<?= $escape($option['current_status']) ?>" data-risk="<?= $escape($option['current_risk'] ?? '') ?>"><?= $escape($option['category_name']) ?></option>
        <?php endforeach; ?>
    </select></label>
    <label class="dialog-field"><span>Request</span><select name="request_type" data-profile-request data-selected="<?= $escape($capabilityFormInput['request_type'] ?? '') ?>" required><option value="">Select request</option></select></label>
    <label class="dialog-field" data-profile-risk-field><span>Requested High-Risk Permission</span><select name="requested_can_handle_high_risk" data-profile-risk data-selected="<?= $escape($capabilityFormInput['risk'] ?? '') ?>" required><option value="">Select permission</option></select></label>
    <label class="dialog-field"><span>Request Reason</span><textarea name="request_reason" rows="3" maxlength="500" required placeholder="Explain the requested change"><?= $escape($capabilityFormInput['reason'] ?? '') ?></textarea></label>
</template>
<div class="workspace-dialog recycler-profile-dialog" data-profile-modal hidden>
    <section class="workspace-dialog-card" role="dialog" aria-modal="true" aria-labelledby="profile-dialog-title" aria-describedby="profile-dialog-description">
        <div class="workspace-dialog-header"><div>
            <span class="dialog-eyebrow" data-profile-eyebrow>My Profile</span>
            <h2 id="profile-dialog-title"></h2>
            <p id="profile-dialog-description"></p>
        </div><button class="workspace-dialog-close" type="button" aria-label="Close profile dialog" data-profile-close>×</button></div>
        <form class="workspace-dialog-form" data-profile-form method="post" action="<?= $escape($basePath) ?>/recycler/profile/capabilities/request">
            <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
            <div class="workspace-dialog-fields" data-profile-fields></div>
            <div class="workspace-dialog-actions">
                <button class="dialog-secondary-btn" type="button" data-profile-close>Cancel</button>
                <button class="dialog-primary-btn" type="submit" <?= !$capabilityOptions ? 'disabled' : '' ?> data-profile-submit>Submit Request</button>
            </div>
        </form>
    </section>
</div>
<script defer src="<?= $escape($basePath) ?>/assets/js/recycler/profile.js?v=<?= Asset::version('js/recycler/profile.js') ?>"></script>
