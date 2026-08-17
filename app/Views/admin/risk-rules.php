<?php
$riskRules = [
    ['id' => 50, 'item' => 'Laboratory Analyzer', 'category' => 'Medical E-Waste', 'condition' => 'Biohazard contamination', 'risk' => 'HIGH', 'action' => 'Reject Collection', 'note' => 'Requires specialist handling outside the normal E-Waste process.', 'status' => 'ACTIVE'],
    ['id' => 49, 'item' => 'CRT Monitor', 'category' => 'Office E-Waste', 'condition' => 'Broken phosphor tube', 'risk' => 'HIGH', 'action' => 'Flag for Officer Review', 'note' => 'Controlled materials require an officer decision.', 'status' => 'ACTIVE'],
    ['id' => 48, 'item' => 'Smoke Detector', 'category' => 'Domestic E-Waste', 'condition' => 'Radioactive source', 'risk' => 'HIGH', 'action' => 'Reject Collection', 'note' => 'Specialized handling procedures are required.', 'status' => 'ACTIVE'],
    ['id' => 47, 'item' => 'X-Ray Equipment', 'category' => 'Medical E-Waste', 'condition' => 'Damaged shielding', 'risk' => 'HIGH', 'action' => 'Flag for Officer Review', 'note' => 'Municipal Officer review is required before collection.', 'status' => 'ACTIVE'],
    ['id' => 46, 'item' => 'Lithium-ion Battery', 'category' => 'Battery and Circuit Boards', 'condition' => 'Swollen or leaking', 'risk' => 'HIGH', 'action' => 'Flag for Officer Review', 'note' => 'Municipal Officer decides the appropriate collection path.', 'status' => 'INACTIVE'],
];
?>
<section class="risk-rules-page">
    <div class="risk-page-intro"><h1>Risk Rule Management</h1><p>Configure collection guidance. Hazardous case decisions remain with Municipal Officers.</p></div>
    <p class="page-notice" data-page-notice tabindex="-1" hidden></p>
    <section class="risk-card create-rule-card">
        <div class="risk-card-header"><div><h2>Create Risk Rule</h2><p>Select a category to find the specific E-Waste item that the rule will reference.</p></div></div>
        <button class="create-rule-btn" type="button" data-admin-dialog="create-rule">Create Risk Rule</button>
    </section>
    
    
    <section class="risk-card rules-list-card">
        <div class="risk-card-header"><div><h2>Risk Rules</h2><p>Review current collection guidance and its frontend management actions.</p></div></div>
        <form class="light-filter" data-client-filter data-rows="[data-rule-row]" data-empty="[data-rule-filter-empty]" data-result="[data-rule-filter-result]"><div class="quick-filters" role="group" aria-label="Filter rules by risk level"><button class="quick-filter" type="button" aria-pressed="true" data-filter-name="risk" data-filter-value="">All Risk</button><button class="quick-filter" type="button" aria-pressed="false" data-filter-name="risk" data-filter-value="LOW">Low</button><button class="quick-filter" type="button" aria-pressed="false" data-filter-name="risk" data-filter-value="MEDIUM">Medium</button><button class="quick-filter" type="button" aria-pressed="false" data-filter-name="risk" data-filter-value="HIGH">High</button></div><div class="light-filter-controls"><div class="filter-field"><label for="rule-status">Status</label><select id="rule-status" name="status"><option value="">All Statuses</option><option value="ACTIVE">Active</option><option value="INACTIVE">Inactive</option></select></div><div class="filter-field"><label for="rule-search">Search rules</label><input id="rule-search" name="search" type="search" placeholder="Rule text or condition"></div></div></form><p class="filter-result" data-rule-filter-result role="status" aria-live="polite"></p>
        <div class="risk-table-wrapper">
            <table class="risk-table">
                <thead><tr><th>Item</th><th>Category</th><th>Condition</th><th>Risk</th><th>Action</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($riskRules as $rule): ?>
                    <tr data-rule-row data-search="<?= htmlspecialchars($rule['item'].' '.$rule['category'].' '.$rule['condition'].' '.$rule['note']) ?>" data-risk="<?= $rule['risk'] ?>" data-status="<?= $rule['status'] ?>">
                        <td><strong><?= htmlspecialchars($rule['item']) ?></strong><br><span class="table-secondary-text">Rule #<?= htmlspecialchars((string) $rule['id']) ?></span></td>
                        <td><?= htmlspecialchars($rule['category']) ?></td>
                        <td><?= htmlspecialchars($rule['condition']) ?></td>
                        <td><span class="risk-badge risk-high"><?= htmlspecialchars(ucfirst(strtolower($rule['risk']))) ?></span></td>
                        <td><strong><?= htmlspecialchars($rule['action']) ?></strong><br><?= htmlspecialchars($rule['note']) ?></td>
                        <td><span class="status-badge <?= $rule['status'] === 'ACTIVE' ? 'status-active' : 'status-rejected' ?>"><?= htmlspecialchars(ucfirst(strtolower($rule['status']))) ?></span></td>
                        <td><div class="rule-actions"><button class="table-action-btn" type="button" data-admin-dialog="view-rule" data-rule-id="<?= htmlspecialchars((string) $rule['id']) ?>" data-name="<?= htmlspecialchars($rule['item']) ?>" data-category="<?= htmlspecialchars($rule['category']) ?>" data-risk="<?= htmlspecialchars($rule['risk']) ?>" data-status="<?= htmlspecialchars($rule['status']) ?>" data-note="<?= htmlspecialchars($rule['condition'].' — '.$rule['action'].'. '.$rule['note']) ?>">View</button><button class="table-action-btn" type="button" data-admin-dialog="edit-rule" data-rule-id="<?= htmlspecialchars((string) $rule['id']) ?>" data-name="<?= htmlspecialchars($rule['item']) ?>" data-category="<?= htmlspecialchars($rule['category']) ?>" data-condition="<?= htmlspecialchars($rule['condition']) ?>" data-risk="<?= htmlspecialchars($rule['risk']) ?>" data-note="<?= htmlspecialchars($rule['note']) ?>">Edit</button><button class="table-action-btn" type="button" data-admin-dialog="rule-status" data-rule-id="<?= htmlspecialchars((string) $rule['id']) ?>" data-action="<?= $rule['status'] === 'ACTIVE' ? 'Deactivate' : 'Activate' ?>"><?= $rule['status'] === 'ACTIVE' ? 'Deactivate' : 'Activate' ?></button></div></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="filtered-empty-state" data-rule-filter-empty hidden>No risk rules match the selected filters.</div>
    </section>
</section>
