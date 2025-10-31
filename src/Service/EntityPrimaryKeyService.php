<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityCheckerBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * 实体主键值获取服务
 */
readonly class EntityPrimaryKeyService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * 获取实体的主键值
     *
     * @param object $entity 实体对象
     *
     * @return array<string, mixed> 主键字段名和值的关联数组
     */
    public function getPrimaryKeyValues(object $entity): array
    {
        $metadata = $this->entityManager->getClassMetadata($entity::class);
        $identifierFieldNames = $metadata->getIdentifierFieldNames();

        $primaryKeyValues = [];
        foreach ($identifierFieldNames as $fieldName) {
            $primaryKeyValues[$fieldName] = $metadata->getFieldValue($entity, $fieldName);
        }

        return $primaryKeyValues;
    }

    /**
     * 判断实体是否使用复合主键
     *
     * @param string|object $entityClass 实体类名或实体对象
     */
    public function hasCompositeIdentifier(string|object $entityClass): bool
    {
        $className = is_object($entityClass) ? $entityClass::class : $entityClass;
        $metadata = $this->entityManager->getClassMetadata($className);

        return count($metadata->getIdentifierFieldNames()) > 1;
    }

    /**
     * 获取实体的主键字段名称
     *
     * @param string|object $entityClass 实体类名或实体对象
     *
     * @return array<int, string> 主键字段名数组
     */
    public function getIdentifierFieldNames(string|object $entityClass): array
    {
        $className = is_object($entityClass) ? $entityClass::class : $entityClass;

        return $this->entityManager->getClassMetadata($className)->getIdentifierFieldNames();
    }
}
