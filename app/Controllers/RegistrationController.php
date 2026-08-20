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
        $this->renderRecyclerForm();
    }

    public function recyclerPending(): void
    {
        $summary = Session::pullFlash('recycler_verification_summary');

        if (!is_array($summary)) {
            $this->redirect('/register/recycler');
        }

        $this->view('auth/recycler-pending', ['summary' => $summary]);
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

    public function submitRecycler(): void
    {
        if (!$this->hasValidCsrfToken()) {
            http_response_code(403);
            $this->renderRecyclerForm(
                ['general' => ['Your session expired. Please try again.']],
                [],
                Csrf::regenerate()
            );
            return;
        }

        $input = $this->recyclerRegistrationInput();
        $file = isset($_FILES['licence_copy']) && is_array($_FILES['licence_copy'])
            ? $_FILES['licence_copy']
            : [];
        $registrationService = new RecyclerRegistrationService();
        try {
            $validation = $registrationService->validate($input, $file);
        } catch (Throwable $exception) {
            $reference = bin2hex(random_bytes(8));
            error_log(
                'Recycler registration validation failure ['
                . $reference
                . '] category='
                . $exception::class
            );
            http_response_code(500);
            $this->renderRecyclerForm(
                ['general' => [
                    'We could not validate your recycler application. '
                    . 'Please try again. Reference: '
                    . $reference,
                ]],
                $this->recyclerOldInput($input),
                Csrf::regenerate()
            );
            return;
        }

        if ($validation['errors'] !== []) {
            http_response_code(422);
            $this->renderRecyclerForm(
                $validation['errors'],
                $this->recyclerOldInput($input)
            );
            return;
        }

        try {
            $userId = $registrationService->createPendingRecycler(
                $input,
                (string) $validation['mobile_number'],
                $validation['activities'],
                $validation['category_ids'],
                $file
            );
        } catch (Throwable $exception) {
            $this->handleRecyclerRegistrationFailure($exception, $input);
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
                'Recycler registration OTP delivery failure ['
                . $reference
                . '] category='
                . $exception::class
            );
            Session::flash(
                'verification_error',
                'Your application was created, but the verification SMS could not be sent. '
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

    private function recyclerRegistrationInput(): array
    {
        return [
            'company_name' => $this->postString('company_name'),
            'contact_person' => $this->postString('contact_person'),
            'company_email' => strtolower($this->postString('company_email')),
            'phone' => $this->postString('phone'),
            'company_password' => $this->postString('company_password', false),
            'confirm_password' => $this->postString('confirm_password', false),
            'business_address' => $this->postString('business_address'),
            'district' => $this->postString('district'),
            'swml_number' => $this->postString('swml_number'),
            'licence_expiry' => $this->postString('licence_expiry'),
            'activities' => $_POST['activities'] ?? [],
            'requested_capabilities' => $_POST['requested_capabilities'] ?? [],
        ];
    }

    private function recyclerOldInput(array $input): array
    {
        return array_intersect_key($input, array_flip([
            'company_name',
            'contact_person',
            'company_email',
            'phone',
            'business_address',
            'district',
            'swml_number',
            'licence_expiry',
            'activities',
            'requested_capabilities',
        ]));
    }

    private function handleRecyclerRegistrationFailure(
        Throwable $exception,
        array $input
    ): void {
        $reference = bin2hex(random_bytes(8));
        error_log(
            'Recycler registration database failure ['
            . $reference
            . '] category='
            . $exception::class
        );

        $isDuplicate = $exception instanceof PDOException
            && $exception->getCode() === '23000';
        http_response_code($isDuplicate ? 409 : 500);
        $message = $isDuplicate
            ? 'An account or recycler application already exists with these details.'
            : 'We could not create your recycler application. Please try again.';

        $this->renderRecyclerForm(
            ['general' => [$message . ' Reference: ' . $reference]],
            $this->recyclerOldInput($input),
            Csrf::regenerate()
        );
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

    private function renderRecyclerForm(
        array $errors = [],
        array $old = [],
        ?string $csrfToken = null
    ): void {
        try {
            $categories = (new RecyclerRegistrationService())->categories();
        } catch (Throwable $exception) {
            error_log(
                'Recycler registration category lookup failure category='
                . $exception::class
            );
            $categories = [];
            $errors['general'] ??= [
                'Recycler registration is temporarily unavailable. Please try again later.',
            ];
            http_response_code(http_response_code() >= 400 ? http_response_code() : 500);
        }

        $this->view('auth/register-recycler', [
            'csrfToken' => $csrfToken ?? Csrf::token(),
            'errors' => $errors,
            'old' => $old,
            'districts' => RecyclerRegistrationService::DISTRICTS,
            'activities' => RecyclerRegistrationService::ACTIVITIES,
            'categories' => $categories,
        ]);
    }
}
