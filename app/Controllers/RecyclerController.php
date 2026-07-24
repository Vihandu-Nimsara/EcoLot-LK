<?php
declare(strict_types=1);

class RecyclerController extends Controller
{
    public function dashboard(): void
    {
        $this->view('recycler/dashboard', ['currentPage' => 'dashboard']);
    }

    public function eligibleELots(): void
    {
        $this->view('recycler/eligible_e-lots', ['currentPage' => 'eligible-e-lots']);
    }

    public function myBids(): void
    {
        $this->view('recycler/my_bids', ['currentPage' => 'my-bids']);
    }

    public function awardedELots(): void
    {
        $this->view('recycler/awarded_e-lots', ['currentPage' => 'awarded-e-lots']);
    }
}
