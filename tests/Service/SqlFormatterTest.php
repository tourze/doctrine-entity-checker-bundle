<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityCheckerBundle\Tests\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query as OrmQuery;
use Doctrine\ORM\Query\Parameter as OrmParameter;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrineEntityCheckerBundle\Service\EntityChecker;
use Tourze\DoctrineEntityCheckerBundle\Service\EntityPrimaryKeyService;
use Tourze\DoctrineEntityCheckerBundle\Service\SqlFormatter;
use Yiisoft\Strings\Inflector;

class SqlFormatterTest extends TestCase
{
    private SqlFormatter $sqlFormatter;
    private EntityChecker|MockObject $entityChecker;
    private Inflector $inflector;
    private EntityPrimaryKeyService|MockObject $primaryKeyService;
    private ObjectManager|MockObject $objectManager;

    protected function setUp(): void
    {
        $this->entityChecker = $this->createMock(EntityChecker::class);
        $this->inflector = new Inflector();
        $this->primaryKeyService = $this->createMock(EntityPrimaryKeyService::class);
        $this->objectManager = $this->createMock(ObjectManager::class);

        $this->sqlFormatter = new SqlFormatter(
            $this->entityChecker,
            $this->inflector,
            $this->primaryKeyService
        );
    }

    public function testFromOrmQuery(): void
    {
        // 创建模拟对象
        $query = $this->createMock(OrmQuery::class);
        $parameter1 = $this->getMockBuilder(OrmParameter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $parameter2 = $this->getMockBuilder(OrmParameter::class)
            ->disableOriginalConstructor()
            ->getMock();

        // 设置参数
        $parameter1->method('getName')->willReturn('param1');
        $parameter1->method('getValue')->willReturn(123);

        $parameter2->method('getName')->willReturn('param2');
        $parameter2->method('getValue')->willReturn('test');

        // 使用 ArrayCollection 而不是数组
        $parameters = new ArrayCollection([$parameter1, $parameter2]);

        // 设置 Query 行为
        $query->method('getDQL')->willReturn('SELECT e FROM Entity e WHERE e.id = :param1 AND e.name = :param2');
        $query->method('getParameters')->willReturn($parameters);

        // 执行测试
        $result = $this->sqlFormatter->fromOrmQuery($query);

        // 验证结果
        $expectedDql = "SELECT e FROM Entity e WHERE e.id = 123 AND e.name = 'test'";
        $this->assertEquals($expectedDql, $result);
    }

    public function testFormatOrmValueWithNull(): void
    {
        // 创建 formatOrmValue 方法的反射
        $reflectionMethod = new \ReflectionMethod(SqlFormatter::class, 'formatOrmValue');
        $reflectionMethod->setAccessible(true);

        // 创建参数对象
        $parameter = $this->getMockBuilder(OrmParameter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $parameter->method('getValue')->willReturn(null);

        // 执行测试
        $result = $reflectionMethod->invoke($this->sqlFormatter, $parameter);

        // 验证结果
        $this->assertEquals('null', $result);
    }

    public function testFormatOrmValueWithBooleanTrue(): void
    {
        // 创建 formatOrmValue 方法的反射
        $reflectionMethod = new \ReflectionMethod(SqlFormatter::class, 'formatOrmValue');
        $reflectionMethod->setAccessible(true);

        // 创建参数对象
        $parameter = $this->getMockBuilder(OrmParameter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $parameter->method('getValue')->willReturn(true);

        // 执行测试
        $result = $reflectionMethod->invoke($this->sqlFormatter, $parameter);

        // 验证结果
        $this->assertEquals(1, $result);
    }

    public function testFormatOrmValueWithDateTime(): void
    {
        // 创建 formatOrmValue 方法的反射
        $reflectionMethod = new \ReflectionMethod(SqlFormatter::class, 'formatOrmValue');
        $reflectionMethod->setAccessible(true);

        // 创建日期时间对象
        $dateTime = new \DateTime('2023-01-01 12:00:00');

        // 创建参数对象
        $parameter = $this->getMockBuilder(OrmParameter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $parameter->method('getValue')->willReturn($dateTime);

        // 执行测试
        $result = $reflectionMethod->invoke($this->sqlFormatter, $parameter);

        // 验证结果
        $this->assertEquals("'2023-01-01 12:00:00'", $result);
    }

    public function testFormatOrmValueWithArray(): void
    {
        // 创建 formatOrmValue 方法的反射
        $reflectionMethod = new \ReflectionMethod(SqlFormatter::class, 'formatOrmValue');
        $reflectionMethod->setAccessible(true);

        // 创建参数对象
        $parameter = $this->getMockBuilder(OrmParameter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $parameter->method('getValue')->willReturn(['item1', 'item2']);

        // 执行测试
        $result = $reflectionMethod->invoke($this->sqlFormatter, $parameter);

        // 验证结果
        $this->assertEquals("'item1', 'item2'", $result);
    }
}
