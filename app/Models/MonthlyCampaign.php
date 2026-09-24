<?php
declare(strict_types=1);

final class MonthlyCampaign extends Model
{
    protected string $table = 'monthly_campaigns';
    protected string $primaryKey = 'campaign_id';

    public function openCampaigns(): array
    {
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
