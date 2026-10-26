<?php
declare(strict_types=1);

final class RecyclerApprovalService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
    
    public function review(
        int $recyclerId,
        string $decision,
        string $rejectionReason = ''
    ): void {
        if (!in_array($decision, ['APPROVE', 'REJECT'], true)) {
            throw new InvalidArgumentException('Invalid review decision.');
        }
        
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                "SELECT user_id, verification_status
                FROM authorized_recyclers
                WHERE user_id = :recycler_id
                FOR UPDATE"
            );

            $stmt->execute(['recycler_id' => $recyclerId,]);

            $recycler = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($recycler === false) {
                throw new RuntimeException('Recycler not found.');
            }

            if (
                strtoupper((string) $recycler['verification_status']) !== 'PENDING'
            ) {
                throw new RuntimeException(
                    'Recycler application has already been reviewed.'
                );

            }

            if ($decision === 'APPROVE') {
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

                if ((string) $license['expiry_date'] < date('Y-m-d')) {
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
            }

            if ($decision === 'REJECT' && trim($rejectionReason) === '') {
                throw new InvalidArgumentException(
                    'Rejection reason is required.'
                );
            }

            $newStatus = $decision === 'APPROVE'
                ? 'VERIFIED'
                : 'REJECTED';

            $updateStmt = $this->db->prepare(
                "UPDATE authorized_recyclers
                SET verification_status = :status
                WHERE user_id = :recycler_id
                    AND verification_status = 'PENDING'"
            );

            $updateStmt->execute([
                'status' =>$newStatus,
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