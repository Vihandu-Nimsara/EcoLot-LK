<?php
declare(strict_types=1);

class CollectorController extends Controller
{
    public function dashboard(): void
    {
        Auth::requireRole('COLLECTOR');

        $this->view('collector/dashboard', [
            'currentPage' => 'dashboard',
            'schedules' => (new CollectionRecord())
                ->assignedSchedules((int) Auth::id())
        ]);
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
            'currentPage' => 'schedules',
            'schedules' => (new CollectionRecord())
                ->assignedSchedules((int) Auth::id()),
            'notice' => Session::pullFlash('collection_notice'),
            'error' => Session::pullFlash('collection_error')
        ]);
    }

    public function showSchedule(string $id): void
    {
        Auth::requireRole('COLLECTOR');

        $model = new CollectionRecord();

        try {
            $schedule = $model->assignedSchedule(
                $this->id($id),
                (int) Auth::id()
            );

            if (!$schedule) {
                throw new DomainException(
                    'Assigned schedule not found.'
                );
            }
        } catch (DomainException $error) {
            http_response_code(404);

            $this->view('collector/error', [
                'currentPage' => 'schedules',
                'message' => 'Assigned schedule not found.'
            ]);

            return;
        }

        $requests = $model->assignedRequests(
            (int) Auth::id(),
            (int) $schedule['schedule_id']
        );

        $this->view('collector/assigned-requests', [
            'currentPage' => 'schedules',
            'schedule' => $schedule,
            'requests' => $requests,
            'canSubmit' => CollectionRecord::canSubmit(
                $schedule,
                $requests
            ),
            'csrfToken' => Csrf::token(),
            'notice' => Session::pullFlash('collection_notice'),
            'error' => Session::pullFlash('collection_error')
        ]);
    }

    private function id(string $value): int
    {
        $id = ctype_digit($value)
            ? filter_var(
                $value,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            )
            : false;

        if ($id === false) {
            throw new DomainException(
                'Invalid record, request or schedule ID.'
            );
        }

        return $id;
    }

    public function legacyRequest(string $id): void
    {
        Auth::requireRole('COLLECTOR');

        $this->redirect(
            '/collector/requests/' . rawurlencode($id)
        );
    }

    public function showRequest(string $id): void
    {
        $this->details($id, false);
    }

    public function showRecord(string $id): void
    {
        $this->details($id, true);
    }

    private function details(string $id, bool $record): void
    {
        Auth::requireRole('COLLECTOR');

        try {
            $model = new CollectionRecord();

            $request = $record
                ? $model->ownedRecord(
                    $this->id($id),
                    (int) Auth::id()
                )
                : $model->assignedRequest(
                    $this->id($id),
                    (int) Auth::id()
                );

            if (!$request) {
                throw new DomainException(
                    'Collection request not found.'
                );
            }

        } catch (DomainException $error) {
            http_response_code(404);

            $this->view('collector/error', [
                'currentPage' => 'schedules',
                'message' => 'Collection request not found.'
            ]);

            return;
        }

        $this->view('collector/collection-record', [
            'currentPage' => 'schedules',
            'request' => $request,
            'csrfToken' => Csrf::token(),
            'editable' => CollectionRecord::editable($request),
            'error' => Session::pullFlash('collection_error'),
            'notice' => Session::pullFlash('collection_notice'),
            'old' => Session::pullFlash('collection_input') ?? []
        ]);
    }

    public function storeRecord(): void
    {
        $this->mutate('create');
    }

    public function updateRecord(string $id): void
    {
        $this->mutate('update', $id);
    }

    public function deleteRecord(string $id): void
    {
        $this->mutate('delete', $id);
    }

    public function submitSchedule(string $id): void
    {
        $this->mutate('submit', $id);
    }

    private function mutate(
        string $action,
        ?string $id = null
    ): void {
        Auth::requireRole('COLLECTOR');

        if (!$this->hasValidCsrfToken()) {
            http_response_code(403);

            $this->view('collector/error', [
                'currentPage' => 'schedules',
                'message' => 'Your session expired. Open your schedule and try again.'
            ]);

            return;
        }

        $destination = '/collector/schedules';

        try {
            $model = new CollectionRecord();
            $collectorId = (int) Auth::id();

            $number = $id === null
                ? null
                : $this->id($id);

            if ($action === 'submit') {
                $destination = '/collector/schedules/' . $number;

                $model->submitSchedule(
                    $number,
                    $collectorId
                );

                Session::flash(
                    'collection_notice',
                    'Schedule submitted for Municipal Officer verification. All collection records are now read-only.'
                );

            } elseif ($action === 'delete') {
                $record = $model->ownedRecord(
                    $number,
                    $collectorId
                );

                if (!$record) {
                    throw new DomainException(
                        'Collection record not found.'
                    );
                }

                $destination = '/collector/schedules/'
                    . $record['schedule_id'];

                $model->deleteDraft(
                    $number,
                    $collectorId
                );

                Session::flash(
                    'collection_notice',
                    'Draft collection record permanently deleted.'
                );

            } else {
                if ($action === 'create') {
                    $requestId = $this->id(
                        $this->postString('request_id')
                    );

                    if (!$model->assignedRequest(
                        $requestId,
                        $collectorId
                    )) {
                        throw new DomainException(
                            'Assigned request not found.'
                        );
                    }

                    $destination = '/collector/requests/'
                        . $requestId;

                } else {
                    $record = $model->ownedRecord(
                        $number,
                        $collectorId
                    );

                    if (!$record) {
                        throw new DomainException(
                            'Collection record not found.'
                        );
                    }

                    $requestId = (int) $record['request_id'];

                    $destination = '/collector/collection-records/'
                        . $number;
                }

                $input = [
                    'pickup_result' => $_POST['pickup_result'] ?? '',
                    'collector_note' => $_POST['collector_note'] ?? '',
                    'items' => $_POST['items'] ?? []
                ];

                $savedId = $model->saveDraft(
                    $requestId,
                    $collectorId,
                    $input,
                    $number
                );

                $destination = '/collector/collection-records/'
                    . $savedId;

                Session::flash(
                    'collection_notice',
                    $action === 'create'
                        ? 'Collection draft saved.'
                        : 'Collection draft updated.'
                );
            }

            Session::pullFlash('collection_input');

        } catch (DomainException $error) {
            Session::flash(
                'collection_error',
                $error->getMessage()
            );

            if (isset($input)) {
                Session::flash(
                    'collection_input',
                    $input
                );
            }

        } catch (Throwable $error) {
            error_log(
                'Collection operation failed: '
                . $error->getMessage()
            );

            Session::flash(
                'collection_error',
                'Unable to save the collection. Reload and try again.'
            );
        }

        $this->redirect($destination);
    }

    public function initialRequest(): void
    {
        Auth::requireRole('COLLECTOR');

        $this->redirect('/collector/schedules');
    }

    // =========================================================
    // E-LOT PAGE
    // =========================================================

    public function eLots(): void
    {
        Auth::requireRole('COLLECTOR');

        $collectorId = (int) Auth::id();
        $model = new ELot();

        $this->view('collector/elots', [
            'currentPage' => 'e-lots',
            'lots' => $model->getCollectorLots($collectorId),
            'pool' => $model->getVerifiedPool($collectorId),
            'categories' => $model->getCategories(),
            'nextLotCode' => $model->getNextLotCode(),
            'csrfToken' => Csrf::token(),
            'notice' => Session::pullFlash('elot_notice'),
            'error' => Session::pullFlash('elot_error')
        ]);
    }

    // =========================================================
    // AVAILABLE VERIFIED ITEMS
    // =========================================================

    public function availableELotItems(): void
    {
        Auth::requireRole('COLLECTOR');

        header('Content-Type: application/json; charset=utf-8');

        try {
            $collectorId = (int) Auth::id();

            $categoryId = filter_var(
                $_GET['category_id'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if ($categoryId === false) {
                throw new DomainException(
                    'Invalid category.'
                );
            }

            $model = new ELot();
            $excludeLotId = null;

            if (!empty($_GET['exclude_lot_id'])) {
                $excludeLotId = $this->id(
                    (string) $_GET['exclude_lot_id']
                );

                $existing = $model->findCollectorLot(
                    $excludeLotId,
                    $collectorId
                );

                if (!$existing) {
                    throw new DomainException(
                        'E-Lot not found.'
                    );
                }

                if (!in_array(
                    $existing['lot_status'],
                    ['DRAFT', 'REJECTED'],
                    true
                )) {
                    throw new DomainException(
                        'This E-Lot cannot be edited.'
                    );
                }

                if (
                    (int) $existing['category_id']
                    !== $categoryId
                ) {
                    throw new DomainException(
                        'E-Lot category cannot be changed.'
                    );
                }
            }

            $items = $model->getAvailableItemsForCategory(
                $collectorId,
                $categoryId,
                $excludeLotId
            );

            echo json_encode([
                'success' => true,
                'items' => $items
            ]);

        } catch (DomainException $error) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => $error->getMessage()
            ]);

        } catch (Throwable $error) {
            error_log(
                'Failed loading E-Lot items: '
                . $error->getMessage()
            );

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'Unable to load verified items.'
            ]);
        }

        exit;
    }

    // =========================================================
    // VIEW E-LOT DETAILS
    // =========================================================

    public function showELot(string $id): void
    {
        Auth::requireRole('COLLECTOR');

        header('Content-Type: application/json; charset=utf-8');

        try {
            $collectorId = (int) Auth::id();
            $lotId = $this->id($id);

            $model = new ELot();

            $lot = $model->findCollectorLot(
                $lotId,
                $collectorId
            );

            if (!$lot) {
                http_response_code(404);

                echo json_encode([
                    'success' => false,
                    'error' => 'E-Lot not found.'
                ]);

                exit;
            }

            echo json_encode([
                'success' => true,
                'lot' => $lot
            ]);

        } catch (DomainException $error) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'error' => $error->getMessage()
            ]);

        } catch (Throwable $error) {
            error_log(
                'Failed loading E-Lot: '
                . $error->getMessage()
            );

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'error' => 'Unable to load E-Lot.'
            ]);
        }

        exit;
    }

    // =========================================================
    // E-LOT JSON RESPONSE
    // =========================================================

    private function elotJson(
        bool $success,
        string $message,
        int $status = 200
    ): void {
        http_response_code($status);

        header(
            'Content-Type: application/json; charset=utf-8'
        );

        header('Cache-Control: no-store');

        echo json_encode([
            'success' => $success,
            'message' => $message
        ]);
    }

    // =========================================================
    // VALIDATE SELECTED E-LOT ITEMS
    // =========================================================

    private function elotPostedItems(): array
    {
        $raw = $_POST['item_ids'] ?? [];

        if (!is_array($raw) || count($raw) === 0) {
            throw new DomainException(
                'Select at least one verified item.'
            );
        }

        $ids = [];

        foreach ($raw as $value) {
            if (!is_scalar($value)) {
                throw new DomainException(
                    'Invalid verified item ID.'
                );
            }

            $id = filter_var(
                (string) $value,
                FILTER_VALIDATE_INT,
                [
                    'options' => [
                        'min_range' => 1
                    ]
                ]
            );

            if ($id === false) {
                throw new DomainException(
                    'Invalid verified item ID.'
                );
            }

            $ids[] = (int) $id;
        }

        if (count($ids) !== count(array_unique($ids))) {
            throw new DomainException(
                'Duplicate verified items selected.'
            );
        }

        return $ids;
    }

    // =========================================================
    // CREATE / UPDATE / DELETE / SUBMIT E-LOT
    // =========================================================

    private function mutateELot(
        string $action,
        ?string $id = null
    ): void {
        Auth::requireRole('COLLECTOR');

        if (!$this->hasValidCsrfToken()) {
            $this->elotJson(
                false,
                'Session expired. Refresh the page and try again.',
                403
            );

            return;
        }

        try {
            $collectorId = (int) Auth::id();

            $model = new ELot();

            if ($action === 'create') {
                $categoryId = filter_var(
                    $_POST['category_id'] ?? null,
                    FILTER_VALIDATE_INT,
                    [
                        'options' => [
                            'min_range' => 1
                        ]
                    ]
                );

                if ($categoryId === false) {
                    throw new DomainException(
                        'Choose a valid E-Waste category.'
                    );
                }

                $lotId = $model->createCollectorLot(
                    $collectorId,
                    $this->postString('title'),
                    (int) $categoryId,
                    $this->elotPostedItems()
                );

                $message =
                    'E-Lot draft saved successfully (ID '
                    . $lotId
                    . ').';

            } else {
                $lotId = $this->id((string) $id);

                switch ($action) {
                    case 'update':
                        $model->updateCollectorLot(
                            $lotId,
                            $collectorId,
                            $this->postString('title'),
                            $this->elotPostedItems()
                        );

                        $message =
                            'E-Lot draft updated successfully.';

                        break;

                    case 'delete':
                        $model->deleteCollectorLot(
                            $lotId,
                            $collectorId
                        );

                        $message =
                            'E-Lot deleted. Items are available again.';

                        break;

                    case 'submit':
                        $model->submitCollectorLot(
                            $lotId,
                            $collectorId
                        );

                        $message =
                            'E-Lot submitted for Municipal Officer verification.';

                        break;

                    default:
                        throw new DomainException(
                            'Unknown E-Lot action.'
                        );
                }
            }

            $this->elotJson(
                true,
                $message
            );

        } catch (DomainException $error) {
            $this->elotJson(
                false,
                $error->getMessage(),
                422
            );

        } catch (Throwable $error) {
            error_log(
                'E-Lot '
                . $action
                . ' failed: '
                . $error->getMessage()
                . ' in '
                . $error->getFile()
                . ':'
                . $error->getLine()
            );

            $this->elotJson(
                false,
                'Database operation failed. Check your PHP error log.',
                500
            );
        }
    }

    // =========================================================
    // CREATE E-LOT
    // =========================================================

    public function storeELot(): void
    {
        $this->mutateELot('create');
    }

    // =========================================================
    // UPDATE E-LOT
    // =========================================================

    public function updateELot(string $id): void
    {
        $this->mutateELot('update', $id);
    }

    // =========================================================
    // DELETE E-LOT
    // =========================================================

    public function deleteELot(string $id): void
    {
        $this->mutateELot('delete', $id);
    }

    // =========================================================
    // SUBMIT E-LOT
    // =========================================================

    public function submitELot(string $id): void
    {
        $this->mutateELot('submit', $id);
    }
}