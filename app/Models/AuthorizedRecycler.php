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

    /** Read-only compliance data for the authenticated Recycler dashboard. */
    public function dashboardCompliance(int $userId): ?array
    {
        return $this->query("SELECT r.verification_status,
            (SELECT MAX(l.expiry_date) FROM recycler_licenses l
             WHERE l.recycler_user_id = r.user_id AND l.license_status = 'VALID'
             AND l.expiry_date >= CURRENT_DATE) AS valid_license_expiry
            FROM authorized_recyclers r WHERE r.user_id = :owner",
            ['owner' => $userId])->fetch() ?: null;
    }

    public function capabilitiesForRecycler(int $userId): array
    {
        return $this->query("SELECT c.category_name, rc.capability_status
            FROM recycler_capabilities rc
            JOIN waste_categories c ON c.category_id = rc.category_id
            WHERE rc.recycler_user_id = :owner ORDER BY c.category_name",
            ['owner' => $userId])->fetchAll();
    }
}

