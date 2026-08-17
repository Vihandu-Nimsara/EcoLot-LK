<?php $capabilities = [
    ['category' => 'Demo Consumer Electronics', 'status' => 'APPROVED', 'class' => 'badge-completed'],
    ['category' => 'Demo Battery and Circuit Boards', 'status' => 'APPROVED', 'class' => 'badge-completed'],
    ['category' => 'Medical E-Waste', 'status' => 'PENDING', 'class' => 'badge-pending'],
]; ?>
<section class="workflow-page">
    <div class="workflow-header">
        <div>
            <h1>My Profile &amp; Compliance</h1>
            <p>Review your company information, EcoLot verification, CEA licence record, and handling capabilities.</p>
        </div>
        <div class="workflow-actions">
            <button class="secondary-workflow-btn" type="button" data-recycler-dialog="edit-profile">Edit Basic Information</button>
        </div>
    </div>

    <p class="page-notice" data-page-notice tabindex="-1" hidden></p>

    <section class="workflow-card">
        <div class="workflow-section-header"><h2>Company Information</h2></div>
        <div class="detail-grid">
            <div class="detail-item"><span class="detail-label">Company Name</span><strong class="detail-value">GreenCycle Lanka Pvt Ltd</strong></div>
            <div class="detail-item"><span class="detail-label">Contact Person</span><strong class="detail-value">Anjana Silva</strong></div>
            <div class="detail-item"><span class="detail-label">Business Email</span><strong class="detail-value">anjana@greencycle.lk</strong></div>
            <div class="detail-item"><span class="detail-label">Phone</span><strong class="detail-value">077 234 5678</strong></div>
            <div class="detail-item"><span class="detail-label">Business Address</span><strong class="detail-value">45 Green Park, Colombo 05</strong></div>
            <div class="detail-item"><span class="detail-label">District</span><strong class="detail-value">Colombo</strong></div>
            <div class="detail-item"><span class="detail-label">Verification</span><span class="badge badge-completed">Verified</span></div>
        </div>
    </section>

    <section class="workflow-card">
        <div class="workflow-section-header"><h2>CEA Licence</h2><p>Verified licence information can be changed only by submitting an update for Administrator review.</p></div>
        <div class="detail-grid">
            <div class="detail-item"><span class="detail-label">Licence Type</span><strong class="detail-value">Scheduled Waste Management Licence (SWML)</strong></div>
            <div class="detail-item"><span class="detail-label">SWML Number</span><strong class="detail-value">SWML/2026/001</strong></div>
            <div class="detail-item"><span class="detail-label">Expiry Date</span><strong class="detail-value">2027-06-30</strong></div>
            <div class="detail-item"><span class="detail-label">Licence Status</span><span class="badge badge-completed">Valid</span></div>
            <div class="detail-item"><span class="detail-label">Licence Document</span><strong class="detail-value">Submitted document preview unavailable in this frontend demo</strong></div>
        </div>
        <div class="form-actions">
            <button class="secondary-workflow-btn" type="button" data-recycler-dialog="licence-request">Submit Licence Update</button>
        </div>
    </section>

    <section class="workflow-card">
        <div class="workflow-section-header"><h2>Waste-Handling Capabilities</h2><p>Approved categories affect eligibility; all changes require Administrator review.</p></div>
        <div class="workflow-table-wrapper">
            <table class="workflow-table">
                <thead><tr><th>Waste Category</th><th>Capability Status</th><th>Action</th></tr></thead>
                <tbody><?php foreach ($capabilities as $capability): ?><tr><td><?= htmlspecialchars($capability['category']) ?></td><td><span class="badge <?= htmlspecialchars($capability['class']) ?>"><?= htmlspecialchars(ucfirst(strtolower($capability['status']))) ?></span></td><td><?php if ($capability['status'] === 'PENDING'): ?><span class="muted-action">Awaiting review</span><?php else: ?><button class="btn-action" type="button" data-recycler-dialog="capability-request" data-category="<?= htmlspecialchars($capability['category']) ?>">Request Change</button><?php endif; ?></td></tr><?php endforeach; ?></tbody>
            </table>
        </div>
        <?php if ($capabilities === []): ?><div class="empty-state">No handling capabilities are recorded.</div><?php endif; ?>
        <div class="form-actions">
            <button class="secondary-workflow-btn" type="button" data-recycler-dialog="capability-request" data-category="">Request Capability Change</button>
        </div>
    </section>
</section>
