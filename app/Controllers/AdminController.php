<?php
declare(strict_types=1);

class AdminController extends Controller
{
    public function dashboard(): void
    {
        Auth::requireRole('ADMIN');
        $this->view('admin/dashboard', ['currentPage' => 'dashboard']);
    }
    
    public function Users(): void
    {
        Auth::requireRole('ADMIN');
        $this->view('admin/users', ['currentPage' => 'users']);
    }

    public function recyclerVerification(): void
    {
        Auth::requireRole('ADMIN');
        $this->view('admin/recycler-verification', ['currentPage' => 'recycler-verification']);
    }

    public function recyclerDetails(string $id): void
    {
        Auth::requireRole('ADMIN');
        $this->view('admin/recycler-details', [
            'currentPage' => 'recycler-verification',
            'pageStylePage' => 'recycler-details',
            'recyclerId' => $id,
        ]);
    }

    public function categoriesAndItems(): void
    {
        Auth::requireRole('ADMIN');
        $this->view('admin/categories-items', ['currentPage' => 'categories-items']);

    }

    public function riskRules(): void
    {
        Auth::requireRole('ADMIN');
        $this->view('admin/risk-rules', ['currentPage' => 'risk-rules']);
    }

    public function reports(): void
    {
        Auth::requireRole('ADMIN');
        $this->view('admin/reports', ['currentPage' => 'reports']);
    }

}
