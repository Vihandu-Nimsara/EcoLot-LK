<?php
declare(strict_types=1);

final class ELot extends Model
{
    protected string $table = 'e_lots';
    protected string $primaryKey = 'e_lot_id';

    public static function eligibilitySql(): string
    {
        return "l.lot_status = 'OPEN_FOR_BIDDING'
            AND l.bidding_open_at IS NOT NULL AND l.bidding_close_at IS NOT NULL
            AND CURRENT_TIMESTAMP >= l.bidding_open_at AND CURRENT_TIMESTAMP < l.bidding_close_at
            AND EXISTS (SELECT 1 FROM users u JOIN authorized_recyclers r ON r.user_id = u.user_id
                WHERE u.user_id = :eligible_user AND u.role = 'RECYCLER'
                AND u.account_status = 'ACTIVE' AND r.verification_status = 'VERIFIED')
            AND EXISTS (SELECT 1 FROM recycler_licenses lic WHERE lic.recycler_user_id = :license_user
                AND lic.license_status = 'VALID' AND lic.expiry_date >= CURRENT_DATE)
            AND EXISTS (SELECT 1 FROM recycler_capabilities cap WHERE cap.recycler_user_id = :cap_user
                AND cap.category_id = l.category_id AND cap.capability_status = 'APPROVED'
                AND (cap.can_handle_high_risk = 1 OR NOT EXISTS (
                    SELECT 1 FROM e_lot_items eli
                    JOIN collection_record_items cri ON cri.record_item_id = eli.record_item_id
                    JOIN request_items ri ON ri.request_item_id = cri.request_item_id
                    WHERE eli.e_lot_id = l.e_lot_id AND ri.applied_risk_level = 'HIGH')))";
    }

    public static function eligibilityParameters(int $userId): array
    {
        return ['eligible_user' => $userId, 'license_user' => $userId, 'cap_user' => $userId];
    }

    private function recyclerSelect(): string
    {
        return "SELECT l.e_lot_id, l.lot_code, l.title, l.category_id, c.category_name,
            l.lot_status, l.bidding_open_at, l.bidding_close_at,
            (" . self::eligibilitySql() . ") AS can_bid,
            (l.lot_status = 'OPEN_FOR_BIDDING' AND CURRENT_TIMESTAMP < l.bidding_close_at) AS can_withdraw,
            b.bid_id, b.bid_amount, b.bid_status, b.submitted_at,
            (SELECT MAX(a.bid_amount) FROM recycler_bids a WHERE a.e_lot_id = l.e_lot_id AND a.bid_status = 'SUBMITTED') AS highest_amount,
            (SELECT COUNT(*) FROM recycler_bids a WHERE a.e_lot_id = l.e_lot_id AND a.bid_status = 'SUBMITTED') AS active_count
            FROM e_lots l JOIN waste_categories c ON c.category_id = l.category_id
            LEFT JOIN recycler_bids b ON b.e_lot_id = l.e_lot_id AND b.recycler_user_id = :own_user";
    }

    public function eligibleForRecycler(int $userId): array
    {
        return $this->query($this->recyclerSelect() . ' HAVING can_bid = 1 ORDER BY l.bidding_close_at, l.e_lot_id',
            self::eligibilityParameters($userId) + ['own_user' => $userId])->fetchAll();
    }

    public function findVisibleForRecycler(int $lotId, int $userId): ?array
    {
        $row = $this->query($this->recyclerSelect() . ' WHERE l.e_lot_id = :lot HAVING can_bid = 1 OR bid_id IS NOT NULL',
            self::eligibilityParameters($userId) + ['own_user' => $userId, 'lot' => $lotId])->fetch();
        return $row ?: null;
    }

    public function itemDetails(int $lotId): array
    {
        return $this->query('SELECT wi.item_name, cri.actual_quantity, cri.actual_weight_kg,
            cri.actual_condition, ri.applied_risk_level FROM e_lot_items eli
            JOIN collection_record_items cri ON cri.record_item_id = eli.record_item_id
            JOIN request_items ri ON ri.request_item_id = cri.request_item_id
            JOIN e_waste_items wi ON wi.waste_item_id = ri.waste_item_id
            WHERE eli.e_lot_id = :lot ORDER BY cri.record_item_id', ['lot' => $lotId])->fetchAll();
    }

    public function lockForBidding(int $lotId): ?array
    {
        $lot = $this->query('SELECT * FROM e_lots WHERE e_lot_id = :lot FOR UPDATE', ['lot' => $lotId])->fetch();
        return $lot ?: null;
    }

    public function isEligible(int $lotId, int $userId): bool
    {
        return (bool) $this->query('SELECT 1 FROM e_lots l WHERE l.e_lot_id = :lot AND ' . self::eligibilitySql(),
            self::eligibilityParameters($userId) + ['lot' => $lotId])->fetchColumn();
    }
}
