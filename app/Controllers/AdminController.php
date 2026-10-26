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
        if ($recyclerId === false || $recyclerId <= 0) {
            http_response_code(404);
            $this->view('admin/recycler-details', [
                'currentPage' => 'recycler-verification',
                'recycler' => null,
                'license' => null,
                'capabilities' => [],
                'activities' => [],
            ]);
            return;
        }
                
        $model = new AuthorizedRecycler();
        $recycler = $model->verificationDetails($recyclerId);
        
        if ($recycler === null) {
            http_response_code(404);
            $this->view('admin/recycler-details', [
                'currentPage' => 'recycler-verification',
                'recycler' => null,
                'license' => null,
                'capabilities' => [],
                'activities' => [],
            ]);
            return;
        }
            
        $license =$model->latestLicenseForRecycler($recyclerId);
        $capabilities = $model->capabilitiesForRecycler($recyclerId);
        $activityModel = new RecyclerAuthorizedActivity();
        $activities = $activityModel->forRecycler($recyclerId);

        $this->view('admin/recycler-details', [
            'currentPage' => 'recycler-verification',
            'recycler' => $recycler,
            'license' => $license,
            'capabilities' => $capabilities,
            'activities' => $activities,
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function reviewRecycler(string $id): void
    {
        Auth::requireRole('ADMIN');

        $recyclerId = filter_var($id, FILTER_VALIDATE_INT);

        if ($recyclerId === false || $recyclerId <= 0) {
            http_response_code(404);
            return;
        }

        if (!$this->hasValideCsrfToken()) {
            http_response_code(403);
            echo '403 Invalid or expired form. Please reload the page and try again.';
            return;
        }

        $decision = strtoupper(
            trim((string) ($_POST['decision'] ?? ''))
        );

        if (!in_array($decision, ['APPROVE', 'REJECT'], true)) {
            http_response_code(422);
            return;
        }

        $rejectionReason = trim(
            (string) ($_POST['rejection_reason'] ?? '')
        );

        if ($decision === 'REJECT' && $rejectionReason === '') {
            http_response_code(422);
            return;
        }
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
