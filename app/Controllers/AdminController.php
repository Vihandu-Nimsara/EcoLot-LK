<?php
declare(strict_types=1);

class AdminController extends Controller
{
    public function dashboard(): void
    {
        $this->view('admin/dashboard', ['currentPage' => 'dashboard']);
    }
    
    public function Users(): void
    {
        $this->view('admin/users', ['currentPage' => 'users']);
    }

    public function recyclerVerification(): void
    {
        $this->view('admin/recycler-verification', ['currentPage' => 'recycler-verification']);
    }

    public function recyclerDetails(string $id): void
    {
        $this->view('admin/recycler-details', [
            'currentPage' => 'recycler-verification',
            'pageStylePage' => 'recycler-details',
            'recyclerId' => $id,
        ]);
    }

    public function categoriesAndItems(): void
    {
        $this->view('admin/categories-items', ['currentPage' => 'categories-items']);
    }

    public function riskRules(): void
    {
        $this->view('admin/risk-rules', ['currentPage' => 'risk-rules']);
    }

    public function reports(): void
    {
        $this->view('admin/reports', ['currentPage' => 'reports']);
    }

}
