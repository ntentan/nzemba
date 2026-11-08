<?php

namespace ntentan\nzemba\backends;

abstract class SqlDatabase implements Backend
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function insert(string $table, array $data): string
    {
        $fields = array_keys($data);
        $query = "INSERT INTO {$this->quoteIdentifier($table)}("
                . implode(", ", array_map(fn($field) => $this->quoteIdentifier($field), $fields))
            . ") VALUES ("
                . implode(", ", array_map(fn($field) => ":{$field}", $fields))
            . ")";
        $statement = $this->pdo->prepare($query);
        $statement->execute($data);
        return $this->pdo->lastInsertId();
    }

    public function find(mixed $query): array
    {

    }

    public function findOne(mixed $query): object
    {
        // TODO: Implement findFirst() method.
    }

    abstract public function quoteIdentifier($identifier);
}
