<?php
declare(strict_types=1);

<<<<<<< HEAD
namespace App\Controllers;

use App\Core\Controller;

final class CollectorController extends Controller
{
=======
class CollectorController extends Controller
{
    public function dashboard(): void
    {
        $this->view('collector/dashboard', ['currentPage' => 'dashboard']);
    }
>>>>>>> 09eed93399941da7a677135bf2a86e7394892ff0
}
