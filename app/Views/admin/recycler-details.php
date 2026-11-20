<?php
$e=static fn($value):string=>htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$recycler=$recycler??null;
$success=Session::get('success');$error=Session::get('error');Session::forget('success');Session::forget('error');
$capabilityRequests=$capabilityRequests??[];
$capabilityScopeRequests=[];
foreach($capabilityRequests as $request) if($request['request_status']==='PENDING' && $request['request_type']!=='DEACTIVATE' && $request['capability_snapshot_json']!==null) $capabilityScopeRequests[$request['request_id']]=$request;
$capabilityCurrent=array_column($capabilities??[],null,'category_id');
$renewalRequests=$renewalRequests??[]; $renewalScopeReview=$renewalScopeReview??false;
$status=$recycler['verification_status']??'';$pending=$status==='PENDING';
$badge=static fn($state):string=>match($state){'VERIFIED','VALID','APPROVED'=>'status-verified','PENDING'=>'status-pending',default=>'status-rejected'};
$label=static fn($state):string=>ucfirst(strtolower(str_replace('_',' ',$state)));
$base=$basePath.'/admin/recycler-verification';
?>
<section class="recycler-details-page">
    <div class="details-header">
        <div><span class="details-eyebrow">Recycler verification</span>
<h1><?= $e($recycler['company_name']??'Recycler not found') ?>
</h1>
<p>Review the application, documentary scope and requested handling permissions.</p>
</div>
        <div class="details-header-actions"><?php if ($recycler): ?><span class="status-badge <?= $badge($status) ?>"><?= $e($label($status)) ?></span><?php endif; ?><a class="header-action-btn" href="<?= $e($base) ?>">Back to Verification</a>
</div>
    </div>
    <?php if (!$recycler): ?>
<p>No application matches this ID.</p>
</section><?php return; endif;
    $owner=(int)$recycler['recycler_id'];$url=$base.'/'.$owner;
    $valid=$license!==null && $license['license_status']==='VALID';
    $unexpired=$license!==null && $license['license_expiry'] >= (new DateTimeImmutable('today',new DateTimeZone('Asia/Colombo')))->format('Y-m-d');
    $documentAvailable=$documentAvailable??false;$scopeAvailable=$scopeAvailable??false;$scopeReady=$scopeReady??false;$scopes=$scopes??[];$scopeSupport=$scopeSupport??[];
    $approvedCaps=count(array_filter($capabilities,static fn($row)=>$row['capability_status']==='APPROVED'));
    $approvedActs=count(array_filter($activities,static fn($row)=>$row['activity_status']==='APPROVED'));
    $pendingCaps=count(array_filter($capabilities,static fn($row)=>$row['capability_status']==='PENDING'));
    $pendingActs=count(array_filter($activities,static fn($row)=>$row['activity_status']==='PENDING'));
    $pendingScopes=count(array_filter($scopes,static fn($row)=>$row['scope_status']==='PENDING'));
    $checks=[
        'Active Recycler account'=>($recycler['role']??'')==='RECYCLER'&&($recycler['account_status']??'')==='ACTIVE',
        'Mobile number verified'=>!empty($recycler['mobile_verified_at']),
        'Current licence approved and unexpired'=>$valid&&$unexpired&&$documentAvailable,
        'Verified current scope supports every approved permission'=>$scopeReady&&$approvedCaps>0&&$approvedActs>0,
        'At least one capability and activity approved'=>$approvedCaps>0&&$approvedActs>0,
        'All scope, capability and activity decisions completed'=>$pendingCaps===0&&$pendingActs===0&&$pendingScopes===0,
    ];$ready=!in_array(false,$checks,true);
    ?>
    <?php if ($success): ?>
<p class="alert alert-success" role="status"><?= $e($success) ?>
</p><?php endif; ?>
    <?php if ($error): ?>
<p class="alert alert-error" role="alert"><?= $e($error) ?>
</p><?php endif; ?>

    <section class="details-card" aria-labelledby="company-heading">
<div class="details-section-header">
<h2 id="company-heading">Company information</h2>
</div>
        <dl class="details-grid"><?php foreach (['Company name'=>'company_name','Contact person'=>'contact_person','Business email'=>'email','Mobile number'=>'phone','District'=>'district','Business address'=>'business_address'] as $caption=>$key): ?>
<div>
<dt class="details-label"><?= $e($caption) ?>
</dt>
<dd class="details-value"><?= $e($recycler[$key]??'Not recorded') ?>
</dd>
</div><?php endforeach; ?>
</dl>
    </section>

    <section class="details-card" id="licence-review" aria-labelledby="licence-heading">
<div class="details-section-header">
<div>
<h2 id="licence-heading"><?= in_array($status,['VERIFIED','SUSPENDED'],true) ? 'Current CEA licence' : 'Initial CEA licence' ?></h2>
<p>Review the submitted document. Licence approval alone grants no category, activity or high-risk authorization.</p>
</div>
</div>
        <?php if (!$license): ?>
<p>No licence was submitted.</p><?php else: ?>
        <dl class="details-grid">
<div>
<dt class="details-label">Licence number</dt>
<dd class="details-value"><?= $e($license['license_no']) ?>
</dd>
</div>
<div>
<dt class="details-label">Expiry date</dt>
<dd class="details-value"><?= $e($license['license_expiry']) ?>
</dd>
</div>
<div>
<dt class="details-label">Recorded status</dt>
<dd><span class="status-badge <?= $badge($license['license_status']) ?>"><?= $e($valid&&!$unexpired?'Expired approved record':$label($license['license_status'])) ?></span>
</dd>
</div>
</dl>
        <div class="details-actions"><?php if ($documentAvailable): ?><a class="header-action-btn" href="<?= $e($url) ?>/license-document" target="_blank" rel="noopener noreferrer">View submitted PDF</a><?php else: ?>
<p class="verification-blocked-note">The licence PDF is missing or invalid.</p><?php endif; ?>
        <?php if ($pending&&$license['license_status']==='PENDING'): ?>
            <form method="post" action="<?= $e($url) ?>/license-review" onsubmit="return confirm('Approve this licence document? Its scope remains unverified.');">
<input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
<input type="hidden" name="license_id" value="<?= (int)$license['license_id'] ?>">
<input type="hidden" name="decision" value="APPROVE">
<button class="action-btn approve-btn" <?= !$documentAvailable||!$unexpired?'disabled':'' ?>>Approve licence</button>
</form>
            <details class="review-editor">
<summary>Reject licence</summary>
<form class="verification-reject-form" method="post" action="<?= $e($url) ?>/license-review" onsubmit="return confirm('Record this licence rejection?');">
<input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
<input type="hidden" name="license_id" value="<?= (int)$license['license_id'] ?>">
<input type="hidden" name="decision" value="REJECT">
<label>Rejection reason<textarea name="review_note" rows="2" maxlength="500" required>
</textarea>
</label>
<button class="action-btn reject-btn">Reject licence</button>
</form>
</details>
        <?php endif; ?>
</div>
        <?php if (!empty($license['reviewed_at'])): ?>
<p class="review-record">Reviewed <?= $e($license['reviewed_at']) ?> by Administrator #<?= (int)$license['reviewed_by_admin_user_id'] ?>. <?= $e($license['review_note']??'No additional note.') ?>
</p><?php endif; ?>
        <?php endif; ?>
    </section>

    <?php if (!$pending && $renewalRequests): ?>
    <section class="details-card" id="licence-updates" aria-labelledby="licence-updates-heading">
        <div class="details-section-header"><div><h2 id="licence-updates-heading">Post-registration licence updates</h2><p>Review replacements separately from registration. A new document needs independently verified scope.</p></div></div>
        <?php foreach ($renewalRequests as $request): ?>
        <?php if ($request['request_status']!=='PENDING'): ?><details class="review-editor renewal-history"><summary><?= $e($request['requested_license_number']) ?> · <?= $e($label($request['request_status'])) ?></summary><?php endif; ?>
        <article class="scope-record"><div class="record-heading"><h3><?= $e($request['requested_license_number']) ?></h3><span class="status-badge <?= $badge($request['request_status']==='APPROVED'?'VERIFIED':$request['request_status']) ?>"><?= $e($label($request['request_status'])) ?></span></div>
            <dl class="details-grid"><div><dt class="details-label">Requested expiry</dt><dd class="details-value"><?= $e($request['requested_expiry_date']) ?></dd></div><div><dt class="details-label">Submitted</dt><dd class="details-value"><?= $e($request['submitted_at']) ?></dd></div></dl>
            <?php if ($request['explanation']): ?><p><?= $e($request['explanation']) ?></p><?php endif; ?>
            <div class="details-actions"><?php if ($request['document_sha256']): ?><a class="header-action-btn" href="<?= $e($url) ?>/license-updates/<?= (int)$request['request_id'] ?>/document" target="_blank" rel="noopener noreferrer">View replacement PDF</a><?php else: ?><p class="verification-blocked-note">Legacy request: submission identity is unavailable. Request a new submission before approval.</p><?php endif; ?>
            <?php if (!empty($request['previous_document_available'])): ?><a class="header-action-btn" href="<?= $e($url) ?>/license-updates/<?= (int)$request['request_id'] ?>/previous-document" target="_blank" rel="noopener noreferrer">Previous approved PDF</a><?php endif; ?></div>
            <?php if ($request['request_status']==='PENDING'): ?>
            <form class="verification-reject-form" method="post" action="<?= $e($url) ?>/license-updates/<?= (int)$request['request_id'] ?>/review" onsubmit="return confirm('Record this licence update decision? Approval selects this document and requires its own scope verification.');">
                <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
                <label>Review decision<select name="decision" required><option value="">Select decision</option><option value="APPROVE" <?= !$request['document_sha256']?'disabled':'' ?>>Approve replacement</option><option value="REJECT">Reject update</option></select></label>
                <label>Reviewer notes<textarea name="review_note" maxlength="500" minlength="5" rows="2" required placeholder="Explain your decision"></textarea></label>
                <button class="action-btn approve-btn">Confirm licence decision</button>
            </form>
            <?php else: ?><p class="review-record">Reviewed <?= $e($request['reviewed_at']) ?> by Administrator #<?= (int)$request['reviewed_by_admin_user_id'] ?>. <?= $e($request['review_note']) ?></p><?php endif; ?>
        </article><?php if ($request['request_status']!=='PENDING'): ?></details><?php endif; ?><?php endforeach; ?>
    </section>
    <?php endif; ?>

    <?php if (!$pending && $capabilityRequests): ?>
    <section class="details-card" id="capability-changes" aria-labelledby="capability-changes-heading">
        <div class="details-section-header"><div><h2 id="capability-changes-heading">Post-registration capability changes</h2><p>Review requested changes separately from initial registration. Pending requests leave current permissions unchanged.</p></div></div>
        <p class="form-help">Current CEA licence: <?= $e($license['license_no']??'Not selected') ?><?= $license?' · expiry '.$e($license['license_expiry']):'' ?>. Inspect the protected document above and matching current scope below.</p>
        <?php $capabilityPast=array_values(array_filter($capabilityRequests,static fn($r)=>$r['request_status']!=='PENDING')); ?>
        <?php foreach([true,false] as $pendingGroup): ?>
        <?php if(!$pendingGroup && $capabilityPast): $latest=$capabilityPast[0]; ?><p class="review-record">Latest decision: <?= $e($latest['category_name']) ?> · <?= $e($label($latest['request_type'])) ?> <span class="status-badge <?= $badge($latest['request_status']==='APPROVED'?'VERIFIED':$latest['request_status']) ?>"><?= $e($label($latest['request_status'])) ?></span> · <?= $e($latest['reviewed_at']) ?></p><?php endif; ?>
        <?php if(!$pendingGroup && $capabilityPast): ?><details class="review-editor capability-decision-history"><summary>Capability change history (<?= count($capabilityPast) ?>)</summary><?php endif; ?>
        <?php foreach($capabilityRequests as $request): if(($request['request_status']==='PENDING')!==$pendingGroup)continue; $before=json_decode($request['capability_snapshot_json']??'null',true);$held=$capabilityCurrent[$request['category_id']]??null;
            $coverage=array_filter($scopes,static fn($s)=>$license && $valid && $unexpired && !empty($license['approval_recorded']) && $documentAvailable && $s['scope_status']==='VERIFIED' && (int)$s['category_id']===(int)$request['category_id'] && (int)$s['license_id']===(int)$license['license_id'] && $s['license_number']===$license['license_no'] && $s['expiry_date']===$license['license_expiry'] && $s['document_path']===$license['document_path'] && hash_equals($s['document_sha256'],$documentHash)); ?>
        <article class="scope-record"><div class="record-heading"><h3><?= $e($request['category_name']) ?> · <?= $e($label($request['request_type'])) ?></h3><span class="status-badge <?= $badge($request['request_status']==='APPROVED'?'VERIFIED':$request['request_status']) ?>"><?= $e($label($request['request_status'])) ?></span></div>
            <dl class="details-grid"><div><dt class="details-label">Current capability</dt><dd class="details-value"><?= $e($label($held['capability_status']??'Not held')) ?> · high-risk <?= !empty($held['can_handle_high_risk'])?'Yes':'No' ?></dd></div><div><dt class="details-label">Requested state</dt><dd class="details-value"><?= $request['request_type']==='DEACTIVATE'?'Inactive':('Handling enabled · high-risk '.($request['requested_can_handle_high_risk']?'Yes':'No')) ?></dd></div><div><dt class="details-label">Submitted</dt><dd class="details-value"><?= $e($request['submitted_at']) ?></dd></div></dl>
            <p><?= $e($request['request_reason']) ?></p>
            <?php if($request['request_type']!=='DEACTIVATE'): ?><p class="form-help">Current verified category scope: <?= !$coverage?'None recorded for this document.':implode('; ',array_map(static fn($s)=>$e($label($s['activity_type'])).' · high-risk '.($s['covers_high_risk']?'Yes':'No'),$coverage)) ?></p><?php endif; ?>
            <?php if($request['obligations']): ?><p class="verification-blocked-note">Reduction blocked by outstanding obligations:</p><ul><?php foreach($request['obligations'] as $obligation): ?><li><?= $e($obligation['lot_code']) ?> · <?= $e($label($obligation['bid_status'])) ?><?= $obligation['handover_status']?' · '.$e($label($obligation['handover_status'])):'' ?></li><?php endforeach; ?></ul><?php endif; ?>
            <?php if($request['request_status']==='PENDING'): ?>
            <form class="verification-reject-form" method="post" action="<?= $e($url) ?>/capability-changes/<?= (int)$request['request_id'] ?>/review" onsubmit="return confirm('Record this capability change decision? Current scope and obligations will be checked again.');">
                <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
                <label>Decision<select name="decision" required><option value="">Choose decision</option><option value="APPROVE" <?= $request['capability_snapshot_json']===null||$request['obligations']?'disabled':'' ?>>Approve capability change</option><option value="REJECT">Reject change request</option></select></label>
                <label>Reviewer feedback<textarea name="review_note" minlength="5" maxlength="500" rows="2" required></textarea></label>
                <button class="action-btn approve-btn">Confirm capability decision</button>
            </form>
            <?php if($request['capability_snapshot_json']===null): ?><p class="verification-blocked-note">This legacy request needs a new submission before approval.</p><?php endif; ?>
            <?php else: ?><p class="review-record">Reviewed <?= $e($request['reviewed_at']) ?> by Administrator #<?= (int)$request['reviewed_by_admin_user_id'] ?>. <?= $e($request['review_note']) ?></p><?php endif; ?>
            <?php if($before): ?><details class="review-editor"><summary>Capability at submission</summary><p><?= $e($label($before['capability_status'])) ?> · high-risk <?= $before['can_handle_high_risk']?'Yes':'No' ?>. <?= $e($before['review_note']??'') ?><?php if(!empty($before['reviewed_at'])): ?> · reviewed <?= $e($before['reviewed_at']) ?> by Administrator #<?= (int)$before['reviewed_by_admin_user_id'] ?><?php endif; ?></p></details><?php endif; ?>
        </article><?php endforeach; ?>
        <?php if(!$pendingGroup && $capabilityPast): ?></details><?php endif; ?><?php endforeach; ?>
    </section><?php endif; ?>

    <section class="details-card" id="licence-scope" aria-labelledby="scope-heading">
<div class="details-section-header">
<div>
<h2 id="scope-heading">Licence scope and evidence</h2>
<p>A proposal records documentary evidence. Only a separately verified proposal can support the requested permissions.</p>
</div>
</div>
        <?php if (!$scopeAvailable): ?>
<p class="verification-blocked-note">Scope review is unavailable. Contact the system administrator before approving permissions.</p>
        <?php elseif (!$scopes): ?>
<p>No scope evidence has been recorded.</p><?php endif; ?>
        <?php if ($scopeAvailable&&($pending||$renewalScopeReview||$capabilityScopeRequests)&&$valid&&$unexpired&&$documentAvailable): ?>
<details class="review-editor">
<summary>Record scope evidence</summary>
            <form class="scope-form" method="post" action="<?= $e($url) ?>/<?= $pending?'scope':($capabilityScopeRequests?'document-scope':'renewal-scope') ?>">
                <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
<input type="hidden" name="license_id" value="<?= (int)$license['license_id'] ?>">
<input type="hidden" name="document_sha256" value="<?= $e($documentHash) ?>">
                <?php if(!$pending && $capabilityScopeRequests): ?><label>Review context<select name="capability_request_id" required><option value="">Select related request</option><?php if($renewalScopeReview): ?><option value="renewal">Current renewal — existing approved permissions</option><?php endif; ?><?php foreach($capabilityScopeRequests as $change): ?><option value="<?= (int)$change['request_id'] ?>"><?= $e($change['category_name']) ?> · <?= $e($label($change['request_type'])) ?> request</option><?php endforeach; ?></select></label><?php endif; ?>
                <?php $scopeCategories=array_column($capabilities,null,'category_id');foreach($capabilityScopeRequests as $change)$scopeCategories[$change['category_id']]=['category_id'=>$change['category_id'],'category_name'=>$change['category_name'],'capability_status'=>'PENDING']; ?>
                <div class="scope-fields">
<label>Requested category<select name="category_id" required><option value="">Select category</option><?php foreach ($scopeCategories as $cap): if (!in_array($cap['capability_status'],['PENDING','APPROVED'],true)) continue; ?><option value="<?= (int)$cap['category_id'] ?>"><?= $e($cap['category_name']) ?></option><?php endforeach; ?>
</select>
</label>
                <label>Requested activity<select name="activity_type" required><option value="">Select activity</option><?php foreach ($activities as $activity): if (!in_array($activity['activity_status'],['PENDING','APPROVED'],true)) continue; ?><option value="<?= $e($activity['activity_type']) ?>"><?= $e($label($activity['activity_type'])) ?></option><?php endforeach; ?>
</select>
</label>
                <label>Authorization basis<select name="authorization_basis" required><option value="">Select evidence</option><?php foreach (['EPL','SWML','EPL_AND_SWML','OTHER'] as $basis): ?><option value="<?= $basis ?>"><?= $e(str_replace('_',' ',$basis)) ?></option><?php endforeach; ?>
</select>
</label>
                <label>Explicit high-risk coverage<select name="covers_high_risk" required><option value="0">No / not established</option><option value="1">Yes — supported by appropriate evidence</option>
</select>
</label>
</div>
                <label>Waste description on document<textarea name="waste_description" rows="2" maxlength="500" required>
</textarea>
</label>
<label>Waste code (optional)<input name="waste_code" maxlength="120">
</label>
                <label>Supporting page, clause or permit reference<textarea name="evidence_reference" rows="2" maxlength="500" required>
</textarea>
</label>
<label>Conditions, or explicitly state none recorded<textarea name="licence_conditions" rows="2" maxlength="1000" required>
</textarea>
</label>
                <p class="form-help">Do not infer high-risk authorization from a generic category or EPL alone. Inspect the actual document and supporting authorization.</p>
<button class="action-btn approve-btn">Record pending proposal</button>
            </form>
</details><?php endif; ?>
        <?php foreach ($scopes as $scope): $currentScope=$valid && $unexpired && $license && (int)$scope['license_id']===(int)$license['license_id'] && isset($documentHash) && hash_equals($scope['document_sha256'],$documentHash) && $scope['license_number']===$license['license_no'] && $scope['expiry_date']===$license['license_expiry'] && $scope['document_path']===$license['document_path']; ?>
<article class="scope-record">
<div class="record-heading">
<h3><?= $e($scope['category_name']) ?> · <?= $e($label($scope['activity_type'])) ?>
</h3><span class="status-badge <?= $badge($scope['scope_status']==='VERIFIED'?'APPROVED':$scope['scope_status']) ?>"><?= $e($label($scope['scope_status'])) ?></span>
</div>
            <p><?= $e(str_replace('_',' ',$scope['authorization_basis'])) ?> · High-risk: <?= $scope['covers_high_risk']?'Explicitly covered':'Not established' ?> · <?= $e($scope['waste_description']) ?>
</p>
            <?php if (!$currentScope): ?>
<p class="verification-blocked-note">Historical or changed document — this record cannot authorize current permissions.</p><?php endif; ?>
            <details class="review-editor evidence-record">
                <summary>Evidence and review record</summary>
            <p>Reference: <?= $e($scope['evidence_reference']) ?>. Conditions: <?= $e($scope['licence_conditions']) ?><?= $scope['waste_code']!==null?' · Code: '.$e($scope['waste_code']):'' ?>
</p>
            <p class="review-record">Licence <?= $e($scope['license_number']) ?> · expiry <?= $e($scope['expiry_date']) ?> · document fingerprint <?= $e(substr($scope['document_sha256'],0,12)) ?>. Proposed <?= $e($scope['proposed_at']) ?> by Administrator #<?= (int)$scope['proposed_by_admin_user_id'] ?>.</p>
                <?php if (!empty($scope['reviewed_at'])): ?><p class="review-record">Reviewed <?= $e($scope['reviewed_at']) ?> by Administrator #<?= (int)$scope['reviewed_by_admin_user_id'] ?>. <?= $e($scope['review_note']) ?></p><?php endif; ?>
            </details>
            <a class="header-action-btn" href="<?= $e($url) ?>/scope/<?= (int)$scope['scope_id'] ?>/document" target="_blank" rel="noopener noreferrer">View exact evidence PDF</a>
            <?php $relatedChange=null;foreach($capabilityScopeRequests as $change)if((int)$change['category_id']===(int)$scope['category_id']){$relatedChange=$change;break;} ?>
            <?php if (($pending||$renewalScopeReview||$relatedChange)&&$scope['scope_status']==='PENDING'): ?>
<details class="review-editor">
<summary>Review proposed scope</summary>
<form class="verification-reject-form" method="post" action="<?= $e($url) ?>/<?= $pending?'scope':($relatedChange?'capability-scope':'renewal-scope') ?>/<?= (int)$scope['scope_id'] ?>/review" onsubmit="return confirm('Record this scope decision?');">
<input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
<?php if(!$pending && $relatedChange): ?><input type="hidden" name="capability_request_id" value="<?= (int)$relatedChange['request_id'] ?>"><?php endif; ?>
<label>Decision<select name="decision" required><option value="">Select decision</option><option value="VERIFIED">Verify supported scope</option><option value="REJECTED">Reject proposal</option>
</select>
</label>
<label class="check-label">
<input type="checkbox" name="evidence_confirmed" value="1"> I inspected the exact document and supporting authorization (required for verification).</label>
<label>Review note<textarea name="review_note" rows="2" maxlength="500" required>
</textarea>
</label>
<button class="action-btn approve-btn">Record scope decision</button>
</form>
</details>
            <?php endif; ?>
        </article><?php endforeach; ?>
    </section>

    <?php foreach (['capability','activity'] as $type): $category=$type==='capability';$rows=$category?$capabilities:$activities;$heading=$category?'Initial capability reviews':'Authorized activity reviews'; ?>
    <section class="details-card" aria-labelledby="<?= $type ?>-heading">
<div class="details-section-header">
<div>
<h2 id="<?= $type ?>-heading"><?= $heading ?>
</h2>
<p><?= $category?'Waste categories and high-risk permissions':'Operational activities' ?> are reviewed separately against verified licence scope.</p>
</div>
</div>
        <?php if (!$rows): ?>
<p>No <?= $category?'capabilities':'activities' ?> were requested.</p><?php else: ?>
<div class="data-table-wrapper">
<table class="report-table">
<thead>
<tr>
<th scope="col"><?= $category?'Waste category':'Handling activity' ?>
</th><?php if ($category): ?>
<th scope="col">High-risk</th><?php endif; ?>
<th scope="col">Status and review</th>
</tr>
</thead>
<tbody>
        <?php foreach ($rows as $row): $state=$row[$type.'_status'];$id=(int)$row[$category?'category_id':'activity_id'];$supported=!empty($scopeSupport[$category?'category':'activity'][$id]); ?>
            <tr>
<td data-label="<?= $category?'Category':'Activity' ?>"><?= $e($category?$row['category_name']:$label($row['activity_type'])) ?>
</td><?php if ($category): ?>
<td data-label="High-risk"><?= $row['can_handle_high_risk']?($row['capability_status']==='APPROVED'?'Approved':'Requested'):'No' ?>
</td><?php endif; ?>
                <td data-label="Status"><span class="status-badge <?= $badge($state) ?>"><?= $e($label($state)) ?></span><?php if (!empty($row['reviewed_at'])): ?>
<p class="review-record"><?= $e($row['reviewed_at']) ?> · Administrator #<?= (int)$row['reviewed_by_admin_user_id'] ?>. <?= $e($row['review_note']??'No additional note.') ?>
</p><?php endif; ?>
</td>
            </tr><?php endforeach; ?>
</tbody>
</table>
</div>
<?php if ($pending): ?>
<div class="permission-reviews">
<?php foreach ($rows as $row): $state=$row[$type.'_status']; if ($state!=='PENDING') continue;
    $id=(int)$row[$category?'category_id':'activity_id'];$supported=!empty($scopeSupport[$category?'category':'activity'][$id]); ?>
<details class="review-editor permission-review">
    <summary>Review <?= $category?$e($row['category_name']):$e($label($row['activity_type'])) ?> <span class="status-badge <?= $badge($state) ?>"><?= $e($label($state)) ?></span></summary>
    <div class="permission-review-body">
        <?php if ($category): ?><p class="permission-request">High-risk permission: <strong><?= $row['can_handle_high_risk']?'Requested':'Not requested' ?></strong></p><?php endif; ?>
<form class="verification-reject-form" method="post" action="<?= $e($url) ?>/<?= $type ?>-review" onsubmit="return confirm('Record this permission decision?');">
<input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
<input type="hidden" name="<?= $category?'category_id':'activity_id' ?>" value="<?= $id ?>">
<label>Decision<select name="decision" required aria-describedby="<?= $type ?>-scope-<?= $id ?>"><option value="">Select decision</option><option value="APPROVE" <?= !$supported?'disabled':'' ?>>Approve supported permission</option><option value="REJECT">Reject request</option>
</select>
</label>
<label>Review note<textarea name="review_note" rows="2" maxlength="500" required>
</textarea>
</label><?php if (!$supported): ?>
<p class="verification-blocked-note" id="<?= $type ?>-scope-<?= $id ?>">Approval unavailable: matching verified current licence scope is missing. You can still reject this request.</p><?php else: ?><p class="form-help" id="<?= $type ?>-scope-<?= $id ?>">Matching verified licence scope supports approval of this request.</p><?php endif; ?>
<button class="action-btn secondary-btn-style">Confirm review decision</button>
</form>
    </div>
</details>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php endif; ?>
    </section><?php endforeach; ?>

    <section class="details-card" id="final-verification" aria-labelledby="final-heading">
<div class="details-section-header">
<div>
<h2 id="final-heading"><?= $pending?'Final registration readiness':'Registration decision' ?>
</h2>
<p><?= $pending?'Every prerequisite is rechecked by the server when approving.':'Initial verification is closed; recorded permissions and evidence are shown above.' ?>
</p>
</div>
</div>
        <?php if ($pending): ?>
<ul class="verification-checklist"><?php foreach ($checks as $caption=>$met): ?>
<li class="<?= $met?'requirement-met':'requirement-unmet' ?>"><span aria-hidden="true"><?= $met?'✓':'○' ?></span><?= $e($caption) ?>
</li><?php endforeach; ?>
</ul>
        <div class="final-review-actions">
<form method="post" action="<?= $e($url) ?>/review" onsubmit="return confirm('Verify this Recycler after completing all reviews?');">
<input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
<input type="hidden" name="decision" value="APPROVE">
<button class="action-btn approve-btn" <?= !$ready?'disabled':'' ?>>Verify Recycler</button><?php if (!$ready): ?>
<p class="verification-blocked-note">Complete the unmet requirements first.</p><?php endif; ?>
</form>
        <details class="review-editor">
<summary>Reject application</summary>
<form class="verification-reject-form" method="post" action="<?= $e($url) ?>/review" onsubmit="return confirm('Reject this registration? The decision and reason will be recorded.');">
<input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
<input type="hidden" name="decision" value="REJECT">
<label>Rejection reason<textarea name="rejection_reason" rows="2" maxlength="500" required>
</textarea>
</label>
<button class="action-btn reject-btn">Reject Recycler</button>
</form>
</details>
</div>
        <?php else: ?>
<p><span class="status-badge <?= $badge($status) ?>"><?= $e($label($status)) ?></span>
</p>
<p>Reviewed <?= $e($recycler['reviewed_at']??'Date not recorded') ?><?= isset($recycler['reviewed_by_admin_user_id'])?' by Administrator #'.(int)$recycler['reviewed_by_admin_user_id']:'' ?>.</p>
<p><?= $e($recycler['review_note']??'No additional review note.') ?>
</p><?php endif; ?>
    </section>
</section>
