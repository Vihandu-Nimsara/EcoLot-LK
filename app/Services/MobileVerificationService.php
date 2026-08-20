<?php
declare(strict_types=1);

final class MobileVerificationService
{
    public function sendRegistrationOtp(int $userId, string $mobileNumber): void
    {
        $otpService = new OtpService();
        $challenge = $otpService->issueRegistrationOtp($userId);

        try {
            (new SmsGatewayService())->sendRegistrationOtp(
                $mobileNumber,
                (string) $challenge['otp']
            );
        } catch (Throwable $exception) {
            $otpService->invalidateChallenge((int) $challenge['otp_id']);
            throw $exception;
        }
    }

    public function verifyAndActivate(int $userId, string $otp): array
    {
        $connection = Database::connection();

        try {
            $connection->beginTransaction();
            $result = (new OtpService($connection))->verifyRegistrationOtp(
                $userId,
                $otp
            );

            if (
                $result['success']
                && !(new User($connection))->activateAfterMobileVerification($userId)
            ) {
                throw new RuntimeException(
                    'The pending account could not be activated.'
                );
            }

            $connection->commit();

            return $result;
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    public function verifyAndActivateRecycler(int $userId, string $otp): array
    {
        $connection = Database::connection();

        try {
            $connection->beginTransaction();
            $result = (new OtpService($connection))->verifyRegistrationOtp(
                $userId,
                $otp
            );

            if (
                $result['success']
                && !(new User($connection))->activateRecyclerAfterMobileVerification(
                    $userId
                )
            ) {
                throw new RuntimeException(
                    'The pending recycler account could not be activated.'
                );
            }

            $connection->commit();
            return $result;
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }
}
