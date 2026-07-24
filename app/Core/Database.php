<?php
declare(strict_types=1);

final class Database
{
    private static ?self $instance = null;
    private PDO $connection;

    private function __construct(?array $config = null)
    {
        $rootPath = defined('APP_ROOT')
            ? APP_ROOT
            : dirname(__DIR__, 2);

        $config ??= require $rootPath . '/config/database.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset'] ?? 'utf8mb4'
        );

        $this->connection = new PDO(
            $dsn,
            (string) $config['username'],
            (string) $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }

    public static function getInstance(?array $config = null): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    public static function connection(): PDO
    {
        return self::getInstance()->connection;
    }

    private function __clone()
    {
    }

    public function __wakeup(): void
    {
        throw new LogicException('Database connections cannot be unserialized.');
    }
}
