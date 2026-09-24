<?php
declare(strict_types=1);

// Run against an isolated, disposable database; never writes the application database.
// php -d session.save_path=/tmp tests/ewaste-catalogue-seed.php
// The configured DB account needs CREATE/DROP DATABASE privileges.
require_once __DIR__ . '/support/bootstrap.php';
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
