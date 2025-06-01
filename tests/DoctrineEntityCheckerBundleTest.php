<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityCheckerBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\DoctrineEntityCheckerBundle\DependencyInjection\DoctrineEntityCheckerExtension;
use Tourze\DoctrineEntityCheckerBundle\DoctrineEntityCheckerBundle;

/**
 * @coversDefaultClass \Tourze\DoctrineEntityCheckerBundle\DoctrineEntityCheckerBundle
 */
class DoctrineEntityCheckerBundleTest extends TestCase
{
    private DoctrineEntityCheckerBundle $bundle;

    protected function setUp(): void
    {
        $this->bundle = new DoctrineEntityCheckerBundle();
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $this->assertInstanceOf(DoctrineEntityCheckerBundle::class, $this->bundle);
        $this->assertInstanceOf(Bundle::class, $this->bundle);
    }

    public function testGetContainerExtension(): void
    {
        $extension = $this->bundle->getContainerExtension();
        
        $this->assertInstanceOf(DoctrineEntityCheckerExtension::class, $extension);
        $this->assertEquals('doctrine_entity_checker', $extension->getAlias());
    }

    public function testBundleName(): void
    {
        $this->assertEquals('DoctrineEntityCheckerBundle', $this->bundle->getName());
    }
} 