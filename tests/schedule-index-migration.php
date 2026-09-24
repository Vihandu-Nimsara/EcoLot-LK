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
    $db->exec('ALTER TABLE area_collection_schedules ADD UNIQUE KEY uq_schedules_campaign_area (campaign_id, postal_area_id)');
    $db->exec('ALTER TABLE area_collection_schedules DROP INDEX uq_schedules_campaign_area_date');
    $before = $model->all();
    $sql = preg_replace('/^--.*$/m', '', file_get_contents(APP_ROOT . '/database/migrations/20260924_schedule_date_uniqueness.sql'));
    for ($run = 0; $run < 2; $run++) {
        foreach (explode(';', $sql) as $statement) if (trim($statement) !== '') $db->exec($statement);
    }
    verify($before === $model->all(), 'Migration changed records');
    $attributes = $before[0];
    unset($attributes['schedule_id']);
    $attributes['collection_date'] = "$month-20";
    $model->create($attributes);
    verify(count($model->all()) === 2, 'Second date blocked');
    try { $model->create($attributes); throw new RuntimeException('Duplicate date accepted'); }
    catch (PDOException $expected) { verify((int) $expected->errorInfo[1] === 1062, 'Unexpected insert failure'); }
    echo "PASS: legacy index migration, retry, record preservation, second date and duplicate-date rejection\n";
} finally {
    $db->exec('USE `' . str_replace('`', '``', $originalDatabase) . '`');
    $db->exec("DROP DATABASE `$database`");
}
