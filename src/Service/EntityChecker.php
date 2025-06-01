<?php

namespace Tourze\DoctrineEntityCheckerBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Persistence\ObjectManager;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Tourze\DoctrineEntityCheckerBundle\Checker\EntityCheckerInterface;

#[Autoconfigure(lazy: true)]
class EntityChecker
{
    public function __construct(
        #[TaggedIterator(EntityCheckerInterface::SERVICE_TAG)] private readonly iterable $checkers,
        #[Autowire(service: EntityManagerInterface::class, lazy: true)] private readonly EntityManagerInterface $entityManager,
        #[Autowire(service: 'service_container')] private readonly ContainerInterface $container,
    ) {
    }

    /**
     * 更新实体前执行处理
     */
    public function prePersistEntity(ObjectManager $objectManager, object $entity): void
    {
        foreach ($this->checkers as $checker) {
            /* @var EntityCheckerInterface $checker */
            $checker->prePersistEntity($objectManager, $entity);
        }
        assert($objectManager instanceof EntityManagerInterface);

        // 主键没办法通过上面的方式处理
        $reflection = $this->entityManager->getClassMetadata($entity::class)->getReflectionClass();
        
        try {
            $property = $reflection->getProperty('id');
            $customIdGenerator = $property->getAttributes(ORM\CustomIdGenerator::class);
            if (!empty($customIdGenerator)) {
                $customIdGenerator = $customIdGenerator[0]->newInstance();
                /** @var ORM\CustomIdGenerator $customIdGenerator */
                $generator = $this->getIdGenerator($customIdGenerator->class);

                // 生成ID并分配给实体
                $generatedId = $generator->generateId($objectManager, $entity);
                $this->entityManager->getUnitOfWork()->assignPostInsertId($entity, $generatedId);
            }
        } catch (\ReflectionException $e) {
            // 如果实体没有 id 属性，跳过ID生成处理
            // 这是正常情况，某些实体可能不需要 id 属性
        }
    }

    /**
     * 从容器获取ID生成器
     */
    protected function getIdGenerator(string $generatorClass): AbstractIdGenerator
    {
        $generator = $this->container->get($generatorClass);
        assert($generator instanceof AbstractIdGenerator);

        return $generator;
    }
}
