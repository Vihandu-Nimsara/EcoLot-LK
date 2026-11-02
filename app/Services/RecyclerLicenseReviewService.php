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
        string $decision,
        string $reviewNote = ''
    ): void {
        if ($recyclerId <= 0 || $adminId <= 0) {
            throw new InvalidArgumentException(
                'Invalid recycler or administrator.'
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

        if (strlen($reviewNote) > 500) {
            throw new InvalidArgumentException(
                'Review note is too long.'
            );
        }

        $this->db->beginTransaction();

        try {
            // Lock the parent recycler record first.
            $recyclerStmt = $this->db->prepare(
                "SELECT user_id, verification_status
                 FROM authorized_recyclers
                 WHERE user_id = :recycler_id
                 FOR UPDATE"
            );

            $recyclerStmt->execute([
                'recycler_id' => $recyclerId
            ]);

            $recycler = $recyclerStmt->fetch(PDO::FETCH_ASSOC);

            if ($recycler === false) {
                throw new RuntimeException('Recycler not found.');
            }

            if ($recycler['verification_status'] !== 'PENDING') {
                throw new RuntimeException(
                    'Recycler application is not pending.'
                );
            }

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

            if ($license['license_status'] !== 'PENDING') {
                throw new RuntimeException(
                    'License has already been reviewed.'
                );
            }

            if ($decision === 'APPROVE') {
                if (empty($license['document_path'])) {
                    throw new RuntimeException(
                        'License document is missing.'
                    );
                }

                if ((int) $license['is_not_expired'] !== 1) {
                    throw new RuntimeException(
                        'An expired license cannot be approved.'
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
}