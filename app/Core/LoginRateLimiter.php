<?php
declare(strict_types=1);

/** Atomic, shared across PHP workers on a single application host. */
final class LoginRateLimiter
{
    public function __construct(private ?string $path = null)
    {
        if ($this->path !== null) return;
        $preferred = APP_ROOT . '/storage/login-attempts.json';
        if (is_file($preferred) ? is_writable($preferred) : is_writable(dirname($preferred))) {
            $this->path = $preferred;
            return;
        }

        // Apache may run as a different user from the repository owner.
        // Keep a stable, private store shared by that user's PHP workers.
        $owner = function_exists('posix_geteuid') ? (string) posix_geteuid() : 'php';
        $temporaryRoot = sys_get_temp_dir();
        // macOS launchers can pass a user's private TMPDIR to Apache.
        if (!is_writable($temporaryRoot) && PHP_OS_FAMILY !== 'Windows') $temporaryRoot = '/tmp';
        $directory = rtrim($temporaryRoot, DIRECTORY_SEPARATOR) . '/ecolot-login-'
            . hash('sha256', APP_ROOT . ':' . $owner);
        if (!is_dir($directory) && !@mkdir($directory, 0700) && !is_dir($directory)) {
            throw new RuntimeException('Login rate-limit runtime directory is unavailable.');
        }
        clearstatcache(true, $directory);
        if (is_link($directory) || !is_writable($directory)
            || (fileperms($directory) & 0077) !== 0
            || (function_exists('posix_geteuid') && fileowner($directory) !== posix_geteuid())) {
            throw new RuntimeException('Login rate-limit runtime directory is not private.');
        }
        $this->path = $directory . '/login-attempts.json';
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
