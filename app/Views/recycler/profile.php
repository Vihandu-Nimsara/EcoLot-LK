<?php
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$verification = $compliance['verification_status'] ?? null;
?>
<link rel="stylesheet" href="<?= $escape($basePath) ?>/assets/css/recycler/profile.css?v=<?= Asset::version('css/recycler/profile.css') ?>">
<section class="workflow-page">
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

    <section class="workflow-card">
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

            <div class="detail-item"><span class="detail-label">Verification</span><span class="badge <?= $verification === 'VERIFIED' ? 'badge-completed' : ($verification === 'REJECTED' ? 'badge-rejected' : 'badge-pending') ?>"><?= $escape(ucfirst(strtolower($verification ?? 'Not recorded'))) ?></span></div>
        </div>
    </section>

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

    <section class="workflow-card">

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

    <section class="workflow-card">
        <div class="workflow-section-header"><h2>Waste-Handling Capabilities</h2><p>Approved categories affect eligibility; all capability changes require Administrator review.</p></div>
        <?php if (!$capabilities): ?><div class="empty-state">No handling capabilities are recorded.</div><?php else: ?>
        <div class="workflow-table-wrapper"><table class="workflow-table">
            <thead><tr><th scope="col">Waste Category</th><th scope="col">Capability Status</th><th scope="col">Action</th></tr></thead>
            <tbody><?php foreach ($capabilities as $capability): ?><tr>
                <td data-label="Waste Category"><?= $escape($capability['category_name']) ?></td>
                <td data-label="Capability Status"><span class="badge <?= $capability['capability_status'] === 'APPROVED' ? 'badge-completed' : ($capability['capability_status'] === 'SUSPENDED' ? 'badge-rejected' : 'badge-pending') ?>"><?= $escape(ucfirst(strtolower($capability['capability_status']))) ?></span></td>
                <td data-label="Action"><?php if ($capability['capability_status'] === 'PENDING'): ?><span class="muted-action">Awaiting review</span><?php else: ?><button class="btn-action" type="button" data-profile-open="capability" data-category="<?= $escape($capability['category_name']) ?>">Request Change</button><?php endif; ?></td>
            </tr><?php endforeach; ?></tbody>
        </table></div><?php endif; ?>
        <div class="form-actions"><button class="secondary-workflow-btn" type="button" data-profile-open="capability">Request Capability Change</button></div>
    </section>
</section>

<template data-profile-template="capability">
    <p class="dialog-context-note">Capability changes require Administrator review before affecting eligibility. This form is a visual draft only; requests cannot be sent from this page.</p>
    <label class="dialog-field"><span>Waste Category</span><input data-profile-category autocomplete="off" placeholder="Enter the requested waste category"></label>
    <label class="dialog-field"><span>Request — Requires Administrator review</span><select><option value="">Select request</option><option>Add capability</option><option>Update capability</option><option>Deactivate capability</option></select></label>
    <label class="dialog-field"><span>Reason</span><textarea rows="3" placeholder="Explain the requested change"></textarea></label>
</template>
<div class="workspace-dialog recycler-profile-dialog" data-profile-modal hidden>
    <section class="workspace-dialog-card" role="dialog" aria-modal="true" aria-labelledby="profile-dialog-title" aria-describedby="profile-dialog-description">
        <div class="workspace-dialog-header"><div>
            <span class="dialog-eyebrow" data-profile-eyebrow>My Profile</span>
            <h2 id="profile-dialog-title"></h2>
            <p id="profile-dialog-description"></p>
        </div><button class="workspace-dialog-close" type="button" aria-label="Close profile dialog" data-profile-close>×</button></div>
        <form class="workspace-dialog-form" data-profile-form>
            <div class="workspace-dialog-fields" data-profile-fields></div>
            <div class="workspace-dialog-actions">
                <button class="dialog-secondary-btn" type="button" data-profile-close>Cancel</button>
                <button class="dialog-primary-btn" type="button" disabled data-profile-unavailable>Saving unavailable</button>
            </div>
        </form>
    </section>
</div>
<script defer src="<?= $escape($basePath) ?>/assets/js/recycler/profile.js?v=<?= Asset::version('js/recycler/profile.js') ?>"></script>
