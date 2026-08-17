<?php
declare(strict_types=1);

class AuthController extends Controller
{
    public function login(): void
    {
        $this->view('auth/login', [
            'csrfToken' => Csrf::token(),
            'error' => null,
            'oldMobile' => '',
        ]);
    }

    public function submitLogin(): void
    {
        $submittedToken = $_POST['_csrf_token'] ?? null;

        if (!is_string($submittedToken) || !Csrf::validate($submittedToken)) {
            http_response_code(403);

            $this->view('auth/login', [
                'csrfToken' => Csrf::regenerate(),
                'error' => 'Your session expired. Please try again.',
                'oldMobile' => '',
            ]);

            return;
        }

        $rawMobile = $_POST['mobile_number'] ?? '';
        $password = $_POST['password'] ?? '';

        if (!is_string($rawMobile)) {
            $rawMobile = '';
        }

        if (!is_string($password)) {
            $password = '';
        }

        $mobileNumber = MobileNumber::normalize($rawMobile);

        if ($mobileNumber === null || $password === '') {
            http_response_code(422);

            $this->view('auth/login', [
                'csrfToken' => Csrf::token(),
                'error' => 'Enter a valid mobile number and password.',
                'oldMobile' => $rawMobile,
            ]);

            return;
        }

        // Temporary response for this increment.
                $userModel = new User();
        $user = $userModel->findByMobileNumber($mobileNumber);

        // Valid fallback hash prevents a noticeably faster unknown-user check.
        $dummyPasswordHash =
            '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';

        $storedPasswordHash = $user !== null
            ? (string) $user['password_hash']
            : $dummyPasswordHash;

        $passwordIsValid = password_verify($password, $storedPasswordHash);

        if ($user === null || !$passwordIsValid) {
            http_response_code(401);

            $this->view('auth/login', [
                'csrfToken' => Csrf::token(),
                'error' => 'The mobile number or password is incorrect.',
                'oldMobile' => $rawMobile,
            ]);

            return;
        }

        // Temporary response for this increment.
        echo 'Credentials verified.';
    }

    public function register(): void
    {
        $this->view('auth/register');
    }

    public function registerPublic(): void
    {
        $this->view('auth/register-public', [
            'csrfToken' => Csrf::token(),
            'errors' => [],
            'old' => [],
        ]);
    }

    public function registerRecycler(): void
    {
        $this->view('auth/register-recycler');
    }
}