<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityCheckerBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Tourze\DoctrineEntityCheckerBundle\DependencyInjection\DoctrineEntityCheckerExtension;

/**
 * @coversDefaultClass \Tourze\DoctrineEntityCheckerBundle\DependencyInjection\DoctrineEntityCheckerExtension
 */
class DoctrineEntityCheckerExtensionTest extends TestCase
{
    private DoctrineEntityCheckerExtension $extension;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new DoctrineEntityCheckerExtension();
        $this->container = new ContainerBuilder();
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $this->assertInstanceOf(DoctrineEntityCheckerExtension::class, $this->extension);
        $this->assertInstanceOf(Extension::class, $this->extension);
    }

    /**
     * @covers ::load
     */
    public function testLoad(): void
    {
        // 执行加载
        $this->extension->load([], $this->container);

        // 验证服务是否已注册
        $this->assertTrue($this->container->hasDefinition('Tourze\DoctrineEntityCheckerBundle\Service\EntityChecker'));
        $this->assertTrue($this->container->hasDefinition('Tourze\DoctrineEntityCheckerBundle\Service\EntityPrimaryKeyService'));
        $this->assertTrue($this->container->hasDefinition('Tourze\DoctrineEntityCheckerBundle\Service\SqlFormatter'));
        $this->assertTrue($this->container->hasDefinition('Yiisoft\Strings\Inflector'));
    }

    /**
     * @covers ::load
     */
    public function testLoadWithEmptyConfigs(): void
    {
        // 测试空配置数组
        $configs = [];
        
        $this->extension->load($configs, $this->container);
        
        // 验证基本服务仍然被注册
        $this->assertTrue($this->container->hasDefinition('Tourze\DoctrineEntityCheckerBundle\Service\EntityChecker'));
    }

    /**
     * @covers ::load
     */
    public function testLoadMultipleConfigs(): void
    {
        // 测试多个配置数组
        $configs = [
            [],
            ['some_config' => 'value'],
        ];
        
        $this->extension->load($configs, $this->container);
        
        // 验证服务配置
        $this->assertTrue($this->container->hasDefinition('Tourze\DoctrineEntityCheckerBundle\Service\EntityChecker'));
    }

    public function testGetAlias(): void
    {
        $this->assertEquals('doctrine_entity_checker', $this->extension->getAlias());
    }

    public function testServicesAutoConfiguration(): void
    {
        $this->extension->load([], $this->container);

        // 检查 EntityChecker 服务配置
        $definition = $this->container->getDefinition('Tourze\DoctrineEntityCheckerBundle\Service\EntityChecker');
        $this->assertTrue($definition->isAutowired());
        $this->assertTrue($definition->isAutoconfigured());

        // 检查 EntityPrimaryKeyService 服务配置
        $definition = $this->container->getDefinition('Tourze\DoctrineEntityCheckerBundle\Service\EntityPrimaryKeyService');
        $this->assertTrue($definition->isAutowired());
        $this->assertTrue($definition->isAutoconfigured());
    }
} 