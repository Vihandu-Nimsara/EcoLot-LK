<?php
declare(strict_types=1);

class AuthController extends Controller
{
    private const DUMMY_PASSWORD_HASH =
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';

    public function login(): void
    {
        if (Auth::check()) {
            $role = Auth::role();
            $dashboardPath = Auth::dashboardPath($role);

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

        $retryAfter = (new LoginRateLimiter())->consume(
            (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
            $mobileNumber
        );
        if ($retryAfter > 0) {
            http_response_code(429);
            header('Retry-After: ' . $retryAfter);
            $this->renderLogin('Too many login attempts. Please try again later.', $rawMobile);
            return;
        }

        $user = (new User())->findByMobileNumber($mobileNumber);
        $passwordHash = $user === null
            ? self::DUMMY_PASSWORD_HASH
            : (string) $user['password_hash'];

        $validPassword = password_verify($password, $passwordHash);
        if ($user === null || !$validPassword) {
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

        $dashboardPath = Auth::dashboardPath($role);

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
        if (!$this->hasValidCsrfToken()) {
            http_response_code(403);
            echo '403 Forbidden';
            return;
        }

        Auth::logout();
        $this->redirect('/login');
    }
}

