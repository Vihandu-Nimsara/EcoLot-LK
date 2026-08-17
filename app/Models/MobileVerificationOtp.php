<?php
declare(strict_types=1);

final class MobileVerificationOtp extends Model
{
    private const PURPOSES = ['REGISTRATION', 'PASSWORD_RESET'];

    protected string $table = 'mobile_verification_otps';
    protected string $primaryKey = 'otp_id';

    public function createChallenge(
        int $userId,
        string $purpose,
        string $otpHash,
        string $expiresAt
    ): int {
        $this->assertPurpose($purpose);

        return $this->create([
            'user_id' => $userId,
            'purpose' => $purpose,
            'otp_hash' => $otpHash,
            'expires_at' => $expiresAt,
        ]);
    }

    public function invalidateActiveForUser(
        int $userId,
        string $purpose
    ): int {
        $this->assertPurpose($purpose);

        $statement = $this->query(
            'UPDATE `mobile_verification_otps`
             SET `invalidated_at` = CURRENT_TIMESTAMP
             WHERE `user_id` = :user_id
               AND `purpose` = :purpose
               AND `verified_at` IS NULL
               AND `invalidated_at` IS NULL',
            [
                'user_id' => $userId,
                'purpose' => $purpose,
            ]
        );

        return $statement->rowCount();
    }

    public function findLatestActive(
        int $userId,
        string $purpose
    ): ?array {
        $this->assertPurpose($purpose);

        $result = $this->query(
            'SELECT
                `otp_id`,
                `user_id`,
                `purpose`,
                `otp_hash`,
                `expires_at`,
                `attempt_count`,
                `sent_at`
             FROM `mobile_verification_otps`
             WHERE `user_id` = :user_id
               AND `purpose` = :purpose
               AND `verified_at` IS NULL
               AND `invalidated_at` IS NULL
             ORDER BY `otp_id` DESC
             LIMIT 1',
            [
                'user_id' => $userId,
                'purpose' => $purpose,
            ]
        )->fetch();

        return $result === false ? null : $result;
    }

    public function findLatestSent(
        int $userId,
        string $purpose
    ): ?array {
        $this->assertPurpose($purpose);

        $result = $this->query(
            'SELECT `otp_id`, `sent_at`
             FROM `mobile_verification_otps`
             WHERE `user_id` = :user_id
               AND `purpose` = :purpose
             ORDER BY `otp_id` DESC
             LIMIT 1',
            [
                'user_id' => $userId,
                'purpose' => $purpose,
            ]
        )->fetch();

        return $result === false ? null : $result;
    }

    public function countSentSince(
        int $userId,
        string $purpose,
        string $since
    ): int {
        $this->assertPurpose($purpose);

        return (int) $this->query(
            'SELECT COUNT(*)
             FROM `mobile_verification_otps`
             WHERE `user_id` = :user_id
               AND `purpose` = :purpose
               AND `sent_at` >= :sent_since',
            [
                'user_id' => $userId,
                'purpose' => $purpose,
                'sent_since' => $since,
            ]
        )->fetchColumn();
    }

    public function incrementAttemptCount(
        int $otpId,
        int $maximumAttempts
    ): bool
    {
        $statement = $this->query(
            'UPDATE `mobile_verification_otps`
             SET `attempt_count` = `attempt_count` + 1
             WHERE `otp_id` = :otp_id
               AND `verified_at` IS NULL
               AND `invalidated_at` IS NULL
               AND `attempt_count` < :maximum_attempts',
            [
                'otp_id' => $otpId,
                'maximum_attempts' => $maximumAttempts,
            ]
        );

        return $statement->rowCount() === 1;
    }

    public function invalidate(int $otpId): bool
    {
        $statement = $this->query(
            'UPDATE `mobile_verification_otps`
             SET `invalidated_at` = CURRENT_TIMESTAMP
             WHERE `otp_id` = :otp_id
               AND `verified_at` IS NULL
               AND `invalidated_at` IS NULL',
            [
                'otp_id' => $otpId,
            ]
        );

        return $statement->rowCount() === 1;
    }

    public function markVerified(int $otpId): bool
    {
        $statement = $this->query(
            'UPDATE `mobile_verification_otps`
             SET `verified_at` = CURRENT_TIMESTAMP
             WHERE `otp_id` = :otp_id
               AND `verified_at` IS NULL
               AND `invalidated_at` IS NULL',
            [
                'otp_id' => $otpId,
            ]
        );

        return $statement->rowCount() === 1;
    }

    private function assertPurpose(string $purpose): void
    {
        if (!in_array($purpose, self::PURPOSES, true)) {
            throw new InvalidArgumentException(
                "Unsupported OTP purpose [{$purpose}]."
            );
        }
    }
}
