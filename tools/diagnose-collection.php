<?php
declare(strict_types=1);
// Read-only: php tools/diagnose-collection.php 4
require_once dirname(__DIR__) . '/app/autoload.php';
if (PHP_SAPI !== 'cli' || !isset($argv[1]) || !preg_match('/^[1-9][0-9]*$/D', $argv[1])) {
    fwrite(STDERR, "Usage: php tools/diagnose-collection.php SCHEDULE_ID\n"); exit(1);
}
$db = Database::connection();
$db->exec('SET TRANSACTION READ ONLY');
$db->beginTransaction();
try {
    $read = static function (string $sql) use ($db, $argv): array {
        $stmt = $db->prepare($sql); $stmt->execute(['id' => $argv[1]]); return $stmt->fetchAll();
    };
    $result = [
        'schedule' => $read('SELECT schedule_id, schedule_status, created_by_officer_user_id FROM area_collection_schedules WHERE schedule_id=:id'),
        'assignment_history' => $read('SELECT * FROM schedule_assignments WHERE schedule_id=:id ORDER BY assignment_id'),
        'collection' => $read('SELECT sc.*, (SELECT COUNT(*) FROM collection_records cr WHERE cr.schedule_collection_id=sc.schedule_collection_id) AS saved_record_count FROM schedule_collections sc WHERE sc.schedule_id=:id'),
        'records' => $read('SELECT cr.*, r.schedule_id AS request_schedule_id FROM schedule_collections sc JOIN collection_records cr ON cr.schedule_collection_id=sc.schedule_collection_id JOIN e_waste_requests r ON r.request_id=cr.request_id WHERE sc.schedule_id=:id'),
        'downstream_lots' => $read('SELECT DISTINCT eli.e_lot_id FROM schedule_collections sc JOIN collection_records cr ON cr.schedule_collection_id=sc.schedule_collection_id JOIN collection_record_items cri ON cri.collection_record_id=cr.collection_record_id JOIN e_lot_items eli ON eli.record_item_id=cri.record_item_id WHERE sc.schedule_id=:id'),
        'parent_audit' => $read("SELECT al.* FROM audit_logs al JOIN schedule_collections sc ON al.entity_id=sc.schedule_collection_id AND al.entity_type='schedule_collections' WHERE sc.schedule_id=:id"),
        'foreign_keys' => $db->query("SELECT k.TABLE_NAME, k.COLUMN_NAME, k.REFERENCED_TABLE_NAME, r.DELETE_RULE FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r USING (CONSTRAINT_SCHEMA, CONSTRAINT_NAME, TABLE_NAME) WHERE k.CONSTRAINT_SCHEMA=DATABASE() AND k.REFERENCED_TABLE_NAME IN ('schedule_collections','collection_records','collection_record_items')")->fetchAll(),
    ];
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
} finally { $db->rollBack(); }
