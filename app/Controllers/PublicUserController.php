<?php
declare(strict_types=1);

class PublicUserController extends Controller
{
    private const ITEM_CONDITIONS = ['WORKING', 'DAMAGED', 'UNKNOWN'];

       public function dashboard(): void
    {
        Auth::requireRole('PUBLIC_USER');

        $requestModel = new EWasteRequest();
        $publicUserId = (int) Auth::id();

        $this->view('public_user/pickup-dashboard', [
            'currentPage' => 'dashboard',
            'summary' => $requestModel->summaryForPublicUser($publicUserId),
            'recentRequests' => array_map(
                [self::class, 'presentRequest'],
                $requestModel->forPublicUser($publicUserId, 2)
            ),
        ]);
    }

    public function myRequests(): void
{
    Auth::requireRole('PUBLIC_USER');

    $publicUserId = (int) Auth::id();
    $profile = (new PublicProfile())->find($publicUserId);
    $requests = (new EWasteRequest())->forPublicUser($publicUserId);

    $scheduleOptions = $profile !== null
        ? (new AreaCollectionSchedule())->openForPostalArea((int) $profile['postal_area_id'])
        : [];

    $this->view('public_user/pickup-request-history', [
        'currentPage' => 'my-requests',
        'requests' => array_map([self::class, 'presentRequest'], $requests),
        'csrfToken' => Csrf::token(),
        'errorMessage' => Session::pullFlash('request_error'),
        'successMessage' => Session::pullFlash('request_success'),
        'scheduleOptionsJson' => json_encode(
            array_map(
                static fn (array $schedule): array => [
                    'schedule_id' => (int) $schedule['schedule_id'],
                    'label' => date('l, d M Y', strtotime((string) $schedule['collection_date'])),
                ],
                $scheduleOptions
            ),
            JSON_UNESCAPED_SLASHES
        ),
    ]);
}

    public function newRequest(): void
    {
        Auth::requireRole('PUBLIC_USER');
        $this->renderNewRequestForm();
    }

    public function storeRequest(): void
    {
        Auth::requireRole('PUBLIC_USER');

        $profile = (new PublicProfile())->find(Auth::id());

        if (!$this->hasValidCsrfToken()) {
            Session::flash('request_error', 'Your session expired. Please try again.');
            $this->redirect('/user/new-request');
        }

        if ($profile === null) {
            Session::flash('request_error', 'We could not find your address profile. Please contact support.');
            $this->redirect('/user/new-request');
        }

        $postalAreaId = (int) $profile['postal_area_id'];
        $scheduleId = filter_var($this->postString('schedule_id'), FILTER_VALIDATE_INT);
        $rawItems = $_POST['items'] ?? [];

        $errors = [];

        if ($scheduleId === false || $scheduleId === null) {
            $errors[] = 'Choose an available collection date.';
        } elseif (!(new AreaCollectionSchedule())->isBookable($scheduleId, $postalAreaId)) {
            $errors[] = 'That collection date is no longer available. Please pick another one.';
        }

        if (!is_array($rawItems) || $rawItems === []) {
            $errors[] = 'Add at least one e-waste item before submitting.';
        }

        [$items, $itemErrors] = $this->validateItems(is_array($rawItems) ? $rawItems : []);
        $errors = array_merge($errors, $itemErrors);

        if ($errors !== []) {
            Session::flash('request_error', implode(' ', $errors));
            $this->redirect('/user/new-request');
        }

        $ewasteRequest = new EWasteRequest();

        try {
            $ewasteRequest->createWithItems(
                [
                    'public_user_id' => (int) Auth::id(),
                    'schedule_id' => $scheduleId,
                    'pickup_address' => (string) $profile['address'],
                ],
                $items
            );
        } catch (Throwable) {
            Session::flash('request_error', 'Something went wrong while submitting your request. Please try again.');
            $this->redirect('/user/new-request');
        }

        Csrf::regenerate();
        Session::flash('request_success', 'Your pickup request has been submitted.');
        $this->redirect('/user/my-requests');
    }

    public function updateRequest(string $id): void
{
    Auth::requireRole('PUBLIC_USER');

    $requestId = (int) $id;
    $publicUserId = (int) Auth::id();
    $requestModel = new EWasteRequest();

    if (!$this->hasValidCsrfToken()) {
        Session::flash('request_error', 'Your session expired. Please try again.');
        $this->redirect('/user/my-requests');
    }

    $existing = $requestModel->findEditableForOwner($requestId, $publicUserId);

    if ($existing === null) {
        Session::flash('request_error', 'That request can no longer be edited.');
        $this->redirect('/user/my-requests');
    }

    $postalAreaId = (int) $existing['postal_area_id'];
    $scheduleId = filter_var($this->postString('schedule_id'), FILTER_VALIDATE_INT);
    $rawItems = $_POST['items'] ?? [];

    $errors = [];

    if ($scheduleId === false || $scheduleId === null) {
        $errors[] = 'Choose an available collection date.';
    } elseif (!(new AreaCollectionSchedule())->isBookable($scheduleId, $postalAreaId, $requestId)) {
        $errors[] = 'That collection date is no longer available. Please pick another one.';
    }

    if (!is_array($rawItems) || $rawItems === []) {
        $errors[] = 'Add at least one e-waste item before submitting.';
    }

    [$items, $itemErrors] = $this->validateItems(is_array($rawItems) ? $rawItems : []);
    $errors = array_merge($errors, $itemErrors);

    if ($errors !== []) {
        Session::flash('request_error', implode(' ', $errors));
        $this->redirect('/user/my-requests');
    }

    try {
        $requestModel->updateScheduleAndItems($requestId, $scheduleId, $items);
    } catch (Throwable) {
        Session::flash('request_error', 'Something went wrong while updating your request. Please try again.');
        $this->redirect('/user/my-requests');
    }

    Csrf::regenerate();
    Session::flash('request_success', 'Your pickup request has been updated.');
    $this->redirect('/user/my-requests');
}

public function deleteRequest(string $id): void
{
    Auth::requireRole('PUBLIC_USER');

    if (!$this->hasValidCsrfToken()) {
        Session::flash('request_error', 'Your session expired. Please try again.');
        $this->redirect('/user/my-requests');
    }

    $deleted = (new EWasteRequest())->deleteOwned((int) $id, (int) Auth::id());

    Csrf::regenerate();
    Session::flash(
        $deleted ? 'request_success' : 'request_error',
        $deleted
            ? 'The pickup request has been deleted.'
            : 'That request can no longer be deleted.'
    );
    $this->redirect('/user/my-requests');
}

    public function feedback(): void
    {
        Auth::requireRole('PUBLIC_USER');
        $this->view('public_user/feedback-form', ['currentPage' => 'feedback']);
    }

    public function profile(): void
    {
        Auth::requireRole('PUBLIC_USER');
        $this->view('public_user/account-profile', ['currentPage' => 'profile']);
    }

        /**
     * Turns one raw request row (as returned by EWasteRequest::forPublicUser)
     * into the display-ready fields the dashboard and history views need.
     */
    public static function presentRequest(array $request): array
    {
        $statusMap = [
            'SUBMITTED' => ['Pending', 'badge-pending', 'status-badge pending'],
            'PENDING_REVIEW' => ['Pending', 'badge-pending', 'status-badge pending'],
            'APPROVED' => ['Pending', 'badge-pending', 'status-badge pending'],
            'COMPLETED' => ['Completed', 'badge-completed', 'status-badge completed'],
            'REJECTED' => ['Cancelled', 'badge-cancelled', 'status-badge cancelled'],
            'CANCELLED' => ['Cancelled', 'badge-cancelled', 'status-badge cancelled'],
        ];
        [$statusLabel, $historyBadgeClass, $dashboardBadgeClass] =
            $statusMap[$request['request_status']] ?? ['Pending', 'badge-pending', 'status-badge pending'];

        $isEditable = in_array($request['request_status'], ['SUBMITTED', 'PENDING_REVIEW'], true);

        $items = $request['items'] ?? [];
        $categories = [];
        $conditions = [];
        $totalQuantity = 0;
        $totalWeight = 0.0;
        $viewItems = [];

        foreach ($items as $item) {
            $categories[$item['category_name']] = true;
            $conditions[$item['item_condition']] = true;
            $totalQuantity += (int) $item['quantity'];
            $totalWeight += (float) $item['estimated_weight_kg'];

            $viewItems[] = [
                'category' => $item['category_name'],
                'item' => $item['item_name'],
                'quantity' => (int) $item['quantity'],
                'weight' => (float) $item['estimated_weight_kg'],
                'condition' => strtoupper($item['item_condition']),                'note' => $item['condition_note'] ?? '',
            ];
        }

        $conditionSummary = count($conditions) === 1
            ? ucfirst(strtolower(array_key_first($conditions)))
            : 'Mixed';

        return [
            'request_id' => (int) $request['request_id'],
            'schedule_id' => (int) $request['schedule_id'],
            'code' => 'REQ-' . $request['request_id'],
            'submitted_date' => date('M j, Y', strtotime((string) $request['submitted_at'])),
            'collection_date' => date('M j, Y', strtotime((string) $request['collection_date'])),
            'collection_date_iso' => date('Y-m-d', strtotime((string) $request['collection_date'])),
            'postal_label' => sprintf('%s (%s)', $request['area_name'], $request['postal_code']),
            'address' => $request['pickup_address'],
            'status_label' => $statusLabel,
            'is_editable' => $isEditable,
            'history_badge_class' => $historyBadgeClass,
            'dashboard_badge_class' => $dashboardBadgeClass,
            'category_summary' => $categories === [] ? '—' : implode(' / ', array_keys($categories)),
            'total_quantity' => $totalQuantity,
            'total_weight' => $totalWeight,
            'total_weight_label' => number_format($totalWeight, 1) . ' kg',
            'condition_summary' => $conditionSummary,
            'items' => $viewItems,
            'items_json' => json_encode($viewItems, JSON_UNESCAPED_SLASHES),
        ];
    }

    private function renderNewRequestForm(): void
    {
        $profile = (new PublicProfile())->find(Auth::id());
        $schedules = $profile !== null
            ? (new AreaCollectionSchedule())->openForPostalArea((int) $profile['postal_area_id'])
            : [];

        $this->view('public_user/pickup-request-form', [
            'currentPage' => 'new-request',
            'csrfToken' => Csrf::token(),
            'errorMessage' => Session::pullFlash('request_error'),
            'successMessage' => Session::pullFlash('request_success'),
            'address' => $profile['address'] ?? '',
            'schedules' => $schedules,
        ]);
    }

    /**
     * @return array{0: array<int, array>, 1: array<int, string>}
     */
    private function validateItems(array $rawItems): array
    {
        $items = [];
        $errors = [];
        $categoryModel = new WasteCategory();
        $itemModel = new EWasteItem();

        foreach ($rawItems as $rawItem) {
            if (!is_array($rawItem)) {
                continue;
            }

            $categoryName = trim((string) ($rawItem['category'] ?? ''));
            $itemName = trim((string) ($rawItem['item'] ?? ''));
            $quantity = filter_var($rawItem['quantity'] ?? null, FILTER_VALIDATE_INT);
            $weight = filter_var($rawItem['weight'] ?? null, FILTER_VALIDATE_FLOAT);
            $condition = strtoupper(trim((string) ($rawItem['condition'] ?? '')));
            $note = trim((string) ($rawItem['note'] ?? ''));

            if ($categoryName === '' || $itemName === '') {
                $errors[] = 'One of the items you added is missing a category or item name.';
                continue;
            }

            $category = $categoryModel->findActiveByName($categoryName);

            if ($category === null) {
                $errors[] = sprintf('"%s" is not a recognised category.', $categoryName);
                continue;
            }

            $wasteItem = $itemModel->findOrCreateByName((int) $category['category_id'], $itemName);

            if ($quantity === false || $quantity === null || $quantity < 1) {
                $errors[] = sprintf('Enter a valid quantity for "%s".', $itemName);
                continue;
            }

            if ($weight === false || $weight === null || $weight <= 0) {
                $errors[] = sprintf('Enter a valid estimated weight for "%s".', $itemName);
                continue;
            }

            if (!in_array($condition, self::ITEM_CONDITIONS, true)) {
                $errors[] = sprintf('Choose a valid condition for "%s".', $itemName);
                continue;
            }

            $items[] = [
                'waste_item_id' => (int) $wasteItem['waste_item_id'],
                'quantity' => $quantity,
                'estimated_weight_kg' => $weight,
                'item_condition' => $condition,
                'condition_note' => $note === '' ? null : $note,
                'applied_risk_level' => $wasteItem['default_risk_level'],
            ];
        }

        return [$items, $errors];
    }
}