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

    public function profile(): void
    {
        Auth::requireRole('RECYCLER');

        $this->readPage(function (): void {
            $userId = (int) Auth::id();
            $db = Database::connection();

            $recycler = new AuthorizedRecycler();

            // Read the recycler's latest approved licence.
            // An expired approved licence is still shown
            // so its renewal details remain visible.
            $statement = $db->prepare(
                "SELECT
                    license_id,
                    license_number,
                    expiry_date,
                    license_status
                FROM recycler_licenses
                WHERE recycler_user_id = :recycler_user_id
                AND license_status = 'VALID'
                ORDER BY submitted_at DESC, license_id DESC
                LIMIT 1"
            );

            $statement->execute([
                'recycler_user_id' => $userId,
            ]);

            $license = $statement->fetch(PDO::FETCH_ASSOC);

            $currentLicense = $license === false
                ? null
                : $license;

            // Read only this recycler's pending request.
            $licenseRequestModel =
                new RecyclerLicenseUpdateRequest($db);

            $pendingLicenseUpdate =
                $licenseRequestModel->pendingForRecycler($userId);

            $this->view('recycler/profile', [
                'currentPage' => 'profile',
                'csrfToken' => Csrf::token(),

                'phoneChange' => $this->phoneChangeService()->pendingChange($userId),
                'phoneEditorOpen' => (bool) Session::pullFlash('phone_editor_open', false),

                'profileSuccess' =>
                    Session::pullFlash('profile_success'),

                'profileError' =>
                    Session::pullFlash('profile_error'),

                'profile' =>
                    $recycler->profileForRecycler($userId),

                'compliance' =>
                    $recycler->dashboardCompliance($userId),

                'capabilities' =>
                    $recycler->capabilitiesForRecycler($userId),

                'capabilityOptions' => (new RecyclerCapabilityChangeService($db))->options($userId),
                'capabilityRequests' => (new RecyclerCapabilityChangeRequest($db))->historyForRecycler($userId),
                'capabilityFormInput' => Session::pullFlash('capability_form_input', []),
                'licenceCoverage' => (new RecyclerLicenceCoverage($db))->history($userId),
                'licenceConflicts' => (new RecyclerLicenceCoverage($db))->conflicts($userId),

                'activities' =>
                    (new RecyclerAuthorizedActivity())
                        ->forRecycler($userId),

                'currentLicense' => $currentLicense,
                'pendingLicenseUpdate' => $pendingLicenseUpdate,
            ]);
        });
    }

    public function requestCapabilityChange(): void
    {
        Auth::requireRole('RECYCLER');
        if (!$this->hasValidCsrfToken()) {
            Session::flash('profile_error', 'Your session expired. Please reload the page and try again.');
            $this->redirect('/recycler/profile');
        }
        $category = filter_var($this->postString('category_id'), FILTER_VALIDATE_INT);
        $type = $this->postString('request_type');
        $risk = $this->postString('requested_can_handle_high_risk');
        $reason = $this->postString('request_reason');
        try {
            if ($category === false || $category <= 0 || !in_array($risk, ['', '0', '1'], true)) throw new DomainException('Select a valid category and high-risk permission.');
            (new RecyclerCapabilityChangeService())->submit((int) Auth::id(), $category, $type, $risk === '' ? null : (int) $risk, $reason);
            Session::flash('profile_success', 'Capability change submitted for Administrator review. Your current capabilities are unchanged.');
        } catch (DomainException $error) {
            Session::flash('profile_error', $error->getMessage());
            Session::flash('capability_form_input', ['category_id' => (int) $category, 'request_type' => $type, 'risk' => $risk, 'reason' => mb_substr($reason, 0, 500, 'UTF-8')]);
        } catch (Throwable $error) {
            error_log('Capability request failed category=' . $error::class);
            Session::flash('profile_error', 'Unable to submit the capability request. Please try again later.');
        }
        $this->redirect('/recycler/profile');
    }

    protected function phoneChangeService(): RecyclerPhoneChangeService
    {
        return new RecyclerPhoneChangeService();
    }

    public function requestPhoneChange(): void
    {
        $this->handlePhoneChange('request');
    }

    public function verifyPhoneChange(): void
    {
        $this->handlePhoneChange('verify');
    }

    public function resendPhoneChange(): void
    {
        $this->handlePhoneChange('resend');
    }

    public function cancelPhoneChange(): void
    {
        $this->handlePhoneChange('cancel');
    }

    protected function rotatePhoneChangeSession(): bool
    {
        return Session::regenerate();
    }

    private function handlePhoneChange(string $action): void
    {
        Auth::requireRole('RECYCLER');
        Session::flash('phone_editor_open', true);
        if (!$this->hasValidCsrfToken()) {
            Session::flash('profile_error', 'Your session expired. Please try again.');
            $this->redirect('/recycler/profile');
        }
        $reloginNotice = null;
        try {
            $service = $this->phoneChangeService();
            $userId = (int) Auth::id();
            switch ($action) {
                case 'request':
                    $service->startChange($userId, $this->postString('current_password', false), $this->postString('proposed_mobile_number'));
                    $message = 'A verification code was sent to your proposed mobile number.';
                    break;
                case 'verify':
                    $service->verifyChange($userId, $this->postString('otp'));
                    try {
                        if (!$this->rotatePhoneChangeSession()) throw new RuntimeException('Session rotation failed.');
                        Csrf::regenerate();
                    } catch (Throwable) {
                        $reloginNotice = 'Your phone number was updated. Your session could not be renewed. Sign in again using your updated mobile number.';
                        break;
                    }
                    Session::flash('phone_editor_open', false);
                    $message = 'Phone number updated successfully. Use your new number for future logins.';
                    break;
                case 'resend':
                    $service->resend($userId);
                    $message = 'A new verification code was sent. The previous code is no longer valid.';
                    break;
                case 'cancel':
                    $service->cancel($userId);
                    Session::flash('phone_editor_open', false);
                    $message = 'Phone number change cancelled. Your current number is unchanged.';
                    break;
            }
            if ($reloginNotice === null) Session::flash('profile_success', $message);
        } catch (PhoneChangeOutcomeUnknownException) {
            $reloginNotice = 'We could not confirm the phone change outcome. Sign in again and check your profile. Your new number may already be active; if it is not, use your previous number.';
        } catch (OtpRateLimitException $error) {
            Session::flash('profile_error', $error->getMessage() . ' Retry in ' . $error->retryAfterSeconds() . ' seconds.');
        } catch (DomainException $error) {
            Session::flash('profile_error', $error->getMessage());
        } catch (Throwable $error) {
            // Exception category only: never log arguments, SQL, OTPs or credentials.
            error_log('Recycler phone change failure category=' . $error::class);
            Session::flash('profile_error', 'Unable to complete the phone change right now. Please try again later.');
        }
        if ($reloginNotice !== null) {
            Auth::logout();
            Session::flash('auth_success', $reloginNotice);
            $this->redirect('/login');
        }
        $this->redirect('/recycler/profile');
    }

    public function updateProfileContact(): void
    {
        Auth::requireRole('RECYCLER');

        if (!$this->hasValidCsrfToken()) {
            http_response_code(403);
            echo '403 Invalid or expired form.';
            return;
        }

        $fullName = $this->postString('full_name');

        if (
            preg_match(
                '/\A(?=.{2,120}\z)[^\x00-\x1F\x7F]+\z/u',
                $fullName
            ) !== 1
        ) {
            Session::flash(
                'profile_error',
                'Contact person name must contain 2 to 120 valid characters.'
            );

            $this->redirect('/recycler/profile');
        }

        try {
            (new AuthorizedRecycler())->updateContactPerson(
                (int) Auth::id(),
                $fullName
            );

            Session::flash(
                'profile_success',
                'Contact person updated successfully.'
            );
        } catch (Throwable $error) {
            error_log('Recycler contact update failed: ' . $error);

            Session::flash(
                'profile_error',
                'Unable to update contact person. Please try again.'
            );
        }

        $this->redirect('/recycler/profile');
    }

    public function submitLicenseUpdate(): void
    {
        Auth::requireRole('RECYCLER');

        if (!$this->hasValidCsrfToken()) {
            http_response_code(403);
            echo '403 Invalid or expired form.';
            return;
        }

        $input = [
            'requested_license_number' =>
                $_POST['requested_license_number'] ?? null,
            'requested_expiry_date' =>
                $_POST['requested_expiry_date'] ?? null,
        ];

        $file = $_FILES['license_document']
            ?? ['error' => UPLOAD_ERR_NO_FILE];

        try {
            if (!is_array($file)) {
                throw new InvalidArgumentException(
                    'A valid CEA licence PDF is required.'
                );
            }

            $service = new RecyclerLicenseUpdateService();

            $service->submit(
                (int) Auth::id(),
                $input,
                $file
            );

            Session::flash(
                'profile_success',
                'CEA licence update submitted for Admin review. '
                . 'Your current approved licence remains unchanged.'
            );

        } catch (PDOException $error) {
            error_log(
                'Recycler licence update database failure: '
                . $error::class
            );

            Session::flash(
                'profile_error',
                'Unable to submit the licence update. Please try again.'
            );

        } catch (InvalidArgumentException | RuntimeException $error) {
            Session::flash(
                'profile_error',
                $error->getMessage()
            );

        } catch (Throwable $error) {
            error_log(
                'Recycler licence update failure: '
                . $error::class
            );

            Session::flash(
                'profile_error',
                'Unable to submit the licence update. Please try again.'
            );
        }

        $this->redirect('/recycler/profile');
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
