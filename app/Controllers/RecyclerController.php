<?php

class RecyclerController extends Controller
{
    public function dashboard()
{
    $this->view(
        'recycler/dashboard',
        [
            'currentPage' => 'dashboard'
        ]
    );
}


    public function Eligible_ELots()
{
    $this->view(
        'recycler/eligible_e-lots',
        [
            'currentPage' => 'eligible_e-lots'
        ]
    );
}

    public function My_Bids()
{
    $this->view(
        'recycler/my_bids',
        [
            'currentPage' => 'my_bids'
        ]
    );
}

    public function Awarded_ELots()
{
    $this->view(
        'recycler/awarded_e-lots',
        [
            'currentPage' => 'awarded_e-lots'
        ]
    );
}

}