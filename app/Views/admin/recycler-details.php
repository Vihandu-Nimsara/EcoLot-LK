<?php
$selectedId = (string) ($recyclerId ?? '');
$records = [
 '1'=>['company'=>'GreenCycle Lanka Pvt Ltd','contact'=>'Anjana Silva','email'=>'anjana@greencycle.lk','phone'=>'077 234 5678','address'=>'45 Green Park, Colombo 05','district'=>'Colombo','swml'=>'SWML/2026/001','expiry'=>'2027-06-30','status'=>'VERIFIED','licence'=>'VALID','capabilities'=>[['Demo Consumer Electronics','APPROVED'],['Demo Battery and Circuit Boards','APPROVED']]],
 '2'=>['company'=>'E-Waste Recovery Colombo','contact'=>'Nimal Perera','email'=>'nimal@ewasterecovery.lk','phone'=>'071 456 7890','address'=>'28 Recovery Road, Colombo 10','district'=>'Colombo','swml'=>'SWML/2026/002','expiry'=>'2027-04-18','status'=>'VERIFIED','licence'=>'VALID','capabilities'=>[['Office E-Waste','APPROVED']]],
 '3'=>['company'=>'SafeDispose Electronics','contact'=>'Kavindi Fernando','email'=>'kavindi@safedispose.lk','phone'=>'076 876 5432','address'=>'14 Circular Avenue, Wattala','district'=>'Gampaha','swml'=>'SWML/2026/003','expiry'=>'2027-03-22','status'=>'VERIFIED','licence'=>'VALID','capabilities'=>[['Domestic E-Waste','APPROVED']]],
 '4'=>['company'=>'Eco Recyclers Pvt Ltd','contact'=>'Tharindu Jayasinghe','email'=>'tharindu@ecorecyclers.lk','phone'=>'075 345 9087','address'=>'8 Coastal Road, Panadura','district'=>'Kalutara','swml'=>'SWML/2026/004','expiry'=>'2027-08-15','status'=>'VERIFIED','licence'=>'VALID','capabilities'=>[['Automobile E-Waste','APPROVED']]],
 '5'=>['company'=>'Ceylon Tech Recyclers','contact'=>'Isuru Bandara','email'=>'isuru@ceylontechrecyclers.lk','phone'=>'078 112 3344','address'=>'18 Industrial Road, Kandy','district'=>'Kandy','swml'=>'SWML/2026/005','expiry'=>'2027-02-10','status'=>'PENDING','licence'=>'PENDING','capabilities'=>[['Demo Consumer Electronics','PENDING'],['Demo Battery and Circuit Boards','PENDING']]],
 '6'=>['company'=>'Urban Eco Metals','contact'=>'Malith Gunawardena','email'=>'malith@urbaneco.lk','phone'=>'070 998 7766','address'=>'21 Harbour Road, Galle','district'=>'Galle','swml'=>'SWML/2026/006','expiry'=>'2026-01-12','status'=>'REJECTED','licence'=>'EXPIRED','capabilities'=>[['Demo Consumer Electronics','SUSPENDED']]],
];
$record = $records[$selectedId] ?? null;
if ($record === null): ?>
<section class="recycler-details-page"><div class="details-header"><div><span class="details-eyebrow">Recycler Verification Details</span><h1>Recycler not found</h1><p>No recycler registration matches the requested ID.</p></div><a class="header-action-btn" href="<?= htmlspecialchars($basePath) ?>/admin/recycler-verification">Back to Verification</a></div></section>
<?php return; endif;
$companyName=$record['company']; $contactPerson=$record['contact']; $email=$record['email']; $phone=$record['phone']; $address=$record['address']; $district=$record['district']; $swmlNumber=$record['swml']; $expiry=$record['expiry']; $recordStatus=$record['status'];
$isPending = $recordStatus === 'PENDING';
$isRejected = $recordStatus === 'REJECTED';
$isExpired = $expiry < date('Y-m-d');
$licenceVerified = $record['licence'] === 'VALID' && !$isExpired;
$approvedCapabilityCount = count(array_filter($record['capabilities'], static fn(array $capability): bool => $capability[1] === 'APPROVED'));
$canVerify = $isPending && $licenceVerified && $approvedCapabilityCount > 0;
$overallStatus = $isPending ? 'Pending Verification' : ($isRejected ? 'Rejected' : 'Verified');
$statusClass = $isPending ? 'status-pending' : ($isRejected ? 'status-rejected' : 'status-verified');
$licenceStatus = $isExpired ? 'Expired' : ucfirst(strtolower($record['licence']));
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
                    <?php foreach ($record['capabilities'] as [$capabilityName,$capabilityStatus]): $capabilityClass=$capabilityStatus==='APPROVED'?'status-verified':($capabilityStatus==='PENDING'?'status-pending':'status-rejected'); ?><tr><td><?= htmlspecialchars($capabilityName) ?></td><td><span class="status-badge <?= $capabilityClass ?>"><?= htmlspecialchars(ucfirst(strtolower($capabilityStatus))) ?></span></td><td><button class="table-action-btn" type="button" data-admin-dialog="review-capability" data-name="<?= htmlspecialchars($capabilityName) ?>" data-status="<?= htmlspecialchars($capabilityStatus) ?>">Review</button></td></tr><?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="details-card final-decision-card">
        <div class="details-section-header"><span class="section-step">D</span><div><h2>Final Recycler Verification</h2><p>A recycler is verified only after company information, licence compliance, and at least one capability have been reviewed.</p></div></div>
        <ul class="verification-checklist">
            <li class="requirement-met"><span aria-hidden="true">✓</span> Company information reviewed</li>
            <li class="<?= $licenceVerified?'requirement-met':'requirement-unmet' ?>"><span aria-hidden="true"><?= $licenceVerified?'✓':'○' ?></span> CEA licence record verified</li>
            <li class="<?= !$isExpired?'requirement-met':'requirement-unmet' ?>"><span aria-hidden="true"><?= !$isExpired?'✓':'○' ?></span> Licence record is not expired</li>
            <li class="<?= $approvedCapabilityCount>0?'requirement-met':'requirement-unmet' ?>"><span aria-hidden="true"><?= $approvedCapabilityCount>0?'✓':'○' ?></span> At least one capability approved</li>
        </ul>
        <div class="final-decision-note">Frontend representation only. Final verification does not automatically approve pending capabilities.</div>
        <div class="details-actions final-actions">
            <?php if ($isPending): ?>
                <button class="action-btn reject-btn" type="button" data-admin-dialog="reject-recycler" data-name="<?= htmlspecialchars($companyName) ?>">Reject Recycler</button>
                <button class="action-btn approve-btn" type="button" data-admin-dialog="approve-recycler" data-name="<?= htmlspecialchars($companyName) ?>"<?= $canVerify?'':' disabled aria-disabled="true" title="Complete licence and capability review first."' ?>>Verify Recycler</button>
                <?php if (!$canVerify): ?><p class="verification-blocked-note">Complete licence and capability review before verifying this recycler.</p><?php endif; ?>
            <?php elseif ($isRejected): ?>
                <button class="action-btn approve-btn" type="button" data-admin-dialog="reconsider-recycler" data-name="<?= htmlspecialchars($companyName) ?>">Reconsider Application</button>
            <?php else: ?>
                <span class="status-badge status-verified">Recycler Verified</span>
            <?php endif; ?>
        </div>
    </section>
</section>
