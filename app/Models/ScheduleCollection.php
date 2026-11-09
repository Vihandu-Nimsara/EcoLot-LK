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


    // There is no municipality mapping in this schema. The schedule creator owns review.
    private function officerSelect(): string
    {
        return "SELECT sc.*, s.collection_date, s.schedule_status, pa.area_name,
            collector.full_name AS collector_name, reviewer.full_name AS reviewer_name,
            (SELECT COUNT(*) FROM collection_records cr WHERE cr.schedule_collection_id = sc.schedule_collection_id) AS record_count
            FROM schedule_collections sc
            JOIN area_collection_schedules s ON s.schedule_id = sc.schedule_id
            JOIN postal_code_areas pa ON pa.postal_area_id = s.postal_area_id
            JOIN users collector ON collector.user_id = sc.submitted_by_collector_user_id
            LEFT JOIN users reviewer ON reviewer.user_id = sc.verified_by_officer_user_id
            WHERE s.created_by_officer_user_id = :officer AND sc.verification_status <> 'DRAFT'";
    }

    public function forOfficer(int $officer, string $status = 'PENDING'): array
    {
        return $this->query($this->officerSelect() . ($status === '' ? '' : ' AND sc.verification_status = :status') .
            ' ORDER BY sc.submitted_at DESC, sc.schedule_collection_id DESC',
            ['officer' => $officer] + ($status === '' ? [] : ['status' => $status]))->fetchAll();
    }

    public function pendingForOfficer(): array
    {
        return $this->forOfficer((int) Auth::id());
    }

    public function findPendingForOfficer(int $submissionId): ?array
    {
        $row = $this->findForOfficer($submissionId, (int) Auth::id());
        return $row && $row['verification_status'] === 'PENDING' ? $row : null;
    }

    public function findForOfficer(int $id, int $officer): ?array
    {
        $row = $this->query($this->officerSelect() . ' AND sc.schedule_collection_id = :id',
            ['officer' => $officer, 'id' => $id])->fetch();
        if (!$row) return null;
        $row['records'] = $this->records($id);
        return $row;
    }

    public function records(int $id): array
    {
        $records = $this->query('SELECT cr.*, r.schedule_id, r.request_status, r.risk_review_status,
            u.full_name AS resident_name, r.pickup_address
            FROM collection_records cr JOIN e_waste_requests r ON r.request_id = cr.request_id
            JOIN users u ON u.user_id = r.public_user_id
            WHERE cr.schedule_collection_id = :id ORDER BY cr.request_id', ['id' => $id])->fetchAll();
        // Start at requested items: missing actual rows remain visible rather than becoming zeroes.
        $items = $this->query('SELECT cr.collection_record_id, ri.request_item_id, ri.quantity AS requested_quantity,
            wi.item_name, wc.category_name, cri.record_item_id, cri.actual_quantity, cri.actual_weight_kg,
            cri.actual_condition, cri.item_result, cri.actual_risk_level, cri.notes
            FROM collection_records cr JOIN request_items ri ON ri.request_id = cr.request_id
            JOIN e_waste_items wi ON wi.waste_item_id = ri.waste_item_id
            JOIN waste_categories wc ON wc.category_id = wi.category_id
            LEFT JOIN collection_record_items cri ON cri.request_item_id = ri.request_item_id
                AND cri.collection_record_id = cr.collection_record_id
            WHERE cr.schedule_collection_id = :id ORDER BY cr.request_id, ri.request_item_id', ['id' => $id])->fetchAll();
        $grouped = [];
        foreach ($items as $item) $grouped[$item['collection_record_id']][] = $item;
        foreach ($records as &$record) $record['items'] = $grouped[$record['collection_record_id']] ?? [];
        return $records;
    }

    public function assertComplete(int $id, int $scheduleId): void
    {
        $records = $this->records($id);
        $active = $this->query("SELECT request_id FROM e_waste_requests WHERE schedule_id = :id
            AND request_status NOT IN ('CANCELLED','REJECTED')", ['id' => $scheduleId])->fetchAll(PDO::FETCH_COLUMN);
        if (!$active || count($records) !== count($active)) throw new DomainException('Every active request must have a collection record.');
        foreach ($records as $record) {
            if ((int) $record['schedule_id'] !== $scheduleId || !in_array($record['request_id'], $active)
                || !in_array($record['request_status'], ['SUBMITTED','APPROVED'], true)
                || !in_array($record['risk_review_status'], ['NOT_REQUIRED','APPROVED'], true) || !$record['items']) {
                throw new DomainException('The submission contains ineligible or incomplete requests.');
            }
            $results = [];
            foreach ($record['items'] as $item) {
                if ($item['record_item_id'] === null || $item['actual_quantity'] > $item['requested_quantity']) {
                    throw new DomainException('Every requested item needs valid actual collection data.');
                }
                $quantity = (int) $item['actual_quantity'];
                $expected = $quantity === 0 ? 'NOT_COLLECTED' : ($quantity === (int) $item['requested_quantity'] ? 'COLLECTED' : 'PARTIAL');
                if ($item['item_result'] !== $expected || ($quantity > 0 &&
                    ((float) $item['actual_weight_kg'] <= 0 || $item['actual_condition'] === null || $item['actual_risk_level'] === null))) {
                    throw new DomainException('Stored collection outcomes are inconsistent.');
                }
                $results[] = $expected;
            }
            $results = array_unique($results);
            if ($record['pickup_result'] !== (count($results) === 1 ? reset($results) : 'PARTIAL')) {
                throw new DomainException('The pickup result does not match its items.');
            }
        }
        $extra = $this->query('SELECT COUNT(*) FROM collection_record_items cri
            JOIN collection_records cr ON cr.collection_record_id = cri.collection_record_id
            JOIN request_items ri ON ri.request_item_id = cri.request_item_id
            WHERE cr.schedule_collection_id = :id AND ri.request_id <> cr.request_id', ['id' => $id])->fetchColumn();
        if ($extra) throw new DomainException('A collection item belongs to another request.');
    }

    public function decide(int $id, int $officer, string $decision, string $note): void
    {
        $note = trim($note);
        $length = preg_match_all('/./us', $note);
        if (!in_array($decision, ['VERIFIED','REJECTED'], true) || $length === false || $length > 500
            || ($decision === 'REJECTED' && $note === '')) {
            throw new DomainException('Choose Verify or Reject. Rejection requires a reason; notes allow at most 500 characters.');
        }
        $this->db->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
        $this->db->beginTransaction();
        try {
            $snapshot = $this->find($id);
            if (!$snapshot) throw new OutOfBoundsException('Collection submission not found.');
            // Same lock order as Collector writes and public request changes.
            $schedule = (new AreaCollectionSchedule($this->db))->lockSchedule((int) $snapshot['schedule_id']);
            if (!$schedule || (int) $schedule['created_by_officer_user_id'] !== $officer) {
                throw new OutOfBoundsException('Collection submission not found.');
            }
            $batch = $this->query('SELECT * FROM schedule_collections WHERE schedule_collection_id = :id FOR UPDATE', ['id' => $id])->fetch();
            if (!$batch || $batch['verification_status'] !== 'PENDING' || $schedule['schedule_status'] !== 'COLLECTION_SUBMITTED') {
                throw new DomainException('This submission has changed or has already been reviewed. Reload the page.');
            }
            if ($decision === 'VERIFIED') $this->assertComplete($id, (int) $batch['schedule_id']);
            $changed = $this->query("UPDATE schedule_collections SET verification_status = :decision,
                verified_by_officer_user_id = :officer, verified_at = :time, verification_note = :note
                WHERE schedule_collection_id = :id AND verification_status = 'PENDING'", [
                'decision' => $decision, 'officer' => $officer, 'id' => $id, 'note' => $note === '' ? null : $note,
                'time' => (new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format('Y-m-d H:i:s'),
            ]);
            if ($changed->rowCount() !== 1) throw new DomainException('This submission was already reviewed.');
            if ($decision === 'VERIFIED') (new AreaCollectionSchedule($this->db))->update((int) $batch['schedule_id'], ['schedule_status' => 'COMPLETED']);
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }
}
