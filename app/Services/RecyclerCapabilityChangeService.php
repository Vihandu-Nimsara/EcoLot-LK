<?php
declare(strict_types=1);

class RecyclerCapabilityChangeService
{
    protected PDO $db;
    public function __construct(?PDO $db = null) { $this->db = $db ?? Database::connection(); }

    protected function requireOwnTransaction(): void
    {
        if ($this->db->inTransaction()) throw new LogicException('Capability changes must own their transaction.');
    }

    protected function lockOwner(int $owner): array
    {
        // Match bidding's users -> authorized_recyclers -> capability order.
        (new User($this->db))->lockForVerification($owner);
        $statement = $this->db->prepare('SELECT r.verification_status, u.role, u.account_status
            FROM authorized_recyclers r JOIN users u ON u.user_id = r.user_id WHERE r.user_id = ? FOR UPDATE');
        $statement->execute([$owner]);
        $row = $statement->fetch();
        if ($row === false || $row['role'] !== 'RECYCLER') throw new DomainException('Recycler not found.');
        return $row;
    }

    protected function requireEligibleOwner(array $row): void
    {
        if ($row['account_status'] !== 'ACTIVE' || $row['verification_status'] !== 'VERIFIED') {
            throw new DomainException('Capability changes require an active, verified Recycler account.');
        }
    }

    protected function lockCapability(int $owner, int $category): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM recycler_capabilities WHERE recycler_user_id = ? AND category_id = ? FOR UPDATE');
        $statement->execute([$owner, $category]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    protected function requireCategory(int $category, string $type): void
    {
        $statement = $this->db->prepare('SELECT category_id, category_status FROM waste_categories WHERE category_id = ? FOR UPDATE');
        $statement->execute([$category]);
        $row = $statement->fetch();
        if ($row === false) throw new DomainException('Select a valid waste category.');
        // Deactivation may remove permission even when a category is no longer active.
        if ($type !== 'DEACTIVATE' && ($row['category_status'] !== 'ACTIVE'
            || (new WasteCategory($this->db))->selectableIds([$category]) === [])) {
            throw new DomainException('Select an active Recycler waste category.');
        }
    }

    protected function validateChange(?array $capability, string $type, ?int $risk): void
    {
        if (!in_array($type, ['ADD', 'UPDATE', 'DEACTIVATE'], true)) throw new DomainException('Select a valid request type.');
        if ($type === 'DEACTIVATE') {
            if ($risk !== null || ($capability['capability_status'] ?? '') !== 'APPROVED') throw new DomainException('Only approved capabilities can be deactivated.');
        } elseif ($risk !== 0 && $risk !== 1) {
            throw new DomainException('Select a valid high-risk permission.');
        } elseif ($type === 'ADD') {
            if ($capability !== null) throw new DomainException('This category already has a capability record.');
        } else {
            if ($capability === null || !in_array($capability['capability_status'], ['APPROVED', 'INACTIVE'], true)) {
                throw new DomainException('Only approved or voluntarily inactive capabilities can be updated.');
            }
            if ($capability['capability_status'] === 'APPROVED' && (int) $capability['can_handle_high_risk'] === $risk) {
                throw new DomainException('The requested permission is unchanged.');
            }
        }
    }

    public function submit(int $owner, int $category, string $type, ?int $risk, string $reason): int
    {
        if (Auth::role() !== 'RECYCLER' || (int) Auth::id() !== $owner || $owner <= 0) throw new DomainException('An authenticated Recycler is required.');
        $reason = trim($reason);
        if (!mb_check_encoding($reason, 'UTF-8') || $reason === '' || mb_strlen($reason, 'UTF-8') > 500) throw new DomainException('Provide a request reason of 1 to 500 characters.');
        if ($category <= 0 || !in_array($type, ['ADD', 'UPDATE', 'DEACTIVATE'], true)) throw new DomainException('Select a valid category and request type.');
        $this->requireOwnTransaction();
        $this->db->beginTransaction();
        try {
            $this->requireEligibleOwner($this->lockOwner($owner));
            $requests = new RecyclerCapabilityChangeRequest($this->db);
            if ($requests->pendingForCategory($owner, $category) !== null) throw new DomainException('This category already has a pending request.');
            $this->requireCategory($category, $type);
            $capability = $this->lockCapability($owner, $category);
            $this->validateChange($capability, $type, $risk);
            $id = $requests->createPendingRequest($owner, $category, $type, $capability, $risk, $reason);
            $this->db->commit();
            return $id;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if ($error instanceof PDOException && (int) ($error->errorInfo[1] ?? 0) === 1062) throw new DomainException('This category already has a pending request.');
            throw $error;
        }
    }

    /** The UI and backend use the same category policy and state restrictions. */
    public function options(int $owner): array
    {
        if (Auth::role() !== 'RECYCLER' || (int) Auth::id() !== $owner) throw new DomainException('An authenticated Recycler is required.');
        $statement = $this->db->prepare("SELECT 1 FROM users u JOIN authorized_recyclers r ON r.user_id = u.user_id
            WHERE u.user_id = ? AND u.role = 'RECYCLER' AND u.account_status = 'ACTIVE' AND r.verification_status = 'VERIFIED'");
        $statement->execute([$owner]);
        if (!$statement->fetchColumn()) return [];
        $pending = array_column((new RecyclerCapabilityChangeRequest($this->db))->pendingForRecycler($owner), null, 'category_id');
        $capabilities = array_column((new AuthorizedRecycler($this->db))->capabilitiesForRecycler($owner), null, 'category_id');
        $categories = array_column((new WasteCategory($this->db))->recyclerSelectable(), null, 'category_id');
        foreach ($capabilities as $id => $capability) $categories[$id] ??= ['category_id' => $id, 'category_name' => $capability['category_name']];
        $options = [];
        foreach ($categories as $id => $category) {
            if (isset($pending[$id])) continue;
            $cap = $capabilities[$id] ?? null;
            $active = (new WasteCategory($this->db))->selectableIds([(int) $id]) !== [];
            $actions = $cap === null && $active ? ['ADD'] : [];
            if ($cap !== null && $active && in_array($cap['capability_status'], ['APPROVED', 'INACTIVE'], true)) $actions[] = 'UPDATE';
            if (($cap['capability_status'] ?? '') === 'APPROVED') $actions[] = 'DEACTIVATE';
            if ($actions) $options[] = $category + ['actions' => $actions, 'current_status' => $cap['capability_status'] ?? '', 'current_risk' => $cap === null ? null : (int) $cap['can_handle_high_risk']];
        }
        return $options;
    }
}
