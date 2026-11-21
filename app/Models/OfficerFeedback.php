<?php
declare(strict_types=1);

final class OfficerFeedback extends Model
{
    public const STATUSES=['OPEN','IN_REVIEW','RESOLVED','CLOSED'];
    public const LABELS=['OPEN'=>'Open','IN_REVIEW'=>'In Review','RESOLVED'=>'Resolved','CLOSED'=>'Closed'];
    public const TYPES=['COMPLAINT','SUGGESTION','COMPLIMENT'];

    private function from(): string
    {
        return ' FROM complaint_feedback f JOIN users u ON u.user_id=f.public_user_id
            JOIN public_profiles p ON p.user_id=f.public_user_id
            LEFT JOIN e_waste_requests r ON r.request_id=f.request_id
            LEFT JOIN area_collection_schedules s ON s.schedule_id=r.schedule_id
            LEFT JOIN postal_code_areas a ON a.postal_area_id=COALESCE(s.postal_area_id,p.postal_area_id)
            LEFT JOIN users reviewer ON reviewer.user_id=f.reviewed_by_officer_user_id ';
    }

    private function scope(): string
    {
        // General feedback uses the submitter area until claimed; only its reviewer can continue.
        return "(f.reviewed_by_officer_user_id IS NULL OR f.reviewed_by_officer_user_id=:reviewer)
            AND ((f.request_id IS NOT NULL AND r.public_user_id=f.public_user_id AND s.created_by_officer_user_id=:owner)
            OR (f.request_id IS NULL AND (f.reviewed_by_officer_user_id=:assigned OR EXISTS (
                SELECT 1 FROM area_collection_schedules own WHERE own.postal_area_id=p.postal_area_id
                AND own.created_by_officer_user_id=:area_owner))))";
    }

    private function parameters(int $officer): array
    {
        return ['reviewer'=>$officer,'owner'=>$officer,'assigned'=>$officer,'area_owner'=>$officer];
    }

    private function select(): string
    {
        return 'SELECT f.*,u.full_name AS public_name,reviewer.full_name AS reviewer_name,
            r.schedule_id,r.request_status,s.collection_date,a.area_name'.$this->from().' WHERE '.$this->scope();
    }

    public function listing(int $officer, array $filters): array
    {
        $where='';$params=$this->parameters($officer);
        foreach (['status'=>'feedback_status','type'=>'feedback_type'] as $filter=>$column) {
            if ($filters[$filter]!=='') { $where.=" AND f.$column=:$filter"; $params[$filter]=$filters[$filter]; }
        }
        if ($filters['q']!=='') { $where.=" AND (f.message LIKE :message ESCAPE '!' OR u.full_name LIKE :name ESCAPE '!')"; $params['message']=$params['name']='%'.str_replace(['!','%','_'],['!!','!%','!_'],$filters['q']).'%'; }
        $total=(int)$this->query('SELECT COUNT(*)'.$this->from().' WHERE '.$this->scope().$where,$params)->fetchColumn();
        $pages=max(1,(int)ceil($total/20)); $page=min($filters['page'],$pages);
        $offset=($page-1)*20;$sort=$filters['sort']==='oldest'?'ASC':'DESC';
        return ['total'=>$total,'page'=>$page,'pages'=>$pages,'rows'=>$this->query($this->select().$where." ORDER BY f.submitted_at $sort,f.feedback_id $sort LIMIT 20 OFFSET $offset",$params)->fetchAll()];
    }

    public function counts(int $officer): array
    {
        $counts=array_fill_keys(self::STATUSES,0);
        foreach ($this->query('SELECT f.feedback_status,COUNT(*) AS total'.$this->from().' WHERE '.$this->scope().' GROUP BY f.feedback_status',$this->parameters($officer))->fetchAll() as $row) {
            $counts[$row['feedback_status']]=(int)$row['total'];
        }
        return $counts;
    }

    public function details(int $id, int $officer, bool $lock=false): ?array
    {
        // Serialize the decision, then stabilize only the context used for authorization.
        if ($lock) {
            $snapshot=$this->query('SELECT public_user_id,request_id FROM complaint_feedback WHERE feedback_id=:id FOR UPDATE',['id'=>$id])->fetch();
            if ($snapshot) {
                if ($snapshot['request_id']!==null) $this->query('SELECT request_id FROM e_waste_requests WHERE request_id=:id FOR UPDATE',['id'=>$snapshot['request_id']])->fetch();
                else $this->query('SELECT user_id FROM public_profiles WHERE user_id=:id FOR UPDATE',['id'=>$snapshot['public_user_id']])->fetch();
            }
        }
        $row=$this->query($this->select().' AND f.feedback_id=:id',$this->parameters($officer)+['id'=>$id])->fetch();
        if (!$row) return null;
        if (!$lock) $row['history']=OfficerActionAudit::history($this->db,'complaint_feedback',$id);
        return $row;
    }

    public static function transitions(string $status): array
    {
        return match ($status) {
            'OPEN'=>['IN_REVIEW','RESOLVED','CLOSED'],
            'IN_REVIEW'=>['RESOLVED','CLOSED'],
            'RESOLVED'=>['CLOSED'],
            default=>[],
        };
    }

    public function review(int $id, int $officer, string $expected, string $status, string $response): void
    {
        if (!mb_check_encoding($response,'UTF-8')) throw new DomainException('Response must use valid UTF-8 text.');
        $response=preg_replace('/^\s+|\s+$/u','',$response);
        // TEXT supports more, but use the existing public-message policy to bound the response.
        if (mb_strlen($response)>2000 || !mb_check_encoding($response,'UTF-8')) throw new DomainException('Response must be valid text of at most 2000 characters.');
        $this->db->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
        $this->db->beginTransaction();
        try {
            $feedback=$this->details($id,$officer,true);
            if (!$feedback) throw new OutOfBoundsException('Feedback not found.');
            if ($feedback['feedback_status']!==$expected || !in_array($status,self::transitions($feedback['feedback_status']),true)) throw new DomainException('This feedback has changed or the transition is not allowed. Reload the page.',409);
            if (in_array($status,['RESOLVED','CLOSED'],true) && $response==='') throw new DomainException('An Officer response is required to resolve or close feedback.');
            $changed=$this->query('UPDATE complaint_feedback SET feedback_status=:status,officer_response=:response,
                reviewed_by_officer_user_id=:officer,reviewed_at=CURRENT_TIMESTAMP WHERE feedback_id=:id AND feedback_status=:expected',
                ['status'=>$status,'response'=>$response===''?null:$response,'officer'=>$officer,'id'=>$id,'expected'=>$expected]);
            if ($changed->rowCount()!==1) throw new DomainException('Feedback was already updated.',409);
            OfficerActionAudit::record($this->db,$officer,'complaint_feedback',$id,
                ['status'=>$feedback['feedback_status'],'response'=>$feedback['officer_response'],'reviewed_by'=>$feedback['reviewed_by_officer_user_id'],'reviewed_at'=>$feedback['reviewed_at']],
                ['status'=>$status,'response'=>$response===''?null:$response]);
            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }
}
