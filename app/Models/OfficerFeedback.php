<?php
declare(strict_types=1);

final class OfficerFeedback extends Model
{
    public const STATUSES=['OPEN','IN_REVIEW','RESOLVED','CLOSED'];
    public const TYPES=['COMPLAINT','SUGGESTION','COMPLIMENT'];

    private function from(): string
    {
        return ' FROM complaint_feedback f JOIN users u ON u.user_id=f.public_user_id
            JOIN public_profiles p ON p.user_id=f.public_user_id
            LEFT JOIN e_waste_requests r ON r.request_id=f.request_id
            LEFT JOIN area_collection_schedules s ON s.schedule_id=r.schedule_id
            LEFT JOIN postal_code_areas a ON a.postal_area_id=s.postal_area_id
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
        if ($filters['q']!=='') { $where.=' AND (f.message LIKE :message OR u.full_name LIKE :name)'; $params['message']=$params['name']='%'.$filters['q'].'%'; }
        $total=(int)$this->query('SELECT COUNT(*)'.$this->from().' WHERE '.$this->scope().$where,$params)->fetchColumn();
        $offset=($filters['page']-1)*20;$sort=$filters['sort']==='oldest'?'ASC':'DESC';
        return ['total'=>$total,'rows'=>$this->query($this->select().$where." ORDER BY f.submitted_at $sort,f.feedback_id $sort LIMIT 20 OFFSET $offset",$params)->fetchAll()];
    }

    public function details(int $id, int $officer, bool $lock=false): ?array
    {
        return $this->query($this->select().' AND f.feedback_id=:id'.($lock?' FOR UPDATE':''),$this->parameters($officer)+['id'=>$id])->fetch() ?: null;
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
        $response=trim($response);
        // TEXT supports more, but use the existing public-message policy to bound the response.
        if (mb_strlen($response)>2000 || !mb_check_encoding($response,'UTF-8')) throw new DomainException('Response must be valid text of at most 2000 characters.');
        $this->db->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
        $this->db->beginTransaction();
        try {
            $feedback=$this->details($id,$officer,true);
            if (!$feedback) throw new OutOfBoundsException('Feedback not found.');
            if ($feedback['feedback_status']!==$expected || !in_array($status,self::transitions($feedback['feedback_status']),true)) throw new DomainException('This feedback has changed or the transition is not allowed. Reload the page.');
            if (in_array($status,['RESOLVED','CLOSED'],true) && $response==='') throw new DomainException('An Officer response is required to resolve or close feedback.');
            $changed=$this->query('UPDATE complaint_feedback SET feedback_status=:status,officer_response=:response,
                reviewed_by_officer_user_id=:officer,reviewed_at=CURRENT_TIMESTAMP WHERE feedback_id=:id AND feedback_status=:expected',
                ['status'=>$status,'response'=>$response===''?null:$response,'officer'=>$officer,'id'=>$id,'expected'=>$expected]);
            if ($changed->rowCount()!==1) throw new DomainException('Feedback was already updated.');
            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }
}
