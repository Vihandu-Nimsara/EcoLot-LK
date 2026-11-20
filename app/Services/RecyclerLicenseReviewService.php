<?php
declare(strict_types=1);

final class RecyclerLicenseReviewService
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    public function review(
        int $recyclerId,
        int $adminId,
        int $licenseId,
        string $decision,
        string $reviewNote = ''
    ): void {
        if ($recyclerId <= 0 || $adminId <= 0 || $licenseId <= 0) {
            throw new InvalidArgumentException(
                'Invalid recycler, administrator, or license.'
            );
        }

        if (!in_array($decision, ['APPROVE', 'REJECT'], true)) {
            throw new InvalidArgumentException(
                'Invalid license review decision.'
            );
        }

        $reviewNote = trim($reviewNote);

        if ($decision === 'REJECT' && $reviewNote === '') {
            throw new InvalidArgumentException(
                'Rejection reason is required.'
            );
        }

        if (mb_strlen($reviewNote, 'UTF-8') > 500) {
            throw new InvalidArgumentException(
                'Review note is too long.'
            );
        }

        $this->db->beginTransaction();

        try {
            // Lock the parent recycler record first.
            (new AuthorizedRecycler($this->db))->lockPendingReview($recyclerId,$adminId,$decision === 'APPROVE');

            // Lock the latest submitted license.
            $licenseStmt = $this->db->prepare(
                "SELECT
                    license_id,
                    license_status,
                    document_path,
                    (expiry_date >= CURRENT_DATE()) AS is_not_expired
                 FROM recycler_licenses
                 WHERE recycler_user_id = :recycler_id
                 ORDER BY submitted_at DESC, license_id DESC
                 LIMIT 1
                 FOR UPDATE"
            );

            $licenseStmt->execute([
                'recycler_id' => $recyclerId
            ]);

            $license = $licenseStmt->fetch(PDO::FETCH_ASSOC);

            if ($license === false) {
                throw new RuntimeException(
                    'Recycler license not found.'
                );
            }

            if ((int) $license['license_id'] !== $licenseId) {
                throw new RuntimeException(
                    'The license has changed. Please reload the page and review the latest document.'
                );
            }

            if ($license['license_status'] !== 'PENDING') {
                throw new RuntimeException(
                    'License has already been reviewed.'
                );
            }

            if ($decision === 'APPROVE') {

                // Reject expired licenses.
                if ((int) $license['is_not_expired'] !== 1) {
                    throw new RuntimeException(
                        'An expired license cannot be approved.'
                    );
                }

                // Validate the actual PDF document.
                $documentValidator = new RecyclerLicenseDocumentValidator();

                $validatedPath = $documentValidator->validatedPath(
                    $license['document_path'] ?? null
                );

                if ($validatedPath === null) {
                    throw new RuntimeException(
                        'A valid, accessible CEA license PDF is required for approval.'
                    );
                }
            }

            $newStatus = $decision === 'APPROVE'
                ? 'VALID'
                : 'REJECTED';

            $updateStmt = $this->db->prepare(
                "UPDATE recycler_licenses
                 SET license_status = :status,
                     reviewed_by_admin_user_id = :admin_id,
                     reviewed_at = CURRENT_TIMESTAMP,
                     review_note = :review_note
                 WHERE license_id = :license_id
                   AND recycler_user_id = :recycler_id
                   AND license_status = 'PENDING'"
            );

            $updateStmt->execute([
                'status' => $newStatus,
                'admin_id' => $adminId,
                'review_note' => $reviewNote !== ''
                    ? $reviewNote
                    : null,
                'license_id' => $license['license_id'],
                'recycler_id' => $recyclerId
            ]);

            if ($updateStmt->rowCount() !== 1) {
                throw new RuntimeException(
                    'License review could not be completed.'
                );
            }

            $this->db->commit();

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /** Current initial document, fingerprinted rather than inferred from a category label. */
    private function scopeDocument(int $owner): array
    {
        $model = new AuthorizedRecycler($this->db);
        $registration = $model->findByUserId($owner);
        if (($registration['verification_status'] ?? null) === 'PENDING') {
            // Preserve initial review ordering and row locks.
            $s=$this->db->prepare('SELECT * FROM recycler_licenses WHERE recycler_user_id=? ORDER BY submitted_at DESC,license_id DESC LIMIT 1' . ($this->db->inTransaction() ? ' FOR UPDATE' : ''));
            $s->execute([$owner]); $row=$s->fetch(PDO::FETCH_ASSOC);
        } else {
            $selected = $model->currentApprovedLicenseForRecycler($owner);
            $row = $selected ? $selected + ['license_number'=>$selected['license_no'], 'expiry_date'=>$selected['license_expiry']] : null;
        }
        if (!$row || empty($row['reviewed_at']) || empty($row['reviewed_by_admin_user_id']) || $row['license_status']!=='VALID' || $row['expiry_date']<$this->db->query('SELECT CURRENT_DATE')->fetchColumn()) throw new RuntimeException('A current valid, unexpired licence is required.');
        $path=(new RecyclerLicenseDocumentValidator())->validatedPath($row['document_path']);
        if ($path===null) throw new RuntimeException('A valid accessible licence PDF is required.');
        $row['document_sha256']=hash_file('sha256',$path);
        return $row;
    }

    public function requireScope(int $owner, ?int $category, ?string $activity, ?int $risk, bool $final = false): void
    {
        $document=$this->scopeDocument($owner);
        $states=$final ? "('APPROVED')" : "('PENDING','APPROVED')";
        $sql="SELECT 1 FROM recycler_license_scopes s JOIN waste_categories c ON c.category_id=s.category_id
            JOIN recycler_capabilities rc ON rc.category_id=s.category_id AND rc.recycler_user_id=?
            JOIN recycler_authorized_activities a ON a.recycler_user_id=rc.recycler_user_id AND a.activity_type=s.activity_type
            WHERE s.license_id=? AND s.scope_status='VERIFIED' AND c.category_status='ACTIVE'
            AND rc.capability_status IN $states AND a.activity_status IN $states
            AND BINARY s.license_number=BINARY ? AND s.expiry_date=? AND BINARY s.document_path=BINARY ? AND BINARY s.document_sha256=BINARY ?
            AND (COALESCE(?,rc.can_handle_high_risk)=0 OR s.covers_high_risk=1)";
        $params=[$owner,$document['license_id'],$document['license_number'],$document['expiry_date'],$document['document_path'],$document['document_sha256'],$risk];
        if ($category!==null) { $sql.=' AND s.category_id=?'; $params[]=$category; }
        if ($activity!==null) { $sql.=' AND s.activity_type=?'; $params[]=$activity; }
        $s=$this->db->prepare($sql.' LIMIT 1');$s->execute($params);
        if (!$s->fetchColumn()) throw new RuntimeException('Verify matching current licence scope for this category, activity and high-risk permission first.');
    }

    /** Operational scope check for a caller-supplied, reviewed activity set; not a bid engine. */
    public function requireApprovedScope(int $owner, int $category, array $activities, bool $highRisk): void
    {
        $s = $this->db->prepare("SELECT 1 FROM authorized_recyclers r JOIN users u ON u.user_id=r.user_id
            JOIN recycler_capabilities c ON c.recycler_user_id=r.user_id
            WHERE r.user_id=? AND r.verification_status='VERIFIED' AND u.role='RECYCLER'
              AND u.account_status='ACTIVE' AND c.category_id=? AND c.capability_status='APPROVED'
              AND (?=0 OR c.can_handle_high_risk=1)");
        $s->execute([$owner,$category,(int)$highRisk]);
        if (!$s->fetchColumn() || !$activities) throw new RuntimeException('Approved permissions and reviewed activities are required.');
        foreach ($activities as $activity) {
            if (!is_string($activity) || !array_key_exists($activity, RecyclerRegistrationService::ACTIVITIES)) {
                throw new InvalidArgumentException('A valid reviewed activity is required.');
            }
            $this->requireScope($owner,$category,$activity,(int)$highRisk,true);
        }
    }

    /** Record evidence only; a pending proposal grants no authorization. */
    public function proposeScope(int $owner, int $admin, array $input, bool $renewal = false, ?int $capabilityRequest = null): void
    {
        $category=filter_var($input['category_id']??null,FILTER_VALIDATE_INT);
        $activity=$input['activity_type']??'';$basis=$input['authorization_basis']??'';$risk=$input['covers_high_risk']??'';
        if (!$category || $category<1 || !is_string($activity) || !array_key_exists($activity,RecyclerRegistrationService::ACTIVITIES)
            || !in_array($basis,['EPL','SWML','EPL_AND_SWML','OTHER'],true) || !in_array($risk,['0','1'],true)) throw new InvalidArgumentException('Select valid category, activity, evidence basis and risk coverage.');
        if ($risk==='1' && $basis==='EPL') throw new InvalidArgumentException('EPL alone does not establish high-risk authorization.');
        foreach (['waste_description'=>500,'evidence_reference'=>500,'licence_conditions'=>1000] as $field=>$max) {
            if (!is_string($input[$field]??null) || !mb_check_encoding($input[$field],'UTF-8') || trim($input[$field])==='' || mb_strlen(trim($input[$field]),'UTF-8')>$max) throw new InvalidArgumentException('Complete the waste description, supporting reference and conditions within the displayed limits.');
        }
        $code=$input['waste_code']??'';
        if (!is_string($code) || !mb_check_encoding($code,'UTF-8') || mb_strlen($code,'UTF-8')>120) throw new InvalidArgumentException('Waste code must be at most 120 characters.');
        $this->db->beginTransaction();
        try {
            $change = null;
            if ($capabilityRequest !== null) $change=(new RecyclerCapabilityChangeService($this->db))->lockScopeReview($owner,$admin,$capabilityRequest);
            elseif ($renewal) (new RecyclerLicenseRenewalService($this->db))->lockScopeReview($owner,$admin);
            else (new AuthorizedRecycler($this->db))->lockPendingReview($owner,$admin,true);
            $doc=$this->scopeDocument($owner);
            if ((string)$doc['license_id']!==($input['license_id']??'') || !is_string($input['document_sha256']??null) || !hash_equals($doc['document_sha256'],$input['document_sha256'])) throw new RuntimeException('The submitted licence changed. Reload before proposing scope.');
            if ($change !== null) {
                if ((int)$change['category_id'] !== $category) throw new RuntimeException('Scope category must match the selected capability request.');
                $s=$this->db->prepare("SELECT 1 FROM waste_categories c JOIN recycler_authorized_activities a ON a.recycler_user_id=? WHERE c.category_id=? AND c.category_status='ACTIVE' AND a.activity_type=? AND a.activity_status='APPROVED'");
            } else {
                $s=$this->db->prepare("SELECT 1 FROM recycler_capabilities rc JOIN waste_categories c ON c.category_id=rc.category_id JOIN recycler_authorized_activities a ON a.recycler_user_id=rc.recycler_user_id WHERE rc.recycler_user_id=? AND rc.category_id=? AND c.category_status='ACTIVE' AND rc.capability_status IN ('PENDING','APPROVED') AND a.activity_type=? AND a.activity_status IN ('PENDING','APPROVED')");
            }
            $s->execute([$owner,$category,$activity]); if (!$s->fetchColumn()) throw new RuntimeException($change !== null ? 'Scope must match the requested category and an approved activity.' : 'Scope must match a requested active category and activity.');
            if ($renewal) {
                $approved=$this->db->prepare("SELECT 1 FROM recycler_capabilities c JOIN recycler_authorized_activities a ON a.recycler_user_id=c.recycler_user_id WHERE c.recycler_user_id=? AND c.category_id=? AND c.capability_status='APPROVED' AND a.activity_type=? AND a.activity_status='APPROVED'");
                $approved->execute([$owner,$category,$activity]);
                if (!$approved->fetchColumn()) throw new RuntimeException('Renewal scope must match existing approved capabilities and activities.');
            }
            $s=$this->db->prepare("SELECT scope_id FROM recycler_license_scopes WHERE license_id=? AND category_id=? AND activity_type=? AND (scope_status='PENDING' OR (scope_status='VERIFIED' AND covers_high_risk=? AND document_sha256=?))");
            $s->execute([$doc['license_id'],$category,$activity,(int)$risk,$doc['document_sha256']]);if ($s->fetchColumn()) throw new RuntimeException('This document already has the same verified scope, or a proposal is awaiting review.');
            $s=$this->db->prepare('INSERT INTO recycler_license_scopes (license_id,category_id,activity_type,covers_high_risk,authorization_basis,waste_description,waste_code,evidence_reference,licence_conditions,license_number,expiry_date,document_path,document_sha256,proposed_by_admin_user_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $s->execute([$doc['license_id'],$category,$activity,(int)$risk,$basis,trim($input['waste_description']),trim($code)?:null,trim($input['evidence_reference']),trim($input['licence_conditions']),$doc['license_number'],$doc['expiry_date'],$doc['document_path'],$doc['document_sha256'],$admin]);
            $this->db->commit();
        } catch (Throwable $error) { if ($this->db->inTransaction()) $this->db->rollBack();throw $error; }
    }

    public function reviewScope(int $owner, int $scope, int $admin, string $decision, string $note, bool $inspected, bool $renewal = false, ?int $capabilityRequest = null): void
    {
        $note=trim($note);
        if (!in_array($decision,['VERIFIED','REJECTED'],true) || !mb_check_encoding($note,'UTF-8') || $note==='' || mb_strlen($note,'UTF-8')>500) throw new InvalidArgumentException('Select a decision and provide a review note of 1–500 characters.');
        if ($decision==='VERIFIED' && !$inspected) throw new InvalidArgumentException('Confirm inspection of the exact licence and supporting evidence.');
        $this->db->beginTransaction();
        try {
            $change = null;
            if ($capabilityRequest !== null) $change=(new RecyclerCapabilityChangeService($this->db))->lockScopeReview($owner,$admin,$capabilityRequest);
            elseif ($renewal) (new RecyclerLicenseRenewalService($this->db))->lockScopeReview($owner,$admin);
            else (new AuthorizedRecycler($this->db))->lockPendingReview($owner,$admin,$decision==='VERIFIED');
            $s=$this->db->prepare("SELECT s.* FROM recycler_license_scopes s JOIN recycler_licenses l ON l.license_id=s.license_id WHERE s.scope_id=? AND l.recycler_user_id=? FOR UPDATE");
            $s->execute([$scope,$owner]);$row=$s->fetch(PDO::FETCH_ASSOC);
            if (!$row || $row['scope_status']!=='PENDING') throw new RuntimeException('Scope is missing or already reviewed.');
            if ($change !== null && (int)$change['category_id'] !== (int)$row['category_id']) throw new RuntimeException('Scope category does not match this capability request.');
            if ($decision==='VERIFIED') {
                $doc=$this->scopeDocument($owner);
                foreach (['license_id','license_number','expiry_date','document_path','document_sha256'] as $field) if ((string)$row[$field] !== (string)$doc[$field]) throw new RuntimeException('The licence or its document changed. Reject this stale proposal.');
                $s=$this->db->prepare("SELECT category_id FROM waste_categories WHERE category_id=? AND category_status='ACTIVE' LOCK IN SHARE MODE");$s->execute([$row['category_id']]);if (!$s->fetchColumn()) throw new RuntimeException('The category is no longer active.');
            }
            $s=$this->db->prepare("UPDATE recycler_license_scopes SET scope_status=?,reviewed_by_admin_user_id=?,reviewed_at=CURRENT_TIMESTAMP,review_note=? WHERE scope_id=? AND scope_status='PENDING'");
            $s->execute([$decision,$admin,$note,$scope]);if ($s->rowCount()!==1) throw new RuntimeException('Scope decision could not be saved.');
            $this->db->commit();
        } catch (Throwable $error) { if ($this->db->inTransaction()) $this->db->rollBack();throw $error; }
    }
}