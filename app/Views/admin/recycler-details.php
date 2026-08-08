<?php
$selectedId = (string) ($recyclerId ?? '5');
$isPending = $selectedId === '5';
$isRejected = $selectedId === '6';
$status = $isPending ? 'PENDING' : ($isRejected ? 'REJECTED' : 'VERIFIED');
$statusClass = $isPending ? 'status-pending' : ($isRejected ? 'status-rejected' : 'status-verified');
$companyName = $isPending ? 'Ceylon Tech Recyclers' : ($isRejected ? 'Urban Eco Metals' : 'GreenCycle Lanka Pvt Ltd');
$contactPerson = $isPending ? 'Isuru Bandara' : ($isRejected ? 'Malith Gunawardena' : 'Anjana Silva');
$email = $isPending ? 'isuru@ceylontechrecyclers.lk' : ($isRejected ? 'malith@urbaneco.lk' : 'anjana@greencycle.lk');
$phone = $isPending ? '078 112 3344' : ($isRejected ? '070 998 7766' : '077 234 5678');
?>
<section class="recycler-details-page">
    <div class="details-header">
        <div><h1>Recycler Details &amp; Compliance</h1><p>Review company, verification, licence and capability information.</p></div>
        <a class="header-action-btn" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/admin/recycler-verification">Back to Verification</a>
    </div>

    <p class="page-notice" data-page-notice tabindex="-1" hidden></p>

    <section class="details-card">
        <div class="details-section-header"><h2>Company Information</h2></div>
        <div class="details-grid">
            <div class="details-item"><span class="details-label">Company Name</span><strong class="details-value"><?= htmlspecialchars($companyName) ?></strong></div>
            <div class="details-item"><span class="details-label">Contact Person</span><strong class="details-value"><?= htmlspecialchars($contactPerson) ?></strong></div>
            <div class="details-item"><span class="details-label">Email</span><strong class="details-value"><?= htmlspecialchars($email) ?></strong></div>
            <div class="details-item"><span class="details-label">Phone</span><strong class="details-value"><?= htmlspecialchars($phone) ?></strong></div>
            <div class="details-item"><span class="details-label">Business Address</span><strong class="details-value"><?= $isPending ? '18 Industrial Road, Kandy' : '45 Green Park, Colombo 05' ?></strong></div>
            <div class="details-item"><span class="details-label">Recycler ID</span><strong class="details-value">#<?= htmlspecialchars($selectedId) ?></strong></div>
        </div>
    </section>

    <section class="details-card">
        <div class="details-section-header"><h2>Verification</h2><p>Verification actions below represent the future review workflow only.</p></div>
        <div class="details-grid">
            <div class="details-item"><span class="details-label">Verification Status</span><span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars(ucwords(strtolower($status))) ?></span></div>
            <div class="details-item"><span class="details-label">Submitted At</span><strong class="details-value"><?= $isPending ? '2026-07-09 08:55:00' : '2026-07-02 09:30:00' ?></strong></div>
            <div class="details-item"><span class="details-label">Review Notes</span><strong class="details-value"><?= $isPending ? 'Awaiting administrator review.' : 'Licence and company information reviewed.' ?></strong></div>
        </div>
        <?php if ($isPending): ?>
            <div class="details-actions"><button class="action-btn approve-btn" type="button" data-admin-dialog="approve-recycler" data-name="<?= htmlspecialchars($companyName) ?>">Approve Recycler</button><button class="action-btn reject-btn" type="button" data-admin-dialog="reject-recycler" data-name="<?= htmlspecialchars($companyName) ?>">Reject Recycler</button></div>
        <?php endif; ?>
        <?php if ($isRejected): ?><div class="details-actions"><button class="action-btn approve-btn" type="button" data-admin-dialog="reconsider-recycler" data-name="<?= htmlspecialchars($companyName) ?>">Reconsider Application</button></div><?php endif; ?>
    </section>

    <section class="details-card">
        <div class="details-section-header"><h2>Licence</h2><p>Licence controls are frontend-only and do not change approved records.</p></div>
        <div class="details-grid">
            <div class="details-item"><span class="details-label">Licence Number</span><strong class="details-value"><?= $isPending ? 'CEA-RC-2026-005' : 'CEA-RC-2026-001' ?></strong></div>
            <div class="details-item"><span class="details-label">Expiry Date</span><strong class="details-value"><?= $isPending ? '2027-02-10' : '2027-06-30' ?></strong></div>
            <div class="details-item"><span class="details-label">Licence Status</span><span class="status-badge <?= $isPending ? 'status-pending' : 'status-verified' ?>"><?= $isPending ? 'Pending Review' : 'Active' ?></span></div>
        </div>
        <div class="compliance-actions"><button class="table-action-btn" type="button" data-admin-dialog="licence-status" data-action="Update Status">Verify / Update Status</button><button class="action-btn reject-btn" type="button" data-admin-dialog="licence-status" data-action="Deactivate">Deactivate</button></div>
    </section>

    <section class="details-card">
        <div class="details-section-header"><h2>Waste-Handling Capabilities</h2><p>Review requested and approved waste categories.</p></div>
        <div class="data-table-wrapper"><table class="categories-table capabilities-table"><thead><tr><th>Waste Category</th><th>Capability Status</th><th>Actions</th></tr></thead><tbody><tr><td>Demo Consumer Electronics</td><td><span class="status-badge <?= $isPending ? 'status-pending' : 'status-verified' ?>"><?= $isPending ? 'Pending' : 'Approved' ?></span></td><td><div class="compliance-actions"><button class="table-action-btn" type="button" data-admin-dialog="capability-action" data-action="Approve" data-name="Demo Consumer Electronics">Approve</button><button class="action-btn reject-btn" type="button" data-admin-dialog="capability-action" data-action="Reject" data-name="Demo Consumer Electronics">Reject</button><button class="table-action-btn" type="button" data-admin-dialog="capability-action" data-action="Deactivate" data-name="Demo Consumer Electronics">Deactivate</button></div></td></tr></tbody></table></div>
    </section>
</section>
