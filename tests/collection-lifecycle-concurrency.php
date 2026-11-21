<?php
declare(strict_types=1);
require_once __DIR__ . '/support/bootstrap.php';
if (($argv[1] ?? '') === '--worker') {
    $name = $argv[2];
    if (!preg_match('/^ecolot_lifecycle_test_[a-f0-9]{12}$/D', $name)) throw new RuntimeException('Invalid test database');
    $config = require APP_ROOT . '/config/database.php';
    $db = new class(sprintf('mysql:host=%s;port=%s;dbname=%s', $config['host'], $config['port'], $name), $config['username'], $config['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false]) extends PDO {
        public string $marker;
        public function prepare(string $sql, array $options = []): PDOStatement|false {
            if (str_contains($sql, 'area_collection_schedules WHERE schedule_id') && str_contains($sql, 'FOR UPDATE')) touch($this->marker);
            return parent::prepare($sql, $options);
        }
    };
    $db->marker = $argv[3];
    try {
        $records = new CollectionRecord($db);
        match ($argv[4]) {
            'save' => $records->saveDraft(1,3,['items'=>[1=>['actual_quantity'=>'1','actual_weight_kg'=>'1','actual_condition'=>'WORKING','notes'=>'']]]),
            'delete' => $records->deleteDraft((int)$argv[5],3),
            'submit' => $records->submitSchedule(1,3),
            'assign' => (new CollectionAssignment($db))->change(1,'assign',3,1,1),
            'unassign' => (new CollectionAssignment($db))->change(1,'unassign',null,null,1),
        };
        echo 'OK';
    } catch (DomainException $e) { echo 'REJECTED'; }
    exit;
}
$db = Database::connection();
$original = $db->query('SELECT DATABASE()')->fetchColumn();
$name = 'ecolot_lifecycle_test_' . bin2hex(random_bytes(6));
$db->exec("CREATE DATABASE `$name`");
try {
    $db->exec("USE `$name`");
    TestDatabase::loadSchema($db);
    $month = TestDatabase::seedScheduleActors($db);
    $db->exec("UPDATE users SET account_status='ACTIVE'");
    $db->exec("INSERT INTO vehicles (vehicle_id,vehicle_number,vehicle_type) VALUES (1,'RACE-1','Truck')");
    $db->exec("INSERT INTO waste_categories (category_id,category_name) VALUES (1,'Test')");
    $db->exec("INSERT INTO e_waste_items (waste_item_id,category_id,item_name) VALUES (1,1,'Device')");
    $db->exec("INSERT INTO area_collection_schedules (schedule_id,campaign_id,postal_area_id,created_by_officer_user_id,collection_date,request_cutoff_at,request_capacity,schedule_status) VALUES (1,1,1,1,'$month-15','$month-10 23:59:59',10,'OPEN')");
    $db->exec("INSERT INTO e_waste_requests (request_id,public_user_id,schedule_id,pickup_address) VALUES (1,2,1,'Test')");
    $db->exec("INSERT INTO request_items (request_item_id,request_id,waste_item_id,quantity,item_condition,applied_risk_level) VALUES (1,1,1,1,'WORKING','LOW')");
    $db->exec("UPDATE area_collection_schedules SET schedule_status='CLOSED' WHERE schedule_id=1");
    $race = static function (array $actions, int $recordId = 0) use ($db, $name): array {
        $workers = []; $markers = [];
        $db->beginTransaction();
        (new AreaCollectionSchedule($db))->lockSchedule(1);
        try {
            foreach ($actions as $action) {
                $marker = sys_get_temp_dir().'/ecolot-lifecycle-'.bin2hex(random_bytes(8));
                $markers[] = $marker;
                $process = proc_open([PHP_BINARY,__FILE__,'--worker',$name,$marker,$action,(string)$recordId], [1=>['pipe','w'],2=>['pipe','w']], $pipes);
                $workers[] = [$process,$pipes];
            }
            $deadline = microtime(true)+10;
            do { clearstatcache(); $ready = count(array_filter($markers,'file_exists')) === count($markers); if (!$ready) usleep(10000); } while (!$ready && microtime(true)<$deadline);
            verify($ready,'Workers did not reach schedule lock');
            $db->commit();
            $results = [];
            foreach ($workers as [$process,$pipes]) {
                $results[] = stream_get_contents($pipes[1]); $error = stream_get_contents($pipes[2]);
                fclose($pipes[1]); fclose($pipes[2]);
                verify(proc_close($process) === 0 && $error === '', 'Worker failure or deadlock: '.$error);
            }
            $workers=[];
            sort($results);
            verify($results === ['OK','REJECTED'], 'Race allowed contradictory operations: '.json_encode($results));
            return $results;
        } finally {
            if ($db->inTransaction()) $db->rollBack();
            foreach ($workers as [$process,$pipes]) { proc_terminate($process); foreach ($pipes as $pipe) if (is_resource($pipe)) fclose($pipe); proc_close($process); }
            foreach ($markers as $marker) if (file_exists($marker)) unlink($marker);
        }
    };
    $race(['assign','assign']);
    verify((int)$db->query('SELECT COUNT(*) FROM schedule_assignments WHERE unassigned_at IS NULL')->fetchColumn() === 1,'Duplicate active assignments');
    $race(['save','unassign']);
    $records = new CollectionRecord($db);
    $schedule = (new AreaCollectionSchedule($db))->find(1);
    if ($schedule['schedule_status'] === 'CLOSED') {
        verify($records->all() === [] && (int)$db->query('SELECT COUNT(*) FROM schedule_collections')->fetchColumn() === 0,'Unassign left collection work');
        (new CollectionAssignment($db))->change(1,'assign',3,1,1);
        $records->saveDraft(1,3,['items'=>[1=>['actual_quantity'=>'1','actual_weight_kg'=>'1','actual_condition'=>'WORKING','notes'=>'']]]);
    } else {
        verify($schedule['schedule_status'] === 'IN_PROGRESS' && count($records->all()) === 1,'Save race left inconsistent schedule');
    }
    $race(['delete','submit'], (int)$records->all()[0]['collection_record_id']);
    $schedule = (new AreaCollectionSchedule($db))->find(1);
    $batch = (new ScheduleCollection($db))->forSchedule(1);
    verify(($schedule['schedule_status'] === 'ASSIGNED' && $batch === null && $records->all() === [])
        || ($schedule['schedule_status'] === 'COLLECTION_SUBMITTED' && $batch['verification_status'] === 'PENDING' && count($records->all()) === 1), 'Delete/submit race corrupted lifecycle');
    echo "PASS: concurrent assign/assign, save/unassign, delete/submit serialize and reject stale writes without deadlocks\n";
} finally {
    if ($db->inTransaction()) $db->rollBack();
    $db->exec("USE `$original`"); $db->exec("DROP DATABASE `$name`");
}
