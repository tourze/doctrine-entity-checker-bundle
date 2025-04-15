<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityCheckerBundle\Tests\Checker;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrineEntityCheckerBundle\Tests\Fixtures\TestEntityChecker;

class TestEntityCheckerTest extends TestCase
{
    private TestEntityChecker $checker;
    private ObjectManager|MockObject $objectManager;

    protected function setUp(): void
    {
        $this->checker = new TestEntityChecker();
        $this->objectManager = $this->createMock(ObjectManager::class);
    }

    public function testPrePersistEntity(): void
    {
        // 创建测试实体
        $entity = new class {
            public ?\DateTimeImmutable $createdAt = null;
        };

        // 执行测试
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
        $eventArgs = $this->createMock(PreUpdateEventArgs::class);

        // 执行测试
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
        $eventArgs = $this->createMock(PreUpdateEventArgs::class);

        // 添加一些实体进记录
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
