<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

$app = require APP_ROOT . '/config/app.php';

ini_set('display_errors', $app['debug'] ? '1' : '0');
ini_set('log_errors', '1');
set_exception_handler(static function (Throwable $exception) use ($app): void {
    http_response_code(500);
    error_log('Unhandled application error: ' . $exception::class);
    echo $app['debug']
        ? htmlspecialchars((string) $exception, ENT_QUOTES, 'UTF-8')
        : 'An unexpected error occurred. Please try again later.';
});
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');


spl_autoload_register(static function (string $class): void {
    $directories = [
        APP_ROOT . '/app/Core/',
        APP_ROOT . '/app/Controllers/',
        APP_ROOT . '/app/Models/',
        APP_ROOT . '/app/Services/',
    ];

    foreach ($directories as $directory) {
        $file = $directory . $class . '.php';

        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

$router = new Router((string) ($app['base_path'] ?? ''));

$registerRoutes = require APP_ROOT . '/routes/web.php';
$registerRoutes($router, $app);

$router->dispatch();
