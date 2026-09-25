<?php
declare(strict_types=1);

// Shared by the web entry point and CLI tests. No session or database side effects.
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

spl_autoload_register(static function (string $class): void {
    foreach (['Core', 'Controllers', 'Models', 'Services'] as $directory) {
        $file = APP_ROOT . '/app/' . $directory . '/' . $class . '.php';
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});
