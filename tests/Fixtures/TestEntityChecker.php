<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityCheckerBundle\Tests\Fixtures;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\ObjectManager;
use Tourze\DoctrineEntityCheckerBundle\Checker\EntityCheckerInterface;

/**
 * 测试用实体检查器
 */
class TestEntityChecker implements EntityCheckerInterface
{
    private array $checkedEntities = [];
    private array $updatedEntities = [];

    /**
     * 重置检查记录
     */
    public function reset(): void
    {
        $this->checkedEntities = [];
        $this->updatedEntities = [];
    }

    /**
     * 获取已检查的实体列表
     */
    public function getCheckedEntities(): array
    {
        return $this->checkedEntities;
    }

    /**
     * 获取已更新的实体列表
     */
    public function getUpdatedEntities(): array
    {
        return $this->updatedEntities;
    }

    /**
     * {@inheritdoc}
     */
    public function prePersistEntity(ObjectManager $objectManager, object $entity): void
    {
        $this->checkedEntities[] = $entity;

        // 如果实体有 createdAt 属性，设置当前时间
        if (property_exists($entity, 'createdAt') && $entity->createdAt === null) {
            $entity->createdAt = new \DateTimeImmutable();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdateEntity(ObjectManager $objectManager, object $entity, PreUpdateEventArgs $eventArgs): void
    {
        $this->updatedEntities[] = $entity;

        // 如果实体有 updatedAt 属性，设置当前时间
        if (property_exists($entity, 'updatedAt')) {
            $entity->updatedAt = new \DateTimeImmutable();
        }
    }
}
