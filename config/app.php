<?php
declare(strict_types=1);

return [
    'name' => 'EcoLot LK',
    'environment' => 'local',
    'debug' => true,

    'base_path' => '/EcoLot-LK/public',

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
];
