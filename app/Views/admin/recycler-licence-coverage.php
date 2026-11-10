<?php
$coverageEscape=static fn($v):string=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$licenceCoverage=$licenceCoverage??[]; $coverageSource=$coverageSource??null;
$coverageActivities=RecyclerRegistrationService::ACTIVITIES;
$coverageBase=$basePath.'/admin/recycler-verification/'.(int)$recycler['recycler_id'];
?>
<section class="details-card licence-coverage-section">
    <div class="details-section-header"><div><h2>Licence Coverage Verification</h2><p>Validity and scope are separate. Inspect the specific protected PDF and supporting evidence. EcoLot records verification; it does not issue CEA authorization.</p></div></div>
    <p>EPL alone does not establish Scheduled Waste authorization. Application categories and HIGH risk are not automatic CEA waste-code classifications. Record only descriptions, codes, activities and conditions supported by the evidence.</p>
    <?php if (!$coverageSource): ?><p class="verification-blocked-note">Approve the overall licence document first. This does not automatically authorize any category or activity.</p>
    <?php else: ?>
        <p>Current source: <?= $coverageEscape($coverageSource['license_number']) ?>, version <?= (int)$coverageSource['document_version'] ?>, expiry <?= $coverageEscape($coverageSource['expiry_date']) ?>.</p>
        <a class="header-action-btn" target="_blank" rel="noopener" href="<?= $coverageEscape($coverageBase) ?>/licence-source/<?= (int)$coverageSource['license_id'] ?>/<?= (int)$coverageSource['document_version'] ?>/document">Open Current Protected PDF</a>
        <details class="capability-change-request"><summary>Record a pending coverage proposal</summary><p>A proposal grants no permission. Renewed/replaced documents require new verification.</p>
            <form class="verification-reject-form" method="post" action="<?= $coverageEscape($coverageBase) ?>/licence-coverage">
                <input type="hidden" name="_csrf_token" value="<?= $coverageEscape($csrfToken??'') ?>"><input type="hidden" name="license_id" value="<?= (int)$coverageSource['license_id'] ?>"><input type="hidden" name="document_version" value="<?= (int)$coverageSource['document_version'] ?>">
                <label class="form-group">Application category<select name="category_id" required><option value="">Select supported category</option><?php foreach (($coverageCategories??[]) as $category): if ($category['category_status']!=='ACTIVE') continue; ?><option value="<?= (int)$category['category_id'] ?>"><?= $coverageEscape($category['category_name']) ?></option><?php endforeach; ?></select></label>
                <label class="form-group">Authorized activity<select name="activity_type" required><option value="">Select activity</option><?php foreach ($coverageActivities as $key=>$label): ?><option value="<?= $coverageEscape($key) ?>"><?= $coverageEscape($label) ?></option><?php endforeach; ?></select></label>
                <label class="form-group">Evidence basis<select name="authorization_basis" required><option value="">Select basis</option><option value="EPL">EPL</option><option value="SWML">SWML</option><option value="EPL_AND_SWML">EPL and SWML</option><option value="OTHER">Other authorization — explain in reference</option></select></label>
                <label class="form-group">Explicit application HIGH-risk coverage<select name="covers_high_risk" required><option value="0">No / not established</option><option value="1">Yes — supported by appropriate authorization</option></select></label>
                <label class="form-group">Waste description on document<textarea name="waste_description" maxlength="500" rows="2" required></textarea></label>
                <label class="form-group">Waste code exactly as recorded (optional)<input name="document_waste_code" maxlength="120"></label>
                <label class="form-group">Reference: page, clause, permit, supporting evidence<textarea name="evidence_reference" maxlength="500" rows="3" required></textarea></label>
                <label class="form-group">Relevant conditions, or explicitly state none recorded<textarea name="licence_conditions" maxlength="1000" rows="3" required></textarea></label>
                <button class="action-btn approve-btn" type="submit">Save Pending Coverage Proposal</button>
            </form>
        </details>
    <?php endif; ?>
    <?php if (!$licenceCoverage): ?><p class="verification-blocked-note">Legacy review required. Prior EcoLot capability approval is not proof of CEA scope. No coverage is automatically verified.</p><?php endif; ?>
    <?php foreach ($licenceCoverage as $scope): ?>
        <details class="capability-change-request" <?= $scope['verification_status']==='PENDING'?'open':'' ?>>
            <summary><?= $coverageEscape($scope['category_name'].' — '.$scope['activity_type'].' — '.$scope['verification_status']) ?><?= !(int)$scope['current_document']?' — Historical / re-verification required':'' ?><?= $scope['superseded_at']!==null?' — Superseded scope':'' ?></summary>
            <dl class="details-grid">
                <div><dt>Source</dt><dd><?= $coverageEscape($scope['license_number']) ?>, version <?= (int)$scope['document_version'] ?>, expiry <?= $coverageEscape($scope['expiry_date']) ?></dd></div>
                <div><dt>Basis / high-risk</dt><dd><?= $coverageEscape($scope['authorization_basis']) ?> / <?= (int)$scope['covers_high_risk']===1?'Explicitly covered':'Not established' ?></dd></div>
                <div><dt>Description / document code</dt><dd><?= $coverageEscape($scope['waste_description']) ?> / <?= $coverageEscape($scope['document_waste_code']??'Not recorded') ?></dd></div>
                <div><dt>Evidence reference</dt><dd><?= $coverageEscape($scope['evidence_reference']) ?></dd></div>
                <div><dt>Conditions</dt><dd><?= $coverageEscape($scope['licence_conditions']) ?></dd></div>
                <div><dt>Review</dt><dd><?= $coverageEscape($scope['review_note']??'Awaiting review') ?> <?= $coverageEscape($scope['reviewed_at']??'') ?></dd></div>
            </dl>
            <a class="header-action-btn" target="_blank" rel="noopener" href="<?= $coverageEscape($coverageBase) ?>/licence-evidence/<?= (int)$scope['evidence_id'] ?>/document">Open Exact Reviewed Evidence</a>
            <?php if ($scope['verification_status']==='PENDING'): ?>
                <form class="verification-reject-form" method="post" action="<?= $coverageEscape($coverageBase) ?>/licence-coverage/<?= (int)$scope['coverage_id'] ?>/review">
                    <input type="hidden" name="_csrf_token" value="<?= $coverageEscape($csrfToken??'') ?>">
                    <label class="form-group">Decision<select name="decision" required><option value="">Select decision</option><?php if ((int)$scope['current_document']===1): ?><option value="VERIFIED">Verify this supported scope</option><?php endif; ?><option value="UNCERTAIN">Unclear — more evidence needed</option><option value="REJECTED">Reject proposed coverage</option></select></label>
                    <label><input type="checkbox" name="evidence_confirmed" value="1"> I inspected this exact document and supporting authorization (required when verifying).</label>
                    <label class="form-group">Rationale / unresolved uncertainty<textarea name="review_note" maxlength="500" rows="3" required></textarea></label>
                    <button class="action-btn approve-btn" type="submit">Record Coverage Decision</button>
                </form>
            <?php elseif ($scope['verification_status']==='VERIFIED' && $scope['superseded_at']===null): ?>
                <form class="verification-reject-form" method="post" action="<?= $coverageEscape($coverageBase) ?>/licence-coverage/<?= (int)$scope['coverage_id'] ?>/review">
                    <input type="hidden" name="_csrf_token" value="<?= $coverageEscape($csrfToken??'') ?>"><input type="hidden" name="decision" value="REVOKED">
                    <label class="form-group">Revocation reason<textarea name="review_note" maxlength="500" rows="2" required></textarea></label><button class="action-btn reject-btn" type="submit">Revoke Coverage — Block New Authorization</button>
                </form>
            <?php endif; ?>
        </details>
    <?php endforeach; ?>
    <details class="capability-change-request"><summary>Coverage decision audit</summary><?php foreach (($coverageAudit??[]) as $event): ?><p>Scope #<?= (int)$event['coverage_id'] ?> — <?= $coverageEscape($event['decision']) ?> by Administrator #<?= (int)$event['admin_user_id'] ?> at <?= $coverageEscape($event['reviewed_at']) ?>: <?= $coverageEscape($event['review_note']) ?></p><?php endforeach; ?></details>
    <?php if (!empty($licenceConflicts)): ?><h3>Existing bid / award authorization conflicts</h3><p>These records were preserved and need Officer / Admin review. No bid, award or handover was cancelled.</p><?php foreach ($licenceConflicts as $conflict): ?><p><?= $coverageEscape($conflict['lot_code']) ?> — bid #<?= (int)$conflict['bid_id'] ?>: <?= $coverageEscape($conflict['bid_status']) ?>, handover <?= $coverageEscape($conflict['handover_status']??'Not recorded') ?></p><?php endforeach; endif; ?>
</section>
<section class="details-card licence-coverage-section">
    <h2>E-Lot Required Handling Activities</h2><p>The existing workflow has no universal activity requirement. Record each lot's actual required operations. Unconfigured lots fail closed. This does not grant CEA authorization.</p>
    <?php foreach (($coverageLots??[]) as $coverageLot): ?><details class="capability-change-request"><summary><?= $coverageEscape($coverageLot['lot_code'].' — '.$coverageLot['title']) ?> — <?= $coverageLot['required_activities']?$coverageEscape($coverageLot['required_activities']):'Activity review required' ?></summary>
        <form class="verification-reject-form" method="post" action="<?= $coverageEscape($coverageBase) ?>/lot-activities">
            <input type="hidden" name="_csrf_token" value="<?= $coverageEscape($csrfToken??'') ?>"><input type="hidden" name="e_lot_id" value="<?= (int)$coverageLot['e_lot_id'] ?>"><input type="hidden" name="requirement_version" value="<?= (int)$coverageLot['requirement_version'] ?>">
            <fieldset><legend>Actual required operations</legend><?php foreach ($coverageActivities as $key=>$label): ?><label><input type="checkbox" name="required_activities[]" value="<?= $coverageEscape($key) ?>" <?= in_array($key,explode(',',$coverageLot['required_activities']??''),true)?'checked':'' ?>> <?= $coverageEscape($label) ?></label><?php endforeach; ?></fieldset>
            <label class="form-group">Rationale from lot composition and handling workflow<textarea name="review_note" maxlength="500" rows="3" required></textarea></label><button class="action-btn approve-btn" type="submit">Confirm Required Activities</button>
        </form>
    </details><?php endforeach; ?>
</section>
