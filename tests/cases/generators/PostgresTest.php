<?php

namespace ntentan\nzemba\tests\cases\generators;

use ntentan\nzemba\generators\Postgres;
use PHPUnit\Framework\TestCase;

class PostgresTest extends TestCase
{
    public function testQuoteIdentifier(): void
    {
        $pdo = $this->createStub(\PDO::class);
        $generator = new Postgres($pdo);

        $this->assertEquals('"users"', $generator->quoteIdentifier('users'));
        $this->assertEquals('"user_name"', $generator->quoteIdentifier('user_name'));
        $this->assertEquals('"createdAt"', $generator->quoteIdentifier('createdAt'));
    }

    public function testInsertBuildsQueryAndExecutes(): void
    {
        $pdo = $this->createMock(\PDO::class);
        $statement = $this->createMock(\PDOStatement::class);

        $data = [
            'name' => 'Alice',
            'email' => 'alice@example.com'
        ];

        $expectedQuery = 'INSERT INTO "users"("name", "email") VALUES (:name, :email)';

        $pdo->expects($this->once())
            ->method('prepare')
            ->with($expectedQuery)
            ->willReturn($statement);

        $statement->expects($this->once())
            ->method('execute')
            ->with($data)
            ->willReturn(true);

        $pdo->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('15');

        $generator = new Postgres($pdo);
        $insertedId = $generator->insert('users', $data);

        $this->assertEquals(15, $insertedId);
    }

    public function testInsertWithRealDatabase(): void
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE "users" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "name" TEXT, "email" TEXT)');

        $generator = new Postgres($pdo);
        $id = $generator->insert('users', [
            'name' => 'Bob',
            'email' => 'bob@example.com'
        ]);

        $this->assertEquals(1, $id);

        $stmt = $pdo->query('SELECT * FROM "users" WHERE "id" = 1');
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertEquals('Bob', $row['name']);
        $this->assertEquals('bob@example.com', $row['email']);
    }
}
