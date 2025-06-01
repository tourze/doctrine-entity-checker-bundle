<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityCheckerBundle\Tests\Fixtures;

use Doctrine\ORM\Mapping as ORM;

/**
 * 用于测试的复杂实体，包含各种字段类型和关系
 */
#[ORM\Entity]
#[ORM\Table(name: 'complex_test_entity')]
class ComplexTestEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    public ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $description = null;

    #[ORM\Column(type: 'boolean')]
    public bool $isActive = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    public ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    public ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'json', nullable: true)]
    public ?array $metadata = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    public ?int $categoryId = null;

    #[ORM\ManyToOne(targetEntity: CategoryTestEntity::class)]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id')]
    public ?CategoryTestEntity $category = null;

    public ?TestEnum $status = null;

    /**
     * 获取实体类名
     */
    public static function class(): string
    {
        return self::class;
    }
} 