<?php
declare(strict_types=1);

class CollectorController extends Controller
{
    public function dashboard(): void
    {
        Auth::requireRole('COLLECTOR');
        $this->view('collector/dashboard', ['currentPage' => 'dashboard', 'schedules' => (new CollectionRecord())->assignedSchedules((int) Auth::id())]);
    }

    public function myRequests(): void
    {
        Auth::requireRole('COLLECTOR');
        $this->redirect('/collector/schedules');
    }

    public function schedules(): void
    {
        Auth::requireRole('COLLECTOR');
        $this->view('collector/assigned-schedules', [
            'currentPage' => 'schedules', 'schedules' => (new CollectionRecord())->assignedSchedules((int) Auth::id()),
            'notice' => Session::pullFlash('collection_notice'), 'error' => Session::pullFlash('collection_error'),
        ]);
    }

    public function showSchedule(string $id): void
    {
        Auth::requireRole('COLLECTOR');
        $model = new CollectionRecord();
        try {
            $schedule = $model->assignedSchedule($this->id($id), (int) Auth::id());
            if (!$schedule) throw new DomainException('Assigned schedule not found.');
        } catch (DomainException $error) {
            http_response_code(404);
            $this->view('collector/error', ['currentPage' => 'schedules', 'message' => 'Assigned schedule not found.']);
            return;
        }
        $requests = $model->assignedRequests((int) Auth::id(), (int) $schedule['schedule_id']);
        $this->view('collector/assigned-requests', [
            'currentPage' => 'schedules', 'schedule' => $schedule, 'requests' => $requests,
            'canSubmit' => CollectionRecord::canSubmit($schedule, $requests),
            'csrfToken' => Csrf::token(), 'notice' => Session::pullFlash('collection_notice'),
            'error' => Session::pullFlash('collection_error'),
        ]);
    }

    private function id(string $value): int
    {
        $id = ctype_digit($value) ? filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
        if ($id === false) throw new DomainException('Invalid record, request or schedule ID.');
        return $id;
    }

    public function legacyRequest(string $id): void
    {
        Auth::requireRole('COLLECTOR');
        $this->redirect('/collector/requests/' . rawurlencode($id));
    }

    public function showRequest(string $id): void { $this->details($id, false); }
    public function showRecord(string $id): void { $this->details($id, true); }

    private function details(string $id, bool $record): void
    {
        Auth::requireRole('COLLECTOR');
        try {
            $model = new CollectionRecord();
            $request = $record ? $model->ownedRecord($this->id($id), (int) Auth::id()) : $model->assignedRequest($this->id($id), (int) Auth::id());
            if (!$request) throw new DomainException('Collection request not found.');
        } catch (DomainException $error) {
            http_response_code(404);
            $this->view('collector/error', ['currentPage' => 'schedules', 'message' => 'Collection request not found.']);
            return;
        }
        $this->view('collector/collection-record', [
            'currentPage' => 'schedules', 'request' => $request, 'csrfToken' => Csrf::token(),
            'editable' => CollectionRecord::editable($request), 'error' => Session::pullFlash('collection_error'),
            'notice' => Session::pullFlash('collection_notice'), 'old' => Session::pullFlash('collection_input') ?? [],
        ]);
    }

    public function storeRecord(): void { $this->mutate('create'); }
    public function updateRecord(string $id): void { $this->mutate('update', $id); }
    public function deleteRecord(string $id): void { $this->mutate('delete', $id); }
    public function submitSchedule(string $id): void { $this->mutate('submit', $id); }

    private function mutate(string $action, ?string $id = null): void
    {
        Auth::requireRole('COLLECTOR');
        if (!$this->hasValidCsrfToken()) {
            http_response_code(403);
            $this->view('collector/error', ['currentPage' => 'schedules', 'message' => 'Your session expired. Open your schedule and try again.']);
            return;
        }
        $destination = '/collector/schedules';
        try {
            $model = new CollectionRecord();
            $collectorId = (int) Auth::id();
            $number = $id === null ? null : $this->id($id);
            if ($action === 'submit') {
                $destination = '/collector/schedules/' . $number;
                $model->submitSchedule($number, $collectorId);
                Session::flash('collection_notice', 'Schedule submitted for Municipal Officer verification. All collection records are now read-only.');
            } elseif ($action === 'delete') {
                $record = $model->ownedRecord($number, $collectorId);
                if (!$record) throw new DomainException('Collection record not found.');
                $destination = '/collector/schedules/' . $record['schedule_id'];
                $model->deleteDraft($number, $collectorId);
                Session::flash('collection_notice', 'Draft collection record permanently deleted.');
            } else {
                if ($action === 'create') {
                    $requestId = $this->id($this->postString('request_id'));
                    if (!$model->assignedRequest($requestId, $collectorId)) throw new DomainException('Assigned request not found.');
                    $destination = '/collector/requests/' . $requestId;
                } else {
                    $record = $model->ownedRecord($number, $collectorId);
                    if (!$record) throw new DomainException('Collection record not found.');
                    $requestId = (int) $record['request_id'];
                    $destination = '/collector/collection-records/' . $number;
                }
                $input = ['pickup_result' => $_POST['pickup_result'] ?? '', 'collector_note' => $_POST['collector_note'] ?? '', 'items' => $_POST['items'] ?? []];
                $savedId = $model->saveDraft($requestId, $collectorId, $input, $number);
                $destination = '/collector/collection-records/' . $savedId;
                Session::flash('collection_notice', $action === 'create' ? 'Collection draft saved.' : 'Collection draft updated.');
            }
            Session::pullFlash('collection_input');
        } catch (DomainException $error) {
            Session::flash('collection_error', $error->getMessage());
            // The view ignores non-scalar form values during redisplay.
            if (isset($input)) Session::flash('collection_input', $input);
        } catch (Throwable $error) {
            error_log('Collection operation failed: ' . $error->getMessage());
            Session::flash('collection_error', 'Unable to save the collection. Reload and try again. If this continues, contact your administrator.');
        }
        $this->redirect($destination);
    }

    public function initialRequest(): void
    {
        Auth::requireRole('COLLECTOR');
        $this->redirect('/collector/schedules');
    }

    public function eLots(): void
    {
        Auth::requireRole('COLLECTOR');
        $this->view('collector/elots', ['currentPage' => 'e-lots']);
    }
}
