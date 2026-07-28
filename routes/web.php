<?php
declare(strict_types=1);

<<<<<<< HEAD
// Web routes will be registered here.
=======
return static function (Router $router, array $app): void {
    $router->get('/', 'HomeController@index');
    $router->get('/login', 'AuthController@login');
    $router->get('/register', 'AuthController@register');

    foreach ($app['roles'] ?? [] as $role) {
        if (!isset($role['route_prefix'], $role['controller'])) {
            continue;
        }

        $router->get(
            rtrim((string) $role['route_prefix'], '/') . '/dashboard',
            $role['controller'] . '@dashboard'
        );
    }

    $router->group('/officer', static function (Router $router): void {
        $router->get('/campaigns', 'MunicipalOfficerController@campaigns');
        $router->get('/area-schedules', 'MunicipalOfficerController@areaSchedules');
        $router->get('/flagged-requests', 'MunicipalOfficerController@flaggedRequests');
        $router->get('/routes', 'MunicipalOfficerController@routes');
        $router->get('/collection-records', 'MunicipalOfficerController@collectionRecords');
        $router->get('/e-lots', 'MunicipalOfficerController@eLots');
        $router->get('/feedback', 'MunicipalOfficerController@feedback');
        $router->get('/reports', 'MunicipalOfficerController@reports');
    });
};
>>>>>>> 09eed93399941da7a677135bf2a86e7394892ff0
