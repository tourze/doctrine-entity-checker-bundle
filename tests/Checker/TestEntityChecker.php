<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityCheckerBundle\Tests\Checker;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\ObjectManager;
use Tourze\DoctrineEntityCheckerBundle\Checker\EntityCheckerInterface;

class TestEntityChecker implements EntityCheckerInterface
{
    /** @var array<object> */
    private array $checkedEntities = [];

    /** @var array<object> */
    private array $updatedEntities = [];

    public function prePersistEntity(ObjectManager $objectManager, object $entity): void
    {
        $this->checkedEntities[] = $entity;

        // 设置 createdAt 如果实体有这个属性
        if (property_exists($entity, 'createdAt')) {
            $entity->createdAt = new \DateTimeImmutable();
        }
    }

    public function preUpdateEntity(ObjectManager $objectManager, object $entity, PreUpdateEventArgs $eventArgs): void
    {
        $this->updatedEntities[] = $entity;

        // 设置 updatedAt 如果实体有这个属性
        if (property_exists($entity, 'updatedAt')) {
            $entity->updatedAt = new \DateTimeImmutable();
        }
    }

    /** @return array<object> */
    public function getCheckedEntities(): array
    {
        return $this->checkedEntities;
    }

    /** @return array<object> */
    public function getUpdatedEntities(): array
    {
        return $this->updatedEntities;
    }

    public function reset(): void
    {
        $this->checkedEntities = [];
        $this->updatedEntities = [];
    }
}
