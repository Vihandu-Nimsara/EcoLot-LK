<?php
declare(strict_types=1);

final class RecyclerAuthorizedActivityReviewService
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    public function review(
        int $recyclerId,
        int $activityId,
        int $adminId,
        string $decision,
        string $reviewNote = ''
    ): void {
        if (
            $recyclerId <= 0 ||
            $activityId <= 0 ||
            $adminId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid recycler, activity or administrator.'
            );
        }

        if (!in_array($decision, ['APPROVE', 'REJECT'], true)) {
            throw new InvalidArgumentException(
                'Invalid activity review decision.'
            );
        }

        $reviewNote = trim($reviewNote);

        if ($decision === 'REJECT' && $reviewNote === '') {
            throw new InvalidArgumentException(
                'Activity rejection reason is required.'
            );
        }

        if (mb_strlen($reviewNote, 'UTF-8') > 500) {
            throw new InvalidArgumentException(
                'Review note must not exceed 500 characters.'
            );
        }

        $this->db->beginTransaction();

        try {
            $authorization = new RecyclerLicenceAuthorization($this->db);
            $authorization->requireAdmin($adminId);
            $authorization->lockOwner($recyclerId);
            // Lock the recycler first to maintain lock ordering.
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

            // Lock the specific activity belonging to this recycler.
            $activityStmt = $this->db->prepare(
                "SELECT
                    activity_id,
                    recycler_user_id,
                    activity_type,
                    activity_status
                 FROM recycler_authorized_activities
                 WHERE activity_id = :activity_id
                   AND recycler_user_id = :recycler_id
                 FOR UPDATE"
            );

            $activityStmt->execute([
                'activity_id' => $activityId,
                'recycler_id' => $recyclerId
            ]);

            $activity = $activityStmt->fetch(PDO::FETCH_ASSOC);

            if ($activity === false) {
                throw new RuntimeException(
                    'Recycler authorized activity not found.'
                );
            }

            if ($activity['activity_status'] !== 'PENDING') {
                throw new RuntimeException(
                    'Activity has already been reviewed.'
                );
            }

            if ($decision === 'APPROVE') $authorization->requireActivity($recyclerId, $activity['activity_type'], true);

            $newStatus = $decision === 'APPROVE'
                ? 'APPROVED'
                : 'REJECTED';

            // Update only this recycler's requested activity.
            $updateStmt = $this->db->prepare(
                "UPDATE recycler_authorized_activities
                 SET activity_status = :status,
                     reviewed_by_admin_user_id = :admin_id,
                     reviewed_at = CURRENT_TIMESTAMP,
                     review_note = :review_note
                 WHERE activity_id = :activity_id
                   AND recycler_user_id = :recycler_id
                   AND activity_status = 'PENDING'"
            );

            $updateStmt->execute([
                'status' => $newStatus,
                'admin_id' => $adminId,
                'review_note' => $reviewNote !== ''
                    ? $reviewNote
                    : null,
                'activity_id' => $activityId,
                'recycler_id' => $recyclerId
            ]);

            if ($updateStmt->rowCount() !== 1) {
                throw new RuntimeException(
                    'Activity review could not be completed.'
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
