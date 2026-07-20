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


public function routes()
{
    $this->view(
        'municipal_officer/routes',
        [
            'currentPage' => 'routes'
        ]
    );
}

public function collectionRecords()
{
    $this->view(
        'municipal_officer/collection-records',
        [
            'currentPage' => 'collection-records'
        ]
    );
}

public function eLots()
{
    $this->view(
        'municipal_officer/elots',
        [
            'currentPage' => 'e-lots'
        ]
    );
}

public function feedback()
{
    $this->view(
        'municipal_officer/feedback',
        [
            'currentPage' => 'feedback'
        ]
    );
}

}