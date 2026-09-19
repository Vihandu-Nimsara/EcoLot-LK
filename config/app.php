<?php
declare(strict_types=1);

return [
    'name' => 'EcoLot LK',
    'environment' => getenv('APP_ENV') ?: 'local',
    'debug' => filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOL),

    'base_path' => getenv('APP_BASE_PATH') !== false ? getenv('APP_BASE_PATH') : '/EcoLot-LK/public',

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
