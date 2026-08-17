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
        echo 'Login input validation passed.';
    }

    public function register(): void
    {
        $this->view('auth/register');
    }

    public function registerPublic(): void
    {
        $this->view('auth/register-public');
    }

    public function registerRecycler(): void
    {
        $this->view('auth/register-recycler');
    }
}