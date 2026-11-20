<?php
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$label = static fn ($value): string => ucfirst(strtolower(str_replace('_', ' ', (string) $value)));
$badge = static fn ($state): string => match ($state) {
    'VERIFIED', 'APPROVED', 'VALID' => 'badge-completed',
    'REJECTED', 'SUSPENDED', 'EXPIRED' => 'badge-rejected',
    default => 'badge-pending',
};
$editState = $editState ?? ['editable'=>false,'phone_available'=>false,'pending'=>null,'other_session'=>false];
$edit = $edit ?? '';
$feedback = $feedback ?? null;
$capabilityRequests = $capabilityRequests ?? [];
$capabilityFeedback = $capabilityFeedback ?? null;
$capabilityInput = $capabilityFeedback['input'] ?? [];
$capabilityCategories = $capabilityCategories ?? [];
$capabilityChoices = array_column($capabilityCategories,null,'category_id');
foreach ($capabilities as $record) if (isset($record['category_id']) && !isset($capabilityChoices[$record['category_id']])) $capabilityChoices[$record['category_id']]=$record;
$capabilityCurrent = array_column($capabilities,null,'category_id');
$renewalRequests = $renewalRequests ?? [];
$renewalFeedback = $renewalFeedback ?? null;
$renewalPending = array_filter($renewalRequests,static fn($row)=>$row['request_status']==='PENDING');
$renewalInput = $renewalFeedback['input'] ?? [];
$pending = $editState['pending'];
$error = static function (string $field) use ($feedback, $escape): string {
    return $feedback && !$feedback['success'] && $feedback['field'] === $field
        ? '<p class="profile-field-error" id="error-'.$field.'" role="alert">'.$escape($feedback['message']).'</p>' : '';
};
$invalid = static fn (string $field): string => $feedback && !$feedback['success'] && $feedback['field'] === $field ? ' aria-invalid="true" aria-describedby="error-'.$field.'"' : '';
$verification = $compliance['verification_status'] ?? 'NOT_RECORDED';
$license = $license ?? null;
$scopes = $scopes ?? [];
$activities = $activities ?? [];
$currentScopes = array_filter($scopes, static fn ($scope) => !empty($scope['current_document']) && $scope['scope_status'] === 'VERIFIED');
$scopeLabel = !($scopeAvailable ?? false) ? 'Unavailable' : ($currentScopes ? 'Verified coverage recorded' : 'No current verified scope');
$licenseState = $license !== null && $license['license_status'] === 'VALID' && !($license['approval_recorded'] ?? true) ? 'UNVERIFIED' : ($license === null ? 'NOT_RECORDED' : (($license['license_status'] === 'VALID' && !($licenseValid ?? false)) ? 'EXPIRED' : $license['license_status']));
?>
<link rel="stylesheet" href="<?= $escape($basePath) ?>/assets/css/recycler/profile.css?v=<?= Asset::version('css/recycler/profile.css') ?>">
<section class="workflow-page recycler-profile-page">
    <div class="workflow-header"><div>
        <h1>My Profile &amp; Compliance</h1>
        <p>Your registered company, licence evidence and recorded recycling permissions.</p>
    </div></div>
    <div class="profile-columns">
        <div class="profile-column">
            <section class="workflow-card" aria-labelledby="profile-company-heading">
                <div class="workflow-section-header"><h2 id="profile-company-heading">Company information</h2></div>
                <?php if ($feedback && ($feedback['success'] || !in_array($feedback['field'], ['contact_person','mobile_number','current_password','otp'], true))): ?><p class="profile-feedback" role="<?= $feedback['success'] ? 'status' : 'alert' ?>"><?= $escape($feedback['message']) ?></p><?php endif; ?>
                <dl class="profile-facts">
                    <?php foreach (['Company name' => 'company_name', 'Contact person' => 'full_name', 'Business email' => 'email', 'Mobile number' => 'mobile_number', 'Business address' => 'business_address', 'District' => 'district'] as $caption => $field): ?>
                    <div><dt><?= $escape($caption) ?></dt><dd class="profile-editable-value"><span><?= $escape(($profile[$field] ?? '') !== '' ? $profile[$field] : 'Not recorded') ?></span>
                        <?php if ($editState['editable'] && ($field === 'full_name' || ($field === 'mobile_number' && $editState['phone_available']))): ?><a class="profile-edit-link" href="<?= $escape($basePath) ?>/recycler/profile?edit=<?= $field === 'full_name' ? 'contact' : 'phone' ?>#profile-company-heading" aria-label="Edit <?= $escape(strtolower($caption)) ?>">Edit</a><?php endif; ?>
                    </dd>
                    <?php if ($editState['editable'] && $field === 'full_name' && $edit === 'contact'): ?>
                    <dd class="profile-edit-panel"><form method="post" action="<?= $escape($basePath) ?>/recycler/profile/contact">
                        <input type="hidden" name="_csrf_token" value="<?= $escape($csrf ?? '') ?>">
                        <label for="contact-person">Contact person's name</label>
                        <input id="contact-person" name="contact_person" autocomplete="name" required minlength="2" maxlength="120" value="<?= $escape($feedback && !$feedback['success'] ? ($feedback['value'] ?? '') : ($profile['full_name'] ?? '')) ?>"<?= $invalid('contact_person') ?> autofocus>
                        <?= $error('contact_person') ?>
                        <div class="profile-edit-actions"><button class="primary-workflow-btn" type="submit">Save contact</button><a class="secondary-workflow-btn" href="<?= $escape($basePath) ?>/recycler/profile">Cancel</a></div>
                    </form></dd>
                    <?php elseif ($editState['editable'] && $editState['phone_available'] && $field === 'mobile_number' && $edit !== 'contact' && ($edit === 'phone' || $pending)): ?>
                    <dd class="profile-edit-panel">
                    <?php if ($editState['other_session']): ?><p role="status" class="profile-help">A phone change is awaiting verification in another login session. Finish or cancel it there, or wait for it to expire.</p>
                    <?php elseif ($pending): ?>
                    <form method="post" action="<?= $escape($basePath) ?>/recycler/profile/phone/verify">
                        <input type="hidden" name="_csrf_token" value="<?= $escape($csrf ?? '') ?>">
                        <p class="profile-help">Code sent to <?= $escape($pending['masked_mobile']) ?>. Expires <?= $escape($pending['expires_at']) ?>. Your current number remains active until verification succeeds.</p>
                        <label for="phone-otp">Verification code</label><input id="phone-otp" name="otp" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required<?= $invalid('otp') ?> autofocus>
                        <?= $error('otp') ?><?= $error('mobile_number') ?>
                        <div class="profile-edit-actions"><button class="primary-workflow-btn" type="submit"<?= !$pending['sent'] ? ' disabled' : '' ?>>Verify number</button><button class="secondary-workflow-btn" type="submit" formaction="<?= $escape($basePath) ?>/recycler/profile/phone/resend" formnovalidate<?= !$pending['sent'] ? ' disabled' : '' ?>>Resend code</button><button class="secondary-workflow-btn" type="submit" formaction="<?= $escape($basePath) ?>/recycler/profile/phone/cancel" formnovalidate>Cancel change</button></div>
                        <?php if (!$pending['sent']): ?><p role="status" class="profile-help">Your code is being sent. Reload this page shortly.</p><?php endif; ?>
                    </form>
                    <?php else: ?><form method="post" action="<?= $escape($basePath) ?>/recycler/profile/phone/request">
                        <input type="hidden" name="_csrf_token" value="<?= $escape($csrf ?? '') ?>">
                        <label for="new-mobile">New mobile number</label><input id="new-mobile" name="mobile_number" type="tel" autocomplete="tel" maxlength="24" required value="<?= $escape($feedback && !$feedback['success'] ? ($feedback['value'] ?? '') : '') ?>"<?= $invalid('mobile_number') ?> autofocus>
                        <?= $error('mobile_number') ?><?= $error('otp') ?>
                        <label for="current-password">Current password</label><input id="current-password" name="current_password" type="password" autocomplete="current-password" required<?= $invalid('current_password') ?>>
                        <?= $error('current_password') ?>
                        <p class="profile-help">We will send a verification code to your new number. Your current number stays active until it is verified.</p>
                        <div class="profile-edit-actions"><button class="primary-workflow-btn" type="submit">Send verification code</button><a class="secondary-workflow-btn" href="<?= $escape($basePath) ?>/recycler/profile">Cancel</a></div>
                    </form><?php endif; ?>
                    </dd><?php endif; ?></div>
                    <?php endforeach; ?>
                    <div><dt>Registration</dt><dd><span class="badge <?= $badge($verification) ?>"><?= $escape($label($verification)) ?></span></dd></div>
                </dl>
                <p class="profile-help">Company registration details remain read-only.</p>
                <?php if ($editState['editable'] && !$editState['phone_available']): ?><p class="profile-help">Phone editing is currently unavailable. Your existing number is unchanged.</p><?php endif; ?>
                <?php if (!empty($profile['review_note'])): ?><details class="profile-history"><summary>Registration decision</summary><p><?= $escape($profile['review_note']) ?></p><?php if (!empty($profile['reviewed_at'])): ?><p class="profile-help">Reviewed <?= $escape($profile['reviewed_at']) ?></p><?php endif; ?></details><?php endif; ?>
            </section>
            <section class="workflow-card" aria-labelledby="profile-capabilities-heading">
                <div class="workflow-section-header"><h2 id="profile-capabilities-heading">Recycling capabilities</h2><p>Category decisions and high-risk permissions are recorded separately.</p></div>
                <?php if (!$capabilities): ?><p class="profile-help">No handling capabilities are recorded.</p><?php else: ?>
                <ul class="profile-permissions">
                    <?php foreach ($capabilities as $capability): $state = $capability['capability_status']; ?>
                    <li><div class="profile-record-heading"><h3><?= $escape($capability['category_name']) ?></h3><span class="badge <?= $badge($state) ?>"><?= $escape($label($state)) ?></span></div>
                        <p class="profile-help">High-risk handling: <strong><?= !empty($capability['can_handle_high_risk']) ? ($state === 'APPROVED' ? 'Approved' : ($state === 'PENDING' ? 'Requested — awaiting review' : 'Not authorized')) : 'Not included' ?></strong></p>
                        <?php if ($state === 'PENDING'): ?><p class="profile-help">Awaiting review. This submission does not grant permission.</p><?php endif; ?>
                        <?php if (!empty($capability['review_note'])): ?><details class="profile-history"><summary>Review feedback</summary><p><?= $escape($capability['review_note']) ?></p><?php if (!empty($capability['reviewed_at'])): ?><p class="profile-help">Reviewed <?= $escape($capability['reviewed_at']) ?></p><?php endif; ?></details><?php endif; ?>
                    </li><?php endforeach; ?>
                </ul><?php endif; ?>
                <?php if ($capabilityFeedback): ?><p class="profile-feedback" role="<?= $capabilityFeedback['success']?'status':'alert' ?>"><?= $escape($capabilityFeedback['message']) ?></p><?php endif; ?>
                <?php if ($editState['editable'] && ($capabilityAvailable ?? false)): ?>
                <?php if ($edit === 'capability'): ?>
                <form class="profile-edit-panel profile-capability-form" method="post" action="<?= $escape($basePath) ?>/recycler/profile/capability-change" aria-label="Request Capability Change">
                    <input type="hidden" name="_csrf_token" value="<?= $escape($csrf ?? '') ?>">
                    <label for="capability-category">Waste category</label><select id="capability-category" name="category_id" required autofocus><option value="">Choose category</option>
                    <?php foreach ($capabilityChoices as $choice): $held=$capabilityCurrent[$choice['category_id']] ?? null; ?><option value="<?= (int)$choice['category_id'] ?>" <?= (string)($capabilityInput['category_id']??'')===(string)$choice['category_id']?'selected':'' ?>><?= $escape($choice['category_name']) ?><?= $held?' · '.$escape($label($held['capability_status'])).' · high-risk '.(!empty($held['can_handle_high_risk'])?'Yes':'No'): ' · New category' ?></option><?php endforeach; ?></select>
                    <label for="capability-operation">Requested operation</label><select id="capability-operation" name="request_type" required><option value="">Choose change</option><?php foreach (['ADD'=>'Add new category','UPDATE'=>'Update or reactivate capability','DEACTIVATE'=>'Deactivate capability'] as $value=>$caption): ?><option value="<?= $value ?>" <?= ($capabilityInput['request_type']??'')===$value?'selected':'' ?>><?= $caption ?></option><?php endforeach; ?></select>
                    <label for="capability-risk">Requested high-risk handling</label><select id="capability-risk" name="requested_can_handle_high_risk"><option value="">Not applicable for deactivation</option><option value="0" <?= ($capabilityInput['requested_can_handle_high_risk']??'')==='0'?'selected':'' ?>>No</option><option value="1" <?= ($capabilityInput['requested_can_handle_high_risk']??'')==='1'?'selected':'' ?>>Yes — requires verified coverage</option></select>
                    <label for="capability-reason">Reason for change</label><textarea id="capability-reason" name="request_reason" minlength="5" maxlength="500" rows="2" required><?= $escape($capabilityInput['request_reason']??'') ?></textarea>
                    <p class="profile-help">Your current permissions stay unchanged until approval. Outstanding bids or handovers may prevent a reduction.</p>
                    <div class="profile-edit-actions"><button class="primary-workflow-btn" type="submit">Submit capability request</button><a class="secondary-workflow-btn" href="<?= $escape($basePath) ?>/recycler/profile#profile-capabilities-heading">Cancel</a></div>
                </form>
                <?php else: ?><p><a class="secondary-workflow-btn" href="<?= $escape($basePath) ?>/recycler/profile?edit=capability#profile-capabilities-heading">Request Capability Change</a></p><?php endif; ?>
                <?php endif; ?>
                <?php foreach ($capabilityRequests as $request): if ($request['request_status']!=='PENDING') continue; ?>
                <div class="profile-capability-pending"><div class="profile-record-heading"><h3><?= $escape($request['category_name']) ?> · <?= $escape($label($request['request_type'])) ?></h3><span class="badge badge-pending">Pending change</span></div>
                    <p class="profile-help">At submission: <?= $escape($label($request['previous_capability_status']??'Not held')) ?> · high-risk <?= $request['previous_can_handle_high_risk']===null?'Not held':($request['previous_can_handle_high_risk']?'Yes':'No') ?><br>Requested: <?= $request['request_type']==='DEACTIVATE'?'Inactive':('Handling enabled · high-risk '.($request['requested_can_handle_high_risk']?'Yes':'No')) ?></p><p><?= $escape($request['request_reason']) ?></p>
                    <?php if ($request['obligations']): ?><p class="profile-help">Outstanding bids or handovers must be resolved before this reduction can be approved.</p><?php endif; ?>
                </div><?php endforeach; ?>
                <?php if ($capabilityRequests): ?><details class="profile-history capability-history"><summary>Capability change history</summary><ul class="profile-permissions">
                    <?php foreach ($capabilityRequests as $request): $before=json_decode($request['capability_snapshot_json']??'null',true); ?>
                    <li><div class="profile-record-heading"><h3><?= $escape($request['category_name']) ?> · <?= $escape($label($request['request_type'])) ?></h3><span class="badge <?= $badge($request['request_status']==='APPROVED'?'VERIFIED':$request['request_status']) ?>"><?= $escape($label($request['request_status'])) ?></span></div>
                        <p class="profile-help">Requested <?= $escape($request['submitted_at']) ?> · proposed <?= $request['request_type']==='DEACTIVATE'?'Inactive':('Handling enabled, high-risk '.($request['requested_can_handle_high_risk']?'Yes':'No')) ?></p><p><?= $escape($request['request_reason']) ?></p>
                        <?php if ($request['reviewed_at']): ?><p class="profile-help">Reviewed <?= $escape($request['reviewed_at']) ?> by Administrator #<?= (int)$request['reviewed_by_admin_user_id'] ?>. <?= $escape($request['review_note']) ?></p><?php endif; ?>
                        <?php if ($before): ?><p class="profile-help">Prior capability: <?= $escape($label($before['capability_status'])) ?> · high-risk <?= $before['can_handle_high_risk']?'Yes':'No' ?>. <?= $escape($before['review_note']??'') ?><?php if (!empty($before['reviewed_at'])): ?> · reviewed <?= $escape($before['reviewed_at']) ?> by Administrator #<?= (int)$before['reviewed_by_admin_user_id'] ?><?php endif; ?></p><?php endif; ?>
                    </li><?php endforeach; ?></ul></details><?php endif; ?>
            </section>
        </div>
        <div class="profile-column">
            <section class="workflow-card" aria-labelledby="profile-licence-heading">
                <div class="profile-record-heading"><h2 id="profile-licence-heading">CEA licence</h2><span class="badge <?= $badge($licenseState) ?>"><?= $escape($label($licenseState)) ?></span></div>
                <?php if ($license): ?>
                <dl class="profile-licence-facts"><div><dt>Licence number</dt><dd><?= $escape($license['license_no']) ?></dd></div><div><dt>Expiry date</dt><dd><?= $escape($license['license_expiry']) ?></dd></div></dl>
                <p class="profile-help"><?= $verification === 'PENDING' ? 'Initial submitted licence.' : 'Selected licence record.' ?> Licence validity does not by itself authorize waste categories, activities or high-risk handling.</p>
                <?php if ($documentAvailable ?? false): ?><a class="secondary-workflow-btn" href="<?= $escape($basePath) ?>/recycler/profile/licence-document" target="_blank" rel="noopener noreferrer">View protected PDF<span class="profile-sr-only"> (opens in a new tab)</span></a><?php else: ?><p class="profile-help">The licence document is currently unavailable.</p><?php endif; ?>
                <?php if (!empty($license['review_note'])): ?><details class="profile-history"><summary>Licence review feedback</summary><p><?= $escape($license['review_note']) ?></p><?php if (!empty($license['reviewed_at'])): ?><p class="profile-help">Reviewed <?= $escape($license['reviewed_at']) ?></p><?php endif; ?></details><?php endif; ?>
                <?php else: ?><p class="profile-help"><?= in_array($verification, ['VERIFIED','REJECTED','SUSPENDED'], true) ? 'No approved licence is selected. Historical records require Administrator review.' : 'No licence has been submitted.' ?></p><?php endif; ?>
                <?php if ($renewalFeedback): ?><p class="profile-feedback" role="<?= $renewalFeedback['success'] ? 'status' : 'alert' ?>"><?= $escape($renewalFeedback['message']) ?></p><?php endif; ?>
                <?php if ($editState['editable'] && ($renewalAvailable ?? false) && !$renewalPending): ?>
                    <?php if ($edit === 'licence'): ?>
                    <form class="profile-edit-panel profile-renewal-form" method="post" enctype="multipart/form-data" action="<?= $escape($basePath) ?>/recycler/profile/licence-update" aria-label="Update CEA Licence">
                        <input type="hidden" name="_csrf_token" value="<?= $escape($csrf ?? '') ?>">
                        <label for="renewal-number">New licence number</label><input id="renewal-number" name="license_number" maxlength="100" minlength="2" required value="<?= $escape($renewalInput['license_number'] ?? ($license['license_no'] ?? '')) ?>" autofocus>
                        <label for="renewal-expiry">Expiry date</label><input id="renewal-expiry" name="expiry_date" type="date" required value="<?= $escape($renewalInput['expiry_date'] ?? '') ?>">
                        <label for="renewal-pdf">CEA licence PDF (maximum 5 MB)</label><input id="renewal-pdf" name="licence_pdf" type="file" accept="application/pdf,.pdf" required aria-describedby="renewal-help">
                        <label for="renewal-explanation">Explanation (optional)</label><textarea id="renewal-explanation" name="explanation" maxlength="500" rows="2"><?= $escape($renewalInput['explanation'] ?? '') ?></textarea>
                        <p class="profile-help" id="renewal-help">Your approved licence stays unchanged while this update is reviewed. Select the PDF again if validation fails.</p>
                        <div class="profile-edit-actions"><button class="primary-workflow-btn" type="submit">Submit licence update</button><a class="secondary-workflow-btn" href="<?= $escape($basePath) ?>/recycler/profile#profile-licence-heading">Cancel</a></div>
                    </form>
                    <?php else: ?><p><a class="secondary-workflow-btn" href="<?= $escape($basePath) ?>/recycler/profile?edit=licence#profile-licence-heading">Update CEA Licence</a></p><?php endif; ?>
                <?php endif; ?>
                <?php if ($editState['editable'] && !($renewalAvailable ?? false)): ?><p class="profile-help">Licence updates are currently unavailable. Your approved record is unchanged.</p><?php endif; ?>
                <?php foreach ($renewalPending as $request): ?>
                <div class="profile-renewal-pending"><div class="profile-record-heading"><h3>Licence update awaiting review</h3><span class="badge badge-pending">Pending</span></div>
                    <p><?= $escape($request['requested_license_number']) ?> · expires <?= $escape($request['requested_expiry_date']) ?></p><p class="profile-help">Submitted <?= $escape($request['submitted_at']) ?>. Your approved licence and permissions remain unchanged.</p>
                    <?php if ($request['document_sha256']): ?><a class="secondary-workflow-btn" href="<?= $escape($basePath) ?>/recycler/profile/licence-update/<?= (int)$request['request_id'] ?>/document" target="_blank" rel="noopener noreferrer">View submitted PDF</a><?php endif; ?>
                </div><?php endforeach; ?>
                <?php if ($renewalRequests): ?><details class="profile-history"><summary>Licence update history</summary><ul class="profile-permissions">
                <?php foreach ($renewalRequests as $request): ?>
                    <li><div class="profile-record-heading"><h3><?= $escape($request['requested_license_number']) ?></h3><span class="badge <?= $badge($request['request_status']==='APPROVED'?'VERIFIED':$request['request_status']) ?>"><?= $escape($label($request['request_status'])) ?></span></div><p class="profile-help">Expiry <?= $escape($request['requested_expiry_date']) ?> · submitted <?= $escape($request['submitted_at']) ?></p>
                        <?php if ($request['explanation']): ?><p><?= $escape($request['explanation']) ?></p><?php endif; ?>
                        <?php if ($request['reviewed_at']): ?><p class="profile-help">Reviewed <?= $escape($request['reviewed_at']) ?> by Administrator #<?= (int)$request['reviewed_by_admin_user_id'] ?>. <?= $escape($request['review_note']) ?></p><?php endif; ?>
                        <?php if ($request['document_sha256']): ?><a href="<?= $escape($basePath) ?>/recycler/profile/licence-update/<?= (int)$request['request_id'] ?>/document" target="_blank" rel="noopener noreferrer">Submitted PDF</a><?php endif; ?>
                        <?php if (!empty($request['previous_document_available'])): ?><a href="<?= $escape($basePath) ?>/recycler/profile/licence-update/<?= (int)$request['request_id'] ?>/previous-document" target="_blank" rel="noopener noreferrer">Previous approved PDF</a><?php endif; ?>
                    </li>
                <?php endforeach; ?></ul></details><?php endif; ?>
            </section>
            <section class="workflow-card" aria-labelledby="profile-compliance-heading">
                <div class="workflow-section-header"><h2 id="profile-compliance-heading">Compliance overview</h2></div>
                <dl class="profile-snapshot">
                    <div><dt>Registration</dt><dd><?= $escape($label($verification)) ?></dd></div>
                    <div><dt>CEA licence</dt><dd><?= $escape($label($licenseState)) ?></dd></div>
                    <div><dt>Licence scope</dt><dd><?= $escape($scopeLabel) ?></dd></div>
                    <div><dt>Approved categories</dt><dd><?= count(array_filter($capabilities, static fn ($row) => $row['capability_status'] === 'APPROVED')) ?></dd></div>
                </dl>
                <p class="profile-help">Eligibility is checked for each E-Lot against your registration, licence, approved category, high-risk permission and the bidding window. A pending submission grants no additional permission.</p>
                <?php if ($scopes): ?><details class="profile-history"><summary>Licence scope evidence and decisions</summary>
                    <ul class="profile-permissions">
                    <?php foreach ($scopes as $scope): ?>
                        <li><div class="profile-record-heading"><h3><?= $escape($scope['category_name']) ?> · <?= $escape($label($scope['activity_type'])) ?></h3><span class="badge <?= $badge($scope['scope_status']) ?>"><?= $escape($label($scope['scope_status'])) ?></span></div>
                            <p class="profile-help"><?= !empty($scope['current_document']) ? 'Matches the current valid licence document.' : 'Historical or unavailable document — not current verified coverage.' ?></p>
                            <p>High-risk coverage: <?= !empty($scope['covers_high_risk']) ? ($scope['scope_status'] === 'VERIFIED' && !empty($scope['current_document']) ? 'Verified for this scope' : 'Claim recorded — not current verified coverage') : 'Not established' ?> · <?= $escape($scope['authorization_basis']) ?></p>
                            <p><?= $escape($scope['waste_description']) ?></p>
                            <p class="profile-help">Evidence: <?= $escape($scope['evidence_reference']) ?><br>Conditions: <?= $escape($scope['licence_conditions']) ?></p>
                            <?php if (!empty($scope['review_note'])): ?><p class="profile-help">Decision: <?= $escape($scope['review_note']) ?></p><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                </details><?php endif; ?>
            </section>
            <section class="workflow-card" aria-labelledby="profile-activities-heading">
                <div class="workflow-section-header"><h2 id="profile-activities-heading">Authorized activities</h2><p>Only approved activities represent recorded permissions.</p></div>
                <?php if (!$activities): ?><p class="profile-help">No activities are recorded.</p><?php else: ?><ul class="profile-permissions">
                    <?php foreach ($activities as $activity): $state = $activity['activity_status']; ?>
                    <li><div class="profile-record-heading"><h3><?= $escape($label($activity['activity_type'])) ?></h3><span class="badge <?= $badge($state) ?>"><?= $escape($label($state)) ?></span></div>
                        <?php if (!empty($activity['review_note'])): ?><details class="profile-history"><summary>Review feedback</summary><p><?= $escape($activity['review_note']) ?></p><?php if (!empty($activity['reviewed_at'])): ?><p class="profile-help">Reviewed <?= $escape($activity['reviewed_at']) ?></p><?php endif; ?></details><?php endif; ?>
                    </li><?php endforeach; ?>
                </ul><?php endif; ?>
            </section>
        </div>
    </div>
</section>
