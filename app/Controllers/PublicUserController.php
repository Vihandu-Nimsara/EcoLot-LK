<?php
declare(strict_types=1);

class PublicUserController extends Controller
{
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
            'catalogue' => $this->catalogue(),
            'draft' => Session::pullFlash('request_draft') ?? [],
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
                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
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
        $this->mutateRequest();
    }

    public function updateRequest(string $id): void
    {
        Auth::requireRole('PUBLIC_USER');
        $this->mutateRequest($id);
    }

    private function mutateRequest(?string $id = null): void
    {
        $destination = $id === null ? '/user/new-request' : '/user/my-requests';
        $rawItems = $_POST['items'] ?? [];
        $draft = ['request_id' => $id, 'schedule_id' => $this->postString('schedule_id'),
            'items' => is_array($rawItems) ? array_slice($rawItems, 0, 100) : []];
        Session::flash('request_draft', $draft);
        if (!$this->hasValidCsrfToken()) {
            Session::flash('request_error', 'Your session expired. Please try again.');
            $this->redirect($destination);
        }
        try {
            $scheduleId = filter_var($draft['schedule_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $requestId = $id === null ? null : filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (!$scheduleId || $requestId === false) {
                throw new DomainException('Choose a valid request and collection date.');
            }
            $model = new EWasteRequest();
            if ($id === null) {
                $requestId = $model->createWithItems(['public_user_id' => (int) Auth::id(), 'schedule_id' => $scheduleId], is_array($rawItems) ? $rawItems : []);
            } else {
                $model->updateScheduleAndItems($requestId, $scheduleId, is_array($rawItems) ? $rawItems : [], (int) Auth::id());
            }
        } catch (DomainException $error) {
            Session::flash('request_error', $error->getMessage());
            $this->redirect($destination);
        } catch (Throwable $error) {
            error_log('Pickup request failed: ' . $error->getMessage());
            Session::flash('request_error', 'Could not save your request. Please try again.');
            $this->redirect($destination);
        }
        Session::pullFlash('request_draft');
        Csrf::regenerate();
        Session::flash('request_success', 'Request REQ-' . $requestId . ($id === null ? ' submitted.' : ' updated.'));
        $this->redirect('/user/my-requests');
    }

    public function deleteRequest(string $id): void
    {
        Auth::requireRole('PUBLIC_USER');
        if (!$this->hasValidCsrfToken()) {
            Session::flash('request_error', 'Your session expired. Please try again.');
            $this->redirect('/user/my-requests');
        }
        try {
            $requestId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (!$requestId) {
                throw new DomainException('Request not found.');
            }
            (new EWasteRequest())->cancelOwned($requestId, (int) Auth::id());
            Csrf::regenerate();
            Session::flash('request_success', 'The pickup request has been cancelled. Its history is preserved.');
        } catch (DomainException $error) {
            Session::flash('request_error', $error->getMessage());
        } catch (Throwable $error) {
            error_log('Pickup cancellation failed: ' . $error->getMessage());
            Session::flash('request_error', 'Could not cancel your request. Please try again.');
        }
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
        return PickupRequestPresenter::present($request);
    }

    private function renderNewRequestForm(): void
    {
        $profile = (new PublicProfile())->find(Auth::id());
        $schedules = $profile !== null
            ? (new AreaCollectionSchedule())->openForPostalArea((int) $profile['postal_area_id'])
            : [];

        $this->view('public_user/pickup-request-form', [
            'currentPage' => 'new-request',
            'catalogue' => $this->catalogue(),
            'draft' => Session::pullFlash('request_draft') ?? [],
            'csrfToken' => Csrf::token(),
            'errorMessage' => Session::pullFlash('request_error'),
            'successMessage' => Session::pullFlash('request_success'),
            'address' => $profile['address'] ?? '',
            'postalArea' => $profile ? (new PostalCodeArea())->find((int) $profile['postal_area_id']) : null,
            'schedules' => $schedules,
        ]);
    }

    private function catalogue(): array
    {
        $groups = [];
        foreach ((new EWasteItem())->pickupCatalogue() as $item) {
            $groups[$item['category_name']][] = $item['item_name'];
        }
        return $groups;
    }
}
