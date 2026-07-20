<?php

require_once "../app/Core/Controller.php";
require_once "../app/Core/Router.php";


$router = new Router();


$router->get(
    "/officer/dashboard",
    "MunicipalOfficerController@dashboard"
);

$router->get(
    "/officer/campaigns",
    "MunicipalOfficerController@campaigns"
);

$router->get(
    "/officer/area-schedules",
    "MunicipalOfficerController@areaSchedules"
);

$router->get(
    "/officer/flagged-requests",
    "MunicipalOfficerController@flaggedRequests"
);

$router->get(
    "/officer/routes",
    "MunicipalOfficerController@routes"
);

$router->get(
    "/officer/collection-records",
    "MunicipalOfficerController@collectionRecords"
);


$uri = $_SERVER['REQUEST_URI'];

$uri = str_replace(
    "/EcoLot-LK/public",
    "",
    $uri
);


$router->dispatch($uri);