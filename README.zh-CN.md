# Doctrine Entity Checker Bundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version][latest-version-badge]][packagist]
[![Total Downloads][total-downloads-badge]][packagist]
[![PHP Version][php-version-badge]][packagist]
[![License][license-badge]](LICENSE)

一个 Symfony Bundle，提供 Doctrine 实体检查和处理功能。
通过自定义检查器，您可以在实体持久化之前自动处理特定的逻辑。

## 目录

- [特性](#特性)
- [安装](#安装)
- [系统要求](#系统要求)
- [快速开始](#快速开始)
- [配置](#配置)
- [高级用法](#高级用法)
- [API 参考](#api-参考)
- [测试](#测试)
- [贡献](#贡献)
- [许可证](#许可证)

## 特性

- 实体持久化前的自动处理
- 支持 `#[ORM\CustomIdGenerator]` 属性的自定义 ID 生成器
- 实体主键管理工具
- SQL 格式化和生成工具
- 懒加载服务，性能优化
- 支持复合主键

## 安装

通过 Composer 安装：

```bash
composer require tourze/doctrine-entity-checker-bundle
```

## 系统要求

- PHP 8.1 或更高版本
- Symfony 6.4 或更高版本
- Doctrine ORM 3.0 或更高版本

## 快速开始

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
        if (property_exists($entity, 'createTime') && $entity->createTime === null) {
            $entity->createTime = new \DateTimeImmutable();
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

检查器将通过 `#[AutoconfigureTag]` 属性自动注册。

## 配置

该包开箱即用，配置最少。所有服务都自动配置了懒加载以优化性能。

### 服务配置

此包中的所有服务默认都配置为懒加载：

- `EntityChecker` - 主要实体处理服务
- `EntityPrimaryKeyService` - 主键工具
- `SqlFormatter` - SQL 生成工具

这些服务在需要时自动注入，支持依赖注入。

### 自定义配置

如果您需要自定义服务行为，可以在 `config/services.yaml` 中覆盖服务：

```yaml
services:
    # 如果需要，覆盖主要实体检查器
    Tourze\DoctrineEntityCheckerBundle\Service\EntityChecker:
        # 您的自定义配置
```

## 高级用法

### 自定义 ID 生成器

使用 `#[ORM\CustomIdGenerator]` 属性指定自定义 ID 生成器：

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Generator\SnowflakeIdGenerator;

#[ORM\Entity]
class MyEntity
{
    #[ORM\Id]
    #[ORM\CustomIdGenerator(class: SnowflakeIdGenerator::class)]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    // ... 其他属性
}
```

### 主键服务

从实体获取主键信息：

```php
<?php

use Tourze\DoctrineEntityCheckerBundle\Service\EntityPrimaryKeyService;

class MyService
{
    public function __construct(
        private EntityPrimaryKeyService $primaryKeyService
    ) {}

    public function analyzeEntity(object $entity): void
    {
        // 获取主键值
        $pkValues = $this->primaryKeyService->getPrimaryKeyValues($entity);
        
        // 检查实体是否有复合主键
        $hasComposite = $this->primaryKeyService->hasCompositeIdentifier($entity);
        
        // 获取主键字段名
        $fieldNames = $this->primaryKeyService->getIdentifierFieldNames($entity);
    }
}
```

### SQL 格式化

从实体生成 SQL：

```php
<?php

use Tourze\DoctrineEntityCheckerBundle\Service\SqlFormatter;

class MyService
{
    public function __construct(
        private SqlFormatter $sqlFormatter
    ) {}

    public function generateInsertSql(object $entity): array
    {
        // 返回 [tableName, parameters]
        [$tableName, $params] = $this->sqlFormatter->getObjectInsertSql(
            $this->entityManager, 
            $entity
        );
        
        // 构建 INSERT SQL
        $fields = implode(', ', array_keys($params));
        $placeholders = ':' . implode(', :', array_keys($params));
        $sql = "INSERT INTO {$tableName} ({$fields}) VALUES ({$placeholders})";
        
        return [$sql, $params];
    }
}
```

## API 参考

### EntityCheckerInterface

- `prePersistEntity(ObjectManager $objectManager, object $entity): void` - 在实体持久化之前调用
- `preUpdateEntity(ObjectManager $objectManager, object $entity, PreUpdateEventArgs $eventArgs): void` - 在实体更新之前调用

### EntityPrimaryKeyService

- `getPrimaryKeyValues(object $entity): array` - 获取主键值
- `hasCompositeIdentifier(string|object $entityClass): bool` - 检查是否为复合主键
- `getIdentifierFieldNames(string|object $entityClass): array` - 获取主键字段名

### SqlFormatter

- `getObjectInsertSql(ObjectManager $objectManager, object $object): array` - 生成插入 SQL 数据
- `fromOrmQuery(OrmQuery $query): string` - 格式化带参数的 DQL

## 测试

运行测试：

```bash
# 从项目根目录执行
./vendor/bin/phpunit packages/doctrine-entity-checker-bundle/tests
```

运行 PHPStan 分析：

```bash
php -d memory_limit=2G ./vendor/bin/phpstan analyse packages/doctrine-entity-checker-bundle
```

## 贡献

请查看 [CONTRIBUTING.md](CONTRIBUTING.md) 了解详细信息。

## 许可证

MIT 许可证 (MIT)。请查看 [License File](LICENSE) 了解更多信息。

[packagist]: https://packagist.org/packages/tourze/doctrine-entity-checker-bundle
[latest-version-badge]: https://img.shields.io/packagist/v/tourze/doctrine-entity-checker-bundle.svg?style=flat-square
[total-downloads-badge]: https://img.shields.io/packagist/dt/tourze/doctrine-entity-checker-bundle.svg?style=flat-square
[php-version-badge]: https://img.shields.io/packagist/php-v/tourze/doctrine-entity-checker-bundle.svg?style=flat-square
[license-badge]: https://img.shields.io/packagist/l/tourze/doctrine-entity-checker-bundle.svg?style=flat-square
