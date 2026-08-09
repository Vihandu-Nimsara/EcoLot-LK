<?php
$selectedId = (string) ($recyclerId ?? '5');
$isPending = $selectedId === '5';
$isRejected = $selectedId === '6';
$overallStatus = $isPending ? 'Pending Verification' : ($isRejected ? 'Rejected' : 'Verified');
$statusClass = $isPending ? 'status-pending' : ($isRejected ? 'status-rejected' : 'status-verified');
$companyName = $isPending ? 'Ceylon Tech Recyclers' : ($isRejected ? 'Urban Eco Metals' : 'GreenCycle Lanka Pvt Ltd');
$contactPerson = $isPending ? 'Isuru Bandara' : ($isRejected ? 'Malith Gunawardena' : 'Anjana Silva');
$email = $isPending ? 'isuru@ceylontechrecyclers.lk' : ($isRejected ? 'malith@urbaneco.lk' : 'anjana@greencycle.lk');
$phone = $isPending ? '078 112 3344' : ($isRejected ? '070 998 7766' : '077 234 5678');
$swmlNumber = $isPending ? 'SWML/2026/005' : 'SWML/2026/001';
$licenceStatus = $isPending ? 'Pending Verification' : 'Verified';
$capabilityStatus = $isPending ? 'Pending' : 'Approved';
?>
<section class="recycler-details-page">
    <div class="details-header">
        <div>
            <span class="details-eyebrow">Recycler Verification Details</span>
            <h1><?= htmlspecialchars($companyName) ?></h1>
            <p>Review the company, CEA licence record, requested capabilities, and final EcoLot verification.</p>
        </div>
        <div class="details-header-actions"><span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($overallStatus) ?></span><a class="header-action-btn" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/admin/recycler-verification">Back to Verification</a></div>
    </div>

    <p class="page-notice" data-page-notice tabindex="-1" hidden></p>

    <section class="details-card">
        <div class="details-section-header"><span class="section-step">A</span><div><h2>Company Information</h2><p>Read-only information submitted with the recycler registration.</p></div></div>
        <div class="details-grid">
            <div class="details-item"><span class="details-label">Company Name</span><strong class="details-value"><?= htmlspecialchars($companyName) ?></strong></div>
            <div class="details-item"><span class="details-label">Contact Person</span><strong class="details-value"><?= htmlspecialchars($contactPerson) ?></strong></div>
            <div class="details-item"><span class="details-label">Email</span><strong class="details-value"><?= htmlspecialchars($email) ?></strong></div>
            <div class="details-item"><span class="details-label">Phone</span><strong class="details-value"><?= htmlspecialchars($phone) ?></strong></div>
            <div class="details-item"><span class="details-label">Business Address</span><strong class="details-value"><?= $isPending ? '18 Industrial Road, Kandy' : '45 Green Park, Colombo 05' ?></strong></div>
            <div class="details-item"><span class="details-label">District</span><strong class="details-value"><?= $isPending ? 'Kandy' : 'Colombo' ?></strong></div>
        </div>
    </section>

    <section class="details-card">
        <div class="details-section-header"><span class="section-step">B</span><div><h2>CEA Licence Details</h2><p>EcoLot verifies the submitted record of the CEA-issued licence; it does not issue or revoke the licence.</p></div></div>
        <div class="details-grid">
            <div class="details-item details-item-wide"><span class="details-label">Licence Type</span><strong class="details-value">Scheduled Waste Management Licence (SWML)</strong></div>
            <div class="details-item"><span class="details-label">SWML Number</span><strong class="details-value"><?= htmlspecialchars($swmlNumber) ?></strong></div>
            <div class="details-item"><span class="details-label">Expiry Date</span><strong class="details-value"><?= $isPending ? '2027-02-10' : '2027-06-30' ?></strong></div>
            <div class="details-item"><span class="details-label">Licence Verification Status</span><span class="status-badge <?= $isPending ? 'status-pending' : 'status-verified' ?>"><?= htmlspecialchars($licenceStatus) ?></span></div>
            <div class="details-item"><span class="details-label">Licence Document</span><strong class="details-value">PDF evidence submitted</strong></div>
        </div>
        <div class="submitted-activities"><span>Activities Submitted</span><ul><li>Recovery</li><li>Recycling</li><li>Storage</li></ul></div>
        <div class="details-actions">
            <button class="action-btn approve-btn" type="button" data-admin-dialog="review-licence" data-name="<?= htmlspecialchars($companyName) ?>" data-swml="<?= htmlspecialchars($swmlNumber) ?>" data-expiry="<?= $isPending ? '2027-02-10' : '2027-06-30' ?>" data-status="<?= htmlspecialchars($licenceStatus) ?>">Review Licence</button>
        </div>
    </section>

    <section class="details-card">
        <div class="details-section-header"><span class="section-step">C</span><div><h2>Requested / Approved Waste-Handling Capabilities</h2><p>Each requested category is reviewed individually against the submitted licence information.</p></div></div>
        <div class="data-table-wrapper">
            <table class="categories-table capabilities-table">
                <thead><tr><th>Waste Category</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <tr><td>Demo Consumer Electronics</td><td><span class="status-badge <?= $isPending ? 'status-pending' : 'status-verified' ?>"><?= $capabilityStatus ?></span></td><td><button class="table-action-btn" type="button" data-admin-dialog="review-capability" data-name="Demo Consumer Electronics" data-status="<?= $capabilityStatus ?>">Review</button></td></tr>
                    <tr><td>Demo Battery and Circuit Boards</td><td><span class="status-badge status-rejected">Rejected</span></td><td><button class="table-action-btn" type="button" data-admin-dialog="review-capability" data-name="Demo Battery and Circuit Boards" data-status="Rejected">Review</button></td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="details-card final-decision-card">
        <div class="details-section-header"><span class="section-step">D</span><div><h2>Final Recycler Verification</h2><p>A recycler is verified only after company information, licence compliance, and at least one capability have been reviewed.</p></div></div>
        <ul class="verification-checklist">
            <li><span aria-hidden="true">✓</span> Company information reviewed</li>
            <li><span aria-hidden="true"><?= $isPending ? '○' : '✓' ?></span> CEA licence record verified</li>
            <li><span aria-hidden="true">✓</span> Licence record is not expired</li>
            <li><span aria-hidden="true"><?= $isPending ? '○' : '✓' ?></span> At least one capability approved</li>
        </ul>
        <div class="final-decision-note">Frontend representation only. Final verification does not automatically approve pending capabilities.</div>
        <div class="details-actions final-actions">
            <?php if ($isPending): ?>
                <button class="action-btn reject-btn" type="button" data-admin-dialog="reject-recycler" data-name="<?= htmlspecialchars($companyName) ?>">Reject Recycler</button>
                <button class="action-btn approve-btn" type="button" data-admin-dialog="approve-recycler" data-name="<?= htmlspecialchars($companyName) ?>">Verify Recycler</button>
            <?php elseif ($isRejected): ?>
                <button class="action-btn approve-btn" type="button" data-admin-dialog="reconsider-recycler" data-name="<?= htmlspecialchars($companyName) ?>">Reconsider Application</button>
            <?php else: ?>
                <span class="status-badge status-verified">Recycler Verified</span>
            <?php endif; ?>
        </div>
    </section>
</section>
