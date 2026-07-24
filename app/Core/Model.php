<?php
declare(strict_types=1);

abstract class Model
{
    protected PDO $db;
    protected string $table = '';
    protected string $primaryKey = 'id';

    public function __construct(?PDO $connection = null)
    {
        $this->db = $connection ?? Database::connection();
    }

    public function all(): array
    {
        $table = $this->identifier($this->table);

        return $this->db
            ->query("SELECT * FROM {$table}")
            ->fetchAll();
    }

    public function find(int|string $id): ?array
    {
        $table = $this->identifier($this->table);
        $primaryKey = $this->identifier($this->primaryKey);
        $statement = $this->db->prepare(
            "SELECT * FROM {$table} WHERE {$primaryKey} = :id LIMIT 1"
        );
        $statement->execute(['id' => $id]);
        $result = $statement->fetch();

        return $result === false ? null : $result;
    }

    public function create(array $attributes): int
    {
        if ($attributes === []) {
            throw new InvalidArgumentException('Cannot create a record without attributes.');
        }

        $table = $this->identifier($this->table);
        $columns = array_map([$this, 'identifier'], array_keys($attributes));
        $placeholders = array_map(
            static fn (string $column): string => ':' . $column,
            array_keys($attributes)
        );

        $statement = $this->db->prepare(sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        ));
        $statement->execute($attributes);

        return (int) $this->db->lastInsertId();
    }

    public function update(int|string $id, array $attributes): bool
    {
        if ($attributes === []) {
            return false;
        }

        $table = $this->identifier($this->table);
        $primaryKey = $this->identifier($this->primaryKey);
        $assignments = array_map(
            fn (string $column): string => $this->identifier($column) . ' = :' . $column,
            array_keys($attributes)
        );
        $attributes['_record_id'] = $id;

        $statement = $this->db->prepare(sprintf(
            'UPDATE %s SET %s WHERE %s = :_record_id',
            $table,
            implode(', ', $assignments),
            $primaryKey
        ));

        return $statement->execute($attributes);
    }

    public function delete(int|string $id): bool
    {
        $table = $this->identifier($this->table);
        $primaryKey = $this->identifier($this->primaryKey);
        $statement = $this->db->prepare(
            "DELETE FROM {$table} WHERE {$primaryKey} = :id"
        );

        return $statement->execute(['id' => $id]);
    }

    protected function query(string $sql, array $parameters = []): PDOStatement
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($parameters);

        return $statement;
    }

    private function identifier(string $identifier): string
    {
        if ($identifier === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier)) {
            throw new InvalidArgumentException("Invalid SQL identifier [{$identifier}].");
        }

        return '`' . $identifier . '`';
    }
}
