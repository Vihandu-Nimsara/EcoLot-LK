<?php
declare(strict_types=1);
// Explicit opt-in. All data is synthetic in randomly named disposable databases; no SMS.
if(getenv('ECOLOT_CAPABILITY_CHANGE_TEST')!=='1'){http_response_code(404);exit(1);}
require __DIR__.'/support/bootstrap.php';Session::start();$config=require APP_ROOT.'/config/database.php';
if(PHP_SAPI==='cli-server'){
 if(!preg_match('/^ecolot_test_capability_[a-f0-9]{12}$/D',$config['database']))exit(1);
 $path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
 if(str_starts_with($path,'/assets/') && is_file(APP_ROOT.'/public'.$path))return false;
 $_SERVER['SCRIPT_NAME']='/index.php';require APP_ROOT.'/public/index.php';exit;
}
$db=new PDO(sprintf('mysql:host=%s;port=%s;charset=utf8mb4',$config['host'],$config['port']),$config['username'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);$db->exec("SET time_zone='+05:30'");
if(($argv[1]??'')==='--worker'){
 if(!preg_match('/^ecolot_test_capability_[a-f0-9]{12}$/D',$config['database']))exit(1);
 $db->exec('USE `'.$config['database'].'`');$owner=(int)$argv[3];$admin=(int)$argv[4];$target=(int)$argv[5];
 Session::put('auth_user',['id'=>$argv[2]==='review'?$admin:$owner,'role'=>$argv[2]==='review'?'ADMIN':'RECYCLER']);echo "READY\n";flush();
 try{if($argv[2]==='review'){(new RecyclerCapabilityChangeService($db))->review($owner,$admin,$target,'APPROVE','Concurrent evidence review');echo "APPROVED\n";}else{(new RecyclerBidService($db))->placeBid($owner,$target,'1500');echo "BID\n";}}
 catch(DomainException|RuntimeException $error){echo "BLOCKED\n";}exit;
}
require APP_ROOT.'/tests/support/RecyclerProfileFixture.php';$checks=0;$keep=null;$tempFiles=[];
$check=static function(bool $ok,string $why)use(&$checks):void{if(!$ok)throw new RuntimeException($why);$checks++;};
$deny=static function(callable $fn,string $why)use($check):void{try{$fn();}catch(DomainException|InvalidArgumentException|RuntimeException $error){$check(true,$why);return;}throw new RuntimeException('Expected denial: '.$why);};
$q=static function(string $sql,array $args=[])use($db):PDOStatement{$s=$db->prepare($sql);$s->execute($args);return $s;};
$login=static function(int $id,string $role='RECYCLER'):void{Session::put('auth_user',['id'=>$id,'role'=>$role,'name'=>'Synthetic capability account']);};
try{
 foreach(['fresh','upgraded'] as $kind){
  $name='ecolot_test_capability_'.bin2hex(random_bytes(6));$db->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
  try{
   $db->exec("USE `$name`");
   if($kind==='fresh')TestDatabase::loadSchema($db);
   else{
    $legacy=getenv('ECOLOT_LEGACY_SCHEMA');if(!$legacy || !is_file($legacy))throw new RuntimeException('Metadata-only legacy schema required.');
    $env=getenv();$env['MYSQL_PWD']=$config['password'];$p=proc_open(['/opt/lampp/bin/mysql','--no-defaults','--host='.$config['host'],'--port='.$config['port'],'--user='.$config['username'],$name],[0=>['file',$legacy,'r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,$env);stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);if(proc_close($p)!==0)throw new RuntimeException($err);
    foreach(['20261116_recycler_initial_scope.sql','20261119_recycler_authorization_foundation.sql','20261119_recycler_license_renewal.sql'] as $file)TestDatabase::applyMigration($db,APP_ROOT.'/database/migrations/'.$file);
   }
   $legacyBefore=null;$capsBefore=null;$f=null;
   if($kind==='upgraded'){
    $db->beginTransaction();$f=recyclerProfileFixture($db,'CapabilityTest123!');$db->commit();
    $q("INSERT INTO recycler_capability_change_requests(recycler_user_id,category_id,request_type,previous_capability_status,previous_can_handle_high_risk,requested_can_handle_high_risk,request_reason) VALUES(?,?,'UPDATE','APPROVED',1,0,'Existing request before additive migration')",[$f['users']['recycler'],$f['category']]);$preMigrationRequest=(int)$db->lastInsertId();
    $legacyBefore=$q('SELECT * FROM recycler_capability_change_requests WHERE request_id=?',[$preMigrationRequest])->fetch();$capsBefore=$q('SELECT * FROM recycler_capabilities ORDER BY recycler_user_id,category_id')->fetchAll();
   }
   $migration=APP_ROOT.'/database/migrations/20261119_recycler_capability_changes.sql';TestDatabase::applyMigration($db,$migration);TestDatabase::applyMigration($db,$migration);
   if($legacyBefore!==null){
    $after=$q('SELECT * FROM recycler_capability_change_requests WHERE request_id=?',[$preMigrationRequest])->fetch();
    foreach($legacyBefore as $field=>$value)$check($after[$field]===$value,'Initial upgrade preserves legacy request field '.$field);
    $check($after['capability_snapshot_json']===null && $after['license_identity_sha256']===null && $after['approved_activity_snapshot_json']===null,'Initial upgrade does not fabricate trusted snapshots');
    $check($capsBefore===$q('SELECT * FROM recycler_capabilities ORDER BY recycler_user_id,category_id')->fetchAll(),'Initial upgrade preserves populated current capabilities');
   }
   if($f===null){$db->beginTransaction();$f=recyclerProfileFixture($db,'CapabilityTest123!');$db->commit();}$owner=(int)$f['users']['recycler'];$other=(int)$f['users']['competitor'];$admin=(int)$f['users']['admin'];$category=(int)$f['category'];$lot=(int)$f['lot'];
   $s=new RecyclerCapabilityChangeService($db);$scope=new RecyclerLicenseReviewService($db);$model=new AuthorizedRecycler($db);$bids=new RecyclerBidService($db);$lots=new ELot($db);
   $check($s->available(),'Fresh/upgraded request storage ready');
   if($legacyBefore!==null){$login($admin,'ADMIN');$deny(fn()=>$s->review($owner,$admin,$preMigrationRequest,'APPROVE','Old request before migration'),'Pre-migration request cannot be auto-approved');$s->review($owner,$admin,$preMigrationRequest,'REJECT','Submit a new request with current verified evidence');}
   // Arrange an absent target category plus a preserved pre-existing reviewed capability.
   $q('DELETE FROM recycler_capabilities WHERE recycler_user_id=? AND category_id=?',[$owner,$category]);
   $q("UPDATE recycler_license_scopes SET scope_status='REJECTED',review_note='Synthetic rejected prior evidence' WHERE license_id=(SELECT current_license_id FROM authorized_recyclers WHERE user_id=?)",[$owner]);
   $q("INSERT INTO waste_categories(category_name) VALUES(?)",['Initial retained '.$name]);$spare=(int)$db->lastInsertId();
   $q("INSERT INTO recycler_capabilities(recycler_user_id,category_id,capability_status,can_handle_high_risk,reviewed_by_admin_user_id,reviewed_at,review_note) VALUES(?,?,'APPROVED',0,?,CURRENT_TIMESTAMP,'Original approval retained')",[$owner,$spare,$admin]);
   $original=$q('SELECT * FROM recycler_capabilities WHERE recycler_user_id=? AND category_id=?',[$owner,$spare])->fetch();
   $getCap=static fn()=> $q('SELECT * FROM recycler_capabilities WHERE recycler_user_id=? AND category_id=?',[$owner,$category])->fetch();
   $input=['category_id'=>(string)$category,'request_type'=>'ADD','requested_can_handle_high_risk'=>'1','request_reason'=>'New category processing capability'];$login($owner);
   foreach([['category_id'=>'999999'],['request_type'=>'INVALID'],['request_reason'=>''],['request_reason'=>'.....'],['requested_can_handle_high_risk'=>'2']] as $bad)$deny(fn()=>$s->submit($owner,array_replace($input,$bad)),'Invalid request rejected');
   $deny(fn()=>$s->submit($other,$input),'Foreign owner denied');$login($owner,'ADMIN');$deny(fn()=>$s->submit($owner,$input),'Wrong role denied');$login($owner);
   foreach(['SUSPENDED','DISABLED'] as $status){$q('UPDATE users SET account_status=? WHERE user_id=?',[$status,$owner]);$deny(fn()=>$s->submit($owner,$input),'Inactive account denied');}$q("UPDATE users SET account_status='ACTIVE' WHERE user_id=?",[$owner]);
   $q('UPDATE users SET mobile_verified_at=NULL WHERE user_id=?',[$owner]);$deny(fn()=>$s->submit($owner,$input),'Unverified mobile denied');$q('UPDATE users SET mobile_verified_at=CURRENT_TIMESTAMP WHERE user_id=?',[$owner]);
   $q("UPDATE authorized_recyclers SET verification_status='SUSPENDED',review_note='Synthetic suspension' WHERE user_id=?",[$owner]);$deny(fn()=>$s->submit($owner,$input),'Non-VERIFIED registration denied');$q("UPDATE authorized_recyclers SET verification_status='VERIFIED',review_note=NULL WHERE user_id=?",[$owner]);
   $request=$s->submit($owner,$input);$check($getCap()===false,'ADD pending does not create a live capability');$check(!$lots->isEligible($lot,$owner),'Pending ADD grants no E-Lot authorization');
   $check($original===$q('SELECT * FROM recycler_capabilities WHERE recycler_user_id=? AND category_id=?',[$owner,$spare])->fetch(),'Other initial reviewed capability unchanged');
   $deny(fn()=>$s->submit($owner,$input),'Duplicate pending protected');
   $login($admin,'ADMIN');$deny(fn()=>$s->review($other,$admin,$request,'APPROVE','Wrong owner review'),'Foreign request review denied');
   $deny(fn()=>$s->review($owner,$admin,$request,'APPROVE','Scope is missing'),'Missing/rejected scope denied');$check($getCap()===false,'Failed approval rolls back prospective capability');
   $doc=$model->selectedLicenseForRecycler($owner);
   $proposal=static function(int $risk)use($doc,$category):array{return ['license_id'=>(string)$doc['license_id'],'document_sha256'=>hash_file('sha256',APP_ROOT.'/storage/'.$doc['document_path']),'category_id'=>(string)$category,'activity_type'=>'RECYCLING','covers_high_risk'=>(string)$risk,'authorization_basis'=>'SWML','waste_description'=>'Synthetic devices','evidence_reference'=>'Page 2','licence_conditions'=>'Recorded conditions','waste_code'=>''];};
   $deny(fn()=>$scope->proposeScope($owner,$admin,$proposal(1)),'Initial scope review remains closed for VERIFIED owner');
   $scope->proposeScope($owner,$admin,$proposal(0),false,$request);$scopeId=(int)$q('SELECT MAX(scope_id) FROM recycler_license_scopes')->fetchColumn();
   $check($getCap()===false,'Scope proposal does not materialize a capability');
   $deny(fn()=>$scope->reviewScope($owner,$scopeId,$admin,'VERIFIED','Inspect document',false,false,$request),'Independent inspection required');
   $scope->reviewScope($owner,$scopeId,$admin,'VERIFIED','Inspected low-risk coverage',true,false,$request);
   $deny(fn()=>$s->review($owner,$admin,$request,'APPROVE','Low-risk scope only'),'Explicit high-risk scope required');$check($getCap()===false,'High-risk rejection leaves ADD absent');
   $scope->proposeScope($owner,$admin,$proposal(1),false,$request);$scopeId=(int)$q('SELECT MAX(scope_id) FROM recycler_license_scopes')->fetchColumn();$scope->reviewScope($owner,$scopeId,$admin,'VERIFIED','Inspected high-risk permit',true,false,$request);
   $currentPath=APP_ROOT.'/storage/'.$doc['document_path'];$content=file_get_contents($currentPath);
   rename($currentPath,$currentPath.'.missing');try{$deny(fn()=>$s->review($owner,$admin,$request,'APPROVE','Missing current evidence'),'Missing current PDF denied');}finally{rename($currentPath.'.missing',$currentPath);}
   file_put_contents($currentPath,"%PDF-1.4\nChanged synthetic bytes\n%%EOF\n");$deny(fn()=>$s->review($owner,$admin,$request,'APPROVE','Changed current bytes'),'Changed current document denied');file_put_contents($currentPath,$content);
   $q("UPDATE users SET account_status='DISABLED' WHERE user_id=?",[$admin]);$deny(fn()=>$s->review($owner,$admin,$request,'APPROVE','Disabled Admin'),'Disabled Administrator denied');$q("UPDATE users SET account_status='ACTIVE' WHERE user_id=?",[$admin]);
   $q("UPDATE waste_categories SET category_status='INACTIVE' WHERE category_id=?",[$category]);$deny(fn()=>$s->review($owner,$admin,$request,'APPROVE','Inactive category'),'Live inactive category denied');$q("UPDATE waste_categories SET category_status='ACTIVE' WHERE category_id=?",[$category]);
   $activity=$q("SELECT * FROM recycler_authorized_activities WHERE recycler_user_id=? AND activity_type='RECYCLING'",[$owner])->fetch();
   $q("UPDATE recycler_authorized_activities SET activity_status='REJECTED',review_note='Synthetic activity revocation' WHERE activity_id=?",[$activity['activity_id']]);$deny(fn()=>$s->review($owner,$admin,$request,'APPROVE','Changed activity'),'Changed/missing approved activity denied');
   $q("UPDATE recycler_authorized_activities SET activity_status='APPROVED',review_note=? WHERE activity_id=?",[$activity['review_note'],$activity['activity_id']]);
   $check($getCap()===false,'All failed approval paths retain absent ADD capability');
   $s->review($owner,$admin,$request,'APPROVE','New category and high-risk scope inspected');$check($getCap()['capability_status']==='APPROVED' && (int)$getCap()['can_handle_high_risk']===1,'ADD applied only after approval');$check($lots->isEligible($lot,$owner),'Canonical E-Lot authorization recognizes approved ADD');
   $deny(fn()=>$s->review($owner,$admin,$request,'APPROVE','Repeated decision'),'Repeated decision denied');$deny(fn()=>$q("UPDATE recycler_capability_change_requests SET review_note='Rewritten' WHERE request_id=?",[$request]),'Completed history immutable');
   $login($owner);$deny(fn()=>$s->submit($owner,$input),'ADD existing category denied');$update=array_replace($input,['request_type'=>'UPDATE','requested_can_handle_high_risk'=>'1']);$deny(fn()=>$s->submit($owner,$update),'No-op UPDATE denied');
   $bid=$bids->placeBid($owner,$lot,'1100');$deactivate=array_replace($input,['request_type'=>'DEACTIVATE','requested_can_handle_high_risk'=>'']);
   $deny(fn()=>$s->submit($owner,$deactivate),'Active bid blocks deactivation');$reduce=array_replace($update,['requested_can_handle_high_risk'=>'0']);$deny(fn()=>$s->submit($owner,$reduce),'HIGH source obligation blocks risk reduction');
   // A demonstrably LOW source does not prohibit continued fulfilment after removing high-risk permission.
   $q("UPDATE request_items SET applied_risk_level='LOW' WHERE request_item_id=?",[$f['requestItem']]);$q("UPDATE collection_record_items SET actual_risk_level='LOW' WHERE request_item_id=?",[$f['requestItem']]);
   $before=$getCap();$low=$s->submit($owner,$reduce);$check($getCap()===$before,'Pending UPDATE retains approved row');$login($admin,'ADMIN');$s->review($owner,$admin,$low,'APPROVE','Low-risk obligations remain supported');$check((int)$getCap()['can_handle_high_risk']===0,'UPDATE reduces high-risk permission');$check($lots->isEligible($lot,$owner),'LOW E-Lot remains authorized after supported reduction');
   $login($owner);$restore=$s->submit($owner,$update);$login($admin,'ADMIN');$s->review($owner,$admin,$restore,'APPROVE','Explicit high-risk coverage remains current');
   $q("UPDATE request_items SET applied_risk_level='HIGH' WHERE request_item_id=?",[$f['requestItem']]);$q("UPDATE collection_record_items SET actual_risk_level='HIGH' WHERE request_item_id=?",[$f['requestItem']]);
   $q('UPDATE e_lots SET bidding_close_at=CURRENT_TIMESTAMP-INTERVAL 1 MINUTE WHERE e_lot_id=?',[$lot]);$login($owner);$deny(fn()=>$s->submit($owner,$deactivate),'Closed bidding awaiting award still outstanding');
   $q("UPDATE recycler_bids SET bid_status='WINNING',reviewed_by_officer_user_id=?,reviewed_at=CURRENT_TIMESTAMP WHERE bid_id=?",[$f['users']['officer'],$bid]);$q("UPDATE e_lots SET lot_status='AWARDED' WHERE e_lot_id=?",[$lot]);
   $q('INSERT INTO handover_records(winning_bid_id,recorded_by_officer_user_id) VALUES(?,?)',[$bid,$f['users']['officer']]);$deny(fn()=>$s->submit($owner,$deactivate),'Winning bid/incomplete handover blocks reduction');
   foreach(['SCHEDULED', 'PENDING'] as $status){$q('UPDATE handover_records SET handover_status=? WHERE winning_bid_id=?',[$status,$bid]);$deny(fn()=>$s->submit($owner,$deactivate),'Outstanding handover state blocks reduction');}
   $q("UPDATE handover_records SET handover_status='COMPLETED',handover_date=CURRENT_TIMESTAMP WHERE winning_bid_id=?",[$bid]);$pending=$s->submit($owner,$deactivate);$before=$getCap();$check($before['capability_status']==='APPROVED','Pending DEACTIVATE keeps current capability');
   $q("UPDATE handover_records SET handover_status='PENDING' WHERE winning_bid_id=?",[$bid]);$login($admin,'ADMIN');$deny(fn()=>$s->review($owner,$admin,$pending,'APPROVE','Obligations rechecked'),'Live obligation recheck at approval');$check($getCap()===$before,'Unsafe approval leaves capability unchanged');
   $q("UPDATE handover_records SET handover_status='COMPLETED' WHERE winning_bid_id=?",[$bid]);$bidRows=$q('SELECT * FROM recycler_bids ORDER BY bid_id')->fetchAll();$handoverRows=$q('SELECT * FROM handover_records')->fetchAll();$s->review($owner,$admin,$pending,'APPROVE','All relevant handovers completed');
   $check($getCap()['capability_status']==='INACTIVE','DEACTIVATE preserves row as INACTIVE');$check($bidRows===$q('SELECT * FROM recycler_bids ORDER BY bid_id')->fetchAll() && $handoverRows===$q('SELECT * FROM handover_records')->fetchAll(),'Historical bids, awards and handovers preserved');
   $row=$q('SELECT * FROM recycler_capability_change_requests WHERE request_id=?',[$pending])->fetch();$check(json_decode($row['capability_snapshot_json'],true)===$before,'Full previous approval and reviewer snapshot retained');
   $login($owner);$reactivate=$s->submit($owner,$update);$login($admin,'ADMIN');$s->review($owner,$admin,$reactivate,'APPROVE','Reactivate with current verified coverage');$check($getCap()['capability_status']==='APPROVED','INACTIVE reactivation uses UPDATE');
   // Rejection never changes live permissions; stale capability and review metadata are rechecked.
   $login($owner);$rejected=$s->submit($owner,$reduce);$before=$getCap();$login($admin,'ADMIN');$deny(fn()=>$s->review($owner,$admin,$rejected,'REJECT','...'),'Meaningful reviewer feedback required');
   $q("UPDATE recycler_capabilities SET review_note='Concurrent audit change' WHERE recycler_user_id=? AND category_id=?",[$owner,$category]);$deny(fn()=>$s->review($owner,$admin,$rejected,'APPROVE','Stale snapshot check'),'Stale capability snapshot rejected');$q('UPDATE recycler_capabilities SET review_note=? WHERE recycler_user_id=? AND category_id=?',[$before['review_note'],$owner,$category]);
   $s->review($owner,$admin,$rejected,'REJECT','Requested change is not required');$check($getCap()===$before,'Rejection preserves current capability');
   // Legacy requests remain rejectable; no snapshot backfill grants permission.
   $q("INSERT INTO recycler_capability_change_requests(recycler_user_id,category_id,request_type,previous_capability_status,previous_can_handle_high_risk,requested_can_handle_high_risk,request_reason) VALUES(?,?,'UPDATE','APPROVED',1,0,'Historical pending request')",[$owner,$category]);$legacyRequest=(int)$db->lastInsertId();$deny(fn()=>$s->review($owner,$admin,$legacyRequest,'APPROVE','Legacy snapshot missing'),'Legacy snapshot-free approval denied');$s->review($owner,$admin,$legacyRequest,'REJECT','Resubmit with a trustworthy current snapshot');
   // Independent processes cannot review the same request twice.
   $login($owner);$race=$s->submit($owner,array_replace($deactivate,['category_id'=>(string)$spare]));$env=getenv();$env['DB_DATABASE']=$name;$workers=[];$db->beginTransaction();$q('SELECT user_id FROM users WHERE user_id=? FOR UPDATE',[$owner]);
   for($i=0;$i<2;$i++){$p=proc_open([PHP_BINARY,'-d','session.save_path=/tmp',__FILE__,'--worker','review',(string)$owner,(string)$admin,(string)$race],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,$env);$check(trim(fgets($pipes[1]))==='READY','Concurrent reviewer ready');$workers[]=[$p,$pipes];}
   $db->commit();$results=[];foreach($workers as [$p,$pipes]){$results[]=trim(stream_get_contents($pipes[1]));$err=stream_get_contents($pipes[2]);$check(proc_close($p)===0 && $err==='','Concurrent worker clean');}$check(count(array_filter($results,fn($r)=>$r==='APPROVED'))===1,'Exactly one concurrent decision succeeds');
   // A new bid racing with a reduction must not both commit successfully.
   $q("INSERT INTO e_lots(lot_code,created_by_collector_user_id,category_id,title,lot_status,verified_by_officer_user_id,verified_at,bidding_open_at,bidding_close_at) SELECT CONCAT(lot_code,'-race'),created_by_collector_user_id,category_id,title,'OPEN_FOR_BIDDING',verified_by_officer_user_id,verified_at,CURRENT_TIMESTAMP-INTERVAL 1 DAY,CURRENT_TIMESTAMP+INTERVAL 7 DAY FROM e_lots WHERE e_lot_id=?",[$lot]);$raceLot=(int)$db->lastInsertId();
   $login($owner);$raceRequest=$s->submit($owner,$deactivate);$workers=[];$db->beginTransaction();$q('SELECT user_id FROM users WHERE user_id=? FOR UPDATE',[$owner]);
   foreach([['review',$raceRequest],['bid',$raceLot]] as [$action,$target]){$p=proc_open([PHP_BINARY,'-d','session.save_path=/tmp',__FILE__,'--worker',$action,(string)$owner,(string)$admin,(string)$target],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,$env);$check(trim(fgets($pipes[1]))==='READY','Bid/reduction worker ready');$workers[]=[$p,$pipes];}
   $db->commit();$results=[];foreach($workers as [$p,$pipes]){$results[]=trim(stream_get_contents($pipes[1]));$err=stream_get_contents($pipes[2]);$check(proc_close($p)===0 && $err==='','Bid/reduction worker clean');}$check(!($results[0]==='APPROVED' && $results[1]==='BID'),'Concurrent new bid and deactivation cannot both succeed');$check(in_array('APPROVED',$results,true) || in_array('BID',$results,true),'Concurrent operations preserve progress: one legitimate action commits');$check(in_array($results[0],['APPROVED','BLOCKED'],true) && in_array($results[1],['BID','BLOCKED'],true),'Race returns controlled outcomes');
   $login($owner);
   if($getCap()['capability_status']==='INACTIVE'){$id=$s->submit($owner,$update);$login($admin,'ADMIN');$s->review($owner,$admin,$id,'APPROVE','Current evidence supports reactivation');}
   else{$login($owner);$raceBid=(int)$q('SELECT bid_id FROM recycler_bids WHERE e_lot_id=? AND recycler_user_id=?',[$raceLot,$owner])->fetchColumn();if($raceBid)$bids->withdrawBid($owner,$raceBid);$login($admin,'ADMIN');$s->review($owner,$admin,$raceRequest,'REJECT','Request overtaken by a new active bid');}
   // Required renewal race: pending renewal is harmless; approved replacement makes old request stale.
   $login($owner);$old=$s->submit($owner,$reduce);$before=$getCap();$tmp=tempnam('/tmp','ecolot-cap-renew-');$tempFiles[]=$tmp;file_put_contents($tmp,"%PDF-1.4\nSynthetic new capability evidence ".bin2hex(random_bytes(8))."\n%%EOF\n");
   $renewal=new RecyclerLicenseRenewalService($db,static fn(string $path):bool=>is_file($path));$renew=$renewal->submit($owner,['license_number'=>$doc['license_no'],'expiry_date'=>'2099-12-31'],['name'=>'replacement.pdf','tmp_name'=>$tmp,'error'=>UPLOAD_ERR_OK]);
   $check($getCap()===$before,'Pending renewal and capability request preserve current permission');$login($admin,'ADMIN');$renewal->review($owner,$admin,$renew,'APPROVE','New licence document independently inspected');$deny(fn()=>$s->review($owner,$admin,$old,'APPROVE','Old request after renewal'),'Approved renewal invalidates old request document identity');$check($getCap()===$before,'Stale post-renewal approval cannot alter capability');$s->review($owner,$admin,$old,'REJECT','Re-evaluate with the newly approved licence document');
   $login($owner);$fresh=$s->submit($owner,$reduce);$login($admin,'ADMIN');$deny(fn()=>$s->review($owner,$admin,$fresh,'APPROVE','Old document scope only'),'Old VERIFIED scope cannot authorize replacement');
   $newDoc=$model->selectedLicenseForRecycler($owner);$proposalNew=$proposal(1);$proposalNew['document_sha256']=hash_file('sha256',APP_ROOT.'/storage/'.$newDoc['document_path']);
   $scope->proposeScope($owner,$admin,$proposalNew,false,$fresh);$id=(int)$q('SELECT MAX(scope_id) FROM recycler_license_scopes')->fetchColumn();$scope->reviewScope($owner,$id,$admin,'VERIFIED','New document scope inspected independently',true,false,$fresh);$s->review($owner,$admin,$fresh,'APPROVE','Current new evidence supports requested risk');$check((int)$getCap()['can_handle_high_risk']===0,'Re-evaluated request uses only new verified document');
   $rows=$q('SELECT * FROM recycler_capability_change_requests ORDER BY request_id')->fetchAll();$caps=$q('SELECT * FROM recycler_capabilities ORDER BY recycler_user_id,category_id')->fetchAll();TestDatabase::applyMigration($db,$migration);$check($rows===$q('SELECT * FROM recycler_capability_change_requests ORDER BY request_id')->fetchAll() && $caps===$q('SELECT * FROM recycler_capabilities ORDER BY recycler_user_id,category_id')->fetchAll(),'Populated migration replay preserves all request and capability history');
   echo "PASS $kind capability changes, scope, renewal and bid/review races\n";
   if($kind==='fresh' && getenv('ECOLOT_KEEP_CAPABILITY_TEST')==='1'){$keep=$name;file_put_contents('/tmp/ecolot-capability/fixture.json',json_encode(['database'=>$name,'owner'=>$owner,'other'=>$other,'admin'=>$admin,'category'=>$category,'spare'=>$spare,'mobile'=>$f['mobiles']['recycler'],'admin_mobile'=>$f['mobiles']['admin']]));}
  }finally{
   if($db->inTransaction())$db->rollBack();
   if($name!==$keep){$paths=[];try{$paths=$q('SELECT document_path FROM recycler_license_update_requests')->fetchAll(PDO::FETCH_COLUMN);}catch(PDOException $ignored){}foreach($paths as $relative)if(preg_match('#^uploads/recycler-licenses/[a-f0-9]{48}\.pdf$#D',$relative) && is_file(APP_ROOT.'/storage/'.$relative))unlink(APP_ROOT.'/storage/'.$relative);$db->exec("DROP DATABASE IF EXISTS `$name`");}
  }
 }
 echo "PASS: $checks capability lifecycle/security/history/fresh-upgraded/concurrency assertions\n";
}finally{foreach($tempFiles as $path)if(is_file($path))unlink($path);}
