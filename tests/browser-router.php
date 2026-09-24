<?php
// Only for php -S launched by the isolated browser test harness.
if (PHP_SAPI !== 'cli-server' || !str_starts_with(getenv('DB_DATABASE') ?: '', 'ecolot_browser_test_')) {
    http_response_code(404); exit;
}
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (str_starts_with($path, '/assets/')) {
    $file = realpath(dirname(__DIR__) . '/public' . $path);
    if ($file && str_starts_with($file, dirname(__DIR__) . '/public/assets/')) return false;
}
require dirname(__DIR__) . '/public/index.php';
