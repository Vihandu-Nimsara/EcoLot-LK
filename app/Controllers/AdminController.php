<?php
declare(strict_types=1);

class AdminController extends Controller
{
    public function dashboard(): void
    {
        Auth::requireRole('ADMIN');

        $dashboardStats = null;
        $dashboardActivity = [];
        $dashboardUnavailable = false;
        try {
            $dashboard = new AdminDashboardService();
            $dashboardStats = $dashboard->statistics();
            $dashboardActivity = $dashboard->recentActivity(5);
        } catch (Throwable $error) {
            // Fail closed: show unavailable rather than invented '0' counts or demo events.
            error_log('Admin dashboard data read failed: ' . $error::class);
            $dashboardUnavailable = true;
            http_response_code(503);
        }

        $this->view('admin/dashboard', [
            'currentPage' => 'dashboard',
            'dashboardStats' => $dashboardStats,
            'dashboardActivity' => $dashboardActivity,
            'dashboardUnavailable' => $dashboardUnavailable,
        ]);
    }
    
    public function Users(): void
    {
        Auth::requireRole('ADMIN');
        $this->view('admin/users', ['currentPage' => 'users']);
    }

    public function recyclerVerification(): void
    {
        $this->requireRecyclerReviewer();
        $recyclerProfiles = (new AuthorizedRecycler())->verificationList(); 
        $this->view('admin/recycler-verification', 
        ['currentPage' => 'recycler-verification',
        'recyclerProfiles' => $recyclerProfiles,
        ]);
    }

    public function recyclerDetails(string $id): void
    {
        $this->requireRecyclerReviewer();
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
            
        $license = in_array($recycler['verification_status'], ['PENDING','REJECTED'], true)
            ? $model->latestLicenseForRecycler($recyclerId) : $model->selectedLicenseForRecycler($recyclerId);
        $capabilities = $model->capabilitiesForRecycler($recyclerId);
        $activityModel = new RecyclerAuthorizedActivity();
        $activities = $activityModel->forRecycler($recyclerId);

        $scopeAvailable=true; $scopes=[]; $scopeSupport=[]; $scopeReady=true;
        try { $scopes=$model->initialScopes($recyclerId); }
        catch (PDOException $error) { if ((int)($error->errorInfo[1]??0)!==1146) throw $error; $scopeAvailable=false; }
        $scopeService=new RecyclerLicenseReviewService();
        foreach ($capabilities as $cap) {
            try { if (!$scopeAvailable) throw new RuntimeException(); $scopeService->requireScope($recyclerId,(int)$cap['category_id'],null,(int)$cap['can_handle_high_risk']); $scopeSupport['category'][$cap['category_id']]=true; }
            catch (RuntimeException $error) { $scopeSupport['category'][$cap['category_id']]=false; }
            if ($cap['capability_status']==='APPROVED') {
                try { if (!$scopeAvailable) throw new RuntimeException(); $scopeService->requireScope($recyclerId,(int)$cap['category_id'],null,(int)$cap['can_handle_high_risk'],true); }
                catch (RuntimeException $error) { $scopeReady=false; }
            }
        }
        foreach ($activities as $activity) {
            try { if (!$scopeAvailable) throw new RuntimeException(); $scopeService->requireScope($recyclerId,null,$activity['activity_type'],null); $scopeSupport['activity'][$activity['activity_id']]=true; }
            catch (RuntimeException $error) { $scopeSupport['activity'][$activity['activity_id']]=false; }
            if ($activity['activity_status']==='APPROVED') {
                try { if (!$scopeAvailable) throw new RuntimeException(); $scopeService->requireScope($recyclerId,null,$activity['activity_type'],null,true); }
                catch (RuntimeException $error) { $scopeReady=false; }
            }
        }
        $documentPath=(new RecyclerLicenseRenewalService())->currentDocument($recyclerId,$license);
        $this->view('admin/recycler-details', [
            'currentPage' => 'recycler-verification',
            'pageStylePage' => 'recycler-details',
            'recycler' => $recycler,
            'license' => $license,
            'scopeAvailable' => $scopeAvailable, 'scopes' => $scopes, 'scopeSupport' => $scopeSupport,
            'scopeReady' => $scopeReady && $scopeAvailable,
            'documentAvailable' => $documentPath !== null,
            'documentHash' => $documentPath === null ? '' : hash_file('sha256',$documentPath),
            'capabilities' => $capabilities,
            'activities' => $activities,
            'capabilityRequests' => (new RecyclerCapabilityChangeService())->requests($recyclerId),
            'renewalRequests' => (new RecyclerLicenseRenewalService())->requests($recyclerId),
            'renewalScopeReview' => (new RecyclerLicenseRenewalService())->canReviewScope($recyclerId),
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function reviewRecycler(string $id): void
    {
        $this->requireRecyclerReviewer();

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
            $this->postString('decision')
        );

        if (!in_array($decision, ['APPROVE', 'REJECT'], true)) {
            http_response_code(422);
            return;
        }

        $rejectionReason = $this->postString('rejection_reason');

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
        $this->requireRecyclerReviewer();

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

        $rawLicenseId = $_POST['license_id'] ?? null;

        if (!is_string($rawLicenseId) || preg_match('/^[1-9][0-9]*$/', $rawLicenseId) !== 1)
        {
            http_response_code(422);
            return;
        }

        $licenseId = filter_var($rawLicenseId, FILTER_VALIDATE_INT);

        if ($licenseId === false || $licenseId <= 0) {
            http_response_code(422);
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
                $licenseId,
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

    public function reviewRecyclerCapability(string $id): void
    {
        $this->requireRecyclerReviewer();

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

        // Validate the selected waste category.
        $rawCategoryId = $_POST['category_id'] ?? null;

        if (
            !is_string($rawCategoryId) ||
            preg_match('/^[1-9][0-9]*$/', $rawCategoryId) !== 1
        ) {
            http_response_code(422);
            return;
        }

        $categoryId = filter_var(
            $rawCategoryId,
            FILTER_VALIDATE_INT
        );

        if ($categoryId === false || $categoryId <= 0) {
            http_response_code(422);
            return;
        }

        $rawDecision = $_POST['decision'] ?? null;
        $rawReviewNote = $_POST['review_note'] ?? '';

        if (
            !is_string($rawDecision) ||
            !is_string($rawReviewNote)
        ) {
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
            $service = new RecyclerCapabilityReviewService();

            $service->review(
                $recyclerId,
                $categoryId,
                $adminId,
                $decision,
                $reviewNote
            );

            $_SESSION['success'] = $decision === 'APPROVE'
                ? 'Recycler capability approved successfully.'
                : 'Recycler capability rejected successfully.';

        } catch (PDOException $e) {
            error_log(
                'Capability review database error: ' . $e->getMessage()
            );

            $_SESSION['error'] =
                'Capability review could not be completed.';

        } catch (InvalidArgumentException | RuntimeException $e) {
            $_SESSION['error'] = $e->getMessage();

        } catch (Throwable $e) {
            error_log(
                'Capability review error: ' . $e->getMessage()
            );

            $_SESSION['error'] =
                'Capability review could not be completed.';
        }

        $basePath = rtrim(
            str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])),
            '/'
        );

        header(
            'Location: ' . $basePath .
            '/admin/recycler-verification/' . $recyclerId,
            true,
            303
        );

        exit;
    }

    public function reviewRecyclerAuthorizedActivity(string $id): void
    {
        $this->requireRecyclerReviewer();

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

        // Validate the selected authorized activity.
        $rawActivityId = $_POST['activity_id'] ?? null;

        if (
            !is_string($rawActivityId) ||
            preg_match('/^[1-9][0-9]*$/', $rawActivityId) !== 1
        ) {
            http_response_code(422);
            return;
        }

        $activityId = filter_var(
            $rawActivityId,
            FILTER_VALIDATE_INT
        );

        if ($activityId === false || $activityId <= 0) {
            http_response_code(422);
            return;
        }

        $rawDecision = $_POST['decision'] ?? null;
        $rawReviewNote = $_POST['review_note'] ?? '';

        if (
            !is_string($rawDecision) ||
            !is_string($rawReviewNote)
        ) {
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
            $service = new RecyclerAuthorizedActivityReviewService();

            $service->review(
                $recyclerId,
                $activityId,
                $adminId,
                $decision,
                $reviewNote
            );

            $_SESSION['success'] = $decision === 'APPROVE'
                ? 'Recycler authorized activity approved successfully.'
                : 'Recycler authorized activity rejected successfully.';

        } catch (PDOException $e) {
            error_log(
                'Authorized activity review database error: '
                . $e->getMessage()
            );

            $_SESSION['error'] =
                'Authorized activity review could not be completed.';

        } catch (InvalidArgumentException | RuntimeException $e) {
            $_SESSION['error'] = $e->getMessage();

        } catch (Throwable $e) {
            error_log(
                'Authorized activity review error: ' . $e->getMessage()
            );

            $_SESSION['error'] =
                'Authorized activity review could not be completed.';
        }

        $basePath = rtrim(
            str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])),
            '/'
        );

        header(
            'Location: ' . $basePath .
            '/admin/recycler-verification/' . $recyclerId,
            true,
            303
        );

        exit;
    }

    public function reviewCapabilityChange(string $id,string $requestId): void
    {
        $this->requireRecyclerReviewer();
        if (!$this->hasValidCsrfToken()) { http_response_code(403); echo '403 Invalid or expired form.'; return; }
        if (!ctype_digit($id) || !ctype_digit($requestId) || (int)$id<1 || (int)$requestId<1) { http_response_code(404); return; }
        try {
            (new RecyclerCapabilityChangeService())->review((int)$id,(int)Auth::id(),(int)$requestId,$this->postString('decision'),$this->postString('review_note'));
            Session::put('success',$this->postString('decision')==='APPROVE' ? 'Capability change approved.' : 'Capability change rejected. Current permissions are unchanged.');
        } catch (PDOException $error) { Session::put('error','Capability review is currently unavailable. Reload and try again.'); }
        catch (DomainException|InvalidArgumentException|RuntimeException $error) { Session::put('error',$error->getMessage()); }
        catch (Throwable $error) { Session::put('error','This change could not be reviewed. Reload and check the current request and permissions.'); }
        $this->redirect('/admin/recycler-verification/'.(int)$id.'#capability-changes');
    }

    public function proposeDocumentScope(string $id): void
    {
        $context=$this->postString('capability_request_id');
        if ($context==='renewal') { $this->mutateRecyclerScope($id,null,true); return; }
        $request=filter_var($context,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if (!$request) { $this->requireRecyclerReviewer(); http_response_code(400); echo 'Select a valid scope review context.'; return; }
        $this->mutateRecyclerScope($id,null,false,(int)$request);
    }

    public function reviewCapabilityScope(string $id,string $scopeId): void
    {
        $request=filter_var($this->postString('capability_request_id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if (!$request) { $this->requireRecyclerReviewer(); http_response_code(400); echo 'Select a valid capability request.'; return; }
        $this->mutateRecyclerScope($id,$scopeId,false,(int)$request);
    }

    public function reviewLicenseUpdate(string $id, string $requestId): void
    {
        $this->requireRecyclerReviewer();
        if (!$this->hasValidCsrfToken()) { http_response_code(403); echo '403 Invalid or expired form.'; return; }
        if (!ctype_digit($id) || !ctype_digit($requestId) || (int)$id<1 || (int)$requestId<1) { http_response_code(404); return; }
        try {
            (new RecyclerLicenseRenewalService())->review((int)$id,(int)Auth::id(),(int)$requestId,$this->postString('decision'),$this->postString('review_note'));
            Session::put('success',$this->postString('decision')==='APPROVE' ? 'Licence update approved. Scope for this document requires independent verification.' : 'Licence update rejected. The approved licence is unchanged.');
        } catch (DomainException|InvalidArgumentException $error) {
            Session::put('error',$error->getMessage());
        } catch (Throwable $error) {
            Session::put('error','The review could not be saved. Reload and check for a changed request or conflicting licence number.');
        }
        $this->redirect('/admin/recycler-verification/'.(int)$id.'#licence-updates');
    }

    public function viewLicenseUpdateDocument(string $id, string $requestId): void { $this->viewRecyclerLicense($id,null,$requestId); }
    public function viewLicensePreviousDocument(string $id, string $requestId): void { $this->viewRecyclerLicense($id,null,$requestId,true); }

    public function viewRecyclerLicense(string $id, ?string $scopeId = null, ?string $requestId = null, bool $previous = false): void
    {
        $this->requireRecyclerReviewer();

        $recyclerId = filter_var($id, FILTER_VALIDATE_INT);

        if ($recyclerId === false || $recyclerId <= 0) {
            http_response_code(404);
            return;
        }

        if ($scopeId!==null) {
            $scope=filter_var($scopeId,FILTER_VALIDATE_INT);
            if (!$scope || $scope<1) { http_response_code(404); return; }
            $s=Database::connection()->prepare('SELECT s.document_path,s.document_sha256 FROM recycler_license_scopes s JOIN recycler_licenses l ON l.license_id=s.license_id WHERE s.scope_id=? AND l.recycler_user_id=?');
            $s->execute([$scope,$recyclerId]); $license=$s->fetch(PDO::FETCH_ASSOC) ?: null;
        } else {
            $model = new AuthorizedRecycler();
            $registration = $model->findByUserId($recyclerId);
            $license = in_array($registration['verification_status'] ?? '', ['PENDING','REJECTED'], true)
                ? $model->latestLicenseForRecycler($recyclerId) : $model->selectedLicenseForRecycler($recyclerId);
        }

        if ($requestId !== null) $license = ['document_path'=>null];
        if ($license === null) {
            http_response_code(404);
            return;
        }

        // Reuse the centralized PDF document validator.
        $validator = new RecyclerLicenseDocumentValidator();

        $renewal = new RecyclerLicenseRenewalService();
        $filePath = $requestId !== null
            ? (ctype_digit($requestId) && (int)$requestId>0 ? $renewal->document($recyclerId,(int)$requestId,$previous) : null)
            : ($scopeId !== null ? $validator->validatedPath($license['document_path'] ?? null) : $renewal->currentDocument($recyclerId,$license));

        if ($filePath === null || (isset($license['document_sha256']) && !hash_equals($license['document_sha256'],(string)hash_file('sha256',$filePath)))) {
            http_response_code(404);
            return;
        }

        $fileSize = filesize($filePath);

        if ($fileSize === false) {
            http_response_code(404);
            return;
        }

        header('Content-Type: application/pdf');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        header(
            'Content-Disposition: inline; filename="cea-license.pdf"'
        );
        header('Content-Length: ' . $fileSize);

        readfile($filePath);
        exit;
    }

    private function requireRecyclerReviewer(): void
    {
        Auth::requireRole('ADMIN');
        $s=Database::connection()->prepare("SELECT a.user_id FROM administrators a JOIN users u ON u.user_id=a.user_id WHERE a.user_id=? AND u.role='ADMIN' AND u.account_status='ACTIVE'");
        $s->execute([(int) Auth::id()]);
        if (!$s->fetchColumn()) { http_response_code(403); exit('403 Active Administrator required.'); }
    }

    public function proposeRenewalScope(string $id): void { $this->mutateRecyclerScope($id,null,true); }
    public function reviewRenewalScope(string $id, string $scopeId): void { $this->mutateRecyclerScope($id,$scopeId,true); }

    public function proposeRecyclerScope(string $id): void { $this->mutateRecyclerScope($id); }
    public function reviewRecyclerScope(string $id, string $scopeId): void { $this->mutateRecyclerScope($id,$scopeId); }

    private function mutateRecyclerScope(string $id, ?string $scopeId = null, bool $renewal = false, ?int $capabilityRequest = null): void
    {
        $this->requireRecyclerReviewer();
        $owner=filter_var($id,FILTER_VALIDATE_INT);
        $scope=$scopeId===null ? null : filter_var($scopeId,FILTER_VALIDATE_INT);
        if (!$owner || $owner<1 || ($scopeId!==null && (!$scope || $scope<1))) { http_response_code(404); return; }
        if (!$this->hasValidCsrfToken()) { http_response_code(403); echo '403 Invalid or expired form.'; return; }
        try {
            $service=new RecyclerLicenseReviewService();
            if ($scopeId===null) {
                $input=[];
                foreach (['license_id','document_sha256','category_id','activity_type','covers_high_risk','authorization_basis','waste_description','waste_code','evidence_reference','licence_conditions'] as $field) $input[$field]=$this->postString($field);
                $service->proposeScope((int)$owner,(int)Auth::id(),$input,$renewal,$capabilityRequest);
                Session::put('success','Scope proposal recorded. Independent verification is still required.');
            } else {
                $service->reviewScope((int)$owner,(int)$scope,(int)Auth::id(),$this->postString('decision'),$this->postString('review_note'),$this->postString('evidence_confirmed')==='1',$renewal,$capabilityRequest);
                Session::put('success','Scope decision recorded. Requested capabilities and activities remain separate reviews.');
            }
        } catch (PDOException $error) { Session::put('error','Scope review is unavailable. Please contact the system administrator.'); }
        catch (InvalidArgumentException|DomainException|RuntimeException $error) { Session::put('error',$error->getMessage()); }
        $this->redirect('/admin/recycler-verification/'.$owner.'#licence-scope');
    }

    public function viewRecyclerScopeDocument(string $id, string $scopeId): void
    {
        $this->viewRecyclerLicense($id,$scopeId);
    }

    public function categoriesAndItems(): void
    {
        (new AdminCatalogueController())->index();

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
