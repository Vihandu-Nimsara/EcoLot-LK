<?php
declare(strict_types=1);

// Web routes will be registered here.

// 1. Municipal Officer Routes
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
$router->get(
    "/officer/reports",
    "MunicipalOfficerController@reports"
);

// 2. Public User (Customer) Routes
$router->get(
    "/user/dashboard",
    "PublicUserController@dashboard"
);
$router->get(
    "/user/profile",
    "PublicUserController@profile"
);
$router->get(
    "/user/my-requests",
    "PublicUserController@myRequests"
);
$router->get(
    "/user/new-request",
    "PublicUserController@newRequest"
);
$router->get(
    "/user/feedback",
    "PublicUserController@feedback"
);

// Form Actions (POST handlers)
$router->get(
    "/user/new-request-submit",
    "PublicUserController@submitRequest"
);
$router->get(
    "/user/feedback-submit",
    "PublicUserController@submitFeedback"
);
