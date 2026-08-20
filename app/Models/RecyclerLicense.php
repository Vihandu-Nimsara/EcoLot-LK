<?php
declare(strict_types=1);

final class RecyclerLicense extends Model
{
    protected string $table = 'recycler_licenses';
    protected string $primaryKey = 'license_id';

    public function findByLicenseNumber(string $licenseNumber): ?array
    {
        $result = $this->query(
            'SELECT `license_id`, `license_number`
             FROM `recycler_licenses`
             WHERE `license_number` = :license_number
             LIMIT 1',
            ['license_number' => $licenseNumber]
        )->fetch();

        return $result === false ? null : $result;
    }
}
