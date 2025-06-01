<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityCheckerBundle\Tests\Fixtures;

use Doctrine\ORM\Mapping as ORM;

/**
 * 用于测试自定义ID生成器的实体
 */
#[ORM\Entity]
#[ORM\Table(name: 'custom_id_test_entity')]
class CustomIdTestEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'string')]
    #[ORM\CustomIdGenerator(class: 'NonExistentIdGenerator')]
    public ?string $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    public ?string $name = null;

    /**
     * 获取实体类名
     */
    public static function class(): string
    {
        return self::class;
    }
} 