<?php
declare(strict_types=1);

final class CollectionRecordItem extends Model
{
    protected string $table = 'collection_record_items';
    protected string $primaryKey = 'record_item_id';

    public function forRequest(int $requestId): array
    {
        return $this->query('SELECT ri.*, wi.item_name, cri.actual_quantity, cri.actual_weight_kg,
                cri.actual_condition, cri.notes, cri.item_result
            FROM request_items ri JOIN e_waste_items wi ON wi.waste_item_id = ri.waste_item_id
            LEFT JOIN collection_record_items cri ON cri.request_item_id = ri.request_item_id
            WHERE ri.request_id = :id ORDER BY ri.request_item_id', ['id' => $requestId])->fetchAll();
    }

    public function validated(int $requestId, mixed $input): array
    {
        $requested = $this->forRequest($requestId);
        if (!is_array($input) || !$requested || count($input) !== count($requested)) {
            throw new DomainException('Provide actual collection data for every requested item.');
        }
        $rows = [];
        foreach ($requested as $item) {
            $id = (int) $item['request_item_id'];
            $raw = $input[$id] ?? null;
            if (!is_array($raw)) throw new DomainException('An item does not belong to this request or is missing.');
            $quantity = $raw['actual_quantity'] ?? null;
            if (!is_string($quantity) || !ctype_digit($quantity) || ($qty = filter_var($quantity, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => (int) $item['quantity']]])) === false) {
                throw new DomainException('Collected quantity must be a whole number from zero to the requested quantity.');
            }
            $weight = $raw['actual_weight_kg'] ?? '';
            if (!is_string($weight) || ($weight !== '' && !preg_match('/^\d{1,7}(?:\.\d{1,3})?$/D', $weight))) {
                throw new DomainException('Weight must be non-negative, at most 9999999.999 kg, with up to three decimal places.');
            }
            $condition = $raw['actual_condition'] ?? '';
            $notes = $raw['notes'] ?? '';
            if (!is_string($condition) || !is_string($notes)) throw new DomainException('Condition and notes must be text.');
            $validator = new Validator();
            if (!$validator->validate(['actual_condition' => $condition, 'notes' => $notes], ['actual_condition' => ['in:WORKING,DAMAGED,UNKNOWN'], 'notes' => ['max:500']])) {
                throw new DomainException('Choose a valid condition and use at most 500 characters for item notes.');
            }
            if ($qty > 0 && ($weight === '' || (float) $weight <= 0 || $condition === '')) {
                throw new DomainException('Collected items require positive weight and an actual condition.');
            }
            if ($qty === 0 && (($weight !== '' && (float) $weight != 0) || $condition !== '')) {
                throw new DomainException('Uncollected items require zero/blank weight and no actual condition.');
            }
            $risk = null;
            if ($qty > 0) {
                $risk = $this->query("SELECT COALESCE((SELECT risk_level FROM risk_rules WHERE waste_item_id = wi.waste_item_id
                    AND condition_type = :condition AND rule_status = 'ACTIVE'), wi.default_risk_level)
                    FROM e_waste_items wi WHERE wi.waste_item_id = :id", ['condition' => $condition, 'id' => $item['waste_item_id']])->fetchColumn();
            }
            $rows[] = ['request_item_id' => $id, 'actual_quantity' => $qty, 'actual_weight_kg' => $weight === '' ? null : $weight,
                'actual_condition' => $qty ? $condition : null, 'notes' => trim((string) $notes) ?: null,
                'actual_risk_level' => $risk, 'item_result' => $qty === 0 ? 'NOT_COLLECTED' : ($qty === (int) $item['quantity'] ? 'COLLECTED' : 'PARTIAL')];
        }
        return $rows;
    }

    public function replaceForRecord(int $recordId, array $rows): void
    {
        $this->query('DELETE FROM collection_record_items WHERE collection_record_id = :id', ['id' => $recordId]);
        foreach ($rows as $row) $this->create(['collection_record_id' => $recordId] + $row);
    }
}
