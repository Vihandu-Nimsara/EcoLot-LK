<?php
declare(strict_types=1);

final class EWasteRequest extends Model
{
    protected string $table = 'e_waste_requests';
    protected string $primaryKey = 'request_id';

    /** All request inserts must share the schedule lock with capacity/status edits. */
    public function create(array $attributes): int
    {
        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) $this->db->beginTransaction();
        try {
            $scheduleModel = new AreaCollectionSchedule($this->db);
            $schedule = $scheduleModel->lockSchedule((int) ($attributes['schedule_id'] ?? 0));
            $profile = (new PublicProfile($this->db))->find((int) ($attributes['public_user_id'] ?? 0));
            $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo'));
            if ($schedule === null || $schedule['schedule_status'] !== 'OPEN'
                || $now->format('Y-m-d H:i:s') > $schedule['request_cutoff_at']
                || $profile === null || (int) $profile['postal_area_id'] !== (int) $schedule['postal_area_id']
                || $scheduleModel->countActiveRequests((int) $schedule['schedule_id']) >= (int) $schedule['request_capacity']) {
                throw new DomainException('Select an open schedule in your postal area before its cut-off with remaining capacity.');
            }
            $id = parent::create($attributes);
            if ($ownsTransaction) $this->db->commit();
            return $id;
        } catch (Throwable $error) {
            if ($ownsTransaction && $this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }
}
