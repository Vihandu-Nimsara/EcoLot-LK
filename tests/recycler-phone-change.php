<?php
declare(strict_types=1);

// Explicit opt-in; creates and drops ONLY its own random disposable database.
// ECOLOT_PHONE_TEST=1 php -d session.save_path=/tmp tests/recycler-phone-change.php
require dirname(__DIR__) . '/app/autoload.php';
if (getenv('ECOLOT_PHONE_TEST') !== '1') {
    fwrite(STDERR, "Requires ECOLOT_PHONE_TEST=1; no application database is used.\n");
    exit(1);
}
Session::start();
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$config = require APP_ROOT . '/config/database.php';
$connect = static function (?string $database = null) use ($config): PDO {
    if ($database !== null && preg_match('/\Aecolot_test_phone_request_[0-9a-f]{16}\z/', $database) !== 1) {
        throw new RuntimeException('Refusing a non-disposable database.');
    }
    $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $config['host'], $config['port']);
    if ($database !== null) $dsn .= ';dbname=' . $database;
    $db = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $db->exec("SET time_zone = '+05:30'");
    $db->exec('SET timestamp = 1893456000');
    return $db;
};

// Independent process for the destination quota race; always uses a fake sender.
if (($argv[1] ?? '') === '--verify-worker') {
    $worker = $connect($argv[2] ?? '');
    $payload = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
    Session::put('auth_user', ['id' => $payload['id'], 'role' => 'RECYCLER']);
    Session::put('recycler_phone_change_context', $payload['context']);
    $workerLimiter = tempnam(sys_get_temp_dir(), 'ecolot-phone-verify-');
    try {
        $service = new RecyclerPhoneChangeService($worker, static function (): void {}, new LoginRateLimiter($workerLimiter));
        echo "READY\n";
        flush();
        try { $service->verifyChange($payload['id'], $payload['otp']); echo "VERIFIED\n"; }
        catch (DomainException) { echo "REJECTED\n"; }
    } finally { unlink($workerLimiter); }
    exit;
}

if (($argv[1] ?? '') === '--worker') {
    $worker = $connect($argv[2] ?? '');
    Session::put('auth_user', ['id' => 2, 'role' => 'RECYCLER']);
    $workerLimiter = tempnam(sys_get_temp_dir(), 'ecolot-phone-worker-');
    try {
        $service = new RecyclerPhoneChangeService($worker, static function (): void {}, new LoginRateLimiter($workerLimiter));
        echo "READY\n";
        flush();
        try {
            $service->startChange(2, ' PhoneTest123! ', '0779000001');
            echo "SENT\n";
        } catch (OtpRateLimitException) {
            echo "LIMITED\n";
        }
    } finally { unlink($workerLimiter); }
    exit;
}

$checks = 0;
final class PhoneTestRedirect extends RuntimeException {}
final class PhoneTestController extends RecyclerController
{
    public bool $rotationFails = false;
    public function __construct(private RecyclerPhoneChangeService $service) {}
    protected function phoneChangeService(): RecyclerPhoneChangeService { return $this->service; }
    protected function rotatePhoneChangeSession(): bool { return !$this->rotationFails && parent::rotatePhoneChangeSession(); }
    protected function redirect(string $path, int $status = 303): never { throw new PhoneTestRedirect($path, $status); }
}
final class PhoneFaultStatement extends PDOStatement
{
    public function __construct(private string $mode) {}
    public function execute(?array $params = null): bool {
        if ($this->mode === 'throw') throw new RuntimeException('Injected lock release failure');
        return true;
    }
    public function fetchColumn(int $column = 0): mixed { return 0; }
}
/** Real MariaDB transactions with faults only at the requested cleanup boundaries. */
final class PhoneFaultConnection extends PDO
{
    public string $releaseFault = '';
    public bool $rollbackFault = false;
    public string $commitFault = '';
    public bool $killAttempted = false;
    public function prepare(string $query, array $options = []): PDOStatement|false {
        if ($this->releaseFault !== '' && str_contains($query, 'SELECT RELEASE_LOCK')) return new PhoneFaultStatement($this->releaseFault);
        return parent::prepare($query, $options);
    }
    public function commit(): bool {
        if ($this->commitFault === 'before') throw new RuntimeException('Injected missing commit acknowledgement');
        $committed = parent::commit();
        if ($this->commitFault === 'after') throw new RuntimeException('Injected lost commit acknowledgement');
        return $committed;
    }
    public function rollBack(): bool {
        if ($this->rollbackFault) throw new RuntimeException('Injected rollback failure');
        return parent::rollBack();
    }
    public function exec(string $statement): int|false {
        if (str_starts_with($statement, 'KILL CONNECTION ')) $this->killAttempted = true;
        return parent::exec($statement);
    }
}
final class PhoneTestAuthController extends AuthController
{
    protected function redirect(string $path, int $status = 303): never { throw new PhoneTestRedirect($path, $status); }
}
$assert = static function (bool $ok, string $message) use (&$checks): void {
    if (!$ok) throw new RuntimeException($message);
    ++$checks;
};
$reject = static function (callable $action, string $class, string $message = '') use ($assert): Throwable {
    try { $action(); } catch (Throwable $error) {
        $assert($error instanceof $class, 'Expected ' . $class . ', got ' . $error::class);
        if ($message !== '') $assert($error->getMessage() === $message, 'Unexpected rejection message');
        return $error;
    }
    throw new RuntimeException('Expected rejection: ' . $class);
};
$database = 'ecolot_test_phone_request_' . bin2hex(random_bytes(8));
$db = $connect();
$created = false;
$limiterFile = tempnam(sys_get_temp_dir(), 'ecolot-phone-limits-');
$process = null;
try {
    $db->exec("CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $db->exec("USE `$database`");
    $schema = file_get_contents(APP_ROOT . '/database/schema.sql');
    foreach (['users', 'administrators', 'authorized_recyclers', 'recycler_phone_change_challenges'] as $table) {
        if (preg_match('/CREATE TABLE IF NOT EXISTS `' . $table . '` \([\s\S]*?;/', $schema, $match) !== 1) {
            throw new RuntimeException('Missing table definition: ' . $table);
        }
        $db->exec($match[0]);
    }
    $passwordHash = password_hash(' PhoneTest123! ', PASSWORD_DEFAULT);
    $insertUser = $db->prepare("INSERT INTO users
        (user_id, full_name, mobile_number, password_hash, role, account_status, mobile_verified_at)
        VALUES (?, 'Test recycler', ?, ?, 'RECYCLER', 'ACTIVE', '2020-01-01')");
    $insertRecycler = $db->prepare("INSERT INTO authorized_recyclers
        (user_id, company_name, business_address, district) VALUES (?, ?, 'Test address', 'Colombo')");
    for ($id = 1; $id <= 3; ++$id) {
        $insertUser->execute([$id, '9477000000' . $id, $passwordHash]);
        $insertRecycler->execute([$id, 'Test company ' . $id]);
    }
    $db->exec("INSERT INTO users (user_id, full_name, mobile_number, password_hash, role, account_status)
        VALUES (99, 'Fixture Admin', '94779999999', 'unused', 'ADMIN', 'ACTIVE')");
    $db->exec('INSERT INTO administrators VALUES (99)');
    $db->exec("UPDATE authorized_recyclers SET verification_status='VERIFIED', reviewed_by_admin_user_id=99, reviewed_at=CURRENT_TIMESTAMP");
    $observer = $connect($database);
    $baseline = $db->query('SELECT * FROM users ORDER BY user_id')->fetchAll();
    $reset = static function () use ($db, $limiterFile): void {
        // Fixture reset between independent cases, never an application cleanup path.
        $db->exec('DELETE FROM recycler_phone_change_challenges');
        file_put_contents($limiterFile, '');
        Session::put('auth_user', ['id' => 1, 'role' => 'RECYCLER']);
        Session::forget('recycler_phone_change_context');
    };
    $sent = [];
    $sender = static function (string $mobile, string $otp) use ($db, $observer, &$sent, $assert, $database): void {
        $assert(!$db->inTransaction(), 'SMS must run outside the reservation transaction');
        $row = $observer->query('SELECT * FROM recycler_phone_change_challenges ORDER BY challenge_id DESC LIMIT 1')->fetch();
        $assert($row !== false && $row['delivery_status'] === 'PENDING', 'Reservation visible to another connection before SMS');
        $assert(preg_match('/\A[0-9]{6}\z/', $otp) === 1 && password_verify($otp, $row['otp_hash']), 'Secure six-digit OTP matches stored hash');
        $assert(!in_array($otp, array_values($row), true), 'No plaintext OTP in any challenge column');
        $context = Session::get('recycler_phone_change_context');
        $assert(strlen($context['token']) === 64 && hash('sha256', $context['token']) === $row['session_context_hash'], 'Session binding stores only token hash');
        $assert($row['session_context_hash'] !== $context['token'], 'No plaintext context token in database');
        $name = 'ecolot:phone:' . substr(hash('sha256', $database . ':' . $mobile), 0, 48);
        $lock = $observer->prepare('SELECT GET_LOCK(?, 0)');
        $lock->execute([$name]);
        $assert((int) $lock->fetchColumn() === 1, 'Destination lock released before SMS');
        $release = $observer->prepare('SELECT RELEASE_LOCK(?)');
        $release->execute([$name]);
        $sent[] = [$mobile, $otp];
    };
    $service = new RecyclerPhoneChangeService($db, $sender, new LoginRateLimiter($limiterFile));
    $start = static fn (string $number = '0779000001', string $password = ' PhoneTest123! ') => $service->startChange(1, $password, $number);
    $reset();
    $result = $start();
    $row = $db->query('SELECT * FROM recycler_phone_change_challenges')->fetch();
    $assert($row['original_mobile_number'] === '94770000001' && $row['proposed_mobile_number'] === '94779000001', 'Account and normalized number binding');
    $assert($row['password_state_fingerprint'] === hash('sha256', $passwordHash), 'Password state bound');
    $assert($row['delivery_status'] === 'SENT' && $row['sent_at'] !== null && $row['invalidated_at'] === null, 'SMS acceptance persisted');
    $assert($result['challenge_id'] === (int) $row['challenge_id'] && !isset($result['otp']) && !isset($result['token']), 'Return metadata contains no secrets');
    $assert((int) $db->query('SELECT TIMESTAMPDIFF(SECOND, created_at, expires_at) FROM recycler_phone_change_challenges')->fetchColumn() === 300, 'Five-minute expiry');
    $reject($start, OtpRateLimitException::class);
    $assert(count($sent) === 1, 'Cooldown prevents a second SMS');

    foreach ([['0779000001', 'wrong', 'Your current password is incorrect.'],
              ['bad number', ' PhoneTest123! ', 'Enter a valid Sri Lankan mobile number.'],
              ['0770000001', ' PhoneTest123! ', 'Enter a different mobile number.'],
              ['0770000002', ' PhoneTest123! ', 'That mobile number is unavailable.'],
              ['0779000001', 'PhoneTest123!', 'Your current password is incorrect.']] as [$number, $password, $message]) {
        $reset();
        $reject(fn () => $start($number, $password), DomainException::class, $message);
        $assert((int) $db->query('SELECT COUNT(*) FROM recycler_phone_change_challenges')->fetchColumn() === 0, 'Validation failure creates no challenge');
        $assert(!$db->inTransaction(), 'Rejected transaction rolled back');
    }
    $reset();
    $reject(fn () => $service->startChange(2, ' PhoneTest123! ', '0779000001'), DomainException::class);
    $db->exec("UPDATE users SET account_status = 'SUSPENDED' WHERE user_id = 1");
    $reject($start, DomainException::class);
    $db->exec("UPDATE users SET account_status = 'ACTIVE', role = 'PUBLIC_USER' WHERE user_id = 1");
    $reject($start, DomainException::class);
    $db->exec("UPDATE users SET role = 'RECYCLER' WHERE user_id = 1");
    $db->beginTransaction();
    $reject($start, LogicException::class);
    $assert($db->inTransaction(), 'Service does not rollback caller-owned transaction');
    $db->rollBack();

    $reset();
    $failedSender = new RecyclerPhoneChangeService($db, static function (string $mobile, string $otp): void {
        throw new RuntimeException('Secret gateway diagnostic: ' . $otp);
    }, new LoginRateLimiter($limiterFile));
    $error = $reject(fn () => $failedSender->startChange(1, ' PhoneTest123! ', '0779000001'), RuntimeException::class, 'The verification SMS could not be sent. Please try again.');
    $assert($error->getPrevious() === null, 'Gateway secrets not chained into public exception');
    $row = $db->query('SELECT * FROM recycler_phone_change_challenges')->fetch();
    $assert($row['delivery_status'] === 'FAILED' && $row['invalidated_at'] !== null && $row['sent_at'] === null, 'Failure retained and invalidated');
    $assert(!Session::has('recycler_phone_change_context'), 'Failed request clears context');
    $reject($start, OtpRateLimitException::class);

    $reset();
    $lateFailure = new RecyclerPhoneChangeService($db, static function (): void {
        Session::put('recycler_phone_change_context', ['challenge_id' => 999999, 'token' => 'newer-session-context']);
        throw new RuntimeException('Late gateway failure');
    }, new LoginRateLimiter($limiterFile));
    $reject(fn () => $lateFailure->startChange(1, ' PhoneTest123! ', '0779000001'), RuntimeException::class);
    $assert(Session::get('recycler_phone_change_context')['challenge_id'] === 999999, 'Late failure cannot clear a newer request context');

    // Seed historical terminal reservations without changing the production limits.
    $seed = static function (int $owner, string $mobile, int $age) use ($db, $passwordHash): void {
        $statement = $db->prepare("INSERT INTO recycler_phone_change_challenges
            (recycler_user_id, original_mobile_number, proposed_mobile_number, otp_hash,
             session_context_hash, password_state_fingerprint, created_at, expires_at, invalidated_at)
            VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP - INTERVAL ? SECOND,
                    CURRENT_TIMESTAMP + INTERVAL 300 SECOND, CURRENT_TIMESTAMP)");
        $statement->execute([$owner, '9477000000' . $owner, $mobile, $passwordHash, hash('sha256', 'fixture'), hash('sha256', $passwordHash), $age]);
    };
    foreach ([[1, 5, 61], [1, 10, 7200], [2, 5, 61], [2, 10, 7200]] as [$owner, $count, $age]) {
        $reset();
        for ($i = 0; $i < $count; ++$i) $seed($owner, $owner === 1 ? '94779000002' : '94779000001', $age + $i);
        $before = count($sent);
        $reject($start, OtpRateLimitException::class);
        $assert(count($sent) === $before, 'Account/destination hourly/daily quota blocks SMS');
        $assert((int) $db->query('SELECT COUNT(*) FROM recycler_phone_change_challenges')->fetchColumn() === $count, 'Rate limit retains terminal history');
    }
    $reset();
    for ($i = 0; $i < 10; ++$i) $reject(fn () => $start('0779000001', 'incorrect'), DomainException::class);
    $reject($start, OtpRateLimitException::class);
    $assert((int) $db->query('SELECT COUNT(*) FROM recycler_phone_change_challenges')->fetchColumn() === 0, 'Password limiter blocks before reservation');
    $reset();
    $loginLimiter = new LoginRateLimiter($limiterFile);
    for ($i = 0; $i < 10; ++$i) $loginLimiter->consume('127.0.0.1', '94770000001');
    $start();
    $assert($loginLimiter->consume('127.0.0.1', '94770000001') > 0, 'Login counters remain isolated from phone confirmations');

    $reset();
    $old = $start()['challenge_id'];
    $db->exec("UPDATE recycler_phone_change_challenges SET created_at = CURRENT_TIMESTAMP - INTERVAL 60 SECOND WHERE challenge_id = $old");
    $new = $start()['challenge_id'];
    $assert($new !== $old && (int) $db->query('SELECT COUNT(*) FROM recycler_phone_change_challenges')->fetchColumn() === 2, 'Replacement creates history at cooldown boundary');
    $assert($db->query("SELECT invalidated_at FROM recycler_phone_change_challenges WHERE challenge_id = $old")->fetchColumn() !== null, 'Previous active challenge invalidated');
    $assert((int) $db->query('SELECT COUNT(active_recycler_user_id) FROM recycler_phone_change_challenges')->fetchColumn() === 1, 'Exactly one unresolved challenge');

    // Hold the destination gate while a second account starts the fifth-send race.
    $reset();
    for ($i = 0; $i < 4; ++$i) $seed(3, '94779000001', 61 + $i);
    $lockName = 'ecolot:phone:' . substr(hash('sha256', $database . ':94779000001'), 0, 48);
    $lock = $db->prepare('SELECT GET_LOCK(?, 0)');
    $lock->execute([$lockName]);
    $assert((int) $lock->fetchColumn() === 1, 'Race gate acquired');
    $process = proc_open([PHP_BINARY, '-d', 'session.save_path=' . sys_get_temp_dir(), __FILE__, '--worker', $database], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Could not start quota worker');
    fclose($pipes[0]);
    stream_set_timeout($pipes[1], 10);
    $assert(trim((string) fgets($pipes[1])) === 'READY', 'Independent quota worker ready');
    // Parent's nested GET_LOCK releases only its own acquisition on MariaDB.
    $raceService = new RecyclerPhoneChangeService($db, static function (): void {}, new LoginRateLimiter($limiterFile));
    $raceService->startChange(1, ' PhoneTest123! ', '0779000001');
    $release = $db->prepare('SELECT RELEASE_LOCK(?)');
    $release->execute([$lockName]);
    $assert(trim((string) fgets($pipes[1])) === 'LIMITED', 'Concurrent account rechecks destination quota after gate release');
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    $status = proc_close($process);
    $process = null;
    $assert($status === 0 && $errors === '', 'Quota worker completed without errors');
    $assert((int) $db->query('SELECT COUNT(*) FROM recycler_phone_change_challenges')->fetchColumn() === 5, 'Concurrent destination requests cannot exceed hourly quota');
    $assert($db->query('SELECT * FROM users ORDER BY user_id')->fetchAll() === $baseline, 'All users columns, mobile numbers and verified timestamps unchanged');
    // Full lifecycle cases restore only disposable fixtures between scenarios.
    $resetFlow = static function () use ($reset, $db, $passwordHash): void {
        $reset();
        $statement = $db->prepare("UPDATE users SET mobile_number = ?, password_hash = ?, mobile_verified_at = '2020-01-01', role = 'RECYCLER', account_status = 'ACTIVE' WHERE user_id = ?");
        for ($id = 1; $id <= 3; ++$id) $statement->execute(['9477000000' . $id, $passwordHash, $id]);
    };
    $lastOtp = static function () use (&$sent): string { return $sent[array_key_last($sent)][1]; };
    $resetFlow();
    $service->startChange(1, ' PhoneTest123! ', '0779000001');
    $beforeSuspended = count($sent);
    $db->exec("UPDATE authorized_recyclers SET verification_status='SUSPENDED',review_note='Synthetic suspension' WHERE user_id=1");
    $reject(fn() => $service->verifyChange(1, $lastOtp()), DomainException::class);
    $reject(fn() => $service->resend(1), DomainException::class);
    $assert(count($sent) === $beforeSuspended && (new User($db))->find(1)['mobile_number'] === '94770000001', 'Verification suspension blocks phone completion/resend without SMS or number change');
    $db->exec("UPDATE authorized_recyclers SET verification_status='VERIFIED',review_note=NULL WHERE user_id=1");
    $resetFlow();

    $resetFlow();
    $start();
    $assert($service->pendingChange(1)['masked_mobile'] === '9477****001', 'Only safe pending metadata');
    $service->verifyChange(1, $lastOtp());
    $assert((new User($db))->findByMobileNumber('94779000001')['user_id'] === 1 && (new User($db))->findByMobileNumber('94770000001') === null, 'New login identifier replaces old only after verification');
    $assert($db->query('SELECT verified_at FROM recycler_phone_change_challenges')->fetchColumn() !== null, 'Challenge consumed atomically');
    $assert($service->pendingChange(1) === null && !Session::has('recycler_phone_change_context'), 'Success clears session context');
    $reject(fn () => $service->verifyChange(1, $lastOtp()), DomainException::class);

    $resetFlow();
    $start();
    $correct = $lastOtp();
    $wrong = $correct === '111111' ? '222222' : '111111';
    for ($i = 1; $i <= 5; ++$i) {
        $reject(fn () => $service->verifyChange(1, $wrong), DomainException::class);
        $assert((int) $observer->query('SELECT attempt_count FROM recycler_phone_change_challenges')->fetchColumn() === $i, 'Rejected attempts are committed');
    }
    $assert($db->query('SELECT invalidated_at FROM recycler_phone_change_challenges')->fetchColumn() !== null, 'Fifth failure invalidates');
    $reject(fn () => $service->verifyChange(1, $correct), DomainException::class);
    $assert((new User($db))->find(1)['mobile_number'] === '94770000001', 'Lockout leaves number unchanged');

    $resetFlow();
    $start();
    $db->exec('UPDATE recycler_phone_change_challenges SET created_at = CURRENT_TIMESTAMP - INTERVAL 400 SECOND, expires_at = CURRENT_TIMESTAMP - INTERVAL 1 SECOND');
    $reject(fn () => $service->verifyChange(1, $lastOtp()), DomainException::class);
    $assert($db->query('SELECT invalidated_at FROM recycler_phone_change_challenges')->fetchColumn() !== null, 'Expired verification invalidates');

    $resetFlow();
    $start();
    $firstOtp = $lastOtp();
    $firstContext = Session::get('recycler_phone_change_context');
    $reject(fn () => $service->resend(1), OtpRateLimitException::class);
    $db->exec('UPDATE recycler_phone_change_challenges SET created_at = CURRENT_TIMESTAMP - INTERVAL 60 SECOND');
    $service->resend(1);
    $secondOtp = $lastOtp();
    $secondContext = Session::get('recycler_phone_change_context');
    $assert($firstContext['challenge_id'] !== $secondContext['challenge_id'] && $firstContext['token'] !== $secondContext['token'], 'Resend rotates challenge and context');
    $assert((int) $db->query('SELECT COUNT(*) FROM recycler_phone_change_challenges')->fetchColumn() === 2, 'Resend preserves history');
    Session::put('recycler_phone_change_context', $firstContext);
    $reject(fn () => $service->verifyChange(1, $firstOtp), DomainException::class);
    Session::put('recycler_phone_change_context', $secondContext);
    $assert($firstOtp !== $secondOtp, 'Resend never reuses the previous code');
    $reject(fn () => $service->verifyChange(1, $firstOtp), DomainException::class);
    $service->verifyChange(1, $secondOtp);

    $resetFlow();
    $start();
    $context = Session::get('recycler_phone_change_context');
    Session::put('recycler_phone_change_context', array_replace($context, ['token' => 'wrong-context']));
    $reject(fn () => $service->verifyChange(1, $lastOtp()), DomainException::class);
    $reject(fn () => $service->resend(1), DomainException::class);
    $reject(fn () => $service->cancel(1), DomainException::class);
    $assert((int) $db->query('SELECT attempt_count FROM recycler_phone_change_challenges')->fetchColumn() === 0, 'Context mismatch cannot consume guesses');
    Session::put('recycler_phone_change_context', $context);
    $service->cancel(1);
    $assert(!Session::has('recycler_phone_change_context') && $service->pendingChange(1) === null, 'Cancel clears bound context');
    $assert((int) $db->query('SELECT COUNT(*) FROM recycler_phone_change_challenges')->fetchColumn() === 1, 'Cancel keeps quota history');
    $reject($start, OtpRateLimitException::class);

    foreach (['mobile_number', 'password_hash'] as $column) {
        $resetFlow();
        $start();
        $statement = $db->prepare('UPDATE users SET ' . $column . ' = ? WHERE user_id = 1');
        $statement->execute([$column === 'mobile_number' ? '94779999998' : password_hash('ChangedPassword123!', PASSWORD_DEFAULT)]);
        $reject(fn () => $service->verifyChange(1, $lastOtp()), DomainException::class);
        $assert($db->query('SELECT invalidated_at FROM recycler_phone_change_challenges')->fetchColumn() !== null, 'Stale account invalidates pending change');
    }

    $resetFlow();
    $start();
    $db->exec("CREATE TRIGGER reject_phone_consumption BEFORE UPDATE ON recycler_phone_change_challenges FOR EACH ROW BEGIN IF NEW.verified_at IS NOT NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Injected test failure'; END IF; END");
    $reject(fn () => $service->verifyChange(1, $lastOtp()), PDOException::class);
    $assert((new User($db))->find(1)['mobile_number'] === '94770000001' && $db->query('SELECT verified_at FROM recycler_phone_change_challenges')->fetchColumn() === null, 'Consumption failure rolls back user update');
    $db->exec('DROP TRIGGER reject_phone_consumption');
    $db->exec("CREATE TRIGGER reject_phone_consumption BEFORE UPDATE ON recycler_phone_change_challenges FOR EACH ROW BEGIN IF NEW.verified_at IS NOT NULL THEN SIGNAL SQLSTATE '23000' SET MYSQL_ERRNO = 1062, MESSAGE_TEXT = 'Injected duplicate race'; END IF; END");
    $reject(fn () => $service->verifyChange(1, $lastOtp()), DomainException::class, 'That mobile number is unavailable. Please try again.');
    $assert((new User($db))->find(1)['mobile_number'] === '94770000001' && $db->query('SELECT verified_at FROM recycler_phone_change_challenges')->fetchColumn() === null, 'Duplicate-key race rolls back both writes');
    $db->exec('DROP TRIGGER reject_phone_consumption');
    $service->verifyChange(1, $lastOtp());

    $resetFlow();
    $lateService = null;
    $lateService = new RecyclerPhoneChangeService($db, static function () use (&$lateService): void {
        $lateService->cancel(1);
    }, new LoginRateLimiter($limiterFile));
    $lateService->startChange(1, ' PhoneTest123! ', '0779000001');
    $lateRow = $db->query('SELECT * FROM recycler_phone_change_challenges')->fetch();
    $assert($lateRow['delivery_status'] === 'SENT' && $lateRow['invalidated_at'] !== null && $service->pendingChange(1) === null, 'Late gateway acceptance records outcome without reviving cancellation');

    // Competing verified requests serialize on the destination; only one can claim it.
    $resetFlow();
    $start();
    $firstOtp = $lastOtp();
    $firstContext = Session::get('recycler_phone_change_context');
    Session::put('auth_user', ['id' => 2, 'role' => 'RECYCLER']);
    $service->startChange(2, ' PhoneTest123! ', '0779000001');
    $payload = ['id' => 2, 'context' => Session::get('recycler_phone_change_context'), 'otp' => $lastOtp()];
    Session::put('auth_user', ['id' => 1, 'role' => 'RECYCLER']);
    Session::put('recycler_phone_change_context', $firstContext);
    $lock->execute([$lockName]);
    $process = proc_open([PHP_BINARY, '-d', 'session.save_path=' . sys_get_temp_dir(), __FILE__, '--verify-worker', $database], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Could not start verification worker');
    fwrite($pipes[0], json_encode($payload, JSON_THROW_ON_ERROR)); fclose($pipes[0]);
    stream_set_timeout($pipes[1], 10);
    $assert(trim((string) fgets($pipes[1])) === 'READY', 'Concurrent claimant ready');
    $service->verifyChange(1, $firstOtp);
    $release->execute([$lockName]);
    $assert(trim((string) fgets($pipes[1])) === 'REJECTED', 'Concurrent duplicate destination rejected');
    $errors = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
    $status = proc_close($process); $process = null;
    $assert($status === 0 && $errors === '', 'Claim worker cleanly completed');
    $assert((new User($db))->find(1)['mobile_number'] === '94779000001' && (new User($db))->find(2)['mobile_number'] === '94770000002', 'Only one account claims destination');
    // Exercise the real controller actions and their POST/redirect/session contract.
    $resetFlow();
    putenv('DB_DATABASE=' . $database);
    $controller = new PhoneTestController($service);
    $post = static function (string $method, array $payload) use ($controller, $assert): void {
        $_POST = $payload;
        try { $controller->$method(); }
        catch (PhoneTestRedirect $redirect) {
            $assert($redirect->getMessage() === '/recycler/profile' && $redirect->getCode() === 303, 'Profile POST redirects with 303');
            return;
        }
        throw new RuntimeException('Expected profile redirect');
    };
    $_POST = ['_csrf_token' => 'invalid', 'current_password' => ' PhoneTest123! ', 'proposed_mobile_number' => '0779000001'];
    ob_start(); $controller->requestPhoneChange(); $csrfHtml = ob_get_clean();
    $assert(http_response_code() === 403 && str_contains($csrfHtml, '403'), 'Invalid CSRF rejected with HTTP 403');
    $assert((int) $db->query('SELECT COUNT(*) FROM recycler_phone_change_challenges')->fetchColumn() === 0, 'Invalid CSRF cannot request SMS');
    $post('requestPhoneChange', ['_csrf_token' => Csrf::token(), 'current_password' => ['invalid'], 'proposed_mobile_number' => '0779000001']);
    $assert(Session::pullFlash('profile_error') === 'Your current password is incorrect.', 'Array password safely rejected');
    $post('requestPhoneChange', ['_csrf_token' => Csrf::token(), 'user_id' => 2, 'current_password' => ' PhoneTest123! ', 'proposed_mobile_number' => '0779000001']);
    $assert((int) $db->query('SELECT recycler_user_id FROM recycler_phone_change_challenges')->fetchColumn() === 1, 'Controller ignores submitted owner');
    $oldSessionId = session_id();
    $oldCsrf = Csrf::token();
    $post('verifyPhoneChange', ['_csrf_token' => $oldCsrf, 'otp' => $lastOtp()]);
    $assert(session_id() !== $oldSessionId && Csrf::token() !== $oldCsrf && (int) Auth::id() === 1, 'Successful verification rotates session and CSRF while preserving authentication');
    $assert(Session::pullFlash('phone_editor_open') === false && str_contains(Session::pullFlash('profile_success'), 'updated successfully'), 'Success closes editor with feedback');
    $post('updateProfileContact', ['_csrf_token' => Csrf::token(), 'full_name' => 'Updated contact', 'mobile_number' => '94778888888']);
    $assert((new User($db))->find(1)['full_name'] === 'Updated contact' && (new User($db))->find(1)['mobile_number'] === '94779000001', 'Contact Person update remains independent');

    $resetFlow();
    $start();
    $db->exec('UPDATE recycler_phone_change_challenges SET created_at = CURRENT_TIMESTAMP - INTERVAL 60 SECOND');
    $post('resendPhoneChange', ['_csrf_token' => Csrf::token(), 'proposed_mobile_number' => '0778888888']);
    $assert($sent[array_key_last($sent)][0] === '94779000001', 'Resend ignores submitted replacement destination');
    $post('cancelPhoneChange', ['_csrf_token' => Csrf::token()]);
    $assert($service->pendingChange(1) === null && (new User($db))->find(1)['mobile_number'] === '94770000001', 'Controller cancellation retains old number');
    foreach (['request', 'verify', 'resend', 'cancel'] as $action) {
        $router = new Router('');
        (require APP_ROOT . '/routes/web.php')($router, require APP_ROOT . '/config/app.php');
        ob_start(); $router->dispatch('GET', '/recycler/profile/phone/' . $action); ob_end_clean();
        $assert(http_response_code() === 405, 'Phone action is POST-only: ' . $action);
    }

    // Security-fix regressions use independent connections so self-KILL cannot
    // affect the fixture/observer connection or any pre-existing database.
    $faultConnect = static function () use ($config, $database): PhoneFaultConnection {
        $fault = new PhoneFaultConnection(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $database), $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $fault->exec("SET time_zone = '+05:30'");
        $fault->exec('SET timestamp = 1893456000');
        return $fault;
    };
    $faultSender = static function (string $mobile, string $otp) use (&$sent): void { $sent[] = [$mobile, $otp]; };
    $verifyPost = static function (PhoneTestController $target, string $code, string $expectedPath) use ($assert): void {
        $_POST = ['_csrf_token' => Csrf::token(), 'otp' => $code];
        try { $target->verifyPhoneChange(); }
        catch (PhoneTestRedirect $redirect) {
            $assert($redirect->getMessage() === $expectedPath && $redirect->getCode() === 303, 'Security finalization redirect');
            return;
        }
        throw new RuntimeException('Expected security finalization redirect');
    };
    $assertLockFree = static function () use ($observer, $lockName, $assert): void {
        // MariaDB may finish a killed connection's rollback asynchronously.
        $statement = $observer->prepare('SELECT IS_USED_LOCK(?)');
        $until = microtime(true) + 3;
        do {
            $statement->execute([$lockName]);
            $owner = $statement->fetchColumn();
            if ($owner === null) break;
            usleep(10000);
        } while (microtime(true) < $until);
        $assert($owner === null, 'Failed lock release terminates server session and frees named lock');
    };
    foreach (['throw', 'no-ack'] as $releaseFault) {
        $resetFlow();
        $fault = $faultConnect();
        $faultService = new RecyclerPhoneChangeService($fault, $faultSender, new LoginRateLimiter($limiterFile));
        $faultService->startChange(1, ' PhoneTest123! ', '0779000001');
        $fault->releaseFault = $releaseFault;
        $beforeId = session_id(); $beforeCsrf = Csrf::token();
        $verifyPost(new PhoneTestController($faultService), $lastOtp(), '/recycler/profile');
        $assert($fault->killAttempted, 'Connection retirement attempted after release fault');
        $assertLockFree();
        $assert((new User($db))->find(1)['mobile_number'] === '94779000001' && $db->query('SELECT verified_at FROM recycler_phone_change_challenges')->fetchColumn() !== null, 'Confirmed commit remains complete after cleanup failure');
        $assert(!Session::has('recycler_phone_change_context') && session_id() !== $beforeId && Csrf::token() !== $beforeCsrf && Auth::check(), 'Cleanup failure cannot bypass context cleanup or session/CSRF rotation');
        $assert(str_contains(Session::pullFlash('profile_success'), 'updated successfully') && Session::pullFlash('profile_error') === null, 'Confirmed commit is never reported as failure');
        $reject(fn () => $faultService->pendingChange(1), LogicException::class);
    }
    foreach ([false, true] as $rollbackFault) {
        $resetFlow();
        $fault = $faultConnect();
        $faultService = new RecyclerPhoneChangeService($fault, $faultSender, new LoginRateLimiter($limiterFile));
        $faultService->startChange(1, ' PhoneTest123! ', '0779000001');
        $fault->releaseFault = 'throw'; $fault->rollbackFault = $rollbackFault;
        $db->exec("CREATE TRIGGER reject_phone_consumption BEFORE UPDATE ON recycler_phone_change_challenges FOR EACH ROW BEGIN IF NEW.verified_at IS NOT NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Original transaction failure'; END IF; END");
        $failure = $reject(fn () => $faultService->verifyChange(1, $lastOtp()), PDOException::class);
        $assert(str_contains($failure->getMessage(), 'Original transaction failure'), 'Rollback/lock cleanup cannot mask original error');
        $assertLockFree();
        $assert((new User($db))->find(1)['mobile_number'] === '94770000001' && $db->query('SELECT verified_at FROM recycler_phone_change_challenges')->fetchColumn() === null, 'Rollback or forced disconnect leaves neither write committed');
        $db->exec('DROP TRIGGER reject_phone_consumption');
    }

    $resetFlow();
    $fault = $faultConnect();
    $faultService = new RecyclerPhoneChangeService($fault, $faultSender, new LoginRateLimiter($limiterFile));
    $faultService->startChange(1, ' PhoneTest123! ', '0779000001');
    $correctCode = $lastOtp();
    $wrongCode = $correctCode === '111111' ? '222222' : '111111';
    $fault->releaseFault = 'throw';
    $reject(fn () => $faultService->verifyChange(1, $wrongCode), DomainException::class, 'Incorrect code. Attempts remaining: 4.');
    $assertLockFree();
    $assert((int) $db->query('SELECT attempt_count FROM recycler_phone_change_challenges')->fetchColumn() === 1 && Session::has('recycler_phone_change_context'), 'Rejected guess commits counter and retains context despite cleanup failure');
    $assert((new User($db))->find(1)['mobile_number'] === '94770000001', 'Committed rejected guess cannot change number');
    $service->verifyChange(1, $correctCode);
    $reject(fn () => $service->verifyChange(1, $correctCode), DomainException::class);

    $resetFlow();
    $start();
    Session::pullFlash('profile_success'); Session::pullFlash('profile_error');
    $failedRotationController = new PhoneTestController($service);
    $failedRotationController->rotationFails = true;
    $verifyPost($failedRotationController, $lastOtp(), '/login');
    $assert(!Auth::check() && !Session::has('_csrf_token') && !Session::has('recycler_phone_change_context'), 'Rotation failure terminates authentication and destroys old CSRF/context');
    $assert((new User($db))->find(1)['mobile_number'] === '94779000001' && $db->query('SELECT verified_at FROM recycler_phone_change_challenges')->fetchColumn() !== null, 'Rotation failure does not undo or misrepresent committed phone change');
    $assert(Session::pullFlash('profile_success') === null && Session::pullFlash('profile_error') === null, 'No misleading profile feedback after forced logout');
    ob_start(); (new AuthController())->login(); $loginHtml = ob_get_clean();
    $assert(str_contains($loginHtml, 'Your phone number was updated.') && str_contains($loginHtml, 'Sign in again using your updated mobile number.'), 'Guest login page explains committed update and re-login requirement');
    $loginUser = (new User($db))->findByMobileNumber('94779000001');
    $assert($loginUser !== null && password_verify(' PhoneTest123! ', $loginUser['password_hash']) && (new User($db))->findByMobileNumber('94770000001') === null, 'Updated credentials remain valid for re-login; old identifier no longer resolves');
    Auth::login(['id' => (int) $loginUser['user_id'], 'role' => $loginUser['role'], 'name' => $loginUser['full_name']]);
    Csrf::regenerate();
    $assert(Auth::check() && (int) Auth::id() === 1 && Csrf::token() !== '', 'Existing authentication/session APIs can sign the user in again');

    foreach (['before', 'after'] as $commitFault) {
        $resetFlow();
        $fault = $faultConnect();
        $faultService = new RecyclerPhoneChangeService($fault, $faultSender, new LoginRateLimiter($limiterFile));
        $faultService->startChange(1, ' PhoneTest123! ', '0779000001');
        $fault->commitFault = $commitFault;
        $verifyPost(new PhoneTestController($faultService), $lastOtp(), '/login');
        $assert(!Auth::check(), 'Uncertain commit requires re-login');
        $notice = Session::pullFlash('auth_success');
        $assert(str_contains($notice, 'could not confirm') && str_contains($notice, 'new number may already be active'), 'Uncertain commit never claims unchanged phone number');
        $user = (new User($db))->find(1);
        $verified = $db->query('SELECT verified_at FROM recycler_phone_change_challenges')->fetchColumn();
        $assert($commitFault === 'after' ? ($user['mobile_number'] === '94779000001' && $verified !== null) : ($user['mobile_number'] === '94770000001' && $verified === null), 'Lost acknowledgement leaves either both writes or neither, never partial state');
        $assertLockFree();
    }

    // Test the shared Session API's actual false return without changing PHP or
    // installing a production hook: PHP 8 permits redefining a disabled builtin
    // in this one isolated subprocess only.
    $sessionTest = 'function session_regenerate_id(bool $delete_old_session = false): bool { return false; } '
        . 'require ' . var_export(APP_ROOT . '/app/autoload.php', true) . '; '
        . 'Session::put("auth_user", ["id" => 1, "role" => "RECYCLER"]); '
        . 'if (Session::regenerate() !== false) exit(1); Auth::logout(); '
        . 'if ($_SESSION !== [] || session_status() === PHP_SESSION_ACTIVE) exit(2);';
    $child = proc_open([PHP_BINARY, '-d', 'session.save_path=' . sys_get_temp_dir(), '-d', 'disable_functions=session_regenerate_id', '-r', $sessionTest], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $streams);
    if (!is_resource($child)) throw new RuntimeException('Could not start Session API regression');
    fclose($streams[0]); $output = stream_get_contents($streams[1]); $errors = stream_get_contents($streams[2]);
    fclose($streams[1]); fclose($streams[2]);
    $assert(proc_close($child) === 0 && $output === '' && $errors === '', 'Shared Session API returns native failure and existing logout destroys authentication');

    // Load the remaining schema only into this disposable database for existing regressions.
    require APP_ROOT . '/tests/support/TestDatabase.php';
    TestDatabase::loadSchema($db);
    foreach (['otp-locking.php', 'recycler-profile-ui.php'] as $regression) {
        $child = proc_open([PHP_BINARY, '-d', 'session.save_path=' . sys_get_temp_dir(), APP_ROOT . '/tests/' . $regression], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $streams);
        if (!is_resource($child)) throw new RuntimeException('Could not start regression');
        fclose($streams[0]); $output = stream_get_contents($streams[1]); $errors = stream_get_contents($streams[2]);
        fclose($streams[1]); fclose($streams[2]); $status = proc_close($child);
        $assert($status === 0, 'Existing regression failed: ' . $regression . ': ' . $errors . $output);
        echo $output;
    }
    echo "PASS: $checks phone-change request/lifecycle/controller assertions; fake SMS only; isolated MariaDB database\n";
} finally {
    if (is_resource($process)) { proc_terminate($process); proc_close($process); }
    if ($db->inTransaction()) $db->rollBack();
    if ($created) {
        $db->exec("DROP DATABASE `$database`");
        echo "Removed disposable database $database\n";
    }
    unlink($limiterFile);
    Session::forget('recycler_phone_change_context');
}
