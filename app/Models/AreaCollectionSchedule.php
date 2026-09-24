<?php
declare(strict_types=1);

final class AreaCollectionSchedule extends Model
{
    protected string $table = 'area_collection_schedules';
    protected string $primaryKey = 'schedule_id';

    public function countForCampaignAndArea(
        int $campaignId,
        int $postalAreaId
    ): int {
        $row = $this->query(
            'SELECT COUNT(*) AS total
             FROM area_collection_schedules
             WHERE campaign_id = :campaign_id
               AND postal_area_id = :postal_area_id
               AND schedule_status <> :cancelled',
            [
                'campaign_id' => $campaignId,
                'postal_area_id' => $postalAreaId,
                'cancelled' => 'CANCELLED',
            ]
        )->fetch();

        return (int) ($row['total'] ?? 0);
    }

    public function existsForCampaignAreaDate(
        int $campaignId,
        int $postalAreaId,
        string $collectionDate
    ): bool {
        $row = $this->query(
            'SELECT schedule_id
             FROM area_collection_schedules
             WHERE campaign_id = :campaign_id
               AND postal_area_id = :postal_area_id
               AND collection_date = :collection_date
             LIMIT 1',
            [
                'campaign_id' => $campaignId,
                'postal_area_id' => $postalAreaId,
                'collection_date' => $collectionDate,
            ]
        )->fetch();

        return $row !== false;
    }

    /** Keep the validation reads and write in one transaction. */
    public function transaction(callable $operation): mixed
    {
        $this->db->beginTransaction();
        try {
            $result = $operation();
            $this->db->commit();
            return $result;
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function lockCampaign(int $id): ?array
    {
        $row = $this->query('SELECT * FROM monthly_campaigns WHERE campaign_id = :id FOR UPDATE', ['id' => $id])->fetch();
        return $row === false ? null : $row;
    }

    public function lockArea(int $id): ?array
    {
        $row = $this->query('SELECT * FROM postal_code_areas WHERE postal_area_id = :id FOR UPDATE', ['id' => $id])->fetch();
        return $row === false ? null : $row;
    }

    public function lockSchedule(int $id): ?array
    {
        $row = $this->query('SELECT * FROM area_collection_schedules WHERE schedule_id = :id FOR UPDATE', ['id' => $id])->fetch();
        return $row === false ? null : $row;
    }

    private function officerSelect(): string
    {
        return "SELECT s.*, c.campaign_name, c.campaign_month, a.postal_code, a.area_name,
                       u.full_name AS created_by_name,
                       (SELECT COUNT(*) FROM e_waste_requests r
                        WHERE r.schedule_id = s.schedule_id
                        AND r.request_status NOT IN ('CANCELLED', 'REJECTED')) AS active_request_count
                FROM area_collection_schedules s
                JOIN monthly_campaigns c ON c.campaign_id = s.campaign_id
                JOIN postal_code_areas a ON a.postal_area_id = s.postal_area_id
                JOIN users u ON u.user_id = s.created_by_officer_user_id";
    }

    public function getSchedulesForOfficerView(): array
    {
        return $this->query($this->officerSelect() . ' ORDER BY s.collection_date DESC, s.schedule_id DESC')->fetchAll();
    }

    public function getScheduleDetails(int $id): ?array
    {
        $row = $this->query($this->officerSelect() . ' WHERE s.schedule_id = :id', ['id' => $id])->fetch();
        return $row === false ? null : $row;
    }

    public function countActiveRequests(int $id): int
    {
        return (int) $this->query(
            "SELECT COUNT(*) FROM e_waste_requests WHERE schedule_id = :id
             AND request_status NOT IN ('CANCELLED', 'REJECTED')", ['id' => $id]
        )->fetchColumn();
    }

    public function hasRequests(int $id): bool
    {
        return (bool) $this->query('SELECT 1 FROM e_waste_requests WHERE schedule_id = :id LIMIT 1', ['id' => $id])->fetchColumn();
    }

    public function hasAssignments(int $id): bool
    {
        return (bool) $this->query('SELECT 1 FROM schedule_assignments WHERE schedule_id = :id LIMIT 1', ['id' => $id])->fetchColumn();
    }

    public function hasCollectionSubmission(int $id): bool
    {
        return (bool) $this->query('SELECT 1 FROM schedule_collections WHERE schedule_id = :id LIMIT 1', ['id' => $id])->fetchColumn();
    }

    public function hasStartedWork(int $id): bool
    {
        return $this->hasCollectionSubmission($id) || (bool) $this->query(
            "SELECT 1 FROM schedule_assignments WHERE schedule_id = :id AND unassigned_at IS NULL LIMIT 1", ['id' => $id]
        )->fetchColumn() || (bool) $this->query(
            "SELECT 1 FROM e_waste_requests WHERE schedule_id = :id AND request_status = 'COMPLETED' LIMIT 1", ['id' => $id]
        )->fetchColumn();
    }

    public function cancelPendingRequests(int $id): void
    {
        $this->query("UPDATE e_waste_requests SET request_status = 'CANCELLED'
            WHERE schedule_id = :id AND request_status IN ('SUBMITTED', 'PENDING_REVIEW', 'APPROVED')", ['id' => $id]);
    }

    public static function intakeLabel(array $schedule): string
    {
        if ($schedule['schedule_status'] !== 'OPEN') return '';
        $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo'));
        if ($now->format('Y-m-d H:i:s') > $schedule['request_cutoff_at']) return 'Deadline passed';
        return (int) $schedule['active_request_count'] >= (int) $schedule['request_capacity'] ? 'Full' : 'Accepting requests';
    }

    public static function manualStatuses(array $schedule, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo'));
        $beforeCutoff = $now->setTimezone(new DateTimeZone('Asia/Colombo'))->format('Y-m-d H:i:s') <= $schedule['request_cutoff_at'];
        $next = match ($schedule['schedule_status']) {
            'PLANNED' => $beforeCutoff ? ['OPEN', 'CANCELLED'] : ['CANCELLED'],
            'OPEN' => ['CLOSED', 'CANCELLED'],
            'CLOSED' => $beforeCutoff ? ['OPEN', 'CANCELLED'] : ['CANCELLED'],
            default => [],
        };
        // Keeping the current state permits capacity-only edits.
        return array_merge([$schedule['schedule_status']], $next);
    }

    public function availableForPublicUser(int $userId): array
    {
        return $this->query($this->officerSelect() . "
            JOIN public_profiles p ON p.postal_area_id = s.postal_area_id
            WHERE p.user_id = :user_id AND s.schedule_status = 'OPEN'
              AND s.request_cutoff_at >= :now
            HAVING active_request_count < request_capacity
            ORDER BY s.collection_date", [
                'user_id' => $userId,
                'now' => (new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format('Y-m-d H:i:s'),
            ])->fetchAll();
    }
}
