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
        Session::destroy();
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

    public static function dashboardPath(?string $role = null): ?string
    {
        $config = require APP_ROOT . '/config/app.php';
        $prefix = $config['roles'][strtolower($role ?? self::role() ?? '')]['route_prefix'] ?? null;
        return is_string($prefix) ? rtrim($prefix, '/') . '/dashboard' : null;
    }

    /** One current-state read per protected Admin/Recycler entry; never session-cached. */
    public static function currentAccountAllows(string $role, ?PDO $db = null): bool
    {
        if (!in_array($role, ['ADMIN', 'RECYCLER'], true)) return true;
        $db ??= Database::connection();
        $membership = $role === 'ADMIN'
            ? 'JOIN administrators membership ON membership.user_id = u.user_id'
            : "JOIN authorized_recyclers membership ON membership.user_id = u.user_id AND membership.verification_status = 'VERIFIED'";
        $statement = $db->prepare("SELECT u.user_id FROM users u $membership
            WHERE u.user_id = ? AND u.role = ? AND u.account_status = 'ACTIVE'");
        $statement->execute([(int) self::id(), $role]);
        return $statement->fetchColumn() !== false;
    }

    public static function requireRole(string $role): void
    {
        if (!self::check()) {
            $config = require APP_ROOT . '/config/app.php';
            header('Location: ' . rtrim($config['base_path'], '/') . '/login');
            exit;
        }

        if (self::role() !== $role) {
            http_response_code(403);
            echo '403 Forbidden';
            exit;
        }

        if (in_array($role, ['ADMIN', 'RECYCLER'], true)) {
            try {
                $allowed = self::currentAccountAllows($role);
            } catch (Throwable $error) {
                // Fail closed without exposing credentials, SQL or document paths.
                error_log('Privileged account check failed category=' . $error::class);
                http_response_code(503);
                echo 'Account access could not be verified. Please try again later.';
                exit;
            }
            if (!$allowed) {
                self::logout();
                http_response_code(403);
                echo '403 This account is no longer authorized. Please sign in again.';
                exit;
            }
        }
    }
}
