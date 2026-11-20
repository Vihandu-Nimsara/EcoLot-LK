<?php
declare(strict_types=1);

final class AuthorizedRecycler extends Model
{
    protected string $table = 'authorized_recyclers';
    protected string $primaryKey = 'user_id';

    public function findByCompanyName(string $companyName): ?array
    {
        $result = $this->query(
            'SELECT `user_id`, `company_name`
             FROM `authorized_recyclers`
             WHERE `company_name` = :company_name
             LIMIT 1',
            ['company_name' => $companyName]
        )->fetch();

        return $result === false ? null : $result;
    }

    public function findByUserId(int $userId): ?array
    {
        $result = $this->query(
            'SELECT `user_id`, `company_name`, `verification_status`
             FROM `authorized_recyclers`
             WHERE `user_id` = :user_id
             LIMIT 1',
            ['user_id' => $userId]
        )->fetch();

        return $result === false ? null : $result;
    }

    public function profileForRecycler(int $userId): ?array
    {
        return $this->query("SELECT r.company_name, r.business_address, r.district,
            u.full_name, u.mobile_number, u.email, r.reviewed_at, r.review_note
            FROM authorized_recyclers r JOIN users u ON u.user_id = r.user_id
            WHERE r.user_id = :owner AND u.role = 'RECYCLER'",
            ['owner' => $userId])->fetch() ?: null;
    }

    /** Read-only compliance data for the authenticated Recycler dashboard. */
    public function dashboardCompliance(int $userId): ?array
    {
        try {
            return $this->query("SELECT r.verification_status,
                (SELECT MAX(l.expiry_date) FROM recycler_licenses l
                 WHERE l.recycler_user_id = r.user_id AND l.license_id = r.current_license_id
                 AND l.reviewed_by_admin_user_id IS NOT NULL AND l.reviewed_at IS NOT NULL AND l.license_status = 'VALID'
                 AND l.expiry_date >= CURRENT_DATE) AS valid_license_expiry
                FROM authorized_recyclers r WHERE r.user_id = :owner",
                ['owner' => $userId])->fetch() ?: null;
        } catch (PDOException $error) {
            if ($error->getCode() !== '42S22') throw $error;
            return $this->query('SELECT verification_status,NULL AS valid_license_expiry FROM authorized_recyclers WHERE user_id=?', [$userId])->fetch() ?: null;
        }
    }

    public function capabilitiesForRecycler(int $recyclerId): array
    {
        return $this->query(
            "SELECT 
                rc.category_id,
                wc.category_name,
                rc.can_handle_high_risk,
                rc.capability_status, rc.reviewed_by_admin_user_id, rc.reviewed_at, rc.review_note
            FROM recycler_capabilities rc
            JOIN waste_categories wc 
            ON wc.category_id = rc.category_id
            WHERE rc.recycler_user_id = :recycler_id
            ORDER BY wc.category_name",
            ['recycler_id' => $recyclerId]
        )->fetchAll();
    }

    public function verificationList(): array
    {
    return $this->query(
        "SELECT
            r.user_id AS recycler_id,
            r.company_name,
            r.business_address,
            r.district,
            r.submitted_at,
            r.verification_status AS status,
            u.full_name AS contact_person,
            u.email,
            u.mobile_number AS phone,
            l.license_number AS license_no,
            l.expiry_date AS license_expiry
        FROM authorized_recyclers r
        JOIN users u
            ON u.user_id = r.user_id
        LEFT JOIN recycler_licenses l
            ON l.license_id = (
                SELECT l2.license_id
                FROM recycler_licenses l2
                WHERE l2.recycler_user_id = r.user_id
                ORDER BY l2.submitted_at DESC, l2.license_id DESC
                LIMIT 1
            )
        ORDER BY r.submitted_at DESC"
    )->fetchAll();
    }

    public function verificationDetails(int $recyclerId): ?array
    {
        $result = $this->query(
            "SELECT 
                r.user_id AS recycler_id,
                r.company_name,
                r.business_address,
                r.district,
                r.verification_status,
                r.submitted_at, r.reviewed_at, r.reviewed_by_admin_user_id, r.review_note,
                u.role, u.account_status, u.mobile_verified_at,
                u.full_name AS contact_person,
                u.email,
                u.mobile_number AS phone
            FROM authorized_recyclers r
            JOIN users u ON u.user_id = r.user_id
            WHERE r.user_id = :recycler_id
            LIMIT 1", 
            ['recycler_id' => $recyclerId]
        )->fetch();

        return $result === false ? null : $result;
    }

    public function latestLicenseForRecycler(int $recyclerId): ?array
    {
        $result = $this->query(
            "SELECT license_id,
                license_number AS license_no,
                expiry_date AS license_expiry,
                license_status,
                document_path,
                reviewed_at, reviewed_by_admin_user_id, review_note,
                (reviewed_at IS NOT NULL AND reviewed_by_admin_user_id IS NOT NULL) AS approval_recorded,
                submitted_at
            FROM recycler_licenses
            WHERE recycler_user_id = :recycler_id
            ORDER BY submitted_at DESC, license_id DESC
            LIMIT 1",
            ['recycler_id' => $recyclerId]
        )->fetch();
        return $result === false ? null : $result;
    }

    /** Explicit selection only. Retain expired/revoked selected identities for display, never fall back. */
    public function selectedLicenseForRecycler(int $owner): ?array
    {
        try {
            return $this->query("SELECT l.license_id, l.license_number AS license_no,
                l.expiry_date AS license_expiry, l.license_status, l.document_path,
                l.reviewed_at, l.reviewed_by_admin_user_id, l.review_note, l.submitted_at,
                (l.reviewed_at IS NOT NULL AND l.reviewed_by_admin_user_id IS NOT NULL) AS approval_recorded
                FROM authorized_recyclers r JOIN recycler_licenses l
                  ON l.license_id=r.current_license_id AND l.recycler_user_id=r.user_id
                WHERE r.user_id=?", [$owner])->fetch() ?: null;
        } catch (PDOException $error) {
            if ($error->getCode() !== '42S22') throw $error;
            return null; // Selection infrastructure is not installed: never infer an approval.
        }
    }

    public function currentApprovedLicenseForRecycler(int $owner): ?array
    {
        $license = $this->selectedLicenseForRecycler($owner);
        return $license && $license['license_status'] === 'VALID' && $license['approval_recorded'] ? $license : null;
    }

    /** Initial submission selection is confined to the original PENDING registration workflow. */
    public function profileLicenseForRecycler(int $owner): ?array
    {
        $registration = $this->findByUserId($owner);
        return ($registration['verification_status'] ?? null) === 'PENDING'
            ? $this->latestLicenseForRecycler($owner) : $this->selectedLicenseForRecycler($owner);
    }

    /** All owner records, including rejected and historical submissions, grant no permission. */
    public function licenseHistoryForRecycler(int $owner): array
    {
        return $this->query('SELECT license_id,license_number,expiry_date,license_status,
            submitted_at,reviewed_at,review_note FROM recycler_licenses
            WHERE recycler_user_id=? ORDER BY submitted_at DESC,license_id DESC', [$owner])->fetchAll();
    }

    /** Existing revision snapshots are audit-only and never candidates for current selection. */
    public function licenseRevisionHistoryForRecycler(int $owner): array
    {
        try {
            return $this->query('SELECT v.revision_id,v.license_id,v.previous_license_number,
                v.previous_expiry_date,v.previous_license_status,v.previous_reviewed_at,
                v.previous_review_note,v.archived_at FROM recycler_license_revisions v
                JOIN recycler_licenses l ON l.license_id=v.license_id
                WHERE l.recycler_user_id=? ORDER BY v.revision_id DESC', [$owner])->fetchAll();
        } catch (PDOException $error) {
            if ($error->getCode() !== '42S02') throw $error;
            return [];
        }
    }

    /** Legacy pending replacement requests are audit reads only, never the selected licence. */
    public function pendingLicenseReplacementsForRecycler(int $owner): array
    {
        try {
            return $this->query("SELECT request_id,current_license_id,requested_license_number,
                requested_expiry_date,submitted_at FROM recycler_license_update_requests
                WHERE recycler_user_id=? AND request_status='PENDING' ORDER BY request_id DESC", [$owner])->fetchAll();
        } catch (PDOException $error) {
            if ($error->getCode() !== '42S02') throw $error;
            return []; // This workflow does not exist on a fresh Phase 2A installation.
        }
    }

    /** Shared transaction boundary for the existing initial-review services. */
    public function lockPendingReview(int $owner, int $admin, bool $approving = false): array
    {
        if (!$this->db->inTransaction()) throw new LogicException('Review requires a transaction.');
        if (Auth::role() !== 'ADMIN' || (int) Auth::id() !== $admin || $admin <= 0) throw new RuntimeException('An authenticated Administrator is required.');
        $actor=$this->query("SELECT a.user_id FROM administrators a JOIN users u ON u.user_id=a.user_id WHERE a.user_id=? AND u.role='ADMIN' AND u.account_status='ACTIVE' LOCK IN SHARE MODE",[$admin])->fetch();
        if (!$actor) throw new RuntimeException('Administrator account is not active.');
        (new User($this->db))->lockForVerification($owner);
        $row=$this->query('SELECT r.verification_status,u.role,u.account_status,u.mobile_verified_at FROM authorized_recyclers r JOIN users u ON u.user_id=r.user_id WHERE r.user_id=? FOR UPDATE',[$owner])->fetch();
        if (!$row || $row['role']!=='RECYCLER') throw new RuntimeException('Recycler not found.');
        if ($row['verification_status']!=='PENDING') throw new RuntimeException('Recycler application has already been reviewed.');
        if ($approving && $row['account_status']!=='ACTIVE') throw new RuntimeException('An active Recycler account is required.');
        return $row;
    }

    public function initialScopes(int $owner): array
    {
        return $this->query('SELECT s.*,c.category_name FROM recycler_license_scopes s JOIN recycler_licenses l ON l.license_id=s.license_id JOIN waste_categories c ON c.category_id=s.category_id WHERE l.recycler_user_id=? ORDER BY s.scope_id DESC',[$owner])->fetchAll();
    }
}

