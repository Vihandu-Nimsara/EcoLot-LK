<?php
declare(strict_types=1);

class AuthController extends Controller
{
    public function login(): void
    {
        $this->view('auth/login');
    }

    public function register(): void
    {
        $this->view('auth/register');
    }

    public function registerPublic(): void
    {
        $this->view('auth/register-public');
    }

    public function registerRecycler(): void
    {
        $this->view('auth/register-recycler');
    }
}
