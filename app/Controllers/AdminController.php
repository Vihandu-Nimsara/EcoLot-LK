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
        $licenseUpdateModel = new RecyclerLicenseUpdateRequest();
        $pendingLicenseUpdate = $licenseUpdateModel->pendingForRecycler($recyclerId);
        $capabilities = $model->capabilitiesForRecycler($recyclerId);
        $activityModel = new RecyclerAuthorizedActivity();
        $activities = $activityModel->forRecycler($recyclerId);

        $this->view('admin/recycler-details', [
            'currentPage' => 'recycler-verification',
            'pageStylePage' => 'recycler-details',
            'recycler' => $recycler,
            'license' => $license,
            'pendingLicenseUpdate' => $pendingLicenseUpdate,
            'capabilities' => $capabilities,
            'capabilityRequests' => (new RecyclerCapabilityChangeRequest())->historyForRecycler($recyclerId),
            'licenceCoverage' => (new RecyclerLicenceCoverage())->history($recyclerId),
            'coverageAudit' => (new RecyclerLicenceCoverage())->reviewHistory($recyclerId),
            'coverageSource' => (new RecyclerLicenceAuthorization(Database::connection()))->currentLicense($recyclerId),
            'coverageCategories' => (new WasteCategory())->all(),
            'licenceConflicts' => (new RecyclerLicenceCoverage())->conflicts($recyclerId),
            'coverageLots' => (new RecyclerLicenceCoverage())->lotChoices(),
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
        } catch (InvalidArgumentException | DomainException | RuntimeException $e) {
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
            error_log('Recycler licence review database failure category=' . $e::class);

            $_SESSION['error'] = 'License review could not be completed.';
        
        } catch (InvalidArgumentException | DomainException | RuntimeException $e) {
            $_SESSION['error'] = $e->getMessage();
        
        } catch (Throwable $e) {
            error_log('Recycler licence review failure category=' . $e::class);

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

    public function reviewRecyclerLicenseUpdate(
        string $id,
        string $requestId
    ): void {
        Auth::requireRole('ADMIN');

        $adminId = Auth::id();

        if ($adminId === null) {
            http_response_code(403);
            return;
        }

        // Validate both URL parameters.
        $recyclerId = filter_var($id, FILTER_VALIDATE_INT);
        $updateRequestId = filter_var(
            $requestId,
            FILTER_VALIDATE_INT
        );

        if (
            $recyclerId === false ||
            $recyclerId <= 0 ||
            $updateRequestId === false ||
            $updateRequestId <= 0
        ) {
            http_response_code(404);
            return;
        }

        // CSRF protection.
        if (!$this->hasValidCsrfToken()) {
            http_response_code(403);
            echo '403 Invalid or expired form. Please reload the page.';
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
            $service = new RecyclerLicenseUpdateReviewService();

            $service->review(
                $recyclerId,
                $updateRequestId,
                (int) $adminId,
                $decision,
                $reviewNote
            );

            $_SESSION['success'] = $decision === 'APPROVE'
                ? 'CEA licence update approved successfully.'
                : 'CEA licence update rejected successfully.';

        } catch (PDOException $e) {
            error_log(
                'Recycler licence update database failure category=' .
                $e::class
            );

            $_SESSION['error'] =
                'Licence update review could not be completed.';

        } catch (InvalidArgumentException | DomainException | RuntimeException $e) {
            $_SESSION['error'] = $e->getMessage();

        } catch (Throwable $e) {
            error_log(
                'Recycler licence update review failure category=' .
                $e::class
            );

            $_SESSION['error'] =
                'Licence update review could not be completed.';
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

    public function proposeLicenceCoverage(string $id): void { $this->handleLicenceCoverage($id,'propose'); }
    public function reviewLicenceCoverage(string $id,string $coverageId): void { $this->handleLicenceCoverage($id,'review',$coverageId); }
    public function setLicenceLotActivities(string $id): void { $this->handleLicenceCoverage($id,'lot'); }

    private function handleLicenceCoverage(string $id,string $action,string $coverageId='0'): void
    {
        Auth::requireRole('ADMIN');
        $owner=filter_var($id,FILTER_VALIDATE_INT);
        if ($owner===false || $owner<=0) { http_response_code(404); return; }
        if (!$this->hasValidCsrfToken()) { http_response_code(403); echo '403 Invalid or expired form.'; return; }
        try {
            $service=new RecyclerLicenceCoverageService(); $admin=(int)Auth::id();
            if ($action==='propose') {
                $input=[];
                foreach (['license_id','document_version','category_id','activity_type','covers_high_risk','authorization_basis','waste_description','document_waste_code','evidence_reference','licence_conditions'] as $key) $input[$key]=$this->postString($key);
                $service->propose($owner,$admin,$input);
                Session::put('success','Coverage proposal recorded. Explicit verification is still required.');
            } elseif ($action==='review') {
                $coverage=filter_var($coverageId,FILTER_VALIDATE_INT);
                if ($coverage===false || $coverage<=0) throw new DomainException('Invalid coverage record.');
                if ($this->postString('decision')==='VERIFIED' && $this->postString('evidence_confirmed')!=='1') throw new DomainException('Confirm that you inspected the specific document and supporting authorization.');
                $service->review($owner,$coverage,$admin,$this->postString('decision'),$this->postString('review_note'));
                Session::put('success','Licence coverage decision recorded. Existing bids and handovers were preserved.');
            } else {
                $lot=filter_var($this->postString('e_lot_id'),FILTER_VALIDATE_INT);
                $version=filter_var($this->postString('requirement_version'),FILTER_VALIDATE_INT);
                $activities=$_POST['required_activities']??null;
                if ($lot===false || $version===false || !is_array($activities)) throw new DomainException('Select a valid E-Lot and its required activities.');
                $service->setLotActivities($lot,$admin,$version,$activities,$this->postString('review_note'));
                Session::put('success','E-Lot activity requirements recorded. Existing bids were not changed.');
            }
        } catch (DomainException $error) { Session::put('error',$error->getMessage()); }
        catch (Throwable $error) {
            error_log('Licence coverage action failed category=' . $error::class);
            Session::put('error','Unable to record this authorization decision. Refresh and try again.');
        }
        $this->redirect('/admin/recycler-verification/' . $owner);
    }

    public function viewLicenceSource(string $id,string $licenseId,string $version): void
    {
        $this->serveCoveragePdf($id,$licenseId,$version,false);
    }

    public function viewLicenceEvidence(string $id,string $evidenceId): void
    {
        $this->serveCoveragePdf($id,$evidenceId,'0',true);
    }

    private function serveCoveragePdf(string $id,string $sourceId,string $version,bool $historical): void
    {
        Auth::requireRole('ADMIN');
        $owner=filter_var($id,FILTER_VALIDATE_INT); $source=filter_var($sourceId,FILTER_VALIDATE_INT);
        if ($owner===false || $owner<=0 || $source===false || $source<=0) { http_response_code(404); return; }
        $db=Database::connection();
        try { (new RecyclerLicenceAuthorization($db))->requireAdmin((int)Auth::id()); }
        catch (DomainException) { http_response_code(403); return; }
        $sql=$historical ? 'SELECT document_path,document_sha256 FROM recycler_license_evidence WHERE recycler_user_id=? AND evidence_id=?'
            : 'SELECT document_path FROM recycler_licenses WHERE recycler_user_id=? AND license_id=? AND document_version=?';
        $s=$db->prepare($sql); $s->execute($historical ? [$owner,$source] : [$owner,$source,(int)$version]); $row=$s->fetch();
        $path=$row ? (new RecyclerLicenseDocumentValidator())->validatedPath($row['document_path']) : null;
        if ($path===null || ($historical && !hash_equals($row['document_sha256'],(string)hash_file('sha256',$path)))) { http_response_code(404); return; }
        header('Content-Type: application/pdf'); header('X-Content-Type-Options: nosniff'); header('Cache-Control: private, no-store');
        header('Content-Disposition: inline; filename="cea-evidence.pdf"'); header('Content-Length: ' . filesize($path));
        readfile($path);
    }

    public function reviewCapabilityChange(string $id, string $requestId): void
    {
        Auth::requireRole('ADMIN');
        $owner = filter_var($id, FILTER_VALIDATE_INT);
        $request = filter_var($requestId, FILTER_VALIDATE_INT);
        if ($owner === false || $owner <= 0 || $request === false || $request <= 0) {
            http_response_code(404);
            return;
        }
        if (!$this->hasValidCsrfToken()) {
            http_response_code(403);
            echo '403 Invalid or expired form. Please reload the page.';
            return;
        }
        try {
            (new RecyclerCapabilityChangeReviewService())->review($owner, $request, (int) Auth::id(), $this->postString('decision'), $this->postString('review_note'));
            Session::put('success', 'Capability change request reviewed successfully.');
        } catch (DomainException $error) {
            Session::put('error', $error->getMessage());
        } catch (Throwable $error) {
            error_log('Capability change review failed category=' . $error::class);
            Session::put('error', 'Unable to review this request. Please refresh and try again.');
        }
        $this->redirect('/admin/recycler-verification/' . $owner);
    }

    public function reviewRecyclerCapability(string $id): void
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
                'Capability review database failure category=' . $e::class
            );

            $_SESSION['error'] =
                'Capability review could not be completed.';

        } catch (InvalidArgumentException | DomainException | RuntimeException $e) {
            $_SESSION['error'] = $e->getMessage();

        } catch (Throwable $e) {
            error_log(
                'Capability review failure category=' . $e::class
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
                'Authorized activity review database failure category='
                . $e::class
            );

            $_SESSION['error'] =
                'Authorized activity review could not be completed.';

        } catch (InvalidArgumentException | DomainException | RuntimeException $e) {
            $_SESSION['error'] = $e->getMessage();

        } catch (Throwable $e) {
            error_log(
                'Authorized activity review failure category=' . $e::class
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

    public function viewRecyclerLicense(string $id): void
    {
        Auth::requireRole('ADMIN');

        $recyclerId = filter_var($id, FILTER_VALIDATE_INT);

        if ($recyclerId === false || $recyclerId <= 0) {
            http_response_code(404);
            return;
        }

        $license = (new AuthorizedRecycler())
            ->latestLicenseForRecycler($recyclerId);

        if ($license === null) {
            http_response_code(404);
            return;
        }

        // Reuse the centralized PDF document validator.
        $validator = new RecyclerLicenseDocumentValidator();

        $filePath = $validator->validatedPath(
            $license['document_path'] ?? null
        );

        if ($filePath === null) {
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

    public function viewRecyclerLicenseUpdateDocument(
        string $id,
        string $requestId
    ): void {
        Auth::requireRole('ADMIN');

        $recyclerId = filter_var($id, FILTER_VALIDATE_INT);
        $updateRequestId = filter_var($requestId, FILTER_VALIDATE_INT);

        if (
            $recyclerId === false ||
            $recyclerId <= 0 ||
            $updateRequestId === false ||
            $updateRequestId <= 0
        ) {
            http_response_code(404);
            return;
        }

        // Retrieve only a request belonging to this recycler.
        $stmt = Database::connection()->prepare(
            "SELECT document_path
            FROM recycler_license_update_requests
            WHERE request_id = :request_id
            AND recycler_user_id = :recycler_id
            LIMIT 1"
        );

        $stmt->execute([
            'request_id' => $updateRequestId,
            'recycler_id' => $recyclerId
        ]);

        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($request === false) {
            http_response_code(404);
            return;
        }

        // Reuse the existing secure PDF validator.
        $validator = new RecyclerLicenseDocumentValidator();

        $filePath = $validator->validatedPath(
            $request['document_path'] ?? null
        );

        if ($filePath === null) {
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
            'Content-Disposition: inline; filename="cea-license-update.pdf"'
        );
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
