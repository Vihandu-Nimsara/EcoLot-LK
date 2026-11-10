<?php
declare(strict_types=1);

final class RecyclerCapabilityChangeRequest extends Model
{
    protected string $table = 'recycler_capability_change_requests';
    protected string $primaryKey = 'request_id';

    private function authorize(int $owner, bool $adminOnly = false): void
    {
        if ($owner <= 0 || (Auth::role() !== 'ADMIN'
            && ($adminOnly || Auth::role() !== 'RECYCLER' || (int) Auth::id() !== $owner))) {
            throw new DomainException('You cannot access this capability request.');
        }
    }

    private function requireTransaction(): void
    {
        if (!$this->db->inTransaction()) throw new LogicException('A service-owned transaction is required.');
    }

    public function pendingForCategory(int $owner, int $categoryId): ?array
    {
        $this->authorize($owner);
        $row = $this->query("SELECT * FROM recycler_capability_change_requests
            WHERE pending_recycler_user_id = :owner AND pending_category_id = :category",
            ['owner' => $owner, 'category' => $categoryId])->fetch();
        return $row === false ? null : $row;
    }

    public function pendingForRecycler(int $owner): array
    {
        $this->authorize($owner);
        return $this->query("SELECT * FROM recycler_capability_change_requests
            WHERE recycler_user_id = :owner AND request_status = 'PENDING' ORDER BY request_id", ['owner' => $owner])->fetchAll();
    }

    public function pendingForAdmin(int $owner): array
    {
        $this->authorize($owner, true);
        return $this->pendingForRecycler($owner);
    }

    public function historyForRecycler(int $owner): array
    {
        $this->authorize($owner);
        return $this->query('SELECT q.*, c.category_name FROM recycler_capability_change_requests q
            JOIN waste_categories c ON c.category_id = q.category_id
            WHERE q.recycler_user_id = :owner
            ORDER BY (q.request_status = \'PENDING\') DESC, q.submitted_at DESC, q.request_id DESC LIMIT 100', ['owner' => $owner])->fetchAll();
    }

    public function createPendingRequest(int $owner, int $categoryId, string $type, ?array $previous, ?int $risk, string $reason): int
    {
        $this->authorize($owner);
        if (Auth::role() !== 'RECYCLER') throw new DomainException('Only recyclers can submit capability requests.');
        $this->requireTransaction();
        return parent::create([
            'recycler_user_id' => $owner, 'category_id' => $categoryId, 'request_type' => $type,
            'previous_capability_status' => $previous['capability_status'] ?? null,
            'previous_can_handle_high_risk' => $previous === null ? null : (int) $previous['can_handle_high_risk'],
            'requested_can_handle_high_risk' => $risk, 'request_reason' => $reason,
        ]);
    }

    public function lockForReview(int $owner, int $requestId): ?array
    {
        $this->authorize($owner, true);
        $this->requireTransaction();
        $row = $this->query('SELECT * FROM recycler_capability_change_requests
            WHERE request_id = :request AND recycler_user_id = :owner FOR UPDATE', ['request' => $requestId, 'owner' => $owner])->fetch();
        return $row === false ? null : $row;
    }

    public function recordDecision(int $owner, int $requestId, string $status, string $note): bool
    {
        $this->authorize($owner, true);
        $this->requireTransaction();
        if (!in_array($status, ['APPROVED', 'REJECTED'], true)) throw new InvalidArgumentException('Invalid review status.');
        return $this->query("UPDATE recycler_capability_change_requests
            SET request_status = :status, reviewed_by_admin_user_id = :admin,
                reviewed_at = CURRENT_TIMESTAMP, review_note = :note
            WHERE request_id = :request AND recycler_user_id = :owner AND request_status = 'PENDING'",
            ['status' => $status, 'admin' => (int) Auth::id(), 'note' => $note === '' ? null : $note, 'request' => $requestId, 'owner' => $owner])->rowCount() === 1;
    }

    // The generic Model API would bypass ownership, lifecycle guards or history retention.
    public function all(): array { throw new LogicException('Use scoped request methods.'); }
    public function find(int|string $id): ?array { throw new LogicException('Use scoped request methods.'); }
    public function create(array $attributes): int { throw new LogicException('Use createPendingRequest().'); }
    public function update(int|string $id, array $attributes): bool { throw new LogicException('Use recordDecision().'); }
    public function delete(int|string $id): bool { throw new LogicException('Request history must be retained.'); }
}
