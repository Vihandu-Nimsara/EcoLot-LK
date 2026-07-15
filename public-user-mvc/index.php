<?php
// index.php
// Central router (Front Controller)

// Start session if needed for future authentication/session usage
session_start();

// Include database connection
require_once __DIR__ . '/config/db.php';

// Include Models
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Request.php';
require_once __DIR__ . '/models/Feedback.php';

// Include Controllers
require_once __DIR__ . '/controllers/DashboardController.php';
require_once __DIR__ . '/controllers/ProfileController.php';
require_once __DIR__ . '/controllers/RequestController.php';
require_once __DIR__ . '/controllers/FeedbackController.php';

// Fetch the 'route' parameter, defaulting to 'dashboard'
$route = isset($_GET['route']) ? $_GET['route'] : 'dashboard';

// Route the request to the correct controller and method
switch ($route) {
    case 'dashboard':
        $controller = new DashboardController();
        $controller->index();
        break;

    case 'profile':
        $controller = new ProfileController();
        $controller->index();
        break;

    case 'my-requests':
        $controller = new RequestController();
        $controller->myRequests();
        break;

    case 'new-request':
        $controller = new RequestController();
        $controller->newRequest();
        break;

    case 'new-request-submit':
        $controller = new RequestController();
        $controller->submit();
        break;

    case 'feedback':
        $controller = new FeedbackController();
        $controller->index();
        break;

    case 'feedback-submit':
        $controller = new FeedbackController();
        $controller->submit();
        break;

    default:
        // Page not found: default to dashboard
        $controller = new DashboardController();
        $controller->index();
        break;
}
