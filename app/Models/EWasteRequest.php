<?php
declare(strict_types=1);

final class EWasteRequest extends Model
{
    private const EDITABLE_STATUSES = ['SUBMITTED', 'PENDING_REVIEW'];
    protected string $table = 'e_waste_requests';
    protected string $primaryKey = 'request_id';

    private function beginMutation(): void
    {
        // Catalogue/profile reads must not freeze capacity before the schedule lock.
        $this->db->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
        $this->db->beginTransaction();
    }

    private function mutate(callable $operation): mixed
    {
        $this->beginMutation();
        try {
            $result = $operation();
            $this->db->commit();
            return $result;
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    /** All inserts share the schedule lock with officer edits. */
    public function create(array $attributes): int
    {
        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) {
            $this->beginMutation();
        }
        try {
            $schedules = new AreaCollectionSchedule($this->db);
            $schedules->lockSchedule((int) $attributes['schedule_id']);
            $profile = (new PublicProfile($this->db))->find((int) $attributes['public_user_id']);
            if (!$profile || !$schedules->isBookable((int) $attributes['schedule_id'], (int) $profile['postal_area_id'])) {
                throw new DomainException('This collection date is no longer available.');
            }
            $id = parent::create($attributes);
            if ($ownsTransaction) {
                $this->db->commit();
            }
            return $id;
        } catch (Throwable $error) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }

    public function createWithItems(array $request, array $rawItems): int
    {
        return $this->mutate(function () use ($request, $rawItems): int {
            $items = (new EWasteItem($this->db))->validatePickupItems($rawItems);
            $profile = (new PublicProfile($this->db))->find((int) $request['public_user_id']);
            if (!$profile || trim((string) $profile['address']) === '') {
                throw new DomainException('Complete your address profile before requesting pickup.');
            }
            $requestId = $this->create([
                'public_user_id' => (int) $request['public_user_id'],
                'schedule_id' => (int) $request['schedule_id'],
                'pickup_address' => $profile['address'],
            ] + $this->reviewState($items));
            $this->insertItems($requestId, $items);
            return $requestId;
        });
    }

    private function reviewState(array $items): array
    {
        $review = in_array(true, array_column($items, 'requires_review'), true);
        return [
            'request_status' => $review ? 'PENDING_REVIEW' : 'SUBMITTED',
            'risk_review_status' => $review ? 'PENDING' : 'NOT_REQUIRED',
            'reviewed_by_officer_user_id' => null,
            'reviewed_at' => null,
            'review_note' => null,
        ];
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
                    s.`collection_date`, s.schedule_status, s.request_cutoff_at,
                    EXISTS(SELECT 1 FROM schedule_assignments sa WHERE sa.schedule_id = s.schedule_id AND sa.unassigned_at IS NULL) AS has_assignment,
                    EXISTS(SELECT 1 FROM schedule_collections sc WHERE sc.schedule_id = s.schedule_id) AS has_collection,
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
     * estimated weight (kg) across completed pickups.
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

    public static function canModify(array $request): bool
    {
        return in_array($request['request_status'], self::EDITABLE_STATUSES, true)
            && $request['schedule_status'] === 'OPEN'
            && $request['request_cutoff_at'] >= (new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format('Y-m-d H:i:s')
            && empty($request['has_assignment']) && empty($request['has_collection']);
    }

    private function lockOwned(int $requestId, int $ownerId, ?int $targetSchedule = null): array
    {
        $snapshot = $this->find($requestId);
        if (!$snapshot || (int) $snapshot['public_user_id'] !== $ownerId) {
            throw new DomainException('Request not found.');
        }
        $ids = array_unique([(int) $snapshot['schedule_id'], $targetSchedule ?? (int) $snapshot['schedule_id']]);
        sort($ids, SORT_NUMERIC);
        $schedules = new AreaCollectionSchedule($this->db);
        foreach ($ids as $id) {
            $schedules->lockSchedule($id);
        }
        $request = $this->query('SELECT * FROM e_waste_requests WHERE request_id = :id FOR UPDATE', ['id' => $requestId])->fetch();
        if (!$request || (int) $request['public_user_id'] !== $ownerId || $request['schedule_id'] != $snapshot['schedule_id']) {
            throw new DomainException('This request changed. Reload and try again.');
        }
        $schedule = $schedules->find((int) $request['schedule_id']);
        if (!$schedule || !self::canModify($request + $schedule) || $schedules->hasStartedWork((int) $request['schedule_id'])) {
            throw new DomainException('Only pending requests on an open schedule before its deadline and assignment can be changed.');
        }
        return $request;
    }

    public function updateScheduleAndItems(int $requestId, int $scheduleId, array $rawItems, int $ownerId): void
    {
        $this->mutate(function () use ($requestId, $scheduleId, $rawItems, $ownerId): void {
            $this->lockOwned($requestId, $ownerId, $scheduleId);
            $profile = (new PublicProfile($this->db))->find($ownerId);
            if (!$profile || !(new AreaCollectionSchedule($this->db))->isBookable($scheduleId, (int) $profile['postal_area_id'], $requestId)) {
                throw new DomainException('That collection date is no longer available.');
            }
            $items = (new EWasteItem($this->db))->validatePickupItems($rawItems);
            $this->update($requestId, ['schedule_id' => $scheduleId] + $this->reviewState($items));
            $this->query('DELETE FROM request_items WHERE request_id = :id', ['id' => $requestId]);
            $this->insertItems($requestId, $items);
        });
    }

    public function cancelOwned(int $requestId, int $ownerId): void
    {
        $this->mutate(function () use ($requestId, $ownerId): void {
            $this->lockOwned($requestId, $ownerId);
            $this->update($requestId, ['request_status' => 'CANCELLED']);
        });
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
