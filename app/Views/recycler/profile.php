<?php
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$verification = $compliance['verification_status'] ?? null;
?>
<section class="workflow-page">
    <div class="workflow-header"><div>
        <h1>My Profile &amp; Compliance</h1>
        <p>Review your recorded company information, EcoLot verification, CEA licence status, and handling capabilities.</p>
    </div></div>
    <p class="page-notice">This profile is read-only. Profile edits, licence update requests, and capability change requests are not available here.</p>

    <section class="workflow-card">
        <div class="workflow-section-header"><h2>Company Information</h2></div>
        <div class="detail-grid">
            <?php foreach (['Company Name' => 'company_name', 'Contact Person' => 'full_name',
                'Email' => 'email', 'Phone' => 'mobile_number', 'Business Address' => 'business_address',
                'District' => 'district'] as $label => $field): ?>
            <div class="detail-item"><span class="detail-label"><?= $escape($label) ?></span><strong class="detail-value"><?= $escape(($profile[$field] ?? '') !== '' ? $profile[$field] : 'Not recorded') ?></strong></div>
            <?php endforeach; ?>
            <div class="detail-item"><span class="detail-label">Verification</span><span class="badge <?= $verification === 'VERIFIED' ? 'badge-completed' : ($verification === 'REJECTED' ? 'badge-rejected' : 'badge-pending') ?>"><?= $escape(ucfirst(strtolower($verification ?? 'Not recorded'))) ?></span></div>
        </div>
    </section>

    <section class="workflow-card">
        <div class="workflow-section-header"><h2>CEA Licence</h2><p>Current licence eligibility, using the same recorded compliance information as your Dashboard.</p></div>
        <div class="detail-grid">
            <div class="detail-item"><span class="detail-label">Current Valid Licence</span><strong class="detail-value"><?= !empty($compliance['valid_license_expiry']) ? 'Valid until ' . $escape($compliance['valid_license_expiry']) : 'No current valid licence' ?></strong></div>
            <div class="detail-item"><span class="detail-label">Licence Details &amp; Documents</span><strong class="detail-value">Detailed licence records and document previews are not available on this page.</strong></div>
        </div>
    </section>

    <section class="workflow-card">
        <div class="workflow-section-header"><h2>Waste-Handling Capabilities</h2><p>Recorded category approvals affect bidding eligibility. Changes are not available on this page.</p></div>
        <?php if (!$capabilities): ?><div class="empty-state">No handling capabilities are recorded.</div><?php else: ?>
        <div class="workflow-table-wrapper"><table class="workflow-table">
            <thead><tr><th scope="col">Waste Category</th><th scope="col">Capability Status</th></tr></thead>
            <tbody><?php foreach ($capabilities as $capability): ?><tr>
                <td data-label="Waste Category"><?= $escape($capability['category_name']) ?></td>
                <td data-label="Capability Status"><span class="badge <?= $capability['capability_status'] === 'APPROVED' ? 'badge-completed' : ($capability['capability_status'] === 'SUSPENDED' ? 'badge-rejected' : 'badge-pending') ?>"><?= $escape(ucfirst(strtolower($capability['capability_status']))) ?></span></td>
            </tr><?php endforeach; ?></tbody>
        </table></div><?php endif; ?>
    </section>
</section>
