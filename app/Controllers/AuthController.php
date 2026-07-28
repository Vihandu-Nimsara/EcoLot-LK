<?php
declare(strict_types=1);

<<<<<<< HEAD
namespace App\Controllers;

use App\Core\Controller;

final class AuthController extends Controller
{
=======
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
>>>>>>> 09eed93399941da7a677135bf2a86e7394892ff0
}
