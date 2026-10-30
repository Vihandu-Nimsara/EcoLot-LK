<?php
declare(strict_types=1);
require_once __DIR__ . '/support/bootstrap.php';
$db = Database::connection();
$original = $db->query('SELECT DATABASE()')->fetchColumn();
$name = 'ecolot_assignment_test_' . bin2hex(random_bytes(6));
$db->exec("CREATE DATABASE `$name`");
try {
    $db->exec("USE `$name`");
    TestDatabase::loadSchema($db);
    $month = TestDatabase::seedScheduleActors($db);
    $db->exec("UPDATE users SET account_status = 'ACTIVE' WHERE user_id = 3");
    $db->exec("INSERT INTO vehicles (vehicle_number,vehicle_type) VALUES ('TEST-1','Truck'),('TEST-2','Truck')");
    for ($i = 0; $i < 2; $i++) {
        $db->exec("INSERT INTO area_collection_schedules (campaign_id,postal_area_id,created_by_officer_user_id,collection_date,request_cutoff_at,request_capacity,schedule_status) VALUES (1,1,1,'$month-15','$month-10 23:59:59',10,'OPEN')");
        $id = $db->lastInsertId();
        $db->exec("INSERT INTO e_waste_requests (public_user_id,schedule_id,pickup_address) VALUES (2,$id,'Test')");
        $db->exec("UPDATE area_collection_schedules SET schedule_status = 'CLOSED' WHERE schedule_id = $id");
        // A second schedule on the same date must use another area (database uniqueness).
        if ($i === 0) $db->exec('UPDATE area_collection_schedules SET postal_area_id = 2 WHERE schedule_id = 1');
    }
    $model = new CollectionAssignment();
    $reject = static function (callable $operation): void {
        try { $operation(); } catch (DomainException $e) { return; }
        throw new RuntimeException('Invalid assignment accepted');
    };
    $db->exec("UPDATE e_waste_requests SET request_status = 'PENDING_REVIEW', risk_review_status = 'PENDING' WHERE schedule_id = 1");
    $reject(fn () => $model->change(1,'assign',3,1,1));
    $db->exec("UPDATE e_waste_requests SET request_status = 'SUBMITTED', risk_review_status = 'NOT_REQUIRED' WHERE schedule_id = 1");
    $db->exec("UPDATE vehicles SET availability_status = 'MAINTENANCE' WHERE vehicle_id = 1");
    $reject(fn () => $model->change(1,'assign',3,1,1));
    $db->exec("UPDATE vehicles SET availability_status = 'AVAILABLE' WHERE vehicle_id = 1");
    $model->change(1,'assign',3,1,1);
    verify($db->query('SELECT schedule_status FROM area_collection_schedules WHERE schedule_id = 1')->fetchColumn() === 'ASSIGNED', 'Schedule not assigned');
    $reject(fn () => $model->change(1,'assign',3,1,1));
    $reject(fn () => $model->change(2,'assign',3,2,1));
    $db->exec("INSERT INTO users (user_id,full_name,mobile_number,password_hash,role,account_status) VALUES (4,'Other collector','0771234570','unused','COLLECTOR','ACTIVE')");
    $db->exec('INSERT INTO collectors VALUES (4)');
    $reject(fn () => $model->change(2,'assign',4,1,1));
    $db->exec("UPDATE users SET account_status = 'SUSPENDED' WHERE user_id = 4");
    $reject(fn () => $model->change(2,'assign',4,2,1));

    $model->change(1,'unassign',null,null,1);
    verify($db->query('SELECT unassigned_at FROM schedule_assignments WHERE schedule_id = 1')->fetchColumn() !== null, 'Unassignment history lost');
    $model->change(2,'assign',3,1,1);
    $db->exec('INSERT INTO schedule_collections (schedule_id,submitted_by_collector_user_id) VALUES (2,3)');
    $reject(fn () => $model->change(2,'unassign',null,null,1));
    verify(count($model->listing()) === 2 && count($model->collectors()) === 1 && count($model->vehicles()) === 2, 'Live listing incorrect');
    echo "PASS: readiness, availability, duplicate/conflict protection, unassignment audit and collection lock\n";
} finally {
    $db->exec("USE `$original`");
    $db->exec("DROP DATABASE `$name`");
}
