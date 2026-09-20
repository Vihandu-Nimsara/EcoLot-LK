<?php
declare(strict_types=1);

final class EWasteItem extends Model
{
    protected string $table = 'e_waste_items';
    protected string $primaryKey = 'waste_item_id';

    public function findByCategoryAndName(int $categoryId, string $itemName): ?array
    {
        $result = $this->query(
            'SELECT `waste_item_id`, `category_id`, `item_name`, `default_risk_level`
             FROM `e_waste_items`
             WHERE `category_id` = :category_id
               AND `item_name` = :item_name
             LIMIT 1',
            ['category_id' => $categoryId, 'item_name' => $itemName]
        )->fetch();

        return $result === false ? null : $result;
    }

    /**
     * Looks up an item by category + name, and creates it (as an
     * accepted, active, LOW-risk item) the first time it's ever used.
     */
    public function findOrCreateByName(int $categoryId, string $itemName): array
    {
        $existing = $this->findByCategoryAndName($categoryId, $itemName);

        if ($existing !== null) {
            return $existing;
        }

        $wasteItemId = $this->create([
            'category_id' => $categoryId,
            'item_name' => $itemName,
            'collection_status' => 'ACCEPTED',
            'default_risk_level' => 'LOW',
            'item_status' => 'ACTIVE',
        ]);

        return [
            'waste_item_id' => $wasteItemId,
            'category_id' => $categoryId,
            'item_name' => $itemName,
            'default_risk_level' => 'LOW',
        ];
    }
}