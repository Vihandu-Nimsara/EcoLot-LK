<?php
<<<<<<< HEAD
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

final class MunicipalOfficerController extends Controller
{
}
=======

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

public function reports()
{
    $this->view(
        'municipal_officer/reports',
        [
            'currentPage' => 'reports'
        ]
    );
}

}
>>>>>>> 09eed93399941da7a677135bf2a86e7394892ff0
