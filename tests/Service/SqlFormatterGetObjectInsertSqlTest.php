<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityCheckerBundle\Tests\Service;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\MappingException;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tourze\DoctrineEntityCheckerBundle\Service\EntityChecker;
use Tourze\DoctrineEntityCheckerBundle\Service\EntityPrimaryKeyService;
use Tourze\DoctrineEntityCheckerBundle\Service\SqlFormatter;
use Tourze\DoctrineEntityCheckerBundle\Tests\Fixtures\CategoryTestEntity;
use Tourze\DoctrineEntityCheckerBundle\Tests\Fixtures\ComplexTestEntity;
use Tourze\DoctrineEntityCheckerBundle\Tests\Fixtures\TestEnum;
use Yiisoft\Strings\Inflector;

/**
 * 专门测试 SqlFormatter::getObjectInsertSql 方法
 */
class SqlFormatterGetObjectInsertSqlTest extends TestCase
{
    private SqlFormatter $sqlFormatter;
    /** @var EntityChecker&MockObject */
    private $entityChecker;
    private Inflector $inflector;
    /** @var EntityPrimaryKeyService&MockObject */
    private $primaryKeyService;
    /** @var ObjectManager&MockObject */
    private $objectManager;
    /** @var ClassMetadata&MockObject */
    private $metadata;

    protected function setUp(): void
    {
        $this->entityChecker = $this->createMock(EntityChecker::class);
        $this->inflector = new Inflector();
        $this->primaryKeyService = $this->createMock(EntityPrimaryKeyService::class);
        $this->objectManager = $this->createMock(ObjectManager::class);
        $this->metadata = $this->createMock(ClassMetadata::class);

        $this->sqlFormatter = new SqlFormatter(
            $this->entityChecker,
            $this->inflector,
            $this->primaryKeyService
        );
    }

    public function testGetObjectInsertSqlWithComplexEntity(): void
    {
        // 创建复杂测试实体
        $entity = new ComplexTestEntity();
        $entity->id = 123;
        $entity->name = 'Test Entity';
        $entity->description = 'Test Description';
        $entity->isActive = true;
        $entity->createdAt = new \DateTimeImmutable('2023-01-01 12:00:00');
        $entity->metadata = ['key' => 'value', 'number' => 42];
        $entity->status = TestEnum::ACTIVE;

        // 创建关联实体
        $category = new CategoryTestEntity();
        $category->id = 456;
        $category->name = 'Test Category';
        $entity->category = $category;

        // 模拟 metadata 行为
        $reflectionClass = new ReflectionClass(ComplexTestEntity::class);
        $this->metadata
            ->method('getReflectionClass')
            ->willReturn($reflectionClass);

        $this->objectManager
            ->method('getClassMetadata')
            ->with(ComplexTestEntity::class)
            ->willReturn($this->metadata);

        // 模拟 EntityChecker 行为
        $this->entityChecker
            ->expects($this->once())
            ->method('prePersistEntity')
            ->with($this->objectManager, $entity);

        // 模拟 PrimaryKeyService 行为 - 处理不同类型的对象
        $this->primaryKeyService
            ->method('getPrimaryKeyValues')
            ->willReturnCallback(function ($object) {
                if ($object instanceof CategoryTestEntity) {
                    return ['id' => 456];
                }
                // 对于其他对象类型（如 DateTimeImmutable），抛出 MappingException
                throw new \Doctrine\Persistence\Mapping\MappingException('Not an entity');
            });

        // 执行测试
        [$tableName, $params] = $this->sqlFormatter->getObjectInsertSql($this->objectManager, $entity);

        // 验证表名
        $this->assertEquals('complex_test_entity', $tableName);

        // 验证参数
        $this->assertEquals(123, $params['id']);
        $this->assertEquals('Test Entity', $params['name']);
        $this->assertEquals('Test Description', $params['description']);
        $this->assertEquals(1, $params['is_active']); // boolean 转换为 int
        $this->assertEquals('{"key":"value","number":42}', $params['metadata']); // JSON 编码
        $this->assertEquals('active', $params['status']); // 枚举值
        $this->assertEquals(456, $params['category_id']); // 关联实体ID
        
        // 验证 DateTimeImmutable 对象被转换为字符串格式
        $this->assertIsString($params['created_at']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $params['created_at']);
    }

    public function testGetObjectInsertSqlWithNullValues(): void
    {
        // 创建实体，大部分字段为 null
        $entity = new ComplexTestEntity();
        $entity->id = 0; // 主键为0，应该被跳过
        $entity->name = 'Test';

        // 模拟 metadata 行为
        $reflectionClass = new ReflectionClass(ComplexTestEntity::class);
        $this->metadata
            ->method('getReflectionClass')
            ->willReturn($reflectionClass);

        $this->objectManager
            ->method('getClassMetadata')
            ->with(ComplexTestEntity::class)
            ->willReturn($this->metadata);

        // 执行测试
        [$tableName, $params] = $this->sqlFormatter->getObjectInsertSql($this->objectManager, $entity);

        // 验证表名
        $this->assertEquals('complex_test_entity', $tableName);

        // 验证参数 - id 应该被跳过，因为值为0
        $this->assertArrayNotHasKey('id', $params);
        $this->assertEquals('Test', $params['name']);
        $this->assertEquals(0, $params['is_active']); // false 转换为 0
        $this->assertNull($params['description']);
        $this->assertNull($params['created_at']);
    }

    public function testGetObjectInsertSqlWithBooleanValues(): void
    {
        // 测试布尔值处理
        $entity = new ComplexTestEntity();
        $entity->name = 'Test';
        $entity->isActive = false;

        // 模拟 metadata 行为
        $reflectionClass = new ReflectionClass(ComplexTestEntity::class);
        $this->metadata
            ->method('getReflectionClass')
            ->willReturn($reflectionClass);

        $this->objectManager
            ->method('getClassMetadata')
            ->with(ComplexTestEntity::class)
            ->willReturn($this->metadata);

        // 执行测试
        [$tableName, $params] = $this->sqlFormatter->getObjectInsertSql($this->objectManager, $entity);

        // 验证布尔值转换
        $this->assertEquals(0, $params['is_active']);
    }

    public function testGetObjectInsertSqlWithArrayValues(): void
    {
        // 测试数组值处理
        $entity = new ComplexTestEntity();
        $entity->name = 'Test';
        $entity->metadata = ['complex' => ['nested' => 'value']];

        // 模拟 metadata 行为
        $reflectionClass = new ReflectionClass(ComplexTestEntity::class);
        $this->metadata
            ->method('getReflectionClass')
            ->willReturn($reflectionClass);

        $this->objectManager
            ->method('getClassMetadata')
            ->with(ComplexTestEntity::class)
            ->willReturn($this->metadata);

        // 执行测试
        [$tableName, $params] = $this->sqlFormatter->getObjectInsertSql($this->objectManager, $entity);

        // 验证 JSON 编码
        $this->assertEquals('{"complex":{"nested":"value"}}', $params['metadata']);
    }

    public function testGetObjectInsertSqlWithMappingException(): void
    {
        // 测试映射异常处理
        $entity = new ComplexTestEntity();
        $entity->name = 'Test';
        
        // 创建一个无法映射的对象作为关联
        $invalidEntity = new \stdClass();
        $invalidEntity->someProperty = 'value';
        
        // 由于 category 属性是强类型的，我们需要通过不同方式测试
        // 这里直接设置一个有效的对象，但让 primaryKeyService 抛出异常
        $category = new CategoryTestEntity();
        $category->id = 999;
        $entity->category = $category;

        // 模拟 metadata 行为
        $reflectionClass = new ReflectionClass(ComplexTestEntity::class);
        $this->metadata
            ->method('getReflectionClass')
            ->willReturn($reflectionClass);

        $this->objectManager
            ->method('getClassMetadata')
            ->with(ComplexTestEntity::class)
            ->willReturn($this->metadata);

        // 模拟 PrimaryKeyService 总是抛出 MappingException
        $this->primaryKeyService
            ->method('getPrimaryKeyValues')
            ->willThrowException(new MappingException('Entity not mapped'));

        // 执行测试 - 应该正常完成，异常被捕获并忽略
        [$tableName, $params] = $this->sqlFormatter->getObjectInsertSql($this->objectManager, $entity);

        // 验证结果
        $this->assertEquals('complex_test_entity', $tableName);
        $this->assertEquals('Test', $params['name']);
        // category_id 应该包含原始的 CategoryTestEntity 对象（异常被捕获）
        $this->assertInstanceOf(CategoryTestEntity::class, $params['category_id']);
    }
} 