<?php
declare(strict_types=1);
// Guarded initial-review integration and HTTP acceptance. No real SMS or application DB.
if (getenv('ECOLOT_ADMIN_REVIEW_TEST')!=='1' || (PHP_SAPI==='cli-server' && !preg_match('/^ecolot_test_admin_review_[a-f0-9]{16}$/D',(string)getenv('DB_DATABASE')))) { http_response_code(404); exit(1); }
$root=dirname(__DIR__);
if (PHP_SAPI==='cli-server') { $_SERVER['SCRIPT_NAME']='/index.php';require $root.'/public/index.php';exit; }
require __DIR__.'/support/bootstrap.php';
Session::start();
$config=require APP_ROOT.'/config/database.php';
$db=new PDO(sprintf('mysql:host=%s;port=%s;charset=utf8mb4',$config['host'],$config['port']),$config['username'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
if (($argv[1]??'')==='--review-worker') {
    $target=$argv[2]??'';
    if (!preg_match('/^ecolot_test_admin_review_[a-f0-9]{16}$/D',$target)) exit(1);
    $db->exec("USE `$target`"); Session::put('auth_user',['id'=>1,'role'=>'ADMIN']);
    echo "READY\n";flush();
    try {(new RecyclerApprovalService($db))->review((int)$argv[3],1,'APPROVE');echo "APPROVED\n";}
    catch (RuntimeException $error) {echo "BLOCKED\n";}
    exit;
}
$name='ecolot_test_admin_review_'.bin2hex(random_bytes(8));$created=false;$files=[];$server=null;$checks=0;
$check=static function(bool $ok,string $why)use(&$checks):void{if(!$ok)throw new RuntimeException($why);++$checks;};
$blocked=static function(callable $fn)use($check):void{try{$fn();}catch(InvalidArgumentException|RuntimeException $error){$check(true,$error->getMessage());return;}throw new RuntimeException('Expected rejection');};
$password='Phase1Review123!';$hash=password_hash($password,PASSWORD_DEFAULT);
try {
    $db->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");$created=true;$db->exec("USE `$name`");$db->exec("SET time_zone='+05:30'");TestDatabase::loadSchema($db);
    $db->exec(file_get_contents(APP_ROOT.'/database/migrations/20261116_recycler_initial_scope.sql'));
    $db->exec("INSERT INTO users(user_id,full_name,mobile_number,password_hash,role,account_status,mobile_verified_at) VALUES(1,'Review Admin','94779999001','$hash','ADMIN','ACTIVE',CURRENT_TIMESTAMP)");$db->exec('INSERT INTO administrators VALUES(1)');
    $db->exec("INSERT INTO waste_categories(category_id,category_name) VALUES(1,'Domestic'),(2,'Office')");
    $pdf=static function()use(&$files):string{$relative='uploads/recycler-licenses/phase1-'.bin2hex(random_bytes(8)).'.pdf';$path=APP_ROOT.'/storage/'.$relative;file_put_contents($path,"%PDF-1.4\nSynthetic evidence ".bin2hex(random_bytes(4))."\n%%EOF\n");$files[]=$path;return $relative;};
    $owner=static function(int $id)use($db,$hash,$pdf):array{
        $db->prepare("INSERT INTO users(user_id,full_name,mobile_number,password_hash,role,account_status,mobile_verified_at) VALUES(?,'Synthetic contact',?,?,'RECYCLER','ACTIVE',CURRENT_TIMESTAMP)")->execute([$id,'9477'.str_pad((string)$id,7,'0',STR_PAD_LEFT),$hash]);
        $db->prepare("INSERT INTO authorized_recyclers(user_id,company_name,business_address,district) VALUES(?,?,'Test address','Colombo')")->execute([$id,'Synthetic company '.$id]);
        $file=$pdf();$db->prepare("INSERT INTO recycler_licenses(recycler_user_id,license_number,expiry_date,document_path) VALUES(?,?,CURRENT_DATE+INTERVAL 1 YEAR,?)")->execute([$id,'TEST-'.$id,$file]);$license=(int)$db->lastInsertId();
        $db->prepare('INSERT INTO recycler_capabilities(recycler_user_id,category_id) VALUES(?,1)')->execute([$id]);
        $db->prepare("INSERT INTO recycler_authorized_activities(recycler_user_id,activity_type) VALUES(?,'RECYCLING')")->execute([$id]);
        return [$license,(int)$db->lastInsertId(),$file];
    };
    Session::put('auth_user',['id'=>1,'role'=>'ADMIN']);
    $lic=new RecyclerLicenseReviewService($db);$cap=new RecyclerCapabilityReviewService($db);$act=new RecyclerAuthorizedActivityReviewService($db);$final=new RecyclerApprovalService($db);
    [$license,$activity,$file]=$owner(10);
    $blocked(fn()=>$final->review(10,1,'APPROVE'));
    $blocked(fn()=>$lic->review(10,1,$license+999,'APPROVE'));
    $lic->review(10,1,$license,'APPROVE');
    $check((int)$db->query('SELECT COUNT(*) FROM recycler_license_scopes')->fetchColumn()===0,'Licence approval never grants scope');
    $blocked(fn()=>$final->review(10,1,'APPROVE'));
    $blocked(fn()=>$lic->review(10,1,$license,'APPROVE'));
    $blocked(fn()=>$cap->review(10,1,1,'APPROVE'));$blocked(fn()=>$act->review(10,$activity,1,'APPROVE'));
    $proposal=static function(int $owner,int $license,string $file)use($db):array{return ['license_id'=>(string)$license,'document_sha256'=>hash_file('sha256',APP_ROOT.'/storage/'.$file),'category_id'=>'1','activity_type'=>'RECYCLING','covers_high_risk'=>'0','authorization_basis'=>'SWML','waste_description'=>'Recorded domestic waste','waste_code'=>'TEST','evidence_reference'=>'Page 1 / synthetic supporting permit','licence_conditions'=>'None recorded in synthetic evidence'];};
    $input=$proposal(10,$license,$file);
    $blocked(fn()=>$lic->proposeScope(10,1,array_replace($input,['document_sha256'=>str_repeat('0',64)])));
    $blocked(fn()=>$lic->proposeScope(10,1,array_replace($input,['covers_high_risk'=>'1','authorization_basis'=>'EPL'])));
    $blocked(fn()=>$lic->proposeScope(10,1,array_replace($input,['evidence_reference'=>' '])));
    $lic->proposeScope(10,1,$input);$scope=(int)$db->query('SELECT MAX(scope_id) FROM recycler_license_scopes')->fetchColumn();
    $check($db->query('SELECT scope_status FROM recycler_license_scopes WHERE scope_id='.$scope)->fetchColumn()==='PENDING','Evidence proposal remains pending');
    $blocked(fn()=>$lic->proposeScope(10,1,$input));$blocked(fn()=>$cap->review(10,1,1,'APPROVE'));
    $blocked(fn()=>$lic->reviewScope(10,$scope,1,'VERIFIED','Inspected',false));
    [$foreign]=$owner(11);$blocked(fn()=>$lic->reviewScope(11,$scope,1,'VERIFIED','Foreign',true));
    $lic->reviewScope(10,$scope,1,'VERIFIED','Exact evidence inspected',true);
    $blocked(fn()=>$lic->reviewScope(10,$scope,1,'REJECTED','Replay',true));
    $blocked(fn()=>$lic->proposeScope(10,1,$input));
    $db->exec('UPDATE recycler_capabilities SET can_handle_high_risk=1 WHERE recycler_user_id=10');$blocked(fn()=>$cap->review(10,1,1,'APPROVE'));$db->exec('UPDATE recycler_capabilities SET can_handle_high_risk=0 WHERE recycler_user_id=10');
    $cap->review(10,1,1,'APPROVE');$blocked(fn()=>$final->review(10,1,'APPROVE'));$act->review(10,$activity,1,'APPROVE');
    $db->exec('UPDATE users SET mobile_verified_at=NULL WHERE user_id=10');$blocked(fn()=>$final->review(10,1,'APPROVE'));$db->exec('UPDATE users SET mobile_verified_at=CURRENT_TIMESTAMP WHERE user_id=10');
    $db->exec("UPDATE users SET account_status='SUSPENDED' WHERE user_id=10");$blocked(fn()=>$final->review(10,1,'APPROVE'));$db->exec("UPDATE users SET account_status='ACTIVE' WHERE user_id=10");
    $db->exec('INSERT INTO recycler_capabilities(recycler_user_id,category_id) VALUES(10,2)');$blocked(fn()=>$final->review(10,1,'APPROVE'));
    $blocked(fn()=>$cap->review(10,2,1,'REJECT',' '));$cap->review(10,2,1,'REJECT','Not supported by evidence');
    $db->exec("INSERT INTO recycler_authorized_activities(recycler_user_id,activity_type) VALUES(10,'STORAGE')");$storage=(int)$db->lastInsertId();$blocked(fn()=>$final->review(10,1,'APPROVE'));$act->review(10,$storage,1,'REJECT','Not supported');
    $lic->proposeScope(10,1,array_replace($input,['covers_high_risk'=>'1']));$extra=(int)$db->query('SELECT MAX(scope_id) FROM recycler_license_scopes')->fetchColumn();$blocked(fn()=>$final->review(10,1,'APPROVE'));$lic->reviewScope(10,$extra,1,'REJECTED','Additional high-risk evidence not established',false);
    $path=APP_ROOT.'/storage/'.$file;$bytes=file_get_contents($path);file_put_contents($path,"%PDF-1.4\nChanged bytes\n%%EOF\n");$blocked(fn()=>$final->review(10,1,'APPROVE'));file_put_contents($path,$bytes);
    $db->exec("UPDATE recycler_licenses SET expiry_date=CURRENT_DATE-INTERVAL 1 DAY WHERE license_id=$license");$blocked(fn()=>$final->review(10,1,'APPROVE'));$db->exec("UPDATE recycler_licenses SET expiry_date=(SELECT expiry_date FROM recycler_license_scopes WHERE scope_id=$scope) WHERE license_id=$license");
    Session::put('auth_user',['id'=>11,'role'=>'RECYCLER']);$blocked(fn()=>$final->review(10,1,'APPROVE'));Session::put('auth_user',['id'=>1,'role'=>'ADMIN']);
    $db->exec("UPDATE users SET account_status='SUSPENDED' WHERE user_id=1");$blocked(fn()=>$final->review(10,1,'APPROVE'));$db->exec("UPDATE users SET account_status='ACTIVE' WHERE user_id=1");
    $final->review(10,1,'APPROVE');$record=$db->query('SELECT * FROM authorized_recyclers WHERE user_id=10')->fetch();
    $check($record['verification_status']==='VERIFIED'&&(int)$record['reviewed_by_admin_user_id']===1&&$record['reviewed_at']!==null,'Final verified decision is audited');$blocked(fn()=>$final->review(10,1,'APPROVE'));
    $blocked(fn()=>$final->review(11,1,'REJECT',' '));$final->review(11,1,'REJECT','Incomplete supporting evidence');
    $check($db->query('SELECT review_note FROM authorized_recyclers WHERE user_id=11')->fetchColumn()==='Incomplete supporting evidence','Final rejection records reason');
    [$invalidLicense,,$invalidFile]=$owner(81);$invalidPath=APP_ROOT.'/storage/'.$invalidFile;$validBytes=file_get_contents($invalidPath);
    file_put_contents($invalidPath,'Not a PDF');$blocked(fn()=>$lic->review(81,1,$invalidLicense,'APPROVE'));file_put_contents($invalidPath,$validBytes);
    rename($invalidPath,$invalidPath.'.missing');try{$blocked(fn()=>$lic->review(81,1,$invalidLicense,'APPROVE'));}finally{rename($invalidPath.'.missing',$invalidPath);}
    $db->exec("UPDATE recycler_licenses SET expiry_date=CURRENT_DATE-INTERVAL 1 DAY WHERE license_id=$invalidLicense");$blocked(fn()=>$lic->review(81,1,$invalidLicense,'APPROVE'));$lic->review(81,1,$invalidLicense,'REJECT','Expired evidence');
    $check($db->query('SELECT review_note FROM recycler_licenses WHERE license_id='.$invalidLicense)->fetchColumn()==='Expired evidence','Initial licence rejection retains audit feedback');
    [$staleLicense,,$staleFile]=$owner(82);$lic->review(82,1,$staleLicense,'APPROVE');$lic->proposeScope(82,1,$proposal(82,$staleLicense,$staleFile));$staleScope=(int)$db->query('SELECT MAX(scope_id) FROM recycler_license_scopes')->fetchColumn();
    file_put_contents(APP_ROOT.'/storage/'.$staleFile,"%PDF-1.4\nDifferent document\n%%EOF\n");$blocked(fn()=>$lic->reviewScope(82,$staleScope,1,'VERIFIED','Old evidence',true));$lic->reviewScope(82,$staleScope,1,'REJECTED','Source changed',false);$blocked(fn()=>$cap->review(82,1,1,'APPROVE'));
    // Two independent reviewers serialize on the owner; exactly one decision wins.
    [$raceLicense,$raceActivity,$raceFile]=$owner(90);$lic->review(90,1,$raceLicense,'APPROVE');$lic->proposeScope(90,1,$proposal(90,$raceLicense,$raceFile));$raceScope=(int)$db->query('SELECT MAX(scope_id) FROM recycler_license_scopes')->fetchColumn();$lic->reviewScope(90,$raceScope,1,'VERIFIED','Inspected',true);$cap->review(90,1,1,'APPROVE');$act->review(90,$raceActivity,1,'APPROVE');
    $workers=[];$db->beginTransaction();$db->query('SELECT user_id FROM users WHERE user_id=90 FOR UPDATE');
    try {
        foreach([1,2] as $_){$pipes=[];$worker=proc_open([PHP_BINARY,'-d','session.save_path='.sys_get_temp_dir(),__FILE__,'--review-worker',$name,'90'],[['pipe','r'],['pipe','w'],['pipe','w']],$pipes);if(!is_resource($worker))throw new RuntimeException('Cannot start review worker');$workers[]=[$worker,$pipes];$check(trim((string)fgets($pipes[1]))==='READY','Concurrent reviewer ready');}
        $db->commit();$results=[];
        foreach($workers as [$worker,$pipes]){fclose($pipes[0]);$results[]=trim(stream_get_contents($pipes[1]));$errors=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$check(proc_close($worker)===0,'Concurrent reviewer exit: '.$errors);}
        $workers=[];sort($results);$check($results===['APPROVED','BLOCKED'],'Exactly one concurrent final decision succeeds');
    } finally {if($db->inTransaction())$db->rollBack();foreach($workers as [$worker,$pipes]){proc_terminate($worker);foreach($pipes as $pipe)if(is_resource($pipe))fclose($pipe);proc_close($worker);}}
    // Real registration OTP algorithm, without invoking the SMS transport.
    [$otpLicense]=$owner(60);$db->exec("UPDATE users SET account_status='PENDING',mobile_verified_at=NULL WHERE user_id=60");$otp=new OtpService($db);$challenge=$otp->issueRegistrationOtp(60);
    $db->beginTransaction();$result=$otp->verifyRegistrationOtp(60,$challenge['otp']);$activated=$result['success']&&(new User($db))->activateRecyclerAfterMobileVerification(60);$db->commit();
    $check($activated,'Existing OTP activation contract succeeds');
    $check($db->query('SELECT verification_status FROM authorized_recyclers WHERE user_id=60')->fetchColumn()==='PENDING'&&$db->query('SELECT license_status FROM recycler_licenses WHERE license_id='.$otpLicense)->fetchColumn()==='PENDING','OTP leaves registration/licence awaiting Admin');

    // Render and submit the actual forms through the application router.
    [$httpLicense,$httpActivity,$httpFile]=$owner(80);
    $socket=stream_socket_server('tcp://127.0.0.1:0',$errno,$error);$address=stream_socket_get_name($socket,false);fclose($socket);
    $sessionDir=sys_get_temp_dir().'/phase1-sessions-'.bin2hex(random_bytes(6));mkdir($sessionDir,0700);
    $env=getenv();$env['DB_DATABASE']=$name;$env['APP_BASE_PATH']='';$env['ECOLOT_ADMIN_REVIEW_TEST']='1';
    $server=proc_open([PHP_BINARY,'-d','session.save_path='.$sessionDir,'-S',$address,'-t',APP_ROOT.'/public',__FILE__],[0=>['file','/dev/null','r'],1=>['file',$sessionDir.'/server.log','a'],2=>['file',$sessionDir.'/server.log','a']],$unused,APP_ROOT,$env);
    $http=static function(string $path,array $data=[],string $actor='admin')use($address,$sessionDir):array{
        $args=['curl','-sS','--max-time','10','-D',$sessionDir.'/headers','-o',$sessionDir.'/body','-w','%{http_code}','-b',$sessionDir.'/'.$actor.'.cookies','-c',$sessionDir.'/'.$actor.'.cookies'];
        foreach($data as $key=>$value){$args[]='--data-urlencode';$args[]=$key.'='.$value;}$args[]='http://'.$address.$path;
        $p=proc_open($args,[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);$status=stream_get_contents($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);if(proc_close($p)!==0)throw new RuntimeException($error);
        return [(int)$status,file_get_contents($sessionDir.'/body'),file_get_contents($sessionDir.'/headers')];
    };
    $token=static function(string $html):string{if(!preg_match('/name="_csrf_token"\\s+value="([^"]+)"/',$html,$m))throw new RuntimeException('Missing token');return html_entity_decode($m[1],ENT_QUOTES,'UTF-8');};
    for($n=0;$n<40;$n++){if($p=@stream_socket_client('tcp://'.$address,$errno,$error,.1)){fclose($p);break;}usleep(50000);}
    [$status,$login]=$http('/login');$check($http('/login',['_csrf_token'=>$token($login),'mobile_number'=>'94779999001','password'=>$password])[0]===303,'Normal Admin login');
    $url='/admin/recycler-verification/80';[$status,$page]=$http($url);$check($status===200&&str_contains($page,'Licence scope and evidence'),'Actual details route renders');$csrf=$token($page);
    $check(str_contains($page,'permission-review-body'),'Permission review uses a full-width panel');
    $check(!preg_match('~<td\b[^>]*>(?:(?!</td>).)*<form\b~s',$page),'Decision forms stay outside table cells');
    $check(str_contains($page,'You can still reject this request.'),'Missing scope explains that rejection remains available');
    foreach(['review','license-review','capability-review','activity-review','scope','scope/1/review'] as $route)$check($http($url.'/'.$route,['decision'=>'APPROVE'])[0]===403,'CSRF required '.$route);
    $check($http($url.'/license-document')[0]===200,'Protected submitted PDF destination');
    $check($http($url.'/license-document',[],'guest')[0]===302,'Guest denied protected PDF');
    $post=static function(string $route,array $data)use($http,$csrf,$url,$check):void{$check($http($url.'/'.$route,['_csrf_token'=>$csrf]+$data)[0]===303,'Real form PRG '.$route);};
    $post('license-review',['license_id'=>$httpLicense,'decision'=>'APPROVE']);
    $post('capability-review',['category_id'=>1,'decision'=>'APPROVE']);$check($db->query('SELECT capability_status FROM recycler_capabilities WHERE recycler_user_id=80')->fetchColumn()==='PENDING','Direct unsupported permission POST fails closed');
    $post('scope',$proposal(80,$httpLicense,$httpFile));$httpScope=(int)$db->query('SELECT MAX(scope_id) FROM recycler_license_scopes')->fetchColumn();
    $check($http($url.'/scope/'.$httpScope.'/document')[0]===200,'Exact evidence destination works');$check($http('/admin/recycler-verification/11/scope/'.$httpScope.'/document')[0]===404,'Foreign evidence owner denied');
    $post('scope/'.$httpScope.'/review',['decision'=>'VERIFIED','review_note'=>'Exact submitted document inspected','evidence_confirmed'=>1]);
    $post('capability-review',['category_id'=>1,'decision'=>'APPROVE','review_note'=>'Supported']);$post('activity-review',['activity_id'=>$httpActivity,'decision'=>'APPROVE','review_note'=>'Supported']);$post('review',['decision'=>'APPROVE']);
    [$status,$verified]=$http($url);$check(str_contains($verified,'Registration decision')&&!str_contains($verified,'name="decision"'),'Verified page contains no initial mutation forms');
    $post('review',['decision'=>'APPROVE']);$check($db->query('SELECT verification_status FROM authorized_recyclers WHERE user_id=80')->fetchColumn()==='VERIFIED','Repeated HTTP decision preserves state');
    $check($http('/admin/recycler-verification?status[]=PENDING')[0]===200,'Malformed list filter safely handled');
    // Read-only Profile and PDF checks reuse this suite's disposable owners and HTTP server.
    $check($http('/recycler/profile')[0]===403,'Admin cannot access Recycler Profile');
    $check($http('/recycler/profile/licence-document')[0]===403,'Admin cannot use owner document route');
    $check($http('/recycler/profile',[],'profileguest')[0]===302,'Guest denied Profile');
    $check($http('/recycler/profile/licence-document',[],'profileguest')[0]===302,'Guest denied owner PDF');
    [, $ownerLogin]=$http('/login',[],'profileowner');
    $check($http('/login',['_csrf_token'=>$token($ownerLogin),'mobile_number'=>'9477'.str_pad('80',7,'0',STR_PAD_LEFT),'password'=>$password],'profileowner')[0]===303,'Recycler logs in normally');
    [$status,$ownerPage]=$http('/recycler/profile?recycler_id=10',[],'profileowner');
    $check($status===200&&str_contains($ownerPage,'Synthetic company 80')&&!str_contains($ownerPage,'Synthetic company 10'),'Profile ignores injected owner and shows authenticated company');
    $check(str_contains($ownerPage,'Verified coverage recorded')&&str_contains($ownerPage,'TEST-80'),'Profile displays current licence and independently verified scope');
    [$status,$ownerPdf,$pdfHeaders]=$http('/recycler/profile/licence-document?recycler_id=10',[],'profileowner');
    $check($status===200&&$ownerPdf===file_get_contents(APP_ROOT.'/storage/'.$httpFile),'PDF query cannot select another owner');
    $check(str_contains(strtolower($pdfHeaders),'cache-control: private, no-store')&&str_contains(strtolower($pdfHeaders),'x-content-type-options: nosniff'),'Owner PDF remains private and nosniff');
    $bytes=file_get_contents(APP_ROOT.'/storage/'.$httpFile);file_put_contents(APP_ROOT.'/storage/'.$httpFile,'invalid document');
    $check($http('/recycler/profile/licence-document',[],'profileowner')[0]===404,'Invalid PDF fails closed');
    file_put_contents(APP_ROOT.'/storage/'.$httpFile,"%PDF-1.4\nChanged valid PDF bytes\n%%EOF\n");
    [$status,$changedProfile]=$http('/recycler/profile',[],'profileowner');
    $check($status===200&&str_contains($changedProfile,'No current verified scope'),'Changed PDF cannot appear as verified current coverage');
    file_put_contents(APP_ROOT.'/storage/'.$httpFile,$bytes);
    $check(!$db->inTransaction(),'No partial transaction left open');
    $scopeCount=(int)$db->query('SELECT COUNT(*) FROM recycler_license_scopes')->fetchColumn();$db->exec(file_get_contents(APP_ROOT.'/database/migrations/20261116_recycler_initial_scope.sql'));
    $check((int)$db->query('SELECT COUNT(*) FROM recycler_license_scopes')->fetchColumn()===$scopeCount,'Migration reapplication preserves scope history');
    $db->exec('DROP TABLE recycler_license_scopes');
    [$status,$missingMigration]=$http('/admin/recycler-verification/60');
    $check($status===200&&str_contains($missingMigration,'Scope review is unavailable'),'Missing migration shows actionable blocked state rather than a broken page');
    $core=[];
    foreach(['users','authorized_recyclers','recycler_licenses','recycler_capabilities','recycler_authorized_activities'] as $table)$core[$table]=$db->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    $migration=file_get_contents(APP_ROOT.'/database/migrations/20261116_recycler_initial_scope.sql');
    $db->exec($migration);
    $check((int)$db->query('SELECT COUNT(*) FROM recycler_license_scopes')->fetchColumn()===0,'Migration on populated registrations grants no automatic scope');
    foreach($core as $table=>$rows)$check($db->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC)===$rows,'Migration preserves existing '.$table);
    $check(count($db->query("SELECT * FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='recycler_license_scopes'")->fetchAll())===4,'All four scope foreign keys exist');
    $check(count($db->query("SHOW INDEX FROM recycler_license_scopes WHERE Key_name='uq_scope_document'")->fetchAll())===6,'Document-bound active scope uniqueness exists');
    $columns=['license_id','category_id','activity_type','covers_high_risk','authorization_basis','waste_description','evidence_reference','licence_conditions','license_number','expiry_date','document_path','document_sha256','scope_status','proposed_by_admin_user_id','reviewed_by_admin_user_id','reviewed_at','review_note'];
    $values=[$httpLicense,1,'RECYCLING',0,'SWML','Test devices','Page 1','Recorded conditions','TEST','2099-01-01','test.pdf',str_repeat('a',64),'VERIFIED',1,1,'2026-11-16 10:00:00','Inspected'];
    $insert=$db->prepare('INSERT INTO recycler_license_scopes ('.implode(',',$columns).') VALUES ('.implode(',',array_fill(0,count($columns),'?')).')');
    $insert->execute($values);
    try{$insert->execute($values);$check(false,'Duplicate verified scope must fail');}catch(PDOException $e){$check((int)$e->errorInfo[1]===1062,'Database prevents duplicate verified scope');}
    foreach([0,1,13,14] as $foreignColumn){$invalid=$values;$invalid[$foreignColumn]=999999999;$invalid[11]=str_repeat('b',64);
        try{$insert->execute($invalid);$check(false,'Invalid foreign key must fail');}catch(PDOException $e){$check((int)$e->errorInfo[1]===1452,'Scope foreign key enforced '.$columns[$foreignColumn]);}}
    $invalid=$values;$invalid[14]=null;$invalid[11]=str_repeat('b',64);
    try{$insert->execute($invalid);$check(false,'Reviewed scope requires reviewer');}catch(PDOException $e){$check(in_array((int)$e->errorInfo[1],[4025,3819],true),'Reviewed decision requires reviewer identity');}
    $values[12]='REJECTED';$insert->execute($values);$insert->execute($values);
    $check((int)$db->query('SELECT COUNT(*) FROM recycler_license_scopes')->fetchColumn()===3,'Rejected decision history retained independently');
    $db->exec($migration);
    $check((int)$db->query('SELECT COUNT(*) FROM recycler_license_scopes')->fetchColumn()===3,'Repeated migration preserves populated scope decisions');
    $db->exec('ALTER TABLE recycler_license_scopes DROP INDEX uq_scope_document, DROP COLUMN active_scope');
    $db->exec($migration);
    $check((int)$db->query('SELECT COUNT(*) FROM recycler_license_scopes')->fetchColumn()===3,'Earlier Phase 1 table upgrades without losing decisions');
    $db->exec('ALTER TABLE recycler_license_scopes DROP INDEX uq_scope_document, DROP COLUMN active_scope');
    $values[12]='VERIFIED';$insert->execute($values);
    try{$db->exec($migration);$check(false,'Upgrade must refuse existing active duplicates');}catch(PDOException $e){$check((int)$e->errorInfo[1]===1062,'Earlier duplicate decisions cause safe migration failure');}
    $check((int)$db->query('SELECT COUNT(*) FROM recycler_license_scopes')->fetchColumn()===4,'Failed duplicate upgrade deletes no decision history');

    echo "PASS: $checks initial Admin review / scope / OTP / security / HTTP assertions\n";
} finally {
    if(is_resource($server)){proc_terminate($server);proc_close($server);}
    if(isset($sessionDir)&&is_dir($sessionDir)){foreach(glob($sessionDir.'/*') as $p)unlink($p);rmdir($sessionDir);}
    if($db->inTransaction())$db->rollBack();
    if($created){$db->exec("DROP DATABASE `$name`");echo "Removed disposable database $name\n";}
    foreach($files as $path)if(is_file($path))unlink($path);
}
