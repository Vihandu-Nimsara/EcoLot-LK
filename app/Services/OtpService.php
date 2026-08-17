<?php
declare(strict_types=1);

final class OtpService
{
    public const PURPOSE_REGISTRATION = 'REGISTRATION';

    private const OTP_VALIDITY_SECONDS = 300;
    private const MAXIMUM_ATTEMPTS = 5;
    private const RESEND_COOLDOWN_SECONDS = 60;
    private const MAXIMUM_SENDS_PER_HOUR = 5;
    private const MAXIMUM_SENDS_PER_DAY = 10;

    private PDO $connection;
    private MobileVerificationOtp $otpModel;
    private DateTimeZone $timezone;

    public function __construct(
        ?PDO $connection = null,
        ?DateTimeZone $timezone = null
    ) {
        $this->connection = $connection ?? Database::connection();
        $this->otpModel = new MobileVerificationOtp($this->connection);
        $this->timezone = $timezone ?? new DateTimeZone('Asia/Colombo');
    }

    public function issueRegistrationOtp(int $userId): array
    {
        $now = new DateTimeImmutable('now', $this->timezone);

        $this->assertSendAllowed(
            $userId,
            self::PURPOSE_REGISTRATION,
            $now
        );

        $plainOtp = (string) random_int(100000, 999999);
        $otpHash = password_hash($plainOtp, PASSWORD_DEFAULT);
        $expiresAt = $now
            ->modify('+' . self::OTP_VALIDITY_SECONDS . ' seconds')
            ->format('Y-m-d H:i:s');

        try {
            $this->connection->beginTransaction();

            $this->otpModel->invalidateActiveForUser(
                $userId,
                self::PURPOSE_REGISTRATION
            );

            $otpId = $this->otpModel->createChallenge(
                $userId,
                self::PURPOSE_REGISTRATION,
                $otpHash,
                $expiresAt
            );

            $this->connection->commit();
        } catch (Throwable $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $exception;
        }

        return [
            'otp_id' => $otpId,
            'otp' => $plainOtp,
            'expires_at' => $expiresAt,
            'expires_in_seconds' => self::OTP_VALIDITY_SECONDS,
        ];
    }

    public function verifyRegistrationOtp(
        int $userId,
        string $submittedOtp
    ): array {
        $challenge = $this->otpModel->findLatestActive(
            $userId,
            self::PURPOSE_REGISTRATION
        );

        if ($challenge === null) {
            return $this->result(false, 'not_found', 0);
        }

        $otpId = (int) $challenge['otp_id'];
        $attemptCount = (int) $challenge['attempt_count'];
        $expiresAt = DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            (string) $challenge['expires_at'],
            $this->timezone
        );

        if (
            $expiresAt === false
            || $expiresAt <= new DateTimeImmutable('now', $this->timezone)
        ) {
            $this->otpModel->invalidate($otpId);

            return $this->result(false, 'expired', 0);
        }

        if ($attemptCount >= self::MAXIMUM_ATTEMPTS) {
            $this->otpModel->invalidate($otpId);

            return $this->result(false, 'locked', 0);
        }

        $hasValidFormat = preg_match('/^\d{6}$/', $submittedOtp) === 1;
        $matches = $hasValidFormat && password_verify(
            $submittedOtp,
            (string) $challenge['otp_hash']
        );

        if (!$matches) {
            $this->otpModel->incrementAttemptCount(
                $otpId,
                self::MAXIMUM_ATTEMPTS
            );

            $remainingAttempts = max(
                0,
                self::MAXIMUM_ATTEMPTS - ($attemptCount + 1)
            );

            if ($remainingAttempts === 0) {
                $this->otpModel->invalidate($otpId);

                return $this->result(false, 'locked', 0);
            }

            return $this->result(
                false,
                'incorrect',
                $remainingAttempts
            );
        }

        if (!$this->otpModel->markVerified($otpId)) {
            return $this->result(false, 'not_found', 0);
        }

        return $this->result(true, 'verified', 0);
    }

    public function invalidateChallenge(int $otpId): bool
    {
        return $this->otpModel->invalidate($otpId);
    }

    private function assertSendAllowed(
        int $userId,
        string $purpose,
        DateTimeImmutable $now
    ): void {
        $latest = $this->otpModel->findLatestSent($userId, $purpose);

        if ($latest !== null) {
            $lastSentAt = DateTimeImmutable::createFromFormat(
                'Y-m-d H:i:s',
                (string) $latest['sent_at'],
                $this->timezone
            );

            if ($lastSentAt !== false) {
                $secondsSinceLastSend = $now->getTimestamp()
                    - $lastSentAt->getTimestamp();

                if ($secondsSinceLastSend < self::RESEND_COOLDOWN_SECONDS) {
                    throw new OtpRateLimitException(
                        'Please wait before requesting another code.',
                        self::RESEND_COOLDOWN_SECONDS
                            - max(0, $secondsSinceLastSend)
                    );
                }
            }
        }

        $sentDuringHour = $this->otpModel->countSentSince(
            $userId,
            $purpose,
            $now->modify('-1 hour')->format('Y-m-d H:i:s')
        );

        if ($sentDuringHour >= self::MAXIMUM_SENDS_PER_HOUR) {
            throw new OtpRateLimitException(
                'Too many verification codes were requested. Try again later.',
                3600
            );
        }

        $sentDuringDay = $this->otpModel->countSentSince(
            $userId,
            $purpose,
            $now->modify('-24 hours')->format('Y-m-d H:i:s')
        );

        if ($sentDuringDay >= self::MAXIMUM_SENDS_PER_DAY) {
            throw new OtpRateLimitException(
                'The daily verification-code limit has been reached.',
                86400
            );
        }
    }

    private function result(
        bool $success,
        string $status,
        int $attemptsRemaining
    ): array {
        return [
            'success' => $success,
            'status' => $status,
            'attempts_remaining' => $attemptsRemaining,
        ];
    }
}
