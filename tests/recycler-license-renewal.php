<?php
declare(strict_types=1);
// Disposable fresh/upgraded acceptance only. Upload fakes are injected exclusively in CLI tests.
if (getenv('ECOLOT_RENEWAL_TEST') !== '1') { http_response_code(404); exit(1); }
require __DIR__.'/support/bootstrap.php';
Session::start();
$config = require APP_ROOT.'/config/database.php';
if (PHP_SAPI==='cli-server') {
    if (!preg_match('/^ecolot_test_renewal_[a-f0-9]{12}$/D',$config['database'])) exit(1);
    $path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
    if (str_starts_with($path,'/assets/') && is_file(APP_ROOT.'/public'.$path)) return false;
    $_SERVER['SCRIPT_NAME']='/index.php'; require APP_ROOT.'/public/index.php'; exit;
}
$db = new PDO(sprintf('mysql:host=%s;port=%s;charset=utf8mb4',$config['host'],$config['port']),$config['username'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
$db->exec("SET time_zone='+05:30'");
if (($argv[1]??'')==='--review-worker') {
    if (!preg_match('/^ecolot_test_renewal_[a-f0-9]{12}$/D',$config['database'])) exit(1);
    $db->exec('USE `'.$config['database'].'`');Session::put('auth_user',['id'=>(int)$argv[3],'role'=>'ADMIN']);
    echo "READY\n";flush();
    try {(new RecyclerLicenseRenewalService($db))->review((int)$argv[2],(int)$argv[3],(int)$argv[4],'APPROVE','Concurrent review');echo "APPROVED\n";}
    catch (Throwable $error) {echo "BLOCKED\n";}
    exit;
}
require APP_ROOT.'/tests/support/RecyclerProfileFixture.php';
$checks=0;$files=[];$keep=null;
$check=static function(bool $ok,string $why)use(&$checks):void{if(!$ok)throw new RuntimeException($why);$checks++;};
$deny=static function(callable $fn,string $why)use($check):void{try{$fn();}catch(DomainException|InvalidArgumentException|RuntimeException $error){$check(true,$why);return;}throw new RuntimeException('Expected denial: '.$why);};
$q=static function(string $sql,array $args=[])use($db):PDOStatement{$s=$db->prepare($sql);$s->execute($args);return $s;};
$login=static function(int $id,string $role='RECYCLER'):void{Session::put('auth_user',['id'=>$id,'role'=>$role,'name'=>'Synthetic review account']);};
$pdf=static function(?string $content=null)use(&$files):array{$tmp=tempnam('/tmp','ecolot-renewal-upload-');file_put_contents($tmp,$content ?? "%PDF-1.4\nSynthetic renewal evidence ".bin2hex(random_bytes(8))."\n%%EOF\n");$files[]=$tmp;return ['name'=>'licence.pdf','tmp_name'=>$tmp,'error'=>UPLOAD_ERR_OK];};
try {
 foreach (['fresh','upgraded'] as $kind) {
    $name='ecolot_test_renewal_'.bin2hex(random_bytes(6));$db->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    try {
        $db->exec("USE `$name`");
        if ($kind==='fresh') TestDatabase::loadSchema($db);
        else {
            $legacy=getenv('ECOLOT_LEGACY_SCHEMA');if (!$legacy || !is_file($legacy))throw new RuntimeException('Metadata-only legacy schema required.');
            $env=getenv();$env['MYSQL_PWD']=$config['password'];
            $p=proc_open(['/opt/lampp/bin/mysql','--no-defaults','--host='.$config['host'],'--port='.$config['port'],'--user='.$config['username'],$name],[0=>['file',$legacy,'r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,$env);
            stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);if(proc_close($p)!==0)throw new RuntimeException($err);
            foreach(['20261116_recycler_initial_scope.sql','20261119_recycler_authorization_foundation.sql'] as $migration)TestDatabase::applyMigration($db,APP_ROOT.'/database/migrations/'.$migration);
        }
        $migration=APP_ROOT.'/database/migrations/20261119_recycler_license_renewal.sql';
        $triggers=$q('SELECT TRIGGER_NAME,ACTION_STATEMENT FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() ORDER BY TRIGGER_NAME')->fetchAll();
        TestDatabase::applyMigration($db,$migration);TestDatabase::applyMigration($db,$migration);
        $afterTriggers=$q("SELECT TRIGGER_NAME,ACTION_STATEMENT FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME<>'trg_recycler_renewal_submission_bu' ORDER BY TRIGGER_NAME")->fetchAll();
        $beforeTriggers=array_values(array_filter($triggers,fn($row)=>$row['TRIGGER_NAME']!=='trg_recycler_renewal_submission_bu'));
        $check($beforeTriggers===$afterTriggers,'Renewal migration preserves all legacy/canonical triggers');
        $db->beginTransaction();$f=recyclerProfileFixture($db,'RenewalTest123!');$historical=recyclerProfileFixture($db,'RenewalTest123!');$db->commit();
        $q("UPDATE recycler_bids SET bid_status='WINNING',reviewed_by_officer_user_id=?,reviewed_at=CURRENT_TIMESTAMP WHERE bid_id=?",[$historical['users']['officer'],$historical['competitorBid']]);
        $q("UPDATE e_lots SET lot_status='AWARDED' WHERE e_lot_id=?",[$historical['lot']]);
        $q("INSERT INTO handover_records(winning_bid_id,handover_status,handover_date,recorded_by_officer_user_id,remarks) VALUES(?,'COMPLETED',CURRENT_TIMESTAMP,?,'Historical obligation')",[$historical['competitorBid'],$historical['users']['officer']]);
        $owner=(int)$f['users']['recycler'];$admin=(int)$f['users']['admin'];$other=(int)$f['users']['competitor'];
        $service=new RecyclerLicenseRenewalService($db,static fn(string $path):bool=>is_file($path));
        $model=new AuthorizedRecycler($db);$scope=new RecyclerLicenseReviewService($db);$bids=new RecyclerBidService($db);$lots=new ELot($db);
        $login($owner);$current=$model->selectedLicenseForRecycler($owner);$licenseId=(int)$current['license_id'];
        $input=['license_number'=>$current['license_no'],'expiry_date'=>$q('SELECT CURRENT_DATE+INTERVAL 1 DAY')->fetchColumn(),'explanation'=>'Renewed licence evidence'];
        $upload=$pdf();$rawLicense=$q('SELECT * FROM recycler_licenses WHERE license_id=?',[$licenseId])->fetch();
        $check($service->available(),'Renewal storage ready');
        $check($lots->isEligible((int)$f['lot'],$owner),'Original canonical grant eligible');
        $bid=$bids->placeBid($owner,(int)$f['lot'],'1100');
        $snapshot=$q('SELECT * FROM recycler_bids ORDER BY bid_id')->fetchAll();$handovers=$q('SELECT * FROM handover_records ORDER BY winning_bid_id')->fetchAll();$scopes=$q('SELECT * FROM recycler_license_scopes ORDER BY scope_id')->fetchAll();
        foreach([['expiry_date'=>'2020-01-01'],['expiry_date'=>'2099-02-30'],['license_number'=>''],['license_number'=>str_repeat('x',101)],['explanation'=>str_repeat('x',501)]] as $bad)$deny(fn()=>$service->submit($owner,array_replace($input,$bad),$upload),'Invalid licence fields');
        foreach(['not a pdf',"%PDF-1.4\nNo end marker",str_repeat('x',5*1024*1024+1)] as $bad)$deny(fn()=>$service->submit($owner,$input,$pdf($bad)),'Unsafe PDF rejected');
        $deny(fn()=>$service->submit($owner,$input,[]),'Missing PDF');
        $deny(fn()=>$service->submit($other,$input,$upload),'Foreign owner denied');
        $login($owner,'ADMIN');$deny(fn()=>$service->submit($owner,$input,$upload),'Wrong role denied');$login($owner);
        $q("UPDATE users SET account_status='SUSPENDED' WHERE user_id=?",[$owner]);$deny(fn()=>$service->submit($owner,$input,$upload),'Suspended owner denied');$q("UPDATE users SET account_status='ACTIVE' WHERE user_id=?",[$owner]);
        $request=$service->submit($owner,$input,$upload);
        $check($rawLicense===$q('SELECT * FROM recycler_licenses WHERE license_id=?',[$licenseId])->fetch(),'Pending leaves authoritative row unchanged');
        $check($model->selectedLicenseForRecycler($owner)['license_no']===$current['license_no'],'Current selection stays authoritative');
        $check($lots->isEligible((int)$f['lot'],$owner),'Pending preserves canonical eligibility');
        $deny(fn()=>$service->submit($owner,$input,$upload),'Duplicate pending request');
        $check($service->document($other,$request)===null,'Foreign document denied');
        $path=$service->document($owner,$request);$check($path!==null && hash_file('sha256',$path)===hash_file('sha256',$upload['tmp_name']),'Stable submitted document hash');
        $check((fileperms($path)&0222)===0,'New document has no write permission');
        $deny(fn()=>$service->review($owner,$admin,$request,'APPROVE','Reviewed'),'Recycler cannot review');
        $login($admin,'ADMIN');$deny(fn()=>$service->review($other,$admin,$request,'APPROVE','Reviewed'),'Foreign owner review denied');
        $deny(fn()=>$service->review($owner,$admin,$request,'REJECT','...'),'Meaningful rejection reason required');
        $q('UPDATE recycler_licenses SET review_note=? WHERE license_id=?',['Concurrent change',$licenseId]);$deny(fn()=>$service->review($owner,$admin,$request,'APPROVE','Reviewed'),'Stale baseline denied');$q('UPDATE recycler_licenses SET review_note=? WHERE license_id=?',[$rawLicense['review_note'],$licenseId]);
        // Read-only storage protects application writes; this simulates a privileged filesystem replacement.
        chmod($path,0600);$original=file_get_contents($path);file_put_contents($path,"%PDF-1.4\nChanged valid PDF\n%%EOF\n");
        $deny(fn()=>$service->review($owner,$admin,$request,'APPROVE','Reviewed'),'Changed submitted PDF denied');$check($service->document($owner,$request)===null,'Changed bytes denied by protected reader');file_put_contents($path,$original);chmod($path,0440);
        $q("UPDATE users SET account_status='SUSPENDED' WHERE user_id=?",[$owner]);$deny(fn()=>$service->review($owner,$admin,$request,'APPROVE','Reviewed'),'Owner suspension blocks approval');$q("UPDATE users SET account_status='ACTIVE' WHERE user_id=?",[$owner]);
        $q("UPDATE users SET account_status='DISABLED' WHERE user_id=?",[$admin]);$deny(fn()=>$service->review($owner,$admin,$request,'APPROVE','Reviewed'),'Inactive Administrator blocked');$q("UPDATE users SET account_status='ACTIVE' WHERE user_id=?",[$admin]);
        $deny(fn()=>$q("UPDATE recycler_license_update_requests SET requested_expiry_date='2020-01-01' WHERE request_id=?",[$request]),'Submitted metadata cannot change after upload');
        $q('SET timestamp='.(time()+3*86400));try{$deny(fn()=>$service->review($owner,$admin,$request,'APPROVE','Reviewed'),'Expired pending document cannot be approved');}finally{$q('SET timestamp=0');}
        $service->review($owner,$admin,$request,'APPROVE','Authentic new licence inspected');
        $check($model->selectedLicenseForRecycler($owner)['document_path']!==$current['document_path'],'New selected document path/version');
        $check($q('SELECT previous_document_path FROM recycler_license_revisions WHERE update_request_id=?',[$request])->fetchColumn()===$current['document_path'],'Previous document retained');
        $check($service->document($owner,$request,true)!==null,'Original PDF protected historical access');
        $check($scopes===$q('SELECT * FROM recycler_license_scopes ORDER BY scope_id')->fetchAll(),'No automatic scope inheritance or rewrite');
        $check($snapshot===$q('SELECT * FROM recycler_bids ORDER BY bid_id')->fetchAll(),'Existing bids and awards untouched by approval');$check($handovers===$q('SELECT * FROM handover_records ORDER BY winning_bid_id')->fetchAll(),'Completed handover history preserved');
        $deny(fn()=>$scope->requireApprovedScope($owner,(int)$f['category'],['RECYCLING'],true),'New document lacks independent approved scope');


        $login($admin,'ADMIN');$deny(fn()=>$service->review($owner,$admin,$request,'APPROVE','Replay'),'Repeated decision denied');
        $check($service->canReviewScope($owner),'Renewed document has separate scope review context');
        $doc=$model->selectedLicenseForRecycler($owner);
        $proposal=['license_id'=>(string)$licenseId,'document_sha256'=>hash_file('sha256',$path),'category_id'=>(string)$f['category'],'activity_type'=>'RECYCLING','covers_high_risk'=>'1','authorization_basis'=>'SWML','waste_description'=>'Synthetic reviewed devices','evidence_reference'=>'Page 2','licence_conditions'=>'Synthetic conditions','waste_code'=>''];
        $deny(fn()=>$scope->proposeScope($owner,$admin,$proposal),'Initial scope context still closed');
        $scope->proposeScope($owner,$admin,$proposal,true);$scopeId=(int)$q('SELECT MAX(scope_id) FROM recycler_license_scopes')->fetchColumn();
        $deny(fn()=>$scope->requireApprovedScope($owner,(int)$f['category'],['RECYCLING'],true),'Scope proposal alone grants nothing');
        $deny(fn()=>$scope->reviewScope($owner,$scopeId,$admin,'VERIFIED','Inspected',false,true),'Explicit independent inspection required');
        $scope->reviewScope($owner,$scopeId,$admin,'VERIFIED','Exact new evidence inspected',true,true);
        $scope->requireApprovedScope($owner,(int)$f['category'],['RECYCLING'],true);$check(true,'Independent canonical scope supports current profile permissions');
        $check($q('SELECT bid_amount FROM recycler_bids WHERE bid_id=?',[$bid])->fetchColumn()==='1100.00','Renewal and scope reviews preserve the historical bid amount');
        $login($owner);$deny(fn()=>$service->submit($owner,$input,$upload),'Already-recorded PDF cannot create a new version');
        $approved=$q('SELECT * FROM recycler_licenses WHERE license_id=?',[$licenseId])->fetch();
        $rejected=$service->submit($owner,array_replace($input,['license_number'=>'REJECT-'.bin2hex(random_bytes(4))]),$pdf());
        $login($admin,'ADMIN');$service->review($owner,$admin,$rejected,'REJECT','Document not supported by submitted evidence');
        $check($approved===$q('SELECT * FROM recycler_licenses WHERE license_id=?',[$licenseId])->fetch(),'Rejection preserves approved row');
        $check($q('SELECT request_status FROM recycler_license_update_requests WHERE request_id=?',[$rejected])->fetchColumn()==='REJECTED','Rejection history retained');
        // A number claimed after submission must roll back both the row change and archive.
        $login($owner);$conflictNumber='CONFLICT-'.bin2hex(random_bytes(4));
        $conflict=$service->submit($owner,array_replace($input,['license_number'=>$conflictNumber]),$pdf());
        $q("INSERT INTO recycler_licenses(recycler_user_id,license_number,expiry_date) VALUES(?,?,'2099-12-31')",[$other,$conflictNumber]);
        $login($admin,'ADMIN');$deny(fn()=>$service->review($owner,$admin,$conflict,'APPROVE','Checked replacement'),'Late licence-number conflict denied');
        $check($approved===$q('SELECT * FROM recycler_licenses WHERE license_id=?',[$licenseId])->fetch(),'Conflicting approval preserves authoritative row');
        $check((int)$q('SELECT COUNT(*) FROM recycler_license_revisions WHERE update_request_id=?',[$conflict])->fetchColumn()===0,'Conflicting approval rolls back archive');
        $service->review($owner,$admin,$conflict,'REJECT','Licence number was claimed by a conflicting record');
        $deny(fn()=>$q("UPDATE recycler_license_update_requests SET review_note='Changed history' WHERE request_id=?",[$conflict]),'Completed decision cannot be rewritten');
        $version=$q('SELECT previous_document_version FROM recycler_license_revisions WHERE update_request_id=?',[$request])->fetchColumn();
        $check($kind==='fresh' ? $version===null : (int)$version===(int)$rawLicense['document_version'],'Existing legacy document-version counter archived without reinterpretation');
        // The selected legacy licence can lack a PDF; replacement does not fabricate it.
        $login($other);$legacyDoc=$model->selectedLicenseForRecycler($other);$q('UPDATE recycler_licenses SET document_path=NULL WHERE license_id=?',[$legacyDoc['license_id']]);
        $legacyRequest=$service->submit($other,['license_number'=>$legacyDoc['license_no'],'expiry_date'=>'2099-12-31'],$pdf());
        $login($admin,'ADMIN');$service->review($other,$admin,$legacyRequest,'APPROVE','Legacy original missing; genuine replacement inspected');
        $check($service->document($other,$legacyRequest,true)===null,'Missing historical PDF is not fabricated');
        $check($service->currentDocument($other,$model->selectedLicenseForRecycler($other))!==null,'Legacy missing original safely replaced');
        // Legacy fingerprint-free pending requests are retained and rejectable, never auto-trusted.
        $q("INSERT INTO recycler_license_update_requests(recycler_user_id,current_license_id,requested_license_number,requested_expiry_date,document_path) VALUES(?,?,?,'2099-12-31',?)",[$other,$legacyDoc['license_id'],'LEGACY-PENDING',$legacyDoc['document_path']]);$old=(int)$db->lastInsertId();
        $deny(fn()=>$service->review($other,$admin,$old,'APPROVE','Legacy'),'Legacy pending request requires resubmission');$service->review($other,$admin,$old,'REJECT','Resubmit with a verifiable document identity');
        // Two real PHP processes contend on owner lock; exactly one reviews and archives.
        $login($owner);$race=$service->submit($owner,array_replace($input,['expiry_date'=>'2099-11-30']),$pdf());
        $env=getenv();$env['DB_DATABASE']=$name;$workers=[];$db->beginTransaction();$q('SELECT user_id FROM users WHERE user_id=? FOR UPDATE',[$owner]);
        for($i=0;$i<2;$i++){$p=proc_open([PHP_BINARY,'-d','session.save_path=/tmp',__FILE__,'--review-worker',(string)$owner,(string)$admin,(string)$race],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,$env);$check(trim(fgets($pipes[1]))==='READY','Concurrent reviewer ready');$workers[]=[$p,$pipes];}
        $db->commit();$results=[];foreach($workers as [$p,$pipes]){$results[]=trim(stream_get_contents($pipes[1]));$err=stream_get_contents($pipes[2]);$check(proc_close($p)===0 && $err==='','Concurrent reviewer clean');}
        $check(count(array_filter($results,fn($r)=>$r==='APPROVED'))===1,'Exactly one concurrent review succeeds');
        $check((int)$q('SELECT COUNT(*) FROM recycler_license_revisions WHERE update_request_id=?',[$race])->fetchColumn()===1,'Concurrent review archives once');
        // Migration replay retains populated decisions and revision history.
        $rows=$q('SELECT * FROM recycler_license_update_requests ORDER BY request_id')->fetchAll();$rev=$q('SELECT * FROM recycler_license_revisions ORDER BY revision_id')->fetchAll();TestDatabase::applyMigration($db,$migration);
        $check($rows===$q('SELECT * FROM recycler_license_update_requests ORDER BY request_id')->fetchAll() && $rev===$q('SELECT * FROM recycler_license_revisions ORDER BY revision_id')->fetchAll(),'Populated migration replay preserves complete history');
        echo "PASS $kind licence renewal lifecycle, canonical scope, migration and concurrent review\n";
        if ($kind==='fresh' && getenv('ECOLOT_KEEP_RENEWAL_TEST')==='1') {
            $keep=$name;file_put_contents('/tmp/ecolot-renewal/fixture.json',json_encode(['database'=>$name,'owner'=>$owner,'other'=>$other,'admin'=>$admin,'mobile'=>$f['mobiles']['recycler'],'admin_mobile'=>$f['mobiles']['admin']]));
        }
    } finally {
        if($db->inTransaction())$db->rollBack();
        if($name!==$keep){$paths=[];try{$paths=$q('SELECT document_path FROM recycler_license_update_requests')->fetchAll(PDO::FETCH_COLUMN);}catch(PDOException $ignored){}foreach($paths as $relative){$path=APP_ROOT.'/storage/'.$relative;if(is_file($path) && preg_match('/^[a-f0-9]{48}\.pdf$/D',basename($path)))unlink($path);}$db->exec("DROP DATABASE IF EXISTS `$name`");}
    }
 }
 echo "PASS: $checks renewal security, scope, preservation, fresh/upgraded and concurrency assertions\n";
} finally {foreach($files as $path)if(is_file($path))unlink($path);}
