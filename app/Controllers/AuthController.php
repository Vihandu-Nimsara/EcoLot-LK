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

        $postalArea = null;

        if (!isset($errors['postal_code'])) {
            $postalArea = (new PostalCodeArea())->findActiveByPostalCode(
                $input['postal_code']
            );

            if ($postalArea === null) {
                $errors['postal_code'][] =
                    'The selected postal area is not currently available.';
            }
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

        $connection = Database::connection();

        try {
            $connection->beginTransaction();

            $transactionUserModel = new User($connection);
            $userId = $transactionUserModel->create([
                'full_name' => trim(
                    $input['first_name'] . ' ' . $input['last_name']
                ),
                'mobile_number' => $normalizedMobile,
                'email' => $input['email'] === '' ? null : $input['email'],
                'password_hash' => password_hash(
                    $input['password'],
                    PASSWORD_DEFAULT
                ),
                'role' => 'PUBLIC_USER',
                'account_status' => 'PENDING',
            ]);

            $publicProfileModel = new PublicProfile($connection);
            $publicProfileModel->create([
                'user_id' => $userId,
                'postal_area_id' => (int) $postalArea['postal_area_id'],
                'address' => $input['address'],
            ]);

            $connection->commit();
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            $errorReference = bin2hex(random_bytes(8));
            error_log(
                'Public registration database failure ['
                . $errorReference
                . '] category='
                . $exception::class
            );

            $message = $exception instanceof PDOException
                && $exception->getCode() === '23000'
                ? 'An account already exists with this mobile number or email address.'
                : 'We could not create your account. Please try again.';

            http_response_code(409);

            $this->view('auth/register-public', [
                'csrfToken' => Csrf::regenerate(),
                'errors' => [
                    'general' => [
                        $message . ' Reference: ' . $errorReference,
                    ],
                ],
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

        Session::put('pending_verification_user_id', $userId);
        Session::put('pending_verification_role', 'PUBLIC_USER');
        Session::put('otp_last_sent_at', null);
        Csrf::regenerate();

        $this->dispatchRegistrationOtp($userId, $normalizedMobile);
        $this->redirectTo('/verify-mobile');
    }

    public function verifyMobile(): void
    {
        $user = $this->pendingVerificationUser();

        if ($user === null) {
            $this->redirectTo('/register/public');
        }

        $this->view('auth/verify-mobile', [
            'csrfToken' => Csrf::token(),
            'maskedMobile' => $this->maskMobileNumber(
                (string) $user['mobile_number']
            ),
            'error' => Session::pullFlash('verification_error'),
            'success' => Session::pullFlash('verification_success'),
        ]);
    }

    public function submitMobileVerification(): void
    {
        $user = $this->pendingVerificationUser();

        if ($user === null) {
            $this->redirectTo('/register/public');
        }

        $submittedToken = $_POST['_csrf_token'] ?? null;

        if (!is_string($submittedToken) || !Csrf::validate($submittedToken)) {
            Session::flash(
                'verification_error',
                'Your session expired. Please try again.'
            );
            Csrf::regenerate();
            $this->redirectTo('/verify-mobile');
        }

        $submittedOtp = $_POST['otp'] ?? '';

        if (!is_string($submittedOtp)) {
            $submittedOtp = '';
        }

        $connection = Database::connection();

        try {
            $connection->beginTransaction();

            $otpService = new OtpService($connection);
            $result = $otpService->verifyRegistrationOtp(
                (int) $user['user_id'],
                trim($submittedOtp)
            );

            if ($result['success']) {
                $userModel = new User($connection);

                if (!$userModel->activateVerifiedPublicUser(
                    (int) $user['user_id']
                )) {
                    throw new RuntimeException(
                        'The verified public account could not be activated.'
                    );
                }
            }

            $connection->commit();
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            $reference = bin2hex(random_bytes(8));
            error_log(
                'Mobile verification failure ['
                . $reference
                . '] category='
                . $exception::class
            );

            Session::flash(
                'verification_error',
                'We could not verify your mobile number. Please try again. '
                . 'Reference: '
                . $reference
            );
            $this->redirectTo('/verify-mobile');
        }

        if (!$result['success']) {
            $message = match ($result['status']) {
                'expired' => 'The verification code has expired. Request a new code.',
                'locked' => 'Too many attempts. Request a new verification code.',
                'not_found' => 'No active verification code was found. Request a new code.',
                default => sprintf(
                    'The verification code is incorrect. %d attempts remaining.',
                    (int) $result['attempts_remaining']
                ),
            };

            Session::flash('verification_error', $message);
            $this->redirectTo('/verify-mobile');
        }

        Auth::login([
            'id' => (int) $user['user_id'],
            'user_id' => (int) $user['user_id'],
            'full_name' => (string) $user['full_name'],
            'role' => 'PUBLIC_USER',
            'account_status' => 'ACTIVE',
        ]);

        Session::forget('pending_verification_user_id');
        Session::forget('pending_verification_role');
        Session::forget('otp_last_sent_at');
        Csrf::regenerate();

        $this->redirectTo('/user/dashboard');
    }

    public function resendMobileVerification(): void
    {
        $user = $this->pendingVerificationUser();

        if ($user === null) {
            $this->redirectTo('/register/public');
        }

        $submittedToken = $_POST['_csrf_token'] ?? null;

        if (!is_string($submittedToken) || !Csrf::validate($submittedToken)) {
            Session::flash(
                'verification_error',
                'Your session expired. Please try again.'
            );
            Csrf::regenerate();
            $this->redirectTo('/verify-mobile');
        }

        $this->dispatchRegistrationOtp(
            (int) $user['user_id'],
            (string) $user['mobile_number']
        );

        $this->redirectTo('/verify-mobile');
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

    private function dispatchRegistrationOtp(
        int $userId,
        string $mobileNumber
    ): void {
        try {
            $otpService = new OtpService();
            $challenge = $otpService->issueRegistrationOtp($userId);
            $smsResult = (new NotifySmsService())->sendRegistrationOtp(
                $mobileNumber,
                (string) $challenge['otp']
            );

            if (!$smsResult['success']) {
                $otpService->invalidateChallenge(
                    (int) $challenge['otp_id']
                );

                Session::flash(
                    'verification_error',
                    'We could not send the verification code. Please try again. '
                    . 'Reference: '
                    . (string) $smsResult['reference']
                );

                return;
            }

            Session::put(
                'otp_last_sent_at',
                (new DateTimeImmutable(
                    'now',
                    new DateTimeZone('Asia/Colombo')
                ))->format(DATE_ATOM)
            );
            Session::flash(
                'verification_success',
                'A verification code was sent to your mobile number.'
            );
        } catch (OtpRateLimitException $exception) {
            Session::flash(
                'verification_error',
                $exception->getMessage()
            );
        } catch (Throwable $exception) {
            $reference = bin2hex(random_bytes(8));
            error_log(
                'OTP dispatch failure ['
                . $reference
                . '] category='
                . $exception::class
            );

            Session::flash(
                'verification_error',
                'We could not send the verification code. Please try again. '
                . 'Reference: '
                . $reference
            );
        }
    }

    private function pendingVerificationUser(): ?array
    {
        $userId = Session::get('pending_verification_user_id');
        $role = Session::get('pending_verification_role');

        if (
            !is_int($userId)
            || $userId <= 0
            || $role !== 'PUBLIC_USER'
        ) {
            return null;
        }

        $user = (new User())->find($userId);

        if (
            $user === null
            || ($user['role'] ?? null) !== 'PUBLIC_USER'
            || ($user['account_status'] ?? null) !== 'PENDING'
            || ($user['mobile_verified_at'] ?? null) !== null
        ) {
            return null;
        }

        return $user;
    }

    private function maskMobileNumber(string $mobileNumber): string
    {
        $localNumber = str_starts_with($mobileNumber, '94')
            ? '0' . substr($mobileNumber, 2)
            : $mobileNumber;

        return substr($localNumber, 0, 3)
            . ' *** **'
            . substr($localNumber, -2);
    }

    private function redirectTo(string $path): never
    {
        if (!str_starts_with($path, '/')) {
            throw new InvalidArgumentException(
                'Application redirect paths must begin with a slash.'
            );
        }

        $app = require APP_ROOT . '/config/app.php';
        $basePath = rtrim((string) ($app['base_path'] ?? ''), '/');

        header('Location: ' . $basePath . $path, true, 303);
        exit;
    }
}
