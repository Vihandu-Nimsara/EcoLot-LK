<?php
declare(strict_types=1);

final class CollectionAssignment extends Model
{
    public function listing(): array
    {
        return $this->query("SELECT s.*, c.campaign_name, p.area_name, a.assignment_id, a.collector_user_id, a.vehicle_id, a.assigned_at,
            u.full_name AS collector_name, v.vehicle_number,
            EXISTS(SELECT 1 FROM schedule_collections sc WHERE sc.schedule_id = s.schedule_id) AS has_work,
            (SELECT COUNT(*) FROM e_waste_requests r WHERE r.schedule_id = s.schedule_id AND r.request_status IN ('SUBMITTED','APPROVED')) AS ready_requests,
            (SELECT COUNT(*) FROM e_waste_requests r WHERE r.schedule_id = s.schedule_id AND r.request_status = 'PENDING_REVIEW') AS pending_reviews
            FROM area_collection_schedules s JOIN monthly_campaigns c ON c.campaign_id = s.campaign_id
            JOIN postal_code_areas p ON p.postal_area_id = s.postal_area_id
            LEFT JOIN schedule_assignments a ON a.schedule_id = s.schedule_id AND a.unassigned_at IS NULL
            LEFT JOIN users u ON u.user_id = a.collector_user_id LEFT JOIN vehicles v ON v.vehicle_id = a.vehicle_id
            WHERE s.schedule_status IN ('CLOSED','ASSIGNED','IN_PROGRESS','COLLECTION_SUBMITTED','COMPLETED')
            ORDER BY s.collection_date DESC, s.schedule_id DESC")->fetchAll();
    }

    public function collectors(): array
    {
        return $this->query("SELECT u.user_id, u.full_name FROM users u JOIN collectors c ON c.user_id = u.user_id WHERE u.account_status = 'ACTIVE' AND u.role = 'COLLECTOR' ORDER BY u.full_name")->fetchAll();
    }

    public function vehicles(): array
    {
        return $this->query("SELECT * FROM vehicles WHERE availability_status = 'AVAILABLE' ORDER BY vehicle_number")->fetchAll();
    }

    public function change(int $scheduleId, string $action, ?int $collector, ?int $vehicle, int $officer): void
    {
        if (!in_array($action, ['assign','unassign'], true)) throw new DomainException('Invalid assignment action.');
        $this->db->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
        $this->db->beginTransaction();
        try {
            $model = new AreaCollectionSchedule($this->db);
            $schedule = $model->lockSchedule($scheduleId);
            if (!$schedule || !in_array($schedule['schedule_status'], ['CLOSED','ASSIGNED'], true) || $model->hasCollectionSubmission($scheduleId)) throw new DomainException('Assignment changes require a closed or assigned schedule with no collection work.');
            $active = $this->query('SELECT * FROM schedule_assignments WHERE schedule_id = :id AND unassigned_at IS NULL FOR UPDATE', ['id' => $scheduleId])->fetch();
            if ($action === 'unassign') {
                if (!$active || $schedule['schedule_status'] !== 'ASSIGNED') throw new DomainException('No removable active assignment exists.');
                $this->query('UPDATE schedule_assignments SET unassigned_at = GREATEST(CURRENT_TIMESTAMP, DATE_ADD(assigned_at, INTERVAL 1 SECOND)) WHERE assignment_id = :id', ['id' => $active['assignment_id']]);
                $model->update($scheduleId, ['schedule_status' => 'CLOSED']);
            } else {
                if ($active || $schedule['schedule_status'] !== 'CLOSED') throw new DomainException('Schedule is already assigned. Reload the page.');
                if (!$collector || !$vehicle) throw new DomainException('Select both a collector and vehicle.');
                $user = $this->query("SELECT u.* FROM users u JOIN collectors c ON c.user_id = u.user_id WHERE u.user_id = :id FOR UPDATE", ['id' => $collector])->fetch();
                $truck = $this->query('SELECT * FROM vehicles WHERE vehicle_id = :id FOR UPDATE', ['id' => $vehicle])->fetch();
                if (!$user || $user['account_status'] !== 'ACTIVE' || $user['role'] !== 'COLLECTOR' || !$truck || $truck['availability_status'] !== 'AVAILABLE') throw new DomainException('Collector or vehicle is unavailable.');
                if ($this->query("SELECT 1 FROM e_waste_requests WHERE schedule_id = :id AND request_status = 'PENDING_REVIEW' LIMIT 1", ['id' => $scheduleId])->fetchColumn()) throw new DomainException('Resolve all pending risk reviews before assignment.');
                if (!$this->query("SELECT 1 FROM e_waste_requests WHERE schedule_id = :id AND request_status IN ('SUBMITTED','APPROVED') LIMIT 1", ['id' => $scheduleId])->fetchColumn()) throw new DomainException('No collectable requests remain.');
                $conflict = $this->query("SELECT 1 FROM schedule_assignments a JOIN area_collection_schedules s ON s.schedule_id = a.schedule_id WHERE a.unassigned_at IS NULL AND ((s.collection_date = :date AND (a.collector_user_id = :collector OR a.vehicle_id = :vehicle)) OR (s.schedule_status = 'IN_PROGRESS' AND (a.collector_user_id = :busy_collector OR a.vehicle_id = :busy_vehicle))) LIMIT 1", ['date' => $schedule['collection_date'], 'collector' => $collector, 'vehicle' => $vehicle, 'busy_collector' => $collector, 'busy_vehicle' => $vehicle])->fetchColumn();
                if ($conflict) throw new DomainException('Collector or vehicle has a conflicting assignment or ongoing collection.');
                $this->query('INSERT INTO schedule_assignments (schedule_id,collector_user_id,vehicle_id,assigned_by_officer_user_id) VALUES (:schedule,:collector,:vehicle,:officer)', ['schedule' => $scheduleId, 'collector' => $collector, 'vehicle' => $vehicle, 'officer' => $officer]);
                $model->update($scheduleId, ['schedule_status' => 'ASSIGNED']);
            }
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }
}
