<?php
declare(strict_types=1);
require_once __DIR__.'/support/bootstrap.php';
require_once __DIR__.'/support/OfficerManagementFixture.php';
if (($argv[1]??'')==='--worker') {
    $name=$argv[2]; if (!preg_match('/^ecolot_management_test_[a-f0-9]{12}$/D',$name)) throw new RuntimeException('Invalid test database');
    $config=require APP_ROOT.'/config/database.php';
    $db=new class(sprintf('mysql:host=%s;port=%s;dbname=%s',$config['host'],$config['port'],$name),$config['username'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]) extends PDO {
        public string $marker;
        public function prepare(string $sql,array $options=[]): PDOStatement|false {
            if (str_contains($sql,'FOR UPDATE') && (str_contains($sql,'FROM e_lots') || str_contains($sql,'FROM complaint_feedback'))) touch($this->marker);
            return parent::prepare($sql,$options);
        }
    };
    $db->marker=$argv[3]; $db->exec("SET time_zone='+05:30'");
    $task=json_decode($argv[4],true,512,JSON_THROW_ON_ERROR);
    try {
        if ($task['kind']==='feedback') (new OfficerFeedback($db))->review($task['id'],$task['officer'],'OPEN',$task['status'],'Concurrent response');
        else (new OfficerELot($db))->act($task['id'],1,$task['input']);
        echo 'OK';
    } catch (OutOfBoundsException $e) { echo 'DENIED'; }
    catch (DomainException $e) { if ($e->getCode()!==409) throw $e; echo 'CONFLICT'; }
    exit;
}
$db=Database::connection(); $original=$db->query('SELECT DATABASE()')->fetchColumn();
$name='ecolot_management_test_'.bin2hex(random_bytes(6)); $db->exec("CREATE DATABASE `$name`");
try {
    $db->exec("USE `$name`"); TestDatabase::loadSchema($db); $fixture=OfficerManagementFixture::seed($db);
    $race=static function(string $table,string $column,int $id,array $tasks) use($db,$name): void {
        $workers=[]; $markers=[]; $db->beginTransaction();
        $stmt=$db->prepare("SELECT `$column` FROM `$table` WHERE `$column`=? FOR UPDATE"); $stmt->execute([$id]);
        try {
            foreach ($tasks as $task) {
                $marker=sys_get_temp_dir().'/ecolot-management-race-'.bin2hex(random_bytes(8)); $markers[]=$marker;
                $process=proc_open([PHP_BINARY,__FILE__,'--worker',$name,$marker,json_encode($task,JSON_THROW_ON_ERROR)],[1=>['pipe','w'],2=>['pipe','w']],$pipes); $workers[]=[$process,$pipes];
            }
            $deadline=microtime(true)+10;
            do { clearstatcache(); $ready=count(array_filter($markers,'file_exists'))===2; if (!$ready) usleep(10000); } while (!$ready && microtime(true)<$deadline);
            verify($ready,'Workers did not reach lock barrier'); $db->commit(); $results=[];
            foreach ($workers as [$process,$pipes]) { $results[]=stream_get_contents($pipes[1]); $error=stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]); verify(proc_close($process)===0 && $error==='','Worker SQL error/deadlock: '.$error); }
            $workers=[]; sort($results); verify($results===['CONFLICT','OK'] || $results===['DENIED','OK'],'Contradictory writes: '.json_encode($results));
        } finally { if ($db->inTransaction()) $db->rollBack(); foreach ($workers as [$process,$pipes]) { proc_terminate($process); foreach ($pipes as $pipe) if (is_resource($pipe)) fclose($pipe); proc_close($process); } foreach ($markers as $marker) if (file_exists($marker)) unlink($marker); }
    };
    $now=new DateTimeImmutable('now',new DateTimeZone('Asia/Colombo'));
    $input=['action'=>'approve','note'=>'Concurrent review','open'=>$now->modify('+1 hour')->format('Y-m-d\TH:i:s'),'close'=>$now->modify('+2 hours')->format('Y-m-d\TH:i:s'),'bid_id'=>'','handover_status'=>'','date'=>'','expected_handover'=>''];
    $id=$fixture['primary']; $lots=new OfficerELot($db);
    $race('e_lots','e_lot_id',$id,[['kind'=>'lot','id'=>$id,'input'=>$input],['kind'=>'lot','id'=>$id,'input'=>array_replace($input,['action'=>'reject'])]]);
    if ($lots->details($id,1)['lot_status']==='REJECTED') { (new ELot($db))->submitCollectorLot($id,3); $lots->act($id,1,$input); }
    $db->prepare('UPDATE e_lots SET bidding_open_at=NOW()-INTERVAL 1 HOUR,bidding_close_at=NOW()+INTERVAL 1 HOUR WHERE e_lot_id=?')->execute([$id]);
    $bid1=(new RecyclerBidService($db))->placeBid(6,$id,'100.00'); $bid2=(new RecyclerBidService($db))->placeBid(7,$id,'200.00');
    $db->prepare('UPDATE e_lots SET bidding_close_at=NOW()-INTERVAL 1 SECOND WHERE e_lot_id=?')->execute([$id]);
    $race('e_lots','e_lot_id',$id,[['kind'=>'lot','id'=>$id,'input'=>array_replace($input,['action'=>'award','bid_id'=>(string)$bid1])],['kind'=>'lot','id'=>$id,'input'=>array_replace($input,['action'=>'award','bid_id'=>(string)$bid2])]]);
    verify((int)$db->query("SELECT COUNT(*) FROM recycler_bids WHERE bid_status='WINNING'")->fetchColumn()===1 && (int)$db->query('SELECT COUNT(*) FROM handover_records')->fetchColumn()===1,'Multiple winners/handovers');
    $handover=$lots->details($id,1)['handover'];
    $update=array_replace($input,['action'=>'handover','handover_status'=>'SCHEDULED','date'=>$now->modify('+1 day')->format('Y-m-d\TH:i:s'),'expected_handover'=>OfficerELot::handoverVersion($handover)]);
    $race('e_lots','e_lot_id',$id,[['kind'=>'lot','id'=>$id,'input'=>$update],['kind'=>'lot','id'=>$id,'input'=>array_replace($update,['date'=>$now->modify('+2 days')->format('Y-m-d\TH:i:s')])]]);
    $race('complaint_feedback','feedback_id',1,[['kind'=>'feedback','id'=>1,'officer'=>1,'status'=>'IN_REVIEW'],['kind'=>'feedback','id'=>1,'officer'=>1,'status'=>'RESOLVED']]);
    $race('complaint_feedback','feedback_id',2,[['kind'=>'feedback','id'=>2,'officer'=>1,'status'=>'IN_REVIEW'],['kind'=>'feedback','id'=>2,'officer'=>4,'status'=>'IN_REVIEW']]);
    verify(count((new OfficerFeedback($db))->details(1,1)['history'])===1,'Duplicate feedback history');
    echo "PASS: concurrent E-Lot Verify/Reject, winner selection, handover revisions, feedback decisions and general-feedback claims serialize without conflicting writes or deadlocks\n";
} finally { if ($db->inTransaction()) $db->rollBack(); $db->exec("USE `$original`"); $db->exec("DROP DATABASE `$name`"); }
