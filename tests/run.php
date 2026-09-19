<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
spl_autoload_register(static function (string $class): void {
    foreach (['Core', 'Controllers', 'Services', 'Models'] as $directory) {
        $file = APP_ROOT . '/app/' . $directory . '/' . $class . '.php';
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});
function check(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
set_error_handler(static function (int $severity, string $message): never {
    throw new ErrorException($message, 0, $severity);
});
Session::start();
check(ini_get('session.use_strict_mode') === '1', 'Strict sessions');
$cookies = session_get_cookie_params();
check($cookies['httponly'] && $cookies['samesite'] === 'Lax', 'Protected session cookies');
$token = Csrf::token();
check(Csrf::validate($token) && !Csrf::validate('bad') && !Csrf::validate(null), 'CSRF verification');
$_POST = [];
Session::put('auth_user', ['id' => 1, 'role' => 'ADMIN']);
ob_start();
(new AuthController())->logout();
ob_end_clean();
check(http_response_code() === 403 && Auth::check(), 'Invalid logout must preserve session');
$app = require APP_ROOT . '/config/app.php';
foreach ($app['roles'] as $role => $settings) {
    check(Auth::dashboardPath(strtoupper($role)) === $settings['route_prefix'] . '/dashboard', 'Role destination');
}
check(Auth::dashboardPath('INVALID') === null, 'Unknown role');
$router = new Router($app['base_path']);
(require APP_ROOT . '/routes/web.php')($router, $app);
ob_start();
$router->dispatch('GET', $app['base_path'] . '/logout');
ob_end_clean();
check(http_response_code() === 405 && Auth::check(), 'GET logout rejected');
$validator = new Validator();
check(!$validator->validate(['name' => ['bad']], ['name' => ['required', 'min:2']]), 'Array input rejected');
check($validator->validate(['name' => 'Valid'], ['name' => ['required', 'min:2']]), 'Scalar input preserved');
$errors = [];
PasswordPolicy::validate('GoodPass123', 'GoodPass123', $errors);
check($errors === [], 'Valid password accepted');
PasswordPolicy::validate(str_repeat('A', 73), 'other', $errors, 'company_password', 'confirm_password');
check(isset($errors['company_password'], $errors['confirm_password']), 'Unsafe password and mismatch rejected');
$path = tempnam(sys_get_temp_dir(), 'ecolot-limits-');
try {
    $limiter = new LoginRateLimiter($path);
    for ($attempt = 0; $attempt < 10; $attempt++) {
        check($limiter->consume('127.0.0.1', '94771234567', 1000) === 0, 'Allowed attempt');
    }
    check((new LoginRateLimiter($path))->consume('127.0.0.2', '94771234567', 1001) === 899, 'Account limit shared across sessions and IPs');
    check($limiter->consume('127.0.0.1', '94771234567', 1900) === 0, 'Limit expires at boundary');
    for ($attempt = 0; $attempt < 19; $attempt++) {
        check($limiter->consume('127.0.0.1', 'mobile-' . $attempt, 1900) === 0, 'IP allowed attempt');
    }
    check($limiter->consume('127.0.0.1', 'new-mobile', 1900) === 900, 'IP limit stops rotating accounts');
} finally {
    unlink($path);
}
foreach (['admin', 'collector', 'municipal_officer', 'public_user', 'recycler'] as $role) {
    ob_start();
    Session::put('auth_user', ['id' => 1, 'name' => 'Test User', 'role' => strtoupper($role)]);
    $controller = $app['roles'][$role]['controller'];
    (new $controller())->dashboard();
    $html = ob_get_clean();
    check(substr_count($html, '/css/header.css') === 1, 'Shared assets rendered once');
    check(str_contains($html, 'method="post"') && str_contains($html, '_csrf_token'), 'Logout form rendered');
    check(!preg_match('/<a[^>]+href="[^\"]*\/logout"/', $html), 'No GET logout links');
}
Session::put('pending_verification_user_id', 99);
Auth::logout();
check($_SESSION === [] && session_status() !== PHP_SESSION_ACTIVE, 'Logout destroys full session');
echo "PASS: sessions, CSRF, logout, role routing, validation, rate limits, dashboard rendering\n";
