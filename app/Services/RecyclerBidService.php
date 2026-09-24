<?php
declare(strict_types=1);

final class RecyclerBidService
{
    private PDO $db;
    private RecyclerBid $bids;
    private ELot $lots;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
        $this->bids = new RecyclerBid($this->db);
        $this->lots = new ELot($this->db);
    }

    // Decimal strings avoid floating-point rounding and fit DECIMAL(14,2).
    public static function amount(string $input): string
    {
        $input = trim($input);
        if (!preg_match('/^([0-9]{1,12})(?:\.([0-9]{1,2}))?$/D', $input, $parts)) {
            throw new DomainException('Enter a positive amount with at most 12 whole digits and two decimal places.');
        }
        $whole = ltrim($parts[1], '0') ?: '0';
        $fraction = str_pad($parts[2] ?? '', 2, '0');
        if (strlen($whole) < 3) throw new DomainException('Bid amount must be at least Rs. 100.00.');
        return $whole . '.' . $fraction;
    }

    // Exact decimal-string addition; null means no representable suggestion.
    public static function suggestedAmount(?string $highest): ?string
    {
        if ($highest === null) return '100.00';
        [$whole, $fraction] = explode('.', $highest . '.00');
        $digits = str_split($whole);
        $carry = 100;
        for ($i = count($digits) - 1; $i >= 0 && $carry > 0; --$i) {
            $sum = (int) $digits[$i] + $carry;
            $digits[$i] = (string) ($sum % 10);
            $carry = intdiv($sum, 10);
        }
        $whole = ($carry > 0 ? (string) $carry : '') . implode('', $digits);
        return strlen($whole) > 12 ? null : $whole . '.' . str_pad($fraction, 2, '0');
    }

    private function requireOpenWindow(array $lot, bool $withdraw = false): void
    {
        // Read database time after acquiring the lot lock, not before waiting for it.
        $now = (string) $this->db->query('SELECT CURRENT_TIMESTAMP')->fetchColumn();
        if ($lot['bidding_close_at'] !== null && $now >= $lot['bidding_close_at']) {
            throw new DomainException('Bidding for this E-Lot has closed.');
        }
        if ($lot['lot_status'] !== 'OPEN_FOR_BIDDING' || $lot['bidding_close_at'] === null) {
            throw new DomainException('This E-Lot is not open for bidding.');
        }
        if (!$withdraw && ($lot['bidding_open_at'] === null || $now < $lot['bidding_open_at'])) {
            throw new DomainException('Bidding for this E-Lot has not opened yet.');
        }
    }

    public function placeBid(int $userId, int $lotId, string $amount): int
    {
        $amount = self::amount($amount);
        return $this->transaction(function () use ($userId, $lotId, $amount): int {
            $lot = $this->lots->lockForBidding($lotId);
            if (!$lot) throw new DomainException('E-Lot not found.', 404);
            $this->requireOpenWindow($lot);
            $this->lockEligibility($userId, $lot);
            if (!$this->lots->isEligible($lotId, $userId)) throw new DomainException('You are not currently eligible to bid on this E-Lot, or its bidding window is closed.');
            if ($this->bids->findForRecyclerAndLot($userId, $lotId)) throw new DomainException('You already have a bid for this E-Lot. Withdrawn bids cannot be replaced.');
            return $this->bids->createSubmittedBid($lotId, $userId, $amount);
        });
    }

    public function updateBid(int $userId, int $bidId, string $amount): int
    {
        $amount = self::amount($amount);
        return $this->changeBid($userId, $bidId, $amount);
    }

    public function withdrawBid(int $userId, int $bidId): int
    {
        return $this->changeBid($userId, $bidId, null);
    }

    private function changeBid(int $userId, int $bidId, ?string $amount): int
    {
        // Resolve ownership before taking locks; re-read under lock inside the transaction.
        $owned = $this->bids->findOwnedBid($bidId, $userId);
        if (!$owned) throw new DomainException('Bid not found.', 404);
        return $this->transaction(function () use ($userId, $bidId, $amount, $owned): int {
            $lotId = (int) $owned['e_lot_id'];
            $lot = $this->lots->lockForBidding($lotId);
            if (!$lot) throw new DomainException('E-Lot not found.', 404);
            $bid = $this->bids->findOwnedBid($bidId, $userId, true);
            if (!$bid || (int) $bid['e_lot_id'] !== $lotId) throw new DomainException('Bid not found.', 404);
            if ($bid['bid_status'] === 'WITHDRAWN') throw new DomainException('This bid has already been withdrawn.');
            if ($bid['bid_status'] !== 'SUBMITTED') throw new DomainException('Only submitted bids can be changed.');
            $this->requireOpenWindow($lot, $amount === null);
            if ($amount === null) {
                $this->bids->withdrawOwnedSubmittedBid($bidId, $userId);
            } else {
                $this->lockEligibility($userId, $lot);
                if (!$this->lots->isEligible($lotId, $userId)) throw new DomainException('You are not currently eligible to revise this bid, or its bidding window is closed.');
                if ($amount === (string) $bid['bid_amount']) throw new DomainException('Enter a different amount to revise your bid.');
                $this->bids->reviseOwnedSubmittedBid($bidId, $userId, $amount);
            }
            return $lotId;
        });
    }

    private function lockEligibility(int $userId, array $lot): void
    {
        // Lock the existing authorization and risk sources against concurrent changes.
        // The lot is always locked first, consistently across all three mutations.
        $queries = [
            ['SELECT user_id FROM users WHERE user_id = ? FOR UPDATE', [$userId]],
            ['SELECT user_id FROM authorized_recyclers WHERE user_id = ? FOR UPDATE', [$userId]],
            ['SELECT license_id FROM recycler_licenses WHERE recycler_user_id = ? FOR UPDATE', [$userId]],
            ['SELECT recycler_user_id FROM recycler_capabilities WHERE recycler_user_id = ? AND category_id = ? FOR UPDATE', [$userId, $lot['category_id']]],
            ['SELECT ri.request_item_id FROM e_lot_items eli JOIN collection_record_items cri ON cri.record_item_id = eli.record_item_id JOIN request_items ri ON ri.request_item_id = cri.request_item_id WHERE eli.e_lot_id = ? FOR UPDATE', [$lot['e_lot_id']]],
        ];
        foreach ($queries as [$sql, $parameters]) {
            $statement = $this->db->prepare($sql);
            $statement->execute($parameters);
            $statement->fetchAll();
        }
    }

    private function transaction(callable $action): int
    {
        $this->db->beginTransaction();
        try {
            $result = $action();
            $this->db->commit();
            return $result;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if ($error instanceof PDOException) {
                $code = (int) ($error->errorInfo[1] ?? 0);
                if ($code === 1062) throw new DomainException('You already have a bid for this E-Lot. Withdrawn bids cannot be replaced.', 0, $error);
                if ($code === 1644) throw new DomainException('The bid could not be saved because the E-Lot or your eligibility changed. Please refresh and try again.', 0, $error);
            }
            throw $error;
        }
    }
}
