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
        $recyclerProfiles = (new AuthorizedRecycler())->verificationList(); 
        $this->view('admin/recycler-verification', 
        ['currentPage' => 'recycler-verification',
        'recyclerProfiles' => $recyclerProfiles,
        ]);
    }

    public function recyclerDetails(string $id): void
    {
        Auth::requireRole('ADMIN');
        $recyclerId = filter_var($id, FILTER_VALIDATE_INT);
        IF ($recyclerId === false || $recyclerId <= 0) {
            http_response_code(404);
            $this->view('admin/recycler-details', [
                'currentPage' => 'recycler-verification',
                'recyclerId' => null,
            ]);
            return;
        }

        $recyclerProfile = (new AuthorizedRecycler())->verificationDetails($recyclerId);

        if ($recycler === null) {
            http_response_code(404);
        }

        $this->view('admin/recycler-details', [
        'currentPage' => 'recycler-verification',
        'recycler' => $recycler,
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
