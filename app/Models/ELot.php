<?php
declare(strict_types=1);

final class ELot extends Model
{
    protected string $table = 'e_lots';
    protected string $primaryKey = 'e_lot_id';

    // =========================================================
    // RECYCLER FUNCTIONS — PRESERVED
    // =========================================================

    public static function eligibilitySql(): string
    {
        return "l.lot_status = 'OPEN_FOR_BIDDING'
            AND l.bidding_open_at IS NOT NULL
            AND l.bidding_close_at IS NOT NULL
            AND CURRENT_TIMESTAMP >= l.bidding_open_at
            AND CURRENT_TIMESTAMP < l.bidding_close_at
            AND EXISTS (
                SELECT 1
                FROM users u
                JOIN authorized_recyclers r
                    ON r.user_id = u.user_id
                WHERE u.user_id = :eligible_user
                AND u.role = 'RECYCLER'
                AND u.account_status = 'ACTIVE'
                AND r.verification_status = 'VERIFIED'
            )
            AND EXISTS (
                SELECT 1
                FROM recycler_licenses lic
                WHERE lic.recycler_user_id = :license_user
                AND lic.license_status = 'VALID'
                AND lic.expiry_date >= CURRENT_DATE
            )
            AND EXISTS (
                SELECT 1
                FROM recycler_capabilities cap
                WHERE cap.recycler_user_id = :cap_user
                AND cap.category_id = l.category_id
                AND cap.capability_status = 'APPROVED'
                AND (
                    cap.can_handle_high_risk = 1
                    OR NOT EXISTS (
                        SELECT 1
                        FROM e_lot_items eli
                        JOIN collection_record_items cri
                            ON cri.record_item_id = eli.record_item_id
                        JOIN request_items ri
                            ON ri.request_item_id = cri.request_item_id
                        WHERE eli.e_lot_id = l.e_lot_id
                        AND ri.applied_risk_level = 'HIGH'
                    )
                )
            )";
    }

    public static function eligibilityParameters(int $userId): array
    {
        return [
            'eligible_user' => $userId,
            'license_user' => $userId,
            'cap_user' => $userId
        ];
    }

    private function recyclerSelect(): string
    {
        return "SELECT
            l.e_lot_id,
            l.lot_code,
            l.title,
            l.category_id,
            c.category_name,
            l.lot_status,
            l.bidding_open_at,
            l.bidding_close_at,
            (" . self::eligibilitySql() . ") AS can_bid,
            (
                l.lot_status = 'OPEN_FOR_BIDDING'
                AND CURRENT_TIMESTAMP < l.bidding_close_at
            ) AS can_withdraw,
            b.bid_id,
            b.bid_amount,
            b.bid_status,
            b.submitted_at,
            (
                SELECT MAX(a.bid_amount)
                FROM recycler_bids a
                WHERE a.e_lot_id = l.e_lot_id
                AND a.bid_status = 'SUBMITTED'
            ) AS highest_amount,
            (
                SELECT COUNT(*)
                FROM recycler_bids a
                WHERE a.e_lot_id = l.e_lot_id
                AND a.bid_status = 'SUBMITTED'
            ) AS active_count
            FROM e_lots l
            JOIN waste_categories c
                ON c.category_id = l.category_id
            LEFT JOIN recycler_bids b
                ON b.e_lot_id = l.e_lot_id
                AND b.recycler_user_id = :own_user";
    }

    public function eligibleForRecycler(int $userId): array
    {
        return $this->query(
            $this->recyclerSelect()
            . ' HAVING can_bid = 1 ORDER BY l.bidding_close_at, l.e_lot_id',
            self::eligibilityParameters($userId)
                + ['own_user' => $userId]
        )->fetchAll();
    }

    public function findVisibleForRecycler(
        int $lotId,
        int $userId
    ): ?array {
        $row = $this->query(
            $this->recyclerSelect()
            . ' WHERE l.e_lot_id = :lot'
            . ' HAVING can_bid = 1 OR bid_id IS NOT NULL',
            self::eligibilityParameters($userId)
                + [
                    'own_user' => $userId,
                    'lot' => $lotId
                ]
        )->fetch();

        return $row ?: null;
    }

    public function itemDetails(int $lotId): array
    {
        return $this->query(
            "SELECT
                wi.item_name,
                cri.actual_quantity,
                cri.actual_weight_kg,
                cri.actual_condition,
                ri.applied_risk_level
            FROM e_lot_items eli
            JOIN collection_record_items cri
                ON cri.record_item_id = eli.record_item_id
            JOIN request_items ri
                ON ri.request_item_id = cri.request_item_id
            JOIN e_waste_items wi
                ON wi.waste_item_id = ri.waste_item_id
            WHERE eli.e_lot_id = :lot
            ORDER BY cri.record_item_id",
            ['lot' => $lotId]
        )->fetchAll();
    }

    public function lockForBidding(int $lotId): ?array
    {
        $lot = $this->query(
            'SELECT * FROM e_lots WHERE e_lot_id = :lot FOR UPDATE',
            ['lot' => $lotId]
        )->fetch();

        return $lot ?: null;
    }

    public function isEligible(int $lotId, int $userId): bool
    {
        return (bool) $this->query(
            'SELECT 1 FROM e_lots l WHERE l.e_lot_id = :lot AND '
                . self::eligibilitySql(),
            self::eligibilityParameters($userId)
                + ['lot' => $lotId]
        )->fetchColumn();
    }

    // =========================================================
    // COLLECTOR — MY E-LOTS
    // =========================================================

    public function getCollectorLots(int $collectorId): array
    {
        return $this->query(
            "SELECT
                l.e_lot_id,
                l.lot_code,
                l.title,
                l.category_id,
                c.category_name,
                l.lot_status,
                l.created_at,
                l.verified_at,
                l.verification_note,
                (
                    SELECT COUNT(*)
                    FROM e_lot_items eli
                    WHERE eli.e_lot_id = l.e_lot_id
                ) AS item_count,
                (
                    SELECT COALESCE(
                        SUM(cri.actual_weight_kg), 0
                    )
                    FROM e_lot_items eli
                    JOIN collection_record_items cri
                        ON cri.record_item_id = eli.record_item_id
                    WHERE eli.e_lot_id = l.e_lot_id
                ) AS total_weight_kg
            FROM e_lots l
            JOIN waste_categories c
                ON c.category_id = l.category_id
            WHERE l.created_by_collector_user_id = :collector
            ORDER BY l.created_at DESC, l.e_lot_id DESC",
            ['collector' => $collectorId]
        )->fetchAll();
    }

    // =========================================================
    // VERIFIED ITEMS POOL
    // =========================================================

    public function getVerifiedPool(int $collectorId): array
    {
        return $this->query(
            "SELECT
                cri.record_item_id,
                wi.item_name,
                wc.category_id,
                wc.category_name,
                cri.actual_quantity,
                cri.actual_weight_kg,
                sc.verified_at,

                (
                    SELECT eli.e_lot_id
                    FROM e_lot_items eli
                    JOIN e_lots el
                        ON el.e_lot_id = eli.e_lot_id
                    WHERE eli.record_item_id = cri.record_item_id
                    AND el.lot_status <> 'CANCELLED'
                    LIMIT 1
                ) AS allocated_lot_id,

                (
                    SELECT el.lot_code
                    FROM e_lot_items eli
                    JOIN e_lots el
                        ON el.e_lot_id = eli.e_lot_id
                    WHERE eli.record_item_id = cri.record_item_id
                    AND el.lot_status <> 'CANCELLED'
                    LIMIT 1
                ) AS allocated_lot_code,

                CASE WHEN EXISTS (
                    SELECT 1
                    FROM e_lot_items eli
                    JOIN e_lots el
                        ON el.e_lot_id = eli.e_lot_id
                    WHERE eli.record_item_id = cri.record_item_id
                    AND el.lot_status <> 'CANCELLED'
                ) THEN 1 ELSE 0 END AS is_allocated

            FROM collection_record_items cri

            JOIN collection_records cr
                ON cr.collection_record_id = cri.collection_record_id

            JOIN schedule_collections sc
                ON sc.schedule_collection_id = cr.schedule_collection_id

            JOIN request_items ri
                ON ri.request_item_id = cri.request_item_id

            JOIN e_waste_items wi
                ON wi.waste_item_id = ri.waste_item_id

            JOIN waste_categories wc
                ON wc.category_id = wi.category_id

            WHERE sc.submitted_by_collector_user_id = :collector
            AND sc.verification_status = 'VERIFIED'
            AND cri.item_result IN ('COLLECTED', 'PARTIAL')

            ORDER BY wc.category_name, cri.record_item_id",
            ['collector' => $collectorId]
        )->fetchAll();
    }

    // =========================================================
    // CATEGORIES
    // =========================================================

    public function getCategories(): array
    {
        return $this->query(
            "SELECT category_id, category_name
            FROM waste_categories
            WHERE category_id <> 6
            ORDER BY category_name ASC"
        )->fetchAll();
    }

    // =========================================================
    // AVAILABLE ITEMS BY CATEGORY
    // =========================================================

    public function getAvailableItemsForCategory(
        int $collectorId,
        int $categoryId,
        ?int $excludeLotId = null
    ): array {
        return $this->query(
            "SELECT
                cri.record_item_id,
                wi.item_name,
                wc.category_id,
                wc.category_name,
                cri.actual_quantity,
                cri.actual_weight_kg,
                sc.verified_at

            FROM collection_record_items cri

            JOIN collection_records cr
                ON cr.collection_record_id = cri.collection_record_id

            JOIN schedule_collections sc
                ON sc.schedule_collection_id = cr.schedule_collection_id

            JOIN request_items ri
                ON ri.request_item_id = cri.request_item_id

            JOIN e_waste_items wi
                ON wi.waste_item_id = ri.waste_item_id

            JOIN waste_categories wc
                ON wc.category_id = wi.category_id

            WHERE sc.submitted_by_collector_user_id = :collector
            AND sc.verification_status = 'VERIFIED'
            AND cri.item_result IN ('COLLECTED', 'PARTIAL')
            AND wc.category_id = :category

            AND NOT EXISTS (
                SELECT 1
                FROM e_lot_items eli
                JOIN e_lots el
                    ON el.e_lot_id = eli.e_lot_id
                WHERE eli.record_item_id = cri.record_item_id
                AND el.lot_status <> 'CANCELLED'
                AND (
                    :exclude_lot IS NULL
                    OR eli.e_lot_id <> :exclude_lot_check
                )
            )

            ORDER BY cri.record_item_id ASC",
            [
                'collector' => $collectorId,
                'category' => $categoryId,
                'exclude_lot' => $excludeLotId,
                'exclude_lot_check' => $excludeLotId
            ]
        )->fetchAll();
    }

    // =========================================================
    // FIND COLLECTOR E-LOT WITH ITEMS
    // =========================================================

    public function findCollectorLot(
        int $lotId,
        int $collectorId
    ): ?array {
        $lot = $this->query(
            "SELECT l.*, c.category_name
            FROM e_lots l
            JOIN waste_categories c
                ON c.category_id = l.category_id
            WHERE l.e_lot_id = :lot
            AND l.created_by_collector_user_id = :collector",
            [
                'lot' => $lotId,
                'collector' => $collectorId
            ]
        )->fetch();

        if (!$lot) {
            return null;
        }

        $items = $this->query(
            "SELECT
                cri.record_item_id,
                wi.item_name,
                cri.actual_quantity,
                cri.actual_weight_kg
            FROM e_lot_items eli
            JOIN collection_record_items cri
                ON cri.record_item_id = eli.record_item_id
            JOIN request_items ri
                ON ri.request_item_id = cri.request_item_id
            JOIN e_waste_items wi
                ON wi.waste_item_id = ri.waste_item_id
            WHERE eli.e_lot_id = :lot
            ORDER BY cri.record_item_id ASC",
            ['lot' => $lotId]
        )->fetchAll();

        $lot['items'] = $items;

        $lot['item_ids'] = array_map(
            'intval',
            array_column($items, 'record_item_id')
        );

        $lot['total_weight_kg'] = array_sum(
            array_map(
                'floatval',
                array_column($items, 'actual_weight_kg')
            )
        );

        return $lot;
    }

    // =========================================================
    // VALIDATE VERIFIED ITEMS
    // =========================================================

    private function validateCollectorItems(
        int $collectorId,
        int $categoryId,
        array $recordItemIds,
        ?int $editingLotId = null
    ): void {
        if (empty($recordItemIds)) {
            throw new DomainException(
                'Select at least one verified item.'
            );
        }

        $ids = array_map(
            'intval',
            $recordItemIds
        );

        if (
            in_array(0, $ids, true)
            || count($ids) !== count(array_unique($ids))
        ) {
            throw new DomainException(
                'Invalid or duplicate verified items selected.'
            );
        }

        $available = $this->getAvailableItemsForCategory(
            $collectorId,
            $categoryId,
            $editingLotId
        );

        $allowedIds = array_map(
            'intval',
            array_column($available, 'record_item_id')
        );

        foreach ($ids as $itemId) {
            if (!in_array($itemId, $allowedIds, true)) {
                throw new DomainException(
                    'One or more items are not verified, available, '
                    . 'or do not belong to the selected category.'
                );
            }
        }
    }

    // =========================================================
    // NEXT E-LOT ID (EID-001, EID-002, ...)
    // =========================================================

    public function getNextLotCode(): string
    {
        $maxNum = (int) $this->query(
            "SELECT MAX(
                CAST(
                    SUBSTRING_INDEX(lot_code, '-', -1)
                    AS UNSIGNED
                )
            )
            FROM e_lots
            WHERE lot_code LIKE 'EID-%'"
        )->fetchColumn();

        return 'EID-' . sprintf('%03d', $maxNum + 1);
    }

    // =========================================================
    // GENERATE UNIQUE E-LOT CODE
    // =========================================================

    private function generateLotCode(string $categoryName): string
    {
        return $this->getNextLotCode();
    }

    // =========================================================
    // CREATE E-LOT
    // =========================================================

    public function createCollectorLot(
        int $collectorId,
        string $title,
        int $categoryId,
        array $recordItemIds
    ): int {
        $title = trim($title);

        if ($title === '' || strlen($title) > 100) {
            throw new DomainException(
                'Enter an E-Lot name of up to 100 characters.'
            );
        }

        $category = $this->query(
            "SELECT category_id, category_name
            FROM waste_categories
            WHERE category_id = :category
            AND category_id <> 6",
            [
                'category' => $categoryId
            ]
        )->fetch();

        if (!$category) {
            throw new DomainException(
                'Invalid E-Lot category.'
            );
        }

        $this->db->beginTransaction();

        try {
            // Ensure every selected item is verified,
            // belongs to this collector and category,
            // and is not already allocated.

            $this->validateCollectorItems(
                $collectorId,
                $categoryId,
                $recordItemIds
            );

            $lotCode = $this->generateLotCode(
                (string) $category['category_name']
            );

            // Save the E-Lot as a draft.

            $this->query(
                "INSERT INTO e_lots (
                    lot_code,
                    created_by_collector_user_id,
                    category_id,
                    title,
                    lot_status
                )
                VALUES (
                    :lot_code,
                    :collector,
                    :category,
                    :title,
                    'DRAFT'
                )",
                [
                    'lot_code' => $lotCode,
                    'collector' => $collectorId,
                    'category' => $categoryId,
                    'title' => $title
                ]
            );

            $lotId = (int) $this->db->lastInsertId();

            if ($lotId <= 0) {
                throw new RuntimeException(
                    'Unable to retrieve the new E-Lot ID.'
                );
            }

            // Allocate selected items to the new E-Lot.

            foreach ($recordItemIds as $itemId) {
                $this->query(
                    "INSERT INTO e_lot_items (
                        record_item_id,
                        e_lot_id
                    )
                    VALUES (
                        :item,
                        :lot
                    )",
                    [
                        'item' => (int) $itemId,
                        'lot' => $lotId
                    ]
                );
            }

            $this->db->commit();

            return $lotId;

        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $error;
        }
    }

    // =========================================================
    // UPDATE E-LOT
    // =========================================================

    public function updateCollectorLot(
        int $lotId,
        int $collectorId,
        string $title,
        array $recordItemIds
    ): void {
        $existing = $this->findCollectorLot(
            $lotId,
            $collectorId
        );

        if (!$existing) {
            throw new DomainException(
                'E-Lot not found.'
            );
        }

        if (!in_array(
            $existing['lot_status'],
            ['DRAFT', 'REJECTED'],
            true
        )) {
            throw new DomainException(
                'Only Draft or Rejected E-Lots can be edited.'
            );
        }

        $title = trim($title);

        if ($title === '' || strlen($title) > 100) {
            throw new DomainException(
                'Enter an E-Lot name of up to 100 characters.'
            );
        }

        $this->db->beginTransaction();

        try {
            $this->validateCollectorItems(
                $collectorId,
                (int) $existing['category_id'],
                $recordItemIds,
                $lotId
            );

            // Update the draft title.

            $this->query(
                "UPDATE e_lots
                SET title = :title
                WHERE e_lot_id = :lot
                AND created_by_collector_user_id = :collector",
                [
                    'title' => $title,
                    'lot' => $lotId,
                    'collector' => $collectorId
                ]
            );

            // Remove the previous item allocations.

            $this->query(
                "DELETE FROM e_lot_items
                WHERE e_lot_id = :lot",
                [
                    'lot' => $lotId
                ]
            );

            // Save the new selected item allocations.

            foreach ($recordItemIds as $itemId) {
                $this->query(
                    "INSERT INTO e_lot_items (
                        record_item_id,
                        e_lot_id
                    )
                    VALUES (
                        :item,
                        :lot
                    )",
                    [
                        'item' => (int) $itemId,
                        'lot' => $lotId
                    ]
                );
            }

            $this->db->commit();

        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $error;
        }
    }

    // =========================================================
    // DELETE E-LOT
    // =========================================================

    public function deleteCollectorLot(
        int $lotId,
        int $collectorId
    ): void {
        $existing = $this->findCollectorLot(
            $lotId,
            $collectorId
        );

        if (!$existing) {
            throw new DomainException(
                'E-Lot not found.'
            );
        }

        if (!in_array(
            $existing['lot_status'],
            ['DRAFT', 'REJECTED'],
            true
        )) {
            throw new DomainException(
                'Only Draft or Rejected E-Lots can be deleted.'
            );
        }

        $this->db->beginTransaction();

        try {
            // Release all items allocated to this E-Lot.

            $this->query(
                "DELETE FROM e_lot_items
                WHERE e_lot_id = :lot",
                [
                    'lot' => $lotId
                ]
            );

            // Delete the draft itself.

            $this->query(
                "DELETE FROM e_lots
                WHERE e_lot_id = :lot
                AND created_by_collector_user_id = :collector
                AND lot_status IN ('DRAFT', 'REJECTED')",
                [
                    'lot' => $lotId,
                    'collector' => $collectorId
                ]
            );

            $this->db->commit();

        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $error;
        }
    }

    // =========================================================
    // SUBMIT E-LOT FOR MUNICIPAL OFFICER VERIFICATION
    // =========================================================

    public function submitCollectorLot(
        int $lotId,
        int $collectorId
    ): void {
        $existing = $this->findCollectorLot(
            $lotId,
            $collectorId
        );

        if (!$existing) {
            throw new DomainException(
                'E-Lot not found.'
            );
        }

        if (!in_array(
            $existing['lot_status'],
            ['DRAFT', 'REJECTED'],
            true
        )) {
            throw new DomainException(
                'Only Draft or Rejected E-Lots can be submitted.'
            );
        }

        if (empty($existing['items'])) {
            throw new DomainException(
                'Select at least one verified item before submission.'
            );
        }

        $this->query(
            "UPDATE e_lots
            SET
                lot_status = 'PENDING_VERIFICATION',
                submitted_at = CURRENT_TIMESTAMP
            WHERE e_lot_id = :lot
            AND created_by_collector_user_id = :collector
            AND lot_status IN ('DRAFT', 'REJECTED')",
            [
                'lot' => $lotId,
                'collector' => $collectorId
            ]
        );
    }
}