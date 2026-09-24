<?php
declare(strict_types=1);

final class EWasteItem extends Model
{
    protected string $table = 'e_waste_items';
    protected string $primaryKey = 'waste_item_id';

    public function pickupCatalogue(): array
    {
        return $this->query("SELECT i.*, c.category_name FROM e_waste_items i
            JOIN waste_categories c ON c.category_id = i.category_id
            WHERE i.item_status = 'ACTIVE' AND c.category_status = 'ACTIVE'
              AND i.collection_status <> 'DO_NOT_COLLECT'
            ORDER BY c.category_name, i.item_name")->fetchAll();
    }

    public function validatePickupItems(array $rawItems): array
    {
        if ($rawItems === [] || count($rawItems) > 100) {
            throw new DomainException('Add between 1 and 100 e-waste item rows.');
        }
        $items = [];
        foreach ($rawItems as $raw) {
            if (!is_array($raw)) throw new DomainException('Invalid item details.');
            foreach (['category', 'item', 'quantity', 'weight', 'condition', 'note'] as $key) {
                if (isset($raw[$key]) && !is_scalar($raw[$key])) throw new DomainException('Invalid item details.');
            }
            $condition = strtoupper(trim((string) ($raw['condition'] ?? '')));
            $quantity = filter_var($raw['quantity'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 4294967295]]);
            $weight = trim((string) ($raw['weight'] ?? ''));
            $note = trim((string) ($raw['note'] ?? ''));
            if ($quantity === false || !preg_match('/^\d{1,7}(?:\.\d{1,3})?$/D', $weight) || (float) $weight <= 0
                || !in_array($condition, ['WORKING', 'DAMAGED', 'UNKNOWN'], true)
                || mb_strlen($note) > 500) {
                throw new DomainException('Each item needs a positive whole quantity, a positive total weight (up to 3 decimal places), a valid condition and a note of at most 500 characters.');
            }
            $item = $this->query("SELECT i.*, COALESCE(r.risk_level, i.default_risk_level) AS applied_risk
                FROM e_waste_items i JOIN waste_categories c ON c.category_id = i.category_id
                LEFT JOIN risk_rules r ON r.waste_item_id = i.waste_item_id
                    AND r.condition_type = :condition AND r.rule_status = 'ACTIVE'
                WHERE c.category_name = :category AND i.item_name = :item
                  AND c.category_status = 'ACTIVE' AND i.item_status = 'ACTIVE'
                  AND i.collection_status <> 'DO_NOT_COLLECT'", [
                    'condition' => $condition, 'category' => trim((string) ($raw['category'] ?? '')),
                    'item' => trim((string) ($raw['item'] ?? '')),
                ])->fetch();
            if (!$item) throw new DomainException('An item is unavailable. Select an item from the current catalogue.');
            $items[] = ['waste_item_id' => (int) $item['waste_item_id'], 'quantity' => $quantity,
                'estimated_weight_kg' => $weight, 'item_condition' => $condition,
                'condition_note' => $note === '' ? null : $note, 'applied_risk_level' => $item['applied_risk'],
                'requires_review' => $item['collection_status'] === 'REVIEW_REQUIRED' || $item['applied_risk'] === 'HIGH'];
        }
        return $items;
    }
}
