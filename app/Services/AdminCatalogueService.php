<?php
declare(strict_types=1);

/** Admin-only catalogue management; no destructive deletes or risk-rule mutations. */
final class AdminCatalogueService
{
    private PDO $db;
    private const EXCLUDED = 'E-Waste We Do Not Collect';
    private const FIXED_NAMES = [
        'Automobile E-Waste', 'Domestic E-Waste', 'Industrial E-Waste',
        'Medical E-Waste', 'Office E-Waste', self::EXCLUDED,
    ];

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    public static function fixedName(string $name): bool
    {
        return in_array($name, self::FIXED_NAMES, true);
    }

    public static function excludedCategory(array $category): bool
    {
        // Collector E-Lot queries also reserve category ID 6. Fail closed on drift.
        return (int)$category['category_id'] === 6 || strcasecmp($category['category_name'], self::EXCLUDED) === 0;
    }

    public function categories(): array
    {
        return $this->db->query('SELECT c.*, (SELECT COUNT(*) FROM e_waste_items i WHERE i.category_id=c.category_id) AS item_count FROM waste_categories c ORDER BY c.category_name')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function items(): array
    {
        return $this->db->query('SELECT i.*, c.category_name, c.category_status, (EXISTS (SELECT 1 FROM request_items ri WHERE ri.waste_item_id=i.waste_item_id) OR EXISTS (SELECT 1 FROM risk_rules r WHERE r.waste_item_id=i.waste_item_id)) AS name_locked FROM e_waste_items i JOIN waste_categories c ON c.category_id=i.category_id ORDER BY c.category_name, i.item_name')->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function id(mixed $value): int
    {
        if ((!is_string($value) && !is_int($value)) || !preg_match('/^[1-9][0-9]*$/D', (string)$value)) {
            throw new DomainException('Invalid catalogue record ID.');
        }
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) throw new DomainException('Invalid catalogue record ID.');
        return $id;
    }

    private static function text(mixed $value, int $max, string $label, bool $optional = false): string
    {
        if (!is_string($value)) throw new DomainException("Invalid $label.");
        $value = trim($value);
        if ((!$optional && $value === '') || preg_match('//u', $value) !== 1
            || preg_match_all('/./us', $value) > $max || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) {
            throw new DomainException("Enter a valid $label (maximum $max characters).");
        }
        return $value;
    }

    private static function choice(mixed $value, array $allowed, string $label): string
    {
        if (!is_string($value) || !in_array($value, $allowed, true)) throw new DomainException("Choose a valid $label.");
        return $value;
    }

    public static function itemValues(array $input): array
    {
        return [
            'category_id' => self::id($input['category_id'] ?? null),
            'item_name' => self::text($input['item_name'] ?? null, 160, 'item name'),
            'collection_status' => self::choice($input['collection_status'] ?? null, ['ACCEPTED', 'REVIEW_REQUIRED', 'DO_NOT_COLLECT'], 'collection policy'),
            'default_risk_level' => self::choice($input['default_risk_level'] ?? null, ['LOW', 'MEDIUM', 'HIGH'], 'risk level'),
            'item_status' => self::choice($input['item_status'] ?? null, ['ACTIVE', 'INACTIVE'], 'item status'),
        ];
    }

    private function query(string $sql, array $params): PDOStatement
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement;
    }

    private function transaction(int $actor, callable $change): int
    {
        $this->db->beginTransaction();
        try {
            if (!$this->query("SELECT user_id FROM users WHERE user_id=? AND role='ADMIN' AND account_status='ACTIVE' LOCK IN SHARE MODE", [$actor])->fetchColumn()) {
                throw new DomainException('An active administrator account is required.');
            }
            $id = $change();
            $this->db->commit();
            return $id;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if ($error instanceof PDOException && (int)($error->errorInfo[1] ?? 0) === 1062) {
                throw new DomainException('A record with this name already exists in the catalogue.');
            }
            throw $error;
        }
    }

    private function audit(int $actor, string $action, string $table, int $id, ?array $before, array $after): void
    {
        $this->query('INSERT INTO audit_logs (actor_user_id, action_type, entity_type, entity_id, details) VALUES (?,?,?,?,?)',
            [$actor, $action, $table, $id, json_encode(['before' => $before, 'after' => $after], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)]);
    }

    public function saveItem(int $actor, ?int $id, array $input): int
    {
        $values = self::itemValues($input);
        return $this->transaction($actor, function () use ($actor, $id, $values): int {
            $category = $this->query('SELECT * FROM waste_categories WHERE category_id=? FOR UPDATE', [$values['category_id']])->fetch(PDO::FETCH_ASSOC);
            if (!$category) throw new DomainException('Category not found.');
            if ($id === null && ($category['category_status'] !== 'ACTIVE' || !self::fixedName($category['category_name']))) throw new DomainException('Choose an active standard category for a new item.');
            if (self::excludedCategory($category) && $values['collection_status'] !== 'DO_NOT_COLLECT') {
                throw new DomainException('Items in the excluded category must remain Do Not Collect.');
            }
            $model = new EWasteItem($this->db);
            $before = null;
            if ($id === null) {
                $id = $model->create($values);
            } else {
                $before = $this->query('SELECT * FROM e_waste_items WHERE waste_item_id=? FOR UPDATE', [$id])->fetch(PDO::FETCH_ASSOC);
                if (!$before) throw new DomainException('Item not found.');
                if ((int)$before['category_id'] !== $values['category_id']) throw new DomainException('Existing items cannot be moved between categories.');
                if ($before['item_name'] !== $values['item_name'] && (
                    $this->query('SELECT 1 FROM request_items WHERE waste_item_id=? LIMIT 1', [$id])->fetchColumn()
                    || $this->query('SELECT 1 FROM risk_rules WHERE waste_item_id=? LIMIT 1', [$id])->fetchColumn()
                )) {
                    throw new DomainException('An item linked to pickup records or risk rules cannot be renamed. Create a distinct item if needed.');
                }
                $model->update($id, $values);
            }
            $this->audit($actor, $before === null ? 'CATALOGUE_ITEM_CREATED' : 'CATALOGUE_ITEM_UPDATED', 'e_waste_items', $id, $before, $values);
            return $id;
        });
    }
}
