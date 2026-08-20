<?php
declare(strict_types=1);

class MobileVerificationController extends Controller
{
    public function show(): void
    {
        $user = $this->pendingUserOrRedirect();
        $this->renderVerification($user, [
            'notice' => Session::pullFlash('verification_notice'),
            'error' => Session::pullFlash('verification_error'),
        ]);
    }

    public function verify(): void
    {
        $user = $this->pendingUserOrRedirect();

        if (!$this->hasValidCsrfToken()) {
            http_response_code(403);
            $this->renderVerification($user, [
                'csrfToken' => Csrf::regenerate(),
                'error' => 'Your session expired. Please try again.',
            ]);
            return;
        }

        try {
            $verificationService = new MobileVerificationService();
            $result = ($user['role'] ?? null) === 'RECYCLER'
                ? $verificationService->verifyAndActivateRecycler(
                    (int) $user['user_id'],
                    $this->postString('otp')
                )
                : $verificationService->verifyAndActivate(
                    (int) $user['user_id'],
                    $this->postString('otp')
                );
        } catch (Throwable $exception) {
            error_log('Mobile verification failure category=' . $exception::class);
            http_response_code(500);
            $this->renderVerification($user, [
                'error' => 'We could not verify your account right now. Please try again.',
            ]);
            return;
        }

        if (!$result['success']) {
            http_response_code(422);
            $this->renderVerification($user, [
                'error' => $this->errorMessage($result),
            ]);
            return;
        }

        $isRecycler = ($user['role'] ?? null) === 'RECYCLER';
        $mobile = (string) $user['mobile_number'];
        $maskedMobile = substr($mobile, 0, 4)
            . str_repeat('*', max(0, strlen($mobile) - 7))
            . substr($mobile, -3);
        MobileVerificationSession::clear();
        Session::regenerate();
        Csrf::regenerate();

        if ($isRecycler) {
            Session::flash('recycler_verification_summary', [
                'masked_mobile' => $maskedMobile,
            ]);
            $this->redirect('/register/recycler/pending');
        }

        Session::flash(
            'auth_success',
            'Your mobile number is verified. You can now log in.'
        );
        $this->redirect('/login');
    }

    public function resend(): void
    {
        $user = $this->pendingUserOrRedirect();

        if (!$this->hasValidCsrfToken()) {
            Session::flash(
                'verification_error',
                'Your session expired. Please try again.'
            );
            Csrf::regenerate();
            $this->redirect('/verify-mobile');
        }

        try {
            (new MobileVerificationService())->sendRegistrationOtp(
                (int) $user['user_id'],
                (string) $user['mobile_number']
            );
            Session::flash(
                'verification_notice',
                'A new verification code was sent.'
            );
        } catch (OtpRateLimitException $exception) {
            Session::flash('verification_error', $exception->getMessage());
        } catch (Throwable $exception) {
            $reference = bin2hex(random_bytes(6));
            error_log(
                'OTP resend failure ['
                . $reference
                . '] category='
                . $exception::class
            );
            Session::flash(
                'verification_error',
                'The code could not be sent. Please try again. Reference: '
                . $reference
            );
        }

        Csrf::regenerate();
        $this->redirect('/verify-mobile');
    }

    private function pendingUserOrRedirect(): array
    {
        $user = MobileVerificationSession::user();

        if ($user === null) {
            $this->redirect('/login');
        }

        return $user;
    }

    private function renderVerification(array $user, array $data = []): void
    {
        $mobile = (string) $user['mobile_number'];
        $maskedMobile = substr($mobile, 0, 4)
            . str_repeat('*', max(0, strlen($mobile) - 7))
            . substr($mobile, -3);

        $this->view('auth/verify-mobile', array_merge([
            'csrfToken' => Csrf::token(),
            'maskedMobile' => $maskedMobile,
            'notice' => null,
            'error' => null,
            'isRecycler' => ($user['role'] ?? null) === 'RECYCLER',
        ], $data));
    }

    private function errorMessage(array $result): string
    {
        return match ($result['status'] ?? '') {
            'incorrect' => 'That code is incorrect. Attempts remaining: '
                . (int) ($result['attempts_remaining'] ?? 0)
                . '.',
            'expired' => 'That code has expired. Please request a new one.',
            'locked' => 'Too many incorrect attempts. Please request a new code.',
            default => 'No active verification code was found. Please request a new one.',
        };
    }
}
