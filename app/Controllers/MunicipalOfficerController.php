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
}