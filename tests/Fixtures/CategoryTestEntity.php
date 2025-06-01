<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityCheckerBundle\Tests\Fixtures;

use Doctrine\ORM\Mapping as ORM;

/**
 * 用于测试关系的分类实体
 */
#[ORM\Entity]
#[ORM\Table(name: 'category_test_entity')]
class CategoryTestEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    public ?string $name = null;

    /**
     * 模拟 getId 方法
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * 获取实体类名
     */
    public static function class(): string
    {
        return self::class;
    }
} 