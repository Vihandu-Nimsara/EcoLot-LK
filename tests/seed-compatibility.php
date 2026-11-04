<?php
declare(strict_types=1);

// Run against an isolated, disposable database; never writes the application database.
// Tests the current-schema demonstration seed contract; it intentionally resets demo data.
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
    $seed = file_get_contents(APP_ROOT . '/database/seed.sql');
    $seed = str_replace('USE `ecolot_lk`;', '', $seed);
    $seed = preg_replace('/^--.*$/m', '', $seed);
    $runSeed = static function () use ($db, $seed): void {
        $db->exec($seed);
    };
    $runSeed();
    $runSeed();
    $row = $db->query('SELECT * FROM authorized_recyclers')->fetch();
    verify($row['verification_status'] === 'VERIFIED' && $row['reviewed_by_admin_user_id'] !== null && $row['reviewed_at'] !== null, 'Current schema recycler audit failed');
    verify((int) $db->query('SELECT COUNT(*) FROM e_waste_items')->fetchColumn() === 51, 'Catalogue incomplete');
    verify((int) $db->query('SELECT COUNT(*) FROM public_profiles')->fetchColumn() === 4, 'Seed stopped before public profiles');
    $counts = [];
    foreach (['users','public_profiles','authorized_recyclers','monthly_campaigns','area_collection_schedules','e_waste_requests','handover_records'] as $table) {
        $counts[$table] = (int) $db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    }
    verify($counts['authorized_recyclers'] === 2, 'Recycler fixtures incomplete');
    verify((int) $db->query("SELECT COUNT(*) FROM handover_records h JOIN recycler_bids b ON b.bid_id = h.winning_bid_id JOIN e_lots l ON l.e_lot_id = b.e_lot_id WHERE h.handover_status = 'COMPLETED' AND b.bid_status = 'WINNING' AND l.lot_status = 'COMPLETED'")->fetchColumn() > 0, 'Completed handover timeline inconsistent');
    $db->exec("UPDATE public_profiles SET address = 'Changed demo address'");
    $db->exec("UPDATE users SET password_hash = 'changed' WHERE mobile_number = '94775555555'");
    $runSeed();
    foreach ($counts as $table => $count) verify((int) $db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn() === $count, "Repeat seed duplicated $table");
    verify($db->query("SELECT address FROM public_profiles p JOIN users u ON u.user_id = p.user_id WHERE u.mobile_number = '94775555555'")->fetchColumn() === '18 Marine Drive, Wellawatte', 'Demo address not restored');
    verify(password_verify('Password123', $db->query("SELECT password_hash FROM users WHERE mobile_number = '94775555555'")->fetchColumn()), 'Demo login not restored');
    echo "PASS: current-schema demo seed repeatability, natural keys, catalogue, demo login reset and completed handover timeline\n";
} finally {
    if ($db->inTransaction()) $db->rollBack();
    $db->exec('USE `' . str_replace('`', '``', $originalDatabase) . '`');
    $db->exec("DROP DATABASE `$database`");
}
