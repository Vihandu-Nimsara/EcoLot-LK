<?php
declare(strict_types=1);

final class EWasteRequest extends Model
{
    private const EDITABLE_STATUSES = ['SUBMITTED', 'PENDING_REVIEW'];
    protected string $table = 'e_waste_requests';
    protected string $primaryKey = 'request_id';

        /**
     * Creates a pickup request and its item rows in one transaction.
     */
    public function createWithItems(array $request, array $items): int
    {
        if ($items === []) {
            throw new InvalidArgumentException('A pickup request needs at least one item.');
        }

        $this->db->beginTransaction();

        try {
            $requestId = $this->create($request);
            $this->insertItems($requestId, $items);
            $this->db->commit();

            return $requestId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }
            /**
     * All pickup requests submitted by a public user, newest first, each
     * with its collection schedule/address details and its item rows.
     *
     * @return array<int, array>
     */
    public function forPublicUser(int $publicUserId, ?int $limit = null): array
    {
        $sql = 'SELECT
                    r.`request_id`,
                    r.`schedule_id`,
                    r.`pickup_address`,
                    r.`request_status`,
                    r.`submitted_at`,
                    s.`collection_date`,
                    pa.`postal_code`,
                    pa.`area_name`
                FROM `e_waste_requests` r
                JOIN `area_collection_schedules` s ON s.`schedule_id` = r.`schedule_id`
                JOIN `postal_code_areas` pa ON pa.`postal_area_id` = s.`postal_area_id`
                WHERE r.`public_user_id` = :public_user_id
                ORDER BY r.`submitted_at` DESC';

        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, $limit);
        }

        $requests = $this->query($sql, ['public_user_id' => $publicUserId])->fetchAll();

        if ($requests === []) {
            return [];
        }

        $requestIds = array_map(static fn (array $r): int => (int) $r['request_id'], $requests);
        $placeholders = implode(', ', array_fill(0, count($requestIds), '?'));

        $itemsStatement = $this->db->prepare(
            "SELECT
                ri.`request_id`,
                ri.`quantity`,
                ri.`estimated_weight_kg`,
                ri.`item_condition`,
                ri.`condition_note`,
                wc.`category_name`,
                wi.`item_name`
             FROM `request_items` ri
             JOIN `e_waste_items` wi ON wi.`waste_item_id` = ri.`waste_item_id`
             JOIN `waste_categories` wc ON wc.`category_id` = wi.`category_id`
             WHERE ri.`request_id` IN ({$placeholders})
             ORDER BY ri.`request_item_id`"
        );
        $itemsStatement->execute($requestIds);

        $itemsByRequest = [];
        foreach ($itemsStatement->fetchAll() as $item) {
            $itemsByRequest[(int) $item['request_id']][] = $item;
        }

        foreach ($requests as &$request) {
            $request['items'] = $itemsByRequest[(int) $request['request_id']] ?? [];
        }
        unset($request);

        return $requests;
    }

    /**
     * Dashboard summary counters for a public user: total requests,
     * completed pickups, requests still pending review, and total
     * recycled weight (kg) across completed pickups.
     *
     * @return array{total_requests: int, completed_requests: int, pending_requests: int, recycled_weight_kg: float}
     */
    public function summaryForPublicUser(int $publicUserId): array
    {
        $counts = $this->query(
            "SELECT
                COUNT(*) AS total_requests,
                SUM(r.`request_status` = 'COMPLETED') AS completed_requests,
                SUM(r.`request_status` IN ('SUBMITTED', 'PENDING_REVIEW', 'APPROVED')) AS pending_requests
             FROM `e_waste_requests` r
             WHERE r.`public_user_id` = :public_user_id",
            ['public_user_id' => $publicUserId]
        )->fetch();

        $weight = $this->query(
            "SELECT COALESCE(SUM(ri.`estimated_weight_kg`), 0) AS recycled_weight_kg
             FROM `request_items` ri
             JOIN `e_waste_requests` r ON r.`request_id` = ri.`request_id`
             WHERE r.`public_user_id` = :public_user_id
               AND r.`request_status` = 'COMPLETED'",
            ['public_user_id' => $publicUserId]
        )->fetch();

        return [
            'total_requests' => (int) ($counts['total_requests'] ?? 0),
            'completed_requests' => (int) ($counts['completed_requests'] ?? 0),
            'pending_requests' => (int) ($counts['pending_requests'] ?? 0),
            'recycled_weight_kg' => (float) ($weight['recycled_weight_kg'] ?? 0),
        ];
    }

    public function findEditableForOwner(int $requestId, int $publicUserId): ?array
{
    $result = $this->query(
        'SELECT r.`request_id`, r.`schedule_id`, r.`request_status`, s.`postal_area_id`
         FROM `e_waste_requests` r
         JOIN `area_collection_schedules` s ON s.`schedule_id` = r.`schedule_id`
         WHERE r.`request_id` = :request_id
           AND r.`public_user_id` = :public_user_id
         LIMIT 1',
        ['request_id' => $requestId, 'public_user_id' => $publicUserId]
    )->fetch();

    if ($result === false || !in_array($result['request_status'], self::EDITABLE_STATUSES, true)) {
        return null;
    }

    return $result;
}

public function updateScheduleAndItems(int $requestId, int $scheduleId, array $items): void
{
    if ($items === []) {
        throw new InvalidArgumentException('A pickup request needs at least one item.');
    }

    $this->db->beginTransaction();

    try {
        $this->update($requestId, ['schedule_id' => $scheduleId]);

        $this->db->prepare('DELETE FROM `request_items` WHERE `request_id` = :request_id')
            ->execute(['request_id' => $requestId]);

        $this->insertItems($requestId, $items);

        $this->db->commit();
    } catch (Throwable $exception) {
        $this->db->rollBack();
        throw $exception;
    }
}

public function deleteOwned(int $requestId, int $publicUserId): bool
{
    if ($this->findEditableForOwner($requestId, $publicUserId) === null) {
        return false;
    }

    return $this->delete($requestId);
}

private function insertItems(int $requestId, array $items): void
{
    $itemStatement = $this->db->prepare(
        'INSERT INTO `request_items`
            (`request_id`, `waste_item_id`, `quantity`, `estimated_weight_kg`,
             `item_condition`, `condition_note`, `applied_risk_level`)
         VALUES
            (:request_id, :waste_item_id, :quantity, :estimated_weight_kg,
             :item_condition, :condition_note, :applied_risk_level)'
    );

    foreach ($items as $item) {
        $itemStatement->execute([
            'request_id' => $requestId,
            'waste_item_id' => $item['waste_item_id'],
            'quantity' => $item['quantity'],
            'estimated_weight_kg' => $item['estimated_weight_kg'],
            'item_condition' => $item['item_condition'],
            'condition_note' => $item['condition_note'],
            'applied_risk_level' => $item['applied_risk_level'],
        ]);
    }
}

    }

