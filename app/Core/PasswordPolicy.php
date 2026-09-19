<?php
declare(strict_types=1);

final class PasswordPolicy
{
    public static function validate(
        string $password,
        string $confirmation,
        array &$errors,
        string $field = 'password',
        string $confirmationField = 'password_confirmation'
    ): void {
        foreach (['/[A-Z]/' => 'uppercase letter', '/[a-z]/' => 'lowercase letter', '/[0-9]/' => 'number'] as $pattern => $label) {
            if ($password !== '' && preg_match($pattern, $password) !== 1) {
                $errors[$field][] = 'Password must contain at least one ' . $label . '.';
            }
        }
        // PASSWORD_DEFAULT currently uses bcrypt, which truncates after 72 bytes.
        if (strlen($password) > 72 || str_contains($password, "\0")) {
            $errors[$field][] = 'Password must be at most 72 bytes and contain no null characters.';
        }
        if ($password !== $confirmation) {
            $errors[$confirmationField][] = 'Password confirmation does not match.';
        }
    }
}
