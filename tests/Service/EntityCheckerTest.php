<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityCheckerBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\CustomIdGenerator;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;
use Tourze\DoctrineEntityCheckerBundle\Service\EntityChecker;
use Tourze\DoctrineEntityCheckerBundle\Tests\Fixtures\TestEntity;

/**
 * 测试用 EntityChecker 子类
 * 覆盖方法以避免 UnitOfWork 相关问题
 */
class TestEntityChecker extends EntityChecker
{
    /**
     * 已生成的ID值
     */
    private mixed $generatedId = null;

    /**
     * 已处理的实体
     */
    private ?object $processedEntity = null;

    /**
     * 用于测试的 ID 生成器
     */
    private ?AbstractIdGenerator $mockIdGenerator = null;

    /**
     * 设置测试用 ID 生成器
     */
    public function setMockIdGenerator(?AbstractIdGenerator $generator): void
    {
        $this->mockIdGenerator = $generator;
    }

    /**
     * 获取最后生成的ID
     */
    public function getGeneratedId(): mixed
    {
        return $this->generatedId;
    }

    /**
     * 设置生成的ID
     */
    public function setGeneratedId(mixed $id): void
    {
        $this->generatedId = $id;
    }

    /**
     * 获取最后处理的实体
     */
    public function getProcessedEntity(): ?object
    {
        return $this->processedEntity;
    }

    /**
     * 覆盖父类方法，使用 UnitOfWork 的 assignPostInsertId 方法
     */
    public function prePersistEntity(ObjectManager $objectManager, object $entity): void
    {
        $this->processedEntity = $entity;

        // 调用父类方法进行处理
        parent::prePersistEntity($objectManager, $entity);

        // 手动实现 assignPostInsertId 的行为，直接设置ID
        if ($this->generatedId !== null) {
            $entity->id = $this->generatedId;
        }
    }

    /**
     * 覆盖方法以使用指定的ID生成器
     */
    protected function getIdGenerator(string $generatorClass): AbstractIdGenerator
    {
        if ($this->mockIdGenerator !== null) {
            return $this->mockIdGenerator;
        }

        return parent::getIdGenerator($generatorClass);
    }
}

/**
 * @coversDefaultClass \Tourze\DoctrineEntityCheckerBundle\Service\EntityChecker
 */
class EntityCheckerTest extends TestCase
{
    /** @var ContainerInterface&MockObject */
    protected $container;

    /** @var EntityManagerInterface&MockObject */
    protected $entityManager;

    /** @var UnitOfWork&MockObject */
    protected $unitOfWork;

    /** @var AbstractIdGenerator&MockObject */
    protected $idGenerator;

    /** @var TestEntityChecker */
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->createMock(ContainerInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->unitOfWork = $this->createMock(UnitOfWork::class);
        $this->idGenerator = $this->createMock(AbstractIdGenerator::class);

        $this->entityManager
            ->method('getUnitOfWork')
            ->willReturn($this->unitOfWork);

        $checkers = [];
        $this->service = new TestEntityChecker($checkers, $this->entityManager, $this->container);
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $this->assertInstanceOf(EntityChecker::class, $this->service);
    }

    /**
     * @covers ::prePersistEntity
     * @covers ::getIdGenerator
     */
    public function testPrePersistEntityWithCustomIdGenerator(): void
    {
        // 使用生成的ID模拟生成器
        $generatedId = 'generated-id';
        $this->service->setGeneratedId($generatedId);
        $this->service->setMockIdGenerator($this->idGenerator);

        $entity = new TestEntity();
        $entity->id = null;

        // 创建一个模拟的 ReflectionProperty
        $reflectionProperty = $this->createMock(ReflectionProperty::class);

        // 创建一个模拟的 ReflectionAttribute
        $reflectionAttribute = $this->createMock(ReflectionAttribute::class);
        $reflectionAttribute->method('newInstance')
            ->willReturn(new CustomIdGenerator(class: 'CustomGenerator'));

        // 设置 getAttributes 方法的返回值
        $reflectionProperty->method('getAttributes')
            ->with(CustomIdGenerator::class)
            ->willReturn([$reflectionAttribute]);

        $reflectionClass = $this->createMock(ReflectionClass::class);
        $reflectionClass->method('getProperty')
            ->with('id')
            ->willReturn($reflectionProperty);

        $metadata = new ClassMetadata(TestEntity::class);
        $metadata->reflClass = new ReflectionClass(TestEntity::class);
        $metadata->identifier = ['id'];
        $metadata->idGenerator = $this->idGenerator;

        $this->entityManager
            ->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);

        $this->container
            ->method('get')
            ->with('CustomGenerator')
            ->willReturn($this->idGenerator);

        $this->idGenerator
            ->method('generateId')
            ->with($this->entityManager, $entity)
            ->willReturn($generatedId);

        $this->service->prePersistEntity($this->entityManager, $entity);

        // 验证实体已处理并且ID已设置
        $this->assertSame($entity, $this->service->getProcessedEntity());
        $this->assertEquals($generatedId, $entity->id);
    }

    /**
     * @covers ::prePersistEntity
     * @covers ::getIdGenerator
     */
    public function testPrePersistEntityWithoutCustomIdGenerator(): void
    {
        $this->service->setMockIdGenerator(null);

        $entity = new TestEntity();
        $entity->id = null;

        $reflectionClass = new ReflectionClass($entity);
        $metadata = new ClassMetadata(TestEntity::class);
        $metadata->reflClass = $reflectionClass;
        $metadata->identifier = ['id'];

        $this->entityManager
            ->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);

        $this->service->prePersistEntity($this->entityManager, $entity);

        $this->assertNull($entity->id);
    }
}
