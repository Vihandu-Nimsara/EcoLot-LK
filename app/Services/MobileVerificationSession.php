<?php
declare(strict_types=1);

final class MobileVerificationSession
{
    private const USER_ID_KEY = 'pending_verification_user_id';

    public static function start(int $userId): void
    {
        Session::put(self::USER_ID_KEY, $userId);
    }

    public static function user(): ?array
    {
        $userId = Session::get(self::USER_ID_KEY);

        if (!is_int($userId) && !ctype_digit((string) $userId)) {
            return null;
        }

        $user = (new User())->find((int) $userId);

        if (
            $user === null
            || !in_array(
                $user['role'] ?? null,
                ['PUBLIC_USER', 'RECYCLER'],
                true
            )
            || ($user['account_status'] ?? null) !== 'PENDING'
            || ($user['mobile_verified_at'] ?? null) !== null
        ) {
            return null;
        }

        return $user;
    }

    public static function clear(): void
    {
        Session::forget(self::USER_ID_KEY);
    }
}
