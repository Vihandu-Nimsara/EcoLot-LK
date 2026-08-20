<?php
declare(strict_types=1);

final class WasteCategory extends Model
{
    private const RECYCLER_SELECTABLE_NAMES = [
        'Automobile E-Waste',
        'Domestic E-Waste',
        'Industrial E-Waste',
        'Medical E-Waste',
        'Office E-Waste',
    ];

    protected string $table = 'waste_categories';
    protected string $primaryKey = 'category_id';

    public function recyclerSelectable(): array
    {
        $placeholders = implode(', ', array_fill(
            0,
            count(self::RECYCLER_SELECTABLE_NAMES),
            '?'
        ));
        $statement = $this->db->prepare(
            'SELECT `category_id`, `category_name`
             FROM `waste_categories`
             WHERE `category_status` = \'ACTIVE\'
               AND `category_name` IN (' . $placeholders . ')
             ORDER BY `category_name`'
        );
        $statement->execute(self::RECYCLER_SELECTABLE_NAMES);

        return $statement->fetchAll();
    }

    public function selectableIds(array $categoryIds): array
    {
        if ($categoryIds === []) {
            return [];
        }

        $idPlaceholders = implode(', ', array_fill(0, count($categoryIds), '?'));
        $namePlaceholders = implode(', ', array_fill(
            0,
            count(self::RECYCLER_SELECTABLE_NAMES),
            '?'
        ));
        $statement = $this->db->prepare(
            'SELECT `category_id`
             FROM `waste_categories`
             WHERE `category_status` = \'ACTIVE\'
               AND `category_id` IN (' . $idPlaceholders . ')
               AND `category_name` IN (' . $namePlaceholders . ')'
        );
        $statement->execute(array_merge($categoryIds, self::RECYCLER_SELECTABLE_NAMES));

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }
}
