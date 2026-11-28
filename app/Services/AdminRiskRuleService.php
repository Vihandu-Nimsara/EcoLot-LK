<?php
declare(strict_types=1);

final class AdminRiskRuleService
{
    public const CONDITIONS = ['WORKING', 'DAMAGED', 'UNKNOWN'];
    public const LEVELS = ['LOW', 'MEDIUM', 'HIGH'];
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    public static function effective(string $default, ?array $rule): string
    {
        return ($rule && $rule['rule_status'] === 'ACTIVE' && array_search($rule['risk_level'], self::LEVELS, true) > array_search($default, self::LEVELS, true))
            ? $rule['risk_level'] : $default;
    }

    public static function eligible(array $item): bool
    {
        return $item['item_status'] === 'ACTIVE' && $item['category_status'] === 'ACTIVE' && $item['collection_status'] !== 'DO_NOT_COLLECT';
    }

    public static function outcome(array $item, string $risk): string
    {
        if (!self::eligible($item)) return 'Unavailable for new pickups';
        return $risk === 'HIGH' || $item['collection_status'] === 'REVIEW_REQUIRED' ? 'Officer review required' : 'Normal pickup eligible';
    }

    public static function validate(array $input): array
    {
        foreach (['condition_type','risk_level','action_note','rule_status'] as $key) {
            if (!isset($input[$key]) || !is_string($input[$key])) throw new DomainException('Complete all rule fields.');
        }
        $condition = $input['condition_type']; $risk = $input['risk_level']; $status = $input['rule_status'];
        $note = trim($input['action_note']);
        if (!in_array($condition, self::CONDITIONS, true) || !in_array($risk, self::LEVELS, true) || !in_array($status, ['ACTIVE','INACTIVE'], true)) throw new DomainException('Choose valid condition, risk and status values.');
        if ($note === '' || preg_match('//u', $note) !== 1 || preg_match_all('/./us', $note) > 500) throw new DomainException('Handling guidance must contain 1–500 characters.');
        return ['condition_type'=>$condition,'risk_level'=>$risk,'action_note'=>$note,'rule_status'=>$status];
    }

    private function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt;
    }

    public function profiles(): array
    {
        $items = $this->query('SELECT i.*, c.category_name, c.category_status FROM e_waste_items i JOIN waste_categories c ON c.category_id=i.category_id ORDER BY c.category_name,i.item_name')->fetchAll(PDO::FETCH_ASSOC);
        $rules = $this->query('SELECT * FROM risk_rules ORDER BY waste_item_id,condition_type')->fetchAll(PDO::FETCH_ASSOC);
        $byItem = [];
        foreach ($rules as $rule) $byItem[$rule['waste_item_id']][$rule['condition_type']] = $rule;
        foreach ($items as &$item) $item['rules'] = $byItem[$item['waste_item_id']] ?? [];
        unset($item);
        return $items;
    }

    public static function revision(array $item, ?array $rule): string
    {
        return hash('sha256', json_encode([(string)$item['waste_item_id'], $item['default_risk_level'], $item['collection_status'], $item['item_status'], $item['category_status'], $rule ? [(string)$rule['risk_rule_id'],$rule['risk_level'],$rule['action_note'],$rule['rule_status']] : null], JSON_THROW_ON_ERROR));
    }

    public function save(int $actor, int $itemId, array $input): void
    {
        $data = self::validate($input);
        if ($actor < 1 || $itemId < 1) throw new DomainException('Invalid administrator or item.');
        $this->db->beginTransaction();
        try {
            $item = $this->query('SELECT i.*,c.category_status FROM e_waste_items i JOIN waste_categories c ON c.category_id=i.category_id WHERE i.waste_item_id=? FOR UPDATE', [$itemId])->fetch(PDO::FETCH_ASSOC);
            if (!$item || !self::eligible($item)) throw new DomainException('Only active, collectable items in active categories can be configured.');
            $before = $this->query('SELECT * FROM risk_rules WHERE waste_item_id=? AND condition_type=? FOR UPDATE', [$itemId,$data['condition_type']])->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!is_string($input['revision'] ?? null) || !hash_equals(self::revision($item,$before), $input['revision'])) throw new DomainException('The item or rule has changed. Reload and review the current profile.');
            if (array_search($data['risk_level'], self::LEVELS, true) < array_search($item['default_risk_level'], self::LEVELS, true)) throw new DomainException('A rule cannot be below the item’s default risk.');
            $oldEffective = self::effective($item['default_risk_level'],$before);
            $newEffective = self::effective($item['default_risk_level'],$data);
            if (array_search($newEffective,self::LEVELS,true) < array_search($oldEffective,self::LEVELS,true) && ($input['confirm_reduction'] ?? null) !== '1') throw new DomainException('Confirm the reduction in effective risk before saving.');
            if ($before) {
                $id = (int)$before['risk_rule_id'];
                if ($before['risk_level'] === $data['risk_level'] && $before['action_note'] === $data['action_note'] && $before['rule_status'] === $data['rule_status']) { $this->db->commit(); return; }
                $this->query('UPDATE risk_rules SET risk_level=?,action_note=?,rule_status=? WHERE risk_rule_id=?', [$data['risk_level'],$data['action_note'],$data['rule_status'],$id]);
            } else {
                $this->query('INSERT INTO risk_rules (waste_item_id,condition_type,risk_level,action_note,rule_status) VALUES (?,?,?,?,?)', [$itemId,$data['condition_type'],$data['risk_level'],$data['action_note'],$data['rule_status']]);
                $id = (int)$this->db->lastInsertId();
            }
            $details = json_encode(['item_id'=>$itemId,'condition'=>$data['condition_type'],'before'=>$before,'after'=>$data,'effective_before'=>$oldEffective,'effective_after'=>$newEffective], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            $this->query('INSERT INTO audit_logs (actor_user_id,action_type,entity_type,entity_id,details) VALUES (?,?,?,?,?)', [$actor,$before?'RISK_RULE_UPDATED':'RISK_RULE_CREATED','risk_rules',$id,$details]);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }
}
