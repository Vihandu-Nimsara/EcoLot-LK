<?php
declare(strict_types=1);
// ECOLOT_COVERAGE_TEST=1 php -d session.save_path=/tmp tests/recycler-licence-coverage.php
require __DIR__ . '/support/bootstrap.php';
require __DIR__ . '/support/LicenceCoverageFixture.php';
if (getenv('ECOLOT_COVERAGE_TEST') !== '1') {
    fwrite(STDERR,"Explicit ECOLOT_COVERAGE_TEST=1 required; disposable databases only.\n"); exit(1);
}
Session::start();
$config=require APP_ROOT.'/config/database.php';
$connect=static function (?string $name=null) use ($config): PDO {
    if ($name!==null && !preg_match('/\Aecolot_test_coverage_[a-f0-9]{16}\z/',$name)) throw new RuntimeException('Unsafe database');
    $db=new PDO(sprintf('mysql:host=%s;port=%s;%scharset=utf8mb4',$config['host'],$config['port'],$name===null?'':'dbname='.$name.';'),$config['username'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
    $db->exec("SET time_zone='+05:30'"); return $db;
};
if (($argv[1]??'')==='--bid-worker') {
    $db=$connect($argv[2]??''); Session::put('auth_user',['id'=>10,'role'=>'RECYCLER']);
    echo "READY\n"; flush();
    try { (new RecyclerBidService($db))->placeBid(10,(int)$argv[3],'500'); echo "ACCEPTED\n"; }
    catch (DomainException) { echo "BLOCKED\n"; }
    exit;
}
$checks=0;
$check=static function (bool $ok,string $message) use (&$checks): void { if (!$ok) throw new RuntimeException($message); ++$checks; };
$reject=static function (callable $action,string $type=DomainException::class) use ($check): void {
    try { $action(); } catch (Throwable $e) { $check($e instanceof $type,'Unexpected '.$e::class.': '.$e->getMessage()); return; }
    throw new RuntimeException('Expected rejection');
};
$admin=static fn()=>Session::put('auth_user',['id'=>1,'role'=>'ADMIN']);
$recycler=static fn()=>Session::put('auth_user',['id'=>10,'role'=>'RECYCLER']);
$db=$connect(); $name='ecolot_test_coverage_'.bin2hex(random_bytes(8)); $created=false; $worker=null;
final class CoverageRedirect extends RuntimeException {}
final class CoverageAdminController extends AdminController {
    protected function redirect(string $path,int $status=303): never { throw new CoverageRedirect($path); }
}
try {
    $db->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"); $created=true;
    $db->exec("USE `$name`"); TestDatabase::loadSchema($db);
    // Simulate upgrading the previous schema, then execute ONLY the new migration.
    foreach (['trg_recycler_bid_coverage_bi','trg_recycler_bid_coverage_bu','trg_recycler_license_document_version_bu'] as $trigger) $db->exec('DROP TRIGGER '.$trigger);
    foreach (['e_lot_required_activities','e_lot_authorization_reviews','e_lot_authorization_requirements','recycler_license_coverage_reviews','recycler_license_coverage','recycler_license_evidence'] as $table) $db->exec('DROP TABLE '.$table);
    $db->exec('ALTER TABLE recycler_licenses DROP INDEX uq_license_owner_identity, DROP COLUMN document_version');
    $db->exec("INSERT INTO users (user_id,full_name,mobile_number,password_hash,role,account_status) VALUES (80,'Legacy Admin','94779999980','unused','ADMIN','ACTIVE'),(81,'Legacy Recycler','94779999981','unused','RECYCLER','ACTIVE')");
    $db->exec('INSERT INTO administrators VALUES (80)');
    $db->exec("INSERT INTO authorized_recyclers (user_id,company_name,business_address,district,verification_status,reviewed_by_admin_user_id,reviewed_at) VALUES (81,'Legacy','Address','Colombo','VERIFIED',80,CURRENT_TIMESTAMP)");
    $db->exec("INSERT INTO waste_categories (category_id,category_name) VALUES (99,'Legacy category')");
    $db->exec("INSERT INTO recycler_capabilities (recycler_user_id,category_id,capability_status,reviewed_by_admin_user_id,reviewed_at) VALUES (81,99,'APPROVED',80,CURRENT_TIMESTAMP)");
    $db->prepare("INSERT INTO recycler_licenses (recycler_user_id,license_number,expiry_date,document_path,license_status) VALUES (81,'LEGACY',CURRENT_DATE+INTERVAL 1 YEAR,?,'VALID')")->execute([LicenceCoverageFixture::pdf()]);
    $legacyCapability=$db->query('SELECT * FROM recycler_capabilities WHERE recycler_user_id=81')->fetch();
    $migration=file_get_contents(APP_ROOT.'/database/migrations/20261110_recycler_licence_coverage.sql');
    [$ddl,$triggers]=explode('DELIMITER $$',$migration,2);
    foreach (explode(';',$ddl) as $sql) if (trim($sql)!=='') $db->exec($sql);
    foreach (explode('$$',str_replace('DELIMITER ;','',$triggers)) as $sql) if (trim($sql)!=='') $db->exec($sql);
    $check((int)$db->query('SELECT COUNT(*) FROM recycler_license_coverage')->fetchColumn()===0,'No legacy auto-verification');
    $check($db->query('SELECT * FROM recycler_capabilities WHERE recycler_user_id=81')->fetch()===$legacyCapability,'Incremental migration preserves real-shaped legacy capability data');
    $check((int)$db->query('SELECT COUNT(*) FROM recycler_license_evidence WHERE recycler_user_id=81')->fetchColumn()===0,'Legacy licence is not fabricated as reviewed evidence');
    $check((int)$db->query("SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME LIKE '%coverage%'")->fetchColumn()===2,'New migration triggers compatible');
    $password=password_hash('SyntheticPassword123!',PASSWORD_DEFAULT);
    $s=$db->prepare("INSERT INTO users (user_id,full_name,mobile_number,password_hash,role,mobile_verified_at,account_status) VALUES (?,?,?,?,?,CURRENT_TIMESTAMP,'ACTIVE')");
    foreach ([1=>'ADMIN',2=>'ADMIN',10=>'RECYCLER',11=>'RECYCLER',12=>'RECYCLER',20=>'COLLECTOR',21=>'MUNICIPAL_OFFICER',22=>'PUBLIC_USER'] as $id=>$role) $s->execute([$id,'Test '.$id,'94771'.str_pad((string)$id,6,'0',STR_PAD_LEFT),$password,$role]);
    $db->exec('INSERT INTO administrators VALUES (1),(2)');
    $db->exec("INSERT INTO authorized_recyclers (user_id,company_name,business_address,district,verification_status,reviewed_by_admin_user_id,reviewed_at) VALUES (10,'Test','Address','Colombo','VERIFIED',1,CURRENT_TIMESTAMP),(11,'Other','Address','Colombo','VERIFIED',1,CURRENT_TIMESTAMP),(12,'Initial','Address','Colombo','PENDING',NULL,NULL)");
    $db->exec("INSERT INTO waste_categories (category_id,category_name,category_status) VALUES (1,'Automobile E-Waste','ACTIVE'),(2,'Domestic E-Waste','ACTIVE'),(3,'Inactive','INACTIVE')");
    $db->prepare("INSERT INTO recycler_licenses (recycler_user_id,license_number,expiry_date,document_path,license_status) VALUES (10,'COVERAGE-TEST',CURRENT_DATE+INTERVAL 1 YEAR,?,'VALID')")->execute([LicenceCoverageFixture::pdf()]);
    $service=new RecyclerLicenceCoverageService($db); $auth=new RecyclerLicenceAuthorization($db); $model=new RecyclerLicenceCoverage($db);
    $license=$auth->currentLicense(10); $licenseId=(int)$license['license_id'];
    $input=static function (int $category=1,string $activity='RECYCLING',int $risk=0) use ($auth): array {
        $lic=$auth->currentLicense(10);
        return ['license_id'=>$lic['license_id'],'document_version'=>$lic['document_version'],'category_id'=>$category,'activity_type'=>$activity,'covers_high_risk'=>$risk,'authorization_basis'=>'SWML','waste_description'=>'Synthetic description <escape>','document_waste_code'=>'TEST-ONLY','evidence_reference'=>'Synthetic PDF page 1','licence_conditions'=>'Test conditions'];
    };
    $admin(); $reject(fn()=>$auth->requireCurrentEvidence(10));
    $reject(fn()=>$service->propose(10,1,$input(3))); $reject(fn()=>$service->propose(10,1,$input(1,'SORTING')));
    $reject(fn()=>$service->propose(10,1,array_replace($input(1,'RECYCLING',1),['authorization_basis'=>'EPL'])));
    $reject(fn()=>$service->propose(10,1,array_replace($input(),['evidence_reference'=>''])));
    $recycler(); $reject(fn()=>$service->propose(10,1,$input()));
    $admin(); $reject(fn()=>$service->propose(10,2,$input()));
    $db->exec("UPDATE users SET account_status='SUSPENDED' WHERE user_id=1"); $reject(fn()=>$service->propose(10,1,$input()));
    $db->exec("UPDATE users SET account_status='ACTIVE' WHERE user_id=1");
    $coverage=$service->propose(10,1,$input());
    $reject(fn()=>$service->propose(10,1,$input()));
    $db->exec("INSERT INTO recycler_authorized_activities (recycler_user_id,activity_type,activity_status,reviewed_by_admin_user_id,reviewed_at) VALUES (10,'RECYCLING','APPROVED',1,CURRENT_TIMESTAMP)");
    $reject(fn()=>$auth->requireCapability(10,1,0));
    $reject(fn()=>$service->review(11,$coverage,1,'VERIFIED','Wrong owner'));
    $service->review(10,$coverage,1,'UNCERTAIN','Insufficient evidence');
    $reject(fn()=>$auth->requireCapability(10,1,0));
    $coverage=$service->propose(10,1,$input()); $service->review(10,$coverage,1,'VERIFIED','Simulated evidence checked');
    $auth->requireCapability(10,1,0); $check(true,'Verified category supported');
    $reject(fn()=>$auth->requireCapability(10,2,0)); $reject(fn()=>$auth->requireCapability(10,1,1));
    $reject(fn()=>$service->review(10,$coverage,1,'VERIFIED','Replay'));
    $replacement=$service->propose(10,1,$input(1,'RECYCLING',1));
    $service->review(10,$replacement,1,'VERIFIED','Explicit high-risk evidence');
    $check($db->query("SELECT superseded_at FROM recycler_license_coverage WHERE coverage_id=$coverage")->fetchColumn()!==null,'Superseded scope preserved');
    $auth->requireCapability(10,1,1); $check(true,'High-risk expansion explicitly verified');
    $pending=$service->propose(10,1,$input(2));
    $db->exec("UPDATE waste_categories SET category_status='INACTIVE' WHERE category_id=2");
    $reject(fn()=>$service->review(10,$pending,1,'VERIFIED','Inactive category'));
    $db->exec("UPDATE waste_categories SET category_status='ACTIVE' WHERE category_id=2");
    $service->review(10,$pending,1,'REJECTED','Not supported');
    $auth->requireCapability(10,1,1); $check(true,'Rejected unrelated proposal retains current authorization');

    // Initial approvals enforce scope before changing any requested permission.
    $db->prepare("INSERT INTO recycler_licenses (recycler_user_id,license_number,expiry_date,document_path,license_status) VALUES (12,'INITIAL',CURRENT_DATE+INTERVAL 1 YEAR,?,'VALID')")->execute([LicenceCoverageFixture::pdf()]);
    $db->exec("INSERT INTO recycler_capabilities (recycler_user_id,category_id,can_handle_high_risk) VALUES (12,1,0),(12,2,0)");
    $db->exec("INSERT INTO recycler_authorized_activities (recycler_user_id,activity_type) VALUES (12,'RECYCLING'),(12,'STORAGE')");
    $activity=(int)$db->query("SELECT activity_id FROM recycler_authorized_activities WHERE recycler_user_id=12 AND activity_type='RECYCLING'")->fetchColumn();
    $initial=new RecyclerCapabilityReviewService($db); $activities=new RecyclerAuthorizedActivityReviewService($db);
    $reject(fn()=>$initial->review(12,1,1,'APPROVE')); $reject(fn()=>$activities->review(12,$activity,1,'APPROVE'));
    LicenceCoverageFixture::scope($db,12,1,1,'RECYCLING',0,false);
    $initial->review(12,1,1,'APPROVE'); $activities->review(12,$activity,1,'APPROVE');
    $reject(fn()=>$initial->review(12,2,1,'APPROVE'));
    $reject(fn()=>(new RecyclerApprovalService($db))->review(12,1,'APPROVE'),RuntimeException::class);
    $initial->review(12,2,1,'REJECT','Not covered');
    $storage=(int)$db->query("SELECT activity_id FROM recycler_authorized_activities WHERE recycler_user_id=12 AND activity_type='STORAGE'")->fetchColumn();
    $reject(fn()=>$activities->review(12,$storage,1,'APPROVE'));
    $activities->review(12,$storage,1,'REJECT','Not covered');
    (new RecyclerApprovalService($db))->review(12,1,'APPROVE');
    $check($db->query('SELECT verification_status FROM authorized_recyclers WHERE user_id=12')->fetchColumn()==='VERIFIED','Final scope-aware approval');

    // Build real collection provenance, not a detached synthetic lot.
    $db->exec('INSERT INTO collectors VALUES (20)'); $db->exec('INSERT INTO municipal_officers VALUES (21)');
    $insert=static function (string $sql,array $params=[]) use ($db): int { $s=$db->prepare($sql); $s->execute($params); return (int)$db->lastInsertId(); };
    $area=$insert("INSERT INTO postal_code_areas (postal_code,area_name) VALUES ('00100','Test area')");
    $insert("INSERT INTO public_profiles VALUES (22,?,'Test address')",[$area]);
    $campaign=$insert("INSERT INTO monthly_campaigns (created_by_officer_user_id,campaign_name,campaign_month) VALUES (21,'Test campaign',DATE_FORMAT(CURRENT_DATE,'%Y-%m-01'))");
    $schedule=$insert("INSERT INTO area_collection_schedules (campaign_id,postal_area_id,created_by_officer_user_id,collection_date,request_cutoff_at,request_capacity,schedule_status) VALUES (?,?,21,CURRENT_DATE + INTERVAL 3 DAY,CURRENT_TIMESTAMP + INTERVAL 1 DAY,10,'OPEN')",[$campaign,$area]);
    $insert('INSERT INTO schedule_assignments (schedule_id,collector_user_id,assigned_by_officer_user_id) VALUES (?,20,21)',[$schedule]);
    $pickup=$insert("INSERT INTO e_waste_requests (public_user_id,schedule_id,pickup_address) VALUES (22,?,'Test address')",[$schedule]);
    $item=$insert("INSERT INTO e_waste_items (category_id,item_name) VALUES (1,'Test item')");
    $collection=$insert("INSERT INTO schedule_collections (schedule_id,submitted_by_collector_user_id,verification_status,verified_by_officer_user_id,verified_at) VALUES (?,20,'VERIFIED',21,CURRENT_TIMESTAMP)",[$schedule]);
    $record=$insert("INSERT INTO collection_records (schedule_collection_id,request_id,pickup_result,pickup_attempted_at) VALUES (?,?,'COLLECTED',CURRENT_TIMESTAMP)",[$collection,$pickup]);
    $makeLot=static function (int $risk=0) use ($insert,$pickup,$item,$record): int {
        $level=$risk?'HIGH':'LOW';
        $ri=$insert("INSERT INTO request_items (request_id,waste_item_id,quantity,estimated_weight_kg,item_condition,applied_risk_level) VALUES (?,?,5,12.5,'DAMAGED',?)",[$pickup,$item,$level]);
        $cri=$insert("INSERT INTO collection_record_items (collection_record_id,request_item_id,actual_quantity,actual_weight_kg,actual_condition,actual_risk_level,item_result) VALUES (?,?,5,12.1,'DAMAGED',?,'COLLECTED')",[$record,$ri,$level]);
        $lot=$insert("INSERT INTO e_lots (lot_code,created_by_collector_user_id,category_id,title,lot_status,verified_by_officer_user_id,verified_at,bidding_open_at,bidding_close_at) VALUES (?,20,1,'Test lot','OPEN_FOR_BIDDING',21,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP-INTERVAL 1 DAY,CURRENT_TIMESTAMP+INTERVAL 7 DAY)",['SCOPE-'.bin2hex(random_bytes(6))]);
        $insert('INSERT INTO e_lot_items (record_item_id,e_lot_id) VALUES (?,?)',[$cri,$lot]); return $lot;
    };
    $db->exec("INSERT INTO recycler_capabilities (recycler_user_id,category_id,can_handle_high_risk,capability_status,reviewed_by_admin_user_id,reviewed_at) VALUES (10,1,1,'APPROVED',1,CURRENT_TIMESTAMP)");
    $changes=new RecyclerCapabilityChangeService($db); $changeReview=new RecyclerCapabilityChangeReviewService($db);
    $admin(); LicenceCoverageFixture::scope($db,10,1,1,'RECYCLING',0);
    $recycler(); $reduce=$changes->submit(10,1,'UPDATE',0,'Reduce risk to current evidence');
    $admin(); $changeReview->review(10,$reduce,1,'APPROVE');
    $recycler(); $expand=$changes->submit(10,1,'UPDATE',1,'Request increased risk permission');
    $admin(); $reject(fn()=>$changeReview->review(10,$expand,1,'APPROVE'));
    $check((int)$db->query('SELECT can_handle_high_risk FROM recycler_capabilities WHERE recycler_user_id=10 AND category_id=1')->fetchColumn()===0,'Unsupported risk expansion changes no permission');
    $replacement=LicenceCoverageFixture::scope($db,10,1,1,'RECYCLING',1);
    $changeReview->review(10,$expand,1,'APPROVE');
    $recycler(); $uncovered=$changes->submit(10,2,'ADD',0,'Request an uncovered category');
    $admin(); $reject(fn()=>$changeReview->review(10,$uncovered,1,'APPROVE'));
    $check((int)$db->query('SELECT COUNT(*) FROM recycler_capabilities WHERE recycler_user_id=10 AND category_id=2')->fetchColumn()===0,'Uncovered ADD does not create permission');
    $changeReview->review(10,$uncovered,1,'REJECT','Scope not established');
    $db->exec("INSERT INTO recycler_capabilities (recycler_user_id,category_id,can_handle_high_risk,capability_status,reviewed_by_admin_user_id,reviewed_at) VALUES (10,2,0,'INACTIVE',1,CURRENT_TIMESTAMP)");
    $recycler(); $uncovered=$changes->submit(10,2,'UPDATE',0,'Reactivate without current coverage');
    $admin(); $reject(fn()=>$changeReview->review(10,$uncovered,1,'APPROVE'));
    $check($db->query('SELECT capability_status FROM recycler_capabilities WHERE recycler_user_id=10 AND category_id=2')->fetchColumn()==='INACTIVE','Uncovered reactivation preserves inactive status');
    $changeReview->review(10,$uncovered,1,'REJECT','Current coverage required');
    $lot=$makeLot(); $lots=new ELot($db); $bids=new RecyclerBidService($db);
    $recycler(); $check(!$lots->isEligible($lot,10),'Unconfigured lot fails closed'); $reject(fn()=>$bids->placeBid(10,$lot,'500'));
    $admin(); $service->setLotActivities($lot,1,0,['STORAGE'],'Actual operations require storage');
    $recycler(); $check(!$lots->isEligible($lot,10),'Wrong activity fails closed');
    $admin(); $service->setLotActivities($lot,1,1,['RECYCLING'],'Recycling is the reviewed operation');
    $reject(fn()=>$service->setLotActivities($lot,1,1,['RECYCLING'],'Stale form'));
    $recycler(); $check($lots->isEligible($lot,10),'Verified required activity eligible');
    $bid=$bids->placeBid(10,$lot,'500'); $bids->updateBid(10,$bid,'600');
    $before=$db->query('SELECT * FROM recycler_bids')->fetchAll();
    $admin(); $service->review(10,$replacement,1,'REVOKED','Evidence no longer establishes scope');
    $recycler(); $check(!$lots->isEligible($lot,10),'Revoked coverage blocks eligibility'); $reject(fn()=>$bids->updateBid(10,$bid,'700'));
    $check($db->query('SELECT * FROM recycler_bids')->fetchAll()===$before,'Revocation preserves submitted bids');
    $check(count($model->conflicts(10))===1,'Conflict surfaced');
    $newLot=$makeLot(); $admin(); $service->setLotActivities($newLot,1,0,['RECYCLING'],'Test operation');
    $reject(fn()=>$db->prepare("INSERT INTO recycler_bids (e_lot_id,recycler_user_id,bid_amount) VALUES (?,10,500)")->execute([$newLot]),PDOException::class);
    $current=LicenceCoverageFixture::scope($db,10,1,1,'RECYCLING',0);
    $riskLot=$makeLot(1); $service->setLotActivities($riskLot,1,0,['RECYCLING'],'High-risk recycling');
    $recycler(); $check(!$lots->isEligible($riskLot,10),'High-risk coverage independently required');
    $admin(); $current=LicenceCoverageFixture::scope($db,10,1,1,'RECYCLING',1);
    $service->setLotActivities($riskLot,1,1,['RECYCLING','STORAGE'],'Every actual operation must be covered');
    $recycler(); $check(!$lots->isEligible($riskLot,10),'Every required activity checked');
    $admin(); LicenceCoverageFixture::scope($db,10,1,1,'STORAGE',1);
    $recycler(); $check($lots->isEligible($riskLot,10),'All required activities covered');
    $path=APP_ROOT.'/storage/'.$auth->currentLicense(10)['document_path']; $bytes=file_get_contents($path); file_put_contents($path,$bytes."\n% tampered");
    $check(!$lots->isEligible($newLot,10),'PDF hash mutation blocks UI'); $reject(fn()=>$bids->placeBid(10,$newLot,'500')); file_put_contents($path,$bytes);
    $check($lots->isEligible($newLot,10),'Original immutable bytes restore applicability');

    // Renewal of the SAME row must invalidate old scope, with immutable history.
    $admin(); $stale=$service->propose(10,1,$input(2));
    $version=(int)$auth->currentLicense(10)['document_version'];
    $db->prepare('UPDATE recycler_licenses SET document_path=? WHERE license_id=?')->execute([LicenceCoverageFixture::pdf(),$licenseId]);
    $check((int)$auth->currentLicense(10)['document_version']===$version+1,'Same-row document change increments version');
    $reject(fn()=>$service->review(10,$stale,1,'VERIFIED','Stale source'));
    $recycler(); $check(!$lots->isEligible($newLot,10),'Old coverage not inherited on renewal');
    $check(count($model->history(10))>=7,'Historical coverage retained');
    $admin(); $current=LicenceCoverageFixture::scope($db,10,1,1,'RECYCLING',0);
    $recycler(); $check(!$lots->isEligible($riskLot,10),'Reduced renewed scope cannot authorize HIGH risk');
    $db->exec("UPDATE recycler_licenses SET expiry_date=CURRENT_DATE-INTERVAL 1 DAY WHERE license_id=$licenseId");
    $check(!$lots->isEligible($newLot,10),'Expired licence fails closed');
    $db->exec("UPDATE recycler_licenses SET expiry_date=CURRENT_DATE+INTERVAL 1 YEAR WHERE license_id=$licenseId");
    $admin(); $current=LicenceCoverageFixture::scope($db,10,1,1,'RECYCLING',1);
    $db->exec("UPDATE recycler_licenses SET license_status='REVOKED' WHERE license_id=$licenseId");
    $recycler(); $check(!$lots->isEligible($newLot,10),'Revoked licence fails closed');
    $db->exec("UPDATE recycler_licenses SET license_status='VALID' WHERE license_id=$licenseId");
    $admin(); $current=LicenceCoverageFixture::scope($db,10,1,1,'RECYCLING',1);
    $db->exec("UPDATE recycler_licenses SET license_status='SUPERSEDED' WHERE license_id=$licenseId");
    $db->prepare("INSERT INTO recycler_licenses (recycler_user_id,license_number,expiry_date,document_path,license_status) VALUES (10,'NEW-NUMBER',CURRENT_DATE+INTERVAL 1 YEAR,?,'VALID')")->execute([LicenceCoverageFixture::pdf()]);
    $recycler(); $check(!$lots->isEligible($newLot,10),'Different-number replacement does not inherit scope');
    $admin(); $current=LicenceCoverageFixture::scope($db,10,1,1,'RECYCLING',1);
    $db->exec("UPDATE recycler_bids SET bid_status='WINNING',reviewed_by_officer_user_id=21,reviewed_at=CURRENT_TIMESTAMP WHERE bid_id=$bid");
    $db->exec("UPDATE e_lots SET lot_status='AWARDED' WHERE e_lot_id=$lot");
    $db->exec("INSERT INTO handover_records (winning_bid_id,handover_status,recorded_by_officer_user_id) VALUES ($bid,'PENDING',21)");
    $award=$db->query('SELECT * FROM recycler_bids')->fetchAll(); $handover=$db->query('SELECT * FROM handover_records')->fetchAll();
    $service->review(10,$current,1,'REVOKED','Regulatory conflict requires review');
    $check($db->query('SELECT * FROM recycler_bids')->fetchAll()===$award && $db->query('SELECT * FROM handover_records')->fetchAll()===$handover,'Award and handover unchanged');
    $check(count($model->conflicts(10))===1,'Unfinished award conflict surfaced');
    $recycler(); $reject(fn()=>$model->history(11));

    // A real second connection waits for owner serialization and observes revocation.
    $admin(); $current=LicenceCoverageFixture::scope($db,10,1,1,'RECYCLING',1);
    $db->beginTransaction(); $auth->lockOwner(10);
    $worker=proc_open([PHP_BINARY,'-d','session.save_path=/tmp',__FILE__,'--bid-worker',$name,(string)$newLot],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,APP_ROOT);
    $check(is_resource($worker) && trim((string)fgets($pipes[1]))==='READY','Concurrent worker started');
    $s=$db->prepare("UPDATE recycler_license_coverage SET verification_status='REVOKED',review_note='Concurrent test',reviewed_at=CURRENT_TIMESTAMP WHERE coverage_id=?"); $s->execute([$current]);
    $db->commit();
    $check(trim((string)fgets($pipes[1]))==='BLOCKED','Waiting bid sees committed revocation');
    $errors=stream_get_contents($pipes[2]); foreach ($pipes as $pipe) fclose($pipe);
    $check(proc_close($worker)===0 && $errors==='','Concurrency worker exits cleanly'); $worker=null;
    $check((int)$db->query("SELECT COUNT(*) FROM recycler_bids WHERE e_lot_id=$newLot")->fetchColumn()===0,'No partially accepted bid');

    // New controller boundaries: CSRF, confirmation, owner-bound protected evidence.
    Database::getInstance(array_replace($config,['database'=>$name]));
    $controller=new CoverageAdminController(); $admin(); $_POST=[]; http_response_code(200);
    ob_start(); $controller->proposeLicenceCoverage('10'); $body=ob_get_clean();
    $check(http_response_code()===403 && str_contains($body,'403'),'Missing CSRF rejected');
    $_POST=['_csrf_token'=>Csrf::token(),'decision'=>'VERIFIED','review_note'=>'No confirmation'];
    $reject(fn()=>$controller->reviewLicenceCoverage('10',(string)$stale),CoverageRedirect::class);
    $check(str_contains((string)Session::get('error'),'Confirm that you inspected'),'Verification confirmation required');
    http_response_code(200); ob_start(); $controller->viewLicenceEvidence('11','1'); $body=ob_get_clean();
    $check(http_response_code()===404 && $body==='','Protected PDF enforces Recycler ownership');
    $evidence=$db->query('SELECT evidence_id FROM recycler_license_evidence WHERE recycler_user_id=10 ORDER BY evidence_id DESC LIMIT 1')->fetchColumn();
    http_response_code(200); ob_start(); $controller->viewLicenceEvidence('10',(string)$evidence); $body=ob_get_clean();
    $check(http_response_code()===200 && str_starts_with($body,'%PDF'),'Exact protected evidence accessible');
    $_POST=['_csrf_token'=>Csrf::token()]+array_map('strval',$input(2,'STORAGE'));
    $reject(fn()=>$controller->proposeLicenceCoverage('10'),CoverageRedirect::class);
    $posted=(int)$db->query("SELECT MAX(coverage_id) FROM recycler_license_coverage WHERE category_id=2 AND activity_type='STORAGE'")->fetchColumn();
    $check($posted>0 && str_contains((string)Session::get('success'),'Explicit verification'),'Controller creates only a pending proposal');
    $_POST=['_csrf_token'=>Csrf::token(),'decision'=>'VERIFIED','evidence_confirmed'=>'1','review_note'=>'Controller evidence review'];
    $reject(fn()=>$controller->reviewLicenceCoverage('10',(string)$posted),CoverageRedirect::class);
    $check($db->query("SELECT verification_status FROM recycler_license_coverage WHERE coverage_id=$posted")->fetchColumn()==='VERIFIED','Controller confirms current evidence scope');
    $auditCount=(int)$db->query("SELECT COUNT(*) FROM recycler_license_coverage_reviews WHERE coverage_id=$posted")->fetchColumn();
    $reject(fn()=>$controller->reviewLicenceCoverage('10',(string)$posted),CoverageRedirect::class);
    $check((int)$db->query("SELECT COUNT(*) FROM recycler_license_coverage_reviews WHERE coverage_id=$posted")->fetchColumn()===$auditCount,'Replay creates no duplicate decision');
    foreach (['/admin/recycler-verification/10/licence-coverage','/admin/recycler-verification/10/licence-coverage/1/review','/admin/recycler-verification/10/lot-activities'] as $route) {
        $router=new Router(''); (require APP_ROOT.'/routes/web.php')($router,require APP_ROOT.'/config/app.php');
        ob_start(); $router->dispatch('GET',$route); ob_end_clean();
        $check(http_response_code()===405,'Coverage mutations are POST only');
    }
    $predicate=RecyclerLicenceAuthorization::lotSql('NEW.recycler_user_id','auth_lot');
    $check(substr_count($migration,$predicate)===2,'Trigger and application authorization predicates match');
    // Run presentation/session/CSRF and amount regressions with a disposable DB configured.
    putenv('DB_DATABASE='.$name);
    foreach (['recycler-bids.php','recycler-dashboard-ui.php'] as $test) {
        $process=proc_open([PHP_BINARY,'-d','session.save_path=/tmp',APP_ROOT.'/tests/'.$test],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$streams);
        if (!is_resource($process)) throw new RuntimeException('Could not start regression');
        fclose($streams[0]); $output=stream_get_contents($streams[1]); $errors=stream_get_contents($streams[2]); fclose($streams[1]); fclose($streams[2]);
        $expected = $test === 'recycler-bids.php' ? 'PASS: recycler amount, ID and mutation CSRF checks' : 'dashboard presentation/status/escaping assertions';
        $check(proc_close($process)===0 && str_contains($output,$expected),'Regression '.$test.': '.$output.$errors); echo $output;
    }
    echo "PASS: $checks licence coverage/migration/approval/eligibility/security/concurrency assertions\n";
} finally {
    if ($db->inTransaction()) $db->rollBack();
    if (is_resource($worker)) { proc_terminate($worker); proc_close($worker); }
    LicenceCoverageFixture::cleanup();
    if ($created) { $db->exec("DROP DATABASE `$name`"); echo "Removed disposable database $name\n"; }
}
