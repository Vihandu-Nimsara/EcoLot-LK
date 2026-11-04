<?php
declare(strict_types=1);
// In-memory presentation checks only: no database, session or account writes.
$root = dirname(__DIR__);
$checks = 0;
$assert = static function (bool $ok, string $label) use (&$checks): void {
    if (!$ok) throw new RuntimeException($label);
    ++$checks;
};
$render = static function (array $overrides = []) use ($root): string {
    $basePath = '/EcoLot-LK/public';
    $compliance = ['verification_status' => 'VERIFIED', 'valid_license_expiry' => '2099-12-31'];
    $eligibleCount = 7;
    $submittedCount = 2;
    $awardedCount = 4;
    $awaitingCount = 0;
    $capabilities = [['capability_status' => 'APPROVED'], ['capability_status' => 'PENDING']];
    $activities = [['activity_status' => 'APPROVED'], ['activity_status' => 'REJECTED']];
    $recentBids = [];
    $recentAwards = [];
    extract($overrides, EXTR_OVERWRITE);
    ob_start();
    require $root . '/app/Views/recycler/dashboard.php';
    return ob_get_clean();
};
$html = $render();
$previousLibxml = libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
libxml_clear_errors();
libxml_use_internal_errors($previousLibxml);
$xpath = new DOMXPath($dom);
$assert($xpath->query('//svg')->length === 0, 'Dashboard contains no SVG icons');
foreach (['Eligible E-Lots' => '♻️', 'Submitted Bids' => '⚒️', 'Awarded E-Lots' => '🏆', 'Awaiting Handover' => '🚚'] as $label => $emoji) {
    $nodes = $xpath->query('//a[div[@class="stat-info"]/span[normalize-space()="' . $label . '"]]/div[@aria-hidden="true"]');
    $assert($nodes->length === 1 && trim($nodes->item(0)->textContent) === $emoji, 'Decorative KPI emoji: ' . $label);
}
foreach (['Compliance Overview' => '🛡️', 'Alerts & Updates' => '🔔', 'Recent Bids' => '📋', 'Recent Awards' => '🏆', 'Quick Actions' => '⚡'] as $label => $emoji) {
    $nodes = $xpath->query('//h2[contains(., "' . $label . '")]/span[@aria-hidden="true"]');
    $assert($nodes->length === 1 && trim($nodes->item(0)->textContent) === $emoji, 'Decorative heading emoji: ' . $label);
}
foreach (['Find Eligible E-Lots' => '♻️', 'View My Bids' => '⚒️', 'My Awards' => '🏆', 'My Profile' => '👤'] as $label => $emoji) {
    $nodes = $xpath->query('//a[span[normalize-space()="' . $label . '"]]/span[@aria-hidden="true"]');
    $assert($nodes->length === 1 && trim($nodes->item(0)->textContent) === $emoji, 'Decorative quick action emoji: ' . $label);
}
$assert(str_contains($html, 'All set!'), 'No warnings for current verified compliance');
$assert(substr_count($html, '1 Approved') === 2, 'Only approved capabilities and activities counted');
foreach (['eligible-e-lots', 'my-bids', 'awarded-e-lots', 'profile'] as $path) {
    $assert(str_contains($html, '/recycler/' . $path), 'Real navigation destination ' . $path);
}
foreach (['Pending' => 'awaiting review', 'Rejected' => 'was rejected', 'Suspended' => 'is suspended', '' => 'not recorded'] as $status => $warning) {
    $warningHtml = $render(['compliance' => ['verification_status' => strtoupper($status)]]);
    $assert(str_contains($warningHtml, $warning), 'Accurate verification warning ' . $status);
    $assert(str_contains($warningHtml, 'No currently valid CEA licence'), 'No licence warning ' . $status);
    $assert(!str_contains($warningHtml, 'All set!'), 'No success notice with warnings ' . $status);
}
$nearExpiry = (new DateTimeImmutable('today', new DateTimeZone('Asia/Colombo')))->modify('+30 days')->format('Y-m-d');
$assert(str_contains($render(['compliance' => ['verification_status' => 'VERIFIED', 'valid_license_expiry' => $nearExpiry]]), 'expires within 30 days'), 'Inclusive thirty-day expiry warning');
$assert(str_contains($render(['awaitingCount' => 2]), '2 awarded E-Lot(s) are awaiting handover.'), 'Recorded awaiting handovers alert');
$bid = ['lot_code' => 'LOT-<Owner>', 'title' => '<script>alert(1)</script>', 'bid_amount' => '1234.50', 'bid_status' => 'SUBMITTED', 'submitted_at' => '2026-11-03 09:15:23'];
foreach (['SUBMITTED' => 'info', 'WITHDRAWN' => 'neutral', 'WINNING' => 'positive', 'REJECTED' => 'negative'] as $status => $class) {
    $bid['bid_status'] = $status;
    $rows = $render(['recentBids' => [$bid]]);
    $assert(str_contains($rows, 'status ' . $class), 'Accurate bid badge ' . $status);
}
$rows = $render(['recentBids' => [$bid]]);
$assert(str_contains($rows, 'LOT-&lt;Owner&gt;') && str_contains($rows, '&lt;script&gt;'), 'Lot fields escaped');
$assert(!str_contains($rows, '<script>'), 'No executable lot markup');
$assert(str_contains($rows, '1,234.50'), 'Full currency precision retained');
$assert(str_contains($rows, '2026-11-03T09:15:23+05:30') && str_contains($rows, 'title="2026-11-03 09:15:23"'), 'Machine-readable local timestamp with full precision available');
$award = ['e_lot_id' => 21, 'lot_code' => 'LOT-AWARD', 'title' => 'Owner award', 'bid_amount' => '2000.00', 'reviewed_at' => null];
foreach ([null => 'Not recorded', 'PENDING' => 'Pending', 'SCHEDULED' => 'Scheduled', 'COMPLETED' => 'Completed', 'CANCELLED' => 'Cancelled'] as $status => $label) {
    $award['handover_status'] = $status ?: null;
    $rows = $render(['recentAwards' => [$award]]);
    $assert(str_contains($rows, $label), 'Honest handover label ' . $label);
    $assert(str_contains($rows, '/recycler/awarded-e-lot/21'), 'Existing award details route');
}
$assert(!str_contains($html, '<table'), 'No redundant compliance tables or fabricated recent records');
echo "PASS: $checks dashboard presentation/status/escaping assertions (no database access)\n";
