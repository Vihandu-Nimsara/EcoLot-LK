<?php
declare(strict_types=1);

final class RecyclerLicenseUpdateService
{
    private const MAX_PDF_BYTES = 5 * 1024 * 1024;

    public function submit(
        int $recyclerUserId,
        array $input,
        array $file
    ): int {
        if ($recyclerUserId <= 0) {
            throw new InvalidArgumentException('Invalid recycler.');
        }

        $errors = $this->validate($input, $file);

        if ($errors !== []) {
            throw new InvalidArgumentException(
                implode(' ', array_values($errors))
            );
        }

        $licenseNumber = trim($input['requested_license_number']);
        $expiryDate = $input['requested_expiry_date'];

        [$stagedPath, $finalPath, $relativePath] =
            $this->stagePdf($file);

        $connection = null;

        try {
            $connection = Database::connection();
            $connection->beginTransaction();

            // Lock the recycler row to serialize submissions.
            $stmt = $connection->prepare(
                "SELECT
                    ar.verification_status,
                    u.role,
                    u.account_status
                FROM authorized_recyclers ar
                JOIN users u ON u.user_id = ar.user_id
                WHERE ar.user_id = :user_id
                FOR UPDATE"
            );

            $stmt->execute(['user_id' => $recyclerUserId]);
            $recycler = $stmt->fetch(PDO::FETCH_ASSOC);

            if (
                $recycler === false ||
                $recycler['role'] !== 'RECYCLER' ||
                $recycler['account_status'] !== 'ACTIVE' ||
                $recycler['verification_status'] !== 'VERIFIED'
            ) {
                throw new RuntimeException(
                    'Only active, verified recyclers can submit licence updates.'
                );
            }

            // Prevent multiple pending updates.
            $stmt = $connection->prepare(
                "SELECT request_id
                FROM recycler_license_update_requests
                WHERE recycler_user_id = :user_id
                AND request_status = 'PENDING'
                LIMIT 1"
            );

            $stmt->execute(['user_id' => $recyclerUserId]);

            if ($stmt->fetchColumn() !== false) {
                throw new RuntimeException(
                    'A licence update request is already awaiting review.'
                );
            }

            // A licence number belonging to another recycler
            // must not be claimed through this request.
            $stmt = $connection->prepare(
                "SELECT license_id
                FROM recycler_licenses
                WHERE license_number = :license_number
                AND recycler_user_id <> :user_id
                LIMIT 1"
            );

            $stmt->execute([
                'license_number' => $licenseNumber,
                'user_id' => $recyclerUserId,
            ]);

            if ($stmt->fetchColumn() !== false) {
                throw new RuntimeException(
                    'This licence number belongs to another recycler.'
                );
            }

            // Identify the existing licence record.
            // An expired licence may still be renewed.
            $stmt = $connection->prepare(
                "SELECT license_id
                FROM recycler_licenses
                WHERE recycler_user_id = :user_id
                AND license_status = 'VALID'
                ORDER BY expiry_date DESC, license_id DESC
                LIMIT 1"
            );

            $stmt->execute(['user_id' => $recyclerUserId]);

            $licenseId = $stmt->fetchColumn();
            $currentLicenseId = $licenseId === false
                ? null
                : (int) $licenseId;

            // Insert into the request table only.
            $requestId = (
                new RecyclerLicenseUpdateRequest($connection)
            )->createPendingRequest(
                $recyclerUserId,
                $currentLicenseId,
                $licenseNumber,
                $expiryDate,
                $relativePath
            );

            // Finalize the private PDF before committing.
            if (!rename($stagedPath, $finalPath)) {
                throw new RuntimeException(
                    'The licence document could not be finalized.'
                );
            }

            $connection->commit();

            return $requestId;

        } catch (Throwable $exception) {
            if (
                $connection instanceof PDO &&
                $connection->inTransaction()
            ) {
                $connection->rollBack();
            }

            if (is_file($stagedPath)) {
                @unlink($stagedPath);
            }

            if (is_file($finalPath)) {
                @unlink($finalPath);
            }

            if (
                $exception instanceof PDOException &&
                (int) ($exception->errorInfo[1] ?? 0) === 1062
            ) {
                throw new RuntimeException(
                    'A pending licence update request already exists.',
                    0,
                    $exception
                );
            }

            throw $exception;
        }
    }

    public function validate(array $input, array $file): array
    {
        $errors = [];

        // 1. Licence number
        $rawNumber = $input['requested_license_number'] ?? null;

        if (!is_string($rawNumber)) {
            $errors['requested_license_number'] =
                'Enter a valid CEA licence number.';
        } else {
            $number = trim($rawNumber);

            if (
                $number === '' ||
                strlen($number) > 100 ||
                preg_match('/[\x00-\x1F\x7F]/', $number) === 1
            ) {
                $errors['requested_license_number'] =
                    'Licence number must be between 1 and 100 characters.';
            }
        }

        // 2. Future expiry date
        $rawExpiry = $input['requested_expiry_date'] ?? null;

        if (!is_string($rawExpiry)) {
            $errors['requested_expiry_date'] =
                'Enter a valid expiry date.';
        } else {
            $timezone = new DateTimeZone('Asia/Colombo');

            $expiry = DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $rawExpiry,
                $timezone
            );

            $dateErrors = DateTimeImmutable::getLastErrors();

            $validDate =
                $expiry !== false &&
                $expiry->format('Y-m-d') === $rawExpiry &&
                (
                    $dateErrors === false ||
                    (
                        $dateErrors['warning_count'] === 0 &&
                        $dateErrors['error_count'] === 0
                    )
                );

            if (!$validDate) {
                $errors['requested_expiry_date'] =
                    'Enter a valid expiry date.';
            } elseif (
                $expiry <= new DateTimeImmutable('today', $timezone)
            ) {
                $errors['requested_expiry_date'] =
                    'Licence expiry must be in the future.';
            }
        }

        // 3. PDF upload
        $this->validatePdf($file, $errors);

        return $errors;
    }

    private function validatePdf(array $file, array &$errors): void
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $errors['license_document'] =
                'A CEA licence PDF is required.';
            return;
        }

        $tmpName = $file['tmp_name'] ?? null;
        $originalName = $file['name'] ?? null;

        if (!is_string($tmpName) || !is_uploaded_file($tmpName)) {
            $errors['license_document'] =
                'The uploaded file is invalid.';
            return;
        }

        $size = filesize($tmpName);

        if (
            $size === false ||
            $size < 5 ||
            $size > self::MAX_PDF_BYTES
        ) {
            $errors['license_document'] =
                'The PDF must not exceed 5 MB.';
            return;
        }

        if (
            !is_string($originalName) ||
            strtolower(pathinfo($originalName, PATHINFO_EXTENSION))
                !== 'pdf'
        ) {
            $errors['license_document'] =
                'Only PDF documents are allowed.';
            return;
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmpName);

        if ($mime !== 'application/pdf') {
            $errors['license_document'] =
                'The document must be a valid PDF.';
            return;
        }

        $handle = fopen($tmpName, 'rb');

        if ($handle === false) {
            $errors['license_document'] =
                'The document could not be read.';
            return;
        }

        try {
            $signature = fread($handle, 5);
            $tailLength = min(2048, $size);

            $ending = fseek($handle, -$tailLength, SEEK_END) === 0
                ? fread($handle, $tailLength)
                : false;
        } finally {
            fclose($handle);
        }

        if (
            $signature !== '%PDF-' ||
            !is_string($ending) ||
            !str_contains($ending, '%%EOF')
        ) {
            $errors['license_document'] =
                'The document must be a valid PDF.';
        }
    }

    private function stagePdf(array $file): array
    {
        $root = defined('APP_ROOT')
            ? APP_ROOT
            : dirname(__DIR__, 2);

        $relativeDirectory = 'uploads/recycler-licenses';
        $directory = $root . '/storage/' . $relativeDirectory;
        $stagingDirectory = $directory . '/.staging';

        if (
            !is_dir($stagingDirectory) &&
            !mkdir($stagingDirectory, 0750, true) &&
            !is_dir($stagingDirectory)
        ) {
            throw new RuntimeException(
                'The licence storage directory is unavailable.'
            );
        }

        $filename = bin2hex(random_bytes(24)) . '.pdf';

        $stagedPath = $stagingDirectory . '/'
            . $filename . '.part';

        $finalPath = $directory . '/' . $filename;

        if (!move_uploaded_file(
            (string) $file['tmp_name'],
            $stagedPath
        )) {
            throw new RuntimeException(
                'The licence PDF could not be stored.'
            );
        }

        if (!chmod($stagedPath, 0600)) {
            @unlink($stagedPath);

            throw new RuntimeException(
                'The licence PDF could not be secured.'
            );
        }

        return [
            $stagedPath,
            $finalPath,
            $relativeDirectory . '/' . $filename,
        ];
    }

}
