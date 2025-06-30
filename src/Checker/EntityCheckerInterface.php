<?php

namespace Tourze\DoctrineEntityCheckerBundle\Checker;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * 为了简化我们的维护工作，我们会定义一些Trait或者新的注释来实现额外的自动化功能，如自动分配雪花ID，自动设置创建时间
 * 这种服务写得好了，就不好维护，特地定义一个Interface
 */
#[AutoconfigureTag(name: self::SERVICE_TAG)]
interface EntityCheckerInterface
{
    const SERVICE_TAG = 'doctrine.entity_checker.checker';

    /**
     * 更新实体前执行处理
     */
    public function prePersistEntity(ObjectManager $objectManager, object $entity): void;

    /**
     * 更新实体前执行处理
     */
    public function preUpdateEntity(ObjectManager $objectManager, object $entity, PreUpdateEventArgs $eventArgs): void;
}
