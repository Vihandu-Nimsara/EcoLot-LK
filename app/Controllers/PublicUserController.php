<?php
declare(strict_types=1);

class PublicUserController extends Controller
{
    public function dashboard(): void
    {
        Auth::requireRole('PUBLIC_USER');
        $this->view('public_user/pickup-dashboard', ['currentPage' => 'dashboard']);
    }

    public function myRequests(): void
    {
        Auth::requireRole('PUBLIC_USER');
        $this->view('public_user/pickup-request-history', ['currentPage' => 'my-requests']);
    }

    public function newRequest(): void
    {
        Auth::requireRole('PUBLIC_USER');
        $this->view('public_user/pickup-request-form', ['currentPage' => 'new-request']);
    }

    public function feedback(): void
    {
        Auth::requireRole('PUBLIC_USER');
        $this->view('public_user/feedback-form', ['currentPage' => 'feedback']);
    }

    public function profile(): void
    {
        Auth::requireRole('PUBLIC_USER');
        $this->view('public_user/account-profile', ['currentPage' => 'profile']);
    }
}
