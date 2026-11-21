<?php
declare(strict_types=1);
require_once __DIR__ . '/support/bootstrap.php';
require_once __DIR__ . '/support/CollectionReviewFixture.php';
if (($argv[1] ?? '') === '--worker') {
    $name = $argv[2];
    if (!preg_match('/^ecolot_review_test_[a-f0-9]{12}$/D', $name)) throw new RuntimeException('Invalid test database');
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
        (new ScheduleCollection($db))->decide(1,1,$argv[4],$argv[4] === 'REJECTED' ? 'Concurrent rejection' : 'Concurrent verification');
        echo 'OK';
    } catch (DomainException $e) {
        if ($e->getCode() !== 409) throw $e;
        echo 'REJECTED';
    }
    exit;
}
$db = Database::connection();
$original = $db->query('SELECT DATABASE()')->fetchColumn();
$name = 'ecolot_review_test_' . bin2hex(random_bytes(6));
$db->exec("CREATE DATABASE `$name`");
try {
    $db->exec("USE `$name`");
    TestDatabase::loadSchema($db);
    CollectionReviewFixture::seed($db);
    $before=$db->query('SELECT * FROM collection_record_items ORDER BY record_item_id')->fetchAll();
    $race = static function (array $actions) use ($db, $name): array {
        $workers = []; $markers = [];
        $db->beginTransaction();
        (new AreaCollectionSchedule($db))->lockSchedule(1);
        try {
            foreach ($actions as $action) {
                $marker = sys_get_temp_dir().'/ecolot-review-race-'.bin2hex(random_bytes(8));
                $markers[] = $marker;
                $process = proc_open([PHP_BINARY,__FILE__,'--worker',$name,$marker,$action], [1=>['pipe','w'],2=>['pipe','w']], $pipes);
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
    $race(['VERIFIED','REJECTED']);
    $batch=(new ScheduleCollection($db))->find(1);
    $schedule=(new AreaCollectionSchedule($db))->find(1);
    verify(in_array($batch['verification_status'],['VERIFIED','REJECTED'],true)
        && (int)$batch['verified_by_officer_user_id']===1 && $batch['verified_at']!==null,'Decision metadata missing');
    verify($schedule['schedule_status']===($batch['verification_status']==='VERIFIED'?'COMPLETED':'COLLECTION_SUBMITTED'),'Decision/schedule disagreement');
    verify($before===$db->query('SELECT * FROM collection_record_items ORDER BY record_item_id')->fetchAll(),'Concurrent review modified items');
    echo "PASS: simultaneous Verify/Reject produces exactly one decision, rejects stale review with conflict, and preserves source items without deadlock\n";

} finally {
    if ($db->inTransaction()) $db->rollBack();
    $db->exec("USE `$original`"); $db->exec("DROP DATABASE `$name`");
}
