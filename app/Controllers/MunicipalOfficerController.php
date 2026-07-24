<?php
declare(strict_types=1);

class MunicipalOfficerController extends Controller
{
    public function dashboard(): void
    {
        $this->view('municipal_officer/dashboard', ['currentPage' => 'dashboard']);
    }

    public function campaigns(): void
    {
        $this->view('municipal_officer/campaigns', ['currentPage' => 'campaigns']);
    }

    public function areaSchedules(): void
    {
        $this->view('municipal_officer/area-schedules', ['currentPage' => 'area-schedules']);
    }

    public function flaggedRequests(): void
    {
        $this->view('municipal_officer/flagged-requests', ['currentPage' => 'flagged-requests']);
    }

    public function routes(): void
    {
        $this->view('municipal_officer/routes', ['currentPage' => 'routes']);
    }

    public function collectionRecords(): void
    {
        $this->view('municipal_officer/collection-records', ['currentPage' => 'collection-records']);
    }

    public function eLots(): void
    {
        $this->view('municipal_officer/elots', ['currentPage' => 'e-lots']);
    }

    public function feedback(): void
    {
        $this->view('municipal_officer/feedback', ['currentPage' => 'feedback']);
    }

    public function reports(): void
    {
        $this->view('municipal_officer/reports', ['currentPage' => 'reports']);
    }
}
