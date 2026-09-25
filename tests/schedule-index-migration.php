<?php
declare(strict_types=1);

// Run against an isolated, disposable database; never writes the application database.
// php -d session.save_path=/tmp tests/schedule-index-migration.php
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
