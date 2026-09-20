<?php
declare(strict_types=1);

final class AreaCollectionSchedule extends Model
{
    protected string $table = 'area_collection_schedules';
    protected string $primaryKey = 'schedule_id';

    /**
     * Schedules a public user in the given postal area can currently request against:
     * status OPEN, cutoff not passed, and capacity not yet reached.
     */
    public function openForPostalArea(int $postalAreaId, ?int $excludeRequestId = null): array
{
    $sql = 'SELECT
                s.`schedule_id`,
                s.`collection_date`,
                s.`request_capacity`,
                (
                    SELECT COUNT(*) FROM `e_waste_requests` r
                    WHERE r.`schedule_id` = s.`schedule_id`
                      AND r.`request_status` NOT IN (\'REJECTED\', \'CANCELLED\')'
                      . ($excludeRequestId !== null ? ' AND r.`request_id` != :exclude_request_id' : '') . '
                ) AS `requests_taken`
             FROM `area_collection_schedules` s
             WHERE s.`postal_area_id` = :postal_area_id
               AND s.`schedule_status` = \'OPEN\'
               AND s.`request_cutoff_at` > NOW()
             HAVING `requests_taken` < s.`request_capacity`
             ORDER BY s.`collection_date`';

    $params = ['postal_area_id' => $postalAreaId];

    if ($excludeRequestId !== null) {
        $params['exclude_request_id'] = $excludeRequestId;
    }

    return $this->query($sql, $params)->fetchAll();
}

    /**
     * Re-checks one schedule is still open, in the right area, and has capacity —
     * used server-side right before a request is inserted.
     */
    public function isBookable(int $scheduleId, int $postalAreaId, ?int $excludeRequestId = null): bool
{
    foreach ($this->openForPostalArea($postalAreaId, $excludeRequestId) as $schedule) {
        if ((int) $schedule['schedule_id'] === $scheduleId) {
            return true;
        }
    }

    return false;
}
}