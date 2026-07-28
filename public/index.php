<?php
declare(strict_types=1);

<<<<<<< HEAD
// Front controller.
// Application bootstrapping and routing will be added in the next implementation step.
echo 'EcoLot LK MVC starter is ready.';
=======
define('APP_ROOT', dirname(__DIR__));

$app = require APP_ROOT . '/config/app.php';

spl_autoload_register(static function (string $class): void {
    $directories = [
        APP_ROOT . '/app/Core/',
        APP_ROOT . '/app/Controllers/',
        APP_ROOT . '/app/Models/',
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
>>>>>>> 09eed93399941da7a677135bf2a86e7394892ff0
