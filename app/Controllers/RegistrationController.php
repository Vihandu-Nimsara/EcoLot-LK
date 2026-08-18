<?php
declare(strict_types=1);

class RegistrationController extends Controller
{
    public function index(): void
    {
        $this->view('auth/register');
    }

    public function publicForm(): void
    {
        $this->renderPublicForm();
    }

    public function recyclerForm(): void
    {
        $this->view('auth/register-recycler');
    }

    public function submitPublic(): void
    {
        if (!$this->hasValidCsrfToken()) {
            http_response_code(403);
            $this->renderPublicForm(
                ['general' => ['Your session expired. Please try again.']],
                [],
                Csrf::regenerate()
            );
            return;
        }

        $input = $this->publicRegistrationInput();
        $registrationService = new PublicRegistrationService();
        $validation = $registrationService->validate($input);

        if ($validation['errors'] !== []) {
            http_response_code(422);
            $this->renderPublicForm(
                $validation['errors'],
                $this->oldInput($input)
            );
            return;
        }

        try {
            $userId = $registrationService->createPendingUser(
                $input,
                (string) $validation['mobile_number'],
                (int) $validation['postal_area_id']
            );
        } catch (Throwable $exception) {
            $this->handleRegistrationFailure($exception, $input);
            return;
        }

        MobileVerificationSession::start($userId);
        Csrf::regenerate();

        try {
            (new MobileVerificationService())->sendRegistrationOtp(
                $userId,
                (string) $validation['mobile_number']
            );
            Session::flash(
                'verification_notice',
                'We sent a six-digit verification code to your mobile number.'
            );
        } catch (Throwable $exception) {
            $reference = bin2hex(random_bytes(6));
            error_log(
                'Registration OTP delivery failure ['
                . $reference
                . '] category='
                . $exception::class
            );
            Session::flash(
                'verification_error',
                'Your account was created, but the verification SMS could not be sent. '
                . 'Please try Resend Code. Reference: '
                . $reference
            );
        }

        $this->redirect('/verify-mobile');
    }

    private function publicRegistrationInput(): array
    {
        return [
            'first_name' => $this->postString('first_name'),
            'last_name' => $this->postString('last_name'),
            'contact_number' => $this->postString('contact_number'),
            'email' => strtolower($this->postString('email')),
            'password' => $this->postString('password', false),
            'password_confirmation' => $this->postString(
                'password_confirmation',
                false
            ),
            'postal_code' => $this->postString('postal_code'),
            'address' => $this->postString('address'),
        ];
    }

    private function oldInput(array $input): array
    {
        return array_intersect_key($input, array_flip([
            'first_name',
            'last_name',
            'contact_number',
            'email',
            'postal_code',
            'address',
        ]));
    }

    private function handleRegistrationFailure(
        Throwable $exception,
        array $input
    ): void {
        $reference = bin2hex(random_bytes(8));
        error_log(
            'Public registration database failure ['
            . $reference
            . '] category='
            . $exception::class
        );

        $isDuplicate = $exception instanceof PDOException
            && $exception->getCode() === '23000';
        http_response_code($isDuplicate ? 409 : 500);
        $message = $isDuplicate
            ? 'An account already exists with this mobile number or email address.'
            : 'We could not create your account. Please try again.';

        $this->renderPublicForm(
            ['general' => [$message . ' Reference: ' . $reference]],
            $this->oldInput($input),
            Csrf::regenerate()
        );
    }

    private function renderPublicForm(
        array $errors = [],
        array $old = [],
        ?string $csrfToken = null
    ): void {
        $this->view('auth/register-public', [
            'csrfToken' => $csrfToken ?? Csrf::token(),
            'errors' => $errors,
            'old' => $old,
        ]);
    }
}
