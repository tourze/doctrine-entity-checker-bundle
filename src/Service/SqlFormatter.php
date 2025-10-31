<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityCheckerBundle\Service;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Query as OrmQuery;
use Doctrine\ORM\Query\Parameter as OrmParameter;
use Doctrine\Persistence\Mapping\MappingException;
use Doctrine\Persistence\ObjectManager;
use Yiisoft\Json\Json;
use Yiisoft\Strings\Inflector;

/**
 * 助手方法，用于生成query的真实sql.
 */
readonly class SqlFormatter
{
    public function __construct(
        private EntityChecker $entityChecker,
        private Inflector $inflector,
        private EntityPrimaryKeyService $primaryKeyService,
    ) {
    }

    /**
     * @return array{string, array<string, mixed>}
     */
    public function getObjectInsertSql(ObjectManager $objectManager, object $object): array
    {
        $this->entityChecker->prePersistEntity($objectManager, $object);

        $params = [];
        $reflection = $objectManager->getClassMetadata($object::class)->getReflectionClass();

        foreach ($reflection->getProperties() as $property) {
            $parameterData = $this->processProperty($property, $object);
            if (null !== $parameterData) {
                $params[$parameterData['name']] = $parameterData['value'];
            }
        }

        $tableName = $this->getTableName($reflection);

        return [$tableName, $params];
    }

    /**
     * 格式化参数值
     */
    private function formatOrmValue(OrmParameter $value): string
    {
        $paramValue = $value->getValue();

        if (null === $paramValue) {
            return 'null';
        }

        return $this->formatValueByType($paramValue);
    }

    /**
     * 格式化并返回完整可执行的DQL.
     */
    public function fromOrmQuery(OrmQuery $query): string
    {
        $dql = $query->getDQL();
        if (null === $dql) {
            return '';
        }

        foreach ($query->getParameters() as $parameter) {
            $search = ":{$parameter->getName()}";
            $replace = $this->formatOrmValue($parameter);
            $dql = str_replace($search, $replace, $dql);
        }

        return $dql;
    }

    /**
     * @return array{name: string, value: mixed}|null
     */
    private function processProperty(\ReflectionProperty $property, object $object): ?array
    {
        if ($this->shouldSkipProperty($property)) {
            return null;
        }

        $columnName = $this->getColumnName($property);
        if (null === $columnName) {
            return null;
        }

        $value = $property->getValue($object);

        if ($this->shouldSkipEmptyId($property, $value)) {
            return null;
        }

        $processedValue = $this->processValue($value);

        return ['name' => $columnName, 'value' => $processedValue];
    }

    private function shouldSkipProperty(\ReflectionProperty $property): bool
    {
        return count($property->getAttributes(ORM\OneToMany::class)) > 0
            || count($property->getAttributes(ORM\ManyToMany::class)) > 0;
    }

    private function getColumnName(\ReflectionProperty $property): ?string
    {
        $explicitName = $this->getExplicitColumnName($property);
        if (null !== $explicitName) {
            return $explicitName;
        }

        return $this->generateColumnName($property);
    }

    private function getExplicitColumnName(\ReflectionProperty $property): ?string
    {
        $column = $property->getAttributes(ORM\Column::class);
        if (count($column) > 0) {
            $columnInstance = $column[0]->newInstance();

            return $columnInstance->name;
        }

        return null;
    }

    private function generateColumnName(\ReflectionProperty $property): ?string
    {
        $name = $this->inflector->toSnakeCase($property->getName());

        if (count($property->getAttributes(ORM\ManyToOne::class)) > 0) {
            return "{$name}_id";
        }

        if (count($property->getAttributes(ORM\OneToOne::class)) > 0) {
            $oneToOneAttribute = $property->getAttributes(ORM\OneToOne::class)[0]->newInstance();
            if (null === $oneToOneAttribute->inversedBy) {
                return null;
            }

            return "{$name}_id";
        }

        return $name;
    }

    private function shouldSkipEmptyId(\ReflectionProperty $property, mixed $value): bool
    {
        return count($property->getAttributes(ORM\Id::class)) > 0 && $value <= 0;
    }

    private function processValue(mixed $value): mixed
    {
        if (is_object($value)) {
            if ($value instanceof \BackedEnum) {
                return strval($value->value);
            }

            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d H:i:s');
            }

            return $this->processObjectValue($value);
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_array($value)) {
            return Json::encode($value);
        }

        return $value;
    }

    private function processObjectValue(object $value): mixed
    {
        try {
            $pkValues = $this->primaryKeyService->getPrimaryKeyValues($value);
            if (count($pkValues) > 0) {
                return array_shift($pkValues);
            }
        } catch (MappingException $exception) {
            // 字段不存在，类不存在的话，这里就会报错，我们不需要处理
        }

        return $value;
    }

    /**
     * @param \ReflectionClass<object> $reflection
     */
    private function getTableName(\ReflectionClass $reflection): string
    {
        $tableAttribute = $reflection->getAttributes(ORM\Table::class)[0]->newInstance();
        assert($tableAttribute instanceof ORM\Table);
        $tableName = $tableAttribute->name;
        assert(is_string($tableName));

        return $tableName;
    }

    private function formatValueByType(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return "'{$value->format('Y-m-d H:i:s')}'";
        }

        if (is_array($value)) {
            return $this->formatArrayValue($value);
        }

        if (is_object($value)) {
            return $this->formatObjectId($value);
        }

        return is_scalar($value) ? "'" . (string) $value . "'" : "''";
    }

    /**
     * @param array<mixed> $value
     */
    private function formatArrayValue(array $value): string
    {
        if (0 === count($value)) {
            return "''";
        }

        $tmp = [];
        foreach ($value as $item) {
            if (is_scalar($item) || (is_object($item) && method_exists($item, '__toString'))) {
                $tmp[] = "'" . (string) $item . "'";
            }
        }

        return implode(', ', $tmp);
    }

    private function formatObjectId(object $value): string
    {
        if (method_exists($value, 'getId')) {
            $id = $value->getId();

            return is_scalar($id) ? (string) $id : '0';
        }

        return "''";
    }
}
