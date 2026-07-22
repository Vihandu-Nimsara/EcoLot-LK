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

$router->get(
    "/officer/e-lots",
    "MunicipalOfficerController@eLots"
);

$router->get(
    "/officer/feedback",
    "MunicipalOfficerController@feedback"
);


//Recycler routes

$router->get(
    "/recycler/dashboard",
    "RecyclerController@dashboard"
);

$router->get(
    "/recycler/eligible_e-lots",
    "RecyclerController@Eligible_ELots"
);

$router->get(
    "/recycler/my_bids",
    "RecyclerController@My_Bids"
);

$router->get(
    "/recycler/awarded_e-lots",
    "RecyclerController@Awarded_ELots"
);


$uri = $_SERVER['REQUEST_URI'];

$uri = str_replace(
    "/EcoLot-LK/public",
    "",
    $uri
);


$router->dispatch($uri);