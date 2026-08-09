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
            <div class="detail-item"><span class="detail-label">Verification</span><span class="badge badge-completed">Verified</span></div>
        </div>
    </section>

    <section class="workflow-card">
        <div class="workflow-section-header"><h2>CEA Licence</h2><p>Verified licence information can be changed only by submitting an update for Administrator review.</p></div>
        <div class="detail-grid">
            <div class="detail-item"><span class="detail-label">Licence Type</span><strong class="detail-value">Scheduled Waste Management Licence (SWML)</strong></div>
            <div class="detail-item"><span class="detail-label">SWML Number</span><strong class="detail-value">SWML/2026/001</strong></div>
            <div class="detail-item"><span class="detail-label">Expiry Date</span><strong class="detail-value">2027-06-30</strong></div>
            <div class="detail-item"><span class="detail-label">Licence Verification Status</span><span class="badge badge-completed">Verified</span></div>
            <div class="detail-item"><span class="detail-label">Activities Recorded</span><strong class="detail-value">Recovery, Recycling, Storage</strong></div>
            <div class="detail-item"><span class="detail-label">Licence Document</span><strong class="detail-value">PDF evidence submitted</strong></div>
        </div>
        <div class="form-actions">
            <button class="secondary-workflow-btn" type="button" data-recycler-dialog="licence-request">Submit Licence Update</button>
        </div>
    </section>

    <section class="workflow-card">
        <div class="workflow-section-header"><h2>Waste-Handling Capabilities</h2><p>Approved categories affect eligibility; all changes require Administrator review.</p></div>
        <div class="workflow-table-wrapper">
            <table class="workflow-table">
                <thead><tr><th>Waste Category</th><th>High Risk</th><th>Capability Status</th><th>Action</th></tr></thead>
                <tbody>
                    <tr><td>Demo Consumer Electronics</td><td>Yes</td><td><span class="badge badge-completed">Approved</span></td><td><button class="btn-action" type="button" data-recycler-dialog="capability-request" data-category="Demo Consumer Electronics">Request Change</button></td></tr>
                    <tr><td>Demo Battery and Circuit Boards</td><td>Yes</td><td><span class="badge badge-completed">Approved</span></td><td><button class="btn-action" type="button" data-recycler-dialog="capability-request" data-category="Demo Battery and Circuit Boards">Request Change</button></td></tr>
                    <tr><td>Medical E-Waste</td><td>Yes</td><td><span class="badge badge-pending">Pending</span></td><td><span class="muted-action">Awaiting review</span></td></tr>
                </tbody>
            </table>
        </div>
        <div class="form-actions">
            <button class="secondary-workflow-btn" type="button" data-recycler-dialog="capability-request" data-category="">Request Capability Change</button>
        </div>
    </section>
</section>
