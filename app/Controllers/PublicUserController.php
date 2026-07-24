<?php
declare(strict_types=1);

class PublicUserController extends Controller
{
    public function dashboard(): void
    {
        $this->view('public_user/dashboard', ['currentPage' => 'dashboard']);
    }
}
