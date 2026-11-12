<?php
declare(strict_types=1);
// Explicit opt-in; all writes and synthetic uploads belong to one random disposable DB.
require __DIR__ . '/support/bootstrap.php';
require __DIR__ . '/support/LicenceCoverageFixture.php';
if (getenv('ECOLOT_PROFILE_SECURITY_TEST') !== '1') {
    fwrite(STDERR, "Requires ECOLOT_PROFILE_SECURITY_TEST=1; disposable databases only.\n");
    exit(1);
}
Session::start();
$config = require APP_ROOT . '/config/database.php';
$connect = static function (?string $name = null) use ($config): PDO {
    if ($name !== null && !preg_match('/\Aecolot_test_profile_security_[a-f0-9]{16}\z/', $name)) throw new RuntimeException('Unsafe database');
    return new PDO(sprintf('mysql:host=%s;port=%s;%scharset=utf8mb4', $config['host'], $config['port'], $name === null ? '' : 'dbname=' . $name . ';'), $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
};
if (($argv[1] ?? '') === '--guard') {
    $connect($argv[2] ?? ''); // Validate the disposable target before configuring application PDO.
    putenv('DB_DATABASE=' . $argv[2]);
    $payload = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
    if ($payload['role'] !== 'GUEST') Session::put('auth_user', ['id' => $payload['id'], 'role' => $payload['role'], 'name' => 'Cached session']);
    http_response_code(200);
    ob_start();
    register_shutdown_function(static function (): void {
        $body = ob_get_clean();
        echo json_encode(['status' => http_response_code(), 'authenticated' => Auth::check(), 'pdf' => str_starts_with($body, '%PDF-'), 'body' => str_starts_with($body, '%PDF-') ? '[private document]' : $body], JSON_THROW_ON_ERROR);
    });
    $_POST = ['full_name' => $payload['name'] ?? 'Changed contact', 'recycler_user_id' => 11];
    if (($payload['csrf'] ?? '') === 'valid') $_POST['_csrf_token'] = Csrf::token();
    elseif (($payload['csrf'] ?? '') === 'invalid') $_POST['_csrf_token'] = 'invalid';
    switch ($payload['action']) {
        case 'contact': (new RecyclerController())->updateProfileContact(); break;
        case 'profile': (new RecyclerController())->profile(); break;
        case 'phone': (new RecyclerController())->requestPhoneChange(); break;
        case 'capability': (new RecyclerController())->requestCapabilityChange(); break;
        case 'licence': (new RecyclerController())->submitLicenseUpdate(); break;
        case 'pdf': (new AdminController())->viewRecyclerLicense('10'); break;
        case 'update-pdf': (new AdminController())->viewRecyclerLicenseUpdateDocument('10', '1'); break;
        default: Auth::requireRole($payload['role']); echo 'ALLOWED';
    }
    exit;
}
final class SecurityRedirect extends RuntimeException {}
final class SecurityRecyclerController extends RecyclerController {
    protected function redirect(string $path, int $status = 303): never { throw new SecurityRedirect($path); }
}
final class SecurityLoginController extends AuthController {
    public array $rendered = [];
    public function view(string $view, array $data = [], ?string $layout = null): void { $this->rendered = $data; }
    protected function redirect(string $path, int $status = 303): never { throw new SecurityRedirect($path); }
}
$checks = 0;
$assert = static function (bool $ok, string $label) use (&$checks): void { if (!$ok) throw new RuntimeException($label); ++$checks; };
$db = $connect();
$name = 'ecolot_test_profile_security_' . bin2hex(random_bytes(8));
$created = false;
$paths = [];
try {
    $db->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"); $created = true;
    $db->exec("USE `$name`"); $db->exec("SET time_zone = '+05:30'");
    TestDatabase::loadSchema($db);
    putenv('DB_DATABASE=' . $name);
    $password = 'SecurityTest123!';
    $testMobile = '9470' . str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT);
    $s = $db->prepare("INSERT INTO users (user_id,full_name,mobile_number,password_hash,role,account_status,mobile_verified_at) VALUES (?, ?, ?, ?, ?, 'ACTIVE',CURRENT_TIMESTAMP)");
    foreach ([1 => 'ADMIN', 10 => 'RECYCLER', 11 => 'RECYCLER', 20 => 'COLLECTOR', 21 => 'MUNICIPAL_OFFICER', 22 => 'PUBLIC_USER'] as $id => $role) $s->execute([$id, 'Original ' . $id, $id === 10 ? $testMobile : '94770' . str_pad((string) $id, 6, '0', STR_PAD_LEFT), password_hash($password, PASSWORD_DEFAULT), $role]);
    $db->exec('INSERT INTO administrators VALUES (1)');
    $db->exec("INSERT INTO authorized_recyclers (user_id,company_name,business_address,district,verification_status,reviewed_by_admin_user_id,reviewed_at)
        VALUES (10,'Security Owner','Test address','Colombo','VERIFIED',1,CURRENT_TIMESTAMP),(11,'Other Owner','Test address','Colombo','VERIFIED',1,CURRENT_TIMESTAMP)");
    $pdf = LicenceCoverageFixture::pdf();
    $s = $db->prepare("INSERT INTO recycler_licenses (recycler_user_id,license_number,expiry_date,license_status,document_path) VALUES (10,'SECURITY-SYNTHETIC','2099-01-01','VALID',?)"); $s->execute([$pdf]);
    $s = $db->prepare("INSERT INTO recycler_license_update_requests (recycler_user_id,current_license_id,requested_license_number,requested_expiry_date,document_path) VALUES (10,1,'SECURITY-SYNTHETIC','2099-02-01',?)"); $s->execute([$pdf]);
    $run = static function (array $payload) use ($name, $assert): array {
        $process = proc_open([PHP_BINARY, '-d', 'session.save_path=' . sys_get_temp_dir(), __FILE__, '--guard', $name], [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']], $pipes);
        if (!is_resource($process)) throw new RuntimeException('Cannot start guard worker');
        fwrite($pipes[0], json_encode($payload, JSON_THROW_ON_ERROR)); fclose($pipes[0]);
        $out = stream_get_contents($pipes[1]); $error = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
        $assert(proc_close($process) === 0 && $error === '', 'Guard worker completed without warnings');
        return json_decode($out, true, 512, JSON_THROW_ON_ERROR);
    };
    $owner = static fn() => Session::put('auth_user', ['id' => 10, 'role' => 'RECYCLER']);
    $setState = static function (string $verification, string $account = 'ACTIVE') use ($db): void {
        $s = $db->prepare("UPDATE authorized_recyclers SET verification_status=?, reviewed_by_admin_user_id=?, reviewed_at=?, review_note=? WHERE user_id=10");
        $pending = $verification === 'PENDING';
        $s->execute([$verification, $pending ? null : 1, $pending ? null : '2026-11-12 09:00:00', in_array($verification, ['REJECTED','SUSPENDED'], true) ? 'Synthetic test decision' : null]);
        $db->prepare('UPDATE users SET account_status=? WHERE user_id=10')->execute([$account]);
    };
    // Existing sessions are checked against current DB state, with no success cache.
    foreach (['PENDING', 'REJECTED', 'SUSPENDED'] as $verification) {
        $setState($verification); $owner();
        $assert(!Auth::currentAccountAllows('RECYCLER', $db), 'Current verification denies ' . $verification);
        foreach (['profile','contact','phone','capability','licence'] as $action) {
            $result = $run(['role'=>'RECYCLER','id'=>10,'action'=>$action,'csrf'=>'valid']);
            $assert($result['status'] === 403 && !$result['authenticated'], 'Stale session denied and cleared: ' . $verification . ' ' . $action);
        }
        $calls = 0;
        $limiter = tempnam(sys_get_temp_dir(), 'ecolot-profile-password-');
        try {
            $service = new RecyclerPhoneChangeService($db, static function () use (&$calls): void { ++$calls; }, new LoginRateLimiter($limiter));
            try { $service->startChange(10, $password, '0779000025'); throw new RuntimeException('Unverified phone change accepted'); }
            catch (DomainException) { $assert($calls === 0, 'Phone service independently rejects verification ' . $verification); }
        } finally { unlink($limiter); }
    }
    foreach (['PENDING','SUSPENDED','DISABLED'] as $account) {
        $setState('VERIFIED', $account); $owner();
        $assert(!Auth::currentAccountAllows('RECYCLER', $db), 'Current account denies ' . $account);
        $result = $run(['role'=>'RECYCLER','id'=>10,'action'=>'contact','csrf'=>'valid']);
        $assert($result['status'] === 403 && !$result['authenticated'], 'Deactivated cached session cannot mutate: ' . $account);
    }
    $setState('VERIFIED'); $owner();
    $assert(Auth::currentAccountAllows('RECYCLER', $db), 'Verified active owner allowed after prior denied state');
    $assert($db->query('SELECT full_name FROM users WHERE user_id=10')->fetchColumn() === 'Original 10', 'All denied contact posts preserved original data');
    foreach (['contact','phone','capability','licence'] as $action) foreach (['missing','invalid'] as $csrf) {
        $result = $run(['role'=>'RECYCLER','id'=>10,'action'=>$action,'csrf'=>$csrf]);
        $assert($result['status'] === 403 && $result['authenticated'], 'CSRF denied without changing authentication: ' . $action . ' ' . $csrf);
    }
    $result = $run(['role'=>'ADMIN','id'=>1,'action'=>'profile']);
    $assert($result['status'] === 403, 'Admin cannot enter Recycler profile');
    $result = $run(['role'=>'GUEST','id'=>0,'action'=>'profile']);
    $assert($result['status'] === 302 && !$result['authenticated'], 'Guest redirected before profile access');
    foreach (['pdf','update-pdf'] as $action) {
        $result = $run(['role'=>'ADMIN','id'=>1,'action'=>$action]);
        $assert($result['status'] === 200 && $result['pdf'], 'Active Admin can read synthetic private PDF: ' . $action);
        foreach (['SUSPENDED','DISABLED'] as $status) {
            $db->prepare('UPDATE users SET account_status=? WHERE user_id=1')->execute([$status]);
            $result = $run(['role'=>'ADMIN','id'=>1,'action'=>$action]);
            $assert($result['status'] === 403 && !$result['pdf'] && !$result['authenticated'], 'Revoked Admin session cannot view PDF: ' . $action . ' ' . $status);
        }
        $db->exec("UPDATE users SET account_status='ACTIVE' WHERE user_id=1");
        $result = $run(['role'=>'RECYCLER','id'=>10,'action'=>$action]);
        $assert($result['status'] === 403 && !$result['pdf'], 'Recycler cannot read Admin PDF endpoint');
    }
    // Preserve renewal/profile access when the licence (not the account) is revoked.
    $db->exec("UPDATE recycler_licenses SET license_status='REVOKED' WHERE license_id=1");
    $owner(); $assert(Auth::currentAccountAllows('RECYCLER', $db), 'Revoked licence does not remove account access needed for review/renewal');
    try { (new RecyclerLicenceAuthorization($db))->requireCurrentEvidence(10); throw new RuntimeException('Revoked licence authorized'); }
    catch (DomainException) { $assert(true, 'Revoked licence cannot authorize handling'); }
    foreach ([20=>'COLLECTOR',21=>'MUNICIPAL_OFFICER',22=>'PUBLIC_USER'] as $id=>$role) {
        $db->prepare("UPDATE users SET account_status='DISABLED' WHERE user_id=?")->execute([$id]);
        $result = $run(['role'=>$role,'id'=>$id,'action'=>'guard']);
        $assert($result['status'] === 200 && $result['body'] === 'ALLOWED', 'Other role retains original session-only requireRole behavior: ' . $role);
    }
    // Contact backend: Unicode length/control validation and owner isolation.
    $owner(); $_POST = ['_csrf_token'=>Csrf::token()];
    foreach (['', '   ', 'A', str_repeat('A',121), "Bad\nName", ['array']] as $bad) {
        $_POST['full_name'] = $bad;
        try { (new SecurityRecyclerController())->updateProfileContact(); } catch (SecurityRedirect) {}
        $assert(Session::pullFlash('profile_error') !== null, 'Invalid/overlength contact rejected server-side');
    }
    $_POST = ['_csrf_token'=>Csrf::token(), 'full_name'=>str_repeat('ක',120), 'user_id'=>11];
    try { (new SecurityRecyclerController())->updateProfileContact(); } catch (SecurityRedirect) {}
    $assert($db->query('SELECT full_name FROM users WHERE user_id=10')->fetchColumn() === str_repeat('ක',120), '120 Unicode characters accepted for own contact');
    $assert($db->query('SELECT full_name FROM users WHERE user_id=11')->fetchColumn() === 'Original 11', 'Submitted foreign owner ignored');
    Session::put('auth_user', ['id'=>11,'role'=>'RECYCLER']);
    try { (new RecyclerLicenseUpdateService())->submit(10, [], []); throw new RuntimeException('Cross-owner licence submission accepted'); }
    catch (InvalidArgumentException) { $assert(true, 'Licence service independently rejects cross-owner submission'); }
    // Basic PDF containment/format checks never open a public upload location.
    $validator = new RecyclerLicenseDocumentValidator();
    $assert($validator->validatedPath($pdf) !== null, 'Synthetic private PDF accepted by shared validator');
    foreach ([null, '', '../../config/database.php', 'uploads/recycler-licenses/missing-final-qa.pdf'] as $badPath) {
        $assert($validator->validatedPath($badPath) === null, 'Invalid or escaping document path rejected');
    }
    foreach (["not a PDF", "%PDF-1.4\ntruncated", "%PDF-1.4\n" . str_repeat('X', 5 * 1024 * 1024) . "\n%%EOF"] as $badPdf) {
        $relative = 'uploads/recycler-licenses/profile-security-' . bin2hex(random_bytes(10)) . '.pdf';
        $path = APP_ROOT . '/storage/' . $relative;
        $paths[] = $path;
        file_put_contents($path, $badPdf);
        $assert($validator->validatedPath($relative) === null, 'Invalid/truncated/oversized PDF rejected');
    }
    $db->exec("UPDATE users SET role='PUBLIC_USER' WHERE user_id=1");
    $result = $run(['role'=>'ADMIN','id'=>1,'action'=>'pdf']);
    $assert($result['status'] === 403 && !$result['pdf'], 'Revoked DB role defeats cached Admin role');
    $db->exec("UPDATE users SET role='ADMIN' WHERE user_id=1");
    // Login cases use only synthetic identities, never deliver SMS, and never call OTP endpoints.
    foreach (['VERIFIED','PENDING','REJECTED','SUSPENDED'] as $verification) {
        $setState($verification);
        Session::forget('auth_user');
        $_SERVER['REMOTE_ADDR'] = 'profile-security-' . $name;
        $_POST = ['_csrf_token'=>Csrf::token(),'mobile_number'=>$testMobile,'password'=>$password];
        http_response_code(200); $controller = new SecurityLoginController();
        try { $controller->submitLogin(); } catch (SecurityRedirect $redirect) { $assert($redirect->getMessage()==='/recycler/dashboard', 'Verified login destination'); }
        $assert($verification === 'VERIFIED' ? Auth::check() : (!Auth::check() && http_response_code() === 403), 'Actual login enforces verification ' . $verification);
    }
    foreach (['SUSPENDED','DISABLED'] as $account) {
        $setState('VERIFIED',$account); Session::forget('auth_user');
        $_POST = ['_csrf_token'=>Csrf::token(),'mobile_number'=>$testMobile,'password'=>$password];
        http_response_code(200); (new SecurityLoginController())->submitLogin();
        $assert(http_response_code() === 403 && !Auth::check(), 'Actual login denies account ' . $account);
    }
    // Suspended/rejected users without a verification timestamp cannot enter OTP setup.
    $db->exec('UPDATE users SET mobile_verified_at=NULL WHERE user_id=10');
    foreach (['REJECTED','SUSPENDED'] as $verification) {
        $setState($verification); Session::forget('auth_user'); Session::forget('pending_verification_user_id');
        $_POST = ['_csrf_token'=>Csrf::token(),'mobile_number'=>$testMobile,'password'=>$password];
        http_response_code(200); (new SecurityLoginController())->submitLogin();
        $assert(http_response_code() === 403 && !Session::has('pending_verification_user_id'), 'Denied unverified account never enters OTP setup: ' . $verification);
    }
    $setState('PENDING', 'PENDING'); Session::forget('auth_user');
    $_POST = ['_csrf_token'=>Csrf::token(),'mobile_number'=>$testMobile,'password'=>$password];
    try { (new SecurityLoginController())->submitLogin(); throw new RuntimeException('Registration verification redirect missing'); }
    catch (SecurityRedirect $redirect) { $assert($redirect->getMessage() === '/verify-mobile', 'Original pending registration OTP redirect preserved'); }
    $assert(Session::get('pending_verification_user_id') === 10 && !Auth::check(), 'Pending registration sets verification context without authenticating or sending SMS');
    MobileVerificationSession::clear();
    echo "PASS: $checks profile authorization/contact/CSRF/document/login assertions; disposable DB only\n";
} finally {
    LicenceCoverageFixture::cleanup();
    foreach ($paths as $path) if (is_file($path)) unlink($path);
    if ($db->inTransaction()) $db->rollBack();
    if ($created) { $db->exec("DROP DATABASE `$name`"); echo "Removed disposable database $name\n"; }
}
