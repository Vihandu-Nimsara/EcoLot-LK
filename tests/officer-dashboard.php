<?php
declare(strict_types=1);
require_once __DIR__ . '/support/bootstrap.php';
$db = Database::connection();
$original = $db->query('SELECT DATABASE()')->fetchColumn();
$name = 'ecolot_dashboard_test_' . bin2hex(random_bytes(6));
$db->exec("CREATE DATABASE `$name`");
try {
    $db->exec("USE `$name`");
    TestDatabase::loadSchema($db);
    $model = new OfficerDashboard();
    $empty = $model->overview();
    verify(array_sum($empty['stats']) === 0 && $empty['schedules'] === [], 'Empty dashboard incorrect');
    $month = TestDatabase::seedScheduleActors($db);
    $db->exec("INSERT INTO area_collection_schedules (campaign_id,postal_area_id,created_by_officer_user_id,collection_date,request_cutoff_at,request_capacity,schedule_status) VALUES
        (1,1,1,'$month-15','$month-10 23:59:59',5,'OPEN'),
        (1,2,1,'$month-12','$month-10 23:59:59',5,'OPEN'),
        (1,1,1,'$month-20','$month-10 23:59:59',5,'CANCELLED')");
    $db->exec("INSERT INTO e_waste_requests (public_user_id,schedule_id,pickup_address,request_status) VALUES (2,1,'Test','SUBMITTED')");
    $db->exec("UPDATE area_collection_schedules SET schedule_status = 'CLOSED' WHERE schedule_id = 1");
    $db->exec("UPDATE monthly_campaigns SET campaign_status = 'OPEN' WHERE campaign_id = 2");
    $data = $model->overview();
    verify($db->query('SELECT campaign_status FROM monthly_campaigns WHERE campaign_id = 2')->fetchColumn() === 'CLOSED', 'Dashboard did not catch up expired campaign');
    verify((int) $data['stats']['campaigns'] === 1, 'Open campaign count incorrect');
    verify((int) $data['stats']['assignments'] === 1, 'Ready assignment count incorrect');
    verify(array_column($data['schedules'], 'schedule_id') === [2,1], 'Upcoming ordering/exclusion incorrect');
    verify((int) $data['schedules'][1]['request_count'] === 1, 'Request count incorrect');
    $db->exec("UPDATE e_waste_requests SET request_status = 'PENDING_REVIEW', risk_review_status = 'PENDING'");
    $data = $model->overview();
    verify((int) $data['stats']['reviews'] === 1 && (int) $data['stats']['assignments'] === 0, 'Unreviewed request did not block readiness');
    $db->exec("UPDATE e_waste_requests SET request_status = 'CANCELLED'");
    verify((int) $model->overview()['schedules'][1]['request_count'] === 0, 'Cancelled request consumes capacity');
    echo "PASS: dashboard empty state, live counts, assignment readiness, ordering and cancelled exclusions\n";
} finally {
    $db->exec("USE `$original`");
    $db->exec("DROP DATABASE `$name`");
}
