<?php
declare(strict_types=1);

final class OfficerELot extends Model
{
    public const STATUSES = ['PENDING_VERIFICATION','REJECTED','OPEN_FOR_BIDDING','AWARDED','COMPLETED','CANCELLED'];

    private function scope(): string
    {
        // Every source schedule must belong to this Officer. Mixed-owner lots fail closed.
        return "l.lot_status <> 'DRAFT' AND EXISTS (
            SELECT 1 FROM e_lot_items ei JOIN collection_record_items ci ON ci.record_item_id=ei.record_item_id
            JOIN collection_records cr ON cr.collection_record_id=ci.collection_record_id
            JOIN schedule_collections sc ON sc.schedule_collection_id=cr.schedule_collection_id
            JOIN area_collection_schedules s ON s.schedule_id=sc.schedule_id
            WHERE ei.e_lot_id=l.e_lot_id AND s.created_by_officer_user_id=:owner)
            AND NOT EXISTS (
            SELECT 1 FROM e_lot_items ei JOIN collection_record_items ci ON ci.record_item_id=ei.record_item_id
            JOIN collection_records cr ON cr.collection_record_id=ci.collection_record_id
            JOIN schedule_collections sc ON sc.schedule_collection_id=cr.schedule_collection_id
            JOIN area_collection_schedules s ON s.schedule_id=sc.schedule_id
            WHERE ei.e_lot_id=l.e_lot_id AND s.created_by_officer_user_id<>:other_owner)";
    }

    private function select(): string
    {
        return "SELECT l.*, c.category_name, u.full_name AS collector_name, reviewer.full_name AS reviewer_name,
            (SELECT COUNT(*) FROM e_lot_items ei WHERE ei.e_lot_id=l.e_lot_id) AS item_count,
            (SELECT SUM(ci.actual_quantity) FROM e_lot_items ei JOIN collection_record_items ci ON ci.record_item_id=ei.record_item_id WHERE ei.e_lot_id=l.e_lot_id) AS total_quantity,
            (SELECT SUM(ci.actual_weight_kg) FROM e_lot_items ei JOIN collection_record_items ci ON ci.record_item_id=ei.record_item_id WHERE ei.e_lot_id=l.e_lot_id) AS total_weight,
            (SELECT COUNT(*) FROM recycler_bids b WHERE b.e_lot_id=l.e_lot_id AND b.bid_status='SUBMITTED') AS bid_count
            FROM e_lots l JOIN waste_categories c ON c.category_id=l.category_id
            JOIN users u ON u.user_id=l.created_by_collector_user_id
            LEFT JOIN users reviewer ON reviewer.user_id=l.verified_by_officer_user_id WHERE " . $this->scope();
    }

    public function listing(int $officer, array $filters): array
    {
        $where = ''; $params=['owner'=>$officer,'other_owner'=>$officer];
        if ($filters['status'] !== '') { $where.=' AND l.lot_status=:status'; $params['status']=$filters['status']; }
        if ($filters['category'] !== '') { $where.=' AND l.category_id=:category'; $params['category']=$filters['category']; }
        if ($filters['q'] !== '') { $where.=' AND (l.lot_code LIKE :code OR l.title LIKE :title)'; $params['code']=$params['title']='%'.$filters['q'].'%'; }
        $total=(int)$this->query('SELECT COUNT(*) FROM e_lots l WHERE '.$this->scope().$where,$params)->fetchColumn();
        $sort=$filters['sort']==='oldest' ? 'ASC' : 'DESC';
        $offset=($filters['page']-1)*20;
        $rows=$this->query($this->select().$where." ORDER BY l.created_at $sort, l.e_lot_id $sort LIMIT 20 OFFSET $offset",$params)->fetchAll();
        return ['rows'=>$rows,'total'=>$total];
    }

    public function categories(): array
    {
        return $this->query('SELECT category_id,category_name FROM waste_categories ORDER BY category_name')->fetchAll();
    }

    public function details(int $id, int $officer): ?array
    {
        $lot=$this->query($this->select().' AND l.e_lot_id=:id',['owner'=>$officer,'other_owner'=>$officer,'id'=>$id])->fetch();
        if (!$lot) return null;
        $lot['items']=$this->sources($id);
        $lot['bids']=$this->query("SELECT b.*, r.company_name, u.full_name AS recycler_name, (".$this->eligibilitySql().") AS eligible
            FROM recycler_bids b JOIN e_lots l ON l.e_lot_id=b.e_lot_id JOIN authorized_recyclers r ON r.user_id=b.recycler_user_id
            JOIN users u ON u.user_id=b.recycler_user_id WHERE b.e_lot_id=:id ORDER BY b.submitted_at,b.bid_id",['id'=>$id])->fetchAll();
        $lot['handover']=$this->query("SELECT h.*, r.company_name, reviewer.full_name AS officer_name
            FROM handover_records h JOIN recycler_bids b ON b.bid_id=h.winning_bid_id
            JOIN authorized_recyclers r ON r.user_id=b.recycler_user_id
            JOIN users reviewer ON reviewer.user_id=h.recorded_by_officer_user_id
            WHERE b.e_lot_id=:id",['id'=>$id])->fetch() ?: null;
        $lot['now']=(string)$this->query('SELECT CURRENT_TIMESTAMP')->fetchColumn();
        return $lot;
    }

    private function sources(int $id, bool $lock=false): array
    {
        return $this->query('SELECT ci.*, ri.request_id, ri.quantity AS requested_quantity, wi.item_name, wi.category_id,
            cr.pickup_result, cr.request_id AS recorded_request_id, cr.collection_record_id,
            sc.schedule_collection_id, sc.verification_status, sc.submitted_by_collector_user_id,
            sc.schedule_id, req.schedule_id AS request_schedule_id, s.collection_date, s.created_by_officer_user_id AS source_officer, a.area_name
            FROM e_lot_items ei JOIN collection_record_items ci ON ci.record_item_id=ei.record_item_id
            JOIN request_items ri ON ri.request_item_id=ci.request_item_id
            JOIN e_waste_items wi ON wi.waste_item_id=ri.waste_item_id
            JOIN collection_records cr ON cr.collection_record_id=ci.collection_record_id
            JOIN e_waste_requests req ON req.request_id=cr.request_id
            JOIN schedule_collections sc ON sc.schedule_collection_id=cr.schedule_collection_id
            JOIN area_collection_schedules s ON s.schedule_id=sc.schedule_id
            JOIN postal_code_areas a ON a.postal_area_id=s.postal_area_id
            WHERE ei.e_lot_id=:id ORDER BY ci.record_item_id'.($lock?' FOR UPDATE':''),['id'=>$id])->fetchAll();
    }

    private function validateSources(array $lot, int $officer): void
    {
        $items=$this->sources((int)$lot['e_lot_id'],true);
        if (!$items) throw new DomainException('An E-Lot needs verified collected items.');
        foreach ($items as $item) {
            if ((int)$item['source_officer']!==$officer || $item['verification_status']!=='VERIFIED' || $item['submitted_by_collector_user_id']!=$lot['created_by_collector_user_id']
                || $item['category_id']!=$lot['category_id'] || $item['request_id']!=$item['recorded_request_id']
                || $item['schedule_id']!=$item['request_schedule_id'] || !in_array($item['item_result'],['COLLECTED','PARTIAL'],true)
                || $item['actual_quantity']<1 || $item['actual_quantity']>$item['requested_quantity']
                || $item['actual_weight_kg']===null || (float)$item['actual_weight_kg']<=0
                || $item['actual_condition']===null || $item['actual_risk_level']===null
                || $item['item_result']!==($item['actual_quantity']==$item['requested_quantity']?'COLLECTED':'PARTIAL')) {
                throw new DomainException('The E-Lot source inventory is incomplete, unverified or inconsistent.');
            }
        }
    }

    private function eligibilitySql(): string
    {
        return "EXISTS (SELECT 1 FROM users bidder JOIN authorized_recyclers recycler ON recycler.user_id=bidder.user_id
            WHERE bidder.user_id=b.recycler_user_id AND bidder.role='RECYCLER' AND bidder.account_status='ACTIVE'
            AND recycler.verification_status='VERIFIED'
            AND EXISTS (SELECT 1 FROM recycler_licenses lic WHERE lic.recycler_user_id=bidder.user_id AND lic.license_status='VALID' AND lic.expiry_date>=CURRENT_DATE)
            AND EXISTS (SELECT 1 FROM recycler_capabilities cap WHERE cap.recycler_user_id=bidder.user_id AND cap.category_id=l.category_id
                AND cap.capability_status='APPROVED' AND (cap.can_handle_high_risk=1 OR NOT EXISTS (
                    SELECT 1 FROM e_lot_items ei JOIN collection_record_items ci ON ci.record_item_id=ei.record_item_id
                    JOIN request_items ri ON ri.request_item_id=ci.request_item_id
                    WHERE ei.e_lot_id=l.e_lot_id AND (ci.actual_risk_level='HIGH' OR ri.applied_risk_level='HIGH')))))";
    }

    private function eligible(array $lot, int $recycler): bool
    {
        // Unlike bid submission, award eligibility must remain evaluable after the deadline.
        return (bool)$this->query('SELECT '.$this->eligibilitySql().' FROM e_lots l
            JOIN recycler_bids b ON b.e_lot_id=l.e_lot_id WHERE l.e_lot_id=:lot AND b.recycler_user_id=:recycler',
            ['lot'=>$lot['e_lot_id'],'recycler'=>$recycler])->fetchColumn();
    }

    public static function dateTime(string $value): string
    {
        if (!preg_match('/^[1-9][0-9]{3}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}(?::[0-9]{2})?$/D',$value)) throw new DomainException('Enter a valid date and time.');
        $format=strlen($value)===19?'Y-m-d\\TH:i:s':'Y-m-d\\TH:i';
        $date=DateTimeImmutable::createFromFormat('!'.$format, $value, new DateTimeZone('Asia/Colombo'));
        if (!$date || $date->format($format)!==$value) throw new DomainException('Enter a valid date and time.');
        return $date->format('Y-m-d H:i:s');
    }

    public function act(int $id, int $officer, array $input): void
    {
        $note=trim($input['note']);
        if (!mb_check_encoding($note,'UTF-8') || mb_strlen($note)>500) throw new DomainException('Notes must be at most 500 characters.');
        $this->db->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
        $this->db->beginTransaction();
        try {
            $lot=(new ELot($this->db))->lockForBidding($id);
            if (!$lot || !$this->query('SELECT l.e_lot_id FROM e_lots l WHERE '.$this->scope().' AND l.e_lot_id=:id',
                ['owner'=>$officer,'other_owner'=>$officer,'id'=>$id])->fetch()) throw new OutOfBoundsException('E-Lot not found.');
            $now=(string)$this->query('SELECT CURRENT_TIMESTAMP')->fetchColumn();
            if (in_array($input['action'],['approve','reject'],true)) {
                if ($this->query('SELECT bid_id FROM recycler_bids WHERE e_lot_id=:id LIMIT 1',['id'=>$id])->fetch()) throw new DomainException('An E-Lot with bids cannot be reviewed again.');
                if ($lot['lot_status']!=='PENDING_VERIFICATION') throw new DomainException('Only pending E-Lots can be reviewed.');
                if ($input['action']==='reject' && $note==='') throw new DomainException('A rejection reason is required.');
                $open=$close=null;
                if ($input['action']==='approve') {
                    $this->validateSources($lot,$officer);
                    $open=self::dateTime($input['open']); $close=self::dateTime($input['close']);
                    if ($open<$now || $close<=$open) throw new DomainException('Bidding must open in the future and close after opening.');
                }
                $this->query('UPDATE e_lots SET lot_status=:status, verified_by_officer_user_id=:officer,
                    verified_at=:now, verification_note=:note, bidding_open_at=:open, bidding_close_at=:close WHERE e_lot_id=:id',
                    ['status'=>$input['action']==='approve'?'OPEN_FOR_BIDDING':'REJECTED','officer'=>$officer,'now'=>$now,
                    'note'=>$note===''?null:$note,'open'=>$open,'close'=>$close,'id'=>$id]);
            } elseif ($input['action']==='award') {
                if ($lot['lot_status']!=='OPEN_FOR_BIDDING' || !$lot['verified_at'] || !$lot['verified_by_officer_user_id']
                    || !$lot['bidding_close_at'] || $now<$lot['bidding_close_at']) throw new DomainException('Award only after verified bidding has closed.');
                $bidId=filter_var($input['bid_id'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
                if (!$bidId) throw new DomainException('Select a valid submitted bid.');
                $bid=$this->query('SELECT * FROM recycler_bids WHERE bid_id=:bid AND e_lot_id=:lot FOR UPDATE',['bid'=>$bidId,'lot'=>$id])->fetch();
                if (!$bid || $bid['bid_status']!=='SUBMITTED') throw new DomainException('The selected bid is not available for this E-Lot.');
                // Use the Recycler service lock order: lot → bid → eligibility rows → source rows.
                foreach ([['users','user_id'],['authorized_recyclers','user_id'],['recycler_licenses','recycler_user_id'],['recycler_capabilities','recycler_user_id']] as [$table,$column]) {
                    $this->query("SELECT `$column` FROM `$table` WHERE `$column`=:id FOR UPDATE",['id'=>$bid['recycler_user_id']])->fetchAll();
                }
                $this->validateSources($lot,$officer);
                if (!$this->eligible($lot,(int)$bid['recycler_user_id'])) throw new DomainException('The selected Recycler is no longer eligible.');
                $this->query("UPDATE recycler_bids SET bid_status='WINNING',reviewed_by_officer_user_id=:officer,reviewed_at=:now WHERE bid_id=:id",['officer'=>$officer,'now'=>$now,'id'=>$bidId]);
                $this->query("UPDATE recycler_bids SET bid_status='REJECTED',reviewed_by_officer_user_id=:officer,reviewed_at=:now WHERE e_lot_id=:lot AND bid_status='SUBMITTED'",['officer'=>$officer,'now'=>$now,'lot'=>$id]);
                $this->query("UPDATE e_lots SET lot_status='AWARDED' WHERE e_lot_id=:id",['id'=>$id]);
                $this->query("INSERT INTO handover_records (winning_bid_id,recorded_by_officer_user_id) VALUES (:bid,:officer)",['bid'=>$bidId,'officer'=>$officer]);
            } elseif ($input['action']==='handover') {
                if ($lot['lot_status']!=='AWARDED') throw new DomainException('Only awarded E-Lots permit handover changes.');
                $handover=$this->query("SELECT h.*,b.reviewed_at FROM handover_records h JOIN recycler_bids b ON b.bid_id=h.winning_bid_id
                    WHERE b.e_lot_id=:id AND b.bid_status='WINNING' FOR UPDATE",['id'=>$id])->fetch();
                if (!$handover || !in_array($handover['handover_status'],['PENDING','SCHEDULED'],true)) throw new DomainException('Handover is unavailable or already final.');
                if (!in_array($input['handover_status'],['SCHEDULED','COMPLETED'],true)) throw new DomainException('Choose Scheduled or Completed.');
                $date=self::dateTime($input['date']);
                if (($input['handover_status']==='SCHEDULED' && $date<=$now)
                    || ($input['handover_status']==='COMPLETED' && ($date>$now || $date<$handover['reviewed_at']))) throw new DomainException('Scheduled handover must be in the future; completed handover must be after award and not in the future.');
                if ($handover['handover_status']===$input['handover_status'] && $handover['handover_date']===$date && ($handover['remarks']??'')===$note) throw new DomainException('This handover update was already saved.');
                $this->query('UPDATE handover_records SET handover_status=:status,handover_date=:date,remarks=:note,recorded_by_officer_user_id=:officer WHERE winning_bid_id=:bid',
                    ['status'=>$input['handover_status'],'date'=>$date,'note'=>$note===''?null:$note,'officer'=>$officer,'bid'=>$handover['winning_bid_id']]);
                // The existing database trigger completes the E-Lot only on completed handover.
            } else throw new DomainException('Unsupported E-Lot action.');
            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }
}
