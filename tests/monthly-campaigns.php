<?php
declare(strict_types=1);
require_once __DIR__ . '/support/bootstrap.php';
$db = Database::connection();
$original = $db->query('SELECT DATABASE()')->fetchColumn();
$name = 'ecolot_campaign_test_' . bin2hex(random_bytes(6));
$db->exec("CREATE DATABASE `$name`");
try {
    $db->exec("USE `$name`");
    TestDatabase::loadSchema($db);
    $month = TestDatabase::seedScheduleActors($db);
    $model = new MonthlyCampaign();
    $data = ['campaign_name' => 'Test campaign', 'campaign_month' => '2000-01-01', 'campaign_status' => 'OPEN'];
    foreach ([null, 2] as $id) {
        try { $model->saveManaged($id, $data, 1); throw new RuntimeException('Past creation/reopen accepted'); }
        catch (DomainException $e) {}
    }
    $model->saveManaged(2, array_replace($data, ['campaign_status' => 'CLOSED']), 1);
    try { $model->saveManaged(null, array_replace($data, ['campaign_month' => "$month-01"]), 1); throw new RuntimeException('Duplicate accepted'); }
    catch (PDOException $e) { verify((int) $e->errorInfo[1] === 1062, 'Unexpected database error'); }
    $db->exec("UPDATE monthly_campaigns SET campaign_status = 'OPEN' WHERE campaign_id = 2");
    $model->managementList();
    verify($model->find(2)['campaign_status'] === 'CLOSED', 'Expired campaign not closed');
    verify($model->find(1)['campaign_status'] === 'OPEN', 'Future campaign closed');
    $db->exec("INSERT INTO area_collection_schedules (campaign_id, postal_area_id, created_by_officer_user_id, collection_date, request_cutoff_at, request_capacity) VALUES (1,1,1,'$month-15','$month-10 23:59:59',5)");
    try { $model->saveManaged(1, array_replace($data, ['campaign_month' => '2099-12-01']), 1); throw new RuntimeException('Linked month changed'); }
    catch (DomainException $e) {}
    $model->saveManaged(1, array_replace($data, ['campaign_month' => "$month-01", 'campaign_status' => 'CLOSED']), 1);
    verify($db->query('SELECT schedule_status FROM area_collection_schedules')->fetchColumn() === 'PLANNED', 'Closing campaign mutated schedule');
    $db->exec("UPDATE monthly_campaigns SET campaign_status = 'OPEN' WHERE campaign_id = 2");
    $previousEnv = getenv('DB_DATABASE');
    putenv('DB_DATABASE=' . $name);
    try {
        for ($run = 0; $run < 2; $run++) {
            exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(APP_ROOT . '/bin/close-expired-campaigns.php'), $output, $status);
            verify($status === 0, 'Expiry CLI failed');
        }
    } finally {
        putenv($previousEnv === false ? 'DB_DATABASE' : 'DB_DATABASE=' . $previousEnv);
    }
    verify($model->find(2)['campaign_status'] === 'CLOSED', 'Expiry CLI did not close past campaign');
    echo "PASS: campaign expiry, duplicate protection, historical edit and linked schedule rules\n";
} finally {
    $db->exec("USE `$original`");
    $db->exec("DROP DATABASE `$name`");
}
