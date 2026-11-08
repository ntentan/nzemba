<?php

namespace ntentan\nzemba\tests\cases\generators;

use ntentan\nzemba\backends\Postgres;
use ntentan\nzemba\backends\SqlDatabase;
use ntentan\nzemba\tests\fixtures\User;
use PHPUnit\Framework\TestCase;

class PostgresTest extends TestCase
{
    public function testQuoteIdentifier(): void
    {
        $helper = new Postgres();

        $this->assertEquals('"users"', $helper->quoteIdentifier('users'));
        $this->assertEquals('"user_name"', $helper->quoteIdentifier('user_name'));
        $this->assertEquals('"createdAt"', $helper->quoteIdentifier('createdAt'));
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

        $backend = new SqlDatabase($pdo, new Postgres());
        $insertedId = $backend->insert('users', $data);

        $this->assertEquals(15, $insertedId);
    }

    public function testInsertWithRealDatabase(): void
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE "users" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "name" TEXT, "email" TEXT)');

        $backend = new SqlDatabase($pdo, new Postgres());
        $id = $backend->insert('users', [
            'name' => 'Bob',
            'email' => 'bob@example.com'
        ]);

        $this->assertEquals(1, $id);

        $stmt = $pdo->query('SELECT * FROM "users" WHERE "id" = 1');
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertEquals('Bob', $row['name']);
        $this->assertEquals('bob@example.com', $row['email']);
    }

    public function testFindBuildsQueryAndReturnsHydratedModels(): void
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE "users" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "name" TEXT, "email" TEXT, "age" INTEGER)');

        $backend = new SqlDatabase($pdo, new Postgres());
        $backend->insert('users', ['name' => 'Alice', 'email' => 'alice@example.com', 'age' => 30]);
        $backend->insert('users', ['name' => 'Bob', 'email' => 'bob@example.com', 'age' => 25]);
        $backend->insert('users', ['name' => 'Charlie', 'email' => 'charlie@example.com', 'age' => 30]);

        $results = $backend->find('users', ['age' => 30], User::class);

        $this->assertCount(2, $results);
        $this->assertContainsOnlyInstancesOf(User::class, $results);
        $this->assertEquals('Alice', $results[0]->name);
        $this->assertEquals('Charlie', $results[1]->name);

        $allResults = $backend->find('users', null, User::class);
        $this->assertCount(3, $allResults);
    }

    public function testFindOneReturnsHydratedModelOrNullWhenNotFound(): void
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE "users" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "name" TEXT, "email" TEXT, "age" INTEGER)');

        $backend = new SqlDatabase($pdo, new Postgres());
        $backend->insert('users', ['name' => 'Alice', 'email' => 'alice@example.com', 'age' => 30]);

        $user = $backend->findOne('users', ['email' => 'alice@example.com'], User::class);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Alice', $user->name);
        $this->assertEquals(30, $user->age);

        $missing = $backend->findOne('users', ['email' => 'missing@example.com'], User::class);
        $this->assertNull($missing);
    }
}
