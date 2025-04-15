<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityCheckerBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrineEntityCheckerBundle\Service\EntityPrimaryKeyService;

class EntityPrimaryKeyServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private ClassMetadata $metadata;
    private EntityPrimaryKeyService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->metadata = $this->createMock(ClassMetadata::class);
        $this->service = new EntityPrimaryKeyService($this->entityManager);
    }

    public function testGetPrimaryKeyValues(): void
    {
        // 准备测试数据
        $entity = new \stdClass();
        $identifierFieldNames = ['id', 'code'];

        // 设置模拟行为
        $this->entityManager
            ->expects($this->once())
            ->method('getClassMetadata')
            ->with(\stdClass::class)
            ->willReturn($this->metadata);

        $this->metadata
            ->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn($identifierFieldNames);

        $this->metadata
            ->expects($this->exactly(2))
            ->method('getFieldValue')
            ->willReturnMap([
                [$entity, 'id', 123],
                [$entity, 'code', 'ABC'],
            ]);

        // 执行测试
        $result = $this->service->getPrimaryKeyValues($entity);

        // 验证结果
        $this->assertEquals(['id' => 123, 'code' => 'ABC'], $result);
    }

    public function testHasCompositeIdentifier(): void
    {
        // 测试使用类名
        $entityClass = 'App\Entity\TestEntity';

        // 设置模拟行为 - 有复合主键
        $this->entityManager
            ->expects($this->once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($this->metadata);

        $this->metadata
            ->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id', 'code']);

        // 执行测试
        $result = $this->service->hasCompositeIdentifier($entityClass);

        // 验证结果
        $this->assertTrue($result);
    }

    public function testHasNoCompositeIdentifier(): void
    {
        // 测试使用对象
        $entity = new \stdClass();

        // 设置模拟行为 - 只有单一主键
        $this->entityManager
            ->expects($this->once())
            ->method('getClassMetadata')
            ->with(\stdClass::class)
            ->willReturn($this->metadata);

        $this->metadata
            ->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        // 执行测试
        $result = $this->service->hasCompositeIdentifier($entity);

        // 验证结果
        $this->assertFalse($result);
    }

    public function testGetIdentifierFieldNames(): void
    {
        // 测试使用类名
        $entityClass = 'App\Entity\TestEntity';
        $identifierFieldNames = ['id', 'code'];

        // 设置模拟行为
        $this->entityManager
            ->expects($this->once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($this->metadata);

        $this->metadata
            ->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn($identifierFieldNames);

        // 执行测试
        $result = $this->service->getIdentifierFieldNames($entityClass);

        // 验证结果
        $this->assertEquals($identifierFieldNames, $result);
    }
}
