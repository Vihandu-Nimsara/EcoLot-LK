<?php
declare(strict_types=1);

class PublicUserController extends Controller
{
    public function dashboard(): void
    {
        $this->view('public_user/dashboard', ['currentPage' => 'dashboard']);
    }

    public function myRequests(): void
    {
        $this->view('public_user/my-requests', ['currentPage' => 'my-requests']);
    }

    public function newRequest(): void
    {
        $this->view('public_user/new-request', ['currentPage' => 'new-request']);
    }

    public function feedback(): void
    {
        $this->view('public_user/feedback', ['currentPage' => 'feedback']);
    }

    public function profile(): void
    {
        $this->view('public_user/profile', ['currentPage' => 'profile']);
    }
}
