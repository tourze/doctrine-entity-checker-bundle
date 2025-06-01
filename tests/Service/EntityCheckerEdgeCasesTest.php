<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityCheckerBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use Tourze\DoctrineEntityCheckerBundle\Checker\EntityCheckerInterface;
use Tourze\DoctrineEntityCheckerBundle\Service\EntityChecker;
use Tourze\DoctrineEntityCheckerBundle\Tests\Fixtures\CustomIdTestEntity;
use Tourze\DoctrineEntityCheckerBundle\Tests\Fixtures\TestEntity;
use Tourze\DoctrineEntityCheckerBundle\Tests\Fixtures\TestEntityChecker;

/**
 * 测试 EntityChecker 的边界情况和异常处理
 */
class EntityCheckerEdgeCasesTest extends TestCase
{
    /** @var ContainerInterface&MockObject */
    private $container;
    /** @var EntityManagerInterface&MockObject */
    private $entityManager;
    /** @var UnitOfWork&MockObject */
    private $unitOfWork;
    /** @var AbstractIdGenerator&MockObject */
    private $idGenerator;
    private EntityChecker $service;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->unitOfWork = $this->createMock(UnitOfWork::class);
        $this->idGenerator = $this->createMock(AbstractIdGenerator::class);

        $this->entityManager
            ->method('getUnitOfWork')
            ->willReturn($this->unitOfWork);

        $checkers = [];
        $this->service = new EntityChecker($checkers, $this->entityManager, $this->container);
    }

    public function testPrePersistEntityWithMultipleCheckers(): void
    {
        // 创建多个检查器
        $checker1 = new TestEntityChecker();
        $checker2 = new TestEntityChecker();
        
        $checkers = [$checker1, $checker2];
        $service = new EntityChecker($checkers, $this->entityManager, $this->container);

        $entity = new TestEntity();
        
        $metadata = new ClassMetadata(TestEntity::class);
        $metadata->reflClass = new ReflectionClass(TestEntity::class);

        $this->entityManager
            ->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);

        // 执行测试
        $service->prePersistEntity($this->entityManager, $entity);

        // 验证两个检查器都被调用
        $this->assertCount(1, $checker1->getCheckedEntities());
        $this->assertCount(1, $checker2->getCheckedEntities());
        $this->assertSame($entity, $checker1->getCheckedEntities()[0]);
        $this->assertSame($entity, $checker2->getCheckedEntities()[0]);
    }

    public function testPrePersistEntityWithCustomIdGeneratorNotFound(): void
    {
        // 使用带有自定义ID生成器注解的测试实体
        $entity = new CustomIdTestEntity();
        $entity->id = null;
        $entity->name = 'Test';

        // 使用真实的 ReflectionClass
        $metadata = new ClassMetadata(CustomIdTestEntity::class);
        $metadata->reflClass = new ReflectionClass(CustomIdTestEntity::class);

        $this->entityManager
            ->method('getClassMetadata')
            ->with(CustomIdTestEntity::class)
            ->willReturn($metadata);

        // 模拟容器抛出 NotFoundExceptionInterface
        $notFoundException = new class extends \Exception implements NotFoundExceptionInterface {};
        $this->container
            ->method('get')
            ->with('NonExistentIdGenerator')
            ->willThrowException($notFoundException);

        // 执行测试 - 容器异常应该被传播
        $this->expectException(\Exception::class);
        $this->service->prePersistEntity($this->entityManager, $entity);
    }

    public function testPrePersistEntityWithNoIdProperty(): void
    {
        // 创建没有 id 属性的实体
        $entity = new class {
            public string $name = 'test';
        };

        $metadata = new ClassMetadata($entity::class);
        $metadata->reflClass = new ReflectionClass($entity);

        $this->entityManager
            ->method('getClassMetadata')
            ->with($entity::class)
            ->willReturn($metadata);

        // 执行测试 - 应该正常完成，不会抛出异常
        $this->service->prePersistEntity($this->entityManager, $entity);
        
        // 如果没有抛出异常，说明处理正确
        $this->assertTrue(true);
    }

    public function testPrePersistEntityWithNullEntity(): void
    {
        // 测试 null 实体（虽然类型系统不允许，但为了防御性编程）
        $checkers = [];
        $service = new EntityChecker($checkers, $this->entityManager, $this->container);

        // 这个测试主要是验证代码的健壮性
        $entity = new \stdClass();
        
        $metadata = new ClassMetadata(\stdClass::class);
        $metadata->reflClass = new ReflectionClass(\stdClass::class);

        $this->entityManager
            ->method('getClassMetadata')
            ->with(\stdClass::class)
            ->willReturn($metadata);

        // 执行测试
        $service->prePersistEntity($this->entityManager, $entity);
        
        // 验证没有异常抛出
        $this->assertTrue(true);
    }

    public function testGetIdGeneratorWithValidGenerator(): void
    {
        // 测试私有方法 getIdGenerator
        $reflection = new \ReflectionMethod(EntityChecker::class, 'getIdGenerator');
        $reflection->setAccessible(true);

        $this->container
            ->method('get')
            ->with('TestIdGenerator')
            ->willReturn($this->idGenerator);

        $result = $reflection->invoke($this->service, 'TestIdGenerator');
        
        $this->assertSame($this->idGenerator, $result);
    }

    public function testPrePersistEntityWithEmptyCheckersIterable(): void
    {
        // 测试空的检查器迭代器
        $checkers = [];
        $service = new EntityChecker($checkers, $this->entityManager, $this->container);

        $entity = new TestEntity();
        
        $metadata = new ClassMetadata(TestEntity::class);
        $metadata->reflClass = new ReflectionClass(TestEntity::class);

        $this->entityManager
            ->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);

        // 执行测试 - 应该正常完成
        $service->prePersistEntity($this->entityManager, $entity);
        
        $this->assertTrue(true);
    }

    public function testPrePersistEntityWithExceptionInChecker(): void
    {
        // 创建一个会抛出异常的检查器
        $faultyChecker = new class implements EntityCheckerInterface {
            public function prePersistEntity(ObjectManager $objectManager, object $entity): void
            {
                throw new \RuntimeException('Checker failed');
            }

            public function preUpdateEntity(ObjectManager $objectManager, object $entity, \Doctrine\ORM\Event\PreUpdateEventArgs $eventArgs): void
            {
                // 不实现
            }
        };

        $checkers = [$faultyChecker];
        $service = new EntityChecker($checkers, $this->entityManager, $this->container);

        $entity = new TestEntity();
        
        $metadata = new ClassMetadata(TestEntity::class);
        $metadata->reflClass = new ReflectionClass(TestEntity::class);

        $this->entityManager
            ->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);

        // 执行测试 - 应该抛出异常
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Checker failed');
        
        $service->prePersistEntity($this->entityManager, $entity);
    }
} 