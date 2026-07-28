<?php
declare(strict_types=1);

return [
    'name' => 'EcoLot LK',
    'environment' => 'local',
<<<<<<< HEAD
=======
    'debug' => true,

    /*
     * URL segment between the domain and public/index.php.
     * Use an empty string when the public directory is the web root.
     */
    'base_path' => '/EcoLot-LK/public',

    /*
     * Adding a new role here automatically registers its dashboard route.
     * Role-specific pages can be added in routes/web.php.
     */
    'roles' => [
        'admin' => [
            'route_prefix' => '/admin',
            'controller' => 'AdminController',
        ],
        'municipal_officer' => [
            'route_prefix' => '/officer',
            'controller' => 'MunicipalOfficerController',
        ],
        'collector' => [
            'route_prefix' => '/collector',
            'controller' => 'CollectorController',
        ],
        'recycler' => [
            'route_prefix' => '/recycler',
            'controller' => 'RecyclerController',
        ],
        'public_user' => [
            'route_prefix' => '/user',
            'controller' => 'PublicUserController',
        ],
    ],
>>>>>>> 09eed93399941da7a677135bf2a86e7394892ff0
];
