<?php
declare(strict_types=1);

final class CollectionRecord extends Model
{
    protected string $table = 'collection_records';
    protected string $primaryKey = 'collection_record_id';

    private function requestSelect(): string
    {
        return "SELECT r.*, s.collection_date, s.schedule_status, pa.area_name, u.full_name,
            cr.collection_record_id, cr.pickup_result, cr.pickup_attempted_at, cr.collector_note,
            sc.verification_status, sc.submitted_at AS collection_submitted_at, sc.verification_note,
            sc.submitted_by_collector_user_id,
            (SELECT COUNT(*) FROM request_items ri WHERE ri.request_id = r.request_id) AS item_count
            FROM e_waste_requests r
            JOIN area_collection_schedules s ON s.schedule_id = r.schedule_id
            JOIN postal_code_areas pa ON pa.postal_area_id = s.postal_area_id
            JOIN users u ON u.user_id = r.public_user_id
            LEFT JOIN schedule_collections sc ON sc.schedule_id = s.schedule_id
            LEFT JOIN collection_records cr ON cr.request_id = r.request_id
            WHERE EXISTS (SELECT 1 FROM schedule_assignments sa WHERE sa.schedule_id = s.schedule_id
                AND sa.collector_user_id = :collector AND sa.unassigned_at IS NULL)
            AND (sc.schedule_collection_id IS NULL OR sc.submitted_by_collector_user_id = :owner)";
    }

    public function assignedRequests(int $collectorId): array
    {
        return $this->query($this->requestSelect() . ' ORDER BY s.collection_date, r.request_id', ['collector' => $collectorId, 'owner' => $collectorId])->fetchAll();
    }

    public function assignedRequest(int $requestId, int $collectorId): ?array
    {
        $row = $this->query($this->requestSelect() . ' AND r.request_id = :id', ['collector' => $collectorId, 'owner' => $collectorId, 'id' => $requestId])->fetch();
        if (!$row) return null;
        $row['items'] = (new CollectionRecordItem($this->db))->forRequest($requestId);
        return $row;
    }

    public function ownedRecord(int $recordId, int $collectorId): ?array
    {
        $record = $this->find($recordId);
        return $record ? $this->assignedRequest((int) $record['request_id'], $collectorId) : null;
    }

    public static function editable(array $request): bool
    {
        return in_array($request['schedule_status'], ['ASSIGNED', 'IN_PROGRESS'], true)
            && in_array($request['verification_status'], [null, 'DRAFT'], true)
            && in_array($request['request_status'], ['SUBMITTED', 'APPROVED'], true)
            && in_array($request['risk_review_status'], ['NOT_REQUIRED', 'APPROVED'], true);
    }

    public static function status(array $request): string
    {
        if (!$request['collection_record_id']) return 'NOT RECORDED';
        return $request['verification_status'] === 'PENDING' ? 'SUBMITTED' : $request['verification_status'];
    }

    private function transaction(callable $operation): mixed
    {
        $this->db->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
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

    private function lockRequest(int $requestId, int $collectorId): array
    {
        $snapshot = $this->assignedRequest($requestId, $collectorId);
        if (!$snapshot) throw new DomainException('Assigned request not found.');
        (new ScheduleCollection($this->db))->lockAssigned((int) $snapshot['schedule_id'], $collectorId);
        $this->query('SELECT request_id FROM e_waste_requests WHERE request_id = :id FOR UPDATE', ['id' => $requestId]);
        $request = $this->assignedRequest($requestId, $collectorId);
        if (!$request || $request['schedule_id'] != $snapshot['schedule_id'] || !self::editable($request)) {
            throw new DomainException('This request is not eligible for draft collection changes.');
        }
        return $request;
    }

    public function saveDraft(int $requestId, int $collectorId, array $input, ?int $recordId = null): int
    {
        return $this->transaction(function () use ($requestId, $collectorId, $input, $recordId): int {
            $request = $this->lockRequest($requestId, $collectorId);
            if (($recordId === null && $request['collection_record_id']) || ($recordId !== null && (int) $request['collection_record_id'] !== $recordId)) {
                throw new DomainException('A record already exists or the record does not belong to this request.');
            }
            $validator = new Validator();
            if (!$validator->validate($input, ['pickup_result' => ['required', 'in:COLLECTED,PARTIAL,NOT_COLLECTED'], 'collector_note' => ['max:500']])) {
                throw new DomainException('Choose a valid pickup result and use at most 500 characters for notes.');
            }
            $items = new CollectionRecordItem($this->db);
            $rows = $items->validated($requestId, $input['items'] ?? null);
            $results = array_unique(array_column($rows, 'item_result'));
            $result = count($results) === 1 ? reset($results) : 'PARTIAL';
            if ($input['pickup_result'] !== $result) throw new DomainException('Pickup result must match the actual item quantities.');
            $attributes = ['pickup_result' => $result, 'collector_note' => trim((string) ($input['collector_note'] ?? '')) ?: null];
            if ($recordId === null) {
                $batchId = (new ScheduleCollection($this->db))->draft((int) $request['schedule_id'], $collectorId);
                $recordId = $this->create($attributes + ['request_id' => $requestId, 'schedule_collection_id' => $batchId,
                    'pickup_attempted_at' => (new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format('Y-m-d H:i:s')]);
            } else {
                $this->update($recordId, $attributes);
            }
            $items->replaceForRecord($recordId, $rows);
            return $recordId;
        });
    }

    public function deleteDraft(int $recordId, int $collectorId): void
    {
        $this->transaction(function () use ($recordId, $collectorId): void {
            $record = $this->ownedRecord($recordId, $collectorId);
            if (!$record) throw new DomainException('Collection record not found.');
            $request = $this->lockRequest((int) $record['request_id'], $collectorId);
            if ((int) $request['collection_record_id'] !== $recordId) throw new DomainException('Collection record not found.');
            // The existing FK cascades deletion to collection_record_items.
            $this->delete($recordId);
        });
    }

    public function submitSchedule(int $scheduleId, int $collectorId): void
    {
        $this->transaction(function () use ($scheduleId, $collectorId): void {
            $batches = new ScheduleCollection($this->db);
            $batches->lockAssigned($scheduleId, $collectorId);
            $requests = array_values(array_filter($this->assignedRequests($collectorId), static fn ($r) => (int) $r['schedule_id'] === $scheduleId
                && !in_array($r['request_status'], ['CANCELLED', 'REJECTED'], true)));
            if (!$requests) throw new DomainException('This schedule has no requests to submit.');
            foreach ($requests as $request) {
                if (!self::editable($request) || !$request['collection_record_id']) {
                    throw new DomainException('Every active request must be cleared for collection and have a saved draft before schedule submission.');
                }
                $saved = (new CollectionRecordItem($this->db))->forRequest((int) $request['request_id']);
                $raw = [];
                foreach ($saved as $item) $raw[$item['request_item_id']] = [
                    'actual_quantity' => (string) $item['actual_quantity'], 'actual_weight_kg' => (string) $item['actual_weight_kg'],
                    'actual_condition' => (string) $item['actual_condition'], 'notes' => (string) $item['notes'],
                ];
                (new CollectionRecordItem($this->db))->validated((int) $request['request_id'], $raw);
            }
            $batch = $batches->forSchedule($scheduleId);
            $batches->update((int) $batch['schedule_collection_id'], ['verification_status' => 'PENDING',
                'submitted_at' => (new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format('Y-m-d H:i:s')]);
            (new AreaCollectionSchedule($this->db))->update($scheduleId, ['schedule_status' => 'COLLECTION_SUBMITTED']);
        });
    }
}
