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
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Tourze\DoctrineEntityCheckerBundle\Checker\EntityCheckerInterface;

#[Autoconfigure(lazy: true)]
class EntityChecker
{
    public function __construct(
        #[TaggedIterator(EntityCheckerInterface::SERVICE_TAG)] private readonly iterable $checkers,
        #[Autowire(service: EntityManagerInterface::class, lazy: true)] private readonly EntityManagerInterface $entityManager,
        private readonly ContainerInterface $container,
        private readonly PropertyAccessor $propertyAccessor,
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

        // 主键没办法通过上面的方式处理
        $reflection = $this->entityManager->getClassMetadata($entity)->getReflectionClass();
        $property = $reflection->getProperty('id');
        $customIdGenerator = $property->getAttributes(ORM\CustomIdGenerator::class);
        if (!empty($customIdGenerator)) {
            $customIdGenerator = $customIdGenerator[0]->newInstance();
            /** @var ORM\CustomIdGenerator $customIdGenerator */
            $generator = $this->container->get($customIdGenerator->class);
            /* @var AbstractIdGenerator $generator */
            $this->propertyAccessor->setValue($entity, 'id', $generator->generateId($objectManager, $entity));
        }
    }
}
