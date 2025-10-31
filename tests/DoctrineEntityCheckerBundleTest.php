<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityCheckerBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DoctrineEntityCheckerBundle\DoctrineEntityCheckerBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(DoctrineEntityCheckerBundle::class)]
#[RunTestsInSeparateProcesses]
final class DoctrineEntityCheckerBundleTest extends AbstractBundleTestCase
{
}
