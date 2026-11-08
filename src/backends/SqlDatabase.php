<?php

namespace ntentan\nzemba\backends;

class SqlDatabase implements Backend
{
    private \PDO $pdo;
    private SqlHelper $sqlHelper;

    public function __construct(\PDO $pdo, SqlHelper $sqlHelper)
    {
        $this->pdo = $pdo;
        $this->sqlHelper = $sqlHelper;
    }

    public function insert(string $table, array $data): string
    {
        $fields = array_keys($data);
        $query = "INSERT INTO {$this->sqlHelper->quoteIdentifier($table)}("
                . implode(", ", array_map(fn($field) => $this->sqlHelper->quoteIdentifier($field), $fields))
            . ") VALUES ("
                . implode(", ", array_map(fn($field) => ":{$field}", $fields))
            . ")";
        $statement = $this->pdo->prepare($query);
        $statement->execute($data);
        return $this->pdo->lastInsertId();
    }

    private function executeSelect(string $table, mixed $query, string $model, ?int $limit = null): \PDOStatement
    {
        $sql = "SELECT * FROM {$this->sqlHelper->quoteIdentifier($table)}";
        $params = [];

        if (is_array($query) && !empty($query)) {
            $fields = array_keys($query);
            $conditions = implode(
                " AND ",
                array_map(fn($field) => "{$this->sqlHelper->quoteIdentifier($field)} = :{$field}", $fields)
            );
            $sql .= " WHERE {$conditions}";
            $params = $query;
        }

        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
        }

        $statement = $this->pdo->prepare($sql);
        $statement->setFetchMode(\PDO::FETCH_CLASS, $model);
        $statement->execute($params);

        return $statement;
    }

    public function find(string $table, mixed $query = null, string $model = \stdClass::class): array
    {
        return $this->executeSelect($table, $query, $model)->fetchAll();
    }

    public function findOne(string $table, mixed $query = null, string $model = \stdClass::class): ?object
    {
        $result = $this->executeSelect($table, $query, $model, 1)->fetch();
        return $result === false ? null : $result;
    }
}
