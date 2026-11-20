<?php
declare(strict_types=1);

class RecyclerController extends Controller
{
    public function dashboard(): void
    {
        Auth::requireRole('RECYCLER');

        $this->readPage(function (): void {
            $userId = (int) Auth::id();

            $data = $this->reportingData($userId);

            $recycler = new AuthorizedRecycler();

            $activities = (new RecyclerAuthorizedActivity())
                ->forRecycler($userId);

            $this->view('recycler/dashboard', $data + [
                'currentPage' => 'dashboard',

                'eligibleCount' => count(
                    (new ELot())->eligibleForRecycler($userId)
                ),

                'dashboardProfile' => $recycler->profileForRecycler($userId),

                'compliance' => $recycler->dashboardCompliance($userId),

                'capabilities' => $recycler->capabilitiesForRecycler($userId),

                'activities' => $activities,
            ]);
        });
    }

    public function eligibleELots(): void
    {
        Auth::requireRole('RECYCLER');
        $this->readPage(function (): void {
            $this->view('recycler/eligible_e-lots', ['currentPage' => 'eligible-e-lots',
                'lots' => (new ELot())->eligibleForRecycler((int) Auth::id())]);
        });
    }

    public function eLotDetails(string $id): void
    {
        Auth::requireRole('RECYCLER');
        $lotId = $this->positiveId($id);
        if ($lotId === null) return;
        $this->readPage(function () use ($lotId): void {
            $model = new ELot();
            $lot = $model->findVisibleForRecycler($lotId, (int) Auth::id());
            if (!$lot) { http_response_code(404); echo '404 E-Lot not found'; return; }
            $this->view('recycler/e_lot_details', ['currentPage' => 'eligible-e-lots',
                'lot' => $lot, 'suggestedAmount' => RecyclerBidService::suggestedAmount($lot['highest_amount']), 'items' => $model->itemDetails($lotId)]);
        });
    }

    public function myBids(): void
    {
        Auth::requireRole('RECYCLER');
        $this->readPage(function (): void {
            $this->view('recycler/my_bids', ['currentPage' => 'my-bids',
                'bids' => (new RecyclerBid())->listForRecycler((int) Auth::id())]);
        });
    }

    public function placeBid(string $id): void { $this->mutateBid('place', $id); }
    public function updateBid(string $id): void { $this->mutateBid('update', $id); }
    public function withdrawBid(string $id): void { $this->mutateBid('withdraw', $id); }

    private function mutateBid(string $action, string $id): void
    {
        Auth::requireRole('RECYCLER');
        $recordId = $this->positiveId($id);
        if ($recordId === null) return;
        if (!$this->hasValidCsrfToken()) {
            http_response_code(403);
            echo '403 Invalid or expired form. Please reload the page and try again.';
            return;
        }
        $destination = $action === 'place' ? '/recycler/eligible-e-lots' : '/recycler/my-bids';
        try {
            $service = new RecyclerBidService();
            $userId = (int) Auth::id();
            $owned = $action === 'place' ? null : (new RecyclerBid())->findOwnedBid($recordId, $userId);
            $returnLotId = $action === 'place' ? $recordId : (int) ($owned['e_lot_id'] ?? 0);
            if ($returnLotId && (new ELot())->findVisibleForRecycler($returnLotId, $userId)) {
                $destination = '/recycler/e-lot/' . $returnLotId . '#bid-form';
            }
            if ($action === 'place') {
                $service->placeBid($userId, $recordId, $this->postString('bid_amount'));
                $lotId = $recordId;
            } elseif ($action === 'update') {
                $lotId = $service->updateBid($userId, $recordId, $this->postString('bid_amount'));
            } else {
                $lotId = $service->withdrawBid($userId, $recordId);
            }
            $destination = '/recycler/e-lot/' . $lotId;
            Session::flash('bid_success', $action === 'withdraw' ? 'Bid withdrawn. It remains in your bid history.' : 'Your bid has been saved.');
        } catch (DomainException $error) {
            if ($error->getCode() === 404) { http_response_code(404); echo '404 Record not found'; return; }
            Session::flash('bid_error', $error->getMessage());
        } catch (Throwable $error) {
            error_log('Recycler bid mutation failed: ' . $error);
            Session::flash('bid_error', 'We could not save your bid. Please try again.');
        }
        $this->redirect($destination);
    }

    private function positiveId(string $id): ?int
    {
        if (!preg_match('/^[1-9][0-9]*$/D', $id) || filter_var($id, FILTER_VALIDATE_INT) === false) {
            http_response_code(404); echo '404 Record not found'; return null;
        }
        return (int) $id;
    }

    private function readPage(callable $render): void
    {
        try { $render(); }
        catch (Throwable $error) {
            error_log('Recycler bid read failed: ' . $error);
            http_response_code(500);
            echo 'We could not load your bidding information. Please try again later.';
        }
    }

    public function awardedELots(): void
    {
        Auth::requireRole('RECYCLER');
        $this->readPage(function (): void {
            $this->view('recycler/awarded_e-lots', ['currentPage' => 'awarded-e-lots',
                'awardedLots' => (new RecyclerBid())->listWinningForRecycler((int) Auth::id())]);
        });
    }

    public function awardedELotDetails(string $id): void
    {
        Auth::requireRole('RECYCLER');
        $lotId = $this->positiveId($id);
        if ($lotId === null) return;
        $this->readPage(function () use ($lotId): void {
            $userId = (int) Auth::id();
            $lots = new ELot();
            $lot = $lots->findVisibleForRecycler($lotId, $userId);
            if (!$lot || $lot['bid_status'] !== 'WINNING') {
                http_response_code(404); echo '404 Awarded E-Lot not found'; return;
            }
            $this->view('recycler/awarded_e-lot_details', ['currentPage' => 'awarded-e-lots',
                'lot' => $lot, 'items' => $lots->itemDetails($lotId),
                'handover' => (new RecyclerBid())->handoverForOwnedWinningBid((int) $lot['bid_id'], $userId)]);
        });
    }

    protected function profileService(): RecyclerProfileService
    {
        return new RecyclerProfileService();
    }

    private function profileContext(): string
    {
        if (!Session::has('_recycler_phone_context')) Session::put('_recycler_phone_context', bin2hex(random_bytes(32)));
        return session_id() . ':' . Session::get('_recycler_phone_context');
    }

    public function saveContact(): void { $this->mutateProfile('contact'); }
    public function requestPhone(): void { $this->mutateProfile('request'); }
    public function verifyPhone(): void { $this->mutateProfile('verify'); }
    public function resendPhone(): void { $this->mutateProfile('resend'); }
    public function cancelPhone(): void { $this->mutateProfile('cancel'); }

    private function mutateProfile(string $action): void
    {
        Auth::requireRole('RECYCLER');
        if (!$this->hasValidCsrfToken()) {
            http_response_code(403);
            echo '403 Invalid or expired form. Reload your Profile and try again.';
            return;
        }
        $owner = (int) Auth::id();
        try {
            $service = $this->profileService();
            $context = $this->profileContext();
            $result = match ($action) {
                'contact' => $service->contact($owner, $this->postString('contact_person')),
                'request' => $service->beginPhone($owner, $this->postString('mobile_number'), $this->postString('current_password'), $context),
                'verify' => $service->verify($owner, $context, $this->postString('otp')),
                'resend' => $service->resend($owner, $context),
                'cancel' => $service->cancel($owner, $context),
            };
            if ($result['success'] && $action === 'contact') {
                $user = (new User())->find($owner);
                Session::put('auth_user', ['id'=>$owner,'role'=>'RECYCLER','name'=>$user['full_name']]);
            }
            if ($result['success'] && $action === 'verify') {
                Session::regenerate();
                Csrf::regenerate();
                Session::forget('_recycler_phone_context');
            }
        } catch (DomainException $error) {
            $result = ['success'=>false,'message'=>$error->getMessage(),'field'=>'account'];
        } catch (Throwable $error) {
            // No exception details: SMS providers and submitted credentials may contain secrets.
            $result = ['success'=>false,'message'=>'Unable to save this change. The number may be reserved, or phone editing may be unavailable. Please try again later.','field'=>'account'];
        }
        if (!$result['success']) {
            // Passwords and verification codes must never enter flash state.
            $result['value'] = mb_substr($this->postString($action === 'contact' ? 'contact_person' : 'mobile_number'), 0, 120);
        }
        Session::flash('profile_feedback', $result);
        $edit = $action === 'contact' ? 'contact' : 'phone';
        $this->redirect('/recycler/profile' . ($result['success'] && in_array($action, ['contact','verify','cancel'], true) ? '' : '?edit=' . $edit) . '#profile-company-heading');
    }

    public function submitLicenseUpdate(): void
    {
        Auth::requireRole('RECYCLER');
        if (!$this->hasValidCsrfToken()) { http_response_code(403); echo '403 Invalid or expired form.'; return; }
        $input = [];
        foreach (['license_number','expiry_date','explanation'] as $field) $input[$field] = $this->postString($field);
        try {
            (new RecyclerLicenseRenewalService())->submit((int)Auth::id(),$input,is_array($_FILES['licence_pdf'] ?? null) ? $_FILES['licence_pdf'] : []);
            Session::flash('renewal_feedback',['success'=>true,'message'=>'Licence update submitted for Administrator review. Your approved licence is unchanged.']);
            $this->redirect('/recycler/profile#profile-licence-heading');
        } catch (DomainException|InvalidArgumentException $error) {
            $message = $error->getMessage();
        } catch (Throwable $error) {
            $message = 'The licence update could not be submitted. Reload and try again; the number may already be registered.';
        }
        foreach ($input as &$value) $value = mb_substr($value,0,500);
        unset($value);
        Session::flash('renewal_feedback',['success'=>false,'message'=>$message,'input'=>$input]);
        $this->redirect('/recycler/profile?edit=licence#profile-licence-heading');
    }

    public function licenseUpdateDocument(string $requestId): void { $this->profileLicenseDocument($requestId); }
    public function licensePreviousDocument(string $requestId): void { $this->profileLicenseDocument($requestId,true); }

    public function submitCapabilityChange(): void
    {
        Auth::requireRole('RECYCLER');
        if (!$this->hasValidCsrfToken()) { http_response_code(403); echo '403 Invalid or expired form.'; return; }
        $input=[];
        foreach (['category_id','request_type','requested_can_handle_high_risk','request_reason'] as $field) $input[$field]=$this->postString($field);
        try {
            (new RecyclerCapabilityChangeService())->submit((int)Auth::id(),$input);
            Session::flash('capability_feedback',['success'=>true,'message'=>'Capability change submitted for Administrator review. Your current permissions are unchanged.']);
            $this->redirect('/recycler/profile#profile-capabilities-heading');
        } catch (DomainException|InvalidArgumentException $error) { $message=$error->getMessage(); }
        catch (Throwable $error) { $message='The request could not be saved. Reload and check for a pending or conflicting change.'; }
        foreach($input as &$value)$value=mb_substr($value,0,500);
        unset($value);
        Session::flash('capability_feedback',['success'=>false,'message'=>$message,'input'=>$input]);
        $this->redirect('/recycler/profile?edit=capability#profile-capabilities-heading');
    }

    public function profile(): void
    {
        Auth::requireRole('RECYCLER');
        $this->readPage(function (): void {
            $userId = (int) Auth::id();
            $recycler = new AuthorizedRecycler();
            $license = $recycler->profileLicenseForRecycler($userId);
            $document = (new RecyclerLicenseRenewalService())->currentDocument($userId,$license);
            $scopes = [];
            $scopeAvailable = true;
            try {
                $scopes = $recycler->initialScopes($userId);
            } catch (PDOException $error) {
                if ($error->getCode() !== '42S02') throw $error;
                $scopeAvailable = false;
            }
            $documentHash = $document !== null ? hash_file('sha256', $document) : null;
            $licenseValid = $license !== null && $license['license_status'] === 'VALID' && ($license['approval_recorded'] ?? true)
                && $license['license_expiry'] >= (new DateTimeImmutable('today', new DateTimeZone('Asia/Colombo')))->format('Y-m-d');
            foreach ($scopes as &$scope) {
                // Evidence identity only: this does not grant category/activity permission.
                $scope['current_document'] = $licenseValid && $documentHash !== null
                    && (int) $scope['license_id'] === (int) $license['license_id']
                    && $scope['license_number'] === $license['license_no']
                    && $scope['expiry_date'] === $license['license_expiry']
                    && $scope['document_path'] === $license['document_path']
                    && hash_equals($scope['document_sha256'], $documentHash);
            }
            unset($scope);
            $this->view('recycler/profile', [
                'currentPage' => 'profile',
                'capabilityAvailable' => (new RecyclerCapabilityChangeService())->available(),
                'capabilityRequests' => (new RecyclerCapabilityChangeService())->requests($userId),
                'capabilityCategories' => (new RecyclerCapabilityChangeService())->categories(),
                'capabilityFeedback' => Session::pullFlash('capability_feedback'),
                'renewalAvailable' => (new RecyclerLicenseRenewalService())->available(),
                'renewalRequests' => (new RecyclerLicenseRenewalService())->requests($userId),
                'renewalFeedback' => Session::pullFlash('renewal_feedback'),
                'editState' => $this->profileService()->state($userId, $this->profileContext()),
                'edit' => is_string($_GET['edit'] ?? null) ? $_GET['edit'] : '',
                'feedback' => Session::pullFlash('profile_feedback'),
                'csrf' => Csrf::token(),
                'profile' => $recycler->profileForRecycler($userId),
                'license' => $license,
                'licenseValid' => $licenseValid,
                'documentAvailable' => $document !== null,
                'scopes' => $scopes,
                'scopeAvailable' => $scopeAvailable,
                'compliance' => $recycler->dashboardCompliance($userId),
                'capabilities' => $recycler->capabilitiesForRecycler($userId),
                'activities' => (new RecyclerAuthorizedActivity())
                    ->forRecycler($userId),
            ]);
        });
    }

    /** Read only the authenticated owner's latest submitted document. */
    public function profileLicenseDocument(?string $requestId = null, bool $previous = false): void
    {
        Auth::requireRole('RECYCLER');
        $this->readPage(function () use ($requestId,$previous): void {
            $license = (new AuthorizedRecycler())->profileLicenseForRecycler((int) Auth::id());
            $renewal = new RecyclerLicenseRenewalService();
            $path = $requestId === null ? $renewal->currentDocument((int)Auth::id(),$license)
                : (preg_match('/^[1-9][0-9]*$/D',$requestId) ? $renewal->document((int)Auth::id(),(int)$requestId,$previous) : null);
            if ($path === null || ($size = filesize($path)) === false) {
                http_response_code(404);
                return;
            }
            header('Content-Type: application/pdf');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: private, no-store');
            header('Content-Disposition: inline; filename="cea-licence.pdf"');
            header('Content-Length: ' . $size);
            readfile($path);
        });
    }

    public function reports(): void
    {
        Auth::requireRole('RECYCLER');
        $this->readPage(function (): void {
            $this->view('recycler/reports', $this->reportingData((int) Auth::id()) + ['currentPage' => 'reports']);
        });
    }

    /** Current records, not an audit log: no revision or withdrawal times are inferred. */
    private function reportingData(int $userId): array
    {
        $model = new RecyclerBid();
        $bids = $model->listForRecycler($userId);
        $awards = $model->listWinningForRecycler($userId);
        $statuses = array_count_values(array_column($bids, 'bid_status'));
        return [
            'bidCount' => count($bids),
            'submittedCount' => $statuses['SUBMITTED'] ?? 0,
            'wonCount' => $statuses['WINNING'] ?? 0,
            'rejectedCount' => $statuses['REJECTED'] ?? 0,
            'withdrawnCount' => $statuses['WITHDRAWN'] ?? 0,
            'awardedCount' => count($awards),
            // Only recorded pending/scheduled handovers are counted as awaiting.
            'awaitingCount' => count(array_filter($awards, static fn (array $award): bool =>
                in_array($award['handover_status'], ['PENDING', 'SCHEDULED'], true))),
            'handedCount' => count(array_filter($awards, static fn (array $award): bool =>
                $award['handover_status'] === 'COMPLETED')),
            'recentBids' => array_slice($bids, 0, 5),
            'recentAwards' => array_slice($awards, 0, 5),
        ];
    }
}
