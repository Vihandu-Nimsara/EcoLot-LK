<?php
declare(strict_types=1);

final class Auth
{
    private const USER_KEY = 'auth_user';

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::put(self::USER_KEY, $user);
    }

    public static function logout(): void
    {
        Session::forget(self::USER_KEY);
        Session::regenerate();
    }

    public static function check(): bool
    {
        return Session::has(self::USER_KEY);
    }

    public static function guest(): bool
    {
        return !self::check();
    }

    public static function user(): ?array
    {
        $user = Session::get(self::USER_KEY);

        return is_array($user) ? $user : null;
    }

    public static function id(): int|string|null
    {
        return self::user()['id'] ?? null;
    }

    public static function role(): ?string
    {
        $role = self::user()['role'] ?? null;

        return is_string($role) ? $role : null;
    }

    public static function hasRole(string|array $roles): bool
    {
        return in_array(self::role(), (array) $roles, true);
    }
}
