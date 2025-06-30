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
use Tourze\DoctrineEntityCheckerBundle\Tests\Fixtures\TestEntityCheckerSubclass;


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

    /** @var TestEntityCheckerSubclass */
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
        $this->service = new TestEntityCheckerSubclass($checkers, $this->entityManager, $this->container);
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
