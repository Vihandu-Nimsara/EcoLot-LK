<?php
declare(strict_types=1);
// Run against the same isolated schema as recycler-bids-integration.php.
if (getenv('ECOLOT_BID_TEST') !== '1' || !str_starts_with((string) getenv('DB_DATABASE'), 'ecolot_test_')) {
    fwrite(STDERR, "Requires ECOLOT_BID_TEST=1 and an isolated DB_DATABASE=ecolot_test_* database.\n");
    exit(1);
}
require __DIR__ . '/recycler-bids.php';
require APP_ROOT . '/database/demo/recycler-bids-fixture.php';

// Capture controller-provided data and also render the real view/layout.
final class ReportingTestController extends RecyclerController
{
    public array $data = [];
    public function view(string $view, array $data = [], ?string $layout = null): void
    {
        $this->data = $data;
        parent::view($view, $data, $layout);
    }
}
$db = Database::connection();
$checks = 0;
$assert = static function (bool $ok, string $message) use (&$checks): void { check($ok, $message); ++$checks; };
$exec = static function (string $sql, array $parameters = []) use ($db): void {
    $statement = $db->prepare($sql); $statement->execute($parameters);
};
$login = static function (int $id): void {
    Session::put('auth_user', ['id' => $id, 'role' => 'RECYCLER', 'name' => 'Reporting owner']);
};
$page = static function (string $method, ?int $id = null): array {
    $controller = new ReportingTestController();
    http_response_code(200);
    ob_start();
    try { $id === null ? $controller->$method() : $controller->$method((string) $id); }
    finally { $html = ob_get_clean(); }
    check(http_response_code() === 200, $method . ' renders successfully');
    return [$controller->data, $html];
};
$db->exec('SET timestamp = 1790244000');
$db->beginTransaction();
try {
    $f = recyclerBidFixture($db, bin2hex(random_bytes(12)));
    $owner = $f['users']['recycler']; $other = $f['users']['competitor'];
    $login($owner);
    // A real registered Recycler with no records: no fabricated IDs or presentation data.
    $exec("INSERT INTO users (full_name, mobile_number, password_hash, role, account_status) VALUES ('Empty recycler', ?, ?, 'RECYCLER', 'ACTIVE')", ['947' . random_int(10000000, 99999999), password_hash(bin2hex(random_bytes(12)), PASSWORD_DEFAULT)]);
    $emptyOwner = (int) $db->lastInsertId();
    $exec("INSERT INTO authorized_recyclers (user_id, company_name, business_address, district) VALUES (?, 'Empty company', 'Recorded address', 'Gampaha')", [$emptyOwner]);
    $login($emptyOwner);
    foreach (['dashboard', 'reports'] as $method) {
        [$data, $html] = $page($method);
        foreach (['bidCount', 'submittedCount', 'wonCount', 'rejectedCount', 'withdrawnCount', 'awardedCount', 'awaitingCount', 'handedCount'] as $key) $assert($data[$key] === 0, "$method empty $key");
        $assert($data['recentBids'] === [] && $data['recentAwards'] === [], "$method empty activity");
        if ($method === 'dashboard') {
            $assert($data['eligibleCount'] === 0 && $data['capabilities'] === [], 'Empty dashboard eligibility/capabilities');
            $assert($data['compliance']['verification_status'] === 'PENDING' && $data['compliance']['valid_license_expiry'] === null, 'Empty owner compliance does not borrow verified competitor data');
            $assert(str_contains($html, 'No handling capabilities are recorded.') && str_contains($html, 'No current valid licence'), 'Dashboard empty states rendered');
        } else {
            $assert(str_contains($html, 'You have not placed any bids.') && str_contains($html, 'You have no awarded E-Lots.'), 'Reports empty states rendered');
        }
    }
    [$data, $html] = $page('profile');
    $assert(str_contains($html, 'Empty company') && str_contains($html, 'Not recorded') && str_contains($html, 'No current valid licence'), 'Profile missing email/licence safe');
    $assert(!str_contains($html, 'data-recycler-dialog='), 'Profile has no fake mutation controls');

    $login($owner);
    // Give the competing owner distinct compliance/capabilities to expose scope errors.
    $exec("UPDATE recycler_licenses SET expiry_date = '2028-02-01' WHERE recycler_user_id = ?", [$owner]);
    $exec("UPDATE recycler_licenses SET expiry_date = '2029-03-01' WHERE recycler_user_id = ?", [$other]);
    $exec("INSERT INTO waste_categories (category_name) VALUES (?)", [$f['tag'] . ' Private competitor category']);
    $privateCategory = (int) $db->lastInsertId();
    $exec("INSERT INTO recycler_capabilities (recycler_user_id, category_id, capability_status) VALUES (?, ?, 'PENDING')", [$other, $privateCategory]);
    $exec('UPDATE authorized_recyclers SET company_name = ? WHERE user_id = ?', ['Owner <Recovery>', $owner]);

    $bidIds = []; $awardIds = []; $lotIds = [];
    $statuses = ['SUBMITTED', 'WITHDRAWN', 'REJECTED', 'WINNING', 'WINNING', 'WINNING', 'WINNING', 'WINNING', 'WINNING'];
    // Deliberately non-insertion order, including ties, to check both sorting keys.
    $days = [7, 3, 9, 2, 8, 8, 4, 6, 5];
    $reviewDays = [3 => 9, 4 => 4, 5 => 8, 6 => 8, 7 => 6, 8 => 7];
    foreach ($statuses as $i => $status) {
        $exec("INSERT INTO e_lots (lot_code, created_by_collector_user_id, category_id, title, lot_status, verified_by_officer_user_id, verified_at, bidding_open_at, bidding_close_at)
            SELECT CONCAT(lot_code, ?), created_by_collector_user_id, category_id, title, lot_status, verified_by_officer_user_id, verified_at, bidding_open_at, bidding_close_at FROM e_lots WHERE e_lot_id = ?", ['-report-' . $i, $f['lot']]);
        $lotIds[$i] = (int) $db->lastInsertId();
        $exec('INSERT INTO recycler_bids (e_lot_id, recycler_user_id, bid_amount) VALUES (?, ?, ?)', [$lotIds[$i], $owner, 200 + $i]);
        $bidIds[$i] = (int) $db->lastInsertId();
        $exec('UPDATE recycler_bids SET bid_status = ?, submitted_at = ? WHERE bid_id = ?', [$status, sprintf('2026-09-%02d 10:00:00', $days[$i]), $bidIds[$i]]);
        if ($status === 'WINNING') {
            $awardIds[$i] = $bidIds[$i];
            $exec('UPDATE recycler_bids SET reviewed_by_officer_user_id = ?, reviewed_at = ? WHERE bid_id = ?', [$f['users']['officer'], sprintf('2026-09-%02d 12:00:00', $reviewDays[$i]), $bidIds[$i]]);
            $exec("UPDATE e_lots SET lot_status = 'AWARDED' WHERE e_lot_id = ?", [$lotIds[$i]]);
        }
    }
    foreach ([3 => 'PENDING', 4 => 'SCHEDULED', 5 => 'COMPLETED', 6 => 'CANCELLED'] as $i => $status) {
        $exec('INSERT INTO handover_records (winning_bid_id, handover_status, handover_date, recorded_by_officer_user_id, remarks) VALUES (?, ?, ?, ?, ?)', [$bidIds[$i], $status, $status === 'COMPLETED' ? '2026-09-20 12:00:00' : null, $f['users']['officer'], $i === 3 ? 'Collected <securely> & recorded' : null]);
    }
    // A competing award must not inflate totals or appear in activity.
    $exec("UPDATE recycler_bids SET bid_status = 'WINNING', reviewed_at = CURRENT_TIMESTAMP, reviewed_by_officer_user_id = ? WHERE bid_id = ?", [$f['users']['officer'], $f['competitorBid']]);
    $exec("UPDATE e_lots SET lot_status = 'AWARDED' WHERE e_lot_id = ?", [$f['lot']]);
    $expected = ['bidCount' => 9, 'submittedCount' => 1, 'wonCount' => 6, 'rejectedCount' => 1, 'withdrawnCount' => 1, 'awardedCount' => 6, 'awaitingCount' => 2, 'handedCount' => 1];
    $expectedBids = [$bidIds[2], $bidIds[5], $bidIds[4], $bidIds[0], $bidIds[7]];
    $expectedAwards = [$bidIds[3], $bidIds[6], $bidIds[5], $bidIds[8], $bidIds[7]];
    foreach (['dashboard', 'reports'] as $method) {
        [$data, $html] = $page($method);
        foreach ($expected as $key => $count) $assert($data[$key] === $count, "$method owner-scoped $key");
        $assert(array_map('intval', array_column($data['recentBids'], 'bid_id')) === $expectedBids, "$method recent bids newest first, tie-break and five-row limit");
        $assert(array_map('intval', array_column($data['recentAwards'], 'bid_id')) === $expectedAwards, "$method recent awards by review time, tie-break and five-row limit");
        $assert(!str_contains($html, $f['tag'] . ' competitor') && !str_contains($html, 'Private competitor category'), "$method competitor privacy");
        if ($method === 'dashboard') {
            $assert($data['eligibleCount'] === 3, 'Only three remaining open eligible lots');
            $assert($data['compliance'] === ['verification_status' => 'VERIFIED', 'valid_license_expiry' => '2028-02-01'], 'Dashboard owner compliance');
            $assert(count($data['capabilities']) === 1 && $data['capabilities'][0]['category_name'] === $f['tag'] . ' Electronics', 'Dashboard owner capability table');
            foreach (['My Bids' => 9, 'Awarded E-Lots' => 6, 'Submitted Bids' => 1, 'Withdrawn Bids' => 1] as $label => $count) $assert(str_contains($html, $label . '</span><h2>' . $count . '</h2>'), 'Rendered dashboard ' . $label);
        } else {
            foreach (['Total Bids' => 9, 'Won Bids' => 6, 'Lost Bids' => 1, 'Withdrawn Bids' => 1, 'Awaiting Handover' => 2] as $label => $count) $assert(str_contains($html, $label . '</span><strong>' . $count . '</strong>'), 'Rendered report ' . $label);
            $assert(str_contains($html, 'Cancelled') && str_contains($html, 'Not recorded'), 'Cancelled and missing handovers rendered honestly');
            $assert(!str_contains($html, '/recycler/awarded-e-lot/' . $f['lot'] . '"'), 'Competitor award link absent');
        }
    }
    [$data, $html] = $page('profile');
    $assert($data['profile']['company_name'] === 'Owner <Recovery>' && str_contains($html, 'Owner &lt;Recovery&gt;'), 'Profile real company escaped');
    $assert($data['profile']['mobile_number'] === $f['mobiles']['recycler'] && str_contains($html, '2028-02-01'), 'Profile real phone and owner compliance');
    $assert(count($data['capabilities']) === 1 && !str_contains($html, '2029-03-01') && !str_contains($html, 'Private competitor category'), 'Profile owner scoping');
    $assert(!str_contains($html, 'GreenCycle') && !str_contains($html, 'data-recycler-dialog='), 'No fixed identity or fake edit controls');
    $model = new RecyclerBid();
    $assert($model->handoverForOwnedWinningBid($bidIds[3], $owner)['remarks'] === 'Collected <securely> & recorded', 'Model returns real remarks');
    [, $html] = $page('awardedELotDetails', $lotIds[3]);
    $assert(str_contains($html, 'Collected &lt;securely&gt; &amp; recorded') && !str_contains($html, 'Collected <securely>'), 'Award remarks rendered and escaped');
    foreach ([4, 7] as $i) {
        [, $html] = $page('awardedELotDetails', $lotIds[$i]);
        $assert(str_contains($html, '<span class="detail-label">Remarks</span><strong>Not recorded</strong>'), 'Null/missing remarks fallback');
    }
    $assert($model->handoverForOwnedWinningBid($bidIds[3], $other) === null, 'Competitor cannot read remarks');
    $login($other);
    ob_start(); (new RecyclerController())->awardedELotDetails((string) $lotIds[3]); $html = ob_get_clean();
    $assert(http_response_code() === 404 && !str_contains($html, 'Collected'), 'Award details reject competitor');
    [$data] = $page('reports');
    $assert($data['bidCount'] === 1 && $data['wonCount'] === 1 && $data['awaitingCount'] === 0 && $data['handedCount'] === 0, 'Second owner report excludes first owner and missing handover counts');
    $assert(array_map('intval', array_column($data['recentAwards'], 'bid_id')) === [(int) $f['competitorBid']], 'Second owner award activity isolated');
} finally {
    if ($db->inTransaction()) $db->rollBack();
    $db->exec('SET timestamp = DEFAULT');
}
echo "PASS: $checks Recycler reporting/profile/handover regression assertions (fixtures rolled back)\n";
