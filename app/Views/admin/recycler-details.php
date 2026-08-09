<?php
$selectedId = (string) ($recyclerId ?? '');
$records = [
 '1'=>['GreenCycle Lanka Pvt Ltd','Anjana Silva','anjana@greencycle.lk','077 234 5678','45 Green Park, Colombo 05','Colombo','SWML/2026/001','2027-06-30','VERIFIED'],
 '2'=>['E-Waste Recovery Colombo','Nimal Perera','nimal@ewasterecovery.lk','071 456 7890','28 Recovery Road, Colombo 10','Colombo','SWML/2026/002','2027-04-18','VERIFIED'],
 '3'=>['SafeDispose Electronics','Kavindi Fernando','kavindi@safedispose.lk','076 876 5432','14 Circular Avenue, Wattala','Gampaha','SWML/2026/003','2027-03-22','VERIFIED'],
 '4'=>['Eco Recyclers Pvt Ltd','Tharindu Jayasinghe','tharindu@ecorecyclers.lk','075 345 9087','8 Coastal Road, Panadura','Kalutara','SWML/2026/004','2027-08-15','VERIFIED'],
 '5'=>['Ceylon Tech Recyclers','Isuru Bandara','isuru@ceylontechrecyclers.lk','078 112 3344','18 Industrial Road, Kandy','Kandy','SWML/2026/005','2027-02-10','PENDING'],
 '6'=>['Urban Eco Metals','Malith Gunawardena','malith@urbaneco.lk','070 998 7766','21 Harbour Road, Galle','Galle','SWML/2026/006','2026-01-12','REJECTED'],
];
$record = $records[$selectedId] ?? null;
if ($record === null): ?>
<section class="recycler-details-page"><div class="details-header"><div><span class="details-eyebrow">Recycler Verification Details</span><h1>Recycler not found</h1><p>No recycler registration matches the requested ID.</p></div><a class="header-action-btn" href="<?= htmlspecialchars($basePath) ?>/admin/recycler-verification">Back to Verification</a></div></section>
<?php return; endif;
[$companyName,$contactPerson,$email,$phone,$address,$district,$swmlNumber,$expiry,$recordStatus]=$record;
$isPending = $recordStatus === 'PENDING';
$isRejected = $recordStatus === 'REJECTED';
$overallStatus = $isPending ? 'Pending Verification' : ($isRejected ? 'Rejected' : 'Verified');
$statusClass = $isPending ? 'status-pending' : ($isRejected ? 'status-rejected' : 'status-verified');
$licenceStatus = $isPending ? 'Pending Verification' : ($isRejected ? 'Rejected' : 'Verified');
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
            <div class="details-item"><span class="details-label">Business Address</span><strong class="details-value"><?= htmlspecialchars($address) ?></strong></div>
            <div class="details-item"><span class="details-label">District</span><strong class="details-value"><?= htmlspecialchars($district) ?></strong></div>
        </div>
    </section>

    <section class="details-card">
        <div class="details-section-header"><span class="section-step">B</span><div><h2>CEA Licence Details</h2><p>EcoLot verifies the submitted record of the CEA-issued licence; it does not issue or revoke the licence.</p></div></div>
        <div class="details-grid">
            <div class="details-item details-item-wide"><span class="details-label">Licence Type</span><strong class="details-value">Scheduled Waste Management Licence (SWML)</strong></div>
            <div class="details-item"><span class="details-label">SWML Number</span><strong class="details-value"><?= htmlspecialchars($swmlNumber) ?></strong></div>
            <div class="details-item"><span class="details-label">Expiry Date</span><strong class="details-value"><?= htmlspecialchars($expiry) ?></strong></div>
            <div class="details-item"><span class="details-label">Licence Verification Status</span><span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($licenceStatus) ?></span></div>
            <div class="details-item"><span class="details-label">Licence Document</span><strong class="details-value">Submitted document preview unavailable in this frontend demo</strong></div>
        </div>
        <div class="submitted-activities"><span>Activities Submitted</span><ul><li>Recovery</li><li>Recycling</li><li>Storage</li></ul></div>
        <div class="details-actions">
            <button class="action-btn approve-btn" type="button" data-admin-dialog="review-licence" data-name="<?= htmlspecialchars($companyName) ?>" data-swml="<?= htmlspecialchars($swmlNumber) ?>" data-expiry="<?= htmlspecialchars($expiry) ?>" data-status="<?= htmlspecialchars($licenceStatus) ?>">Review Licence</button>
        </div>
    </section>

    <section class="details-card">
        <div class="details-section-header"><span class="section-step">C</span><div><h2>Requested / Approved Waste-Handling Capabilities</h2><p>Each requested category is reviewed individually against the submitted licence information.</p></div></div>
        <div class="data-table-wrapper">
            <table class="categories-table capabilities-table">
                <thead><tr><th>Waste Category</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <tr><td>Demo Consumer Electronics</td><td><span class="status-badge <?= $isRejected ? 'status-rejected' : ($isPending ? 'status-pending' : 'status-verified') ?>"><?= $isRejected ? 'Rejected' : $capabilityStatus ?></span></td><td><button class="table-action-btn" type="button" data-admin-dialog="review-capability" data-name="Demo Consumer Electronics" data-status="<?= $isRejected ? 'Rejected' : $capabilityStatus ?>">Review</button></td></tr>
                    <?php if (!$isRejected): ?><tr><td>Demo Battery and Circuit Boards</td><td><span class="status-badge <?= $isPending ? 'status-pending' : 'status-verified' ?>"><?= $isPending ? 'Pending' : 'Approved' ?></span></td><td><button class="table-action-btn" type="button" data-admin-dialog="review-capability" data-name="Demo Battery and Circuit Boards" data-status="<?= $isPending ? 'Pending' : 'Approved' ?>">Review</button></td></tr><?php endif; ?>
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
