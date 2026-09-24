<?php
declare(strict_types=1);
// Keep existing runner output buffered so its session teardown can be followed by new sessions.
ob_start();
require __DIR__ . '/run.php';

foreach (['0', '-1', '0.00', 'abc', 'NaN', 'INF', '1.001', '1000000000000', '1e3', ''] as $invalid) {
    try { RecyclerBidService::amount($invalid); throw new RuntimeException('Invalid amount accepted: ' . $invalid); }
    catch (DomainException) {}
}
check(RecyclerBidService::amount(' 0001.2 ') === '1.20', 'Decimal normalization');
check(RecyclerBidService::amount('0.01') === '0.01', 'Small positive bid');
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
