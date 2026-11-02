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
                'pageStylePage' => 'recycler-details',
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
                'pageStylePage' => 'recycler-details',
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
            'pageStylePage' => 'recycler-details',
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

        $adminId = Auth::id();

        if ($adminId === null) {
            http_response_code(403);
            return;
        }

        $recyclerId = filter_var($id, FILTER_VALIDATE_INT);

        if ($recyclerId === false || $recyclerId <= 0) {
            http_response_code(404);
            return;
        }

        if (!$this->hasValidCsrfToken()) {
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

        try {
            $service = new RecyclerApprovalService();

            $service->review(
                $recyclerId,
                $adminId,
                $decision,
                $rejectionReason
            );

            $_SESSION['success'] = $decision === 'APPROVE' 
                ? 'Recycler verified successfully.'
                : 'Recycler application rejected.';

        } catch (PDOException $e) {
            $_SESSION['error'] = 'Recycler review could not be completed.';
        } catch (InvalidArgumentException | RuntimeException $e) {
            $_SESSION['error'] = $e->getMessage();
        } catch (Throwable $e) {
            $_SESSION['error'] = 'Recycler review could not be completed.';
        }

        $basePath = rtrim(
            str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/'
        );

        header('Location: ' . $basePath . '/admin/recycler-verification/' . $recyclerId, true, 303);

        exit;
    }

    public function reviewRecyclerLicense(string $id): void
    {
        Auth::requireRole('ADMIN');

        $adminId = Auth::id();

        if ($adminId === null) {
            http_response_code(403);
            return;
        }

        $recyclerId = filter_var($id, FILTER_VALIDATE_INT);

        if ($recyclerId === false || $recyclerId <= 0) {
            http_response_code(404);
            return;
        }

        if (!$this->hasValidCsrfToken()) {
            http_response_code(403);
            echo '403 Invalid or expired form. Please reload the page.';
            return;
        }

        $rawDecision = $_POST['decision'] ?? null;
        $rawReviewNote = $_POST['review_note'] ?? '';

        if (!is_string($rawDecision) || !is_string($rawReviewNote)) {
            http_response_code(422);
            return;
        }

        $decision = strtoupper(trim($rawDecision));
        $reviewNote = trim($rawReviewNote);

        if (!in_array($decision, ['APPROVE', 'REJECT'], true)) {
            http_response_code(422);
            return;
        }

        if ($decision === 'REJECT' && $reviewNote === '') {
            http_response_code(422);
            return;
        }

        try {
            $service = new RecyclerLicenseReviewService();

            $service->review(
                $recyclerId,
                $adminId,
                $decision,
                $reviewNote
            );

            $_SESSION['success'] = $decision === 'APPROVE' 
                ? 'CEA license approved successfully.'
                : 'CEA license rejected successfully.';
        } catch (PDOException $e) {
            error_log('Recycler license review databse error: ' . $e->getMessage());

            $_SESSION['error'] = 'License review could not be completed.';
        
        } catch (InvalidArgumentException | RuntimeException $e) {
            $_SESSION['error'] = $e->getMessage();
        
        } catch (Throwable $e) {
            error_log('Recycler license review error: '. $e->getMessage());

            $_SESSION['error'] = 'License review could not be completed.';
        }

        $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');

        header(
            'Location: ' . $basePath . 
            '/admin/recycler-verification/' . $recyclerId,
            true,
            303
        );

        exit;
    }

    public function viewRecyclerLicense(string $id): void
    {
        Auth::requireRole('ADMIN');

        $recyclerId = filter_var($id, FILTER_VALIDATE_INT);

        if ($recyclerId === false || $recyclerId <= 0) {
            http_response_code(404);
            return;
        }

        $license = (new AuthorizedRecycler())->latestLicenseForRecycler($recyclerId);
        if ($license === null || empty($license['document_path'])) {
            http_response_code(404);
            return;
        }

        $allowedDirectory = realpath(
            APP_ROOT . '/storage/uploads/recycler-licenses'
        );

        $filePath = realpath(
            APP_ROOT . '/storage/' . $license['document_path']
        );


        // Check file location and permissions
        if (
            $allowedDirectory === false ||
            $filePath === false ||
            !str_starts_with($filePath, $allowedDirectory . DIRECTORY_SEPARATOR) ||
            dirname($filePath) !== $allowedDirectory ||
            !is_file($filePath) ||
            !is_readable($filePath)
        ) {
            http_response_code(404);
            return;
        }


        // Check actual file type 
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($filePath);

        if ($mime !== 'application/pdf') {
            http_response_code(404);
            return;
        }


        // Check PDF file size
        $fileSize = filesize($filePath);

        if (
            $fileSize === false ||
            $fileSize < 5 ||
            $fileSize > 5 * 1024 *1024
        ) {
            http_response_code(404);
            return;
        }

        // Check PDF signature
        $handle = fopen($filePath, 'rb');

        if ($handle === false) {
            http_response_code(404);
            return;
        }

        $signature = fread($handle, 5);

        $tailLength = min(2048, $fileSize);
        $ending = false;

        if (fseek($handle, -$tailLength, SEEK_END) === 0) {
            $ending = fread($handle, $tailLength);
        }

        fclose($handle);


        if (
            $signature !== '%PDF-' ||
            !is_string($ending) ||
            !str_contains($ending, '%%EOF')
        ) {
            http_response_code(404);
            return;
        }

        header('Content-Type: application/pdf');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        header('Content-Disposition: inline; filename="cea-license.pdf"');
        header('Content-Length: ' . $fileSize);

        readfile($filePath);
        exit;
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
