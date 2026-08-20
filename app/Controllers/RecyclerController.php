<?php
declare(strict_types=1);

class RecyclerController extends Controller
{
    public function dashboard(): void
    {
        Auth::requireRole('RECYCLER');
        $this->view('recycler/dashboard', ['currentPage' => 'dashboard']);
    }

    public function eligibleELots(): void
    {
        Auth::requireRole('RECYCLER');
        $this->view('recycler/eligible_e-lots', ['currentPage' => 'eligible-e-lots']);
    }

    public function eLotDetails(string $id): void
    {
        Auth::requireRole('RECYCLER');
        $this->view('recycler/e_lot_details', [
            'currentPage' => 'eligible-e-lots',
            'eLotId' => $id,
        ]);
    }

    public function myBids(): void
    {
        Auth::requireRole('RECYCLER');
        $this->view('recycler/my_bids', ['currentPage' => 'my-bids']);
    }

    public function awardedELots(): void
    {
        Auth::requireRole('RECYCLER');
        $this->view('recycler/awarded_e-lots', ['currentPage' => 'awarded-e-lots']);
    }

    public function awardedELotDetails(string $id): void
    {
        Auth::requireRole('RECYCLER');
        $this->view('recycler/awarded_e-lot_details', [
            'currentPage' => 'awarded-e-lots',
            'eLotId' => $id,
        ]);
    }

    public function profile(): void
    {
        Auth::requireRole('RECYCLER');
        $this->view('recycler/profile', ['currentPage' => 'profile']);
    }

    public function reports(): void
    {
        Auth::requireRole('RECYCLER');
        $this->view('recycler/reports', ['currentPage' => 'reports']);
    }
}
