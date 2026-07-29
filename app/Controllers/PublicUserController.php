<?php
declare(strict_types=1);

class PublicUserController extends Controller
{
    public function dashboard(): void
    {
        $this->view('public_user/pickup-dashboard', ['currentPage' => 'dashboard']);
    }

    public function myRequests(): void
    {
        $this->view('public_user/pickup-request-history', ['currentPage' => 'my-requests']);
    }

    public function newRequest(): void
    {
        $this->view('public_user/pickup-request-form', ['currentPage' => 'new-request']);
    }

    public function feedback(): void
    {
        $this->view('public_user/feedback-form', ['currentPage' => 'feedback']);
    }

    public function profile(): void
    {
        $this->view('public_user/account-profile', ['currentPage' => 'profile']);
    }
}
