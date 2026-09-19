<?php
declare(strict_types=1);

/** Atomic, shared across PHP workers on a single application host. */
final class LoginRateLimiter
{
    public function __construct(private ?string $path = null)
    {
        $this->path ??= APP_ROOT . '/storage/login-attempts.json';
    }

    public function consume(string $address, string $mobile, ?int $now = null): int
    {
        $now ??= time();
        $handle = fopen($this->path, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Login rate-limit storage is unavailable.');
        }
        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Login rate-limit storage cannot be locked.');
            }
            $raw = stream_get_contents($handle);
            $entries = $raw === '' ? [] : json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($entries)) {
                throw new RuntimeException('Invalid login rate-limit storage.');
            }
            $entries = array_filter($entries, static fn (array $entry): bool => $entry['expires'] > $now);
            $limits = [hash('sha256', 'ip:' . $address) => 20, hash('sha256', 'mobile:' . $mobile) => 10];
            $retryAfter = 0;
            foreach ($limits as $key => $limit) {
                if (($entries[$key]['count'] ?? 0) >= $limit) {
                    $retryAfter = max($retryAfter, $entries[$key]['expires'] - $now);
                }
            }
            if ($retryAfter > 0) {
                return $retryAfter;
            }
            foreach ($limits as $key => $limit) {
                $entries[$key] ??= ['count' => 0, 'expires' => $now + 900];
                $entries[$key]['count']++;
            }
            $json = json_encode($entries, JSON_THROW_ON_ERROR);
            rewind($handle);
            if (!ftruncate($handle, 0) || fwrite($handle, $json) !== strlen($json) || !fflush($handle)) {
                throw new RuntimeException('Login rate-limit storage cannot be saved.');
            }
            return 0;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
