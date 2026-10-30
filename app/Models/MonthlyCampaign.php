<?php
declare(strict_types=1);

final class MonthlyCampaign extends Model
{
    protected string $table = 'monthly_campaigns';
    protected string $primaryKey = 'campaign_id';

    public static function nameForMonth(string $month): string
    {
        if (!preg_match('/^[1-9][0-9]{3}-(0[1-9]|1[0-2])-01$/D', $month)) {
            throw new DomainException('Select a valid campaign month.');
        }
        return (new DateTimeImmutable($month, new DateTimeZone('Asia/Colombo')))->format('F Y') . ' E-Waste Campaign';
    }

    public static function currentMonth(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format('Y-m-01');
    }

    public function closeExpired(): void
    {
        $this->query("UPDATE monthly_campaigns SET campaign_status = 'CLOSED' WHERE campaign_month < :month AND campaign_status = 'OPEN'", ['month' => self::currentMonth()]);
    }

    public function managementList(): array
    {
        $this->closeExpired();
        return $this->query('SELECT c.*, u.full_name AS officer_name FROM monthly_campaigns c JOIN users u ON u.user_id = c.created_by_officer_user_id ORDER BY campaign_month DESC')->fetchAll();
    }

    public function saveManaged(?int $id, array $data, int $officer): void
    {
        $data['campaign_name'] = self::nameForMonth($data['campaign_month']);
        $this->db->beginTransaction();
        try {
            if ($id !== null) {
                $old = $this->query('SELECT * FROM monthly_campaigns WHERE campaign_id = :id FOR UPDATE', ['id' => $id])->fetch();
                if (!$old) throw new DomainException('Campaign no longer exists.');
                if ($old['campaign_month'] < self::currentMonth() && $data['campaign_month'] !== $old['campaign_month']) throw new DomainException('Historical campaign months cannot change.');
                if ($data['campaign_month'] !== $old['campaign_month'] && $this->query('SELECT 1 FROM area_collection_schedules WHERE campaign_id = :id LIMIT 1', ['id' => $id])->fetchColumn()) throw new DomainException('A campaign with schedules cannot change month.');
            }
            if ($data['campaign_month'] < self::currentMonth() && ($id === null || $data['campaign_status'] !== 'CLOSED')) throw new DomainException('Past campaigns cannot be created or reopened.');
            if ($id === null) $this->create($data + ['created_by_officer_user_id' => $officer]);
            else $this->update($id, $data);
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function openCampaigns(): array
    {
        $this->closeExpired();
        return $this->query(
            'SELECT *
            FROM monthly_campaigns
            WHERE campaign_status = :status
            ORDER BY campaign_month DESC',
            [
                'status' => 'OPEN',
            ]
        )->fetchAll();
    }
}
