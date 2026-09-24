<?php
declare(strict_types=1);

// Run against an isolated, disposable database; never writes the application database.
// php -d session.save_path=/tmp tests/area-schedules.php
// The configured DB account needs CREATE/DROP DATABASE privileges.
define('APP_ROOT', dirname(__DIR__));
spl_autoload_register(static function (string $class): void {
    foreach (['Core', 'Controllers', 'Models'] as $directory) {
        $file = APP_ROOT . '/app/' . $directory . '/' . $class . '.php';
        if (is_file($file)) { require_once $file; return; }
    }
});
function verify(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
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
    $schema = file_get_contents(APP_ROOT . '/database/schema.sql');
    $schema = preg_replace('/CREATE DATABASE IF NOT EXISTS `ecolot_lk`.*?;/s', '', $schema);
    $schema = str_replace('USE `ecolot_lk`;', '', $schema);
    [$tables, $triggers] = explode('DELIMITER $$', $schema, 2);
    foreach (explode(';', $tables) as $sql) {
        if (trim($sql) !== '') $db->exec($sql);
    }
    $triggers = str_replace('DELIMITER ;', '', $triggers);
    foreach (explode('$$', $triggers) as $sql) {
        if (trim($sql) !== '') $db->exec($sql);
    }
    $db->exec("INSERT INTO users (user_id, full_name, mobile_number, password_hash, role) VALUES
        (1, '<script>Officer</script>', '0771234567', 'unused', 'MUNICIPAL_OFFICER'),
        (2, 'Public', '0771234568', 'unused', 'PUBLIC_USER'),
        (3, 'Collector', '0771234569', 'unused', 'COLLECTOR')");
    $db->exec('INSERT INTO municipal_officers VALUES (1)');
    $db->exec('INSERT INTO collectors VALUES (3)');
    $db->exec("INSERT INTO postal_code_areas VALUES (1, '00100', 'Area One', 'ACTIVE'), (2, '00200', 'Area Two', 'ACTIVE'), (3, '00300', 'Inactive', 'INACTIVE')");
    $db->exec("INSERT INTO public_profiles VALUES (2, 1, 'Test address')");
    $month = (new DateTimeImmutable('first day of next month', new DateTimeZone('Asia/Colombo')))->format('Y-m');
    $statement = $db->prepare("INSERT INTO monthly_campaigns (campaign_id, created_by_officer_user_id, campaign_name, campaign_month, campaign_status) VALUES (1, 1, '<b>Campaign</b>', ?, 'OPEN'), (2, 1, 'Closed', '2000-01-01', 'CLOSED')");
    $statement->execute([$month . '-01']);

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
