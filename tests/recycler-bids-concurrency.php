<?php
declare(strict_types=1);
if (getenv('ECOLOT_BID_TEST') !== '1' || !str_starts_with((string) getenv('DB_DATABASE'), 'ecolot_test_')) {
    fwrite(STDERR, "Requires an explicitly enabled ecolot_test_* database.\n"); exit(1);
}
define('APP_ROOT', dirname(__DIR__));
spl_autoload_register(static function (string $class): void {
    foreach (['Core', 'Models', 'Services'] as $dir) {
        $path = APP_ROOT . '/app/' . $dir . '/' . $class . '.php';
        if (is_file($path)) { require_once $path; return; }
    }
});
$db = Database::connection();
if (($argv[1] ?? '') === 'worker') {
    echo $db->query('SELECT CONNECTION_ID()')->fetchColumn() . "\n";
    flush();
    try {
        (new RecyclerBidService($db))->updateBid((int) $argv[2], (int) $argv[3], '400');
        echo "REVISED\n";
    } catch (DomainException $error) { echo $error->getMessage() . "\n"; }
    exit;
}
require APP_ROOT . '/database/demo/recycler-bids-fixture.php';
$db->beginTransaction();
try { $f = recyclerBidFixture($db, bin2hex(random_bytes(12))); $db->commit(); }
catch (Throwable $error) { $db->rollBack(); throw $error; }
$bid = (new RecyclerBidService($db))->placeBid($f['users']['recycler'], $f['lot'], '500');
foreach ([false, true] as $withdrawWhileWaiting) {
    $db->beginTransaction();
    (new ELot($db))->lockForBidding($f['lot']);
    $process = proc_open([PHP_BINARY, __FILE__, 'worker', (string) $f['users']['recycler'], (string) $bid],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Could not start worker');
    fclose($pipes[0]);
    stream_set_timeout($pipes[1], 10);
    try {
        $connectionId = (int) trim((string) fgets($pipes[1]));
        if (!$connectionId) throw new RuntimeException('Worker did not connect');
        // MariaDB may report a prepared locking read as "Statistics", rather
        // than publishing it in INNODB_TRX. Observe the independent query itself.
        $blocked = false;
        $until = microtime(true) + 5;
        do {
            foreach ($db->query('SHOW PROCESSLIST')->fetchAll() as $row) {
                if ((int) $row['Id'] === $connectionId && str_contains((string) $row['Info'], 'FROM e_lots') && str_contains((string) $row['Info'], 'FOR UPDATE')) {
                    $blocked = true;
                }
            }
            if (!$blocked) usleep(50000);
        } while (!$blocked && microtime(true) < $until);
        if (!$blocked) throw new RuntimeException('Worker locking query was not observed');
        $read = [$pipes[1]]; $write = null; $except = null;
        if (stream_select($read, $write, $except, 0, 200000) !== 0) {
            throw new RuntimeException('Worker completed before the parent released the lot');
        }
        if ($withdrawWhileWaiting) (new RecyclerBid($db))->withdrawOwnedSubmittedBid($bid, $f['users']['recycler']);
        $db->commit();
        $result = trim((string) fgets($pipes[1]));
        $expected = $withdrawWhileWaiting ? 'This bid has already been withdrawn.' : 'REVISED';
        if ($result !== $expected) throw new RuntimeException('Unexpected worker result: ' . $result);
    } finally {
        if ($db->inTransaction()) $db->rollBack();
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        $status = proc_close($process);
    }
    if ($status !== 0 || $errors !== '') throw new RuntimeException('Worker failed: ' . $errors);
}
$row = (new RecyclerBid($db))->findOwnedBid($bid, $f['users']['recycler']);
if ($row['bid_status'] !== 'WITHDRAWN' || $row['bid_amount'] !== '400.00') throw new RuntimeException('Final state incorrect');
echo "PASS: independent MariaDB sessions serialize revisions on the lot and re-read withdrawal after waiting\n";
