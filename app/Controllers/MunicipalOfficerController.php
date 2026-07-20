<?php

class MunicipalOfficerController extends Controller
{
    public function dashboard()
{
    $this->view(
        'municipal_officer/dashboard',
        [
            'currentPage' => 'dashboard'
        ]
    );
}

    public function campaigns()
{
    $this->view(
        'municipal_officer/campaigns',
        [
            'currentPage' => 'campaigns'
        ]
    );
}

public function areaSchedules()
{
    $this->view(
        'municipal_officer/area-schedules',
        [
            'currentPage' => 'area-schedules'
        ]
    );
}

public function flaggedRequests()
{
    $this->view(
        'municipal_officer/flagged-requests',
        [
            'currentPage' => 'flagged-requests'
        ]
    );
}
}