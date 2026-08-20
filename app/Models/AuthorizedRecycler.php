<?php
declare(strict_types=1);

final class AuthorizedRecycler extends Model
{
    protected string $table = 'authorized_recyclers';
    protected string $primaryKey = 'user_id';

    public function findByCompanyName(string $companyName): ?array
    {
        $result = $this->query(
            'SELECT `user_id`, `company_name`
             FROM `authorized_recyclers`
             WHERE `company_name` = :company_name
             LIMIT 1',
            ['company_name' => $companyName]
        )->fetch();

        return $result === false ? null : $result;
    }

    public function findByUserId(int $userId): ?array
    {
        $result = $this->query(
            'SELECT `user_id`, `company_name`, `verification_status`
             FROM `authorized_recyclers`
             WHERE `user_id` = :user_id
             LIMIT 1',
            ['user_id' => $userId]
        )->fetch();

        return $result === false ? null : $result;
    }
}

