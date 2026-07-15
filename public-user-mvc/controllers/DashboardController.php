<?php
// controllers/DashboardController.php

class DashboardController {
    public function index() {
        // Assume user ID = 1 for the logged-in session in this prototype
        $userId = 1;
        
        $user = User::getById($userId);
        $stats = Request::getStatsByUser($userId);
        $latestRequests = Request::getLatestByUser($userId, 5);

        // Define which menu item is active
        $activePage = 'dashboard';

        // Load the view
        require_once __DIR__ . '/../views/dashboard.php';
    }
}
