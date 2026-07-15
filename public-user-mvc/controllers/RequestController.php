<?php
// controllers/RequestController.php

class RequestController {
    public function myRequests() {
        // Assume user ID = 1
        $userId = 1;

        $requests = Request::getAllByUser($userId);
        
        // Define active menu item
        $activePage = 'my-requests';

        // Load the view
        require_once __DIR__ . '/../views/my_requests.php';
    }

    public function newRequest() {
        // Assume user ID = 1
        $userId = 1;
        
        $request = null;
        $isEdit = false;

        // Check if we are editing an existing request
        if (isset($_GET['id'])) {
            $request = Request::getById($_GET['id']);
            if ($request && $request['user_id'] == $userId) {
                $isEdit = true;
            } else {
                $request = null;
            }
        }

        // Define active menu item
        $activePage = 'new-request';

        // Load the view
        require_once __DIR__ . '/../views/new_request.php';
    }

    public function submit() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = 1; // Assume user ID = 1

            $data = [
                'user_id' => $userId,
                'postal_code_area' => isset($_POST['postal_code_area']) ? trim($_POST['postal_code_area']) : '',
                'collection_date' => isset($_POST['collection_date']) ? trim($_POST['collection_date']) : '',
                'pickup_address' => isset($_POST['pickup_address']) ? trim($_POST['pickup_address']) : '',
                'category' => isset($_POST['category']) ? trim($_POST['category']) : '',
                'other_category' => isset($_POST['other_category']) ? trim($_POST['other_category']) : null,
                'quantity' => isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0,
                'weight' => isset($_POST['weight']) ? (float)$_POST['weight'] : 0.0,
                'condition_status' => isset($_POST['condition_status']) ? trim($_POST['condition_status']) : 'working',
                'note' => isset($_POST['note']) ? trim($_POST['note']) : null
            ];

            $id = isset($_POST['id']) ? trim($_POST['id']) : '';

            if (!empty($id)) {
                // Update request
                Request::update($id, $data);
            } else {
                // Create request
                Request::create($data);
            }

            // Redirect to My Requests
            header('Location: index.php?route=my-requests');
            exit;
        }
    }
}
