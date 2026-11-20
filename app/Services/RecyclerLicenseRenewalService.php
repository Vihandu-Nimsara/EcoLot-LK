<?php
declare(strict_types=1);

/** Post-registration requests only; initial approval and canonical scope engines remain separate. */
final class RecyclerLicenseRenewalService
{
    private PDO $db;
    private Closure $isUpload;

    public function __construct(?PDO $db = null, ?Closure $isUpload = null)
    {
        $this->db = $db ?? Database::connection();
        $this->isUpload = $isUpload ?? Closure::fromCallable('is_uploaded_file');
    }

    private function query(string $sql, array $args = []): PDOStatement
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($args);
        return $statement;
    }

    public function available(): bool
    {
        $columns = $this->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='recycler_license_update_requests' AND COLUMN_NAME IN ('document_sha256','current_identity_sha256','current_document_sha256','explanation')")->fetchAll(PDO::FETCH_COLUMN);
        return count($columns) === 4
            && (int)$this->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='recycler_license_revisions' AND COLUMN_NAME IN ('previous_document_sha256','previous_document_version')")->fetchColumn() === 2
            && (bool)$this->query("SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='recycler_license_update_requests' AND INDEX_NAME='uq_recycler_license_one_pending' AND NON_UNIQUE=0")->fetchColumn();
    }

    private function lockOwner(int $owner, bool $approving = true): array
    {
        (new User($this->db))->lockForVerification($owner);
        $row = $this->query('SELECT r.*,u.role,u.account_status,u.mobile_verified_at FROM authorized_recyclers r JOIN users u ON u.user_id=r.user_id WHERE r.user_id=? FOR UPDATE', [$owner])->fetch(PDO::FETCH_ASSOC);
        if (!$row || $row['role'] !== 'RECYCLER' || !in_array($row['verification_status'], $approving ? ['VERIFIED'] : ['VERIFIED','SUSPENDED'], true)
            || ($approving && ($row['account_status'] !== 'ACTIVE' || $row['mobile_verified_at'] === null))) {
            throw new DomainException('An active, mobile-verified and approved Recycler account is required.');
        }
        return $row;
    }

    private function current(array $owner): ?array
    {
        if ($owner['current_license_id'] === null) return null;
        $row = $this->query('SELECT * FROM recycler_licenses WHERE license_id=? AND recycler_user_id=? FOR UPDATE', [$owner['current_license_id'],$owner['user_id']])->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new DomainException('The selected licence is unavailable. Administrator review is required.');
        return $row;
    }

    private function documentHash(?array $license): ?string
    {
        $path = (new RecyclerLicenseDocumentValidator())->validatedPath($license['document_path'] ?? null);
        return $path === null ? null : hash_file('sha256', $path);
    }

    private function identity(?array $license): string
    {
        return hash('sha256', json_encode($license, JSON_THROW_ON_ERROR));
    }

    public function requests(int $owner): array
    {
        try {
            $requests = $this->query('SELECT request_id,current_license_id,requested_license_number,requested_expiry_date,document_path,document_sha256,explanation,request_status,submitted_at,reviewed_at,review_note,reviewed_by_admin_user_id FROM recycler_license_update_requests WHERE recycler_user_id=? ORDER BY request_id DESC', [$owner])->fetchAll(PDO::FETCH_ASSOC);
            foreach ($requests as &$request) $request['previous_document_available'] = $request['request_status'] === 'APPROVED' && $this->document($owner,(int)$request['request_id'],true) !== null;
            unset($request);
            return $requests;
        } catch (PDOException $error) {
            if (!in_array($error->getCode(), ['42S02','42S22'], true)) throw $error;
            return [];
        }
    }

    /** Hash-bound request and archived PDFs; ownership is always in the SQL predicate. */
    public function document(int $owner, int $request, bool $previous = false): ?string
    {
        if (!$this->available()) return null;
        $row = $previous
            ? $this->query('SELECT v.previous_document_path AS document_path,v.previous_document_sha256 AS document_sha256 FROM recycler_license_revisions v JOIN recycler_license_update_requests q ON q.request_id=v.update_request_id JOIN recycler_licenses l ON l.license_id=v.license_id WHERE q.recycler_user_id=? AND q.request_id=? AND l.recycler_user_id=q.recycler_user_id', [$owner,$request])->fetch(PDO::FETCH_ASSOC)
            : $this->query('SELECT document_path,document_sha256 FROM recycler_license_update_requests WHERE recycler_user_id=? AND request_id=?', [$owner,$request])->fetch(PDO::FETCH_ASSOC);
        if (!$row || !$row['document_sha256']) return null;
        $path = (new RecyclerLicenseDocumentValidator())->validatedPath($row['document_path']);
        return $path !== null && hash_equals($row['document_sha256'], (string)hash_file('sha256', $path)) ? $path : null;
    }

    /** Existing initial PDFs retain their contract; renewed PDFs must match their immutable request. */
    public function currentDocument(int $owner, ?array $license): ?string
    {
        $path = (new RecyclerLicenseDocumentValidator())->validatedPath($license['document_path'] ?? null);
        if ($path === null) return null;
        try {
            $row = $this->query("SELECT document_sha256 FROM recycler_license_update_requests WHERE recycler_user_id=? AND request_status='APPROVED' AND current_license_id=? AND BINARY document_path=BINARY ? ORDER BY request_id DESC LIMIT 1", [$owner,$license['license_id'],$license['document_path']])->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $error) {
            if (!in_array($error->getCode(),['42S02','42S22'],true)) throw $error;
            return $path; // Preserve pre-renewal initial/legacy PDF reads before the additive migration.
        }
        return $row && $row['document_sha256'] !== null
            ? (hash_equals($row['document_sha256'],(string)hash_file('sha256',$path)) ? $path : null) : $path;
    }

    public function canReviewScope(int $owner): bool
    {
        if (!$this->available()) return false;
        $license = (new AuthorizedRecycler($this->db))->currentApprovedLicenseForRecycler($owner);
        if (!$license || $license['license_expiry'] < $this->query('SELECT CURRENT_DATE')->fetchColumn() || $this->currentDocument($owner,$license) === null) return false;
        return (bool)$this->query("SELECT 1 FROM recycler_license_update_requests q JOIN authorized_recyclers r ON r.user_id=q.recycler_user_id WHERE q.recycler_user_id=? AND r.verification_status='VERIFIED' AND q.request_status='APPROVED' AND q.current_license_id=r.current_license_id AND BINARY q.document_path=BINARY ? AND BINARY q.requested_license_number=BINARY ? AND q.requested_expiry_date=? LIMIT 1", [$owner,$license['document_path'],$license['license_no'],$license['license_expiry']])->fetchColumn();
    }

    /** Separate VERIFIED-owner context for the existing canonical scope review methods. */
    public function lockScopeReview(int $owner, int $admin): void
    {
        if (!$this->db->inTransaction()) throw new LogicException('Scope review requires a transaction.');
        if (Auth::role() !== 'ADMIN' || (int)Auth::id() !== $admin || !$this->query("SELECT a.user_id FROM administrators a JOIN users u ON u.user_id=a.user_id WHERE a.user_id=? AND u.role='ADMIN' AND u.account_status='ACTIVE' LOCK IN SHARE MODE", [$admin])->fetchColumn()) throw new DomainException('An active Administrator is required.');
        $this->current($this->lockOwner($owner));
        if (!$this->canReviewScope($owner)) throw new DomainException('An approved current renewal document is required for this scope review.');
    }

    public function submit(int $owner, array $input, array $upload): int
    {
        if (Auth::role() !== 'RECYCLER' || (int)Auth::id() !== $owner) throw new DomainException('Only the authenticated Recycler can submit an update.');
        if (!$this->available()) throw new DomainException('Licence updates are currently unavailable. Your approved licence is unchanged.');
        $number = trim(is_string($input['license_number'] ?? null) ? $input['license_number'] : '');
        $expiry = is_string($input['expiry_date'] ?? null) ? $input['expiry_date'] : '';
        $note = trim(is_string($input['explanation'] ?? null) ? $input['explanation'] : '');
        if (!mb_check_encoding($number,'UTF-8') || mb_strlen($number) < 2 || mb_strlen($number) > 100 || preg_match('/[\p{Cc}<>]/u', $number)) throw new InvalidArgumentException('Enter a licence number of 2–100 characters.');
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $expiry);
        if (!$date || $date->format('Y-m-d') !== $expiry || $expiry < $this->query('SELECT CURRENT_DATE')->fetchColumn()) throw new InvalidArgumentException('Enter a valid, unexpired licence expiry date.');
        if (!mb_check_encoding($note,'UTF-8') || mb_strlen($note) > 500 || preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', $note)) throw new InvalidArgumentException('The explanation must be at most 500 characters.');
        $tmp = $upload['tmp_name'] ?? null;
        if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_string($tmp) || !($this->isUpload)($tmp)
            || strtolower(pathinfo((string)($upload['name'] ?? ''),PATHINFO_EXTENSION)) !== 'pdf') throw new InvalidArgumentException('Upload a valid PDF document, at most 5 MB.');
        $size = filesize($tmp);
        if ($size === false || $size < 5 || $size > 5*1024*1024) throw new InvalidArgumentException('The PDF must be at most 5 MB.');
        $stored = null;
        $this->db->beginTransaction();
        try {
            $account = $this->lockOwner($owner);
            $current = $this->current($account);
            if ($this->query("SELECT request_id FROM recycler_license_update_requests WHERE recycler_user_id=? AND request_status='PENDING' FOR UPDATE", [$owner])->fetchColumn()) throw new DomainException('A licence update is already awaiting review.');
            if ($this->query('SELECT license_id FROM recycler_licenses WHERE license_number=? AND license_id<>? LIMIT 1', [$number,$current['license_id'] ?? 0])->fetchColumn()) throw new DomainException('This licence number is already registered. Administrator reconciliation is required.');
            $relative = 'uploads/recycler-licenses/'.bin2hex(random_bytes(24)).'.pdf';
            $destination = APP_ROOT.'/storage/'.$relative;
            $out = fopen($destination,'xb');
            if ($out === false) throw new RuntimeException('The licence PDF could not be stored.');
            $stored = $destination;
            try {
                if (!chmod($stored,0600)) throw new RuntimeException('The PDF could not be secured.');
                $in = fopen($tmp,'rb');
                if ($in === false) throw new RuntimeException('The uploaded PDF could not be read.');
                try { if (stream_copy_to_stream($in,$out) !== $size) throw new RuntimeException('The uploaded PDF could not be stored.'); }
                finally { fclose($in); }
            } finally { fclose($out); }
            if ((new RecyclerLicenseDocumentValidator())->validatedPath($relative) === null) throw new InvalidArgumentException('The document must contain a valid PDF signature, MIME type and end marker.');
            $hash = hash_file('sha256',$stored);
            if ($current && ($this->documentHash($current) === $hash || $this->query('SELECT 1 FROM recycler_license_scopes WHERE license_id=? AND document_sha256=? LIMIT 1',[$current['license_id'],$hash])->fetchColumn())) throw new DomainException('This PDF is already recorded for this licence. Upload the updated licence document.');
            if (!chmod($stored,0440)) throw new RuntimeException('The PDF could not be secured.');
            $this->query('INSERT INTO recycler_license_update_requests(recycler_user_id,current_license_id,requested_license_number,requested_expiry_date,document_path,document_sha256,current_identity_sha256,current_document_sha256,explanation) VALUES(?,?,?,?,?,?,?,?,?)', [$owner,$current['license_id'] ?? null,$number,$expiry,$relative,$hash,$this->identity($current),$this->documentHash($current),$note ?: null]);
            $id = (int)$this->db->lastInsertId();
            $this->audit($owner,$id,'RECYCLER_LICENCE_UPDATE_SUBMITTED');
            $this->db->commit();
            return $id;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if ($stored !== null && is_file($stored)) unlink($stored);
            throw $error;
        }
    }

    private function audit(int $actor, int $request, string $action): void
    {
        $this->query("INSERT INTO audit_logs(actor_user_id,action_type,entity_type,entity_id) VALUES(?,?,'recycler_license_update_requests',?)", [$actor,$action,$request]);
    }

    public function review(int $owner, int $admin, int $request, string $decision, string $note): void
    {
        if (Auth::role() !== 'ADMIN' || (int)Auth::id() !== $admin) throw new DomainException('An authenticated Administrator is required.');
        $note = trim($note);
        if (!in_array($decision,['APPROVE','REJECT'],true) || !mb_check_encoding($note,'UTF-8') || mb_strlen($note)>500 || ($decision==='REJECT' && (mb_strlen($note)<5 || !preg_match('/\p{L}/u',$note)))) throw new InvalidArgumentException('Select a decision. Rejection requires a meaningful reason of 5–500 characters.');
        if (!$this->available()) throw new DomainException('Licence updates are currently unavailable.');
        $this->db->beginTransaction();
        try {
            if (!$this->query("SELECT a.user_id FROM administrators a JOIN users u ON u.user_id=a.user_id WHERE a.user_id=? AND u.role='ADMIN' AND u.account_status='ACTIVE' LOCK IN SHARE MODE", [$admin])->fetchColumn()) throw new DomainException('An active Administrator is required.');
            $account = $this->lockOwner($owner,$decision==='APPROVE');
            $current = $this->current($account);
            $row = $this->query('SELECT * FROM recycler_license_update_requests WHERE request_id=? AND recycler_user_id=? FOR UPDATE', [$request,$owner])->fetch(PDO::FETCH_ASSOC);
            if (!$row || $row['request_status'] !== 'PENDING') throw new DomainException('This update is missing or already reviewed. Reload the page.');
            if ($decision === 'APPROVE') {
                if (!$row['document_sha256'] || !$row['current_identity_sha256']) throw new DomainException('This legacy request lacks a verifiable submission snapshot. Reject it and request a new submission.');
                if (($current['license_id'] ?? null) != $row['current_license_id'] || !hash_equals($row['current_identity_sha256'],$this->identity($current)) || $row['current_document_sha256'] !== $this->documentHash($current)) throw new DomainException('The approved licence changed after submission. Reject this stale update and request a new one.');
                if ($row['requested_expiry_date'] < $this->query('SELECT CURRENT_DATE')->fetchColumn() || $this->document($owner,$request) === null) throw new DomainException('Approval requires an unexpired licence and the unchanged submitted PDF.');
                if ($current) {
                    $this->query('INSERT INTO recycler_license_revisions(license_id,update_request_id,previous_license_number,previous_expiry_date,previous_document_path,previous_license_status,previous_submitted_at,previous_reviewed_by_admin_user_id,previous_reviewed_at,previous_review_note,archived_by_admin_user_id,previous_document_sha256,previous_document_version) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)', [$current['license_id'],$request,$current['license_number'],$current['expiry_date'],$current['document_path'],$current['license_status'],$current['submitted_at'],$current['reviewed_by_admin_user_id'],$current['reviewed_at'],$current['review_note'],$admin,$this->documentHash($current),$current['document_version'] ?? null]);
                    $licenseId = (int)$current['license_id'];
                    $this->query("UPDATE recycler_licenses SET license_number=?,expiry_date=?,document_path=?,license_status='VALID',submitted_at=?,reviewed_by_admin_user_id=?,reviewed_at=CURRENT_TIMESTAMP,review_note=? WHERE license_id=? AND recycler_user_id=?", [$row['requested_license_number'],$row['requested_expiry_date'],$row['document_path'],$row['submitted_at'],$admin,$note ?: null,$licenseId,$owner]);
                } else {
                    $this->query("INSERT INTO recycler_licenses(recycler_user_id,license_number,expiry_date,document_path,license_status,submitted_at,reviewed_by_admin_user_id,reviewed_at,review_note) VALUES(?,?,?,?,'VALID',?,?,CURRENT_TIMESTAMP,?)", [$owner,$row['requested_license_number'],$row['requested_expiry_date'],$row['document_path'],$row['submitted_at'],$admin,$note ?: null]);
                    $licenseId = (int)$this->db->lastInsertId();
                }
                $this->query('UPDATE authorized_recyclers SET current_license_id=? WHERE user_id=?', [$licenseId,$owner]);
                $this->query('UPDATE recycler_license_update_requests SET current_license_id=? WHERE request_id=?', [$licenseId,$request]);
            }
            $this->query('UPDATE recycler_license_update_requests SET request_status=?,reviewed_by_admin_user_id=?,reviewed_at=CURRENT_TIMESTAMP,review_note=? WHERE request_id=? AND request_status=\'PENDING\'', [$decision==='APPROVE'?'APPROVED':'REJECTED',$admin,$note ?: null,$request]);
            $this->audit($admin,$request,'RECYCLER_LICENCE_UPDATE_'.$decision);
            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }
}
