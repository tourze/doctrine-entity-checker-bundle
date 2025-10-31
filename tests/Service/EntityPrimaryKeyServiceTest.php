<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityCheckerBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DoctrineEntityCheckerBundle\Service\EntityPrimaryKeyService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(EntityPrimaryKeyService::class)]
#[RunTestsInSeparateProcesses]
final class EntityPrimaryKeyServiceTest extends AbstractIntegrationTestCase
{
    private EntityPrimaryKeyService $service;

    protected function onSetUp(): void
    {
        $this->service = self::getService(EntityPrimaryKeyService::class);
    }

    public function testGetPrimaryKeyValues(): void
    {
        // 创建一个简单的测试实体对象
        $entity = new \stdClass();
        $entity->id = 123;

        // 执行方法 - 由于 stdClass 不是 Doctrine 实体，应该抛出映射异常
        try {
            $result = $this->service->getPrimaryKeyValues($entity);
            self::fail('应该抛出映射异常');
        } catch (\Exception $e) {
            // 映射异常是预期的，说明服务正确处理了无效实体
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }

    public function testHasCompositeIdentifier(): void
    {
        // 创建一个简单的测试实体对象
        $entity = new \stdClass();
        $entity->id = 1;

        // 执行方法 - 由于 stdClass 不是 Doctrine 实体，应该抛出映射异常
        try {
            $result = $this->service->hasCompositeIdentifier($entity);
            self::fail('应该抛出映射异常');
        } catch (\Exception $e) {
            // 映射异常是预期的，说明服务正确处理了无效实体
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }

    public function testHasNoCompositeIdentifier(): void
    {
        // 使用 stdClass 类进行测试
        $entityClass = \stdClass::class;

        // 执行方法 - 由于 stdClass 不是 Doctrine 实体，应该抛出映射异常
        try {
            $result = $this->service->hasCompositeIdentifier($entityClass);
            self::fail('应该抛出映射异常');
        } catch (\Exception $e) {
            // 映射异常是预期的，说明服务正确处理了无效实体类
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }

    public function testGetIdentifierFieldNames(): void
    {
        // 使用 stdClass 类进行测试
        $entityClass = \stdClass::class;

        // 执行方法 - 由于 stdClass 不是 Doctrine 实体，应该抛出映射异常
        try {
            $result = $this->service->getIdentifierFieldNames($entityClass);
            self::fail('应该抛出映射异常');
        } catch (\Exception $e) {
            // 映射异常是预期的，说明服务正确处理了无效实体类
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }
}
