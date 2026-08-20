<?php
declare(strict_types=1);

class AuthController extends Controller
{
    private const DUMMY_PASSWORD_HASH =
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';

    private const DASHBOARD_PATHS = [
        'ADMIN' => '/admin/dashboard',
        'MUNICIPAL_OFFICER' => '/officer/dashboard',
        'COLLECTOR' => '/collector/dashboard',
        'RECYCLER' => '/recycler/dashboard',
        'PUBLIC_USER' => '/user/dashboard',
    ];

    public function login(): void
    {
        if (Auth::check()) {
            $role = Auth::role();
            $dashboardPath = self::DASHBOARD_PATHS[$role] ?? null;

            if ($dashboardPath !== null) {
                $this->redirect($dashboardPath);
            }
        }

        $this->renderLogin();
    }

    public function submitLogin(): void
    {
        if (!$this->hasValidCsrfToken()) {
            http_response_code(403);
            $this->renderLogin(
                'Your session expired. Please try again.',
                '',
                Csrf::regenerate()
            );
            return;
        }

        $rawMobile = $this->postString('mobile_number');
        $password = $this->postString('password', false);
        $mobileNumber = MobileNumber::normalize($rawMobile);

        if ($mobileNumber === null || $password === '') {
            http_response_code(422);
            $this->renderLogin(
                'Enter a valid mobile number and password.',
                $rawMobile
            );
            return;
        }

        $user = (new User())->findByMobileNumber($mobileNumber);
        $passwordHash = $user === null
            ? self::DUMMY_PASSWORD_HASH
            : (string) $user['password_hash'];

        if ($user === null || !password_verify($password, $passwordHash)) {
            http_response_code(401);
            $this->renderLogin(
                'The mobile number or password is incorrect.',
                $rawMobile
            );
            return;
        }

        if ($this->requiresMobileVerification($user)) {
            MobileVerificationSession::start((int) $user['user_id']);
            Csrf::regenerate();
            Session::flash(
                'verification_notice',
                'Verify your mobile number to finish creating your account.'
            );
            $this->redirect('/verify-mobile');
        }

        $role = (string) $user['role'];

        if ($role === 'RECYCLER') {
            $recycler = (new AuthorizedRecycler())->findByUserId((int) $user['user_id']);
            $verificationStatus = $recycler['verification_status'] ?? 'PENDING';

            if ($verificationStatus === 'PENDING') {
                http_response_code(403);
                $this->renderLogin(
                    'Your Recycler account is awaiting Admin verification approval.',
                    $rawMobile
                );
                return;
            }

            if ($verificationStatus === 'REJECTED') {
                http_response_code(403);
                $this->renderLogin(
                    'Your Recycler application was rejected by an Administrator.',
                    $rawMobile
                );
                return;
            }
        }

        if (($user['account_status'] ?? null) !== 'ACTIVE') {
            http_response_code(403);
            $this->renderLogin(
                'This account is not currently available.',
                $rawMobile
            );
            return;
        }

        $dashboardPath = self::DASHBOARD_PATHS[$role] ?? null;

        if ($dashboardPath === null) {
            http_response_code(403);
            $this->renderLogin('This account role is not supported.', $rawMobile);
            return;
        }

        Auth::login([
            'id' => (int) $user['user_id'],
            'name' => (string) $user['full_name'],
            'role' => $role,
        ]);
        Csrf::regenerate();
        $this->redirect($dashboardPath);
    }

    private function requiresMobileVerification(array $user): bool
    {
        return in_array($user['role'] ?? null, ['PUBLIC_USER', 'RECYCLER'], true)
            && ($user['mobile_verified_at'] ?? null) === null;
    }

    private function renderLogin(
        ?string $error = null,
        string $oldMobile = '',
        ?string $csrfToken = null
    ): void {
        $this->view('auth/login', [
            'csrfToken' => $csrfToken ?? Csrf::token(),
            'error' => $error,
            'success' => Session::pullFlash('auth_success'),
            'oldMobile' => $oldMobile,
        ]);
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/login');
    }
}

