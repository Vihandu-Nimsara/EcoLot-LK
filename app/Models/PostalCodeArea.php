<?php
declare(strict_types=1);

final class PostalCodeArea extends Model
{
    protected string $table = 'postal_code_areas';
    protected string $primaryKey = 'postal_area_id';

    public function findActiveByPostalCode(string $postalCode): ?array
    {
        $result = $this->query(
            'SELECT `postal_area_id`, `postal_code`, `area_name`
             FROM `postal_code_areas`
             WHERE `postal_code` = :postal_code
               AND `area_status` = :area_status
             LIMIT 1',
            [
                'postal_code' => $postalCode,
                'area_status' => 'ACTIVE',
            ]
        )->fetch();

        return $result === false ? null : $result;
    }
}
