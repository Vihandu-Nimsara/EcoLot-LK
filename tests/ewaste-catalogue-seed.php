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

    $seedFile = file_get_contents(APP_ROOT . '/database/seed.sql');
    // Exercise the real category/item section without importing demo accounts.
    $start = strpos($seedFile, 'INSERT INTO `waste_categories`');
    $end = strpos($seedFile, '-- End PDF e-waste catalogue');
    verify($start !== false && $end !== false, 'Catalogue seed section missing');
    $seed = substr($seedFile, $start, $end - $start);
    $seed = preg_replace('/^--.*$/m', '', $seed);
    $runSeed = static function () use ($db, $seed): void {
        foreach (explode(';', $seed) as $sql) {
            if (trim($sql) !== '') $db->exec($sql);
        }
    };
    $runSeed();
    $first = $db->query('SELECT category_id, item_name, collection_status, default_risk_level, item_status FROM e_waste_items ORDER BY waste_item_id')->fetchAll();
    verify(count($first) === 51, 'PDF item coverage mismatch');
    $runSeed();
    verify($first === $db->query('SELECT category_id, item_name, collection_status, default_risk_level, item_status FROM e_waste_items ORDER BY waste_item_id')->fetchAll(), 'Rerun changed or duplicated catalogue');
    $catalogue = (new EWasteItem())->pickupCatalogue();
    verify(count($catalogue) === 43, 'Pickup catalogue coverage mismatch');
    verify(count(array_filter($first, static fn ($item) => $item['collection_status'] === 'DO_NOT_COLLECT')) === 8, 'Exclusion coverage mismatch');
    verify(!in_array('Leaking batteries', array_column($catalogue, 'item_name'), true), 'Excluded item offered');
    $db->exec("UPDATE e_waste_items SET default_risk_level = 'HIGH', collection_status = 'REVIEW_REQUIRED', item_status = 'INACTIVE' WHERE item_name = 'LED lamps'");
    $runSeed();
    $preserved = $db->query("SELECT * FROM e_waste_items WHERE item_name = 'LED lamps'")->fetch();
    verify($preserved['default_risk_level'] === 'HIGH' && $preserved['collection_status'] === 'REVIEW_REQUIRED' && $preserved['item_status'] === 'INACTIVE', 'Seed overwrote managed policy');
    verify((int) $db->query('SELECT COUNT(*) FROM risk_rules')->fetchColumn() === 0, 'Seed invented risk rules');
    echo "PASS: PDF catalogue coverage, exclusion filtering, repeat import and existing-policy preservation\n";
} finally {
    if ($db->inTransaction()) $db->rollBack();
    $db->exec('USE `' . str_replace('`', '``', $originalDatabase) . '`');
    $db->exec("DROP DATABASE `$database`");
}
