<?php
declare(strict_types=1);

class CollectorController extends Controller
{
    public function dashboard(): void
    {
        Auth::requireRole('COLLECTOR');
        $this->view('collector/assigned-routes', ['currentPage' => 'dashboard']);
    }

    public function myRequests(): void
    {
        Auth::requireRole('COLLECTOR');
        $this->view('collector/assigned-requests', ['currentPage' => 'my-requests']);
    }

    public function initialRequest(): void
    {
        Auth::requireRole('COLLECTOR');
        $this->view('collector/initial-request', ['currentPage' => 'initial-request']);
    }
}
