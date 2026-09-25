<?php
declare(strict_types=1);

class MunicipalOfficerController extends Controller
{
    public function dashboard(): void
    {
        Auth::requireRole('MUNICIPAL_OFFICER');
        $this->view('municipal_officer/dashboard', ['currentPage' => 'dashboard']);
    }

    public function campaigns(): void
    {
        Auth::requireRole('MUNICIPAL_OFFICER');
        $this->view('municipal_officer/campaigns', ['currentPage' => 'campaigns']);
    }

    public function areaSchedules(): void
    {
        Auth::requireRole('MUNICIPAL_OFFICER');
        $this->renderAreaSchedules();
    }

    private function renderAreaSchedules(array $errors = [], array $old = []): void
    {
        $this->view('municipal_officer/area-schedules', [
            'currentPage' => 'area-schedules',
            'campaigns' => (new MonthlyCampaign())->openCampaigns(),
            'areas' => (new PostalCodeArea())->activeAreas(),
            'schedules' => (new AreaCollectionSchedule())->getSchedulesForOfficerView(),
            'showCreate' => $errors !== [] || ($_GET['create'] ?? '') === '1',
            'errors' => $errors,
            'old' => $old,
            'notice' => Session::pullFlash('schedule_notice'),
            'csrfToken' => Csrf::token(),
        ]);
    }

    private function parseDate(string $value): ?DateTimeImmutable
    {
        if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $value)) {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('Asia/Colombo'));
        return $date !== false && $date->format('Y-m-d') === $value ? $date : null;
    }

    private function positiveInteger(string $value, int $max = PHP_INT_MAX): ?int
    {
        if (!ctype_digit($value)) {
            return null;
        }
        $number = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => $max]]);
        return $number === false ? null : $number;
    }

    private function scheduleCsrf(): bool
    {
        if ($this->hasValidCsrfToken()) {
            return true;
        }
        http_response_code(403);
        echo 'Your session expired. Reload the schedule page and try again.';
        return false;
    }

    public function storeAreaSchedule(): void
    {
        Auth::requireRole('MUNICIPAL_OFFICER');
        if (!$this->scheduleCsrf()) {
            return;
        }
        $input = [];
        foreach (['campaign_id', 'postal_area_id', 'request_capacity', 'request_cutoff_date', 'collection_date'] as $field) {
            $input[$field] = $this->postString($field);
        }
        $campaignId = $this->positiveInteger($input['campaign_id']);
        $areaId = $this->positiveInteger($input['postal_area_id']);
        // The existing column is INT UNSIGNED, not an arbitrary PHP integer.
        $capacity = $this->positiveInteger($input['request_capacity'], 4294967295);
        $cutoff = $this->parseDate($input['request_cutoff_date']);
        $collection = $this->parseDate($input['collection_date']);
        $errors = [];
        if ($campaignId === null) {
            $errors[] = 'Please select a valid monthly campaign.';
        }
        if ($areaId === null) {
            $errors[] = 'Please select a valid postal-code area.';
        }
        if ($capacity === null) {
            $errors[] = 'Maximum requests must be a positive integer within the database limit (4294967295).';
        }
        if ($cutoff === null) {
            $errors[] = 'Please select a valid request cut-off date.';
        }
        if ($collection === null) {
            $errors[] = 'Please select a valid collection date.';
        }
        $today = new DateTimeImmutable('today', new DateTimeZone('Asia/Colombo'));
        if ($cutoff !== null && $cutoff < $today) {
            $errors[] = 'The request cut-off date cannot be in the past.';
        }
        if ($cutoff !== null && $collection !== null && $cutoff >= $collection) {
            $errors[] = 'The request cut-off date must be before the collection date.';
        }
        if ($collection !== null && $collection < $today) {
            $errors[] = 'The collection date cannot be in the past.';
        }
        if ($errors === []) {
            $model = new AreaCollectionSchedule();
            try {
                $errors = $model->transaction(function () use ($model, $campaignId, $areaId, $capacity, $cutoff, $collection): array {
                    // Serialize creates for a campaign, including when it has no schedules yet.
                    $campaign = $model->lockCampaign($campaignId);
                    $area = $model->lockArea($areaId);
                    $errors = [];
                    if ($campaign === null || $campaign['campaign_status'] !== 'OPEN') {
                        $errors[] = 'The selected monthly campaign does not exist or is not open.';
                    }
                    if ($area === null || $area['area_status'] !== 'ACTIVE') {
                        $errors[] = 'The selected postal-code area does not exist or is not active.';
                    }
                    if ($campaign !== null && substr($campaign['campaign_month'], 0, 7) !== $collection->format('Y-m')) {
                        $errors[] = 'The collection date must be within the selected campaign month and year.';
                    }
                    if ($model->countForCampaignAndArea($campaignId, $areaId) >= 2) {
                        $errors[] = 'This postal-code area already has the maximum of 2 non-cancelled schedules for this monthly campaign.';
                    }
                    if ($model->existsForCampaignAreaDate($campaignId, $areaId, $collection->format('Y-m-d'))) {
                        $errors[] = 'A schedule already exists for this campaign and area on the selected date (including cancelled schedules).';
                    }
                    if ($errors === []) {
                        $model->create([
                            'campaign_id' => $campaignId,
                            'postal_area_id' => $areaId,
                            'created_by_officer_user_id' => (int) Auth::id(),
                            'collection_date' => $collection->format('Y-m-d'),
                            'request_cutoff_at' => $cutoff->format('Y-m-d') . ' 23:59:59',
                            'request_capacity' => $capacity,
                            'schedule_status' => 'PLANNED',
                        ]);
                    }
                    return $errors;
                });
            } catch (PDOException $exception) {
                $errors[] = $this->scheduleDatabaseError($exception);
            }
        }
        if ($errors !== []) {
            http_response_code(422);
            $this->renderAreaSchedules($errors, $input);
            return;
        }
        Session::flash('schedule_notice', 'Collection schedule created successfully.');
        $this->redirect('/officer/area-schedules');
    }

    public function showAreaSchedule(string $id): void
    {
        Auth::requireRole('MUNICIPAL_OFFICER');
        $this->renderScheduleDetails($id);
    }

    private function renderScheduleDetails(string $id, array $errors = [], array $old = []): void
    {
        $number = $this->positiveInteger($id);
        $schedule = $number === null ? null : (new AreaCollectionSchedule())->getScheduleDetails($number);
        if ($schedule === null) {
            http_response_code(404);
            echo 'Collection schedule not found.';
            return;
        }
        $this->view('municipal_officer/area-schedule-details', [
            'currentPage' => 'area-schedules',
            'schedule' => $schedule,
            'statuses' => AreaCollectionSchedule::manualStatuses($schedule),
            'errors' => $errors,
            'old' => $old,
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function updateAreaSchedule(string $id): void
    {
        Auth::requireRole('MUNICIPAL_OFFICER');
        if (!$this->scheduleCsrf()) {
            return;
        }
        $number = $this->positiveInteger($id);
        if ($number === null) {
            http_response_code(404);
            echo 'Collection schedule not found.';
            return;
        }
        $input = ['request_capacity' => $this->postString('request_capacity'), 'schedule_status' => $this->postString('schedule_status')];
        $capacity = $this->positiveInteger($input['request_capacity'], 4294967295);
        $model = new AreaCollectionSchedule();
        try {
            $errors = $model->transaction(function () use ($model, $number, $capacity, $input): array {
                $schedule = $model->lockSchedule($number);
                if ($schedule === null) {
                    return ['Collection schedule not found.'];
                }
                if (in_array($schedule['schedule_status'], ['COMPLETED', 'CANCELLED'], true)) {
                    return ['Completed and cancelled schedules are read-only.'];
                }
                $errors = [];
                $count = $model->countActiveRequests($number);
                if ($capacity === null) {
                    $errors[] = 'Maximum requests must be a positive integer within the database limit (4294967295).';
                } elseif ($capacity < $count) {
                    $errors[] = "Maximum requests cannot be lower than the {$count} existing requests.";
                }
                if (!in_array($input['schedule_status'], AreaCollectionSchedule::manualStatuses($schedule), true)) {
                    $errors[] = 'This status transition is not allowed. A schedule can open only before its request cut-off has passed.';
                }
                if ($input['schedule_status'] === 'CANCELLED' && $model->hasStartedWork($number)) {
                    $errors[] = 'Cannot cancel a schedule with an active assignment or recorded collection work.';
                }
                if ($errors === []) {
                    if ($input['schedule_status'] === 'CANCELLED') {
                        $model->cancelPendingRequests($number);
                    }
                    // Explicit allowlist: no other submitted fields can change the record.
                    $model->update($number, ['request_capacity' => $capacity, 'schedule_status' => $input['schedule_status']]);
                }
                return $errors;
            });
        } catch (PDOException $exception) {
            $errors = [$this->scheduleDatabaseError($exception)];
        }
        if ($errors !== []) {
            http_response_code(422);
            $this->renderScheduleDetails($id, $errors, $input);
            return;
        }
        Session::flash('schedule_notice', 'Collection schedule updated successfully.');
        $this->redirect('/officer/area-schedules');
    }

    public function deleteAreaSchedule(string $id): void
    {
        Auth::requireRole('MUNICIPAL_OFFICER');
        if (!$this->scheduleCsrf()) {
            return;
        }
        $number = $this->positiveInteger($id);
        if ($number === null) {
            http_response_code(404);
            echo 'Collection schedule not found.';
            return;
        }
        $model = new AreaCollectionSchedule();
        try {
            $errors = $model->transaction(function () use ($model, $number): array {
                if ($model->lockSchedule($number) === null) {
                    return ['Collection schedule not found.'];
                }
                if ($model->hasRequests($number) || $model->hasAssignments($number) || $model->hasCollectionSubmission($number)) {
                    return ['This schedule cannot be deleted because it already contains requests, assignments, or collection records. Cancel the schedule instead where its status permits.'];
                }
                $model->delete($number);
                return [];
            });
        } catch (PDOException $exception) {
            $errors = [$this->scheduleDatabaseError($exception)];
        }
        if ($errors !== []) {
            http_response_code(422);
            $this->renderScheduleDetails($id, $errors);
            return;
        }
        Session::flash('schedule_notice', 'Unused collection schedule deleted successfully.');
        $this->redirect('/officer/area-schedules');
    }

    private function scheduleDatabaseError(PDOException $exception): string
    {
        $code = (int) ($exception->errorInfo[1] ?? 0);
        if ($code === 1062) {
            $detail = (string) ($exception->errorInfo[2] ?? '');
            if (str_contains($detail, "'uq_schedules_campaign_area'")) {
                return 'The database still has the old one-schedule-per-area restriction. Apply the schedule date uniqueness migration.';
            }
            return 'A schedule already exists for this campaign, area and collection date.';
        }
        if ($code === 1451) return 'This schedule is already in use and cannot be deleted.';
        error_log('Schedule operation failed: SQLSTATE ' . $exception->getCode());
        return 'The schedule could not be saved. Please reload and try again. If this continues, check the database and officer profile.';
    }

    public function flaggedRequests(): void
    {
        Auth::requireRole('MUNICIPAL_OFFICER');
        $this->view('municipal_officer/flagged-requests', ['currentPage' => 'flagged-requests']);
    }

    public function collectionAssignments(): void
    {
        Auth::requireRole('MUNICIPAL_OFFICER');
        $this->view('municipal_officer/collection-assignments', ['currentPage' => 'collection-assignments']);
    }

    public function collectionRecords(): void
    {
        Auth::requireRole('MUNICIPAL_OFFICER');
        $this->view('municipal_officer/collection-records', ['currentPage' => 'collection-records']);
    }

    public function eLots(): void
    {
        Auth::requireRole('MUNICIPAL_OFFICER');
        $this->view('municipal_officer/elots', ['currentPage' => 'e-lots']);
    }

    public function feedback(): void
    {
        Auth::requireRole('MUNICIPAL_OFFICER');
        $this->view('municipal_officer/feedback', ['currentPage' => 'feedback']);
    }

    public function reports(): void
    {
        Auth::requireRole('MUNICIPAL_OFFICER');
        $this->view('municipal_officer/reports', ['currentPage' => 'reports']);
    }
}
