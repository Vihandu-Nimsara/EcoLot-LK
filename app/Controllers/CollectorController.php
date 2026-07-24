<?php
declare(strict_types=1);

class CollectorController extends Controller
{
    public function dashboard(): void
    {
        $this->view('collector/dashboard', ['currentPage' => 'dashboard']);
    }
}
