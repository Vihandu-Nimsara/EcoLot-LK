<?php
declare(strict_types=1);

final class OfficerDashboard extends Model
{
    public function overview(): array
    {
        // Local demos do not require a background scheduler: visiting the workspace
        // catches up expired campaign scheduling without changing collection work.
        (new MonthlyCampaign($this->db))->closeExpired();
        $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo'));
        $stats = $this->query("SELECT
            (SELECT COUNT(*) FROM monthly_campaigns WHERE campaign_status = 'OPEN' AND campaign_month >= :month) AS campaigns,
            (SELECT COUNT(*) FROM e_waste_requests WHERE request_status = 'PENDING_REVIEW' AND risk_review_status = 'PENDING') AS reviews,
            (SELECT COUNT(*) FROM area_collection_schedules s
                WHERE s.schedule_status = 'CLOSED'
                AND NOT EXISTS (SELECT 1 FROM schedule_assignments a WHERE a.schedule_id = s.schedule_id AND a.unassigned_at IS NULL)
                AND EXISTS (SELECT 1 FROM e_waste_requests r WHERE r.schedule_id = s.schedule_id AND r.request_status IN ('SUBMITTED','APPROVED'))
                AND NOT EXISTS (SELECT 1 FROM e_waste_requests r WHERE r.schedule_id = s.schedule_id AND r.request_status = 'PENDING_REVIEW')) AS assignments,
            (SELECT COUNT(*) FROM schedule_collections WHERE verification_status = 'PENDING') AS verifications,
            (SELECT COUNT(*) FROM e_lots WHERE lot_status = 'OPEN_FOR_BIDDING' AND bidding_open_at <= :now_open AND bidding_close_at >= :now_close) AS lots",
            ['month' => $now->format('Y-m-01'), 'now_open' => $now->format('Y-m-d H:i:s'), 'now_close' => $now->format('Y-m-d H:i:s')])->fetch();
        $schedules = $this->query("SELECT s.*, c.campaign_name, a.postal_code, a.area_name,
            (SELECT COUNT(*) FROM e_waste_requests r WHERE r.schedule_id = s.schedule_id AND r.request_status NOT IN ('CANCELLED','REJECTED')) AS request_count
            FROM area_collection_schedules s
            JOIN monthly_campaigns c ON c.campaign_id = s.campaign_id
            JOIN postal_code_areas a ON a.postal_area_id = s.postal_area_id
            WHERE s.collection_date >= :today AND s.schedule_status IN ('PLANNED','OPEN','CLOSED','ASSIGNED','IN_PROGRESS')
            ORDER BY s.collection_date, s.schedule_id LIMIT 10", ['today' => $now->format('Y-m-d')])->fetchAll();
        return ['stats' => $stats, 'schedules' => $schedules];
    }
}
