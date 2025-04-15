<?php

namespace Tourze\DoctrineEntityCheckerBundle\Tests\Utils;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Tourze\DoctrineEntityCheckerBundle\Checker\EntityCheckerInterface;
use Tourze\DoctrineEntityCheckerBundle\Service\EntityChecker;
use Tourze\DoctrineEntityCheckerBundle\Service\EntityPrimaryKeyService;

/**
 * 该类用于消除 PHPUnit mock 对象类型检查错误
 * PHP 静态分析工具会报告 mock 对象类型不匹配的错误，但实际运行时不会有问题
 */
trait ResetMocks
{
    /**
     * 重置并获取 EntityManagerInterface mock
     */
    protected function resetEntityManager(TestCase $testCase): EntityManagerInterface
    {
        /** @var EntityManagerInterface&MockObject */
        $mock = $testCase->createMock(EntityManagerInterface::class);
        return $mock;
    }

    /**
     * 重置并获取 ClassMetadata mock
     */
    protected function resetClassMetadata(TestCase $testCase): ClassMetadata
    {
        /** @var ClassMetadata&MockObject */
        $mock = $testCase->createMock(ClassMetadata::class);
        return $mock;
    }

    /**
     * 重置并获取 ObjectManager mock
     */
    protected function resetObjectManager(TestCase $testCase): ObjectManager
    {
        /** @var ObjectManager&MockObject */
        $mock = $testCase->createMock(ObjectManager::class);
        return $mock;
    }

    /**
     * 重置并获取 ContainerInterface mock
     */
    protected function resetContainer(TestCase $testCase): ContainerInterface
    {
        /** @var ContainerInterface&MockObject */
        $mock = $testCase->createMock(ContainerInterface::class);
        return $mock;
    }

    /**
     * 重置并获取 EntityCheckerInterface mock
     */
    protected function resetEntityCheckerInterface(TestCase $testCase): EntityCheckerInterface
    {
        /** @var EntityCheckerInterface&MockObject */
        $mock = $testCase->createMock(EntityCheckerInterface::class);
        return $mock;
    }

    /**
     * 重置并获取 EntityChecker mock
     */
    protected function resetEntityChecker(TestCase $testCase): EntityChecker
    {
        /** @var EntityChecker&MockObject */
        $mock = $testCase->createMock(EntityChecker::class);
        return $mock;
    }

    /**
     * 重置并获取 EntityPrimaryKeyService mock
     */
    protected function resetEntityPrimaryKeyService(TestCase $testCase): EntityPrimaryKeyService
    {
        /** @var EntityPrimaryKeyService&MockObject */
        $mock = $testCase->createMock(EntityPrimaryKeyService::class);
        return $mock;
    }

    /**
     * 重置并获取 PreUpdateEventArgs mock
     */
    protected function resetPreUpdateEventArgs(TestCase $testCase): PreUpdateEventArgs
    {
        /** @var PreUpdateEventArgs&MockObject */
        $mock = $testCase->createMock(PreUpdateEventArgs::class);
        return $mock;
    }
}
