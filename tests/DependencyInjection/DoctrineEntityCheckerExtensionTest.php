<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityCheckerBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\DoctrineEntityCheckerBundle\DependencyInjection\DoctrineEntityCheckerExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(DoctrineEntityCheckerExtension::class)]
final class DoctrineEntityCheckerExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private DoctrineEntityCheckerExtension $extension;

    private ContainerBuilder $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new DoctrineEntityCheckerExtension();
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.environment', 'test');
    }

    public function testServicesAreAvailable(): void
    {
        // 加载扩展配置
        $this->extension->load([], $this->container);

        // 验证服务是否可用
        self::assertTrue($this->container->hasDefinition('Tourze\DoctrineEntityCheckerBundle\Service\EntityChecker'));
        self::assertTrue($this->container->hasDefinition('Tourze\DoctrineEntityCheckerBundle\Service\EntityPrimaryKeyService'));
        self::assertTrue($this->container->hasDefinition('Tourze\DoctrineEntityCheckerBundle\Service\SqlFormatter'));
        self::assertTrue($this->container->hasDefinition('Yiisoft\Strings\Inflector'));
    }

    public function testEntityCheckerServiceIsCorrectlyConfigured(): void
    {
        // 加载扩展配置
        $this->extension->load([], $this->container);

        // 验证 EntityChecker 服务配置
        $definition = $this->container->getDefinition('Tourze\DoctrineEntityCheckerBundle\Service\EntityChecker');
        self::assertEquals('Tourze\DoctrineEntityCheckerBundle\Service\EntityChecker', $definition->getClass());
    }
}
