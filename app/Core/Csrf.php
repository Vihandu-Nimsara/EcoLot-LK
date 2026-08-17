<?php
declare(strict_types=1);

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        $token = Session::get(self::SESSION_KEY);

        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::put(self::SESSION_KEY, $token);
        }

        return $token;
    }

    public static function validate(?string $submittedToken): bool
    {
        if ($submittedToken === null || $submittedToken === '') {
            return false;
        }

        $storedToken = Session::get(self::SESSION_KEY);

        if (!is_string($storedToken) || $storedToken === '') {
            return false;
        }

        return hash_equals($storedToken, $submittedToken);
    }

    public static function regenerate(): string
    {
        $token = bin2hex(random_bytes(32));
        Session::put(self::SESSION_KEY, $token);

        return $token;
    }
}