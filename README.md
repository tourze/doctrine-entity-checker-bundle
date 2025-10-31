# Doctrine Entity Checker Bundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version][latest-version-badge]][packagist]
[![Total Downloads][total-downloads-badge]][packagist]
[![PHP Version][php-version-badge]][packagist]
[![License][license-badge]](LICENSE)

A Symfony Bundle that provides Doctrine entity checking and processing capabilities. 
Through custom checkers, you can automatically handle specific logic before entity persistence.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Requirements](#requirements)
- [Quick Start](#quick-start)
- [Configuration](#configuration)
- [Advanced Usage](#advanced-usage)
- [API Reference](#api-reference)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

## Features

- Automatic entity processing before persistence
- Support for custom ID generators with `#[ORM\CustomIdGenerator]` attribute
- Entity primary key management utilities
- SQL formatting and generation tools
- Lazy-loaded services for optimal performance
- Support for composite primary keys

## Installation

Install via Composer:

```bash
composer require tourze/doctrine-entity-checker-bundle
```

## Requirements

- PHP 8.1 or higher
- Symfony 6.4 or higher
- Doctrine ORM 3.0 or higher

## Quick Start

1. Register the Bundle

```php
// config/bundles.php
return [
    // ...
    Tourze\DoctrineEntityCheckerBundle\DoctrineEntityCheckerBundle::class => ['all' => true],
];
```

2. Create a custom entity checker

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

The checker will be automatically registered due to the `#[AutoconfigureTag]` attribute.

## Configuration

The bundle works out of the box with minimal configuration. All services are 
automatically configured with lazy loading for optimal performance.

### Service Configuration

All services in this bundle are configured as lazy-loaded by default:

- `EntityChecker` - Main entity processing service
- `EntityPrimaryKeyService` - Primary key utilities
- `SqlFormatter` - SQL generation utilities

These services are automatically injected when needed and support dependency injection.

### Custom Configuration

If you need to customize service behavior, you can override services in your
`config/services.yaml`:

```yaml
services:
    # Override the main entity checker if needed
    Tourze\DoctrineEntityCheckerBundle\Service\EntityChecker:
        # Your custom configuration
```

## Advanced Usage

### Custom ID Generator

Use the `#[ORM\CustomIdGenerator]` attribute to specify custom ID generators:

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

    // ... other properties
}
```

### Primary Key Service

Get primary key information from entities:

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
        // Get primary key values
        $pkValues = $this->primaryKeyService->getPrimaryKeyValues($entity);
        
        // Check if entity has composite primary key
        $hasComposite = $this->primaryKeyService->hasCompositeIdentifier($entity);
        
        // Get primary key field names
        $fieldNames = $this->primaryKeyService->getIdentifierFieldNames($entity);
    }
}
```

### SQL Formatting

Generate SQL from entities:

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
        // Returns [tableName, parameters]
        [$tableName, $params] = $this->sqlFormatter->getObjectInsertSql(
            $this->entityManager, 
            $entity
        );
        
        // Build INSERT SQL
        $fields = implode(', ', array_keys($params));
        $placeholders = ':' . implode(', :', array_keys($params));
        $sql = "INSERT INTO {$tableName} ({$fields}) VALUES ({$placeholders})";
        
        return [$sql, $params];
    }
}
```

## API Reference

### EntityCheckerInterface

- `prePersistEntity(ObjectManager $objectManager, object $entity): void` - Called before entity persistence
- `preUpdateEntity(ObjectManager $objectManager, object $entity, PreUpdateEventArgs $eventArgs): void` 
  - Called before entity update

### EntityPrimaryKeyService

- `getPrimaryKeyValues(object $entity): array` - Get primary key values
- `hasCompositeIdentifier(string|object $entityClass): bool` - Check for composite primary key
- `getIdentifierFieldNames(string|object $entityClass): array` - Get primary key field names

### SqlFormatter

- `getObjectInsertSql(ObjectManager $objectManager, object $object): array` - Generate insert SQL data
- `fromOrmQuery(OrmQuery $query): string` - Format DQL with parameters

## Testing

Run tests:

```bash
# From project root
./vendor/bin/phpunit packages/doctrine-entity-checker-bundle/tests
```

Run PHPStan analysis:

```bash
php -d memory_limit=2G ./vendor/bin/phpstan analyse packages/doctrine-entity-checker-bundle
```

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

[packagist]: https://packagist.org/packages/tourze/doctrine-entity-checker-bundle
[latest-version-badge]: https://img.shields.io/packagist/v/tourze/doctrine-entity-checker-bundle.svg?style=flat-square
[total-downloads-badge]: https://img.shields.io/packagist/dt/tourze/doctrine-entity-checker-bundle.svg?style=flat-square
[php-version-badge]: https://img.shields.io/packagist/php-v/tourze/doctrine-entity-checker-bundle.svg?style=flat-square
[license-badge]: https://img.shields.io/packagist/l/tourze/doctrine-entity-checker-bundle.svg?style=flat-square
