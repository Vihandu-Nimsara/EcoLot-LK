<?php
declare(strict_types=1);
// Explicit opt-in; all writes are confined to randomly named disposable databases.
if (getenv('ECOLOT_PROFILE_EDIT_TEST') !== '1') { http_response_code(404); exit(1); }
require __DIR__.'/support/bootstrap.php';
Session::start();
$config=require APP_ROOT.'/config/database.php';
if (PHP_SAPI === 'cli-server') {
    if (!preg_match('/^ecolot_test_profile_edit_[a-f0-9]{16}$/D', $config['database'])) exit(1);
    class ProfileTestController extends RecyclerController {
        protected function profileService(): RecyclerProfileService {
            return new RecyclerProfileService(null, static function(string $mobile,string $code):void {
                file_put_contents(getenv('ECOLOT_SMS_CAPTURE'), $code, LOCK_EX);
                chmod(getenv('ECOLOT_SMS_CAPTURE'), 0600);
            }, new LoginRateLimiter(getenv('ECOLOT_TEST_LIMITER')));
        }
    }
    $path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
    if (str_starts_with($path, '/assets/') && is_file(APP_ROOT.'/public'.$path)) return false;
    $prefix=rtrim((require APP_ROOT.'/config/app.php')['base_path'],'/');
    $map=['/profile'=>'profile','/profile/contact'=>'saveContact','/profile/phone/request'=>'requestPhone','/profile/phone/verify'=>'verifyPhone','/profile/phone/resend'=>'resendPhone','/profile/phone/cancel'=>'cancelPhone'];
    foreach($map as $route=>$method)if($path===$prefix.'/recycler'.$route) {
        if($_SERVER['REQUEST_METHOD']!==($route==='/profile'?'GET':'POST')){http_response_code(405);exit;}
        (new ProfileTestController())->$method();exit;
    }
    $_SERVER['SCRIPT_NAME']='/index.php'; require APP_ROOT.'/public/index.php';exit;
}
$db=new PDO(sprintf('mysql:host=%s;port=%s;charset=utf8mb4',$config['host'],$config['port']),$config['username'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
$db->exec("SET time_zone='+05:30'");
if (($argv[1]??'')==='--worker') {
    if(!preg_match('/^ecolot_test_profile_edit_[a-f0-9]{16}$/D',$config['database']))exit(1);
    $db->exec('USE `'.$config['database'].'`');Session::put('auth_user',['id'=>(int)$argv[2],'role'=>'RECYCLER']);
    echo "READY\n";flush();
    try {
        $s=new RecyclerProfileService($db,static function():void {},new LoginRateLimiter($argv[3]));
        $r=($argv[4]??'')==='verify'?$s->verify((int)$argv[2],'context',(string)file_get_contents($argv[5])):$s->beginPhone((int)$argv[2],$argv[5],'ProfileTest123!','context');
        echo $r['success']?"SUCCESS\n":"BLOCKED\n";
    }catch(Throwable $error){echo "BLOCKED\n";}
    exit;
}
$name='ecolot_test_profile_edit_'.bin2hex(random_bytes(8));$checks=0;$files=[];
$check=static function(bool $ok,string $why)use(&$checks):void{if(!$ok)throw new RuntimeException($why);$checks++;};
$q=static function(string $sql,array $args=[])use($db):PDOStatement{$s=$db->prepare($sql);$s->execute($args);return $s;};
$login=static function(int $id):void{Session::put('auth_user',['id'=>$id,'role'=>'RECYCLER','name'=>'Test contact']);};
$hash=password_hash('ProfileTest123!',PASSWORD_DEFAULT);
try {
    $db->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4");$db->exec("USE `$name`");TestDatabase::loadSchema($db);
    $migration=file_get_contents(APP_ROOT.'/database/migrations/20261119_recycler_contact_phone.sql');$db->exec($migration);$db->exec($migration);
    $check((int)$q("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='recycler_phone_change_challenges' AND INDEX_NAME='uq_phone_change_active_target'")->fetchColumn()===1,'Fresh migration and repeat application preserve target uniqueness');
    $q("INSERT INTO users(user_id,full_name,mobile_number,password_hash,role,account_status,mobile_verified_at) VALUES(1,'Admin','94779999999',?,'ADMIN','ACTIVE',CURRENT_TIMESTAMP)",[$hash]);$q('INSERT INTO administrators VALUES(1)');
    $owner=static function(int $id)use($q,$hash):void{
        $q("INSERT INTO users(user_id,full_name,mobile_number,password_hash,role,account_status,mobile_verified_at,email) VALUES(?,'Test contact',?,?,'RECYCLER','ACTIVE',CURRENT_TIMESTAMP,?)",[$id,'9477'.str_pad((string)$id,7,'0',STR_PAD_LEFT),$hash,'owner'.$id.'@example.test']);
        $q("INSERT INTO authorized_recyclers(user_id,company_name,business_address,district,verification_status,reviewed_by_admin_user_id,reviewed_at) VALUES(?,CONCAT('Test Recycling ',?),'Test address','Colombo','VERIFIED',1,CURRENT_TIMESTAMP)",[$id,$id]);
    };
    $make=static function(?Closure $send=null)use($db,&$files):RecyclerProfileService{
        $path=tempnam('/tmp','ecolot-phone-limit-');chmod($path,0600);$files[]=$path;
        return new RecyclerProfileService($db,$send??static function():void{},new LoginRateLimiter($path));
    };
    $last='';$sent=0;$fake=static function(string $number,string $code)use(&$last,&$sent):void{$last=$code;$sent++;};
    $owner(10);$owner(11);$login(10);$s=$make($fake);
    $q("INSERT INTO waste_categories(category_id,category_name) VALUES(1,'Synthetic category')");
    $q("INSERT INTO recycler_licenses(recycler_user_id,license_number,expiry_date,document_path) VALUES(10,'PHONE-PRESERVATION',CURRENT_DATE+INTERVAL 1 YEAR,'uploads/recycler-licenses/synthetic-not-served.pdf')");$license=(int)$db->lastInsertId();
    $q('INSERT INTO recycler_capabilities(recycler_user_id,category_id) VALUES(10,1)');
    $q("INSERT INTO recycler_authorized_activities(recycler_user_id,activity_type) VALUES(10,'RECYCLING')");
    $q("INSERT INTO recycler_license_scopes(license_id,category_id,activity_type,authorization_basis,waste_description,evidence_reference,licence_conditions,license_number,expiry_date,document_path,document_sha256,proposed_by_admin_user_id) SELECT license_id,1,'RECYCLING','SWML','Synthetic evidence','Page 1','Synthetic conditions',license_number,expiry_date,document_path,?,1 FROM recycler_licenses WHERE license_id=?",[str_repeat('a',64),$license]);
    $protected=static function()use($q):string{$data=[];foreach(['authorized_recyclers','recycler_licenses','recycler_license_scopes','recycler_capabilities','recycler_authorized_activities','recycler_bids'] as $table)$data[$table]=$q('SELECT * FROM '.$table)->fetchAll();return serialize($data);};
    $before=$protected();$userBefore=$q('SELECT * FROM users WHERE user_id=10')->fetch();
    foreach(['','A','<script>','12',"Bad\nName",str_repeat('x',121)] as $bad)$check(!$s->contact(10,$bad)['success'],'Invalid contact rejected');
    $check($s->contact(10,'Nadeesha Perera')['success'],'Owner contact update');$check($before===$protected(),'Contact update preserves populated compliance records');
    $check($q('SELECT full_name FROM users WHERE user_id=10')->fetchColumn()==='Nadeesha Perera','Only owner contact saved');
    $count=$q('SELECT COUNT(*) FROM audit_logs')->fetchColumn();$check($s->contact(10,'Nadeesha Perera')['message']==='No changes to save.','No-op feedback');$check($count===$q('SELECT COUNT(*) FROM audit_logs')->fetchColumn(),'No-op creates no decision');
    try{$s->contact(11,'Foreign name');$check(false,'Foreign account denied');}catch(DomainException $e){$check(true,'Foreign account denied');}
    Session::put('auth_user',['id'=>10,'role'=>'ADMIN']);try{$s->contact(10,'Admin name');$check(false,'Role denied');}catch(DomainException $e){$check(true,'Role denied');}$login(10);
    foreach(['SUSPENDED','DISABLED'] as $status){$q('UPDATE users SET account_status=? WHERE user_id=10',[$status]);$check(!$s->contact(10,'Denied name')['success'],'Ineligible status denied');}$q("UPDATE users SET account_status='ACTIVE' WHERE user_id=10");
    $q("UPDATE authorized_recyclers SET verification_status='PENDING',reviewed_by_admin_user_id=NULL,reviewed_at=NULL WHERE user_id=10");$check(!$s->contact(10,'Denied name')['success'],'Pending registration denied');$q("UPDATE authorized_recyclers SET verification_status='VERIFIED',reviewed_by_admin_user_id=1,reviewed_at=CURRENT_TIMESTAMP WHERE user_id=10");
    $before=$protected();
    $check(!$s->beginPhone(10,'0771234567','wrong','context')['success'],'Password mandatory');
    $check(!$s->beginPhone(10,'bad','ProfileTest123!','context')['success'],'Invalid mobile');
    $check(!$s->beginPhone(10,$userBefore['mobile_number'],'ProfileTest123!','context')['success'],'Unchanged mobile');
    $check(!$s->beginPhone(10,'94770000011','ProfileTest123!','context')['success'],'Duplicate mobile');
    $r=$s->beginPhone(10,'0771234567','ProfileTest123!','context');$check($r['success']&&$sent===1,'Password checked and fake SMS delivered');
    $check(!str_contains(json_encode($r),$last),'Code not returned');$check($q('SELECT mobile_number FROM users WHERE user_id=10')->fetchColumn()===$userBefore['mobile_number'],'Old number remains active');
    $check(!$s->beginPhone(10,'0771234568','ProfileTest123!','context')['success'],'Duplicate pending owner blocked');
    $check(!$s->verify(10,'foreign-session',$last)['success'],'Cross-session verification denied');
    $check(!$s->verify(10,'context','000000')['success'],'Invalid OTP');
    $check(!$s->resend(10,'context')['success'],'Resend cooldown');
    $check($s->verify(10,'context',$last)['success'],'Valid OTP consumed');$check(!$s->verify(10,'context',$last)['success'],'OTP replay denied');
    $check($q('SELECT mobile_number FROM users WHERE user_id=10')->fetchColumn()==='94771234567','Canonical mobile saved');
    $check($before===$protected(),'Licence, scope, registration, capabilities, activities and bids unchanged');
    $after=$q('SELECT * FROM users WHERE user_id=10')->fetch();foreach(['email','password_hash','role','account_status'] as $key)$check($userBefore[$key]===$after[$key],'Protected identity preserved: '.$key);
    // Independent owners avoid arbitrary clock injection and exercise persisted limits.
    foreach(range(20,34) as $id)$owner($id);
    $start=static function(int $id,?Closure $sender=null)use($login,$make,$fake):RecyclerProfileService{$login($id);$s=$make($sender??$fake);$r=$s->beginPhone($id,'9478'.str_pad((string)$id,7,'0',STR_PAD_LEFT),'ProfileTest123!','context');if(!$r['success'])throw new RuntimeException($r['message']);return $s;};
    $s=$start(20);for($i=0;$i<5;$i++)$check(!$s->verify(20,'context','000000')['success'],'Invalid attempt persists');$check(!$s->verify(20,'context',$last)['success'],'Exhausted OTP denied');
    $s=$start(21);$q('UPDATE recycler_phone_change_challenges SET created_at=CURRENT_TIMESTAMP-INTERVAL 10 MINUTE,sent_at=CURRENT_TIMESTAMP-INTERVAL 10 MINUTE,expires_at=CURRENT_TIMESTAMP-INTERVAL 5 MINUTE WHERE recycler_user_id=21');$check(!$s->verify(21,'context',$last)['success'],'Expired OTP denied');
    $s=$start(22);$q("UPDATE users SET account_status='SUSPENDED' WHERE user_id=22");$check(!$s->verify(22,'context',$last)['success'],'Status change during verification denied');$check(!$q('SELECT active_recycler_user_id FROM recycler_phone_change_challenges WHERE recycler_user_id=22')->fetchColumn(),'Status change invalidates challenge');
    $s=$start(23);$q('UPDATE users SET password_hash=? WHERE user_id=23',[password_hash('Changed123!',PASSWORD_DEFAULT)]);$check(!$s->verify(23,'context',$last)['success'],'Password change invalidates challenge');
    $s=$start(24);$q("UPDATE users SET mobile_number='94773333333' WHERE user_id=24");$check(!$s->verify(24,'context',$last)['success'],'Original mobile binding');
    $s=$start(25);$old=$last;$s->verify(25,'context','000000');$q('UPDATE recycler_phone_change_challenges SET created_at=CURRENT_TIMESTAMP-INTERVAL 61 SECOND,sent_at=CURRENT_TIMESTAMP-INTERVAL 61 SECOND WHERE recycler_user_id=25');$check($s->resend(25,'context')['success'],'Resend after cooldown');$check(!$s->verify(25,'context',$old)['success'],'Resent old code invalid');$check((int)$q('SELECT attempt_count FROM recycler_phone_change_challenges WHERE active_recycler_user_id=25')->fetchColumn()===2,'Resend preserves attempt budget');$check($s->verify(25,'context',$last)['success'],'Resent code succeeds');
    $login(26);$s=$make(static function():void{throw new RuntimeException('SECRET');});$check(!$s->beginPhone(26,'0774444426','ProfileTest123!','context')['success'],'SMS failure handled');$check($q('SELECT delivery_status FROM recycler_phone_change_challenges WHERE recycler_user_id=26')->fetchColumn()==='FAILED','Failed SMS releases reservation');
    $s=$start(27);$check($s->cancel(27,'context')['success'],'Cancel pending');$check(!$s->verify(27,'context',$last)['success'],'Cancelled code denied');
    $s=$start(28);$q("UPDATE authorized_recyclers SET verification_status='SUSPENDED',review_note='Test suspension' WHERE user_id=28");$check(!$s->verify(28,'context',$last)['success'],'Registration status rechecked');
    $s=$start(29);$code=$last;$q("UPDATE users SET mobile_number='94780000029' WHERE user_id=30");$check(!$s->verify(29,'context',$code)['success'],'Target taken after issue fails closed');
    $s=$start(31);$login(32);try{$r=$make()->beginPhone(32,'94780000031','ProfileTest123!','context');$check(!$r['success'],'Active target reserved');}catch(PDOException $e){$check((int)$e->errorInfo[1]===1062,'Database enforces active target reservation');}
    $login(33);$s=$make();for($i=0;$i<10;$i++)$s->beginPhone(33,'0775555533','wrong','context');$check(str_contains($s->beginPhone(33,'0775555533','ProfileTest123!','context')['message'],'Too many'),'Password reauthentication limited');
    // Adopt a compatible legacy table without destroying a pending or consumed challenge.
    $rows=(int)$q('SELECT COUNT(*) FROM recycler_phone_change_challenges')->fetchColumn();
    $db->exec('ALTER TABLE recycler_phone_change_challenges DROP INDEX uq_phone_change_active_target, DROP COLUMN active_proposed_mobile_number');
    $login(31);$check(!$make()->state(31,'context')['phone_available'],'Legacy table without reservation index disables phone editing');
    try{$make()->verify(31,'context','000000');$check(false,'Missing reservation index blocks mutation');}catch(DomainException $e){$check(true,'Missing reservation index blocks mutation');}
    $login(32);$q("INSERT INTO recycler_phone_change_challenges(recycler_user_id,original_mobile_number,proposed_mobile_number,otp_hash,session_context_hash,password_state_fingerprint,expires_at) SELECT 32,'94770000032',proposed_mobile_number,otp_hash,session_context_hash,password_state_fingerprint,expires_at FROM recycler_phone_change_challenges WHERE active_recycler_user_id=31");
    try{$db->exec($migration);$check(false,'Conflicting legacy target must fail migration');}catch(PDOException $e){$check((int)$e->errorInfo[1]===1062,'Conflicting legacy target fails migration without deleting records');}
    $q('UPDATE recycler_phone_change_challenges SET invalidated_at=CURRENT_TIMESTAMP WHERE active_recycler_user_id=32');$db->exec($migration);$rows++;

    $check((int)$q('SELECT COUNT(*) FROM recycler_phone_change_challenges')->fetchColumn()===$rows,'Legacy upgrade preserves challenge history');
    // Real concurrent workers wait on the same owner lock; only one consumes a challenge.
    $s=$start(34);$codeFile=tempnam('/tmp','ecolot-private-code-');chmod($codeFile,0600);file_put_contents($codeFile,$last);$files[]=$codeFile;
    $env=getenv();$env['DB_DATABASE']=$name;$workers=[];$db->beginTransaction();$q('SELECT user_id FROM users WHERE user_id=34 FOR UPDATE');
    for($i=0;$i<2;$i++){$path=tempnam('/tmp','ecolot-worker-limit-');$files[]=$path;$p=proc_open([PHP_BINARY,'-d','session.save_path=/tmp',__FILE__,'--worker','34',$path,'verify',$codeFile],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,$env);$check(trim(fgets($pipes[1]))==='READY','Concurrent worker ready');$workers[]=[$p,$pipes];}
    $db->commit();$results=[];foreach($workers as [$p,$pipes]){$results[]=trim(stream_get_contents($pipes[1]));$stderr=stream_get_contents($pipes[2]);$check(proc_close($p)===0&&$stderr==='','Concurrent worker clean');}$check(count(array_filter($results,fn($r)=>$r==='SUCCESS'))===1,'Exactly one concurrent verification succeeds');
    foreach(range(40,45) as $id)$owner($id);
    // Pending network delivery must prevent a second request without holding a database lock.
    $login(40);$duplicateBlocked=false;
    $network=$make(function()use($make,&$duplicateBlocked):void{$duplicateBlocked=!$make()->beginPhone(40,'94780000040','ProfileTest123!','context')['success'];});
    $check($network->beginPhone(40,'94780000040','ProfileTest123!','context')['success']&&$duplicateBlocked,'Concurrent request during SMS delivery blocked');
    $login(41);$network=$make(function()use($q):void{$q("UPDATE users SET account_status='SUSPENDED' WHERE user_id=41");});
    $check(!$network->beginPhone(41,'94780000041','ProfileTest123!','context')['success'],'Eligibility rechecked after network delivery');
    $s=$start(42);for($i=0;$i<4;$i++){$q('UPDATE recycler_phone_change_challenges SET created_at=created_at-INTERVAL 61 SECOND,sent_at=sent_at-INTERVAL 61 SECOND WHERE active_recycler_user_id=42');$check($s->resend(42,'context')['success'],'Allowed resend within hourly budget');}
    $q('UPDATE recycler_phone_change_challenges SET created_at=created_at-INTERVAL 61 SECOND,sent_at=sent_at-INTERVAL 61 SECOND WHERE active_recycler_user_id=42');$check(!$s->resend(42,'context')['success'],'Five per hour enforced');
    $s=$start(43);for($i=0;$i<9;$i++){$q('UPDATE recycler_phone_change_challenges SET created_at=created_at-INTERVAL 61 SECOND,sent_at=sent_at-INTERVAL 61 SECOND WHERE active_recycler_user_id=43');$check($s->resend(43,'context')['success'],'Allowed resend within daily budget');$q('UPDATE recycler_phone_change_challenges SET created_at=CURRENT_TIMESTAMP-INTERVAL 2 HOUR,sent_at=CURRENT_TIMESTAMP-INTERVAL 2 HOUR,expires_at=CURRENT_TIMESTAMP-INTERVAL 1 HOUR,invalidated_at=CURRENT_TIMESTAMP-INTERVAL 1 HOUR WHERE recycler_user_id=43 AND invalidated_at IS NOT NULL');}
    $q('UPDATE recycler_phone_change_challenges SET created_at=created_at-INTERVAL 61 SECOND,sent_at=sent_at-INTERVAL 61 SECOND WHERE active_recycler_user_id=43');$check(!$s->resend(43,'context')['success'],'Ten per day enforced');
    $login(44);$q('UPDATE users SET mobile_verified_at=NULL WHERE user_id=44');$check(!$make()->contact(44,'Denied contact')['success'],'Unverified mobile cannot edit');
    // Simultaneous start attempts serialize at the authoritative owner mutation lock.
    $env['DB_DATABASE']=$name;$workers=[];$db->beginTransaction();$q('SELECT user_id FROM users WHERE user_id=45 FOR UPDATE');
    for($i=0;$i<2;$i++){$path=tempnam('/tmp','ecolot-start-limit-');$files[]=$path;$p=proc_open([PHP_BINARY,'-d','session.save_path=/tmp',__FILE__,'--worker','45',$path,'begin','94780000045'],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,$env);$check(trim(fgets($pipes[1]))==='READY','Start worker ready');$workers[]=[$p,$pipes];}
    $db->commit();$results=[];foreach($workers as [$p,$pipes]){$results[]=trim(stream_get_contents($pipes[1]));$stderr=stream_get_contents($pipes[2]);$check(proc_close($p)===0&&$stderr==='','Start worker clean');}$check(count(array_filter($results,fn($r)=>$r==='SUCCESS'))===1,'Exactly one simultaneous phone-change request succeeds');
    echo "PASS: $checks contact/phone security, migration and concurrency assertions; fake SMS only\n";
    if (getenv('ECOLOT_KEEP_PROFILE_TEST')==='1') {file_put_contents('/tmp/ecolot-contact-phone/database.txt',$name);$name='';}
} finally {
    if($db->inTransaction())$db->rollBack();if($name!=='')$db->exec("DROP DATABASE IF EXISTS `$name`");foreach($files as $file)if(is_file($file))unlink($file);
}
