<?php
declare(strict_types=1);

final class RecyclerLicenseDocumentValidator
{
    private const MAX_FILE_SIZE = 5 * 1024 * 1024;

    public function validatedPath(?string $relativePath): ?string
    {
        if ($relativePath === null || trim($relativePath) === '') {
            return null;
        }

        $allowedDirectory = realpath(
            APP_ROOT . '/storage/uploads/recycler-licenses'
        );

        $filePath = realpath(
            APP_ROOT . '/storage/' . $relativePath
        );

        // Ensure the file belongs to the allowed directory.
        if (
            $allowedDirectory === false ||
            $filePath === false ||
            dirname($filePath) !== $allowedDirectory ||
            !is_file($filePath) ||
            !is_readable($filePath)
        ) {
            return null;
        }

        // Validate file size.
        $fileSize = filesize($filePath);

        if (
            $fileSize === false ||
            $fileSize < 5 ||
            $fileSize > self::MAX_FILE_SIZE
        ) {
            return null;
        }

        // Validate actual MIME type.
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($filePath);

        if ($mime !== 'application/pdf') {
            return null;
        }

        // Validate PDF signature and EOF marker.
        $handle = fopen($filePath, 'rb');

        if ($handle === false) {
            return null;
        }

        try {
            $signature = fread($handle, 5);

            $tailLength = min(2048, $fileSize);
            $ending = false;

            if (fseek($handle, -$tailLength, SEEK_END) === 0) {
                $ending = fread($handle, $tailLength);
            }

            if (
                $signature !== '%PDF-' ||
                !is_string($ending) ||
                !str_contains($ending, '%%EOF')
            ) {
                return null;
            }

            return $filePath;

        } finally {
            fclose($handle);
        }
    }
}