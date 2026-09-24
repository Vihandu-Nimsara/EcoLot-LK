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
    verify(count($model->openForPostalArea(1)) === 1, 'Open schedule missing');
    verify($model->openForPostalArea(2) === [], 'Wrong area offered');
    verify($model->isBookable($id, 1), 'Open schedule not bookable');
    verify(!$model->isBookable($id, 2), 'Wrong area bookable');
    verify(!$model->isBookable(999999, 1), 'Missing schedule bookable');
    $render = static function (): void {
        foreach (['newRequest' => 'New Pickup Request', 'myRequests' => 'My Requests'] as $method => $title) {
            ob_start();
            try { (new PublicUserController())->$method(); $html = ob_get_contents(); }
            finally { ob_end_clean(); }
            verify(str_contains($html, $title), $method . ' did not render');
        }
    };
    $render();
    $requestId = (new EWasteRequest())->create(['public_user_id' => 2, 'schedule_id' => $id, 'pickup_address' => 'Test']);
    verify($model->openForPostalArea(1) === [], 'Full schedule offered');
    verify(!$model->isBookable($id, 1), 'Full schedule bookable for new request');
    verify($model->isBookable($id, 1, $requestId), 'Existing request cannot retain its slot');
    $render();
    foreach (['PLANNED', 'CLOSED', 'CANCELLED', 'COMPLETED'] as $status) {
        $model->update($id, ['schedule_status' => $status]);
        verify($model->openForPostalArea(1) === [] && !$model->isBookable($id, 1, $requestId), 'Unavailable status offered: ' . $status);
    }
    $model->update($id, ['schedule_status' => 'OPEN', 'request_cutoff_at' => '2000-01-01 23:59:59']);
    verify($model->openForPostalArea(1) === [] && !$model->isBookable($id, 1, $requestId), 'Expired schedule offered');
    $render();
    echo "PASS: public pickup pages render with open/full/expired schedules; area, status, capacity and edit-slot eligibility checked\n";
} finally {
    $db->exec('USE `' . str_replace('`', '``', $originalDatabase) . '`');
    $db->exec("DROP DATABASE `$database`");
}
