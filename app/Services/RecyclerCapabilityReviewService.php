<?php
declare(strict_types=1);

final class RecyclerCapabilityReviewService
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    public function review(
        int $recyclerId,
        int $categoryId,
        int $adminId,
        string $decision,
        string $reviewNote = ''
    ): void {
        if (
            $recyclerId <= 0 ||
            $categoryId <= 0 ||
            $adminId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid recycler, category or administrator.'
            );
        }

        if (!in_array($decision, ['APPROVE', 'REJECT'], true)) {
            throw new InvalidArgumentException(
                'Invalid capability review decision.'
            );
        }

        $reviewNote = trim($reviewNote);

        if ($decision === 'REJECT' && $reviewNote === '') {
            throw new InvalidArgumentException(
                'Capability rejection reason is required.'
            );
        }

        if (mb_strlen($reviewNote, 'UTF-8') > 500) {
            throw new InvalidArgumentException(
                'Review note must not exceed 500 characters.'
            );
        }

        $this->db->beginTransaction();

        try {
            // Lock the Recycler record first.
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
                throw new RuntimeException(
                    'Recycler not found.'
                );
            }

            if ($recycler['verification_status'] !== 'PENDING') {
                throw new RuntimeException(
                    'Recycler application is not pending.'
                );
            }

            // Lock the requested capability.
            $capabilityStmt = $this->db->prepare(
                "SELECT
                    recycler_user_id,
                    category_id,
                    capability_status,
                    can_handle_high_risk
                 FROM recycler_capabilities
                 WHERE recycler_user_id = :recycler_id
                   AND category_id = :category_id
                 FOR UPDATE"
            );

            $capabilityStmt->execute([
                'recycler_id' => $recyclerId,
                'category_id' => $categoryId
            ]);

            $capability = $capabilityStmt->fetch(PDO::FETCH_ASSOC);

            if ($capability === false) {
                throw new RuntimeException(
                    'Recycler capability not found.'
                );
            }

            if ($capability['capability_status'] !== 'PENDING') {
                throw new RuntimeException(
                    'Capability has already been reviewed.'
                );
            }

            $newStatus = $decision === 'APPROVE'
                ? 'APPROVED'
                : 'REJECTED';

            // Update only the selected Recycler + Category pair.
            $updateStmt = $this->db->prepare(
                "UPDATE recycler_capabilities
                 SET capability_status = :status,
                     reviewed_by_admin_user_id = :admin_id,
                     reviewed_at = CURRENT_TIMESTAMP,
                     review_note = :review_note
                 WHERE recycler_user_id = :recycler_id
                   AND category_id = :category_id
                   AND capability_status = 'PENDING'"
            );

            $updateStmt->execute([
                'status' => $newStatus,
                'admin_id' => $adminId,
                'review_note' => $reviewNote !== ''
                    ? $reviewNote
                    : null,
                'recycler_id' => $recyclerId,
                'category_id' => $categoryId
            ]);

            if ($updateStmt->rowCount() !== 1) {
                throw new RuntimeException(
                    'Capability review could not be completed.'
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