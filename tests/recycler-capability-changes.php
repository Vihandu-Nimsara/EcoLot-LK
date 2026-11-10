<?php
declare(strict_types=1);
// ECOLOT_CAPABILITY_TEST=1 php -d session.save_path=/tmp tests/recycler-capability-changes.php
require __DIR__ . '/support/bootstrap.php';
require __DIR__ . '/support/LicenceCoverageFixture.php';
if (getenv('ECOLOT_CAPABILITY_TEST') !== '1') {
    fwrite(STDERR, "Requires ECOLOT_CAPABILITY_TEST=1. Creates only a new disposable database.\n"); exit(1);
}
Session::start();
$config = require APP_ROOT . '/config/database.php';
$connect = static function (?string $database = null) use ($config): PDO {
    if ($database !== null && !preg_match('/\Aecolot_test_capability_[a-f0-9]{16}\z/', $database)) throw new RuntimeException('Not a disposable database');
    $db = new PDO(sprintf('mysql:host=%s;port=%s;%scharset=utf8mb4', $config['host'], $config['port'], $database === null ? '' : 'dbname=' . $database . ';'), $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
    $db->exec("SET time_zone = '+05:30'");
    return $db;
};
if (($argv[1] ?? '') === '--worker') {
    $db = $connect($argv[2] ?? '');
    $mode = $argv[3] ?? '';
    $actor = $mode === 'submit' ? 10 : (int) ($argv[5] ?? 2);
    Session::put('auth_user', ['id' => $actor, 'role' => $mode === 'submit' ? 'RECYCLER' : 'ADMIN']);
    echo "READY\n"; flush();
    try {
        if ($mode === 'submit') (new RecyclerCapabilityChangeService($db))->submit(10, 1, 'ADD', 0, 'Concurrent addition');
        else (new RecyclerCapabilityChangeReviewService($db))->review(10, (int) $argv[4], $actor, 'APPROVE');
        echo "DONE\n";
    } catch (DomainException) { echo "REJECTED\n"; }
    exit;
}
$checks = 0;
$check = static function (bool $ok, string $message) use (&$checks): void { if (!$ok) throw new RuntimeException($message); ++$checks; };
$reject = static function (callable $action, string $class = DomainException::class) use ($check): void {
    try { $action(); } catch (Throwable $error) { $check($error instanceof $class, 'Unexpected exception: ' . $error::class . ': ' . $error->getMessage()); return; }
    throw new RuntimeException('Expected rejection');
};
$db = $connect();
$database = 'ecolot_test_capability_' . bin2hex(random_bytes(8));
$created = false; $worker = null; $workers = []; $pdfPath = null;
final class CapabilityRedirect extends RuntimeException {}
final class CapabilityRecyclerController extends RecyclerController
{
    protected function redirect(string $path, int $status = 303): never { throw new CapabilityRedirect($path,$status); }
}
final class CapabilityAdminController extends AdminController
{
    protected function redirect(string $path, int $status = 303): never { throw new CapabilityRedirect($path,$status); }
}
try {
    $db->exec("CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"); $created = true;
    $db->exec("USE `$database`");
    TestDatabase::loadSchema($db);
    // Historical migrations are already applied. Inspect fresh definitions only.
    $check(str_contains($db->query("SHOW COLUMNS FROM recycler_capabilities LIKE 'capability_status'")->fetch()['Type'], 'INACTIVE'), 'MariaDB migrations append INACTIVE');
    $check(str_contains($db->query("SHOW COLUMNS FROM recycler_capability_change_requests LIKE 'previous_capability_status'")->fetch()['Type'], 'INACTIVE'), 'Existing request-history ENUM upgrade supported');
    $password = password_hash('CapabilityTest123!', PASSWORD_DEFAULT);
    $insertUser = $db->prepare('INSERT INTO users (user_id,full_name,mobile_number,password_hash,role,account_status) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ([1 => 'ADMIN', 2 => 'ADMIN', 10 => 'RECYCLER', 11 => 'RECYCLER', 12 => 'RECYCLER'] as $id => $role) $insertUser->execute([$id, 'Test ' . $id, '94770' . str_pad((string) $id, 6, '0', STR_PAD_LEFT), $password, $role, 'ACTIVE']);
    $db->exec('INSERT INTO administrators VALUES (1), (2)');
    $db->exec("INSERT INTO authorized_recyclers (user_id,company_name,business_address,district,verification_status,reviewed_by_admin_user_id,reviewed_at) VALUES
        (10,'Recorded company','Test address','Colombo','VERIFIED',1,CURRENT_TIMESTAMP),
        (11,'Other company','Test address','Colombo','VERIFIED',1,CURRENT_TIMESTAMP),
        (12,'Pending company','Test address','Colombo','PENDING',NULL,NULL)");
    $db->exec("INSERT INTO waste_categories (category_id,category_name,category_status) VALUES
        (1,'Automobile E-Waste','ACTIVE'),(2,'Domestic E-Waste','ACTIVE'),(3,'Industrial E-Waste','ACTIVE'),
        (4,'Medical E-Waste','INACTIVE'),(5,'Office E-Waste','ACTIVE'),(6,'Not a Recycler category','ACTIVE')");
    foreach ([1,2,3,5] as $category) LicenceCoverageFixture::scope($db,10,1,$category);
    $service = new RecyclerCapabilityChangeService($db); $review = new RecyclerCapabilityChangeReviewService($db);
    $recycler = static function (int $id = 10): void { Session::put('auth_user', ['id' => $id, 'role' => 'RECYCLER']); };
    $admin = static function (int $id = 1): void { Session::put('auth_user', ['id' => $id, 'role' => 'ADMIN']); };
    $submit = static function (string $type, ?int $risk, int $category = 1, string $reason = 'Recorded request reason') use ($service, $recycler): int { $recycler(); return $service->submit(10, $category, $type, $risk, $reason); };
    $decide = static function (int $request, string $decision = 'APPROVE', string $note = '', int $owner = 10) use ($review, $admin): void { $admin(); $review->review($owner, $request, 1, $decision, $note); };
    $cap = static function (int $category = 1) use ($db): ?array { $s = $db->prepare('SELECT * FROM recycler_capabilities WHERE recycler_user_id = 10 AND category_id = ?'); $s->execute([$category]); return $s->fetch() ?: null; };
    $reset = static function () use ($db): void { $db->exec('DELETE FROM recycler_capability_change_requests'); $db->exec('DELETE FROM recycler_capabilities'); };
    $request = $submit('ADD', 0);
    $check($cap() === null, 'Pending ADD does not create permission');
    $decide($request); $check($cap()['capability_status'] === 'APPROVED' && (int) $cap()['can_handle_high_risk'] === 0, 'ADD approval');
    $reject(fn () => $decide($request));
    $request = $submit('UPDATE', 1); $check((int) $cap()['can_handle_high_risk'] === 0, 'Pending UPDATE leaves old permission');
    $decide($request); $check((int) $cap()['can_handle_high_risk'] === 1, 'UPDATE approval');
    $request = $submit('DEACTIVATE', null); $check($cap()['capability_status'] === 'APPROVED', 'Pending deactivation retains authorization');
    $decide($request); $check($cap()['capability_status'] === 'INACTIVE', 'Voluntary deactivation is distinct from suspension');
    $request = $submit('UPDATE', 1); $decide($request); $check($cap()['capability_status'] === 'APPROVED', 'INACTIVE reactivation may retain risk');
    $request = $submit('UPDATE', 0); $before = $cap(); $decide($request, 'REJECT', 'Not justified'); $check($cap() === $before, 'Rejection preserves capability and metadata');
    $recycler(); $history = (new RecyclerCapabilityChangeRequest($db))->historyForRecycler(10);
    $check(count($history) === 5 && $history[0]['review_note'] === 'Not justified', 'History and Admin feedback retained');
    $reject(fn () => (new RecyclerCapabilityChangeRequest($db))->historyForRecycler(11));
    $reject(fn () => (new RecyclerCapabilityChangeRequest($db))->delete($request), LogicException::class);
    foreach ([['ADD',0,1,'Reason'],['UPDATE',1,1,'Reason'],['OTHER',0,2,'Reason'],['ADD',0,999,'Reason'],['ADD',0,4,'Reason'],['ADD',0,6,'Reason'],['ADD',2,2,'Reason'],['ADD',null,2,'Reason'],['ADD',0,2,' '],['ADD',0,2,str_repeat('a',501)],['DEACTIVATE',0,1,'Reason']] as [$type,$risk,$category,$reason]) $reject(fn () => $submit($type,$risk,$category,$reason));
    $request = $submit('ADD',0,2); $reject(fn () => $submit('ADD',1,2));
    $db->beginTransaction();
    $reject(fn () => (new RecyclerCapabilityChangeRequest($db))->createPendingRequest(10,2,'ADD',null,0,'Direct duplicate'), PDOException::class);
    $db->rollBack();
    $admin(); $reject(fn () => $review->review(10,$request,1,'REJECT','')); $reject(fn () => $review->review(11,$request,1,'APPROVE'));
    $reject(fn () => $review->review(10,$request,2,'APPROVE'));
    $db->exec("UPDATE users SET account_status='SUSPENDED' WHERE user_id=1"); $reject(fn () => $decide($request)); $db->exec("UPDATE users SET account_status='ACTIVE' WHERE user_id=1");
    $decide($request,'REJECT','Not needed');
    foreach (['SUSPENDED','REJECTED','PENDING'] as $state) {
        $db->exec("UPDATE recycler_capabilities SET capability_status='$state' WHERE recycler_user_id=10 AND category_id=1");
        $reject(fn () => $submit('UPDATE',0)); $reject(fn () => $submit('DEACTIVATE',null));
    }
    $db->exec("UPDATE recycler_capabilities SET capability_status='APPROVED' WHERE recycler_user_id=10 AND category_id=1");
    $db->exec("UPDATE users SET account_status='DISABLED' WHERE user_id=10"); $reject(fn () => $submit('ADD',0,2)); $db->exec("UPDATE users SET account_status='ACTIVE' WHERE user_id=10");
    $recycler(12); $reject(fn () => $service->submit(12,2,'ADD',0,'Unverified request')); $reject(fn () => $service->submit(10,2,'ADD',0,'Wrong owner'));
    $admin(); $reject(fn () => $service->submit(10,2,'ADD',0,'Admin cannot self submit'));
    $request = $submit('UPDATE',0); $db->exec("UPDATE recycler_capabilities SET capability_status='SUSPENDED' WHERE recycler_user_id=10 AND category_id=1");
    $reject(fn () => $decide($request)); $check($cap()['capability_status'] === 'SUSPENDED', 'Stale approval cannot overwrite restriction'); $decide($request,'REJECT','Stale request');
    $reset();
    $request = $submit('ADD',0);
    $db->exec("CREATE TRIGGER reject_capability_resolution BEFORE UPDATE ON recycler_capability_change_requests FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Injected resolution failure'");
    $reject(fn () => $decide($request), PDOException::class); $check($cap() === null && $db->query('SELECT request_status FROM recycler_capability_change_requests')->fetchColumn() === 'PENDING', 'Request and capability updates roll back together');
    $db->exec('DROP TRIGGER reject_capability_resolution'); $decide($request);
    $db->exec("UPDATE recycler_capabilities SET capability_status='INACTIVE' WHERE recycler_user_id=10 AND category_id=1");
    $recycler(); $options = array_column($service->options(10), null, 'category_id');
    $check($options[1]['actions'] === ['UPDATE'] && !isset($options[4],$options[6]), 'UI actions match reactivation and category policy');

    // Two real PHP processes serialize on the owner row for submission/review.
    foreach (['submit','review'] as $mode) {
        $reset(); $requestId = $mode === 'review' ? $submit('ADD',0) : 0;
        $db->beginTransaction(); (new User($db))->lockForVerification(10);
        $workerStreams=[];
        foreach ([1,2] as $actor) {
            $workers[$actor] = proc_open([PHP_BINARY,'-d','session.save_path=' . sys_get_temp_dir(),__FILE__,'--worker',$database,$mode,(string)$requestId,(string)$actor], [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes);
            if (!is_resource($workers[$actor])) throw new RuntimeException('Worker failed');
            fclose($pipes[0]); stream_set_timeout($pipes[1],10); $check(trim((string)fgets($pipes[1]))==='READY','Concurrent worker ready');
            $workerStreams[$actor]=$pipes;
        }
        $db->commit();
        $results=[];
        foreach ([1,2] as $actor) {
            $pipes=$workerStreams[$actor]; $results[]=trim((string)fgets($pipes[1]));
            $errors=stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]); $status=proc_close($workers[$actor]); unset($workers[$actor]);
            $check($status===0 && $errors==='','Concurrency worker clean exit');
        }
        sort($results); $check($results===['DONE','REJECTED'],'Two real service callers serialize and reject one replay');
        $check((int)$db->query('SELECT COUNT(*) FROM recycler_capability_change_requests')->fetchColumn()===1,'Concurrent operation creates only one request');
    }

    // Real e-lot provenance/eligibility fixture. Never change existing bids implicitly.
    // The older demo bid fixture uses removed verified_* Recycler columns.
    // Build this feature's fixture against the actual reviewed_* schema instead.
    foreach ([20=>'COLLECTOR',21=>'MUNICIPAL_OFFICER',22=>'PUBLIC_USER'] as $id=>$role) $insertUser->execute([$id,'Actor '.$id,'947700000'.$id,$password,$role,'ACTIVE']);
    $db->exec('INSERT INTO collectors VALUES (20)'); $db->exec('INSERT INTO municipal_officers VALUES (21)');
    $insert = static function (string $sql, array $params = []) use ($db): int { $s=$db->prepare($sql); $s->execute($params); return (int)$db->lastInsertId(); };
    $owner=10; $category=1;
    $db->exec("UPDATE recycler_capabilities SET can_handle_high_risk=1 WHERE recycler_user_id=10 AND category_id=1");
    $db->exec("UPDATE recycler_licenses SET license_number='CAP-TEST' WHERE recycler_user_id=10 AND license_status='VALID'");
    LicenceCoverageFixture::scope($db,10,1,1);
    $area=$insert("INSERT INTO postal_code_areas (postal_code,area_name) VALUES ('00100','Test area')");
    $insert("INSERT INTO public_profiles VALUES (22,?,'Test address')",[$area]);
    $campaign=$insert("INSERT INTO monthly_campaigns (created_by_officer_user_id,campaign_name,campaign_month) VALUES (21,'Test campaign',DATE_FORMAT(CURRENT_DATE,'%Y-%m-01'))");
    $schedule=$insert("INSERT INTO area_collection_schedules (campaign_id,postal_area_id,created_by_officer_user_id,collection_date,request_cutoff_at,request_capacity,schedule_status) VALUES (?,?,21,CURRENT_DATE + INTERVAL 3 DAY,CURRENT_TIMESTAMP + INTERVAL 1 DAY,10,'OPEN')",[$campaign,$area]);
    $insert('INSERT INTO schedule_assignments (schedule_id,collector_user_id,assigned_by_officer_user_id) VALUES (?,20,21)',[$schedule]);
    $pickup=$insert("INSERT INTO e_waste_requests (public_user_id,schedule_id,pickup_address) VALUES (22,?,'Test address')",[$schedule]);
    $item=$insert("INSERT INTO e_waste_items (category_id,item_name) VALUES (1,'Test item')");
    $requestItem=$insert("INSERT INTO request_items (request_id,waste_item_id,quantity,estimated_weight_kg,item_condition,applied_risk_level) VALUES (?,?,5,12.5,'DAMAGED','HIGH')",[$pickup,$item]);
    $collection=$insert("INSERT INTO schedule_collections (schedule_id,submitted_by_collector_user_id,verification_status,verified_by_officer_user_id,verified_at) VALUES (?,20,'VERIFIED',21,CURRENT_TIMESTAMP)",[$schedule]);
    $record=$insert("INSERT INTO collection_records (schedule_collection_id,request_id,pickup_result,pickup_attempted_at) VALUES (?,?,'COLLECTED',CURRENT_TIMESTAMP)",[$collection,$pickup]);
    $recordItem=$insert("INSERT INTO collection_record_items (collection_record_id,request_item_id,actual_quantity,actual_weight_kg,actual_condition,actual_risk_level,item_result) VALUES (?,?,5,12.1,'DAMAGED','HIGH','COLLECTED')",[$record,$requestItem]);
    $lot=$insert("INSERT INTO e_lots (lot_code,created_by_collector_user_id,category_id,title,lot_status,verified_by_officer_user_id,verified_at,bidding_open_at,bidding_close_at) VALUES ('CAP-LOT',20,1,'Test lot','OPEN_FOR_BIDDING',21,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP - INTERVAL 1 DAY,CURRENT_TIMESTAMP + INTERVAL 7 DAY)");
    $insert('INSERT INTO e_lot_items (record_item_id,e_lot_id) VALUES (?,?)',[$recordItem,$lot]);
    $admin(); (new RecyclerLicenceCoverageService($db))->setLotActivities($lot,1,0,['RECYCLING'],'Synthetic lot requires recycling');
    Session::put('auth_user',['id'=>$owner,'role'=>'RECYCLER']);
    $lots = new ELot($db);
    $check($lots->isEligible($lot,$owner),'Approved high-risk capability eligible');
    $riskRequest = $service->submit($owner,$category,'UPDATE',0,'Reduce handling risk');
    $check($lots->isEligible($lot,$owner),'Pending risk request does not affect eligibility');
    $admin(); $review->review($owner,$riskRequest,1,'REJECT','Permission remains needed');
    $check($lots->isEligible($lot,$owner),'Rejected risk request does not affect eligibility');
    $recycler(); $riskRequest=$service->submit($owner,$category,'UPDATE',0,'Reduce handling risk');
    $admin(); $review->review($owner,$riskRequest,1,'APPROVE');
    $check(!$lots->isEligible($lot,$owner),'High-risk eligibility changes only after approval');
    Session::put('auth_user',['id'=>$owner,'role'=>'RECYCLER']);
    $raise=$service->submit($owner,$category,'UPDATE',1,'Restore high risk'); $admin(); $review->review($owner,$raise,1,'APPROVE');
    Session::put('auth_user',['id'=>$owner,'role'=>'RECYCLER']);
    $bidId=(new RecyclerBidService($db))->placeBid($owner,$lot,'500');
    $deactivate=$service->submit($owner,$category,'DEACTIVATE',null,'Stop this category');
    $bidsBefore=$db->query('SELECT * FROM recycler_bids ORDER BY bid_id')->fetchAll();
    $admin(); $reject(fn () => $review->review($owner,$deactivate,1,'APPROVE'));
    $check($db->query('SELECT * FROM recycler_bids ORDER BY bid_id')->fetchAll()===$bidsBefore,'Blocked deactivation never rewrites bids');
    $review->review($owner,$deactivate,1,'REJECT','Resolve outstanding bids');
    $recycler(); $reduce=$service->submit($owner,$category,'UPDATE',0,'Remove high risk');
    $admin(); $reject(fn () => $review->review($owner,$reduce,1,'APPROVE'));
    $review->review($owner,$reduce,1,'REJECT','Resolve outstanding bids');
    $recycler(); $deactivate=$service->submit($owner,$category,'DEACTIVATE',null,'Stop handling category'); $admin();
    $db->exec("UPDATE recycler_bids SET bid_status='WINNING', reviewed_by_officer_user_id=21, reviewed_at=CURRENT_TIMESTAMP WHERE bid_id=$bidId");
    $db->exec("UPDATE e_lots SET lot_status='AWARDED' WHERE e_lot_id=$lot");
    $reject(fn () => $review->review($owner,$deactivate,1,'APPROVE'));
    $db->exec("INSERT INTO handover_records (winning_bid_id,handover_status,handover_date,recorded_by_officer_user_id) VALUES ($bidId,'COMPLETED',CURRENT_TIMESTAMP,21)");
    $beforeHandover=$db->query('SELECT * FROM handover_records')->fetchAll();
    $beforeAward=$db->query('SELECT * FROM recycler_bids ORDER BY bid_id')->fetchAll();
    $review->review($owner,$deactivate,1,'APPROVE');
    $db->exec("UPDATE e_lots SET lot_status='OPEN_FOR_BIDDING' WHERE e_lot_id=$lot");
    $check(!$lots->isEligible($lot,$owner),'INACTIVE never qualifies for bidding');
    $check($db->query('SELECT * FROM handover_records')->fetchAll()===$beforeHandover,'Completed handover is preserved');
    $check($db->query('SELECT * FROM recycler_bids ORDER BY bid_id')->fetchAll()===$beforeAward,'Capability approval leaves winning bid untouched');

    // Initial-registration review remains a separate working path.
    $db->exec("INSERT INTO recycler_capabilities (recycler_user_id,category_id,capability_status) VALUES (12,2,'PENDING')");
    $admin(); $reject(fn () => (new RecyclerCapabilityReviewService($db))->review(12,2,1,'APPROVE'));

    // CEA initial approval, renewal, replacement and rejection remain unchanged.
    $relativePdf='uploads/recycler-licenses/capability-regression-' . bin2hex(random_bytes(8)) . '.pdf';
    $pdfPath=APP_ROOT . '/storage/' . $relativePdf;
    if (file_put_contents($pdfPath,"%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<<>>\n%%EOF\n")===false) throw new RuntimeException('Cannot create private test PDF');
    $licenseId=$insert("INSERT INTO recycler_licenses (recycler_user_id,license_number,expiry_date,license_status,document_path) VALUES (12,'CAP-INITIAL',CURRENT_DATE + INTERVAL 1 YEAR,'PENDING',?)",[$relativePdf]);
    (new RecyclerLicenseReviewService($db))->review(12,1,$licenseId,'APPROVE');
    $check($db->query("SELECT license_status FROM recycler_licenses WHERE license_id=$licenseId")->fetchColumn()==='VALID','Initial CEA review still accepts valid PDF');
    $check((int)$db->query('SELECT COUNT(*) FROM recycler_license_coverage c JOIN recycler_license_evidence e ON e.evidence_id=c.evidence_id WHERE e.recycler_user_id=12')->fetchColumn()===0,'Overall licence approval creates evidence, not inferred coverage');
    LicenceCoverageFixture::scope($db,12,1,2);
    (new RecyclerCapabilityReviewService($db))->review(12,2,1,'APPROVE');
    $check($db->query('SELECT capability_status FROM recycler_capabilities WHERE recycler_user_id=12 AND category_id=2')->fetchColumn()==='APPROVED','Initial capability review requires verified scope');
    $currentId=(int)$db->query("SELECT license_id FROM recycler_licenses WHERE recycler_user_id=10 AND license_status='VALID'")->fetchColumn();
    $licenseRequests=new RecyclerLicenseUpdateRequest($db);
    $future=(new DateTimeImmutable('+2 years'))->format('Y-m-d');
    LicenceCoverageFixture::scope($db,10,1,1);
    $authorization=new RecyclerLicenceAuthorization($db);
    $renewal=$licenseRequests->createPendingRequest(10,$currentId,'CAP-TEST',$future,$relativePdf);
    $authorization->requireCapability(10,1,1); $check(true,'Pending renewal preserves current scope');
    (new RecyclerLicenseUpdateReviewService($db))->review(10,$renewal,1,'APPROVE');
    $reject(fn () => $authorization->requireCapability(10,1,1));
    $check((int)$db->query("SELECT COUNT(*) FROM recycler_license_revisions WHERE update_request_id=$renewal")->fetchColumn()===1,'Same-number renewal keeps history');
    $replacement=$licenseRequests->createPendingRequest(10,$currentId,'CAP-REPLACED',$future,$relativePdf);
    (new RecyclerLicenseUpdateReviewService($db))->review(10,$replacement,1,'APPROVE');
    $check($db->query("SELECT license_status FROM recycler_licenses WHERE license_id=$currentId")->fetchColumn()==='SUPERSEDED','Different-number replacement preserves old licence');
    $currentId=(int)$db->query("SELECT license_id FROM recycler_licenses WHERE recycler_user_id=10 AND license_status='VALID'")->fetchColumn();
    LicenceCoverageFixture::scope($db,10,1,1);
    $licencesBefore=$db->query('SELECT * FROM recycler_licenses ORDER BY license_id')->fetchAll();
    $rejected=$licenseRequests->createPendingRequest(10,$currentId,'CAP-REJECTED',$future,$relativePdf);
    $authorization->requireCapability(10,1,1); $check(true,'Pending replacement preserves applicable scope');
    (new RecyclerLicenseUpdateReviewService($db))->review(10,$rejected,1,'REJECT','Not an acceptable update');
    $check($db->query('SELECT * FROM recycler_licenses ORDER BY license_id')->fetchAll()===$licencesBefore,'Licence rejection leaves current approved records intact');
    $authorization->requireCapability(10,1,1); $check(true,'Rejected update preserves verified scope');

    // Real controller/CSRF/PRG integration uses this disposable database only.
    LicenceCoverageFixture::scope($db,10,1,3);
    putenv('DB_DATABASE=' . $database);
    $recycler();
    $controller=new CapabilityRecyclerController();
    $_POST=['_csrf_token'=>'invalid','category_id'=>'3','request_type'=>'ADD','requested_can_handle_high_risk'=>'0','request_reason'=>'New category'];
    try { $controller->requestCapabilityChange(); } catch (CapabilityRedirect $redirect) { $check($redirect->getMessage()==='/recycler/profile','Invalid CSRF uses profile redirect'); }
    $check(Session::pullFlash('profile_error')==='Your session expired. Please reload the page and try again.','Submission CSRF enforced');
    $_POST=['_csrf_token'=>Csrf::token(),'category_id'=>'3','request_type'=>'ADD','requested_can_handle_high_risk'=>'0','request_reason'=>'New category','recycler_user_id'=>'11'];
    try { $controller->requestCapabilityChange(); } catch (CapabilityRedirect $redirect) { $check($redirect->getCode()===303,'Submission PRG'); }
    $requestId=(int)$db->query("SELECT request_id FROM recycler_capability_change_requests WHERE recycler_user_id=10 AND category_id=3 AND request_status='PENDING'")->fetchColumn();
    $check($requestId>0,'Controller takes owner from session');
    $admin(); $adminController=new CapabilityAdminController();
    $_POST=['_csrf_token'=>'bad','decision'=>'APPROVE'];
    ob_start(); $adminController->reviewCapabilityChange('10',(string)$requestId); ob_end_clean(); $check(http_response_code()===403,'Review CSRF enforced');
    $_POST=['_csrf_token'=>Csrf::token(),'decision'=>'APPROVE','review_note'=>'Verified proposal'];
    try { $adminController->reviewCapabilityChange('10',(string)$requestId); } catch (CapabilityRedirect $redirect) { $check($redirect->getMessage()==='/admin/recycler-verification/10' && $redirect->getCode()===303,'Review PRG'); }
    $check($cap(3)['capability_status']==='APPROVED','Controller approval applies capability');
    $recycler(); $_POST=['_csrf_token'=>Csrf::token(),'full_name'=>'Updated contact'];
    try { $controller->updateProfileContact(); } catch (CapabilityRedirect) {}
    $check((new User($db))->find(10)['full_name']==='Updated contact','Contact Person regression');
    foreach (['GET /recycler/profile/capabilities/request','GET /admin/recycler-verification/10/capability-change/1/review'] as $route) {
        [$method,$path]=explode(' ',$route,2); $router=new Router(''); (require APP_ROOT . '/routes/web.php')($router,require APP_ROOT . '/config/app.php');
        ob_start(); $router->dispatch($method,$path); ob_end_clean(); $check(http_response_code()===405,'Capability mutations are POST-only');
    }

    // Existing phone tests create their own disposable DB and always inject SMS.
    putenv('ECOLOT_PHONE_TEST=1');
    foreach (['recycler-profile-ui.php','recycler-phone-change.php'] as $regression) {
        $child=proc_open([PHP_BINARY,'-d','session.save_path=' . sys_get_temp_dir(),APP_ROOT . '/tests/' . $regression],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$streams);
        if (!is_resource($child)) throw new RuntimeException('Could not start regression');
        fclose($streams[0]); $output=stream_get_contents($streams[1]); $errors=stream_get_contents($streams[2]); fclose($streams[1]); fclose($streams[2]);
        $check(proc_close($child)===0,'Existing regression failed: '.$regression.': '.$errors.$output); echo $output;
    }
    echo "PASS: $checks capability schema/submission/review/concurrency/eligibility assertions\n";
} finally {
    if (is_resource($worker)) { proc_terminate($worker); proc_close($worker); }
    foreach ($workers as $process) if (is_resource($process)) { proc_terminate($process); proc_close($process); }
    if ($db->inTransaction()) $db->rollBack();
    if ($pdfPath !== null && is_file($pdfPath)) unlink($pdfPath);
    LicenceCoverageFixture::cleanup();
    if ($created) { $db->exec("DROP DATABASE `$database`"); echo "Removed disposable database $database\n"; }
}
