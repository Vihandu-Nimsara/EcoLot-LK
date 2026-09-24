<?php
declare(strict_types=1);

class PublicUserController extends Controller
{
    public function dashboard(): void
    {
        Auth::requireRole('PUBLIC_USER');
        $this->view('public_user/pickup-dashboard', ['currentPage' => 'dashboard']);
    }

    public function myRequests(): void
    {
        Auth::requireRole('PUBLIC_USER');
        $this->view('public_user/pickup-request-history', ['currentPage' => 'my-requests']);
    }

    public function newRequest(): void
    {
        Auth::requireRole('PUBLIC_USER');
        $profile = (new PublicProfile())->find((int) Auth::id());
        $area = $profile === null ? null : (new PostalCodeArea())->find((int) $profile['postal_area_id']);
        $this->view('public_user/pickup-request-form', [
            'currentPage' => 'new-request',
            'availableSchedules' => (new AreaCollectionSchedule())->availableForPublicUser((int) Auth::id()),
            'postalAreaLabel' => $area === null ? 'No registered postal area' : $area['area_name'] . ' (' . $area['postal_code'] . ')',
            'user_address' => $profile['address'] ?? '',
        ]);
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
}
