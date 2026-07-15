<?php
// controllers/ProfileController.php

class ProfileController {
    public function index() {
        // Assume user ID = 1
        $userId = 1;

        $user = User::getById($userId);
        
        // Define active menu item
        $activePage = 'profile';

        // Load the view
        require_once __DIR__ . '/../views/profile.php';
    }
}
