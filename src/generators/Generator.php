<?php

namespace ntentan\nzemba\generators;

abstract class Generator
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function insert(string $table, array $data): int
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

    abstract public function quoteIdentifier($identifier);
}
