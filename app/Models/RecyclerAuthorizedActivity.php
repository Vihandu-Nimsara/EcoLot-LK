<?php
declare(strict_types=1);

final class RecyclerAuthorizedActivity extends Model
{
    protected string $table = 'recycler_authorized_activities';
    protected string $primaryKey = 'activity_id';

    public function forRecycler(int $recyclerId): array
    {
        return $this->query(
            "SELECT 
                activity_id,
                activity_type,
                activity_status,
                created_at
            FROM recycler_authorized_activities
            WHERE recycler_user_id = :recycler_id
            ORDER BY activity_type",
            ['recycler_id' => $recyclerId]
        )->fetchAll();
    }
}


