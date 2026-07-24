<?php
declare(strict_types=1);

namespace App\Controllers;

// Load Core and Model dependencies manually (since there is no autoloader)
require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/Model.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/EWasteRequest.php';
require_once __DIR__ . '/../Models/Feedback.php';

use App\Models\User;
use App\Models\EWasteRequest;
use App\Models\Feedback;

final class PublicUserController extends \Controller
{
    private int $userId = 1; // Simulated logged in user ID in prototype

    public function dashboard(): void
    {
        $user = User::getById($this->userId);
        $stats = EWasteRequest::getStatsByUser($this->userId);
        $latestRequests = EWasteRequest::getLatestByUser($this->userId, 5);

        $this->view('public_user/dashboard', [
            'currentPage' => 'dashboard',
            'user' => $user,
            'stats' => $stats,
            'latestRequests' => $latestRequests
        ]);
    }

    public function profile(): void
    {
        $user = User::getById($this->userId);

        $this->view('public_user/profile', [
            'currentPage' => 'profile',
            'user' => $user
        ]);
    }

    public function myRequests(): void
    {
        $requests = EWasteRequest::getAllByUser($this->userId);

        $this->view('public_user/my_requests', [
            'currentPage' => 'my-requests',
            'requests' => $requests
        ]);
    }

    public function newRequest(): void
    {
        $request = null;
        $isEdit = false;

        if (isset($_GET['id'])) {
            $request = EWasteRequest::getById($_GET['id']);
            if ($request && (int)$request['user_id'] === $this->userId) {
                $isEdit = true;
            } else {
                $request = null;
            }
        }

        $this->view('public_user/new_request', [
            'currentPage' => 'new-request',
            'request' => $request,
            'isEdit' => $isEdit
        ]);
    }

    public function submitRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'user_id' => $this->userId,
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
                EWasteRequest::update($id, $data);
            } else {
                EWasteRequest::create($data);
            }

            header('Location: my-requests');
            exit;
        }
    }

    public function feedback(): void
    {
        $requests = EWasteRequest::getAllByUser($this->userId);

        $this->view('public_user/feedback', [
            'currentPage' => 'feedback',
            'requests' => $requests
        ]);
    }

    public function submitFeedback(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'user_id' => $this->userId,
                'feedback_type' => isset($_POST['feedback_type']) ? trim($_POST['feedback_type']) : 'Complaint',
                'request_id' => isset($_POST['request_id']) ? trim($_POST['request_id']) : null,
                'message' => isset($_POST['message']) ? trim($_POST['message']) : ''
            ];

            Feedback::create($data);

            header('Location: dashboard?feedback_success=1');
            exit;
        }
    }
}
