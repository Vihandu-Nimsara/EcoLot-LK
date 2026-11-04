<?php
declare(strict_types=1);

final class FlaggedRequest extends Model
{
    public function listing(): array
    {
        $requests = $this->query("SELECT r.*, u.full_name, s.campaign_id, s.collection_date, s.schedule_status,
            c.campaign_name, a.area_name, reviewer.full_name AS reviewer_name
            FROM e_waste_requests r JOIN users u ON u.user_id = r.public_user_id
            JOIN area_collection_schedules s ON s.schedule_id = r.schedule_id
            JOIN monthly_campaigns c ON c.campaign_id = s.campaign_id
            JOIN postal_code_areas a ON a.postal_area_id = s.postal_area_id
            LEFT JOIN users reviewer ON reviewer.user_id = r.reviewed_by_officer_user_id
            WHERE r.risk_review_status <> 'NOT_REQUIRED'
            ORDER BY (r.risk_review_status = 'PENDING') DESC, r.submitted_at, r.request_id")->fetchAll();
        $items = $this->query('SELECT ri.*, wi.item_name FROM request_items ri JOIN e_waste_items wi ON wi.waste_item_id = ri.waste_item_id JOIN e_waste_requests r ON r.request_id = ri.request_id WHERE r.risk_review_status <> :status', ['status' => 'NOT_REQUIRED'])->fetchAll();
        $byRequest = [];
        foreach ($items as $item) $byRequest[$item['request_id']][] = $item;
        foreach ($requests as &$request) $request['items'] = $byRequest[$request['request_id']] ?? [];
        return $requests;
    }

    public function decide(int $id, string $decision, string $note, int $officer): void
    {
        $length = preg_match_all('/./us', $note);
        if (!in_array($decision, ['APPROVED', 'REJECTED'], true) || $length === false || $length > 500 || ($decision === 'REJECTED' && trim($note) === '')) {
            throw new DomainException('Choose approve or reject. Rejection requires a reason; notes must be at most 500 characters.');
        }
        // Match request editing/cancellation lock order: schedule, then request.
        $this->db->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
        $this->db->beginTransaction();
        try {
            $scheduleId = $this->query('SELECT schedule_id FROM e_waste_requests WHERE request_id = :id', ['id' => $id])->fetchColumn();
            if (!$scheduleId) throw new DomainException('Request no longer exists.');
            $schedule = (new AreaCollectionSchedule($this->db))->lockSchedule((int) $scheduleId);
            $request = $this->query('SELECT * FROM e_waste_requests WHERE request_id = :id FOR UPDATE', ['id' => $id])->fetch();
            if (!$request || (int) $request['schedule_id'] !== (int) $scheduleId || $request['request_status'] !== 'PENDING_REVIEW' || $request['risk_review_status'] !== 'PENDING') throw new DomainException('This request has changed or has already been reviewed. Reload the page.');
            if (!$schedule || !in_array($schedule['schedule_status'], ['PLANNED','OPEN','CLOSED'], true) || (new AreaCollectionSchedule($this->db))->hasStartedWork((int) $scheduleId)) throw new DomainException('This schedule no longer permits risk review.');
            $this->query('UPDATE e_waste_requests SET request_status = :decision, risk_review_status = :risk, review_note = :note, reviewed_by_officer_user_id = :officer, reviewed_at = :time WHERE request_id = :id', [
                'decision' => $decision, 'risk' => $decision, 'note' => trim($note), 'officer' => $officer, 'id' => $id,
                'time' => (new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format('Y-m-d H:i:s'),
            ]);
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }
}
