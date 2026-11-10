<?php
declare(strict_types=1);

/** Central scope checks. The same predicates are embedded in the new bid triggers. */
final class RecyclerLicenceAuthorization
{
    public function __construct(private PDO $db) {}

    /** Expressions are internal SQL identifiers only, never request input. */
    public static function scopeSql(string $owner,string $category,string $activity,string $risk): string
    {
        return "EXISTS (SELECT 1 FROM recycler_license_coverage scope
            JOIN recycler_license_evidence evidence ON evidence.evidence_id=scope.evidence_id
            JOIN recycler_licenses source ON source.license_id=evidence.license_id AND source.recycler_user_id=evidence.recycler_user_id
            JOIN waste_categories scope_category ON scope_category.category_id=scope.category_id
            WHERE evidence.recycler_user_id=$owner AND scope.category_id=$category AND scope.activity_type=$activity
                AND scope_category.category_status='ACTIVE' AND scope.verification_status='VERIFIED' AND scope.superseded_at IS NULL
                AND (($risk)=0 OR scope.covers_high_risk=1)
                AND source.license_status='VALID' AND source.expiry_date>=CURRENT_DATE
                AND source.document_version=evidence.document_version AND source.document_path=evidence.document_path
                AND source.license_number=evidence.license_number AND source.expiry_date=evidence.expiry_date
                AND source.license_id=(SELECT current_source.license_id FROM recycler_licenses current_source
                    WHERE current_source.recycler_user_id=$owner AND current_source.license_status='VALID'
                    ORDER BY current_source.submitted_at DESC,current_source.license_id DESC LIMIT 1))";
    }

    public static function lotSql(string $owner,string $lot='l'): string
    {
        $risk="EXISTS (SELECT 1 FROM e_lot_items auth_item
            JOIN collection_record_items auth_record ON auth_record.record_item_id=auth_item.record_item_id
            JOIN request_items auth_request ON auth_request.request_item_id=auth_record.request_item_id
            WHERE auth_item.e_lot_id=$lot.e_lot_id AND (auth_request.applied_risk_level='HIGH' OR auth_record.actual_risk_level='HIGH'))";
        $scope=self::scopeSql($owner,"$lot.category_id",'required_activity.activity_type',$risk);
        return "EXISTS (SELECT 1 FROM users auth_user JOIN authorized_recyclers auth_recycler ON auth_recycler.user_id=auth_user.user_id
                WHERE auth_user.user_id=$owner AND auth_user.role='RECYCLER' AND auth_user.account_status='ACTIVE' AND auth_recycler.verification_status='VERIFIED')
            AND EXISTS (SELECT 1 FROM recycler_capabilities auth_cap WHERE auth_cap.recycler_user_id=$owner AND auth_cap.category_id=$lot.category_id
                AND auth_cap.capability_status='APPROVED' AND (NOT ($risk) OR auth_cap.can_handle_high_risk=1))
            AND EXISTS (SELECT 1 FROM e_lot_authorization_requirements WHERE e_lot_id=$lot.e_lot_id)
            AND EXISTS (SELECT 1 FROM e_lot_required_activities WHERE e_lot_id=$lot.e_lot_id)
            AND NOT EXISTS (SELECT 1 FROM e_lot_required_activities required_activity WHERE required_activity.e_lot_id=$lot.e_lot_id AND (
                NOT EXISTS (SELECT 1 FROM recycler_authorized_activities approved_activity WHERE approved_activity.recycler_user_id=$owner
                    AND approved_activity.activity_type=required_activity.activity_type AND approved_activity.activity_status='APPROVED')
                OR NOT ($scope)))";
    }

    public function requireAdmin(int $admin): void
    {
        if ($admin <= 0 || Auth::role() !== 'ADMIN' || (int) Auth::id() !== $admin) throw new DomainException('An authenticated Administrator is required.');
        $s = $this->db->prepare("SELECT a.user_id FROM administrators a JOIN users u ON u.user_id=a.user_id
            WHERE a.user_id=? AND u.role='ADMIN' AND u.account_status='ACTIVE' LOCK IN SHARE MODE");
        $s->execute([$admin]);
        if (!$s->fetchColumn()) throw new DomainException('This Administrator account is not active.');
    }

    public function lockOwner(int $owner): array
    {
        if (!$this->db->inTransaction()) throw new LogicException('Authorization requires a transaction.');
        (new User($this->db))->lockForVerification($owner);
        $s=$this->db->prepare('SELECT u.role,u.account_status,u.mobile_verified_at,r.verification_status
            FROM users u JOIN authorized_recyclers r ON r.user_id=u.user_id WHERE u.user_id=? FOR UPDATE');
        $s->execute([$owner]); $row=$s->fetch();
        if (!$row || $row['role']!=='RECYCLER') throw new DomainException('Recycler not found.');
        return $row;
    }

    public function currentLicense(int $owner, bool $lock = false): ?array
    {
        $s=$this->db->prepare("SELECT * FROM recycler_licenses WHERE recycler_user_id=? AND license_status='VALID'
            ORDER BY submitted_at DESC,license_id DESC LIMIT 1" . ($lock ? ' FOR UPDATE' : ''));
        $s->execute([$owner]); return $s->fetch() ?: null;
    }

    public function requireCurrentEvidence(int $owner): array
    {
        $license=$this->currentLicense($owner,$this->db->inTransaction());
        if (!$license || $license['expiry_date'] < $this->db->query('SELECT CURRENT_DATE')->fetchColumn()) throw new DomainException('A current valid, unexpired licence is required.');
        $s=$this->db->prepare('SELECT * FROM recycler_license_evidence WHERE license_id=? AND document_version=?');
        $s->execute([$license['license_id'],$license['document_version']]); $evidence=$s->fetch();
        if (!$evidence) throw new DomainException('The current licence document requires scope verification.');
        $path=(new RecyclerLicenseDocumentValidator())->validatedPath($license['document_path']);
        if ($path===null || !hash_equals($evidence['document_sha256'],(string)hash_file('sha256',$path))) throw new DomainException('The licence document is missing or changed. Review a new document revision.');
        return $evidence;
    }

    public function requireCapability(int $owner,int $category,int $highRisk,bool $initial=false): void
    {
        $this->requireCurrentEvidence($owner);
        $states=$initial ? "('PENDING','APPROVED')" : "('APPROVED')";
        $scope=self::scopeSql('a.recycler_user_id',':category','a.activity_type',':risk');
        $s=$this->db->prepare("SELECT 1 FROM recycler_authorized_activities a WHERE a.recycler_user_id=:owner
            AND a.activity_status IN $states AND $scope LIMIT 1");
        $s->execute(['owner'=>$owner,'category'=>$category,'risk'=>$highRisk]);
        if (!$s->fetchColumn()) throw new DomainException('Verify current licence coverage for this category, a requested handling activity, and the required high-risk permission first.');
    }

    public function requireActivity(int $owner,string $activity,bool $initial=false): void
    {
        $this->requireCurrentEvidence($owner);
        $states=$initial ? "('PENDING','APPROVED')" : "('APPROVED')";
        $scope=self::scopeSql('cap.recycler_user_id','cap.category_id',':activity','cap.can_handle_high_risk');
        $s=$this->db->prepare("SELECT 1 FROM recycler_capabilities cap WHERE cap.recycler_user_id=:owner
            AND cap.capability_status IN $states AND $scope LIMIT 1");
        $s->execute(['owner'=>$owner,'activity'=>$activity]);
        if (!$s->fetchColumn()) throw new DomainException('Verify current licence coverage for this activity and an applicable requested category first.');
    }

    public function requireFinalApproval(int $owner): void
    {
        $user=$this->lockOwner($owner);
        if ($user['account_status']!=='ACTIVE' || $user['mobile_verified_at']===null) throw new DomainException('Mobile verification and an active account are required before final approval.');
        $s=$this->db->prepare("SELECT category_id,can_handle_high_risk FROM recycler_capabilities WHERE recycler_user_id=? AND capability_status='APPROVED' FOR UPDATE");
        $s->execute([$owner]); $caps=$s->fetchAll();
        $s=$this->db->prepare("SELECT activity_type FROM recycler_authorized_activities WHERE recycler_user_id=? AND activity_status='APPROVED' FOR UPDATE");
        $s->execute([$owner]); $activities=$s->fetchAll();
        if (!$caps || !$activities) throw new DomainException('Approved capabilities and activities with verified licence coverage are required.');
        foreach ($caps as $cap) $this->requireCapability($owner,(int)$cap['category_id'],(int)$cap['can_handle_high_risk']);
        foreach ($activities as $activity) $this->requireActivity($owner,$activity['activity_type']);
    }

    public function requireBid(int $owner,int $lot): void
    {
        if (Auth::role()!=='RECYCLER' || (int)Auth::id()!==$owner) throw new DomainException('An authenticated Recycler is required.');
        $this->requireCurrentEvidence($owner);
        $predicate=self::lotSql('auth_owner.user_id');
        $s=$this->db->prepare("SELECT 1 FROM e_lots l JOIN users auth_owner ON auth_owner.user_id=:owner WHERE l.e_lot_id=:lot AND ($predicate)");
        $s->execute(['owner'=>$owner,'lot'=>$lot]);
        if (!$s->fetchColumn()) throw new DomainException('Current verified licence coverage and approved activities must cover every activity required by this E-Lot.');
    }
}
