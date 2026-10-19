<?php
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$verification = $compliance['verification_status'] ?? null;
?>
<link rel="stylesheet" href="<?= $escape($basePath) ?>/assets/css/recycler/profile.css?v=<?= Asset::version('css/recycler/profile.css') ?>">
<section class="workflow-page">
    <div class="workflow-header"><div>
        <h1>My Profile &amp; Compliance</h1>
        <p>Review your recorded company information, EcoLot verification, CEA licence status, and handling capabilities.</p>
    </div><div class="workflow-actions"><button class="secondary-workflow-btn" type="button" data-profile-open="contact">Edit Basic Information</button></div></div>
    <p class="page-notice">Some company and compliance changes require Administrator review.</p>

    <section class="workflow-card">
        <div class="workflow-section-header"><h2>Company Information</h2></div>
        <div class="detail-grid">
            <?php foreach (['Company Name' => 'company_name', 'Contact Person' => 'full_name',
                'Business Email' => 'email', 'Phone' => 'mobile_number', 'Business Address' => 'business_address',
                'District' => 'district'] as $label => $field): ?>
            <div class="detail-item"><span class="detail-label"><?= $escape($label) ?></span><strong class="detail-value"><?= $escape(($profile[$field] ?? '') !== '' ? $profile[$field] : 'Not recorded') ?></strong></div>
            <?php endforeach; ?>
            <div class="detail-item"><span class="detail-label">Verification</span><span class="badge <?= $verification === 'VERIFIED' ? 'badge-completed' : ($verification === 'REJECTED' ? 'badge-rejected' : 'badge-pending') ?>"><?= $escape(ucfirst(strtolower($verification ?? 'Not recorded'))) ?></span></div>
        </div>
    </section>

    <section class="workflow-card">
        <div class="workflow-section-header"><h2>CEA Licence</h2><p>Licence changes require Administrator verification before replacing the current verified record.</p></div>
        <div class="detail-grid">
            <div class="detail-item"><span class="detail-label">Licence Type</span><strong class="detail-value">Not available on this page</strong></div>
            <div class="detail-item"><span class="detail-label">SWML / Licence Number</span><strong class="detail-value">Not available on this page</strong></div>
            <div class="detail-item"><span class="detail-label">Current Valid Licence Expiry</span><strong class="detail-value"><?= $escape($compliance['valid_license_expiry'] ?? 'No current valid licence') ?></strong></div>
            <div class="detail-item"><span class="detail-label">Current Licence Status</span><span class="badge <?= !empty($compliance['valid_license_expiry']) ? 'badge-completed' : 'badge-pending' ?>"><?= !empty($compliance['valid_license_expiry']) ? 'Valid until ' . $escape($compliance['valid_license_expiry']) : 'No current valid licence' ?></span></div>
            <div class="detail-item"><span class="detail-label">Licence Document</span><strong class="detail-value">Document preview and submission are not available on this page.</strong></div>
        </div>
        <div class="form-actions"><button class="secondary-workflow-btn" type="button" data-profile-open="licence">Submit Licence Update</button></div>
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

<template data-profile-template="contact">
    <p class="dialog-context-note">Try changes as a visual draft. Saving contact details and sending company change requests are not available here. Closing this form discards your draft.</p>
    <label class="dialog-field"><span>Contact Person</span><input data-contact-name value="<?= $escape($profile['full_name'] ?? '') ?>" autocomplete="off"></label>
    <label class="dialog-field"><span>Business Email</span><input type="email" data-contact-email value="<?= $escape($profile['email'] ?? '') ?>" autocomplete="off"></label>
    <label class="dialog-field"><span>Phone</span><input readonly value="<?= $escape($profile['mobile_number'] ?? '') ?>" aria-describedby="profile-phone-help"><span class="dialog-helper" id="profile-phone-help">Mobile number changes require verification.</span></label>
    <?php foreach (['Company Name' => 'company_name', 'Business Address' => 'business_address', 'District' => 'district'] as $label => $field): ?>
    <label class="dialog-field"><span><?= $escape($label) ?> — Requires Administrator review</span><input value="<?= $escape($profile[$field] ?? '') ?>" autocomplete="off"></label>
    <?php endforeach; ?>
</template>
<template data-profile-template="licence">
    <p class="dialog-context-note">New licence information requires Administrator verification and does not replace the current verified record until approved. This form is a visual draft only; requests cannot be sent from this page.</p>
    <label class="dialog-field"><span>Current SWML / Licence Number</span><input readonly value="Not available on this page"></label>
    <label class="dialog-field"><span>New SWML / Licence Number — Requires Administrator review</span><input autocomplete="off"></label>
    <label class="dialog-field"><span>New Expiry Date — Requires Administrator review</span><input type="date"></label>
    <label class="dialog-field"><span>New Licence PDF — Requires Administrator review</span><input type="file" accept="application/pdf,.pdf" disabled aria-describedby="profile-document-help"><span class="dialog-helper" id="profile-document-help">Document submission is not available in the current implementation.</span></label>
</template>
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
