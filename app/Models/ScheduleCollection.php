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
        if ($batch && ((int) $batch['submitted_by_collector_user_id'] !== $collectorId || $batch['verification_status'] !== 'DRAFT'
            || $batch['submitted_at'] !== null || $batch['verified_at'] !== null
            || $batch['verified_by_officer_user_id'] !== null || $batch['verification_note'] !== null)) {
            throw new DomainException('This schedule collection is read-only.');
        }
        return $schedule;
    }

    public function draft(int $scheduleId, int $collectorId): int
    {
        if (!$this->db->inTransaction()) throw new LogicException('Draft creation requires a transaction.');
        $this->lockAssigned($scheduleId, $collectorId);
        $batch = $this->forSchedule($scheduleId);
        return $batch ? (int) $batch['schedule_collection_id'] : $this->create([
            'schedule_id' => $scheduleId, 'submitted_by_collector_user_id' => $collectorId,
            'verification_status' => 'DRAFT', 'submitted_at' => null,
        ]);
    }


    // Caller holds the schedule lock, shared by save/delete/submit/assign operations.
    public function removeEmptyDraft(int $scheduleId, int $collectorId): bool
    {
        if (!$this->db->inTransaction()) throw new LogicException('Draft cleanup requires a transaction.');
        $this->lockAssigned($scheduleId, $collectorId);
        $batch = $this->query('SELECT * FROM schedule_collections WHERE schedule_id = :id FOR UPDATE', ['id' => $scheduleId])->fetch();
        if (!$batch) return false;
        $id = (int) $batch['schedule_collection_id'];
        if ($this->query('SELECT 1 FROM collection_records WHERE schedule_collection_id = :id LIMIT 1', ['id' => $id])->fetchColumn()) return false;
        // Generic audit references have no FK; retain any container carrying history.
        if ($this->query("SELECT 1 FROM audit_logs WHERE entity_type = 'schedule_collections' AND entity_id = :id LIMIT 1", ['id' => $id])->fetchColumn()) {
            throw new DomainException('This empty draft has audit history and requires manual review.');
        }
        $this->delete($id);
        $this->query("UPDATE area_collection_schedules SET schedule_status = 'ASSIGNED'
            WHERE schedule_id = :id AND schedule_status = 'IN_PROGRESS'", ['id' => $scheduleId]);
        return true;
    }


    // There is no municipality mapping in this schema. The schedule creator owns review.
    private function officerSelect(): string
    {
        return "SELECT sc.*, s.collection_date, s.schedule_status, pa.area_name,
            collector.full_name AS collector_name, reviewer.full_name AS reviewer_name,
            (SELECT au.full_name FROM schedule_assignments sa JOIN users au ON au.user_id=sa.collector_user_id
                WHERE sa.schedule_id=s.schedule_id AND sa.unassigned_at IS NULL ORDER BY sa.assignment_id DESC LIMIT 1) AS assigned_collector_name,
            (SELECT COUNT(*) FROM collection_records cr WHERE cr.schedule_collection_id = sc.schedule_collection_id) AS record_count
            FROM schedule_collections sc
            JOIN area_collection_schedules s ON s.schedule_id = sc.schedule_id
            JOIN postal_code_areas pa ON pa.postal_area_id = s.postal_area_id
            JOIN users collector ON collector.user_id = sc.submitted_by_collector_user_id
            LEFT JOIN users reviewer ON reviewer.user_id = sc.verified_by_officer_user_id
            WHERE s.created_by_officer_user_id = :officer AND sc.verification_status <> 'DRAFT'";
    }

    public const REVIEW_LABELS = ['PENDING' => 'Pending Review', 'VERIFIED' => 'Verified', 'REJECTED' => 'Rejected'];
    public const REVIEW_SORTS = [
        'newest' => 'sc.submitted_at DESC, sc.schedule_collection_id DESC',
        'oldest' => 'sc.submitted_at ASC, sc.schedule_collection_id ASC',
        'collection_date' => 's.collection_date DESC, sc.schedule_collection_id DESC',
        'identifier' => 'sc.schedule_collection_id DESC',
    ];

    public function reviewQueue(int $officer, array $filters): array
    {
        $status = $filters['verification_status'];
        $sort = self::REVIEW_SORTS[$filters['sort']] ?? null;
        if (($status !== '' && !isset(self::REVIEW_LABELS[$status])) || $sort === null
            || !is_int($filters['page']) || $filters['page'] < 1 || $filters['page'] > 1000000
            || !is_string($filters['q']) || !mb_check_encoding($filters['q'], 'UTF-8') || mb_strlen($filters['q']) > 100) {
            throw new InvalidArgumentException('Invalid collection review filters.');
        }
        // Tab totals cover the Officer's entire submitted history, independent of search/page.
        $counts = array_fill_keys(array_keys(self::REVIEW_LABELS), 0);
        foreach ($this->query("SELECT sc.verification_status, COUNT(*) AS total FROM schedule_collections sc
            JOIN area_collection_schedules s ON s.schedule_id=sc.schedule_id
            WHERE s.created_by_officer_user_id=:officer AND sc.verification_status <> 'DRAFT'
            GROUP BY sc.verification_status", ['officer' => $officer])->fetchAll() as $row) {
            $counts[$row['verification_status']] = (int) $row['total'];
        }
        $where = ''; $params = ['officer' => $officer];
        if ($status !== '') { $where .= ' AND sc.verification_status=:status'; $params['status'] = $status; }
        $search = $filters['q'];
        if (preg_match('/^(SCH-|#)[0-9]+$/iD', $search, $match)) {
            $field = $match[1] === '#' ? "CONCAT('#',sc.schedule_collection_id)" : "CONCAT('SCH-',sc.schedule_id)";
            $where .= " AND $field = :identifier";
            $params['identifier'] = strtoupper($search);
        } elseif ($search !== '') {
            $pattern = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search) . '%';
            $where .= " AND (pa.area_name LIKE :area ESCAPE '!' OR collector.full_name LIKE :collector ESCAPE '!'
                OR CONCAT('SCH-',sc.schedule_id) LIKE :schedule ESCAPE '!'
                OR CONCAT('#',sc.schedule_collection_id) LIKE :submission ESCAPE '!')";
            foreach (['area', 'collector', 'schedule', 'submission'] as $key) $params[$key] = $pattern;
        }
        $total = (int) $this->query('SELECT COUNT(*) FROM (' . $this->officerSelect() . $where . ') matches', $params)->fetchColumn();
        $pages = max(1, (int) ceil($total / 20));
        $page = min($filters['page'], $pages);
        $offset = ($page - 1) * 20;
        $rows = $this->query($this->officerSelect() . $where . " ORDER BY $sort LIMIT 20 OFFSET $offset", $params)->fetchAll();
        return ['rows' => $rows, 'total' => $total, 'counts' => $counts, 'page' => $page, 'pages' => $pages];
    }

    public function findForOfficer(int $id, int $officer): ?array
    {
        $row = $this->query($this->officerSelect() . ' AND sc.schedule_collection_id = :id',
            ['officer' => $officer, 'id' => $id])->fetch();
        if (!$row) return null;
        $row['records'] = $this->records($id);
        $row['overview'] = self::overview($row['records']);
        $row['integrity_error'] = null;
        try { $this->assertComplete($id, (int) $row['schedule_id'], $row['records'], $row['verification_status'] !== 'PENDING'); }
        catch (DomainException $error) { $row['integrity_error'] = $error->getMessage(); }
        $row['can_verify'] = $row['verification_status'] === 'PENDING'
            && $row['schedule_status'] === 'COLLECTION_SUBMITTED' && $row['integrity_error'] === null;
        $row['can_reject'] = $row['verification_status'] === 'PENDING' && $row['schedule_status'] === 'COLLECTION_SUBMITTED';
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
        $items = $this->query('SELECT cr.collection_record_id, ri.request_item_id, ri.request_id AS item_request_id,
            ri.quantity AS requested_quantity, wi.item_name, wc.category_name, cri.record_item_id,
            cri.actual_quantity, cri.actual_weight_kg, cri.actual_condition, cri.item_result, cri.actual_risk_level, cri.notes
            FROM collection_records cr JOIN request_items ri ON ri.request_id = cr.request_id
            JOIN e_waste_items wi ON wi.waste_item_id = ri.waste_item_id
            JOIN waste_categories wc ON wc.category_id = wi.category_id
            LEFT JOIN collection_record_items cri ON cri.request_item_id = ri.request_item_id
                AND cri.collection_record_id = cr.collection_record_id
            WHERE cr.schedule_collection_id = :id
            UNION ALL
            SELECT cr.collection_record_id, ri.request_item_id, ri.request_id AS item_request_id,
            ri.quantity AS requested_quantity, wi.item_name, wc.category_name, cri.record_item_id,
            cri.actual_quantity, cri.actual_weight_kg, cri.actual_condition, cri.item_result, cri.actual_risk_level, cri.notes
            FROM collection_records cr JOIN collection_record_items cri ON cri.collection_record_id=cr.collection_record_id
            JOIN request_items ri ON ri.request_item_id=cri.request_item_id
            JOIN e_waste_items wi ON wi.waste_item_id=ri.waste_item_id
            JOIN waste_categories wc ON wc.category_id=wi.category_id
            WHERE cr.schedule_collection_id=:extra_id AND ri.request_id<>cr.request_id
            ORDER BY collection_record_id, request_item_id', ['id' => $id, 'extra_id' => $id])->fetchAll();
        $grouped = [];
        foreach ($items as $item) $grouped[$item['collection_record_id']][] = $item;
        foreach ($records as &$record) $record['items'] = $grouped[$record['collection_record_id']] ?? [];
        return $records;
    }

    private static function overview(array $records): array
    {
        $totals = ['requests' => count($records), 'COLLECTED' => 0, 'PARTIAL' => 0, 'NOT_COLLECTED' => 0,
            'actual_quantity' => 0, 'actual_weight_kg' => null, 'missing_weights' => 0];
        $milliKg = 0; $hasWeight = false;
        foreach ($records as $record) {
            $totals[$record['pickup_result']]++;
            foreach ($record['items'] as $item) {
                if ($item['record_item_id'] === null) continue;
                $totals['actual_quantity'] += (int) $item['actual_quantity'];
                if ((int) $item['actual_quantity'] === 0) continue;
                if ($item['actual_weight_kg'] === null) { $totals['missing_weights']++; continue; }
                // DECIMAL(10,3) summed in integer thousandths, without float rounding.
                [$whole, $fraction] = array_pad(explode('.', (string) $item['actual_weight_kg'], 2), 2, '');
                $milliKg += (int) $whole * 1000 + (int) str_pad($fraction, 3, '0');
                $hasWeight = true;
            }
        }
        if ($hasWeight) $totals['actual_weight_kg'] = intdiv($milliKg, 1000) . '.' . str_pad((string) ($milliKg % 1000), 3, '0', STR_PAD_LEFT);
        return $totals;
    }

    public function assertComplete(int $id, int $scheduleId, ?array $records = null, bool $historical = false): void
    {
        $records ??= $this->records($id);
        $active = $this->query("SELECT request_id FROM e_waste_requests WHERE schedule_id = :id
            AND request_status NOT IN ('CANCELLED','REJECTED')", ['id' => $scheduleId])->fetchAll(PDO::FETCH_COLUMN);
        if (!$active || count($records) !== count($active)) throw new DomainException('Every active request must have a collection record.');
        foreach ($records as $record) {
            if ((int) $record['schedule_id'] !== $scheduleId || !in_array($record['request_id'], $active)
                || !in_array($record['request_status'], $historical ? ['SUBMITTED','APPROVED','COMPLETED'] : ['SUBMITTED','APPROVED'], true)
                || !in_array($record['risk_review_status'], ['NOT_REQUIRED','APPROVED'], true) || !$record['items']) {
                throw new DomainException('The submission contains ineligible or incomplete requests.');
            }
            $results = [];
            foreach ($record['items'] as $item) {
                if ($item['item_request_id'] != $record['request_id']) throw new DomainException('A collection item belongs to another request.');
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
    }

    public function decide(int $id, int $officer, string $decision, string $note): void
    {
        if (!mb_check_encoding($note, 'UTF-8')) throw new DomainException('Review notes must use valid UTF-8 text.');
        $note = preg_replace('/^\s+|\s+$/u', '', $note);
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
            if (!$batch || $batch['schedule_id'] != $snapshot['schedule_id'] || $batch['verification_status'] !== 'PENDING' || $schedule['schedule_status'] !== 'COLLECTION_SUBMITTED') {
                throw new DomainException('This submission has changed or has already been reviewed. Reload the page.', 409);
            }
            if ($decision === 'VERIFIED') $this->assertComplete($id, (int) $batch['schedule_id']);
            $changed = $this->query("UPDATE schedule_collections SET verification_status = :decision,
                verified_by_officer_user_id = :officer, verified_at = :time, verification_note = :note
                WHERE schedule_collection_id = :id AND verification_status = 'PENDING'", [
                'decision' => $decision, 'officer' => $officer, 'id' => $id, 'note' => $note === '' ? null : $note,
                'time' => (new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format('Y-m-d H:i:s'),
            ]);
            if ($changed->rowCount() !== 1) throw new DomainException('This submission was already reviewed.', 409);
            if ($decision === 'VERIFIED') (new AreaCollectionSchedule($this->db))->update((int) $batch['schedule_id'], ['schedule_status' => 'COMPLETED']);
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }
}
