<?php
declare(strict_types=1);
require_once __DIR__.'/support/bootstrap.php';
require_once __DIR__.'/support/CollectionReviewFixture.php';
final class ReviewTestRedirect extends RuntimeException {}
final class ReviewTestController extends MunicipalOfficerController
{
    public array $data=[];
    public function view(string $view,array $data=[],?string $layout=null): void { $this->data=$data; }
    protected function redirect(string $path,int $status=303): never { throw new ReviewTestRedirect($path,$status); }
}
Session::start();
if (($argv[1]??'') === '--guard') {
    if (($argv[2]??'')!=='guest') Session::put('auth_user',['id'=>2,'role'=>'PUBLIC_USER']);
    register_shutdown_function(static function (): void { echo ' HTTP='.http_response_code(); });
    (new MunicipalOfficerController())->{($argv[3]??'collectionRecords')}('1');
    echo 'UNPROTECTED'; exit;
}
$db=Database::connection();
if (($argv[1]??'')==='--drop') {
    $name=$argv[2]??'';
    if (!preg_match('/^ecolot_browser_test_[a-f0-9]{12}$/D',$name)) throw new RuntimeException('Invalid disposable database');
    $db->exec("DROP DATABASE `$name`"); exit;
}
$original=$db->query('SELECT DATABASE()')->fetchColumn();
$name='ecolot_browser_test_'.bin2hex(random_bytes(6));
$db->exec("CREATE DATABASE `$name`"); $keep=false;
try {
    $db->exec("USE `$name`"); TestDatabase::loadSchema($db); CollectionReviewFixture::seed($db);
    if (($argv[1]??'')==='--fixture') { $keep=true; echo json_encode(['database'=>$name]); exit; }
    Session::put('auth_user',['id'=>1,'role'=>'MUNICIPAL_OFFICER']);
    $model=new ScheduleCollection($db);
    $filters=['verification_status'=>'','q'=>'','sort'=>'newest','page'=>1];
    $queue=$model->reviewQueue(1,$filters);
    verify($queue['total']===24 && count($queue['rows'])===20 && $queue['pages']===2,'Pagination/total incorrect');
    $expected=$db->query("SELECT verification_status,COUNT(*) AS n FROM schedule_collections sc JOIN area_collection_schedules s USING(schedule_id) WHERE s.created_by_officer_user_id=1 AND verification_status<>'DRAFT' GROUP BY verification_status")->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach ($queue['counts'] as $status=>$count) verify($count===(int)($expected[$status]??0),'Counter scope incorrect');
    verify(!in_array(4,array_column($queue['rows'],'schedule_collection_id')) && !in_array(5,array_column($queue['rows'],'schedule_collection_id')),'Draft/foreign listing leak');
    $page2=$model->reviewQueue(1,array_replace($filters,['page'=>2]));
    verify(count($page2['rows'])===4 && !array_intersect(array_column($queue['rows'],'schedule_collection_id'),array_column($page2['rows'],'schedule_collection_id')),'Duplicate/omitted pagination');
    $oldest=$model->reviewQueue(1,array_replace($filters,['sort'=>'oldest']));
    verify($oldest['rows'][0]['schedule_collection_id']===1,'Oldest sort incorrect');
    foreach (['SCH-1'=>'positive',"%' OR 1=1 --"=>'empty','%'=>'empty','_'=>'empty'] as $q=>$kind) {
        $result=$model->reviewQueue(1,array_replace($filters,['q'=>$q]));
        verify($kind==='positive'?$result['total']>0:$result['total']===0,'Search wildcard/injection handling incorrect');
        verify($result['counts']===$queue['counts'],'Search changed global tab totals');
    }
    foreach (['VERIFIED'=>2,'REJECTED'=>3] as $status=>$id) {
        $result=$model->reviewQueue(1,array_replace($filters,['verification_status'=>$status]));
        verify(count($result['rows'])===1 && $result['rows'][0]['schedule_collection_id']===$id,'History filtering failed');
    }
    verify($model->findForOfficer(4,1)===null && $model->findForOfficer(5,1)===null,'Draft/foreign detail leak');
    foreach (['sch-2', '#2'] as $identifier) {
        $exact=$model->reviewQueue(1,array_replace($filters,['q'=>$identifier]));
        verify($exact['total']===1 && $exact['rows'][0]['schedule_collection_id']===2,'Complete identifier matched other submissions');
    }
    $fiveRequests=$model->reviewQueue(1,array_replace($filters,['q'=>'SCH-26']));
    verify(count($fiveRequests['rows'])===1 && (int)$fiveRequests['rows'][0]['record_count']===5,'Five-request fixture missing from review queue');
    verify(count($model->findForOfficer(26,1)['records'])===5,'Five-request fixture details incomplete');
    $details=$model->findForOfficer(1,1);
    verify(count($details['records'])===3 && count($details['records'][0]['items'])===2,'Multi-request/item grouping failed');
    verify($details['overview']['actual_quantity']===3 && $details['overview']['actual_weight_kg']==='3.625','Overview totals incorrect');
    // Alternative SQL aggregate independently checks PHP integer-thousandth arithmetic.
    $aggregate=$db->query('SELECT SUM(actual_quantity) AS qty,SUM(actual_weight_kg) AS weight FROM collection_record_items ci JOIN collection_records cr USING(collection_record_id) WHERE cr.schedule_collection_id=1')->fetch();
    verify((int)$aggregate['qty']===$details['overview']['actual_quantity'] && $aggregate['weight']===$details['overview']['actual_weight_kg'],'Independent SQL aggregate disagrees');
    foreach (['COLLECTED','PARTIAL','NOT_COLLECTED'] as $outcome) verify($details['overview'][$outcome]===1,'Pickup outcomes conflated');
    $controller=new ReviewTestController(); $token=Csrf::token();
    $get=static function (array $query=[]) use ($controller): array {
        $_GET=$query; http_response_code(200); $controller->data=[]; ob_start(); $controller->collectionRecords(); $output=ob_get_clean(); return [http_response_code(),$output];
    };
    $post=static function (int $id,array $input,array $query = []) use ($controller,$token): array {
        $_GET=$query; $_POST=$input+['_csrf_token'=>$token,'review_acknowledged'=>'1']; http_response_code(200); $controller->data=[]; ob_start();
        try { $controller->reviewCollection((string)$id); } catch (ReviewTestRedirect $redirect) { ob_end_clean(); return [$redirect->getCode(),$redirect->getMessage()]; }
        return [http_response_code(),ob_get_clean()];
    };
    foreach ([['submission_id'=>'0'],['submission_id'=>[]],['submission_id'=>'1e0'],['page'=>'0'],['page'=>[]],['q'=>[]],['q'=>str_repeat('a',101)],['sort'=>'id;DROP TABLE users'],['verification_status'=>'DRAFT']] as $query) verify($get($query)[0]===400,'Malformed GET accepted');
    verify($get(['submission_id'=>'999999'])[0]===404 && $get(['submission_id'=>'5'])[0]===404,'Missing/foreign GET accepted');
    verify($post(1,['decision'=>'VERIFIED','_csrf_token'=>'bad'])[0]===403,'CSRF accepted');
    foreach (['', '   ', "\u{00a0}", str_repeat('x',501), "\xFF"] as $note) verify($post(1,['decision'=>'REJECTED','note'=>$note])[0]===422,'Invalid note accepted');
    verify($post(1,['decision'=>['VERIFIED'],'note'=>'x'])[0]===422,'Array decision accepted');
    verify($post(5,['decision'=>'VERIFIED'])[0]===404,'Foreign decision accepted');
    verify($post(1,['decision'=>'VERIFIED','review_acknowledged'=>''])[0]===422,'Verification without acknowledgement accepted');
    $before=$db->query('SELECT * FROM collection_record_items ORDER BY record_item_id')->fetchAll();
    $beforeRecords=$db->query('SELECT * FROM collection_records ORDER BY collection_record_id')->fetchAll();
    $publicRequests = new EWasteRequest($db);
    $residentOutcome = static function (int $requestId) use ($publicRequests): string {
        foreach ($publicRequests->forPublicUser(2) as $request) {
            if ((int) $request['request_id'] === $requestId) {
                return PickupRequestPresenter::present($request)['status_label'];
            }
        }
        throw new RuntimeException('Resident pickup missing.');
    };
    $beforeSummary = $publicRequests->summaryForPublicUser(2);
    verify($residentOutcome(1)==='Awaiting pickup verification','Resident sees a final result before verification');
    verify($residentOutcome(5)==='Pickup unverified','A rejected collection looks like an approved pickup');
    $beforeRequests=$db->query('SELECT * FROM e_waste_requests ORDER BY request_id')->fetchAll();
    $db->exec("CREATE TRIGGER test_review_rollback BEFORE UPDATE ON area_collection_schedules FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Injected schedule update failure'");
    verify($post(1,['decision'=>'VERIFIED'])[0]===500,'Injected review failure ignored');
    $db->exec('DROP TRIGGER test_review_rollback');
    verify($model->find(1)['verification_status']==='PENDING' && $model->find(1)['verified_at']===null,'Review failure did not roll back');
    $response=$post(1,['decision'=>'VERIFIED','note'=>'  Checked <note>  ','verified_by_officer_user_id'=>4], ['verification_status'=>'PENDING','q'=>'Area One','page'=>'2']);
    verify($response[0]===303 && str_contains($response[1],'verification_status=PENDING') && str_contains($response[1],'q=Area+One') && str_contains($response[1],'page=2'),'PRG failed');
    $saved=$model->find(1);
    verify($saved['verification_status']==='VERIFIED' && (int)$saved['verified_by_officer_user_id']===1 && $saved['verified_at']!==null && $saved['verification_note']==='Checked <note>','Verification audit incorrect');
    verify($residentOutcome(1)==='Partially collected' && $residentOutcome(2)==='Completed'
        && $residentOutcome(3)==='Not collected','Resident pickup outcomes disagree with the verified submission');
    $afterSummary = $publicRequests->summaryForPublicUser(2);
    verify($afterSummary['completed_requests']===$beforeSummary['completed_requests']+1
        && $afterSummary['pending_requests']===$beforeSummary['pending_requests']-3
        && abs($afterSummary['collected_weight_kg']-$beforeSummary['collected_weight_kg']-3.625)<0.00001,
        'Resident totals should count verified pickups and measured weight');
    verify((new AreaCollectionSchedule($db))->find(1)['schedule_status']==='COMPLETED','Verification schedule transition failed');
    verify($post(1,['decision'=>'REJECTED','note'=>'Stale'])[0]===409,'Stale decision did not conflict');
    verify($post(3,['decision'=>'VERIFIED'])[0]===409,'Rejected submission verified directly');
    verify($post(6,['decision'=>'REJECTED','note'=>'  Correct pickup evidence  '])[0]===303,'Rejection failed');
    $rejected=$model->find(6);
    verify($rejected['verification_status']==='REJECTED' && $rejected['verification_note']==='Correct pickup evidence' && $rejected['verified_at']!==null && (int)$rejected['verified_by_officer_user_id']===1,'Rejection audit incorrect');
    $collector=(new CollectionRecord($db))->assignedRequest(8,3);
    verify($collector && $collector['verification_note']==='Correct pickup evidence' && !CollectionRecord::editable($collector),'Collector rejection visibility/read-only failed');
    verify($before===$db->query('SELECT * FROM collection_record_items ORDER BY record_item_id')->fetchAll(),'Review changed actual item data');
    verify($beforeRecords===$db->query('SELECT * FROM collection_records ORDER BY collection_record_id')->fetchAll(), 'Review changed pickup records');
    verify($beforeRequests===$db->query('SELECT * FROM e_waste_requests ORDER BY request_id')->fetchAll(), 'Review changed public requests');
    verify(count((new ELot($db))->getVerifiedPool(3))>0,'Verified inventory unavailable downstream');
    $pool=(new ELot($db))->getVerifiedPool(3);
    $poolParents=$db->query('SELECT DISTINCT cr.schedule_collection_id FROM collection_record_items ci JOIN collection_records cr USING(collection_record_id) WHERE ci.record_item_id IN ('.implode(',',array_map('intval',array_column($pool,'record_item_id'))).')')->fetchAll(PDO::FETCH_COLUMN);
    verify(!in_array(6,$poolParents) && !in_array(3,$poolParents) && !in_array(7,$poolParents),'Rejected/pending inventory leaked downstream');
    verify($post(9,['decision'=>'REJECTED','note'=>str_repeat('අ',500)])[0]===303,'Unicode length boundary rejected');
    verify(mb_strlen($model->find(9)['verification_note'])===500,'Unicode note truncated');
    $db->exec("UPDATE e_waste_requests SET request_status='COMPLETED' WHERE schedule_id=2");
    verify($model->findForOfficer(2,1)['integrity_error']===null,'Completed historical requests incorrectly flagged');
    // Remove a required actual row in an unreviewed test fixture; never fabricate a replacement.
    $db->exec('DELETE FROM collection_record_items WHERE collection_record_id=9');
    verify($model->findForOfficer(7,1)['integrity_error']!==null,'Missing actual data hidden');
    verify($post(7,['decision'=>'VERIFIED'])[0]===422,'Incomplete submission verified');
    $missingItemId=(int)$db->query('SELECT request_item_id FROM request_items WHERE request_id=9')->fetchColumn();
    $db->prepare('UPDATE collection_record_items SET request_item_id=? WHERE collection_record_id=10')->execute([$missingItemId]);
    $corrupt=$model->findForOfficer(8,1);
    verify($corrupt['integrity_error']!==null && count($corrupt['records'][0]['items'])===2,'Foreign actual item was hidden');
    verify($post(8,['decision'=>'VERIFIED'])[0]===422,'Foreign actual item verified');
    $db->exec('DELETE FROM request_items WHERE request_id=9 AND request_item_id NOT IN (SELECT request_item_id FROM collection_record_items)');
    $db->exec("UPDATE area_collection_schedules SET schedule_status='IN_PROGRESS' WHERE schedule_id=8");
    verify($post(8,['decision'=>'REJECTED','note'=>'State mismatch'])[0]===409,'Inconsistent schedule accepted');
    $db->exec('DELETE ci FROM collection_record_items ci JOIN collection_records cr USING(collection_record_id) WHERE cr.schedule_collection_id=10');
    $db->exec('DELETE ri FROM request_items ri JOIN collection_records cr USING(request_id) WHERE cr.schedule_collection_id=10');
    $noItems=$model->findForOfficer(10,1);
    verify(count($noItems['records'])===1 && $noItems['records'][0]['items']===[] && $noItems['integrity_error']!==null,'Itemless request omitted');
    verify($post(10,['decision'=>'VERIFIED'])[0]===422,'Itemless submission verified');
    $db->exec("UPDATE collection_record_items ci JOIN collection_records cr USING(collection_record_id) SET ci.actual_quantity=0,ci.actual_weight_kg=NULL,ci.actual_condition=NULL,ci.actual_risk_level=NULL,ci.item_result='NOT_COLLECTED' WHERE cr.schedule_collection_id=11");
    $db->exec("UPDATE collection_records SET pickup_result='NOT_COLLECTED' WHERE schedule_collection_id=11");
    verify($model->findForOfficer(11,1)['overview']['actual_weight_kg']===null,'Unknown weight fabricated as measured zero');
    verify($post(11,['decision'=>'VERIFIED'])[0]===303,'Legitimate no-collection submission rejected');
    $empty=$model->reviewQueue(999,$filters);
    verify($empty['total']===0 && array_sum($empty['counts'])===0 && $empty['rows']===[],'Empty scope counts fabricated');
    $get(['submission_id'=>'1','verification_status'=>'VERIFIED']);
    ob_start(); (new Controller())->view('municipal_officer/collection-records',$controller->data); $html=ob_get_clean();
    verify(str_contains($html,'Collection Reviews') && str_contains($html,'&lt;script&gt;') && !str_contains($html,'<script>alert'),'Terminology/escaping failed');
    verify(!str_contains($html,'data-review-open'),'Reviewed actions exposed');
    foreach (['collectionRecords','reviewCollection'] as $method) foreach (['guest','wrong'] as $role) {
        $command=escapeshellarg(PHP_BINARY).' -d session.save_path='.escapeshellarg(sys_get_temp_dir()).' '.escapeshellarg(__FILE__).' --guard '.$role.' '.$method;
        $output=shell_exec($command); verify(!str_contains($output,'UNPROTECTED'),'Role guard missing');
        if ($role==='wrong') verify(str_contains($output,'403'),'Wrong role not forbidden');
    }
    echo "PASS: scoped review queue/history/search/sort/pagination, multi-request/item totals, Verify/Reject audit, PRG, CSRF/RBAC, conflict, rollback, integrity, escaping and downstream inventory\n";
} finally { if (!$keep) { if ($db->inTransaction()) $db->rollBack(); $db->exec("USE `$original`"); $db->exec("DROP DATABASE `$name`"); } }
