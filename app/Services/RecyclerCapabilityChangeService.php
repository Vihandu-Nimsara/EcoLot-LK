<?php
declare(strict_types=1);

/** Post-registration requests. Initial reviews and per-lot authorization remain authoritative. */
final class RecyclerCapabilityChangeService
{
    private PDO $db;
    public function __construct(?PDO $db = null) { $this->db = $db ?? Database::connection(); }

    private function query(string $sql, array $args = []): PDOStatement
    {
        $s=$this->db->prepare($sql);$s->execute($args);return $s;
    }

    public function available(): bool
    {
        return (int)$this->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='recycler_capability_change_requests' AND COLUMN_NAME IN ('capability_snapshot_json','license_identity_sha256','approved_activity_snapshot_json')")->fetchColumn()===3
            && (bool)$this->query("SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='recycler_capability_change_requests' AND INDEX_NAME='uq_capability_one_pending' AND NON_UNIQUE=0")->fetchColumn();
    }

    public function categories(): array
    {
        return $this->query("SELECT category_id,category_name FROM waste_categories WHERE category_status='ACTIVE' ORDER BY category_name")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function requests(int $owner): array
    {
        if (!$this->available()) return [];
        $rows=$this->query('SELECT q.*,c.category_name FROM recycler_capability_change_requests q JOIN waste_categories c ON c.category_id=q.category_id WHERE q.recycler_user_id=? ORDER BY q.request_id DESC',[$owner])->fetchAll(PDO::FETCH_ASSOC);
        foreach($rows as &$row) {
            $row['obligations']=$row['request_status']==='PENDING' && $this->isReduction($row)
                ? $this->obligations($owner,(int)$row['category_id'],$row['request_type']!=='DEACTIVATE') : [];
        }
        unset($row);return $rows;
    }

    private function json(mixed $value): string { return json_encode($value,JSON_THROW_ON_ERROR); }

    private function capability(int $owner,int $category): ?array
    {
        return $this->query('SELECT * FROM recycler_capabilities WHERE recycler_user_id=? AND category_id=? FOR UPDATE',[$owner,$category])->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function activities(int $owner): array
    {
        return $this->query("SELECT activity_type FROM recycler_authorized_activities WHERE recycler_user_id=? AND activity_status='APPROVED' ORDER BY activity_type FOR UPDATE",[$owner])->fetchAll(PDO::FETCH_COLUMN);
    }

    private function licenseIdentity(int $owner): string
    {
        $license=(new AuthorizedRecycler($this->db))->currentApprovedLicenseForRecycler($owner);
        if (!$license || $license['license_expiry']<$this->query('SELECT CURRENT_DATE')->fetchColumn()) throw new DomainException('A current approved, unexpired CEA licence is required.');
        $path=(new RecyclerLicenseRenewalService($this->db))->currentDocument($owner,$license);
        if ($path===null) throw new DomainException('A valid unchanged protected licence PDF is required.');
        return hash('sha256',$this->json([$license,hash_file('sha256',$path)]));
    }

    private function owner(int $owner,bool $approve=true): void
    {
        (new User($this->db))->lockForVerification($owner);
        $row=$this->query('SELECT r.verification_status,u.role,u.account_status,u.mobile_verified_at FROM authorized_recyclers r JOIN users u ON u.user_id=r.user_id WHERE r.user_id=? FOR UPDATE',[$owner])->fetch(PDO::FETCH_ASSOC);
        if (!$row || $row['role']!=='RECYCLER' || !in_array($row['verification_status'],$approve?['VERIFIED']:['VERIFIED','SUSPENDED'],true)
            || ($approve && ($row['account_status']!=='ACTIVE' || $row['mobile_verified_at']===null))) throw new DomainException('An active, mobile-verified and approved Recycler account is required.');
        $this->query('SELECT license_id FROM recycler_licenses WHERE recycler_user_id=? ORDER BY license_id FOR UPDATE',[$owner])->fetchAll();
    }

    private function admin(int $admin): void
    {
        if (Auth::role()!=='ADMIN' || (int)Auth::id()!==$admin || !$this->query("SELECT a.user_id FROM administrators a JOIN users u ON u.user_id=a.user_id WHERE a.user_id=? AND u.role='ADMIN' AND u.account_status='ACTIVE' LOCK IN SHARE MODE",[$admin])->fetchColumn()) throw new DomainException('An active authenticated Administrator is required.');
    }

    private function lotIds(int $owner,int $category): array
    {
        return array_map('intval',$this->query('SELECT DISTINCT b.e_lot_id FROM recycler_bids b JOIN e_lots l ON l.e_lot_id=b.e_lot_id WHERE b.recycler_user_id=? AND l.category_id=? ORDER BY b.e_lot_id',[$owner,$category])->fetchAll(PDO::FETCH_COLUMN));
    }

    /** Lot-first order agrees with bids, awards and handovers; never change those modules. */
    private function transaction(int $owner,int $category,Closure $action,bool $approve=true,?int $admin=null): mixed
    {
        if (!$this->available()) throw new DomainException('Capability changes are currently unavailable.');
        for($attempt=0;$attempt<3;$attempt++) {
            $this->db->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
            $this->db->beginTransaction();
            try {
                $lots=$this->lotIds($owner,$category);
                foreach($lots as $id)$this->query('SELECT e_lot_id FROM e_lots WHERE e_lot_id=? FOR UPDATE',[$id])->fetch();
                if ($admin!==null) $this->admin($admin);
                $this->owner($owner,$approve);
                if ($lots!==$this->lotIds($owner,$category)) {
                    $this->db->rollBack();
                    if ($attempt<2) continue;
                    throw new DomainException('Bidding obligations changed. Reload before trying again.');
                }
                $result=$action();$this->db->commit();return $result;
            } catch(Throwable $error) {
                if($this->db->inTransaction())$this->db->rollBack();
                if($error instanceof PDOException && in_array((int)($error->errorInfo[1]??0),[1205,1213],true) && $attempt<2)continue;
                throw $error;
            }
        }
        throw new DomainException('The request could not be saved. Reload and try again.');
    }

    private function isReduction(array $row): bool
    {
        return $row['request_type']==='DEACTIVATE' || ($row['request_type']==='UPDATE' && $row['previous_capability_status']==='APPROVED' && (int)$row['previous_can_handle_high_risk']===1 && (int)$row['requested_can_handle_high_risk']===0);
    }

    /** Current obligations, not an eligibility engine. HIGH uses the canonical source-risk definition. */
    public function obligations(int $owner,int $category,bool $highRiskOnly=false,bool $lock=false): array
    {
        $sql="SELECT b.bid_id,l.lot_code,b.bid_status,h.handover_status FROM recycler_bids b
            JOIN e_lots l ON l.e_lot_id=b.e_lot_id LEFT JOIN handover_records h ON h.winning_bid_id=b.bid_id
            WHERE b.recycler_user_id=? AND l.category_id=? AND (
                (b.bid_status='SUBMITTED' AND l.lot_status NOT IN ('CANCELLED','REJECTED'))
                OR (b.bid_status='WINNING' AND (h.handover_status IS NULL OR h.handover_status IN ('PENDING','SCHEDULED'))))";
        if($highRiskOnly)$sql.=" AND (NOT EXISTS(SELECT 1 FROM e_lot_items i WHERE i.e_lot_id=l.e_lot_id)
            OR EXISTS(SELECT 1 FROM e_lot_items i JOIN collection_record_items ci ON ci.record_item_id=i.record_item_id
                JOIN request_items ri ON ri.request_item_id=ci.request_item_id WHERE i.e_lot_id=l.e_lot_id
                AND (ri.applied_risk_level='HIGH' OR ci.actual_risk_level='HIGH')))";
        return $this->query($sql.' ORDER BY l.e_lot_id,b.bid_id'.($lock?' FOR UPDATE':''),[$owner,$category])->fetchAll(PDO::FETCH_ASSOC);
    }

    private function reason(string $value,string $caption): string
    {
        $value=trim($value);
        if(!mb_check_encoding($value,'UTF-8') || mb_strlen($value)<5 || mb_strlen($value)>500 || !preg_match('/\p{L}/u',$value)) throw new InvalidArgumentException($caption.' must contain a meaningful explanation of 5–500 characters.');
        return $value;
    }

    private function audit(int $actor,int $request,string $action): void
    {
        $this->query("INSERT INTO audit_logs(actor_user_id,action_type,entity_type,entity_id) VALUES(?,?,'recycler_capability_change_requests',?)",[$actor,$action,$request]);
    }

    public function submit(int $owner,array $input): int
    {
        if(Auth::role()!=='RECYCLER' || (int)Auth::id()!==$owner)throw new DomainException('Only the authenticated Recycler can submit a change.');
        $category=filter_var($input['category_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        $type=$input['request_type']??'';$risk=$input['requested_can_handle_high_risk']??'';
        if(!$category || !in_array($type,['ADD','UPDATE','DEACTIVATE'],true) || ($type!=='DEACTIVATE' && !in_array($risk,['0','1'],true)))throw new InvalidArgumentException('Select a category, change operation and requested high-risk permission.');
        $reason=$this->reason(is_string($input['request_reason']??null)?$input['request_reason']:'','Request reason');
        return $this->transaction($owner,$category,function()use($owner,$category,$type,$risk,$reason):int{
            if(!$this->query("SELECT category_id FROM waste_categories WHERE category_id=? AND category_status='ACTIVE' LOCK IN SHARE MODE",[$category])->fetchColumn() && $type!=='DEACTIVATE')throw new DomainException('Choose an active waste category.');
            $cap=$this->capability($owner,$category);
            if(($type==='ADD' && $cap!==null) || ($type==='UPDATE' && (!$cap || !in_array($cap['capability_status'],['APPROVED','INACTIVE'],true))) || ($type==='DEACTIVATE' && (!$cap || $cap['capability_status']!=='APPROVED')))throw new DomainException('The operation does not match the current capability. Reload the page.');
            if($type==='UPDATE' && $cap['capability_status']==='APPROVED' && (int)$cap['can_handle_high_risk']===(int)$risk)throw new DomainException('This update would not change your capability.');
            if($this->query("SELECT request_id FROM recycler_capability_change_requests WHERE recycler_user_id=? AND category_id=? AND request_status='PENDING' FOR UPDATE",[$owner,$category])->fetchColumn())throw new DomainException('A change for this category is already awaiting review.');
            $row=['request_type'=>$type,'previous_capability_status'=>$cap['capability_status']??null,'previous_can_handle_high_risk'=>$cap['can_handle_high_risk']??null,'requested_can_handle_high_risk'=>$type==='DEACTIVATE'?null:(int)$risk];
            if($this->isReduction($row) && $this->obligations($owner,$category,$type!=='DEACTIVATE',true))throw new DomainException('Outstanding bids or handovers prevent this reduction. Resolve those obligations first.');
            $license=$type==='DEACTIVATE'?null:$this->licenseIdentity($owner);
            $activities=$this->activities($owner);
            if($type!=='DEACTIVATE' && !$activities)throw new DomainException('At least one approved activity is required.');
            $this->query('INSERT INTO recycler_capability_change_requests(recycler_user_id,category_id,request_type,previous_capability_status,previous_can_handle_high_risk,requested_can_handle_high_risk,request_reason,capability_snapshot_json,license_identity_sha256,approved_activity_snapshot_json) VALUES(?,?,?,?,?,?,?,?,?,?)',[$owner,$category,$type,$row['previous_capability_status'],$row['previous_can_handle_high_risk'],$row['requested_can_handle_high_risk'],$reason,$this->json($cap),$license,$this->json($activities)]);
            $id=(int)$this->db->lastInsertId();$this->audit($owner,$id,'RECYCLER_CAPABILITY_CHANGE_SUBMITTED');return $id;
        });
    }

    private function pending(int $owner,int $request): array
    {
        $row=$this->query("SELECT * FROM recycler_capability_change_requests WHERE request_id=? AND recycler_user_id=? AND request_status='PENDING' FOR UPDATE",[$request,$owner])->fetch(PDO::FETCH_ASSOC);
        if(!$row)throw new DomainException('This request is missing or already reviewed.');return $row;
    }

    private function snapshot(int $owner,array $row): ?array
    {
        $cap=$this->capability($owner,(int)$row['category_id']);
        if($row['capability_snapshot_json']===null || $row['capability_snapshot_json']!==$this->json($cap))throw new DomainException('The capability changed or this legacy request lacks a trusted snapshot. Reject it and request a new submission.');
        if($row['request_type']!=='DEACTIVATE' && ($row['license_identity_sha256']===null || !hash_equals($row['license_identity_sha256'],$this->licenseIdentity($owner)) || $row['approved_activity_snapshot_json']!==$this->json($this->activities($owner))))throw new DomainException('The licence or approved activities changed after submission. Reject this request and re-evaluate against the current document.');
        return $cap;
    }

    public function review(int $owner,int $admin,int $request,string $decision,string $note): void
    {
        if(Auth::role()!=='ADMIN' || (int)Auth::id()!==$admin)throw new DomainException('An authenticated Administrator is required.');
        if(!in_array($decision,['APPROVE','REJECT'],true))throw new InvalidArgumentException('Choose Approve or Reject.');
        $note=$this->reason($note,'Reviewer feedback');
        if(!$this->available())throw new DomainException('Capability changes are currently unavailable.');
        $category=(int)$this->query('SELECT category_id FROM recycler_capability_change_requests WHERE request_id=? AND recycler_user_id=?',[$request,$owner])->fetchColumn();
        if($category<1)throw new DomainException('Capability request not found.');
        $this->transaction($owner,$category,function()use($owner,$admin,$request,$decision,$note,$category):void{
            $row=$this->pending($owner,$request);
            if($decision==='APPROVE') {
                $this->snapshot($owner,$row);
                if($this->isReduction($row) && $this->obligations($owner,$category,$row['request_type']!=='DEACTIVATE',true))throw new DomainException('Outstanding bids or handovers prevent this reduction.');
                $status=$row['request_type']==='DEACTIVATE'?'INACTIVE':'APPROVED';
                $risk=$row['request_type']==='DEACTIVATE'?(int)$row['previous_can_handle_high_risk']:(int)$row['requested_can_handle_high_risk'];
                if($row['request_type']==='ADD')$this->query('INSERT INTO recycler_capabilities(recycler_user_id,category_id,capability_status,can_handle_high_risk,reviewed_by_admin_user_id,reviewed_at,review_note) VALUES(?,?,?,?,?,CURRENT_TIMESTAMP,?)',[$owner,$category,$status,$risk,$admin,$note]);
                else $this->query('UPDATE recycler_capabilities SET capability_status=?,can_handle_high_risk=?,reviewed_by_admin_user_id=?,reviewed_at=CURRENT_TIMESTAMP,review_note=? WHERE recycler_user_id=? AND category_id=?',[$status,$risk,$admin,$note,$owner,$category]);
                if($status==='APPROVED')(new RecyclerLicenseReviewService($this->db))->requireScope($owner,$category,null,$risk,true);
            }
            $this->query('UPDATE recycler_capability_change_requests SET request_status=?,reviewed_by_admin_user_id=?,reviewed_at=CURRENT_TIMESTAMP,review_note=? WHERE request_id=?',[$decision==='APPROVE'?'APPROVED':'REJECTED',$admin,$note,$request]);
            $this->audit($admin,$request,'RECYCLER_CAPABILITY_CHANGE_'.$decision);
        },$decision==='APPROVE',$admin);
    }

    /** Documentary evidence context only: never materializes a pending capability. */
    public function lockScopeReview(int $owner,int $admin,int $request): array
    {
        if(!$this->db->inTransaction())throw new LogicException('Scope review requires a transaction.');
        if(!$this->available())throw new DomainException('Capability change review is currently unavailable.');
        $this->admin($admin);$this->owner($owner);$row=$this->pending($owner,$request);$this->snapshot($owner,$row);
        if($row['request_type']==='DEACTIVATE')throw new DomainException('Deactivation does not grant new scope.');
        return $row;
    }
}
