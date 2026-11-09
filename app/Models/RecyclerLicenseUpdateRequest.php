<?php
declare(strict_types=1);

final class RecyclerLicenseUpdateRequest extends Model
{
    protected string $table = 'recycler_license_update_requests';
    protected string $primaryKey = 'request_id';

    /**
     * Get the authenticated recycler's pending request.
     */
    public function pendingForRecycler(int $recyclerId): ?array
    {
        $result = $this->query(
            "SELECT
                request_id,
                recycler_user_id,
                current_license_id,
                requested_license_number,
                requested_expiry_date,
                document_path,
                request_status,
                submitted_at
             FROM recycler_license_update_requests
             WHERE recycler_user_id = :recycler_id
               AND request_status = 'PENDING'
             LIMIT 1",
            [
                'recycler_id' => $recyclerId,
            ]
        )->fetch();

        return $result === false ? null : $result;
    }

    /**
     * Get a recycler's licence update request history.
     */
    public function historyForRecycler(int $recyclerId): array
    {
        return $this->query(
            "SELECT
                request_id,
                requested_license_number,
                requested_expiry_date,
                request_status,
                submitted_at,
                reviewed_at,
                review_note
             FROM recycler_license_update_requests
             WHERE recycler_user_id = :recycler_id
             ORDER BY submitted_at DESC, request_id DESC",
            [
                'recycler_id' => $recyclerId,
            ]
        )->fetchAll();
    }

    /**
     * Store a validated licence update request.
     *
     * Validation, ownership checks and transactions
     * are handled by the service layer.
     */
    public function createPendingRequest(
        int $recyclerId,
        ?int $currentLicenseId,
        string $licenseNumber,
        string $expiryDate,
        string $documentPath
    ): int {
        return $this->create([
            'recycler_user_id' => $recyclerId,
            'current_license_id' => $currentLicenseId,
            'requested_license_number' => $licenseNumber,
            'requested_expiry_date' => $expiryDate,
            'document_path' => $documentPath,
            'request_status' => 'PENDING',
        ]);
    }
}
