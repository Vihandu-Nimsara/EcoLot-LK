<?php
declare(strict_types=1);
require __DIR__ . '/run.php';

// Exercise lock ordering and rollback without modifying the application database.
final class OtpTestConnection extends PDO
{
    public array $events = [];
    public ?array $challenge = null;
    public bool $active = false;
    public function __construct() {}
    public function beginTransaction(): bool { $this->events[] = 'begin'; return $this->active = true; }
    public function inTransaction(): bool { return $this->active; }
    public function commit(): bool { $this->events[] = 'commit'; $this->active = false; return true; }
    public function rollBack(): bool { $this->events[] = 'rollback'; $this->active = false; return true; }
    public function lastInsertId(?string $name = null): string|false { return '7'; }
    public function prepare(string $query, array $options = []): PDOStatement|false {
        return new OtpTestStatement($this, $query);
    }
}
final class OtpTestStatement extends PDOStatement
{
    public function __construct(private OtpTestConnection $connection, private string $sql) {}
    public function execute(?array $params = null): bool { $this->connection->events[] = $this->sql; return true; }
    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $orientation = PDO::FETCH_ORI_NEXT, int $offset = 0): mixed {
        return $this->connection->challenge ?? false;
    }
    public function fetchColumn(int $column = 0): mixed { return 0; }
    public function rowCount(): int { return 1; }
}
$db = new OtpTestConnection();
$service = new OtpService($db);
$challenge = $service->issueRegistrationOtp(1);
check(preg_match('/^\d{6}$/', $challenge['otp']) === 1, 'OTP format');
check($db->events[0] === 'begin' && str_contains($db->events[1], 'FOR UPDATE'), 'Issue locks before rate checks');
check(end($db->events) === 'commit', 'Issue commits');
$db->events = [];
$db->challenge = ['sent_at' => (new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format('Y-m-d H:i:s')];
try {
    $service->issueRegistrationOtp(1);
    throw new RuntimeException('Cooldown was not enforced');
} catch (OtpRateLimitException) {
    check(end($db->events) === 'rollback', 'Cooldown failure rolls back');
}
$db->challenge = null;
$db->events = [];
$db->beginTransaction();
$result = $service->verifyRegistrationOtp(1, '123456');
check(!$result['success'] && str_contains($db->events[1], 'FOR UPDATE'), 'Verify locks before reading challenge');
$db->rollBack();
try {
    $service->verifyRegistrationOtp(1, '123456');
    throw new RuntimeException('Verification without transaction was accepted');
} catch (LogicException) {}
echo "PASS: OTP transaction and lock ordering (PDO double; MySQL concurrency not exercised)\n";
