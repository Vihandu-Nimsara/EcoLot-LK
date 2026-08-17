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
}