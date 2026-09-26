<?php
declare(strict_types=1);
require_once __DIR__ . '/support/bootstrap.php';
final class CollectorTestRedirect extends RuntimeException {}
final class CollectorTestController extends CollectorController
{
    public array $data = [];
    public function view(string $view, array $data = [], ?string $layout = null): void { $this->data = $data; }
    protected function redirect(string $path, int $status = 303): never { throw new CollectorTestRedirect($path); }
}
Session::start();
if (($argv[1] ?? '') === '--guard') {
    if (($argv[2] ?? '') !== 'guest') Session::put('auth_user', ['id' => 2, 'role' => 'PUBLIC_USER']);
    register_shutdown_function(static function (): void { echo ' HTTP=' . http_response_code(); });
    (new CollectorController())->{$argv[3]}('1');
    echo 'UNPROTECTED'; exit;
}
$db = Database::connection();
if (($argv[1] ?? '') === '--drop') {
    $name = $argv[2] ?? '';
    if (!preg_match('/^ecolot_collector_test_[a-f0-9]{12}$/D', $name)) throw new RuntimeException('Invalid disposable database');
    $db->exec("DROP DATABASE `$name`");
    exit;
}
$original = $db->query('SELECT DATABASE()')->fetchColumn();
$name = 'ecolot_collector_test_' . bin2hex(random_bytes(6));
$db->exec("CREATE DATABASE `$name`");
$keep = false;
try {
    $db->exec("USE `$name`");
    TestDatabase::loadSchema($db);
    $month = TestDatabase::seedScheduleActors($db);
    $db->exec("INSERT INTO users (user_id,full_name,mobile_number,password_hash,role) VALUES (4,'Other Collector','0771234570','unused','COLLECTOR')");
    $db->exec('INSERT INTO collectors VALUES (4)');
    $db->exec("UPDATE users SET account_status='ACTIVE', mobile_verified_at=NOW(), mobile_number=CONCAT('94', SUBSTRING(mobile_number,2))");
    $db->prepare('UPDATE users SET password_hash=?')->execute([password_hash('BrowserTest123!', PASSWORD_DEFAULT)]);
    $db->exec("INSERT INTO waste_categories (category_id,category_name) VALUES (1,'Test category')");
    $db->exec("INSERT INTO e_waste_items (waste_item_id,category_id,item_name) VALUES (1,1,'Test device')");
    $db->exec("INSERT INTO risk_rules (waste_item_id,condition_type,risk_level,action_note) VALUES (1,'DAMAGED','HIGH','Test rule')");
    $schedules = new AreaCollectionSchedule($db);
    for ($i = 1; $i <= 3; $i++) {
        $sid = $schedules->create(['campaign_id'=>1, 'postal_area_id'=>1, 'created_by_officer_user_id'=>1,
            'collection_date'=>"$month-" . ($i === 1 ? '10' : ($i === 2 ? '20' : '25')), 'request_cutoff_at'=>"$month-08 23:59:59", 'request_capacity'=>10, 'schedule_status'=>'OPEN']);
        for ($j = 1; $j <= 2; $j++) {
            $rid = (new EWasteRequest($db))->create(['public_user_id'=>2,'schedule_id'=>$sid,'pickup_address'=>'<script>address</script>','request_status'=>'APPROVED']);
            $db->prepare("INSERT INTO request_items (request_id,waste_item_id,quantity,item_condition,applied_risk_level) VALUES (?,1,2,'UNKNOWN','LOW')")->execute([$rid]);
        }
        $schedules->update($sid, ['schedule_status'=>'ASSIGNED']);
        $db->prepare('INSERT INTO schedule_assignments (schedule_id,collector_user_id,assigned_by_officer_user_id) VALUES (?,?,1)')->execute([$sid, $i === 2 ? 4 : 3]);
    }
    if (($argv[1] ?? '') === '--fixture') {
        $keep = true; echo json_encode(['database'=>$name]); exit;
    }
    Session::put('auth_user', ['id'=>3, 'name'=>'Collector', 'role'=>'COLLECTOR']);
    $controller = new CollectorTestController();
    $model = new CollectionRecord();
    $token = Csrf::token();
    $invoke = static function (string $method, array $input = [], ?string $id = null) use ($controller, $token): string {
        $_POST = $input + ['_csrf_token'=>$token];
        $controller->data = []; http_response_code(200);
        try { $id === null ? $controller->$method() : $controller->$method($id); }
        catch (CollectorTestRedirect $redirect) { return $redirect->getMessage(); }
        return '';
    };
    $valid = ['request_id'=>'1', 'pickup_result'=>'COLLECTED','collector_note'=>'<b>actual note</b>',
        'items'=>[1=>['actual_quantity'=>'2','actual_weight_kg'=>'1.250','actual_condition'=>'DAMAGED','notes'=>'Collected']]];
    foreach ([[], $model->assignedSchedules(4)] as $dashboardSchedules) {
        ob_start(); (new Controller())->view('collector/dashboard', ['currentPage'=>'dashboard', 'schedules'=>$dashboardSchedules]); $dashboardHtml=ob_get_clean();
        verify(str_contains($dashboardHtml, 'Next Assignment'), 'Zero/one dashboard failed');
    }
    $controller->dashboard();
    verify(count($controller->data['schedules']) === 2, 'Dashboard multiple schedules');
    $controller->schedules();
    verify(count($controller->data['schedules']) === 2, 'Multiple schedules missing');
    verify($invoke('myRequests') === '/collector/schedules', 'Legacy redirect');
    $controller->showSchedule('3');
    verify(array_column($controller->data['requests'], 'request_id') === [5,6], 'Workspace isolation');
    ob_start(); $controller->showSchedule('2'); ob_end_clean();
    verify(http_response_code() === 404, 'Foreign workspace access');
    $controller->showSchedule('1');
    verify(!$controller->data['canSubmit'], 'Incomplete submission enabled');
    verify(count($controller->data['requests']) === 2, 'Own list only');
    verify($model->assignedRequest(3, 3) === null, 'Foreign request visible');
    foreach (['3','0','-1','1e0','999999999999999999999999'] as $id) {
        ob_start(); $controller->showRequest($id); ob_end_clean(); verify(http_response_code() === 404, 'Bad/foreign request read allowed');
    }
    foreach (['storeRecord','updateRecord','deleteRecord','submitSchedule'] as $method) {
        ob_start(); $invoke($method, ['_csrf_token'=>['bad']], $method === 'storeRecord' ? null : '1'); ob_end_clean();
        verify(http_response_code() === 403, 'CSRF not rejected: '.$method);
    }
    $invalid = [
        ['request_id'=>'3'], ['request_id'=>'0'], ['collector_note'=>['bad']],
        ['collector_note'=>str_repeat('x',501)], ['items'=>[]], ['items'=>'bad'],
        ['items'=>[3=>$valid['items'][1]]],
    ];
    foreach ([['actual_quantity'=>'3'],['actual_quantity'=>'-1'],['actual_quantity'=>'1.5'],['actual_quantity'=>['2']],['actual_weight_kg'=>'-1'],['actual_weight_kg'=>'1e2'],['actual_weight_kg'=>'10000000'],['actual_weight_kg'=>'0.0001'],['actual_weight_kg'=>'0'],['actual_condition'=>'USED'],['notes'=>['bad']]] as $change) {
        $invalid[] = ['items'=>[1=>array_replace($valid['items'][1],$change)]];
    }
    foreach ($invalid as $change) {
        $invoke('storeRecord', array_replace($valid,$change));
        verify($model->all() === [], 'Invalid create persisted: '.json_encode($change));
        verify(Session::pullFlash('collection_error') !== null, 'Missing validation feedback');
    }
    verify((int)$db->query('SELECT COUNT(*) FROM schedule_collections')->fetchColumn() === 0, 'Failed create left parent');
    $invoke('storeRecord',array_replace($valid,['pickup_result'=>'NOT_COLLECTED'])+['collector_user_id'=>4,'verification_status'=>'VERIFIED']);
    verify($schedules->find(1)['schedule_status'] === 'IN_PROGRESS', 'Work did not start schedule');
    verify($model->all()[0]['pickup_result'] === 'COLLECTED', 'Browser result was trusted');
    $recordId = (int)$model->all()[0]['collection_record_id'];
    $record = $model->ownedRecord($recordId,3);
    verify(CollectionRecord::status($record)==='DRAFT' && $record['collection_submitted_at']===null, 'New record not draft');
    verify($model->ownedRecord($recordId,4)===null, 'Foreign record visible');
    verify($db->query('SELECT actual_risk_level FROM collection_record_items')->fetchColumn()==='HIGH','Actual condition risk rule ignored');
    $controller->showRecord((string)$recordId); verify($controller->data['editable']===true,'Draft not editable');
    ob_start(); (new Controller())->view('collector/collection-record',$controller->data); $html=ob_get_clean();
    verify(str_contains($html,'&lt;script&gt;address&lt;/script&gt;') && !str_contains($html,'<script>address</script>'),'View escaping');
    $invoke('storeRecord',$valid); verify(count($model->all())===1,'Duplicate record created'); Session::pullFlash('collection_error');
    Session::put('auth_user',['id'=>4,'role'=>'COLLECTOR']);
    $invoke('updateRecord',$valid,(string)$recordId); verify(Session::pullFlash('collection_error')!==null,'Foreign update accepted');
    $invoke('deleteRecord',[],(string)$recordId); verify($model->find($recordId)!==null,'Foreign delete accepted');
    $invoke('submitSchedule',[],'1'); verify(Session::pullFlash('collection_error')!==null,'Foreign submission accepted');
    Session::put('auth_user',['id'=>3,'role'=>'COLLECTOR']);
    $partial = array_replace($valid,['pickup_result'=>'PARTIAL','request_id'=>'3','items'=>[1=>array_replace($valid['items'][1],['actual_quantity'=>'1'])]]);
    $invoke('updateRecord',$partial,(string)$recordId);
    verify($model->find($recordId)['pickup_result']==='PARTIAL','Draft update failed');
    verify((int)$model->find($recordId)['request_id']===1,'Forged request ID changed parent');
    $invoke('submitSchedule',[],'1'); verify(Session::pullFlash('collection_error')!==null,'Incomplete schedule submitted');
    $invoke('deleteRecord',[],(string)$recordId);
    verify($model->find($recordId)===null && (int)$db->query('SELECT COUNT(*) FROM collection_record_items')->fetchColumn()===0,'Draft delete did not cascade');
    $invoke('storeRecord',$valid);
    $recordId = (int)$model->all()[0]['collection_record_id'];
    $second = ['request_id'=>'2','pickup_result'=>'NOT_COLLECTED','items'=>[2=>['actual_quantity'=>'0','actual_weight_kg'=>'','actual_condition'=>'DAMAGED','notes'=>'Nobody home']]];
    $invoke('storeRecord',$second);
    verify($model->assignedRequest(2,3)['items'][0]['actual_condition'] === null, 'Uncollected condition was not normalized');
    verify(count($model->all())===2 && (int)$db->query('SELECT COUNT(*) FROM schedule_collections')->fetchColumn()===1,'One parent per schedule violated');
    $controller->showSchedule('1'); verify($controller->data['canSubmit'], 'Complete schedule not ready');
    $db->exec("UPDATE e_waste_requests SET risk_review_status='PENDING' WHERE request_id=2");
    $invoke('submitSchedule',[],'1'); verify(Session::pullFlash('collection_error')!==null, 'Pending review submitted');
    $db->exec("UPDATE e_waste_requests SET risk_review_status='NOT_REQUIRED' WHERE request_id=2");
    $before = $db->query('SELECT * FROM e_waste_requests ORDER BY request_id')->fetchAll();
    $invoke('submitSchedule',[],'1');
    verify(Session::pullFlash('collection_error')===null,'Valid submission failed');
    $record=$model->ownedRecord($recordId,3);
    verify(CollectionRecord::status($record)==='SUBMITTED' && $record['collection_submitted_at']!==null,'Submission did not persist');
    verify($schedules->find(1)['schedule_status']==='COLLECTION_SUBMITTED','Schedule state not advanced');
    $controller->showRecord((string)$recordId); verify(!$controller->data['editable'],'Submitted view editable');
    ob_start(); (new Controller())->view('collector/collection-record',$controller->data); $html=ob_get_clean();
    verify(!str_contains($html,'Save Changes') && !str_contains($html,'>Delete Draft</button>') && !str_contains($html,'>Submit Schedule'),'Submitted mutation buttons visible');
    $snapshot=$db->query('SELECT * FROM collection_record_items ORDER BY record_item_id')->fetchAll();
    foreach (['updateRecord','deleteRecord'] as $method) {
        $invoke($method,$partial,(string)$recordId); verify(Session::pullFlash('collection_error')!==null,'Submitted mutation accepted');
    }
    $invoke('submitSchedule',[],'1'); verify(Session::pullFlash('collection_error')!==null,'Duplicate submission accepted');
    verify($model->find($recordId)!==null && $snapshot===$db->query('SELECT * FROM collection_record_items ORDER BY record_item_id')->fetchAll(),'Read-only data changed');
    verify($before===$db->query('SELECT * FROM e_waste_requests ORDER BY request_id')->fetchAll(),'Original request modified');
    foreach (['VERIFIED', 'REJECTED'] as $state) {
        $db->prepare('UPDATE schedule_collections SET verification_status=?, verified_by_officer_user_id=1, verified_at=NOW(), verification_note=? WHERE schedule_id=1')->execute([$state, 'Existing officer outcome']);
        $controller->showRecord((string)$recordId);
        verify(!$controller->data['editable'] && $controller->data['request']['verification_status']===$state, 'Officer outcome not preserved as read-only');
        $invoke('updateRecord',$partial,(string)$recordId);
        verify(Session::pullFlash('collection_error')!==null, 'Officer outcome editable by collector');
    }
    $db->exec("UPDATE schedule_assignments SET unassigned_at=DATE_ADD(assigned_at,INTERVAL 1 SECOND) WHERE schedule_id=1");
    verify($model->assignedRequests(3,1)===[] && $model->ownedRecord($recordId,3)===null,'Revoked assignment retained access');
    // Migration compatibility: restore the original parent definition, apply migration, preserve submitted data.
    $db->exec("ALTER TABLE schedule_collections DROP CONSTRAINT chk_schedule_collections_verification_audit,
        MODIFY verification_status ENUM('PENDING','VERIFIED','REJECTED') NOT NULL DEFAULT 'PENDING',
        MODIFY submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        ADD CONSTRAINT chk_schedule_collections_verification_audit CHECK (
        (verification_status='PENDING' AND verified_by_officer_user_id IS NULL AND verified_at IS NULL) OR
        (verification_status IN ('VERIFIED','REJECTED') AND verified_by_officer_user_id IS NOT NULL AND verified_at IS NOT NULL))");
    $batchBefore=$db->query('SELECT * FROM schedule_collections')->fetchAll();
    $db->exec(file_get_contents(APP_ROOT.'/database/migrations/20260925_collector_collection_record_crud.sql'));
    verify($batchBefore===$db->query('SELECT * FROM schedule_collections')->fetchAll(),'Migration changed existing submission');
    foreach (['dashboard','schedules','showSchedule','myRequests','showRequest','showRecord','storeRecord','updateRecord','deleteRecord','submitSchedule'] as $method) foreach (['guest','wrong'] as $role) {
        $command=escapeshellarg(PHP_BINARY).' -d session.save_path='.escapeshellarg(sys_get_temp_dir()).' '.escapeshellarg(__FILE__).' --guard '.escapeshellarg($role).' '.escapeshellarg($method);
        $output=shell_exec($command); verify(!str_contains($output,'UNPROTECTED'),'Role guard missing');
        if ($role==='wrong') verify(str_contains($output,'403'),'Wrong role not forbidden');
    }
    echo "PASS: collector ownership, CRUD, item validation, CSRF, schedule submission, read-only guards, escaping, role guards and migration preservation\n";
} finally {
    if (!$keep) { $db->exec("USE `$original`"); $db->exec("DROP DATABASE `$name`"); }
}
