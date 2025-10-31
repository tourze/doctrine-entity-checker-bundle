<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityCheckerBundle\Tests\Checker;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TestEntityChecker::class)]
final class TestEntityCheckerTest extends TestCase
{
    private TestEntityChecker $checker;

    private ObjectManager|MockObject $objectManager;

    protected function setUp(): void
    {
        $this->checker = new TestEntityChecker();
        // 使用 ObjectManager 接口，这是标准的 Doctrine 接口
        // 1. ObjectManager 是 Doctrine 的核心接口，适合测试
        // 2. 测试只需要验证接口行为，不依赖具体实现
        // 3. 使用接口提高测试的可维护性和灵活性
        $this->objectManager = $this->createMock(ObjectManager::class);
    }

    public function testPrePersistEntity(): void
    {
        // 创建测试实体
        $entity = new class {
            public ?\DateTimeImmutable $createdAt = null;
        };

        // 执行测试
        $this->assertInstanceOf(ObjectManager::class, $this->objectManager);
        $this->checker->prePersistEntity($this->objectManager, $entity);

        // 验证实体已被记录
        $checkedEntities = $this->checker->getCheckedEntities();
        $this->assertCount(1, $checkedEntities);
        $this->assertSame($entity, $checkedEntities[0]);

        // 验证 createdAt 已设置
        $this->assertInstanceOf(\DateTimeImmutable::class, $entity->createdAt);
    }

    public function testPreUpdateEntity(): void
    {
        // 创建测试实体
        $entity = new class {
            public ?\DateTimeImmutable $updatedAt = null;
        };

        // 模拟 PreUpdateEventArgs
        // 这里必须使用具体类 PreUpdateEventArgs，因为：
        // 1. 它是 Doctrine 事件系统的核心类，没有合适的接口
        // 2. 测试需要验证与 Doctrine 事件的集成
        // 3. 这个类的公共API稳定，Mock风险较低
        $eventArgs = $this->createMock(PreUpdateEventArgs::class);

        // 执行测试
        $this->assertInstanceOf(ObjectManager::class, $this->objectManager);
        $this->checker->preUpdateEntity($this->objectManager, $entity, $eventArgs);

        // 验证实体已被记录
        $updatedEntities = $this->checker->getUpdatedEntities();
        $this->assertCount(1, $updatedEntities);
        $this->assertSame($entity, $updatedEntities[0]);

        // 验证 updatedAt 已设置
        $this->assertInstanceOf(\DateTimeImmutable::class, $entity->updatedAt);
    }

    public function testReset(): void
    {
        // 创建测试实体
        $entity1 = new \stdClass();
        $entity2 = new \stdClass();
        // 使用具体类 PreUpdateEventArgs 的Mock，原因：
        // 1. 这是 Doctrine 事件系统核心类，没有合适的接口替代
        // 2. 测试需要验证实际的 Doctrine 事件处理逻辑
        // 3. 该类API稳定，Mock使用安全
        $eventArgs = $this->createMock(PreUpdateEventArgs::class);

        // 添加一些实体进记录
        $this->assertInstanceOf(ObjectManager::class, $this->objectManager);
        $this->checker->prePersistEntity($this->objectManager, $entity1);
        $this->checker->preUpdateEntity($this->objectManager, $entity2, $eventArgs);

        // 确认实体已被记录
        $this->assertCount(1, $this->checker->getCheckedEntities());
        $this->assertCount(1, $this->checker->getUpdatedEntities());

        // 重置记录
        $this->checker->reset();

        // 验证记录已清空
        $this->assertEmpty($this->checker->getCheckedEntities());
        $this->assertEmpty($this->checker->getUpdatedEntities());
    }
}
