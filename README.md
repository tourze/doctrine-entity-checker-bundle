# Doctrine Entity Checker Bundle

这是一个 Symfony Bundle，提供 Doctrine 实体检查和处理功能。通过自定义检查器，可以在实体保存前自动处理特定逻辑。

## 功能

- 实体持久化前的自动处理
- 自定义 ID 生成器支持
- 实体主键值管理
- SQL 格式化和生成支持

## 安装

使用 Composer 安装：

```bash
composer require tourze/doctrine-entity-checker-bundle
```

## 使用方法

1. 注册 Bundle

```php
// config/bundles.php
return [
    // ...
    Tourze\DoctrineEntityCheckerBundle\DoctrineEntityCheckerBundle::class => ['all' => true],
];
```

2. 创建自定义实体检查器

```php
<?php

namespace App\Doctrine\Checker;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\ObjectManager;
use Tourze\DoctrineEntityCheckerBundle\Checker\EntityCheckerInterface;

class TimestampEntityChecker implements EntityCheckerInterface
{
    public function prePersistEntity(ObjectManager $objectManager, object $entity): void
    {
        if (property_exists($entity, 'createdAt') && $entity->createdAt === null) {
            $entity->createdAt = new \DateTimeImmutable();
        }
    }

    public function preUpdateEntity(ObjectManager $objectManager, object $entity, PreUpdateEventArgs $eventArgs): void
    {
        if (property_exists($entity, 'updatedAt')) {
            $entity->updatedAt = new \DateTimeImmutable();
        }
    }
}
```

## 测试

运行测试：

```bash
# 在项目根目录执行
composer test
```

生成测试覆盖率报告：

```bash
composer test-coverage
```
