<?php
// controllers/FeedbackController.php

class FeedbackController {
    public function index() {
        // Assume user ID = 1
        $userId = 1;

        // Fetch requests for the dropdown selection
        $requests = Request::getAllByUser($userId);
        
        // Define active menu item
        $activePage = 'feedback';

        // Load the view
        require_once __DIR__ . '/../views/feedback.php';
    }

    public function submit() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = 1; // Assume user ID = 1

            $data = [
                'user_id' => $userId,
                'feedback_type' => isset($_POST['feedback_type']) ? trim($_POST['feedback_type']) : 'Complaint',
                'request_id' => isset($_POST['request_id']) ? trim($_POST['request_id']) : null,
                'message' => isset($_POST['message']) ? trim($_POST['message']) : ''
            ];

            Feedback::create($data);

            // Redirect back to dashboard with success query parameter
            header('Location: index.php?route=dashboard&feedback_success=1');
            exit;
        }
    }
}
