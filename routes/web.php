<?php
declare(strict_types=1);

return static function (Router $router, array $app): void {
    $router->get('/', 'HomeController@index');
    $router->get('/login', 'AuthController@login');
    $router->post('/login', 'AuthController@submitLogin');
    $router->post('/logout', 'AuthController@logout');

    $router->get('/register', 'RegistrationController@index');
    $router->get('/register/public', 'RegistrationController@publicForm');
    $router->post('/register/public', 'RegistrationController@submitPublic');
    $router->get('/register/recycler', 'RegistrationController@recyclerForm');
    $router->post('/register/recycler', 'RegistrationController@submitRecycler');
    $router->get('/register/recycler/pending', 'RegistrationController@recyclerPending');

    $router->get('/verify-mobile', 'MobileVerificationController@show');
    $router->post('/verify-mobile', 'MobileVerificationController@verify');
    $router->post('/verify-mobile/resend', 'MobileVerificationController@resend');

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
        $router->post('/area-schedules', 'MunicipalOfficerController@storeAreaSchedule');
        $router->get('/area-schedules/{id}', 'MunicipalOfficerController@showAreaSchedule');
        $router->post('/area-schedules/{id}/update', 'MunicipalOfficerController@updateAreaSchedule');
        $router->post('/area-schedules/{id}/delete', 'MunicipalOfficerController@deleteAreaSchedule');
        $router->get('/flagged-requests', 'MunicipalOfficerController@flaggedRequests');
        $router->get('/routes', 'MunicipalOfficerController@routes');
        $router->get('/collection-records', 'MunicipalOfficerController@collectionRecords');
        $router->get('/e-lots', 'MunicipalOfficerController@eLots');
        $router->get('/feedback', 'MunicipalOfficerController@feedback');
        $router->get('/reports', 'MunicipalOfficerController@reports');
    });

    $router->group('/recycler', static function (Router $router): void {
        $router->get('/eligible-e-lots', 'RecyclerController@eligibleELots');
        $router->get('/e-lot/{id}', 'RecyclerController@eLotDetails');
        $router->get('/my-bids', 'RecyclerController@myBids');
        $router->get('/awarded-e-lots', 'RecyclerController@awardedELots');
        $router->get('/awarded-e-lot/{id}', 'RecyclerController@awardedELotDetails');
        $router->get('/profile', 'RecyclerController@profile');
        $router->get('/reports', 'RecyclerController@reports');
    });

    $router->group('/user', static function (Router $router): void {
        $router->get('/my-requests', 'PublicUserController@myRequests');
        $router->post('/my-requests/{id}/update', 'PublicUserController@updateRequest');
        $router->post('/my-requests/{id}/delete', 'PublicUserController@deleteRequest');
        $router->get('/new-request', 'PublicUserController@newRequest');
        $router->post('/new-request', 'PublicUserController@storeRequest');
        $router->get('/feedback', 'PublicUserController@feedback');
        $router->get('/profile', 'PublicUserController@profile');
    });

    $router->group('/collector', static function (Router $router): void {
        $router->get('/my-requests', 'CollectorController@myRequests');
        $router->get('/initial-request', 'CollectorController@initialRequest');
        $router->get('/e-lots', 'CollectorController@eLots');
    });

    $router->group('/admin', static function (Router $router): void {
        $router->get('/users', 'AdminController@Users');
        $router->get('/recycler-verification', 'AdminController@recyclerVerification');
        $router->get('/recycler-verification/{id}', 'AdminController@recyclerDetails');
        $router->get('/categories-items', 'AdminController@categoriesAndItems');
        $router->get('/risk-rules', 'AdminController@riskRules');
        $router->get('/reports', 'AdminController@reports');
    });
};
