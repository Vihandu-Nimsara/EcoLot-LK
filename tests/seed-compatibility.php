<?php
declare(strict_types=1);

// Run against an isolated, disposable database; never writes the application database.
// php -d session.save_path=/tmp tests/seed-compatibility.php
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
        foreach (explode(';', $seed) as $sql) {
            if (trim($sql) !== '') $db->exec($sql);
        }
    };
    $runSeed();
    $runSeed();
    $row = $db->query('SELECT * FROM authorized_recyclers')->fetch();
    verify($row['verification_status'] === 'VERIFIED' && $row['reviewed_by_admin_user_id'] !== null && $row['reviewed_at'] !== null, 'Current schema recycler audit failed');
    verify((int) $db->query('SELECT COUNT(*) FROM e_waste_items')->fetchColumn() === 51, 'Catalogue incomplete');
    verify((int) $db->query('SELECT COUNT(*) FROM public_profiles')->fetchColumn() === 1, 'Seed stopped before public profile');
    $db->exec("UPDATE public_profiles SET address = 'User edited address'");
    $db->exec("UPDATE postal_code_areas SET area_name = 'Edited area', area_status = 'INACTIVE' WHERE postal_code = '11100'");
    $db->exec("UPDATE waste_categories SET category_status = 'INACTIVE' WHERE category_name = 'Office E-Waste'");
    $hash = password_hash('ChangedPassword!', PASSWORD_DEFAULT);
    $db->prepare("UPDATE users SET password_hash = ? WHERE mobile_number = '94775555555'")->execute([$hash]);
    $runSeed();
    verify($db->query('SELECT address FROM public_profiles')->fetchColumn() === 'User edited address', 'Seed overwrote user address');
    verify($db->query("SELECT area_name FROM postal_code_areas WHERE postal_code = '11100'")->fetchColumn() === 'Edited area', 'Seed overwrote area name');
    verify($db->query("SELECT category_status FROM waste_categories WHERE category_name = 'Office E-Waste'")->fetchColumn() === 'INACTIVE', 'Seed reactivated category');
    verify($db->query("SELECT password_hash FROM users WHERE mobile_number = '94775555555'")->fetchColumn() === $hash, 'Seed reset password');
    $db->exec('ALTER TABLE authorized_recyclers DROP CONSTRAINT chk_recycler_verification_audit');
    $db->exec('ALTER TABLE authorized_recyclers DROP FOREIGN KEY fk_authorized_recyclers_reviewed_by_admin');
    $db->exec('ALTER TABLE authorized_recyclers CHANGE reviewed_by_admin_user_id verified_by_admin_user_id BIGINT UNSIGNED NULL, CHANGE reviewed_at verified_at DATETIME NULL');
    $db->exec('DELETE FROM authorized_recyclers');
    $runSeed();
    $row = $db->query('SELECT * FROM authorized_recyclers')->fetch();
    verify($row['verification_status'] === 'VERIFIED' && $row['verified_by_admin_user_id'] !== null && $row['verified_at'] !== null, 'Legacy schema recycler audit failed');
    $db->exec("UPDATE authorized_recyclers SET verification_status = 'REJECTED'");
    $runSeed();
    verify($db->query('SELECT verification_status FROM authorized_recyclers')->fetchColumn() === 'REJECTED', 'Repeat seed overwrote existing review decision');
    verify((int) $db->query('SELECT COUNT(*) FROM authorized_recyclers')->fetchColumn() === 1, 'Duplicate recycler');
    echo "PASS: full seed imports and reruns on current/legacy recycler schemas, preserves review decisions, and includes catalogue/public profile\n";
} finally {
    if ($db->inTransaction()) $db->rollBack();
    $db->exec('USE `' . str_replace('`', '``', $originalDatabase) . '`');
    $db->exec("DROP DATABASE `$database`");
}
