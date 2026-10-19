<?php
declare(strict_types=1);

final class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'user_id';

    public function lockForVerification(int $userId): void
    {
        if (!$this->db->inTransaction()) {
            throw new LogicException('OTP operations require a transaction.');
        }
        $this->query('SELECT `user_id` FROM `users` WHERE `user_id` = :id FOR UPDATE', ['id' => $userId]);
    }

    public function findByMobileNumber(string $mobileNumber): ?array
    {
        $result = $this->query(
            'SELECT
                `user_id`,
                `full_name`,
                `mobile_number`,
                `email`,
                `password_hash`,
                `role`,
                `account_status`,
                `mobile_verified_at`,
                `must_change_password`
             FROM `users`
             WHERE `mobile_number` = :mobile_number
             LIMIT 1',
            [
                'mobile_number' => $mobileNumber,
            ]
        )->fetch();

        return $result === false ? null : $result;
    }

    public function findByEmail(string $email): ?array
    {
        $result = $this->query(
            'SELECT
                `user_id`,
                `email`
             FROM `users`
             WHERE `email` = :email
             LIMIT 1',
            [
                'email' => $email,
            ]
        )->fetch();

        return $result === false ? null : $result;
    }

    public function activateAfterMobileVerification(int $userId): bool
    {
        $statement = $this->query(
            'UPDATE `users`
             SET `mobile_verified_at` = CURRENT_TIMESTAMP,
                 `account_status` = \'ACTIVE\'
             WHERE `user_id` = :user_id
               AND `role` = \'PUBLIC_USER\'
               AND `account_status` = \'PENDING\'
               AND `mobile_verified_at` IS NULL',
            ['user_id' => $userId]
        );

        return $statement->rowCount() === 1;
    }

    public function activateRecyclerAfterMobileVerification(int $userId): bool
    {
        $statement = $this->query(
            'UPDATE `users`
             SET `mobile_verified_at` = CURRENT_TIMESTAMP,
                 `account_status` = \'ACTIVE\'
             WHERE `user_id` = :user_id
               AND `role` = \'RECYCLER\'
               AND `account_status` = \'PENDING\'
               AND `mobile_verified_at` IS NULL',
            ['user_id' => $userId]
        );

        return $statement->rowCount() === 1;
    }

        public function updatePassword(int $userId, string $newPassword): bool
    {
        return $this->update($userId, [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'must_change_password' => 0,
        ]);
    }

    public function hasActivePickupRequests(int $userId): bool
    {
        return (bool) $this->query(
            "SELECT 1 FROM `e_waste_requests`
             WHERE `public_user_id` = :user_id
               AND `request_status` IN ('SUBMITTED', 'PENDING_REVIEW', 'APPROVED')
             LIMIT 1",
            ['user_id' => $userId]
        )->fetch();
    }

    public function disablePublicAccount(int $userId): bool
    {
        $statement = $this->query(
            "UPDATE `users` SET `account_status` = 'DISABLED'
             WHERE `user_id` = :user_id
               AND `role` = 'PUBLIC_USER'
               AND `account_status` = 'ACTIVE'
               AND NOT EXISTS (
                   SELECT 1 FROM `e_waste_requests` r
                   WHERE r.`public_user_id` = :request_user_id
                     AND r.`request_status` IN ('SUBMITTED', 'PENDING_REVIEW', 'APPROVED')
               )",
            ['user_id' => $userId, 'request_user_id' => $userId]
        );

        return $statement->rowCount() === 1;
    }
}
