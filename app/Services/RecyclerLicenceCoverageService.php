<?php
declare(strict_types=1);

final class RecyclerLicenceCoverageService
{
    private RecyclerLicenceAuthorization $authorization;
    public function __construct(private ?PDO $db=null)
    {
        $this->db??=Database::connection(); $this->authorization=new RecyclerLicenceAuthorization($this->db);
    }

    private function text(string $value,int $max,string $label): string
    {
        $value=trim($value);
        if (!mb_check_encoding($value,'UTF-8') || $value==='' || mb_strlen($value,'UTF-8')>$max) throw new DomainException("$label is required (up to $max characters).");
        return $value;
    }

    /** Called only in an Admin transaction. Snapshots never grant coverage. */
    public function capture(int $owner,int $licenseId,int $version,int $admin): int
    {
        if (!$this->db->inTransaction()) throw new LogicException('A transaction is required.');
        $this->authorization->requireAdmin($admin);
        $license=$this->authorization->currentLicense($owner,true);
        if (!$license || (int)$license['license_id']!==$licenseId || (int)$license['document_version']!==$version) throw new DomainException('The current licence revision changed. Reload before reviewing coverage.');
        $path=(new RecyclerLicenseDocumentValidator())->validatedPath($license['document_path']);
        if ($path===null) throw new DomainException('A valid private licence PDF is required before scope review.');
        $hash=(string)hash_file('sha256',$path);
        $s=$this->db->prepare('SELECT evidence_id,document_sha256 FROM recycler_license_evidence WHERE license_id=? AND document_version=? FOR UPDATE');
        $s->execute([$licenseId,$version]); $existing=$s->fetch();
        if ($existing) {
            if (!hash_equals($existing['document_sha256'],$hash)) throw new DomainException('Document bytes changed without a new revision. Upload and review a new licence document.');
            return (int)$existing['evidence_id'];
        }
        $s=$this->db->prepare('INSERT INTO recycler_license_evidence (license_id,recycler_user_id,document_version,license_number,expiry_date,document_path,document_sha256,captured_by_admin_user_id) VALUES (?,?,?,?,?,?,?,?)');
        $s->execute([$licenseId,$owner,$version,$license['license_number'],$license['expiry_date'],$license['document_path'],$hash,$admin]);
        return (int)$this->db->lastInsertId();
    }

    public function propose(int $owner,int $admin,array $input): int
    {
        foreach (['waste_description','document_waste_code','evidence_reference','licence_conditions'] as $field) {
            if (!is_string($input[$field]??'')) throw new DomainException('Coverage evidence fields must contain text.');
        }
        foreach (['license_id','document_version','category_id'] as $key) if (filter_var($input[$key]??null,FILTER_VALIDATE_INT)===false || (int)$input[$key]<=0) throw new DomainException('Select a valid licence revision and category.');
        $activity=$input['activity_type']??''; $basis=$input['authorization_basis']??''; $risk=$input['covers_high_risk']??'';
        if (!is_string($activity) || !array_key_exists($activity,RecyclerRegistrationService::ACTIVITIES)) throw new DomainException('Select a supported handling activity.');
        if (!in_array($basis,['EPL','SWML','EPL_AND_SWML','OTHER'],true) || !in_array($risk,['0','1',0,1],true)) throw new DomainException('Select valid authorization evidence and high-risk permission.');
        if ((int)$risk===1 && $basis==='EPL') throw new DomainException('EPL alone cannot establish high-risk or Scheduled Waste authorization. Supply appropriate supporting evidence.');
        $description=$this->text((string)($input['waste_description']??''),500,'Waste description');
        $reference=$this->text((string)($input['evidence_reference']??''),500,'Evidence reference');
        $conditions=$this->text((string)($input['licence_conditions']??''),1000,'Licence conditions (or explicit none recorded)');
        $code=trim((string)($input['document_waste_code']??''));
        if (!mb_check_encoding($code,'UTF-8') || mb_strlen($code,'UTF-8')>120) throw new DomainException('Document waste code must not exceed 120 characters.');
        if ($this->db->inTransaction()) throw new LogicException('Coverage proposals own their transaction.');
        $this->db->beginTransaction();
        try {
            $this->authorization->requireAdmin($admin); $this->authorization->lockOwner($owner);
            $license=$this->authorization->currentLicense($owner,true);
            if (!$license || $license['expiry_date']<$this->db->query('SELECT CURRENT_DATE')->fetchColumn()) throw new DomainException('Approve a current, unexpired licence before proposing its coverage.');
            $category=$this->db->prepare("SELECT category_id FROM waste_categories WHERE category_id=? AND category_status='ACTIVE' LOCK IN SHARE MODE");
            $category->execute([(int)$input['category_id']]); if (!$category->fetchColumn()) throw new DomainException('Select an active waste category.');
            $evidence=$this->capture($owner,(int)$input['license_id'],(int)$input['document_version'],$admin);
            $s=$this->db->prepare('INSERT INTO recycler_license_coverage (evidence_id,category_id,activity_type,covers_high_risk,authorization_basis,waste_description,document_waste_code,evidence_reference,licence_conditions,proposed_by_admin_user_id) VALUES (?,?,?,?,?,?,?,?,?,?)');
            $s->execute([$evidence,(int)$input['category_id'],$activity,(int)$risk,$basis,$description,$code===''?null:$code,$reference,$conditions,$admin]);
            $id=(int)$this->db->lastInsertId(); $this->db->commit(); return $id;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if ($error instanceof PDOException && (int)($error->errorInfo[1]??0)===1062) throw new DomainException('This document/category/activity already has a pending coverage proposal.');
            throw $error;
        }
    }

    public function review(int $owner,int $coverage,int $admin,string $decision,string $note): void
    {
        if (!in_array($decision,['VERIFIED','REJECTED','UNCERTAIN','REVOKED'],true)) throw new DomainException('Select a valid coverage decision.');
        $note=$this->text($note,500,'Coverage review notes');
        if ($this->db->inTransaction()) throw new LogicException('Coverage review owns its transaction.');
        $this->db->beginTransaction();
        try {
            $this->authorization->requireAdmin($admin); $this->authorization->lockOwner($owner);
            $row=(new RecyclerLicenceCoverage($this->db))->lockScope($owner,$coverage);
            if (!$row || ($decision==='REVOKED' ? $row['verification_status']!=='VERIFIED' : $row['verification_status']!=='PENDING')) throw new DomainException('Coverage is missing or this decision has already been processed.');
            if ($decision==='VERIFIED') {
                $category=$this->db->prepare("SELECT category_id FROM waste_categories WHERE category_id=? AND category_status='ACTIVE' LOCK IN SHARE MODE");
                $category->execute([$row['category_id']]);
                if (!$category->fetchColumn()) throw new DomainException('Inactive categories cannot receive verified coverage.');
                $evidence=$this->capture($owner,(int)$row['license_id'],(int)$row['document_version'],$admin);
                if ($evidence!==(int)$row['evidence_id']) throw new DomainException('The reviewed evidence changed.');
                $current=$this->authorization->currentLicense($owner,true);
                if ($current['expiry_date']<$this->db->query('SELECT CURRENT_DATE')->fetchColumn()) throw new DomainException('Expired evidence cannot authorize new coverage.');
                $s=$this->db->prepare("SELECT coverage_id FROM recycler_license_coverage WHERE evidence_id=? AND category_id=? AND activity_type=? AND verification_status='VERIFIED' AND superseded_at IS NULL FOR UPDATE");
                $s->execute([$row['evidence_id'],$row['category_id'],$row['activity_type']]);
                foreach ($s->fetchAll() as $old) {
                    $this->db->prepare('UPDATE recycler_license_coverage SET superseded_at=CURRENT_TIMESTAMP WHERE coverage_id=?')->execute([$old['coverage_id']]);
                    $this->audit((int)$old['coverage_id'],$admin,'SUPERSEDED','Superseded by coverage #' . $coverage);
                }
            }
            $s=$this->db->prepare('UPDATE recycler_license_coverage SET verification_status=?,reviewed_by_admin_user_id=?,reviewed_at=CURRENT_TIMESTAMP,review_note=? WHERE coverage_id=? AND verification_status=?');
            $s->execute([$decision,$admin,$note,$coverage,$row['verification_status']]);
            if ($s->rowCount()!==1) throw new RuntimeException('Coverage decision could not be saved.');
            $this->audit($coverage,$admin,$decision,$note); $this->db->commit();
        } catch (Throwable $error) { if ($this->db->inTransaction()) $this->db->rollBack(); throw $error; }
    }

    private function audit(int $coverage,int $admin,string $decision,string $note): void
    {
        $this->db->prepare('INSERT INTO recycler_license_coverage_reviews (coverage_id,decision,admin_user_id,review_note) VALUES (?,?,?,?)')->execute([$coverage,$decision,$admin,$note]);
    }

    public function setLotActivities(int $lot,int $admin,int $expectedVersion,array $activities,string $note): void
    {
        $note=$this->text($note,500,'Required-activity rationale');
        if ($lot<=0 || $expectedVersion<0 || !$activities) throw new DomainException('Select the lot and its actual required activities.');
        foreach ($activities as $activity) if (!is_string($activity) || !array_key_exists($activity,RecyclerRegistrationService::ACTIVITIES)) throw new DomainException('Unsupported required activity.');
        if (count($activities)!==count(array_unique($activities))) throw new DomainException('Duplicate required activity.');
        sort($activities);
        if ($this->db->inTransaction()) throw new LogicException('Lot requirement review owns its transaction.');
        $this->db->beginTransaction();
        try {
            $this->authorization->requireAdmin($admin);
            // Lot first, matching bidding. Never acquire Recycler locks here.
            $s=$this->db->prepare('SELECT e_lot_id FROM e_lots WHERE e_lot_id=? FOR UPDATE'); $s->execute([$lot]); if (!$s->fetchColumn()) throw new DomainException('E-Lot not found.');
            $s=$this->db->prepare('SELECT requirement_version FROM e_lot_authorization_requirements WHERE e_lot_id=? FOR UPDATE'); $s->execute([$lot]); $version=(int)($s->fetchColumn()?:0);
            if ($version!==$expectedVersion) throw new DomainException('E-Lot requirements changed. Reload before reviewing.');
            if ($version===0) $this->db->prepare('INSERT INTO e_lot_authorization_requirements (e_lot_id,reviewed_by_admin_user_id,review_note) VALUES (?,?,?)')->execute([$lot,$admin,$note]);
            else $this->db->prepare('UPDATE e_lot_authorization_requirements SET requirement_version=requirement_version+1,reviewed_by_admin_user_id=?,reviewed_at=CURRENT_TIMESTAMP,review_note=? WHERE e_lot_id=?')->execute([$admin,$note,$lot]);
            $this->db->prepare('DELETE FROM e_lot_required_activities WHERE e_lot_id=?')->execute([$lot]);
            foreach ($activities as $activity) $this->db->prepare('INSERT INTO e_lot_required_activities VALUES (?,?)')->execute([$lot,$activity]);
            $this->db->prepare('INSERT INTO e_lot_authorization_reviews (e_lot_id,requirement_version,required_activities_json,admin_user_id,review_note) VALUES (?,?,?,?,?)')->execute([$lot,$version+1,json_encode($activities,JSON_THROW_ON_ERROR),$admin,$note]);
            $this->db->commit();
        } catch (Throwable $error) { if ($this->db->inTransaction()) $this->db->rollBack(); throw $error; }
    }
}
