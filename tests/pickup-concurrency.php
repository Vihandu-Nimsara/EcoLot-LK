<?php
declare(strict_types=1);

// Run against an isolated, disposable database; never writes the application database.
// php -d session.save_path=/tmp tests/pickup-concurrency.php
// The configured DB account needs CREATE/DROP DATABASE privileges.
require_once __DIR__ . '/support/bootstrap.php';
if (($argv[1] ?? '') === '--worker') {
    $database = $argv[2];
    if (!preg_match('/^ecolot_schedule_test_[a-f0-9]{12}$/D', $database)) throw new RuntimeException('Invalid test database');
    $config = require APP_ROOT . '/config/database.php';
    $connection = new class(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $database), $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]) extends PDO {
        public string $ready;
        public function prepare(string $query, array $options = []): PDOStatement|false {
            if (str_contains($query, 'area_collection_schedules WHERE schedule_id') && str_contains($query, 'FOR UPDATE')) touch($this->ready);
            return parent::prepare($query, $options);
        }
    };
    $connection->ready = $argv[3];
    $connection->exec("SET time_zone = '+05:30'");
    try {
        (new EWasteRequest($connection))->createWithItems(['public_user_id' => 2, 'schedule_id' => (int) $argv[4]], [['category' => 'Domestic E-Waste', 'item' => 'Lamp', 'quantity' => '1', 'weight' => '1', 'condition' => 'WORKING']]);
        echo 'CREATED';
    } catch (DomainException $error) { echo 'FULL'; }
    exit;
}
Session::start();
Session::put('auth_user', ['id' => 2, 'role' => 'PUBLIC_USER']);
set_error_handler(static function ($severity, $message, $file, $line): never {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$db = Database::connection();
$originalDatabase = (string) $db->query('SELECT DATABASE()')->fetchColumn();
$database = 'ecolot_schedule_test_' . bin2hex(random_bytes(6));
$db->exec("CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
try {
    $db->exec("USE `$database`");
    TestDatabase::loadSchema($db);
    $month = TestDatabase::seedScheduleActors($db);

    $model = new AreaCollectionSchedule();
    $id = $model->create(['campaign_id' => 1, 'postal_area_id' => 1,
        'created_by_officer_user_id' => 1, 'collection_date' => "$month-10",
        'request_cutoff_at' => "$month-08 23:59:59", 'request_capacity' => 1,
        'schedule_status' => 'OPEN']);

    $db->exec("INSERT INTO waste_categories (category_id, category_name) VALUES (1, 'Domestic E-Waste')");
    $db->exec("INSERT INTO e_waste_items (waste_item_id, category_id, item_name, collection_status) VALUES
        (1, 1, 'Lamp', 'ACCEPTED'), (2, 1, 'Battery', 'REVIEW_REQUIRED'), (3, 1, 'Forbidden', 'DO_NOT_COLLECT')");
    $db->exec("INSERT INTO risk_rules (waste_item_id, condition_type, risk_level, action_note) VALUES (1, 'DAMAGED', 'HIGH', 'Review damage')");
    $db->beginTransaction();
    $model->lockSchedule($id);
    $workers = [];
    $markers = [];
    try {
        for ($i = 0; $i < 2; $i++) {
            $marker = sys_get_temp_dir() . '/ecolot-race-' . bin2hex(random_bytes(8));
            $markers[] = $marker;
            $process = proc_open([PHP_BINARY, __FILE__, '--worker', $database, $marker, (string) $id], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            $workers[] = [$process, $pipes];
        }
        $deadline = microtime(true) + 10;
        while ((!file_exists($markers[0]) || !file_exists($markers[1])) && microtime(true) < $deadline) { clearstatcache(); usleep(10000); }
        verify(file_exists($markers[0]) && file_exists($markers[1]), 'Workers did not reach locked schedule');
        $db->commit();
        $results = [];
        foreach ($workers as [$process, $pipes]) {
            $results[] = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]);
            verify(proc_close($process) === 0 && $error === '', 'Worker failed: ' . $error);
        }
        $workers = [];
        sort($results);
        verify($results === ['CREATED', 'FULL'], 'Concurrent submissions overbooked: ' . implode(',', $results));
        verify($model->countActiveRequests($id) === 1, 'Capacity exceeded');
        echo "PASS: simultaneous submissions serialize; exactly one request occupies the last slot\n";
    } finally {
        if ($db->inTransaction()) $db->rollBack();
        foreach ($workers as [$process, $pipes]) { proc_terminate($process); foreach ($pipes as $pipe) if (is_resource($pipe)) fclose($pipe); proc_close($process); }
        foreach ($markers as $marker) if (file_exists($marker)) unlink($marker);
    }
} finally {
    if ($db->inTransaction()) $db->rollBack();
    $db->exec('USE `' . str_replace('`', '``', $originalDatabase) . '`');
    $db->exec("DROP DATABASE `$database`");
}
