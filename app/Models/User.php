<?php
declare(strict_types=1);

final class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'user_id';

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

    public function activateVerifiedPublicUser(int $userId): bool
    {
        $statement = $this->query(
            'UPDATE `users`
             SET `mobile_verified_at` = CURRENT_TIMESTAMP,
                 `account_status` = :active_status
             WHERE `user_id` = :user_id
               AND `role` = :public_role
               AND `account_status` = :pending_status
               AND `mobile_verified_at` IS NULL',
            [
                'active_status' => 'ACTIVE',
                'user_id' => $userId,
                'public_role' => 'PUBLIC_USER',
                'pending_status' => 'PENDING',
            ]
        );

        return $statement->rowCount() === 1;
    }
}
