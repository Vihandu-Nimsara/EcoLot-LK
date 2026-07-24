<?php
declare(strict_types=1);

class RecyclerController extends Controller
{
    public function dashboard(): void
    {
        $this->view('recycler/dashboard', ['currentPage' => 'dashboard']);
    }
}
