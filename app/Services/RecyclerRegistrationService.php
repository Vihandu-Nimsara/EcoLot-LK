<?php
declare(strict_types=1);

final class RecyclerRegistrationService
{
    public const DISTRICTS = [
        'Ampara', 'Anuradhapura', 'Badulla', 'Batticaloa', 'Colombo',
        'Galle', 'Gampaha', 'Hambantota', 'Jaffna', 'Kalutara', 'Kandy',
        'Kegalle', 'Kilinochchi', 'Kurunegala', 'Mannar', 'Matale', 'Matara',
        'Monaragala', 'Mullaitivu', 'Nuwara Eliya', 'Polonnaruwa', 'Puttalam',
        'Ratnapura', 'Trincomalee', 'Vavuniya',
    ];

    public const ACTIVITIES = [
        'COLLECTION' => 'Collection',
        'TRANSPORTATION' => 'Transportation',
        'STORAGE' => 'Storage',
        'RECOVERY' => 'Recovery',
        'RECYCLING' => 'Recycling',
        'DISPOSAL' => 'Disposal',
    ];

    private const MAX_PDF_BYTES = 5 * 1024 * 1024;
    private const LICENSE_DIRECTORY = 'uploads/recycler-licenses';

    public function categories(): array
    {
        return (new WasteCategory())->recyclerSelectable();
    }

    public function validate(array $input, array $file): array
    {
        $validator = new Validator();
        $validator->validate($input, [
            'company_name' => ['required', 'min:2', 'max:180'],
            'contact_person' => ['required', 'min:2', 'max:120'],
            'company_email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'max:20'],
            'company_password' => ['required', 'min:8', 'max:128'],
            'confirm_password' => ['required', 'max:128'],
            'business_address' => ['required', 'min:5', 'max:500'],
            'district' => ['required', 'in:' . implode(',', self::DISTRICTS)],
            'swml_number' => ['required', 'max:100'],
            'licence_expiry' => ['required', 'date'],
        ]);

        $errors = $validator->errors();
        $mobileNumber = MobileNumber::normalize((string) $input['phone']);

        if ($mobileNumber === null) {
            $errors['phone'][] = 'Enter a valid Sri Lankan mobile number.';
        }

        $this->validatePassword($input, $errors);
        $this->validateExpiry((string) $input['licence_expiry'], $errors);
        $activities = $this->validateActivities($input['activities'], $errors);
        $categoryIds = $this->validateCapabilities(
            $input['requested_capabilities'],
            $errors
        );
        $this->validateFile($file, $errors);
        $this->validateUniqueness($input, $mobileNumber, $errors);

        return [
            'errors' => $errors,
            'mobile_number' => $mobileNumber,
            'activities' => $activities,
            'category_ids' => $categoryIds,
        ];
    }

    public function createPendingRecycler(
        array $input,
        string $mobileNumber,
        array $activities,
        array $categoryIds,
        array $file
    ): int {
        $connection = Database::connection();
        [$temporaryPath, $relativePath, $absolutePath] = $this->stageFile($file);

        try {
            $connection->beginTransaction();
            $userId = (new User($connection))->create([
                'full_name' => $input['contact_person'],
                'mobile_number' => $mobileNumber,
                'email' => $input['company_email'],
                'password_hash' => password_hash(
                    $input['company_password'],
                    PASSWORD_DEFAULT
                ),
                'role' => 'RECYCLER',
                'account_status' => 'PENDING',
            ]);

            (new AuthorizedRecycler($connection))->create([
                'user_id' => $userId,
                'company_name' => $input['company_name'],
                'business_address' => $input['business_address'],
                'district' => $input['district'],
                'verification_status' => 'PENDING',
            ]);

            (new RecyclerLicense($connection))->create([
                'recycler_user_id' => $userId,
                'license_number' => $input['swml_number'],
                'expiry_date' => $input['licence_expiry'],
                'license_status' => 'PENDING',
                'document_path' => $relativePath,
            ]);

            $capabilityModel = new RecyclerCapability($connection);
            foreach ($categoryIds as $categoryId) {
                $capabilityModel->create([
                    'recycler_user_id' => $userId,
                    'category_id' => $categoryId,
                    'can_handle_high_risk' => 0,
                    'capability_status' => 'PENDING',
                ]);
            }

            $activityModel = new RecyclerAuthorizedActivity($connection);
            foreach ($activities as $activity) {
                $activityModel->create([
                    'recycler_user_id' => $userId,
                    'activity_type' => $activity,
                    'activity_status' => 'PENDING',
                ]);
            }

            if (!rename($temporaryPath, $absolutePath)) {
                throw new RuntimeException('The licence document could not be finalized.');
            }

            $connection->commit();
            return $userId;
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
            if (is_file($absolutePath)) {
                unlink($absolutePath);
            }

            throw $exception;
        }
    }

    private function validatePassword(array $input, array &$errors): void
    {
        $password = (string) $input['company_password'];

        if ($password !== '' && preg_match('/[A-Z]/', $password) !== 1) {
            $errors['company_password'][] =
                'Password must contain at least one uppercase letter.';
        }
        if ($password !== '' && preg_match('/[a-z]/', $password) !== 1) {
            $errors['company_password'][] =
                'Password must contain at least one lowercase letter.';
        }
        if ($password !== '' && preg_match('/[0-9]/', $password) !== 1) {
            $errors['company_password'][] =
                'Password must contain at least one number.';
        }
        if ($password !== (string) $input['confirm_password']) {
            $errors['confirm_password'][] = 'Password confirmation does not match.';
        }
    }

    private function validateExpiry(string $value, array &$errors): void
    {
        if (isset($errors['licence_expiry'])) {
            return;
        }

        $timezone = new DateTimeZone('Asia/Colombo');
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, $timezone);
        $dateErrors = DateTimeImmutable::getLastErrors();
        $isExact = $date !== false && $date->format('Y-m-d') === $value;
        $hasParseErrors = is_array($dateErrors)
            && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0);

        if (!$isExact || $hasParseErrors) {
            $errors['licence_expiry'][] = 'License expiry must be a valid date.';
            return;
        }

        $today = new DateTimeImmutable('today', $timezone);
        if ($date <= $today) {
            $errors['licence_expiry'][] = 'License expiry must be in the future.';
        }
    }

    private function validateActivities(mixed $submitted, array &$errors): array
    {
        if (!is_array($submitted) || $submitted === []) {
            $errors['activities'][] = 'Select at least one license activity.';
            return [];
        }

        $values = [];
        foreach ($submitted as $value) {
            if (!is_string($value) || !array_key_exists($value, self::ACTIVITIES)) {
                $errors['activities'][] = 'The selected license activities are invalid.';
                return [];
            }
            $values[] = $value;
        }

        if (count($values) !== count(array_unique($values))) {
            $errors['activities'][] = 'Duplicate license activities are not allowed.';
            return [];
        }

        return $values;
    }

    private function validateCapabilities(mixed $submitted, array &$errors): array
    {
        if (!is_array($submitted) || $submitted === []) {
            $errors['requested_capabilities'][] =
                'Select at least one requested capability.';
            return [];
        }

        $ids = [];
        foreach ($submitted as $value) {
            if (!is_string($value) || preg_match('/^[1-9][0-9]*$/', $value) !== 1) {
                $errors['requested_capabilities'][] =
                    'The selected capabilities are invalid.';
                return [];
            }
            $ids[] = (int) $value;
        }

        if (count($ids) !== count(array_unique($ids))) {
            $errors['requested_capabilities'][] =
                'Duplicate capabilities are not allowed.';
            return [];
        }

        $validIds = (new WasteCategory())->selectableIds($ids);
        sort($ids);
        sort($validIds);
        if ($ids !== $validIds) {
            $errors['requested_capabilities'][] =
                'One or more selected capabilities are unavailable.';
            return [];
        }

        return $ids;
    }

    private function validateFile(array $file, array &$errors): void
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error !== UPLOAD_ERR_OK) {
            $errors['licence_copy'][] = $error === UPLOAD_ERR_INI_SIZE
                || $error === UPLOAD_ERR_FORM_SIZE
                ? 'The license PDF exceeds the upload size limit.'
                : 'A license PDF is required.';
            return;
        }

        $temporaryName = $file['tmp_name'] ?? '';
        $originalName = $file['name'] ?? '';
        if (!is_string($temporaryName) || !is_uploaded_file($temporaryName)) {
            $errors['licence_copy'][] = 'The license upload is invalid.';
            return;
        }
        $size = filesize($temporaryName);
        if ($size === false || $size < 5 || $size > self::MAX_PDF_BYTES) {
            $errors['licence_copy'][] = 'The license PDF must not exceed 5 MB.';
            return;
        }
        if (!is_string($originalName)
            || strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'pdf') {
            $errors['licence_copy'][] = 'The license document must use the PDF extension.';
            return;
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryName);
        if ($mime !== 'application/pdf') {
            $errors['licence_copy'][] = 'The license document must be a valid PDF.';
            return;
        }

        $handle = fopen($temporaryName, 'rb');
        $signature = $handle === false ? false : fread($handle, 5);
        $ending = false;
        if (is_resource($handle)) {
            $tailLength = min(2048, $size);
            if (fseek($handle, -$tailLength, SEEK_END) === 0) {
                $ending = fread($handle, $tailLength);
            }
        }
        if (is_resource($handle)) {
            fclose($handle);
        }
        if ($signature !== '%PDF-'
            || !is_string($ending)
            || !str_contains($ending, '%%EOF')) {
            $errors['licence_copy'][] = 'The license document must be a valid PDF.';
        }
    }

    private function validateUniqueness(
        array $input,
        ?string $mobileNumber,
        array &$errors
    ): void {
        $userModel = new User();
        if ($mobileNumber !== null
            && $userModel->findByMobileNumber($mobileNumber) !== null) {
            $errors['phone'][] = 'An account already exists with this mobile number.';
        }
        if ($input['company_email'] !== ''
            && $userModel->findByEmail($input['company_email']) !== null) {
            $errors['company_email'][] = 'An account already exists with this email address.';
        }
        if ((new AuthorizedRecycler())->findByCompanyName($input['company_name']) !== null) {
            $errors['company_name'][] = 'A recycler already exists with this company name.';
        }
        if ((new RecyclerLicense())->findByLicenseNumber($input['swml_number']) !== null) {
            $errors['swml_number'][] = 'This license number is already registered.';
        }
    }

    private function stageFile(array $file): array
    {
        $rootPath = defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 2);
        $directory = $rootPath . '/storage/' . self::LICENSE_DIRECTORY;
        $stagingDirectory = $directory . '/.staging';

        if (!is_dir($stagingDirectory)
            && !mkdir($stagingDirectory, 0750, true)
            && !is_dir($stagingDirectory)) {
            throw new RuntimeException('The license storage directory is unavailable.');
        }

        $filename = bin2hex(random_bytes(24)) . '.pdf';
        $temporaryPath = $stagingDirectory . '/' . $filename . '.part';
        $absolutePath = $directory . '/' . $filename;
        if (!move_uploaded_file((string) $file['tmp_name'], $temporaryPath)) {
            throw new RuntimeException('The license document could not be stored.');
        }

        return [
            $temporaryPath,
            self::LICENSE_DIRECTORY . '/' . $filename,
            $absolutePath,
        ];
    }
}
