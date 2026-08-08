<section class="workflow-page">
    <div class="workflow-header">
        <div>
            <h1>My Profile &amp; Compliance</h1>
            <p>Review your company information, verification, licence and approved handling capabilities.</p>
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
        <div class="workflow-section-header"><h2>Licence Details</h2><p>Approved licence details can be changed only through a review request.</p></div>
        <div class="detail-grid">
            <div class="detail-item"><span class="detail-label">Licence Number</span><strong class="detail-value">CEA-RC-2026-001</strong></div>
            <div class="detail-item"><span class="detail-label">Expiry Date</span><strong class="detail-value">2027-06-30</strong></div>
            <div class="detail-item"><span class="detail-label">Licence Status</span><span class="badge badge-completed">Active</span></div>
        </div>
        <div class="form-actions">
            <button class="secondary-workflow-btn" type="button" data-recycler-dialog="licence-request">Request Update</button>
        </div>
    </section>

    <section class="workflow-card">
        <div class="workflow-section-header"><h2>Waste-Handling Capabilities</h2><p>Categories approved for your recycler profile.</p></div>
        <div class="workflow-table-wrapper">
            <table class="workflow-table">
                <thead><tr><th>Waste Category</th><th>High Risk</th><th>Capability Status</th><th>Action</th></tr></thead>
                <tbody>
                    <tr><td>Demo Consumer Electronics</td><td>Yes</td><td><span class="badge badge-completed">Approved</span></td><td><button class="btn-action" type="button" data-recycler-dialog="capability-request" data-category="Demo Consumer Electronics">Request Change</button></td></tr>
                    <tr><td>Demo Battery and Circuit Boards</td><td>Yes</td><td><span class="badge badge-completed">Approved</span></td><td><button class="btn-action" type="button" data-recycler-dialog="capability-request" data-category="Demo Battery and Circuit Boards">Request Change</button></td></tr>
                </tbody>
            </table>
        </div>
        <div class="form-actions">
            <button class="secondary-workflow-btn" type="button" data-recycler-dialog="capability-request" data-category="">Request Capability</button>
        </div>
    </section>
</section>
