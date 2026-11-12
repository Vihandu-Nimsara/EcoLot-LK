<?php
declare(strict_types=1);
// View-only regression checks: no database, session or fixture writes.
require __DIR__ . '/../app/autoload.php';
$checks = 0;
$assert = static function (bool $ok, string $message) use (&$checks): void {
    if (!$ok) throw new RuntimeException($message);
    ++$checks;
};
$render = static function (?array $profile, ?array $compliance, array $capabilities, array $activities = [], ?array $currentLicense = null, ?array $pendingLicenseUpdate = null, ?array $phoneChange = null, bool $phoneEditorOpen = false, array $feedback = [], array $capabilityHistory = []): string {
    $basePath = '/EcoLot-LK';
    $csrfToken = 'profile-test-token';
    $profileSuccess = $feedback['success'] ?? null;
    $profileError = $feedback['error'] ?? null;
    $capabilityRequests = $capabilityHistory;
    $capabilityOptions = ($compliance['verification_status'] ?? '') === 'VERIFIED'
        ? [['category_id' => 1, 'category_name' => 'Owner <Category>', 'actions' => ['UPDATE', 'DEACTIVATE'], 'current_status' => 'APPROVED', 'current_risk' => 0]] : [];
    ob_start();
    require APP_ROOT . '/app/Views/recycler/profile.php';
    return ob_get_clean();
};
$html = $render(['company_name' => 'Owner <Recovery>', 'full_name' => 'Owner "Contact"', 'email' => 'owner@example.org', 'mobile_number' => '94770000000', 'business_address' => 'Recorded & private', 'district' => 'Colombo'], ['verification_status' => 'VERIFIED', 'valid_license_expiry' => '2028-02-01'], [['category_id' => 1, 'category_name' => 'Owner <Category>', 'capability_status' => 'APPROVED'], ['category_id' => 2, 'category_name' => 'Pending category', 'capability_status' => 'PENDING']]);
foreach (['Owner &lt;Recovery&gt;', 'Owner &quot;Contact&quot;', 'Recorded &amp; private', 'Owner &lt;Category&gt;', '2028-02-01', 'Awaiting review', 'Approved', 'Pending'] as $value) $assert(str_contains($html, $value), 'Real escaped values/status: ' . $value);
foreach (['Company Information', 'CEA Licence', 'Compliance Overview', 'Recycling Capabilities', 'Authorized Activities', 'Save Changes', 'Submit for Admin Review', 'Request Change', 'Request Capability Change'] as $label) $assert(str_contains($html, $label), 'Existing action or card: ' . $label);
foreach (['GreenCycle', 'Anjana', 'SWML/2026/001', 'Demo Consumer', 'data-recycler-dialog='] as $fake) $assert(!str_contains($html, $fake), 'Old demo path absent: ' . $fake);
$dom = new DOMDocument();
@$dom->loadHTML($html);
$x = new DOMXPath($dom);
$assert($x->query('//form[@method="post" and @action="/EcoLot-LK/recycler/profile/contact"]//input[@name="full_name" and @required and @minlength="2" and @maxlength="120"]')->length === 1, 'Working contact form preserved');
$assert($x->query('//input[@name="email" or @name="mobile_number" or @name="company_name" or @name="business_address" or @name="district"]')->length === 0, 'Other company fields remain read-only');
$assert($x->query('//form[@method="post"]//input[@name="_csrf_token" and @value="profile-test-token"]')->length === 4, 'Contact, phone, capability and licence forms retain CSRF');
$assert($x->query('//input[@name="current_password" and @type="password" and not(@value)]')->length === 1, 'Password is never repopulated');
$assert($x->query('//input[@name="proposed_mobile_number" and @type="tel"]')->length === 1, 'Separate phone request editor');
$assert($x->query('//details[contains(@class,"profile-license-update")]//form[@method="post" and @enctype="multipart/form-data" and @action="/EcoLot-LK/recycler/profile/license-update"]//input[@type="file" and @name="license_document" and @accept=".pdf,application/pdf" and @required and not(@disabled)]')->length === 1, 'Collapsible PDF upload preserved');
$assert($x->query('//input[@name="requested_expiry_date" and @required and @min]')->length === 1, 'Expiry validation preserved');
$assert($x->query('//form[@data-profile-form and @method="post"]')->length === 1, 'Capability modal has POST backend');
$assert($x->query('//button[@data-profile-submit and @type="submit" and not(@disabled)]')->length === 1, 'Capability submission is available');
$assert(str_contains($html, 'name="category_id"') && str_contains($html, 'data-category-id="1"'), 'Capability selected by ID');
$assert(!str_contains($html, '100%'), 'No invented compliance percentage');
$assert(str_contains($html, '1 Approved') && str_contains($html, '0 Approved'), 'Only recorded approvals counted');
$activityHtml = $render(null, ['verification_status' => 'REJECTED'], [], [
    ['activity_type' => 'COLLECTION', 'activity_status' => 'APPROVED'],
    ['activity_type' => 'Sorting <private>', 'activity_status' => 'SUSPENDED'],
]);
$assert(str_contains($activityHtml, 'Collection') && str_contains($activityHtml, 'Sorting &lt;private&gt;'), 'Recorded activity names escaped');
$assert(str_contains($activityHtml, 'Suspended') && str_contains($activityHtml, 'Rejected') && str_contains($activityHtml, '1 Approved'), 'Actual activity and verification statuses');
$pending = $render(null, ['verification_status' => 'VERIFIED'], [], [], ['license_number' => 'Approved <record>', 'expiry_date' => '2000-01-01'], ['requested_license_number' => 'Renewal <record>', 'requested_expiry_date' => '2099-01-01']);
foreach (['Approved &lt;record&gt;', 'Renewal &lt;record&gt;', 'Expired', 'Pending Review', 'Another licence update cannot be submitted'] as $value) $assert(str_contains($pending, $value), 'Pending/expired licence presentation: ' . $value);
$assert(!str_contains($pending, 'name="license_document"'), 'Pending request prevents another submission');
$valid = $render(null, ['verification_status' => 'VERIFIED'], [], [], ['license_number' => 'Approved record', 'expiry_date' => '2099-01-01']);
$assert(str_contains($valid, 'Valid') && str_contains($valid, 'Approved record'), 'Approved current licence retained');
$empty = $render(null, null, []);
foreach (['Not recorded', 'No current valid licence', 'No handling capabilities are recorded.', 'No authorized activities are recorded.', 'Request Capability Change', 'Licence updates are available to verified recyclers.'] as $text) $assert(str_contains($empty, $text), 'Empty state: ' . $text);
$assert(!str_contains($empty, 'Awaiting review'), 'No invented pending state');
$phone = $render(null, null, [], [], null, null, ['masked_mobile' => '9477****001', 'expires_at' => '2030-01-01 06:00:00', 'retry_after' => 60, 'can_verify' => true]);
$phoneDom = new DOMDocument();
@$phoneDom->loadHTML($phone);
$phoneXpath = new DOMXPath($phoneDom);
$assert($phoneXpath->query('//details[contains(@class,"profile-phone-editor") and @open]')->length === 1, 'Pending editor stays open');
$assert($phoneXpath->query('//input[@name="otp" and @inputmode="numeric" and @pattern="[0-9]{6}" and not(@value)]')->length === 1, 'OTP accessible and never repopulated');
foreach (['verify', 'resend', 'cancel'] as $action) $assert($phoneXpath->query('//form[@method="post" and @action="/EcoLot-LK/recycler/profile/phone/' . $action . '"]//input[@name="_csrf_token"]')->length === 1, 'Phone CSRF: ' . $action);
$assert(!str_contains($phone, 'name="current_password"') && str_contains($phone, '9477****001'), 'Only masked destination in verification state');
$error = $render(null, null, [], [], null, null, null, true);
$assert(str_contains($error, 'class="profile-contact-editor profile-phone-editor" open'), 'Request errors keep editor open');
$notices = $render(null, null, [], [], null, null, null, false, ['success' => 'Saved <contact>', 'error' => 'Rejected <input>']);
$noticeDom = new DOMDocument();
@$noticeDom->loadHTML($notices);
$noticeXpath = new DOMXPath($noticeDom);
foreach (['status' => 'profile-notice-success', 'alert' => 'profile-notice-error'] as $role => $class) {
    $assert($noticeXpath->query('//p[@role="' . $role . '" and contains(@class,"' . $class . '") and @tabindex="-1" and @data-profile-feedback]')->length === 1, 'Distinct accessible feedback ' . $role);
}
$assert(str_contains($notices, 'Saved &lt;contact&gt;') && str_contains($notices, 'Rejected &lt;input&gt;'), 'Feedback escaped');
$assert($noticeXpath->query('//p[contains(@class,"profile-notice-warning")]')->length > 0, 'Informational review warnings distinguished');
$assert($x->query('//section[contains(@class,"profile-capabilities")]//tbody/tr/td[@data-label]')->length === 6, 'Every capability cell retains its mobile label');
$assert($x->query('//section[@role="dialog" and @aria-modal="true" and @aria-labelledby="profile-dialog-title" and @aria-describedby="profile-dialog-description"]')->length === 1, 'Capability dialog has an accessible name and description');
$history = $render(null, null, [], [], null, null, null, false, [], [[
    'category_name' => 'Category <private>', 'request_type' => 'UPDATE', 'request_status' => 'REJECTED',
    'previous_capability_status' => 'APPROVED', 'previous_can_handle_high_risk' => 0,
    'requested_can_handle_high_risk' => 1, 'request_reason' => str_repeat('Long <reason> ', 30),
    'submitted_at' => '2026-11-12 10:00:00', 'reviewed_at' => '2026-11-12 11:00:00', 'review_note' => 'Rejected <note>',
]]);
$assert(str_contains($history, '<details class="profile-request-description"><summary>Request reason</summary>'), 'Long reason accessible through native expandable disclosure');
$assert(str_contains($history, 'Long &lt;reason&gt;') && str_contains($history, 'Rejected &lt;note&gt;'), 'Full reason and rejection feedback preserved and escaped');
echo "PASS: $checks Profile view/permissions/escaping assertions (no database access)\n";
