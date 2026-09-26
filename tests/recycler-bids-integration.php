<?php
declare(strict_types=1);
if (getenv('ECOLOT_BID_TEST') !== '1' || !str_starts_with((string) getenv('DB_DATABASE'), 'ecolot_test_')) {
    fwrite(STDERR, "Requires ECOLOT_BID_TEST=1 and an isolated DB_DATABASE=ecolot_test_* database with schema imported.\n"); exit(1);
}
require __DIR__ . '/recycler-bids.php';
require APP_ROOT . '/database/demo/recycler-bids-fixture.php';
$db = Database::connection();
$db->exec('SET timestamp = 1790244000');
$db->beginTransaction();
try { $f = recyclerBidFixture($db, bin2hex(random_bytes(12))); $db->commit(); }
catch (Throwable $e) { $db->rollBack(); throw $e; }
$service = new RecyclerBidService($db);
$bids = new RecyclerBid($db);
$lots = new ELot($db);
$user = $f['users']['recycler']; $other = $f['users']['competitor']; $lotId = $f['lot'];
Session::put('auth_user', ['id' => $user, 'role' => 'RECYCLER', 'name' => '<Recycler>']);
$checks = 0;
$assert = static function (bool $ok, string $message) use (&$checks): void { check($ok, $message); ++$checks; };
$reject = static function (callable $action, string $message, ?string $expected = null) use ($assert): void {
    try { $action(); } catch (DomainException $error) { $assert($expected === null || $error->getMessage() === $expected, $message . ': ' . $error->getMessage()); return; }
    throw new RuntimeException($message . ': unexpectedly accepted');
};
$exec = static function (string $sql, array $params = []) use ($db): void { $s = $db->prepare($sql); $s->execute($params); };
$assertActions = static function (bool $revise, bool $withdraw, string $label) use ($bids, $user, $assert): void {
    $rows = $bids->listForRecycler($user);
    $assert((bool) $rows[0]['can_revise'] === $revise, $label . ' revise availability');
    $assert((bool) $rows[0]['can_withdraw'] === $withdraw, $label . ' withdraw availability');
    ob_start();
    (new Controller())->view('recycler/my_bids', ['currentPage' => 'my-bids', 'bids' => $rows]);
    $html = ob_get_clean();
    $assert(str_contains($html, '>Revise Bid</a>') === $revise, $label . ' revise link');
    $assert(str_contains($html, '>Withdraw Bid</button>') === $withdraw, $label . ' withdraw form');
};
// Capture the existing PRG seam while exercising real controller validation and DB writes.
final class BidTestRedirect extends RuntimeException {}
final class BidTestController extends RecyclerController
{
    protected function redirect(string $path, int $status = 303): never
    {
        throw new BidTestRedirect($path, $status);
    }
}
$post = static function (string $action, int $id, array $data = []) use ($assert): string {
    $_POST = $data + ['_csrf_token' => Csrf::token()];
    try { (new BidTestController())->$action((string) $id); }
    catch (BidTestRedirect $redirect) {
        $assert($redirect->getCode() === 303, 'Mutation uses PRG');
        return $redirect->getMessage();
    }
    throw new RuntimeException('Expected a redirect from ' . $action);
};
$assert(count($lots->eligibleForRecycler($user)) === 1, 'Eligible matching lot appears once');
$assert(count($lots->itemDetails($lotId)) === 1, 'Valid provenance item loads');
$assert($lots->itemDetails($lotId)[0]['applied_risk_level'] === 'HIGH', 'Stored request risk used');
$reject(fn () => $service->placeBid($user, $lotId, '99.99'), 'Below-floor create', 'Bid amount must be at least Rs. 100.00.');
$bidId = $service->placeBid($user, $lotId, '100');
$bid = $bids->findOwnedBid($bidId, $user);
$assert($bid['bid_amount'] === '100.00' && $bid['bid_status'] === 'SUBMITTED', 'Positive bid below competitor persists');
$reject(fn () => $service->placeBid($user, $lotId, '200'), 'Duplicate rejected');
$assertActions(true, true, 'Open submitted bid');
$assert(count($bids->listForRecycler($user)) === 1 && $bids->listForRecycler($user)[0]['bid_id'] == $bidId, 'Own records only');
$reject(fn () => $service->updateBid($other, $bidId, '300'), 'Cross-owner revision rejected');
$reject(fn () => $service->withdrawBid($other, $bidId), 'Cross-owner withdrawal rejected');
$reject(fn () => $service->updateBid($user, $bidId, '100'), 'Equal revision rejected', 'Enter a different amount to revise your bid.');
$reject(fn () => $service->updateBid($user, $bidId, '99'), 'Below-floor revision rejected', 'Bid amount must be at least Rs. 100.00.');
$service->updateBid($user, $bidId, '150');
$updated = $bids->findOwnedBid($bidId, $user);
$assert($updated['bid_amount'] === '150.00', 'Revision below competitor accepted');
foreach (['submitted_at', 'e_lot_id', 'recycler_user_id', 'reviewed_at', 'reviewed_by_officer_user_id'] as $field) $assert($updated[$field] === $bid[$field], 'Revision preserves ' . $field);
// Revisions may move either direction; the competitor's amount is informational.
foreach (['400', '100', '550', '100.01', '1000'] as $amount) {
    $service->updateBid($user, $bidId, '500');
    $service->updateBid($user, $bidId, $amount);
    $assert($bids->findOwnedBid($bidId, $user)['bid_amount'] === RecyclerBidService::amount($amount), 'Allowed revision to ' . $amount);
}
$service->updateBid($user, $bidId, '500');
$reject(fn () => $service->updateBid($user, $bidId, '99.99'), '500 to below floor', 'Bid amount must be at least Rs. 100.00.');
$reject(fn () => $service->updateBid($user, $bidId, '500.00'), '500 unchanged', 'Enter a different amount to revise your bid.');
$service->updateBid($user, $bidId, '1500');
$assert(RecyclerBidService::suggestedAmount($bids->highestSubmittedForLot($lotId)) === '1600.00', 'Own highest suggestion');
$service->updateBid($user, $bidId, '400');
$assert(RecyclerBidService::suggestedAmount($bids->highestSubmittedForLot($lotId)) === '1100.00', 'Suggestion decreases with previous highest');
$service->updateBid($user, $bidId, '150');
$afterRevisions = $bids->findOwnedBid($bidId, $user);
foreach (['bid_id', 'submitted_at', 'e_lot_id', 'recycler_user_id', 'reviewed_at', 'reviewed_by_officer_user_id'] as $field) $assert($afterRevisions[$field] === $bid[$field], 'Downward revision preserves ' . $field);
// Use a second lot for create eligibility checks, preserving every bid row.
$exec("INSERT INTO e_lots (lot_code, created_by_collector_user_id, category_id, title, lot_status, verified_by_officer_user_id, verified_at, bidding_open_at, bidding_close_at) SELECT CONCAT(lot_code, '-2'), created_by_collector_user_id, category_id, title, lot_status, verified_by_officer_user_id, verified_at, bidding_open_at, bidding_close_at FROM e_lots WHERE e_lot_id = ?", [$lotId]);
$emptyLot = (int) $db->lastInsertId();
$cases = [
    ["UPDATE e_lots SET lot_status = 'CANCELLED' WHERE e_lot_id IN (?, ?)", "UPDATE e_lots SET lot_status = 'OPEN_FOR_BIDDING' WHERE e_lot_id IN (?, ?)", [$lotId, $emptyLot], 'Closed lot status'],
    ["UPDATE users SET account_status = 'SUSPENDED' WHERE user_id = ?", "UPDATE users SET account_status = 'ACTIVE' WHERE user_id = ?", [$user], 'Inactive account'],
    ["UPDATE authorized_recyclers SET verification_status = 'PENDING', verified_by_admin_user_id = NULL, verified_at = NULL WHERE user_id = ?", "UPDATE authorized_recyclers SET verification_status = 'VERIFIED', verified_by_admin_user_id = " . $f['users']['admin'] . ", verified_at = CURRENT_TIMESTAMP WHERE user_id = ?", [$user], 'Pending recycler'],
    ["UPDATE authorized_recyclers SET verification_status = 'REJECTED' WHERE user_id = ?", "UPDATE authorized_recyclers SET verification_status = 'VERIFIED' WHERE user_id = ?", [$user], 'Rejected recycler'],
    ["UPDATE recycler_licenses SET expiry_date = CURRENT_DATE - INTERVAL 1 DAY WHERE recycler_user_id = ?", "UPDATE recycler_licenses SET expiry_date = CURRENT_DATE WHERE recycler_user_id = ?", [$user], 'Expired licence'],
    ["UPDATE recycler_licenses SET license_status = 'REVOKED' WHERE recycler_user_id = ?", "UPDATE recycler_licenses SET license_status = 'VALID' WHERE recycler_user_id = ?", [$user], 'Invalid licence'],
    ["UPDATE recycler_capabilities SET capability_status = 'SUSPENDED' WHERE recycler_user_id = ?", "UPDATE recycler_capabilities SET capability_status = 'APPROVED' WHERE recycler_user_id = ?", [$user], 'Suspended capability'],
    ["UPDATE e_lots SET bidding_open_at = CURRENT_TIMESTAMP + INTERVAL 1 DAY WHERE e_lot_id IN (?, ?)", "UPDATE e_lots SET bidding_open_at = CURRENT_TIMESTAMP - INTERVAL 1 DAY WHERE e_lot_id IN (?, ?)", [$lotId, $emptyLot], 'Before open'],
    ["UPDATE e_lots SET bidding_close_at = CURRENT_TIMESTAMP WHERE e_lot_id IN (?, ?)", "UPDATE e_lots SET bidding_close_at = CURRENT_TIMESTAMP + INTERVAL 7 DAY WHERE e_lot_id IN (?, ?)", [$lotId, $emptyLot], 'Exact close'],
    ["UPDATE e_lots SET bidding_close_at = CURRENT_TIMESTAMP - INTERVAL 1 SECOND WHERE e_lot_id IN (?, ?)", "UPDATE e_lots SET bidding_close_at = CURRENT_TIMESTAMP + INTERVAL 7 DAY WHERE e_lot_id IN (?, ?)", [$lotId, $emptyLot], 'After close'],
];
foreach ($cases as [$change, $restore, $params, $label]) {
    $exec($change, $params);
    $reject(fn () => $service->placeBid($user, $emptyLot, '200'), $label . ' create');
    $reject(fn () => $service->updateBid($user, $bidId, '200'), $label . ' revise');
    $assert(!$lots->findVisibleForRecycler($emptyLot, $user), $label . ' hidden without bid');
    $assertActions(false, !in_array($label, ['Exact close', 'After close', 'Closed lot status'], true), $label);
    if (in_array($label, ['Exact close', 'After close', 'Closed lot status'], true)) $reject(fn () => $service->withdrawBid($user, $bidId), $label . ' withdraw', $label === 'Closed lot status' ? 'This E-Lot is not open for bidding.' : 'Bidding for this E-Lot has closed.');
    $exec($restore, $params);
}
$exec('INSERT INTO waste_categories (category_name) VALUES (?)', [$f['tag'] . ' Unmatched category']);
$unmatchedCategory = (int) $db->lastInsertId();
$exec('UPDATE recycler_capabilities SET category_id = ? WHERE recycler_user_id = ? AND category_id = ?', [$unmatchedCategory, $user, $f['category']]);
$reject(fn () => $service->placeBid($user, $emptyLot, '200'), 'Missing matching capability create');
$reject(fn () => $service->updateBid($user, $bidId, '200'), 'Missing matching capability revise');
$exec('UPDATE recycler_capabilities SET category_id = ? WHERE recycler_user_id = ? AND category_id = ?', [$f['category'], $user, $unmatchedCategory]);
// The database unique key remains authoritative even when bypassing the service precheck.
try {
    $exec('INSERT INTO recycler_bids (e_lot_id, recycler_user_id, bid_amount) VALUES (?, ?, 100)', [$lotId, $user]);
    throw new RuntimeException('Database accepted a duplicate bid');
} catch (PDOException $error) {
    $assert((int) $error->errorInfo[1] === 1062, 'Database duplicate constraint');
}
ob_start();
(new Controller())->view('recycler/e_lot_details', ['currentPage' => 'eligible-e-lots', 'lot' => $lots->findVisibleForRecycler($lotId, $user), 'items' => $lots->itemDetails($lotId)]);
$forms = ob_get_clean();
$assert(str_contains($forms, 'min="100.00"') && str_contains($forms, 'step="0.01"') && str_contains($forms, 'value="1100.00"'), 'Minimum and suggested revision rendered');
$assert(str_contains($forms, 'suggestion is optional'), 'Suggestion is not a restriction');
$assert(str_contains($forms, '/recycler/bid/' . $bidId . '/update') && str_contains($forms, '/recycler/bid/' . $bidId . '/withdraw'), 'Real numeric mutation form routes');
$assert(substr_count($forms, 'name="_csrf_token"') >= 2 && !str_contains($forms, 'name="remarks"'), 'Forms carry CSRF and omit remarks');
$assert(!str_contains($forms, 'data-recycler-dialog="edit-bid"'), 'Bid form bypasses demo interception');
$exec('UPDATE recycler_capabilities SET can_handle_high_risk = 0 WHERE recycler_user_id = ?', [$user]);
$reject(fn () => $service->updateBid($user, $bidId, '200'), 'High risk permission required');
$assert(!$lots->findVisibleForRecycler($lotId, $user)['can_bid'], 'High risk action unavailable');
$exec('UPDATE e_lot_items SET e_lot_id = ? WHERE e_lot_id = ?', [$emptyLot, $lotId]);
$reject(fn () => $service->placeBid($user, $emptyLot, '200'), 'High risk create rejected');
$exec('UPDATE e_lot_items SET e_lot_id = ? WHERE e_lot_id = ?', [$lotId, $emptyLot]);
$exec('UPDATE recycler_capabilities SET can_handle_high_risk = 1 WHERE recycler_user_id = ?', [$user]);
foreach (['WINNING', 'REJECTED', 'WITHDRAWN'] as $status) {
    $exec('UPDATE recycler_bids SET bid_status = ? WHERE bid_id = ?', [$status, $bidId]);
    $reject(fn () => $service->updateBid($user, $bidId, '200'), $status . ' revision rejected');
    $reject(fn () => $service->withdrawBid($user, $bidId), $status . ' withdrawal rejected');
    $assertActions(false, false, $status);
}
// Test setup restores SUBMITTED; production paths never reactivate withdrawn bids.
$exec("UPDATE recycler_bids SET bid_status = 'SUBMITTED' WHERE bid_id = ?", [$bidId]);
$exec("UPDATE recycler_licenses SET expiry_date = CURRENT_DATE - INTERVAL 1 DAY WHERE recycler_user_id = ?", [$user]);
$exec("UPDATE recycler_capabilities SET capability_status = 'SUSPENDED' WHERE recycler_user_id = ?", [$user]);
$assertActions(false, true, 'Expired licence withdrawal');
$assert($post('withdrawBid', $bidId) === '/recycler/e-lot/' . $lotId, 'Withdrawal returns to owned lot');
$assert(Session::pullFlash('bid_success') !== null, 'Withdrawal success flash');
$assert($bids->findOwnedBid($bidId, $user)['bid_status'] === 'WITHDRAWN', 'Withdrawal persists without eligibility');
$assert(count($bids->listForRecycler($user)) === 1, 'Withdrawn row remains in history');
$assert($bids->submittedCountForLot($lotId) === 1 && $bids->highestSubmittedForLot($lotId) === '1000.00', 'Withdrawn excluded from aggregates');
$exec("UPDATE recycler_bids SET bid_status = 'REJECTED' WHERE bid_id = ?", [$f['competitorBid']]);
$assert($bids->submittedCountForLot($lotId) === 0 && $bids->highestSubmittedForLot($lotId) === null, 'Rejected excluded from aggregates');
$exec("UPDATE recycler_licenses SET license_status = 'VALID', expiry_date = CURRENT_DATE WHERE recycler_user_id = ?", [$user]);
$exec("UPDATE recycler_capabilities SET capability_status = 'APPROVED' WHERE recycler_user_id = ?", [$user]);
$reject(fn () => $service->placeBid($user, $lotId, '200'), 'Withdrawn cannot be replaced');
$assert($lots->findVisibleForRecycler($lotId, $user)['bid_status'] === 'WITHDRAWN', 'Withdrawn lot still visible');
// Render the actual pages, checking that private competitor fields never reach HTML.
Session::put('auth_user', ['id' => $user, 'role' => 'RECYCLER', 'name' => '<Recycler>']);
foreach (['eligibleELots', 'myBids', 'eLotDetails', 'dashboard'] as $method) {
    ob_start(); (new RecyclerController())->$method((string) $lotId); $html = ob_get_clean();
    $assert(!str_contains($html, $f['tag'] . ' competitor'), 'Competitor name absent from ' . $method);
    $assert(!str_contains($html, 'recycler_user_id'), 'Ownership fields absent from ' . $method);
    $assert(str_contains($html, '&lt;Recycler&gt;'), 'Authenticated identity escaped on ' . $method);
}
$assert(!str_contains($html, 'DEMO-'), 'No demo bid records');
ob_start(); (new RecyclerController())->eLotDetails((string) $lotId); $withdrawnHtml = ob_get_clean();
$assert(!str_contains($withdrawnHtml, 'id="bid-form"') && !str_contains($withdrawnHtml, '>Withdraw Bid</button>'), 'Withdrawn user cannot place or mutate');
$assert(RecyclerBidService::suggestedAmount($bids->highestSubmittedForLot($lotId)) === '100.00', 'Rejected and withdrawn excluded from suggestion');
$reject(fn () => $service->withdrawBid($user, $bidId), 'No second withdrawal', 'This bid has already been withdrawn.');
// Extra fresh lots cover first defaults, fractional creates, ties and highest withdrawal.
$cloneLot = static function (string $suffix) use ($exec, $db, $emptyLot): int {
    $exec("INSERT INTO e_lots (lot_code, created_by_collector_user_id, category_id, title, lot_status, verified_by_officer_user_id, verified_at, bidding_open_at, bidding_close_at) SELECT CONCAT(lot_code, ?), created_by_collector_user_id, category_id, title, lot_status, verified_by_officer_user_id, verified_at, bidding_open_at, bidding_close_at FROM e_lots WHERE e_lot_id = ?", [$suffix, $emptyLot]);
    return (int) $db->lastInsertId();
};
$fresh = $cloneLot('-floor');
ob_start(); (new RecyclerController())->eLotDetails((string) $fresh); $freshHtml = ob_get_clean();
$assert(str_contains($freshHtml, 'value="100.00"') && str_contains($freshHtml, 'Minimum bid: Rs. 100.00'), 'First form default');
$freshBid = $service->placeBid($user, $fresh, '100.01');
$assert($bids->findOwnedBid($freshBid, $user)['bid_amount'] === '100.01', 'Fractional create succeeds');
$service->placeBid($other, $fresh, '100.01');
$assert($bids->submittedCountForLot($fresh) === 2, 'Equal competitor amount accepted');
$service->updateBid($user, $freshBid, '1000');
$service->withdrawBid($user, $freshBid);
$assert(RecyclerBidService::suggestedAmount($bids->highestSubmittedForLot($fresh)) === '200.01', 'Highest withdrawal lowers suggestion');
$exec("UPDATE recycler_bids SET bid_amount = 999999999999.99 WHERE e_lot_id = ? AND recycler_user_id = ?", [$fresh, $other]);
Session::put('auth_user', ['id' => $other, 'role' => 'RECYCLER', 'name' => 'Other']);
http_response_code(200);
ob_start(); (new RecyclerController())->eLotDetails((string) $fresh); $overflowHtml = ob_get_clean();
$assert(http_response_code() === 200 && str_contains($overflowHtml, 'Unavailable within the amount limit') && str_contains($overflowHtml, 'value="100.00"'), 'Overflow form remains usable');
$otherFresh = $bids->findForRecyclerAndLot($other, $fresh);
$service->updateBid($other, (int) $otherFresh['bid_id'], '100');
$assert($bids->findOwnedBid((int) $otherFresh['bid_id'], $other)['bid_amount'] === '100.00', 'Manual valid amount accepted after suggestion overflow');
Session::put('auth_user', ['id' => $user, 'role' => 'RECYCLER', 'name' => '<Recycler>']);
// Validate the full controller path, including ignoring forged ownership/status fields.
foreach (['0', '-1', 'bad'] as $amount) {
    $assert($post('placeBid', $emptyLot, ['bid_amount' => $amount]) === '/recycler/e-lot/' . $emptyLot . '#bid-form', 'Invalid create redirects safely');
    $assert(Session::pullFlash('bid_error') !== null, 'Invalid amount flash');
    $assert($bids->findForRecyclerAndLot($user, $emptyLot) === null, 'Invalid create inserts nothing');
}
$post('placeBid', $emptyLot, ['bid_amount' => '120', 'recycler_user_id' => $other, 'bid_status' => 'WINNING']);
$created = $bids->findForRecyclerAndLot($user, $emptyLot);
$assert($created !== null && $created['bid_status'] === 'SUBMITTED', 'POST cannot forge owner or winning status');
$assert($bids->findForRecyclerAndLot($other, $emptyLot) === null, 'Forged competitor ID ignored');
$assert(Session::pullFlash('bid_success') !== null, 'Create success flash');
$post('updateBid', (int) $created['bid_id'], ['bid_amount' => '125', 'bid_status' => 'REJECTED']);
$revised = $bids->findOwnedBid((int) $created['bid_id'], $user);
$assert($revised['bid_amount'] === '125.00' && $revised['bid_status'] === 'SUBMITTED', 'POST revises only amount');
$assert(Session::pullFlash('bid_success') !== null, 'Revision success flash');
$post('updateBid', (int) $created['bid_id'], ['bid_amount' => '125']);
$assert(Session::pullFlash('bid_error') !== null, 'Equal revision produces error flash');
// Officer-selected fixture status; Recycler routes cannot set it.
$exec("UPDATE recycler_bids SET bid_status = 'WINNING', reviewed_by_officer_user_id = ?, reviewed_at = CURRENT_TIMESTAMP WHERE bid_id = ?", [$f['users']['officer'], $created['bid_id']]);
$exec("UPDATE e_lots SET lot_status = 'AWARDED' WHERE e_lot_id = ?", [$emptyLot]);
$exec("INSERT INTO handover_records (winning_bid_id, handover_status, recorded_by_officer_user_id) VALUES (?, 'SCHEDULED', ?)", [$created['bid_id'], $f['users']['officer']]);
$assert(count($bids->listWinningForRecycler($user)) === 1, 'Only own actual winning bids listed');
$assert($bids->listWinningForRecycler($other) === [], 'Other recycler cannot see award');
$assert($bids->handoverForOwnedWinningBid((int) $created['bid_id'], $other) === null, 'Handover ownership enforced');
foreach (['awardedELots', 'awardedELotDetails'] as $method) {
    ob_start(); (new RecyclerController())->$method((string) $emptyLot); $html = ob_get_clean();
    $assert(str_contains($html, 'Scheduled') && str_contains($html, $f['tag']), 'Real award/handover rendered');
    $assert(!str_contains($html, '/update') && !str_contains($html, '/withdraw'), 'Awards are read-only');
    $assert(!str_contains($html, $f['tag'] . ' competitor'), 'No competitor identity in awards');
}
Session::put('auth_user', ['id' => $other, 'role' => 'RECYCLER', 'name' => 'Other']);
foreach (['updateBid', 'withdrawBid'] as $method) {
    $_POST = ['_csrf_token' => Csrf::token(), 'bid_amount' => '200'];
    ob_start(); (new RecyclerController())->$method((string) $created['bid_id']); ob_end_clean();
    $assert(http_response_code() === 404, 'Cross-owner POST rejected');
}
ob_start(); (new RecyclerController())->awardedELotDetails((string) $emptyLot); ob_end_clean();
$assert(http_response_code() === 404, 'Cross-owner awarded details rejected');
$css = file_get_contents(APP_ROOT . '/public/assets/css/recycler/theme.css');
$assert(str_contains($css, 'tr[hidden] { display: none !important; }'), 'Mobile filtered rows stay hidden');
foreach (['submitted', 'winning', 'withdrawn', 'rejected'] as $status) $assert(str_contains($css, '.badge-' . $status), 'Shared badge ' . $status);
$assert(str_contains(file_get_contents(APP_ROOT . '/public/assets/css/recycler/my_bids.css'), 'nth-child(7)'), 'Seven-column layout defined');
$assert(!$db->inTransaction(), 'No leaked transaction');
echo "PASS: $checks MariaDB recycler integration assertions (real schema and triggers)\n";
