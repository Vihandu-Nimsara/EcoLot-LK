<?php
declare(strict_types=1);
require_once __DIR__.'/support/bootstrap.php';
require_once __DIR__.'/support/OfficerManagementFixture.php';
final class ManagementRedirect extends RuntimeException {}
final class ManagementController extends MunicipalOfficerController
{
    public array $data=[];
    public function view(string $view,array $data=[],?string $layout=null): void { $this->data=$data; }
    protected function redirect(string $path,int $status=303): never { throw new ManagementRedirect($path,$status); }
}
Session::start();
if (($argv[1]??'')==='--guard') {
    if (($argv[2]??'')!=='guest') Session::put('auth_user',['id'=>2,'role'=>'PUBLIC_USER']);
    register_shutdown_function(static function (): void { echo ' HTTP='.http_response_code(); });
    (new MunicipalOfficerController())->{($argv[3]??'eLots')}('1'); echo 'UNPROTECTED'; exit;
}
$db=Database::connection();
if (($argv[1]??'')==='--drop') {
    $name=$argv[2]??''; if (!preg_match('/^ecolot_browser_test_[a-f0-9]{12}$/D',$name)) throw new RuntimeException('Invalid disposable database');
    $db->exec("DROP DATABASE `$name`"); exit;
}
$original=$db->query('SELECT DATABASE()')->fetchColumn(); $name='ecolot_browser_test_'.bin2hex(random_bytes(6));
$db->exec("CREATE DATABASE `$name`"); $keep=false;
try {
    $db->exec("USE `$name`"); TestDatabase::loadSchema($db); $fixture=OfficerManagementFixture::seed($db);
    if (($argv[1]??'')==='--fixture') {
        $ready=$fixture['lots'][6]; $now=new DateTimeImmutable('now',new DateTimeZone('Asia/Colombo'));
        (new OfficerELot($db))->act($ready,1,['action'=>'approve','note'=>'Browser award fixture','open'=>$now->modify('+1 hour')->format('Y-m-d\TH:i:s'),'close'=>$now->modify('+2 hours')->format('Y-m-d\TH:i:s')]);
        $db->prepare('UPDATE e_lots SET bidding_open_at=NOW()-INTERVAL 1 HOUR,bidding_close_at=NOW()+INTERVAL 1 HOUR WHERE e_lot_id=?')->execute([$ready]);
        $bid=(new RecyclerBidService($db))->placeBid(6,$ready,'100.00');
        (new RecyclerBidService($db))->placeBid(7,$ready,'200.00');
        $db->prepare('UPDATE e_lots SET bidding_close_at=NOW()-INTERVAL 1 SECOND WHERE e_lot_id=?')->execute([$ready]);
        $keep=true; echo json_encode(['database'=>$name,'ready'=>$ready,'bid'=>$bid]+$fixture); exit;
    }
    $lots=new OfficerELot($db); $feedback=new OfficerFeedback($db);
    $filters=['q'=>'','status'=>'','category'=>'','type'=>'','sort'=>'newest','page'=>1];
    $listing=$lots->listing(1,$filters); verify(count($listing['rows'])===20 && $listing['total']===23,'Lot scope/pagination');
    verify(count($lots->listing(1,array_replace($filters,['page'=>2]))['rows'])===3,'Lot second page');
    verify($lots->details($fixture['foreign'],1)===null,'Foreign lot visible');
    $db->beginTransaction();
    $db->prepare('UPDATE e_lot_items SET e_lot_id=? WHERE e_lot_id=?')->execute([$fixture['primary'],$fixture['foreign']]);
    verify($lots->details($fixture['primary'],1)===null && $lots->details($fixture['primary'],4)===null,'Mixed-owner lot accessible');
    $db->rollBack();
    $details=$lots->details($fixture['primary'],1);
    verify(count($details['items'])===2 && (int)$details['total_quantity']===3 && $details['total_weight']==='3.625','Lot source totals/grouping');
    foreach (['%','_',"' OR 1=1 --"] as $q) {
        verify($lots->listing(1,array_replace($filters,['q'=>$q]))['total']===0,'Lot unsafe search');
        verify($feedback->listing(1,array_replace($filters,['q'=>$q]))['total']===0,'Feedback unsafe search');
    }
    verify(array_sum($lots->counts(1))===$listing['total'],'Lot count scope');
    verify($feedback->listing(1,$filters)['total']===25 && count($feedback->listing(1,$filters)['rows'])===20,'Feedback scope/pagination');
    verify($feedback->details(3,1)===null && $feedback->details(2,1)['area_name']==='Area One','Feedback scope/general area');
    $reject=static function(callable $operation): void { try { $operation(); } catch (DomainException|OutOfBoundsException $e) { return; } throw new RuntimeException('Invalid operation accepted'); };
    $input=['action'=>'approve','note'=>'Checked <note>','open'=>'','close'=>'','bid_id'=>'','handover_status'=>'','date'=>'','expected_handover'=>''];
    // Use explicit Sri Lanka timestamps, independently of host timezone.
    $now=new DateTimeImmutable('now',new DateTimeZone('Asia/Colombo'));
    $input['open']=$now->modify('+1 hour')->format('Y-m-d\TH:i:s'); $input['close']=$now->modify('+2 hours')->format('Y-m-d\TH:i:s');
    $id=$fixture['primary'];
    $reject(fn()=> $lots->act($fixture['foreign'],1,$input));
    $reject(fn()=> $lots->act($id,1,array_replace($input,['open'=>'2026-02-30T12:00'])));
    $reject(fn()=> $lots->act($id,1,array_replace($input,['action'=>'reject','note'=>"\u{00a0}"])));
    $db->exec("CREATE TRIGGER test_management_audit_fail BEFORE INSERT ON audit_logs FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Injected audit failure'");
    try { $lots->act($id,1,$input); throw new RuntimeException('Failure injection ignored'); } catch (PDOException $expected) {}
    $db->exec('DROP TRIGGER test_management_audit_fail');
    verify($lots->details($id,1)['lot_status']==='PENDING_VERIFICATION','Lot decision failed rollback');
    $lots->act($id,1,$input);
    verify($lots->details($id,1)['lot_status']==='OPEN_FOR_BIDDING' && count($lots->details($id,1)['history'])===1,'Lot approval/audit');
    $reject(fn()=> $lots->act($id,1,$input));
    $lots->act($fixture['second'],1,array_replace($input,['action'=>'reject','note'=>'Fix source description']));
    verify($lots->details($fixture['second'],1)['verification_note']==='Fix source description','Rejection reason');
    (new ELot($db))->submitCollectorLot($fixture['second'],3);
    verify($lots->details($fixture['second'],1)['lot_status']==='PENDING_VERIFICATION','Existing Collector rejected re-submission broken');
    // No waits: open/close controlled fixture windows around the database's current time.
    $db->prepare("UPDATE e_lots SET bidding_open_at=NOW()-INTERVAL 1 HOUR,bidding_close_at=NOW()+INTERVAL 1 HOUR WHERE e_lot_id=?")->execute([$id]);
    $bids=new RecyclerBidService($db); $bid1=$bids->placeBid(6,$id,'100.00'); $bid2=$bids->placeBid(7,$id,'200.00');
    $reject(fn()=> $lots->act($id,1,array_replace($input,['action'=>'award','bid_id'=>(string)$bid1])));
    $db->prepare('UPDATE e_lots SET bidding_close_at=NOW()-INTERVAL 1 SECOND WHERE e_lot_id=?')->execute([$id]);
    $db->exec("UPDATE users SET account_status='SUSPENDED' WHERE user_id=6");
    $reject(fn()=> $lots->act($id,1,array_replace($input,['action'=>'award','bid_id'=>(string)$bid1])));
    $db->exec("UPDATE users SET account_status='ACTIVE' WHERE user_id=6");
    $lots->act($fixture['second'],1,$input);
    $db->prepare('UPDATE e_lots SET bidding_open_at=NOW()-INTERVAL 1 HOUR,bidding_close_at=NOW()+INTERVAL 1 HOUR WHERE e_lot_id=?')->execute([$fixture['second']]);
    $wrongLotBid=$bids->placeBid(6,$fixture['second'],'150.00');
    $reject(fn()=> $lots->act($id,1,array_replace($input,['action'=>'award','bid_id'=>(string)$wrongLotBid])));
    $db->exec('UPDATE recycler_capabilities SET can_handle_high_risk=0 WHERE recycler_user_id=6');
    $db->prepare("UPDATE collection_record_items ci JOIN e_lot_items ei USING(record_item_id) SET ci.actual_risk_level='HIGH' WHERE ei.e_lot_id=?")->execute([$id]);
    $reject(fn()=> $lots->act($id,1,array_replace($input,['action'=>'award','bid_id'=>(string)$bid1])));
    $db->exec('UPDATE recycler_capabilities SET can_handle_high_risk=1 WHERE recycler_user_id=6');
    $db->prepare("UPDATE collection_record_items ci JOIN e_lot_items ei USING(record_item_id) SET ci.actual_risk_level='LOW' WHERE ei.e_lot_id=?")->execute([$id]);
    $before=$db->query('SELECT * FROM collection_record_items ORDER BY record_item_id')->fetchAll();
    $lots->act($id,1,array_replace($input,['action'=>'award','bid_id'=>(string)$bid1]));
    $awarded=$lots->details($id,1);
    verify($awarded['lot_status']==='AWARDED' && (int)$awarded['handover']['winning_bid_id']===$bid1,'Officer-selected winner not persisted');
    verify($db->query('SELECT bid_status FROM recycler_bids WHERE bid_id='.$bid2)->fetchColumn()==='REJECTED','Other submitted bids not resolved');
    $reject(fn()=> $lots->act($id,1,array_replace($input,['action'=>'award','bid_id'=>(string)$bid2])));
    $version=OfficerELot::handoverVersion($awarded['handover']);
    $handover=array_replace($input,['action'=>'handover','expected_handover'=>$version,'handover_status'=>'SCHEDULED','date'=>$now->modify('+1 day')->format('Y-m-d\TH:i:s'),'note'=>'Scheduled safely']);
    $lots->act($id,1,$handover); $reject(fn()=> $lots->act($id,1,$handover));
    $latest=$lots->details($id,1);
    $completed=array_replace($handover,['expected_handover'=>OfficerELot::handoverVersion($latest['handover']),'handover_status'=>'COMPLETED','date'=>(new DateTimeImmutable('now',new DateTimeZone('Asia/Colombo')))->format('Y-m-d\TH:i:s'),'note'=>'Delivered']);
    $lots->act($id,1,$completed); $reject(fn()=> $lots->act($id,1,$completed));
    verify($lots->details($id,1)['lot_status']==='COMPLETED','Handover trigger failed completion');
    verify($before===$db->query('SELECT * FROM collection_record_items ORDER BY record_item_id')->fetchAll(),'Officer actions changed inventory');
    foreach (['', ' ', "\u{00a0}",str_repeat('x',2001),"\xFF"] as $response) $reject(fn()=> $feedback->review(1,1,'OPEN','RESOLVED',$response));
    $db->exec("CREATE TRIGGER test_feedback_audit_fail BEFORE INSERT ON audit_logs FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Injected feedback audit failure'");
    try { $feedback->review(1,1,'OPEN','IN_REVIEW','Rollback response'); throw new RuntimeException('Feedback rollback injection ignored'); } catch (PDOException $expected) {}
    $db->exec('DROP TRIGGER test_feedback_audit_fail');
    verify($feedback->details(1,1)['feedback_status']==='OPEN' && $feedback->details(1,1)['officer_response']===null,'Feedback failure did not roll back');
    $feedback->review(1,1,'OPEN','IN_REVIEW','First response');
    $reject(fn()=> $feedback->review(1,1,'OPEN','CLOSED','Stale'));
    $feedback->review(1,1,'IN_REVIEW','RESOLVED','Second response');
    $feedback->review(1,1,'RESOLVED','CLOSED','Final response');
    $closed=$feedback->details(1,1);
    verify($closed['officer_response']==='Final response' && count($closed['history'])===3 && $closed['history'][1]['change']['before']['response']==='First response','Feedback audit/response preservation');
    $reject(fn()=> $feedback->review(1,1,'CLOSED','OPEN','Reopen'));
    $reject(fn()=> $feedback->review(3,1,'OPEN','RESOLVED','Foreign'));
    $feedback->review(2,4,'OPEN','IN_REVIEW','Claimed area feedback');
    verify($feedback->details(2,1)===null,'Claimed feedback leaked to another Officer');
    Session::put('auth_user',['id'=>1,'role'=>'MUNICIPAL_OFFICER']); $controller=new ManagementController(); $token=Csrf::token();
    foreach (['manageELot','reviewFeedback'] as $method) {
        $_POST=['_csrf_token'=>'bad']; http_response_code(200); ob_start(); $controller->$method('1'); ob_end_clean(); verify(http_response_code()===403,'CSRF accepted');
        $_POST=['_csrf_token'=>$token,'note'=>['bad']]; ob_start(); $controller->$method('1'); ob_end_clean(); verify(http_response_code()===422,'Array POST accepted');
    }
    foreach (['eLots','feedback'] as $method) {
        foreach ([['id'=>[]],['page'=>'0'],['status'=>'FAKE'],['q'=>"\xFF"],['sort'=>'DROP TABLE']] as $query) { $_GET=$query; ob_start(); $controller->$method(); ob_end_clean(); verify(http_response_code()===400,'Malformed filters accepted'); }
        $_GET=['id'=>(string)($method==='eLots'?$id:1)]; http_response_code(200); $controller->$method();
        ob_start(); (new Controller())->view('municipal_officer/'.($method==='eLots'?'elots':'feedback'),$controller->data); $html=ob_get_clean();
        verify(!str_contains($html,'window.confirm') && str_contains($html,'officer-management-confirm'),'Custom confirmation missing');
        if ($method==='feedback') verify(str_contains($html,'&lt;script&gt;') && !str_contains($html,'<script>alert'),'Feedback XSS');
    }
    foreach (['eLots','feedback','manageELot','reviewFeedback'] as $method) foreach (['guest','wrong'] as $role) {
        $out=shell_exec(escapeshellarg(PHP_BINARY).' -d session.save_path='.escapeshellarg(sys_get_temp_dir()).' '.escapeshellarg(__FILE__).' --guard '.$role.' '.$method);
        verify(!str_contains($out,'UNPROTECTED'),'Missing role guard'); if ($role==='wrong') verify(str_contains($out,'403'),'Wrong role allowed');
    }
    echo "PASS: Officer E-Lot scope/filters/counts/source totals/review/bids/manual award/handover/audit, feedback lifecycle/history/claiming, validation, rollback, CSRF/RBAC and escaping\n";
} finally { if (!$keep) { if ($db->inTransaction()) $db->rollBack(); $db->exec("USE `$original`"); $db->exec("DROP DATABASE `$name`"); } }
