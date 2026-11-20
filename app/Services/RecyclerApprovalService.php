<?php
declare(strict_types=1);

final class RecyclerApprovalService
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }
    
    public function review(
        int $recyclerId,
        int $adminId,
        string $decision,
        string $rejectionReason = ''
    ): void {
        if (!in_array($decision, ['APPROVE', 'REJECT'], true)) {
            throw new InvalidArgumentException('Invalid review decision.');
        }
        
        $this->db->beginTransaction();

        try {
            $ownerState=(new AuthorizedRecycler($this->db))->lockPendingReview($recyclerId,$adminId,$decision === 'APPROVE');

            if ($decision === 'APPROVE') {
                if ($ownerState['mobile_verified_at'] === null) throw new RuntimeException('Mobile verification must be completed.');

                $licenseStmt = $this->db->prepare(
                    "SELECT 
                        license_id,
                        license_status,
                        expiry_date
                    FROM recycler_licenses
                    WHERE recycler_user_id = :recycler_id
                    ORDER BY submitted_at DESC, license_id DESC
                    LIMIT 1
                    FOR UPDATE"
                );

                $licenseStmt->execute(['recycler_id' => $recyclerId,]);

                $license = $licenseStmt->fetch(PDO::FETCH_ASSOC);

                if ($license === false) {
                    throw new RuntimeException(
                        'Recycler does not have a license record.'
                    );
                }

                if (
                    strtoupper((string) $license['license_status']) !== 'VALID'
                ) {
                    throw new RuntimeException(
                        'Recycler license is not valid.'
                    );
                }

                if ((string) $license['expiry_date'] < $this->db->query('SELECT CURRENT_DATE')->fetchColumn()) {
                    throw new RuntimeException(
                        'Recycler license has expired.'
                    );
                }

                $capabilityStmt = $this->db->prepare(
                    "SELECT category_id
                    FROM recycler_capabilities
                    WHERE recycler_user_id = :recycler_id
                        AND capability_status = 'APPROVED'
                    LIMIT 1
                    FOR UPDATE"
                );

                $capabilityStmt->execute(['recycler_id' => $recyclerId,]);
                $approvedCapability = $capabilityStmt->fetch(PDO::FETCH_ASSOC);

                if ($approvedCapability === false) {
                    throw new RuntimeException(
                        'Recycler does not have an approved capability.'
                    );
                }

                // All capability reviews must be completed before final approval.
                $pendingCapabilityStmt = $this->db->prepare(
                    "SELECT COUNT(*)
                    FROM recycler_capabilities
                    WHERE recycler_user_id = :recycler_id
                    AND capability_status = 'PENDING'"
                );

                $pendingCapabilityStmt->execute([
                    'recycler_id' => $recyclerId
                ]);

                if ((int) $pendingCapabilityStmt->fetchColumn() > 0) {
                    throw new RuntimeException(
                        'Recycler has pending capability reviews.'
                    );
                }

                // At least one authorized activity must be approved.
                $approvedActivityStmt = $this->db->prepare(
                    "SELECT activity_id
                    FROM recycler_authorized_activities
                    WHERE recycler_user_id = :recycler_id
                    AND activity_status = 'APPROVED'
                    LIMIT 1
                    FOR UPDATE"
                );

                $approvedActivityStmt->execute([
                    'recycler_id' => $recyclerId
                ]);

                if ($approvedActivityStmt->fetch(PDO::FETCH_ASSOC) === false) {
                    throw new RuntimeException(
                        'Recycler does not have an approved authorized activity.'
                    );
                }

                // All authorized activity reviews must be completed.
                $pendingActivityStmt = $this->db->prepare(
                    "SELECT COUNT(*)
                    FROM recycler_authorized_activities
                    WHERE recycler_user_id = :recycler_id
                    AND activity_status = 'PENDING'"
                );

                $pendingActivityStmt->execute([
                    'recycler_id' => $recyclerId
                ]);

                if ((int) $pendingActivityStmt->fetchColumn() > 0) {
                    throw new RuntimeException(
                        'Recycler has pending authorized activity reviews.'
                    );
                }

            }

            if ($decision === 'APPROVE') {
                $scope=new RecyclerLicenseReviewService($this->db);
                $pending=$this->db->prepare("SELECT COUNT(*) FROM recycler_license_scopes s JOIN recycler_licenses l ON l.license_id=s.license_id WHERE l.recycler_user_id=? AND s.scope_status='PENDING'");
                $pending->execute([$recyclerId]);if ((int)$pending->fetchColumn()>0) throw new RuntimeException('Resolve pending scope decisions before final approval.');
                $caps=$this->db->prepare("SELECT category_id,can_handle_high_risk FROM recycler_capabilities WHERE recycler_user_id=? AND capability_status='APPROVED' FOR UPDATE");
                $caps->execute([$recyclerId]);
                foreach ($caps->fetchAll(PDO::FETCH_ASSOC) as $cap) $scope->requireScope($recyclerId,(int)$cap['category_id'],null,(int)$cap['can_handle_high_risk'],true);
                $acts=$this->db->prepare("SELECT activity_type FROM recycler_authorized_activities WHERE recycler_user_id=? AND activity_status='APPROVED' FOR UPDATE");
                $acts->execute([$recyclerId]);
                foreach ($acts->fetchAll(PDO::FETCH_ASSOC) as $act) $scope->requireScope($recyclerId,null,$act['activity_type'],null,true);
            }

            if ($decision === 'APPROVE') {
                $selection = $this->db->prepare('UPDATE authorized_recyclers SET current_license_id=? WHERE user_id=?');
                $selection->execute([$license['license_id'], $recyclerId]);
            }

            if ($decision === 'REJECT' && trim($rejectionReason) === '') {
                throw new InvalidArgumentException(
                    'Rejection reason is required.'
                );
            }

            if (
                $decision === 'REJECT' &&
                mb_strlen(trim($rejectionReason), 'UTF-8') > 500
            ) {
                throw new InvalidArgumentException(
                    'Rejection reason must not exceed 500 characters.'
                );
            }

            $newStatus = $decision === 'APPROVE'
                ? 'VERIFIED'
                : 'REJECTED';

            $updateStmt = $this->db->prepare(
                "UPDATE authorized_recyclers
                SET verification_status = :status,
                    reviewed_by_admin_user_id = :admin_id,
                    reviewed_at = CURRENT_TIMESTAMP,
                    review_note = :review_note
                WHERE user_id = :recycler_id
                    AND verification_status = 'PENDING'"
            );

            $updateStmt->execute([
                'status' =>$newStatus,
                'admin_id' => $adminId,
                'review_note' => $decision === 'REJECT'
                    ? trim($rejectionReason) 
                    : null,
                'recycler_id' => $recyclerId,
            ]);

            if ($updateStmt->rowCount() !== 1) {
                throw new RuntimeException(
                    'Recycler verification status could not be updated.'
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
}