<?php
declare(strict_types=1);

final class ScheduleCollection extends Model
{
    protected string $table = 'schedule_collections';
    protected string $primaryKey = 'schedule_collection_id';

    public function forSchedule(int $id): ?array
    {
        $row = $this->query('SELECT * FROM schedule_collections WHERE schedule_id = :id', ['id' => $id])->fetch();
        return $row ?: null;
    }

    public function lockAssigned(int $scheduleId, int $collectorId): array
    {
        $schedule = (new AreaCollectionSchedule($this->db))->lockSchedule($scheduleId);
        $assignment = $this->query('SELECT assignment_id FROM schedule_assignments WHERE schedule_id = :id
            AND collector_user_id = :collector AND unassigned_at IS NULL FOR UPDATE', ['id' => $scheduleId, 'collector' => $collectorId])->fetch();
        if (!$schedule || !$assignment) throw new DomainException('Assigned schedule not found.');
        if (!in_array($schedule['schedule_status'], ['ASSIGNED', 'IN_PROGRESS'], true)) {
            throw new DomainException('This schedule is not open for collection changes.');
        }
        $batch = $this->forSchedule($scheduleId);
        if ($batch && ((int) $batch['submitted_by_collector_user_id'] !== $collectorId || $batch['verification_status'] !== 'DRAFT')) {
            throw new DomainException('This schedule collection is read-only.');
        }
        return $schedule;
    }

    public function draft(int $scheduleId, int $collectorId): int
    {
        $batch = $this->forSchedule($scheduleId);
        return $batch ? (int) $batch['schedule_collection_id'] : $this->create([
            'schedule_id' => $scheduleId, 'submitted_by_collector_user_id' => $collectorId,
            'verification_status' => 'DRAFT', 'submitted_at' => null,
        ]);
    }
}
