<?php

namespace ntentan\nzemba\tests\cases;

use ntentan\kaikai\backends\VolatileCache;
use ntentan\kaikai\Cache;
use ntentan\nzemba\exceptions\RepositoryException;
use ntentan\nzemba\generators\Generator;
use ntentan\nzemba\generators\Postgres;
use ntentan\nzemba\Repository;
use ntentan\nzemba\tests\fixtures\UntypedModel;
use ntentan\nzemba\tests\fixtures\User;
use ntentan\nzemba\tests\fixtures\UserProfile;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    private function getPrivateProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        return $prop->getValue($object);
    }

    private function invokePrivateMethod(object $object, string $method, array $args = []): mixed
    {
        $reflection = new \ReflectionClass($object);
        $m = $reflection->getMethod($method);
        return $m->invokeArgs($object, $args);
    }

    public function testDefaultTableNameDerivation(): void
    {
        $generator = $this->createStub(Generator::class);

        $repoUser = new Repository(User::class, $generator);
        $this->assertEquals('users', $this->getPrivateProperty($repoUser, 'table'));

        $repoProfile = new Repository(UserProfile::class, $generator);
        $this->assertEquals('user_profiles', $this->getPrivateProperty($repoProfile, 'table'));
    }

    public function testCustomTableNameOverride(): void
    {
        $generator = $this->createStub(Generator::class);
        $repo = new Repository(User::class, $generator, null, 'custom_users_table');

        $this->assertEquals('custom_users_table', $this->getPrivateProperty($repo, 'table'));
    }

    public function testDefaultAndCustomCache(): void
    {
        $generator = $this->createStub(Generator::class);

        // Default cache
        $repoDefault = new Repository(User::class, $generator);
        $defaultCache = $this->getPrivateProperty($repoDefault, 'cache');
        $this->assertInstanceOf(Cache::class, $defaultCache);

        // Custom cache
        $customCache = new Cache(new VolatileCache());
        $repoCustom = new Repository(User::class, $generator, $customCache);
        $this->assertSame($customCache, $this->getPrivateProperty($repoCustom, 'cache'));
    }

    public function testGetFieldsExtractsMetadataAndCaches(): void
    {
        $generator = $this->createStub(Generator::class);
        $cache = new Cache(new VolatileCache());
        $repo = new Repository(User::class, $generator, $cache);

        $fields = $this->invokePrivateMethod($repo, 'getFields');

        $this->assertEquals([
            ['name' => 'id', 'type' => '?int', 'is_required' => false],
            ['name' => 'name', 'type' => 'string', 'is_required' => true],
            ['name' => 'email', 'type' => '?string', 'is_required' => false],
            ['name' => 'age', 'type' => 'int', 'is_required' => true],
        ], $fields);

        // Ensure metadata is stored in cache
        $cachedFields = $cache->read('repository:users');
        $this->assertEquals($fields, $cachedFields);

        // Second call should return cached data
        $fieldsSecondCall = $this->invokePrivateMethod($repo, 'getFields');
        $this->assertEquals($fields, $fieldsSecondCall);
    }

    public function testGetFieldsWithUntypedProperties(): void
    {
        $generator = $this->createStub(Generator::class);
        $repo = new Repository(UntypedModel::class, $generator);

        $fields = $this->invokePrivateMethod($repo, 'getFields');

        $this->assertEquals([
            ['name' => 'id', 'type' => null, 'is_required' => false],
            ['name' => 'title', 'type' => null, 'is_required' => false],
        ], $fields);
    }

    public function testInsertFromArrayFiltersAndMapsData(): void
    {
        $generator = $this->createMock(Generator::class);
        $repo = new Repository(User::class, $generator);

        $inputData = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'ignored_extra_field' => 'should be filtered out'
        ];

        $expectedData = [
            'id' => null,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'age' => null
        ];

        $generator->expects($this->once())
            ->method('insert')
            ->with('users', $expectedData)
            ->willReturn(101);

        $insertedId = $repo->insert($inputData);
        $this->assertEquals(101, $insertedId);
    }

    public function testInsertFromObjectMapsProperties(): void
    {
        $generator = $this->createMock(Generator::class);
        $repo = new Repository(User::class, $generator);

        $user = new User();
        $user->name = 'Jane Doe';
        $user->email = 'jane@example.com';
        $user->age = 30;

        $expectedData = [
            'id' => null,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'age' => 30
        ];

        $generator->expects($this->once())
            ->method('insert')
            ->with('users', $expectedData)
            ->willReturn(102);

        $insertedId = $repo->insert($user);
        $this->assertEquals(102, $insertedId);
    }

    public function testInsertWithInvalidDataTypeThrowsException(): void
    {
        $generator = $this->createStub(Generator::class);
        $repo = new Repository(User::class, $generator);

        $this->expectException(RepositoryException::class);
        $this->expectExceptionMessage('Unknown datatype for repository insert');

        $repo->insert('invalid string data');
    }

    public function testGetServiceWithPgsqlConfig(): void
    {
        $services = Repository::getService([
            'dsn' => 'pgsql:host=127.0.0.1;dbname=testdb',
            'user' => 'postgres',
            'password' => 'secret'
        ]);

        $this->assertArrayHasKey(Generator::class, $services);
        $this->assertEquals(Postgres::class, $services[Generator::class]);

        $this->assertArrayHasKey(\PDO::class, $services);
        $this->assertIsCallable($services[\PDO::class]);

        // Invoking the closure attempts to instantiate PDO with the configured DSN
        $this->expectException(\PDOException::class);
        $services[\PDO::class]();
    }

    public function testGetServiceMissingDsnThrowsException(): void
    {
        $this->expectException(RepositoryException::class);
        $this->expectExceptionMessage('Please specify a driver for the repository backend.');

        Repository::getService([
            'user' => 'postgres',
            'password' => 'secret'
        ]);
    }

    public function testGetServiceUnsupportedDriverThrowsException(): void
    {
        $this->expectException(RepositoryException::class);
        $this->expectExceptionMessage('Unsupported database driver: mysql');

        Repository::getService([
            'dsn' => 'mysql:host=localhost;dbname=testdb',
            'user' => 'root',
            'password' => 'root'
        ]);
    }
}
