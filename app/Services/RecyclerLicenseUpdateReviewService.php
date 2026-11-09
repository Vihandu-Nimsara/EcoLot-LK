<?php
declare(strict_types=1);

final class RecyclerLicenseUpdateReviewService
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    public function review(
        int $recyclerId,
        int $requestId,
        int $adminId,
        string $decision,
        string $reviewNote = ''
    ): void {
        if ($recyclerId <= 0 || $requestId <= 0 || $adminId <= 0) {
            throw new InvalidArgumentException(
                'Invalid recycler, request, or administrator.'
            );
        }

        $decision = strtoupper(trim($decision));
        $reviewNote = trim($reviewNote);

        if (!in_array($decision, ['APPROVE', 'REJECT'], true)) {
            throw new InvalidArgumentException(
                'Invalid review decision.'
            );
        }

        if ($decision === 'REJECT' && $reviewNote === '') {
            throw new InvalidArgumentException(
                'A rejection reason is required.'
            );
        }

        if (mb_strlen($reviewNote, 'UTF-8') > 500) {
            throw new InvalidArgumentException(
                'Review note cannot exceed 500 characters.'
            );
        }

        $this->db->beginTransaction();

        try {
            // Use the same parent-first locking strategy
            // as the recycler's submission workflow.
            $recyclerStmt = $this->db->prepare(
                "SELECT r.user_id, r.verification_status,
                        u.role, u.account_status
                 FROM authorized_recyclers r
                 JOIN users u ON u.user_id = r.user_id
                 WHERE r.user_id = :recycler_id
                 FOR UPDATE"
            );

            $recyclerStmt->execute([
                'recycler_id' => $recyclerId
            ]);

            $recycler = $recyclerStmt->fetch(PDO::FETCH_ASSOC);

            if ($recycler === false) {
                throw new RuntimeException('Recycler not found.');
            }

            // Lock the pending request before reviewing.
            $requestStmt = $this->db->prepare(
                "SELECT *,
                        (requested_expiry_date > CURRENT_DATE())
                            AS expiry_is_future
                 FROM recycler_license_update_requests
                 WHERE request_id = :request_id
                   AND recycler_user_id = :recycler_id
                 FOR UPDATE"
            );

            $requestStmt->execute([
                'request_id' => $requestId,
                'recycler_id' => $recyclerId
            ]);

            $request = $requestStmt->fetch(PDO::FETCH_ASSOC);

            if ($request === false) {
                throw new RuntimeException(
                    'Licence update request not found.'
                );
            }

            if ($request['request_status'] !== 'PENDING') {
                throw new RuntimeException(
                    'This licence update has already been reviewed.'
                );
            }

            if ($decision === 'APPROVE') {
                if (
                    $recycler['verification_status'] !== 'VERIFIED' ||
                    $recycler['role'] !== 'RECYCLER' ||
                    $recycler['account_status'] !== 'ACTIVE'
                ) {
                    throw new RuntimeException(
                        'Only active, verified recyclers can receive licence approval.'
                    );
                }

                if ((int) $request['expiry_is_future'] !== 1) {
                    throw new RuntimeException(
                        'The requested licence expiry date is no longer in the future.'
                    );
                }

                $validator = new RecyclerLicenseDocumentValidator();

                $validatedPath = $validator->validatedPath(
                    $request['document_path']
                );

                if ($validatedPath === null) {
                    throw new RuntimeException(
                        'The submitted CEA licence PDF is missing or invalid.'
                    );
                }

                // Lock the current approved licence.
                $currentStmt = $this->db->prepare(
                    "SELECT *
                     FROM recycler_licenses
                     WHERE recycler_user_id = :recycler_id
                       AND license_status = 'VALID'
                     ORDER BY submitted_at DESC, license_id DESC
                     LIMIT 1
                     FOR UPDATE"
                );

                $currentStmt->execute([
                    'recycler_id' => $recyclerId
                ]);

                $current = $currentStmt->fetch(PDO::FETCH_ASSOC);
                $current = $current === false ? null : $current;

                $currentId = $current === null
                    ? null
                    : (int) $current['license_id'];

                $expectedId = $request['current_license_id'] === null
                    ? null
                    : (int) $request['current_license_id'];

                if ($currentId !== $expectedId) {
                    throw new RuntimeException(
                        'The current licence has changed since submission. Please review a new request.'
                    );
                }

                $requestedNumber =
                    (string) $request['requested_license_number'];

                // Prevent licence number conflicts globally.
                $numberStmt = $this->db->prepare(
                    "SELECT license_id
                     FROM recycler_licenses
                     WHERE license_number = :license_number
                     LIMIT 1
                     FOR UPDATE"
                );

                $numberStmt->execute([
                    'license_number' => $requestedNumber
                ]);

                $numberOwner = $numberStmt->fetch(PDO::FETCH_ASSOC);

                if (
                    $numberOwner !== false &&
                    (int) $numberOwner['license_id'] !== $currentId
                ) {
                    throw new RuntimeException(
                        'The requested licence number is already registered.'
                    );
                }

                if (
                    $current !== null &&
                    $requestedNumber === $current['license_number']
                ) {
                    // Same-number renewal:
                    // preserve the old licence before updating it.
                    $revisionStmt = $this->db->prepare(
                        "INSERT INTO recycler_license_revisions (
                            license_id,
                            update_request_id,
                            previous_license_number,
                            previous_expiry_date,
                            previous_document_path,
                            previous_license_status,
                            previous_submitted_at,
                            previous_reviewed_by_admin_user_id,
                            previous_reviewed_at,
                            previous_review_note,
                            archived_by_admin_user_id
                        )
                        SELECT
                            license_id,
                            :request_id,
                            license_number,
                            expiry_date,
                            document_path,
                            license_status,
                            submitted_at,
                            reviewed_by_admin_user_id,
                            reviewed_at,
                            review_note,
                            :admin_id
                        FROM recycler_licenses
                        WHERE license_id = :license_id
                          AND recycler_user_id = :recycler_id
                          AND license_status = 'VALID'"
                    );

                    $revisionStmt->execute([
                        'request_id' => $requestId,
                        'admin_id' => $adminId,
                        'license_id' => $currentId,
                        'recycler_id' => $recyclerId
                    ]);

                    if ($revisionStmt->rowCount() !== 1) {
                        throw new RuntimeException(
                            'Could not preserve previous licence history.'
                        );
                    }

                    $updateLicenseStmt = $this->db->prepare(
                        "UPDATE recycler_licenses
                         SET expiry_date = :expiry_date,
                             document_path = :document_path,
                             submitted_at = CURRENT_TIMESTAMP,
                             reviewed_by_admin_user_id = :admin_id,
                             reviewed_at = CURRENT_TIMESTAMP,
                             review_note = :review_note
                         WHERE license_id = :license_id
                           AND recycler_user_id = :recycler_id
                           AND license_status = 'VALID'"
                    );

                    $updateLicenseStmt->execute([
                        'expiry_date' => $request['requested_expiry_date'],
                        'document_path' => $request['document_path'],
                        'admin_id' => $adminId,
                        'review_note' => $reviewNote !== ''
                            ? $reviewNote
                            : null,
                        'license_id' => $currentId,
                        'recycler_id' => $recyclerId
                    ]);

                    if ($updateLicenseStmt->rowCount() !== 1) {
                        throw new RuntimeException(
                            'Could not renew the existing licence.'
                        );
                    }

                } else {
                    // Different-number replacement:
                    // retain the previous licence row as history.
                    if ($current !== null) {
                        $supersedeStmt = $this->db->prepare(
                            "UPDATE recycler_licenses
                             SET license_status = 'SUPERSEDED'
                             WHERE license_id = :license_id
                               AND recycler_user_id = :recycler_id
                               AND license_status = 'VALID'"
                        );

                        $supersedeStmt->execute([
                            'license_id' => $currentId,
                            'recycler_id' => $recyclerId
                        ]);

                        if ($supersedeStmt->rowCount() !== 1) {
                            throw new RuntimeException(
                                'Could not supersede the previous licence.'
                            );
                        }
                    }

                    $insertStmt = $this->db->prepare(
                        "INSERT INTO recycler_licenses (
                            recycler_user_id,
                            license_number,
                            expiry_date,
                            license_status,
                            document_path,
                            reviewed_by_admin_user_id,
                            reviewed_at,
                            review_note
                        ) VALUES (
                            :recycler_id,
                            :license_number,
                            :expiry_date,
                            'VALID',
                            :document_path,
                            :admin_id,
                            CURRENT_TIMESTAMP,
                            :review_note
                        )"
                    );

                    $insertStmt->execute([
                        'recycler_id' => $recyclerId,
                        'license_number' => $requestedNumber,
                        'expiry_date' => $request['requested_expiry_date'],
                        'document_path' => $request['document_path'],
                        'admin_id' => $adminId,
                        'review_note' => $reviewNote !== ''
                            ? $reviewNote
                            : null
                    ]);
                }
            }

            // Finalize the review for either decision.
            $newStatus = $decision === 'APPROVE'
                ? 'APPROVED'
                : 'REJECTED';

            $reviewStmt = $this->db->prepare(
                "UPDATE recycler_license_update_requests
                 SET request_status = :status,
                     reviewed_by_admin_user_id = :admin_id,
                     reviewed_at = CURRENT_TIMESTAMP,
                     review_note = :review_note
                 WHERE request_id = :request_id
                   AND recycler_user_id = :recycler_id
                   AND request_status = 'PENDING'"
            );

            $reviewStmt->execute([
                'status' => $newStatus,
                'admin_id' => $adminId,
                'review_note' => $reviewNote !== ''
                    ? $reviewNote
                    : null,
                'request_id' => $requestId,
                'recycler_id' => $recyclerId
            ]);

            if ($reviewStmt->rowCount() !== 1) {
                throw new RuntimeException(
                    'Licence update review could not be finalized.'
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
