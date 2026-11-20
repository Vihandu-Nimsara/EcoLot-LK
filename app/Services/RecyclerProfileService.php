<?php
declare(strict_types=1);

/** Owner-only contact and purpose-bound phone challenges. Never activates registrations. */
final class RecyclerProfileService
{
    private PDO $db;
    private Closure $send;

    public function __construct(?PDO $db=null, ?Closure $send=null, private ?LoginRateLimiter $limiter=null)
    {
        $this->db=$db ?? Database::connection();
        $this->send=$send ?? Closure::fromCallable([new SmsGatewayService(),'sendPhoneChangeOtp']);
    }

    private function query(string $sql,array $args=[]): PDOStatement
    {
        $s=$this->db->prepare($sql);$s->execute($args);return $s;
    }

    private function account(int $owner,bool $lock=false): ?array
    {
        if($lock)(new User($this->db))->lockForVerification($owner);
        return $this->query('SELECT u.*,r.verification_status FROM users u JOIN authorized_recyclers r ON r.user_id=u.user_id WHERE u.user_id=?'.($lock?' FOR UPDATE':''),[$owner])->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function eligible(?array $user): bool
    {
        return $user && $user['role']==='RECYCLER' && $user['account_status']==='ACTIVE'
            && $user['verification_status']==='VERIFIED' && $user['mobile_verified_at']!==null;
    }

    private function result(bool $success,string $message,string $field=''): array
    {
        return ['success'=>$success,'message'=>$message,'field'=>$field];
    }

    private function invalidate(int $id): void
    {
        $this->query('UPDATE recycler_phone_change_challenges SET invalidated_at=CURRENT_TIMESTAMP WHERE challenge_id=? AND verified_at IS NULL AND invalidated_at IS NULL',[$id]);
    }

    private function phoneReady(): bool
    {
        // A compatible legacy table alone is insufficient: target reservations need the migration.
        $indexes=$this->query("SELECT INDEX_NAME,NON_UNIQUE,COLUMN_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='recycler_phone_change_challenges' AND INDEX_NAME IN ('uq_phone_change_one_active','uq_phone_change_active_target')")->fetchAll(PDO::FETCH_ASSOC);
        $expected=['uq_phone_change_one_active'=>'active_recycler_user_id','uq_phone_change_active_target'=>'active_proposed_mobile_number'];
        foreach($indexes as $index) {
            if((int)$index['NON_UNIQUE']!==0 || ($expected[$index['INDEX_NAME']]??null)!==$index['COLUMN_NAME'])return false;
            unset($expected[$index['INDEX_NAME']]);
        }
        return $expected===[];
    }

    private function transaction(int $owner,Closure $action,bool $phone=false): array
    {
        if(Auth::role()!=='RECYCLER' || (int)Auth::id()!==$owner)throw new DomainException('This account cannot be edited.');
        if($phone && !$this->phoneReady())throw new DomainException('Phone editing is currently unavailable. Your existing number is unchanged.');
        $this->db->beginTransaction();
        try {
            $user=$this->account($owner,true);
            if(!$this->eligible($user)) {
                if($phone)$this->query('UPDATE recycler_phone_change_challenges SET invalidated_at=CURRENT_TIMESTAMP WHERE recycler_user_id=? AND verified_at IS NULL AND invalidated_at IS NULL',[$owner]);
                $result=$this->result(false,'Your account is not eligible to edit contact details.','account');
            } else $result=$action($user);
            $this->db->commit();return $result;
        } catch(Throwable $error) {
            if($this->db->inTransaction())$this->db->rollBack();
            throw $error;
        }
    }

    private function audit(int $owner,string $action): void
    {
        $this->query("INSERT INTO audit_logs(actor_user_id,action_type,entity_type,entity_id) VALUES (?,?,'users',?)",[$owner,$action,$owner]);
    }

    public function contact(int $owner,string $name): array
    {
        $name=mb_check_encoding($name,'UTF-8') ? preg_replace('/[\p{Z}]+/u',' ',trim($name)) : $name;
        $validator=new Validator();
        if(!mb_check_encoding($name,'UTF-8') || !$validator->validate(['contact_person'=>$name],['contact_person'=>['required','min:2','max:120']])
            || preg_match('/[\p{Cc}<>]/u',$name) || !preg_match('/\p{L}/u',$name)) {
            return $this->result(false,'Enter a contact name of 2–120 characters without control characters or markup.','contact_person');
        }
        $name=preg_replace('/\s+/u',' ',trim($name));
        return $this->transaction($owner,function(array $user)use($owner,$name):array {
            if($user['full_name']===$name)return $this->result(true,'No changes to save.');
            $this->query('UPDATE users SET full_name=? WHERE user_id=?',[$name,$owner]);
            $this->audit($owner,'RECYCLER_CONTACT_UPDATED');
            return $this->result(true,'Contact person updated.');
        });
    }

    /** Read-only UI state. Bindings/hashes never leave this service. */
    public function state(int $owner,string $context): array
    {
        $user=$this->account($owner);
        $state=['editable'=>$this->eligible($user),'phone_available'=>$this->phoneReady(),'pending'=>null,'other_session'=>false];
        if(!$state['phone_available'])return $state;
        try {$row=$this->query('SELECT * FROM recycler_phone_change_challenges WHERE recycler_user_id=? AND verified_at IS NULL AND invalidated_at IS NULL AND expires_at>CURRENT_TIMESTAMP ORDER BY challenge_id DESC LIMIT 1',[$owner])->fetch(PDO::FETCH_ASSOC);}
        catch(PDOException $error){if($error->getCode()!=='42S02')throw $error;$state['phone_available']=false;return $state;}
        if(!$row || !$state['editable'] || $row['original_mobile_number']!==$user['mobile_number'] || !hash_equals($row['password_state_fingerprint'],hash('sha256',$user['password_hash'])))return $state;
        if(!hash_equals($row['session_context_hash'],hash('sha256',$context))){$state['other_session']=true;return $state;}
        $mobile=$row['proposed_mobile_number'];
        $state['pending']=['masked_mobile'=>substr($mobile,0,4).'••••'.substr($mobile,-3),'expires_at'=>$row['expires_at'],'sent'=>$row['delivery_status']==='SENT'];
        return $state;
    }

    private function active(int $owner): ?array
    {
        return $this->query('SELECT * FROM recycler_phone_change_challenges WHERE recycler_user_id=? AND verified_at IS NULL AND invalidated_at IS NULL ORDER BY challenge_id DESC LIMIT 1 FOR UPDATE',[$owner])->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function validChallenge(array $user,string $context): array
    {
        $row=$this->active((int)$user['user_id']);
        if(!$row)return [null,$this->result(false,'No pending verification. Start again with your password.','otp')];
        if($row['expires_at']<=$this->query('SELECT CURRENT_TIMESTAMP')->fetchColumn()
            || $row['original_mobile_number']!==$user['mobile_number']
            || !hash_equals($row['password_state_fingerprint'],hash('sha256',$user['password_hash']))) {
            $this->invalidate((int)$row['challenge_id']);
            return [null,$this->result(false,'Verification expired or your account credentials changed. Start again with your password.','otp')];
        }
        if(!hash_equals($row['session_context_hash'],hash('sha256',$context)))return [null,$this->result(false,'This verification belongs to another login session.','otp')];
        return [$row,null];
    }

    private function rate(int $owner,string $target): ?array
    {
        $stats=$this->query('SELECT MAX(created_at) AS latest,SUM(created_at>=CURRENT_TIMESTAMP-INTERVAL 1 HOUR) AS hourly,COUNT(*) AS daily FROM recycler_phone_change_challenges WHERE (recycler_user_id=? OR proposed_mobile_number=?) AND created_at>=CURRENT_TIMESTAMP-INTERVAL 1 DAY',[$owner,$target])->fetch(PDO::FETCH_ASSOC);
        $now=$this->query('SELECT CURRENT_TIMESTAMP')->fetchColumn();
        if(($stats['latest']!==null && strtotime($now)-strtotime($stats['latest'])<60) || (int)$stats['hourly']>=5 || (int)$stats['daily']>=10) {
            return $this->result(false,'Please wait before requesting another code. Limits are one per minute, five per hour and ten per day.','mobile_number');
        }
        return null;
    }

    private function create(array $user,string $target,string $context,string $otp,int $attempts=0): int
    {
        $this->query('INSERT INTO recycler_phone_change_challenges(recycler_user_id,original_mobile_number,proposed_mobile_number,otp_hash,session_context_hash,password_state_fingerprint,attempt_count,expires_at) VALUES (?,?,?,?,?,?,?,CURRENT_TIMESTAMP+INTERVAL 5 MINUTE)',
            [$user['user_id'],$user['mobile_number'],$target,password_hash($otp,PASSWORD_DEFAULT),hash('sha256',$context),hash('sha256',$user['password_hash']),$attempts]);
        return (int)$this->db->lastInsertId();
    }

    public function beginPhone(int $owner,string $proposed,string $password,string $context): array
    {
        if(Auth::role()!=='RECYCLER' || (int)Auth::id()!==$owner)throw new DomainException('This account cannot be edited.');
        $retry=($this->limiter ??= new LoginRateLimiter())->consume('phone-reauth:'.($_SERVER['REMOTE_ADDR'] ?? 'cli'),'phone-reauth:'.$owner);
        if($retry>0)return $this->result(false,'Too many password checks. Try again in 15 minutes.','current_password');
        $target=MobileNumber::normalize($proposed);
        if($target===null)return $this->result(false,'Enter a valid Sri Lankan mobile number.','mobile_number');
        $otp=(string)random_int(100000,999999);$id=0;
        $result=$this->transaction($owner,function(array $user)use($owner,$target,$password,$context,$otp,&$id):array {
            if(!password_verify($password,$user['password_hash']))return $this->result(false,'The current password is incorrect.','current_password');
            if($target===$user['mobile_number'])return $this->result(false,'Enter a different mobile number.','mobile_number');
            if($this->query('SELECT user_id FROM users WHERE mobile_number=? FOR UPDATE',[$target])->fetchColumn())return $this->result(false,'This mobile number is not available.','mobile_number');
            $this->query('UPDATE recycler_phone_change_challenges SET invalidated_at=CURRENT_TIMESTAMP WHERE verified_at IS NULL AND invalidated_at IS NULL AND ((recycler_user_id=? AND (expires_at<=CURRENT_TIMESTAMP OR original_mobile_number<>? OR password_state_fingerprint<>?)) OR (proposed_mobile_number=? AND expires_at<=CURRENT_TIMESTAMP))',[$owner,$user['mobile_number'],hash('sha256',$user['password_hash']),$target]);
            if($this->active($owner))return $this->result(false,'A phone change is already awaiting verification. Finish or cancel it in the original session.','mobile_number');
            if($error=$this->rate($owner,$target))return $error;
            $id=$this->create($user,$target,$context,$otp);
            return $this->result(true,'Verification requested.');
        },true);
        return $result['success'] ? $this->deliver($owner,$id,$target,$otp,$context) : $result;
    }

    public function resend(int $owner,string $context): array
    {
        $otp=(string)random_int(100000,999999);$id=0;$target='';
        $result=$this->transaction($owner,function(array $user)use($owner,$context,$otp,&$id,&$target):array {
            [$row,$error]=$this->validChallenge($user,$context);if($error)return $error;
            if($row['delivery_status']!=='SENT')return $this->result(false,'The code is still being sent. Please wait.','otp');
            if($error=$this->rate($owner,$row['proposed_mobile_number']))return $error;
            $target=$row['proposed_mobile_number'];$this->invalidate((int)$row['challenge_id']);
            $id=$this->create($user,$target,$context,$otp,(int)$row['attempt_count']);
            return $this->result(true,'Verification requested.');
        },true);
        return $result['success'] ? $this->deliver($owner,$id,$target,$otp,$context) : $result;
    }

    /** No transaction/user lock is held during network I/O; pending reservations prevent duplicates. */
    private function deliver(int $owner,int $id,string $target,string $otp,string $context): array
    {
        try {($this->send)($target,$otp);}
        catch(Throwable $error) {
            $this->query("UPDATE recycler_phone_change_challenges SET delivery_status='FAILED',invalidated_at=COALESCE(invalidated_at,CURRENT_TIMESTAMP) WHERE challenge_id=? AND delivery_status='PENDING' AND verified_at IS NULL",[$id]);
            return $this->result(false,'The verification SMS could not be sent. Try again later.','mobile_number');
        }
        return $this->transaction($owner,function(array $user)use($id,$context):array {
            [$row,$error]=$this->validChallenge($user,$context);if($error)return $error;
            if((int)$row['challenge_id']!==$id || $row['delivery_status']!=='PENDING')return $this->result(false,'Verification is no longer pending. Start again.','otp');
            $this->query("UPDATE recycler_phone_change_challenges SET delivery_status='SENT',sent_at=CURRENT_TIMESTAMP WHERE challenge_id=?",[$id]);
            return $this->result(true,'Verification code sent. Your current number remains active.');
        },true);
    }

    public function verify(int $owner,string $context,string $otp): array
    {
        return $this->transaction($owner,function(array $user)use($owner,$context,$otp):array {
            [$row,$error]=$this->validChallenge($user,$context);if($error)return $error;
            if($row['delivery_status']!=='SENT')return $this->result(false,'The code has not been delivered yet. Please wait.','otp');
            $id=(int)$row['challenge_id'];$attempts=(int)$row['attempt_count'];
            if($attempts>=5){$this->invalidate($id);return $this->result(false,'Attempt limit reached. Start again with your password.','otp');}
            if(!preg_match('/^[0-9]{6}$/D',$otp) || !password_verify($otp,$row['otp_hash'])) {
                $attempts++;
                $this->query('UPDATE recycler_phone_change_challenges SET attempt_count=? WHERE challenge_id=?',[$attempts,$id]);
                if($attempts>=5)$this->invalidate($id);
                return $this->result(false,$attempts>=5?'Attempt limit reached. Start again with your password.':'Incorrect code. '.(5-$attempts).' attempts remain.','otp');
            }
            try {$this->query('UPDATE users SET mobile_number=?,mobile_verified_at=CURRENT_TIMESTAMP WHERE user_id=?',[$row['proposed_mobile_number'],$owner]);}
            catch(PDOException $error){if((int)($error->errorInfo[1]??0)!==1062)throw $error;$this->invalidate($id);return $this->result(false,'This mobile number is no longer available. Start again.','mobile_number');}
            $this->query('UPDATE recycler_phone_change_challenges SET verified_at=CURRENT_TIMESTAMP WHERE challenge_id=?',[$id]);
            $this->audit($owner,'RECYCLER_PHONE_CHANGED');
            return $this->result(true,'Mobile number updated.');
        },true);
    }

    public function cancel(int $owner,string $context): array
    {
        return $this->transaction($owner,function(array $user)use($context):array {
            [$row,$error]=$this->validChallenge($user,$context);if($error)return $error;
            $this->invalidate((int)$row['challenge_id']);return $this->result(true,'Phone change cancelled. Your current number is unchanged.');
        },true);
    }
}
