<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/Core/LoginRateLimiter.php';
$root = sys_get_temp_dir() . '/ecolot-limiter-test-' . bin2hex(random_bytes(6));
mkdir($root, 0700);
mkdir($root . '/storage', 0500);
define('APP_ROOT', $root);
$directory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . '/ecolot-login-'
    . hash('sha256', APP_ROOT . ':' . (function_exists('posix_geteuid') ? (string) posix_geteuid() : 'php'));
try {
    if (is_writable($root . '/storage')) throw new RuntimeException('Run as a non-root user to exercise permissions.');
    for ($i = 0; $i < 10; $i++) {
        if ((new LoginRateLimiter())->consume('127.0.0.1', '94771234567', 1000) !== 0) throw new RuntimeException('Early limit');
    }
    if ((new LoginRateLimiter())->consume('127.0.0.2', '94771234567', 1001) !== 899) throw new RuntimeException('Fallback does not share limits');
    if ((new LoginRateLimiter())->consume('127.0.0.1', '94771234567', 1900) !== 0) throw new RuntimeException('Expiration failed');
    if ((fileperms($directory) & 0077) !== 0) throw new RuntimeException('Runtime directory is not private');
    echo "PASS: unwritable repository storage uses private runtime storage; shared limits and expiration preserved\n";
} finally {
    if (is_file($directory . '/login-attempts.json')) unlink($directory . '/login-attempts.json');
    if (is_dir($directory)) rmdir($directory);
    chmod($root . '/storage', 0700);
    rmdir($root . '/storage'); rmdir($root);
}
