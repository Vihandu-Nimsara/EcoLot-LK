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
        $submittedToken = isset($_POST['_csrf_token'])
            ? (string) $_POST['_csrf_token']
            : null;

        if (!Csrf::validate($submittedToken)) {
            http_response_code(403);

            $this->view('auth/login', [
                'csrfToken' => Csrf::regenerate(),
                'error' => 'Your session expired. Please try again.',
                'oldMobile' => '',
            ]);

            return;
        }

        // Temporary response for this increment only.
        echo 'CSRF validation passed.';
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