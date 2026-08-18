<?php
declare(strict_types=1);

final class PublicRegistrationService
{
    private const POSTAL_CODES = [
        '11100',
        '10800',
        '10600',
        '10500',
        '00800',
        '00700',
    ];

    public function validate(array $input): array
    {
        $validator = new Validator();
        $validator->validate($input, [
            'first_name' => ['required', 'min:2', 'max:60'],
            'last_name' => ['required', 'min:2', 'max:60'],
            'contact_number' => ['required', 'max:20'],
            'email' => ['email', 'max:255'],
            'password' => ['required', 'min:8', 'max:128'],
            'password_confirmation' => ['required', 'max:128'],
            'postal_code' => [
                'required',
                'in:' . implode(',', self::POSTAL_CODES),
            ],
            'address' => ['required', 'min:5', 'max:500'],
        ]);

        $errors = $validator->errors();
        $mobileNumber = MobileNumber::normalize($input['contact_number']);

        if ($mobileNumber === null) {
            $errors['contact_number'][] =
                'Enter a valid Sri Lankan mobile number.';
        }

        $postalArea = null;

        if (!isset($errors['postal_code'])) {
            $postalArea = (new PostalCodeArea())->findActiveByPostalCode(
                $input['postal_code']
            );

            if ($postalArea === null) {
                $errors['postal_code'][] =
                    'The selected postal area is not currently available.';
            }
        }

        $this->validatePassword($input, $errors);
        $this->validateUniqueness($input, $mobileNumber, $errors);

        return [
            'errors' => $errors,
            'mobile_number' => $mobileNumber,
            'postal_area_id' => $postalArea === null
                ? null
                : (int) $postalArea['postal_area_id'],
        ];
    }

    public function createPendingUser(
        array $input,
        string $mobileNumber,
        int $postalAreaId
    ): int {
        $connection = Database::connection();

        try {
            $connection->beginTransaction();
            $userId = (new User($connection))->create([
                'full_name' => trim(
                    $input['first_name'] . ' ' . $input['last_name']
                ),
                'mobile_number' => $mobileNumber,
                'email' => $input['email'] === '' ? null : $input['email'],
                'password_hash' => password_hash(
                    $input['password'],
                    PASSWORD_DEFAULT
                ),
                'role' => 'PUBLIC_USER',
                'account_status' => 'PENDING',
            ]);

            (new PublicProfile($connection))->create([
                'user_id' => $userId,
                'postal_area_id' => $postalAreaId,
                'address' => $input['address'],
            ]);

            $connection->commit();

            return $userId;
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    private function validatePassword(array $input, array &$errors): void
    {
        $password = $input['password'];

        if ($password !== '' && preg_match('/[A-Z]/', $password) !== 1) {
            $errors['password'][] =
                'Password must contain at least one uppercase letter.';
        }

        if ($password !== '' && preg_match('/[a-z]/', $password) !== 1) {
            $errors['password'][] =
                'Password must contain at least one lowercase letter.';
        }

        if ($password !== '' && preg_match('/[0-9]/', $password) !== 1) {
            $errors['password'][] =
                'Password must contain at least one number.';
        }

        if ($password !== $input['password_confirmation']) {
            $errors['password_confirmation'][] =
                'Password confirmation does not match.';
        }
    }

    private function validateUniqueness(
        array $input,
        ?string $mobileNumber,
        array &$errors
    ): void {
        $userModel = new User();

        if (
            $mobileNumber !== null
            && $userModel->findByMobileNumber($mobileNumber) !== null
        ) {
            $errors['contact_number'][] =
                'An account already exists with this mobile number.';
        }

        if (
            $input['email'] !== ''
            && $userModel->findByEmail($input['email']) !== null
        ) {
            $errors['email'][] =
                'An account already exists with this email address.';
        }
    }
}
