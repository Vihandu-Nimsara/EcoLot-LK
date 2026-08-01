<?php
declare(strict_types=1);

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

    $router->group('/recycler', static function (Router $router): void {
        $router->get('/eligible-e-lots', 'RecyclerController@eligibleELots');
        $router->get('/my-bids', 'RecyclerController@myBids');
        $router->get('/awarded-e-lots', 'RecyclerController@awardedELots');
    });

    $router->group('/user', static function (Router $router): void {
        $router->get('/dashboard', 'PublicUserController@dashboard');
        $router->get('/my-requests', 'PublicUserController@myRequests');
        $router->get('/new-request', 'PublicUserController@newRequest');
        $router->get('/feedback', 'PublicUserController@feedback');
        $router->get('/profile', 'PublicUserController@profile');
    });

    $router->group('/collector', static function (Router $router): void {
        $router->get('/my-requests', 'CollectorController@myRequests');
    });

    $router->group('/admin', static function (Router $router): void {
        $router->get('/users', 'AdminController@Users');
        $router->get('/recycler-verification', 'AdminController@recyclerVerification');
        $router->get('/categories-items', 'AdminController@categoriesAndItems');
        $router->get('/risk-rules', 'AdminController@riskRules');
        $router->get('/reports', 'AdminController@reports');
    });
};
