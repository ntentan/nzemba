<?php

namespace ntentan\nzemba\tests\cases\exceptions;

use ntentan\nzemba\exceptions\RepositoryException;
use PHPUnit\Framework\TestCase;

class RepositoryExceptionTest extends TestCase
{
    public function testExceptionInheritance(): void
    {
        $exception = new RepositoryException("Something went wrong", 42);

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals("Something went wrong", $exception->getMessage());
        $this->assertEquals(42, $exception->getCode());
    }
}
