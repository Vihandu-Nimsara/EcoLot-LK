<?php
declare(strict_types=1);

/** Post-registration review, independent of RecyclerCapabilityReviewService. */
final class RecyclerCapabilityChangeReviewService extends RecyclerCapabilityChangeService
{
    public function review(int $owner, int $requestId, int $adminId, string $decision, string $note = ''): void
    {
        if (Auth::role() !== 'ADMIN' || (int) Auth::id() !== $adminId || $adminId <= 0 || $owner <= 0 || $requestId <= 0) throw new DomainException('An authorized Administrator and valid request are required.');
        $note = trim($note);
        if (!in_array($decision, ['APPROVE', 'REJECT'], true)) throw new DomainException('Select Approve or Reject.');
        if (!mb_check_encoding($note, 'UTF-8') || mb_strlen($note, 'UTF-8') > 500 || ($decision === 'REJECT' && mb_strlen($note, 'UTF-8') < 3)) throw new DomainException('Rejection requires a meaningful reason of 3 to 500 characters.');
        $this->requireOwnTransaction();
        $this->db->beginTransaction();
        try {
            // Locking read avoids an early repeatable-read snapshot before owner locking.
            $admin = $this->db->prepare("SELECT a.user_id FROM administrators a JOIN users u ON u.user_id = a.user_id
                WHERE a.user_id = ? AND u.role = 'ADMIN' AND u.account_status = 'ACTIVE' LOCK IN SHARE MODE");
            $admin->execute([$adminId]);
            if (!$admin->fetchColumn()) throw new DomainException('This Administrator account is not active.');
            $ownerState = $this->lockOwner($owner);
            $requests = new RecyclerCapabilityChangeRequest($this->db);
            $request = $requests->lockForReview($owner, $requestId);
            if ($request === null) throw new DomainException('Capability request not found for this Recycler.');
            if ($request['request_status'] !== 'PENDING') throw new DomainException('This request has already been reviewed.');
            if ($decision === 'APPROVE') {
                $this->requireEligibleOwner($ownerState);
                $category = (int) $request['category_id'];
                $this->requireCategory($category, $request['request_type']);
                $current = $this->lockCapability($owner, $category);
                if (($current['capability_status'] ?? null) !== $request['previous_capability_status']
                    || ($current === null ? null : (int) $current['can_handle_high_risk']) !== ($request['previous_can_handle_high_risk'] === null ? null : (int) $request['previous_can_handle_high_risk'])) {
                    throw new DomainException('Capability data changed after submission. Reject this stale request and ask for a new one.');
                }
                $risk = $request['requested_can_handle_high_risk'] === null ? null : (int) $request['requested_can_handle_high_risk'];
                $this->validateChange($current, $request['request_type'], $risk);
                if ($request['request_type'] !== 'DEACTIVATE') {
                    (new RecyclerLicenceAuthorization($this->db))->requireCapability($owner, $category, (int) $risk);
                }
                if ($current !== null && $current['capability_status'] === 'APPROVED'
                    && ($request['request_type'] === 'DEACTIVATE' || ((int) $current['can_handle_high_risk'] === 1 && $risk === 0))) {
                    $this->requireNoUnresolvedBids($owner, $category);
                }
                if ($request['request_type'] === 'ADD') {
                    $statement = $this->db->prepare("INSERT INTO recycler_capabilities
                        (recycler_user_id, category_id, capability_status, can_handle_high_risk, reviewed_by_admin_user_id, reviewed_at, review_note)
                        VALUES (?, ?, 'APPROVED', ?, ?, CURRENT_TIMESTAMP, ?)");
                    $statement->execute([$owner, $category, $risk, $adminId, $note === '' ? null : $note]);
                } else {
                    $statement = $this->db->prepare('UPDATE recycler_capabilities SET capability_status = ?, can_handle_high_risk = ?,
                        reviewed_by_admin_user_id = ?, reviewed_at = CURRENT_TIMESTAMP, review_note = ?
                        WHERE recycler_user_id = ? AND category_id = ?');
                    $statement->execute([$request['request_type'] === 'DEACTIVATE' ? 'INACTIVE' : 'APPROVED', $risk ?? (int) $current['can_handle_high_risk'], $adminId, $note === '' ? null : $note, $owner, $category]);
                }
                if ($statement->rowCount() !== 1) throw new RuntimeException('Capability change could not be saved.');
            }
            if (!$requests->recordDecision($owner, $requestId, $decision === 'APPROVE' ? 'APPROVED' : 'REJECTED', $note)) throw new RuntimeException('Review could not be recorded.');
            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    private function requireNoUnresolvedBids(int $owner, int $category): void
    {
        // Owner locking already serializes bid placement. Do not lock lots after
        // the owner: bidding locks lot -> owner, which would invert the order.
        $statement = $this->db->prepare("SELECT b.bid_id FROM recycler_bids b JOIN e_lots l ON l.e_lot_id = b.e_lot_id
            WHERE b.recycler_user_id = ? AND l.category_id = ? AND (
                (b.bid_status = 'SUBMITTED' AND l.lot_status NOT IN ('COMPLETED', 'CANCELLED'))
                OR (b.bid_status = 'WINNING' AND l.lot_status NOT IN ('COMPLETED', 'CANCELLED')
                    AND NOT EXISTS (SELECT 1 FROM handover_records h WHERE h.winning_bid_id = b.bid_id AND h.handover_status = 'COMPLETED')))
            LIMIT 1");
        $statement->execute([$owner, $category]);
        if ($statement->fetchColumn()) throw new DomainException('Cannot reduce handling permission while this category has unresolved bids or unfinished winning handovers. Resolve them first, or reject this request.');
    }
}
