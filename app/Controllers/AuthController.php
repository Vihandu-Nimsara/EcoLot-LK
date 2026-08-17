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
        
    public function submitPublicRegistration(): void
    {
        $submittedToken = $_POST['_csrf_token'] ?? null;

        if (!is_string($submittedToken) || !Csrf::validate($submittedToken)) {
            http_response_code(403);

            $this->view('auth/register-public', [
                'csrfToken' => Csrf::regenerate(),
                'errors' => [
                    'general' => [
                        'Your session expired. Please try again.',
                    ],
                ],
                'old' => [],
            ]);

            return;
        }

        $input = [
            'first_name' => $this->registrationInput('first_name'),
            'last_name' => $this->registrationInput('last_name'),
            'contact_number' => $this->registrationInput('contact_number'),
            'email' => strtolower($this->registrationInput('email')),
            'password' => $this->registrationInput('password', false),
            'password_confirmation' => $this->registrationInput(
                'password_confirmation',
                false
            ),
            'postal_code' => $this->registrationInput('postal_code'),
            'address' => $this->registrationInput('address'),
        ];

        $validator = new Validator();

        $validator->validate($input, [
            'first_name' => ['required', 'min:2', 'max:60'],
            'last_name' => ['required', 'min:2', 'max:60'],
            'contact_number' => ['required', 'max:20'],
            'email' => ['email', 'max:255'],
            'password' => ['required', 'min:8', 'max:128'],
            'password_confirmation' => ['required', 'max:128'],
            'postal_code' => [
                'required',
                'in:11100,10800,10600,10500,00800,00700',
            ],
            'address' => ['required', 'min:5', 'max:500'],
        ]);

        $errors = $validator->errors();

        $normalizedMobile = MobileNumber::normalize(
            $input['contact_number']
        );

        if ($normalizedMobile === null) {
            $errors['contact_number'][] =
                'Enter a valid Sri Lankan mobile number.';
        }

        if (
            $input['password'] !== ''
            && preg_match('/[A-Z]/', $input['password']) !== 1
        ) {
            $errors['password'][] =
                'Password must contain at least one uppercase letter.';
        }

        if (
            $input['password'] !== ''
            && preg_match('/[a-z]/', $input['password']) !== 1
        ) {
            $errors['password'][] =
                'Password must contain at least one lowercase letter.';
        }

        if (
            $input['password'] !== ''
            && preg_match('/[0-9]/', $input['password']) !== 1
        ) {
            $errors['password'][] =
                'Password must contain at least one number.';
        }

        if (
            $input['password'] !== $input['password_confirmation']
        ) {
            $errors['password_confirmation'][] =
                'Password confirmation does not match.';
        }

        $userModel = new User();

        if (
            $normalizedMobile !== null
            && $userModel->findByMobileNumber($normalizedMobile) !== null
        ) {
            $errors['contact_number'][] =
                'An account already exists with this mobile number.';
        }

        if (
            $input['email'] !== ''
            && $userModel->findByEmail($input['email']) !== null
        ) {
            $errors['email'][] =
                'An account already exists with this email address.';
        }

        if ($errors !== []) {
            http_response_code(422);

            $this->view('auth/register-public', [
                'csrfToken' => Csrf::token(),
                'errors' => $errors,
                'old' => [
                    'first_name' => $input['first_name'],
                    'last_name' => $input['last_name'],
                    'contact_number' => $input['contact_number'],
                    'email' => $input['email'],
                    'postal_code' => $input['postal_code'],
                    'address' => $input['address'],
                ],
            ]);

            return;
        }

        // Temporary response until the transactional account insert is added.
        echo 'Public registration validation and uniqueness checks passed.';
    }

    private function registrationInput(
        string $field,
        bool $trim = true
    ): string {
        $value = $_POST[$field] ?? '';

        if (!is_string($value)) {
            return '';
        }

        return $trim ? trim($value) : $value;
    }
}
