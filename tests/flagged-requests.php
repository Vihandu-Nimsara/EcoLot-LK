<?php
declare(strict_types=1);
require_once __DIR__ . '/support/bootstrap.php';
$db = Database::connection();
$original = $db->query('SELECT DATABASE()')->fetchColumn();
$name = 'ecolot_review_test_' . bin2hex(random_bytes(6));
$db->exec("CREATE DATABASE `$name`");
try {
    $db->exec("USE `$name`");
    TestDatabase::loadSchema($db);
    $month = TestDatabase::seedScheduleActors($db);
    $db->exec("INSERT INTO area_collection_schedules (campaign_id,postal_area_id,created_by_officer_user_id,collection_date,request_cutoff_at,request_capacity,schedule_status) VALUES (1,1,1,'$month-15','$month-10 23:59:59',10,'OPEN')");
    for ($i = 0; $i < 3; $i++) $db->exec("INSERT INTO e_waste_requests (public_user_id,schedule_id,pickup_address,request_status,risk_review_status) VALUES (2,1,'Test','PENDING_REVIEW','PENDING')");
    $model = new FlaggedRequest();
    $reject = static function (callable $operation): void {
        try { $operation(); } catch (DomainException $e) { return; }
        throw new RuntimeException('Invalid review accepted');
    };
    $reject(fn () => $model->decide(1, 'REJECTED', '   ', 1));
    $reject(fn () => $model->decide(1, 'PENDING', '', 1));
    $reject(fn () => $model->decide(1, 'APPROVED', str_repeat('a', 501), 1));
    $model->decide(1, 'APPROVED', 'Handle carefully', 1);
    $row = $db->query('SELECT * FROM e_waste_requests WHERE request_id = 1')->fetch();
    verify($row['request_status'] === 'APPROVED' && $row['risk_review_status'] === 'APPROVED' && (int) $row['reviewed_by_officer_user_id'] === 1 && $row['reviewed_at'] !== null, 'Approval audit missing');
    $reject(fn () => $model->decide(1, 'REJECTED', 'Stale decision', 1));
    $model->decide(2, 'REJECTED', 'Cannot safely collect', 1);
    verify($db->query('SELECT request_status FROM e_waste_requests WHERE request_id = 2')->fetchColumn() === 'REJECTED', 'Rejection not persisted');
    $db->exec("UPDATE e_waste_requests SET request_status = 'CANCELLED' WHERE request_id = 3");
    $reject(fn () => $model->decide(3, 'APPROVED', '', 1));
    verify(count($model->listing()) === 3, 'Review history missing');
    $reject(fn () => $model->decide(999, 'APPROVED', '', 1));
    echo "PASS: risk decisions, audit, rejection notes, stale decisions and cancellation protection\n";
} finally {
    $db->exec("USE `$original`");
    $db->exec("DROP DATABASE `$name`");
}
