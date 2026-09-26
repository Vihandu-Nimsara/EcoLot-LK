<?php
declare(strict_types=1);
// Keep existing runner output buffered so its session teardown can be followed by new sessions.
ob_start();
require __DIR__ . '/run.php';

foreach (['0', '0.01', '99.99', '-1', '0.00', 'abc', 'NaN', 'INF', '1.001', '1000000000000', '1e3', ''] as $invalid) {
    try { RecyclerBidService::amount($invalid); throw new RuntimeException('Invalid amount accepted: ' . $invalid); }
    catch (DomainException) {}
}
check(RecyclerBidService::amount(' 0100.2 ') === '100.20', 'Decimal normalization');
check(RecyclerBidService::amount('100.00') === '100.00', 'Absolute floor accepted');
check(RecyclerBidService::amount('100.01') === '100.01', 'Fraction above floor accepted');
check(RecyclerBidService::suggestedAmount(null) === '100.00', 'First suggestion');
check(RecyclerBidService::suggestedAmount('1000.00') === '1100.00', 'Highest plus 100');
check(RecyclerBidService::suggestedAmount('999999999899.99') === '999999999999.99', 'Suggestion upper boundary');
check(RecyclerBidService::suggestedAmount('999999999900.00') === null, 'Suggestion overflow safe');
check(RecyclerBidService::suggestedAmount('1250.75') === '1350.75', 'Exact decimal suggestion');
check(RecyclerBidService::amount('999999999999.99') === '999999999999.99', 'Maximum decimal');
Session::put('auth_user', ['id' => 27, 'role' => 'RECYCLER', 'name' => '<Recycler>']);
foreach (['placeBid', 'updateBid', 'withdrawBid'] as $method) {
    foreach (['0', '-1', 'abc', '1e2', '9999999999999999999999999'] as $id) {
        ob_start(); (new RecyclerController())->$method($id); ob_end_clean();
        check(http_response_code() === 404, 'Invalid numeric ID rejected');
    }
    $_POST = ['_csrf_token' => 'invalid', 'bid_amount' => '100'];
    ob_start(); (new RecyclerController())->$method('1'); ob_end_clean();
    check(http_response_code() === 403, 'Invalid CSRF rejected on ' . $method);
    $_POST['_csrf_token'] = ['bad'];
    ob_start(); (new RecyclerController())->$method('1'); ob_end_clean();
    check(http_response_code() === 403, 'Array CSRF rejected');
}
echo "PASS: recycler amount, ID and mutation CSRF checks\n";
