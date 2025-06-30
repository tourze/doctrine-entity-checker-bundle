<?php

namespace Tourze\DoctrineEntityCheckerBundle\Tests\Fixtures;

use Doctrine\ORM\Id\AbstractIdGenerator;
use Doctrine\Persistence\ObjectManager;
use Tourze\DoctrineEntityCheckerBundle\Service\EntityChecker;

/**
 * 测试用 EntityChecker 子类
 * 覆盖方法以避免 UnitOfWork 相关问题
 */
class TestEntityCheckerSubclass extends EntityChecker
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