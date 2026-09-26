<?php
declare(strict_types=1);
// View-only regression checks: no database, session or fixture writes.
require __DIR__ . '/../app/autoload.php';
$checks = 0;
$assert = static function (bool $ok, string $message) use (&$checks): void {
    if (!$ok) throw new RuntimeException($message);
    ++$checks;
};
$render = static function (?array $profile, ?array $compliance, array $capabilities): string {
    $basePath = '/EcoLot-LK';
    ob_start();
    require APP_ROOT . '/app/Views/recycler/profile.php';
    return ob_get_clean();
};
$html = $render(['company_name' => 'Owner <Recovery>', 'full_name' => 'Owner "Contact"', 'email' => 'owner@example.org', 'mobile_number' => '94770000000', 'business_address' => 'Recorded & private', 'district' => 'Colombo'], ['verification_status' => 'VERIFIED', 'valid_license_expiry' => '2028-02-01'], [['category_name' => 'Owner <Category>', 'capability_status' => 'APPROVED'], ['category_name' => 'Pending category', 'capability_status' => 'PENDING']]);
foreach (['Owner &lt;Recovery&gt;', 'Owner &quot;Contact&quot;', 'Recorded &amp; private', 'Owner &lt;Category&gt;', '2028-02-01', 'Awaiting review', 'Approved', 'Pending'] as $value) $assert(str_contains($html, $value), 'Real escaped values/status: ' . $value);
foreach (['Edit Basic Information', 'Submit Licence Update', 'Request Change', 'Request Capability Change'] as $label) $assert(str_contains($html, $label), 'Restored action ' . $label);
foreach (['GreenCycle', 'Anjana', 'SWML/2026/001', 'Demo Consumer', 'data-recycler-dialog='] as $fake) $assert(!str_contains($html, $fake), 'Old demo path absent: ' . $fake);
$dom = new DOMDocument();
@$dom->loadHTML($html);
$x = new DOMXPath($dom);
$assert($x->query('//input[@data-contact-name and not(@readonly)]')->length === 1, 'Contact draft editable');
$assert($x->query('//input[@data-contact-email and @type="email" and not(@readonly)]')->length === 1, 'Email draft editable');
$assert($x->query('//input[@value="94770000000" and @readonly]')->length === 1, 'Phone read-only');
$assert(str_contains($html, 'Mobile number changes require verification.'), 'Phone verification note');
foreach (['Company Name', 'Business Address', 'District', 'New SWML / Licence Number', 'New Expiry Date'] as $field) $assert(str_contains($html, $field . ' — Requires Administrator review'), 'Review label: ' . $field);
$assert($x->query('//input[@type="file" and @disabled]')->length === 1, 'Upload unavailable');
$assert($x->query('//button[@data-profile-unavailable and @disabled and @type="button"]')->length === 1, 'No submit action');
$assert($x->query('//form[@action or @method]')->length === 0, 'No mutation endpoint');
$assert($x->query('//input[@name] | //select[@name] | //textarea[@name]')->length === 0, 'Draft values not form payloads');
$assert(str_contains($html, 'visual draft only') && str_contains($html, 'Closing this form discards your draft.'), 'Honest draft limitation');
$empty = $render(null, null, []);
foreach (['Not recorded', 'No current valid licence', 'No handling capabilities are recorded.', 'Request Capability Change'] as $text) $assert(str_contains($empty, $text), 'Empty state: ' . $text);
$assert(!str_contains($empty, 'Awaiting review'), 'No invented pending state');
echo "PASS: $checks Profile view/permissions/escaping assertions (no database access)\n";
