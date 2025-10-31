<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityCheckerBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DoctrineEntityCheckerBundle\Exception\EntityCheckerException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(EntityCheckerException::class)]
final class EntityCheckerExceptionTest extends AbstractExceptionTestCase
{
    public function testCanInstantiate(): void
    {
        $exception = new EntityCheckerException('Test message');

        $this->assertInstanceOf(EntityCheckerException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function testCanInstantiateWithCode(): void
    {
        $exception = new EntityCheckerException('Test message', 123);

        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals(123, $exception->getCode());
    }

    public function testCanInstantiateWithPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new EntityCheckerException('Test message', 0, $previous);

        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
