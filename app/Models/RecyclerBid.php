<?php
declare(strict_types=1);

final class RecyclerBid extends Model
{
    protected string $table = 'recycler_bids';
    protected string $primaryKey = 'bid_id';

    public function findOwnedBid(int $bidId, int $recyclerUserId, bool $lock = false): ?array
    {
        return $this->query('SELECT * FROM recycler_bids WHERE bid_id = :bid AND recycler_user_id = :owner' . ($lock ? ' FOR UPDATE' : ''),
            ['bid' => $bidId, 'owner' => $recyclerUserId])->fetch() ?: null;
    }

    public function findForRecyclerAndLot(int $recyclerUserId, int $lotId): ?array
    {
        return $this->query('SELECT * FROM recycler_bids WHERE recycler_user_id = :owner AND e_lot_id = :lot',
            ['owner' => $recyclerUserId, 'lot' => $lotId])->fetch() ?: null;
    }

    public function listForRecycler(int $recyclerUserId): array
    {
        return $this->query("SELECT b.bid_id, b.bid_amount, b.bid_status, b.submitted_at,
            l.e_lot_id, l.lot_code, l.title, l.bidding_close_at, c.category_name,
            (b.bid_status = 'SUBMITTED' AND (" . ELot::eligibilitySql() . ")) AS can_revise,
            (b.bid_status = 'SUBMITTED' AND l.lot_status = 'OPEN_FOR_BIDDING'
                AND CURRENT_TIMESTAMP < l.bidding_close_at) AS can_withdraw,
            (SELECT MAX(a.bid_amount) FROM recycler_bids a WHERE a.e_lot_id = b.e_lot_id AND a.bid_status = 'SUBMITTED') AS highest_amount
            FROM recycler_bids b JOIN e_lots l ON l.e_lot_id = b.e_lot_id
            JOIN waste_categories c ON c.category_id = l.category_id
            WHERE b.recycler_user_id = :owner ORDER BY b.submitted_at DESC, b.bid_id DESC", ELot::eligibilityParameters($recyclerUserId) + ['owner' => $recyclerUserId])->fetchAll();
    }

    public function listWinningForRecycler(int $recyclerUserId): array
    {
        return $this->query("SELECT b.bid_id, b.bid_amount, b.reviewed_at, l.e_lot_id,
            l.lot_code, l.title, l.lot_status, c.category_name, h.handover_status, h.handover_date
            FROM recycler_bids b JOIN e_lots l ON l.e_lot_id = b.e_lot_id
            JOIN waste_categories c ON c.category_id = l.category_id
            LEFT JOIN handover_records h ON h.winning_bid_id = b.bid_id
            WHERE b.recycler_user_id = :owner AND b.bid_status = 'WINNING'
            ORDER BY b.reviewed_at DESC, b.bid_id DESC", ['owner' => $recyclerUserId])->fetchAll();
    }

    public function handoverForOwnedWinningBid(int $bidId, int $recyclerUserId): ?array
    {
        return $this->query("SELECT h.handover_status, h.handover_date, h.remarks FROM handover_records h
            JOIN recycler_bids b ON b.bid_id = h.winning_bid_id
            WHERE b.bid_id = :bid AND b.recycler_user_id = :owner AND b.bid_status = 'WINNING'",
            ['bid' => $bidId, 'owner' => $recyclerUserId])->fetch() ?: null;
    }

    public function highestSubmittedForLot(int $lotId): ?string
    {
        $value = $this->query("SELECT MAX(bid_amount) FROM recycler_bids WHERE e_lot_id = :lot AND bid_status = 'SUBMITTED'", ['lot' => $lotId])->fetchColumn();
        return $value === null || $value === false ? null : (string) $value;
    }

    public function submittedCountForLot(int $lotId): int
    {
        return (int) $this->query("SELECT COUNT(*) FROM recycler_bids WHERE e_lot_id = :lot AND bid_status = 'SUBMITTED'", ['lot' => $lotId])->fetchColumn();
    }

    public function createSubmittedBid(int $lotId, int $userId, string $amount): int
    {
        $statement = $this->query("INSERT INTO recycler_bids (e_lot_id, recycler_user_id, bid_amount, bid_status)
            SELECT l.e_lot_id, :owner, :amount, 'SUBMITTED' FROM e_lots l
            WHERE l.e_lot_id = :lot AND " . ELot::eligibilitySql(),
            ELot::eligibilityParameters($userId) + ['owner' => $userId, 'amount' => $amount, 'lot' => $lotId]);
        if ($statement->rowCount() !== 1) throw new DomainException('This E-Lot is no longer available for bidding.');
        return (int) $this->db->lastInsertId();
    }

    public function reviseOwnedSubmittedBid(int $bidId, int $userId, string $amount): void
    {
        $statement = $this->query("UPDATE recycler_bids b JOIN e_lots l ON l.e_lot_id = b.e_lot_id
            SET b.bid_amount = :amount WHERE b.bid_id = :bid AND b.recycler_user_id = :owner
            AND b.bid_status = 'SUBMITTED' AND b.bid_amount <> :different AND " . ELot::eligibilitySql(),
            ELot::eligibilityParameters($userId) + ['amount' => $amount, 'different' => $amount, 'bid' => $bidId, 'owner' => $userId]);
        if ($statement->rowCount() !== 1) throw new DomainException('Your bid could not be revised. Check that bidding is still open and you are eligible.');
    }

    public function withdrawOwnedSubmittedBid(int $bidId, int $userId): void
    {
        $statement = $this->query("UPDATE recycler_bids b JOIN e_lots l ON l.e_lot_id = b.e_lot_id
            SET b.bid_status = 'WITHDRAWN' WHERE b.bid_id = :bid AND b.recycler_user_id = :owner
            AND b.bid_status = 'SUBMITTED' AND l.lot_status = 'OPEN_FOR_BIDDING'
            AND CURRENT_TIMESTAMP < l.bidding_close_at", ['bid' => $bidId, 'owner' => $userId]);
        if ($statement->rowCount() !== 1) throw new DomainException('Only submitted bids on open E-Lots can be withdrawn before the deadline.');
    }
}
