<?php

namespace Tourze\DoctrineEntityCheckerBundle\Service;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Query as OrmQuery;
use Doctrine\ORM\Query\Parameter as OrmParameter;
use Doctrine\Persistence\Mapping\MappingException;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Yiisoft\Json\Json;
use Yiisoft\Strings\Inflector;

/**
 * 助手方法，用于生成query的真实sql.
 */
#[Autoconfigure(lazy: true)]
class SqlFormatter
{
    public function __construct(
        private readonly EntityChecker $entityChecker,
        private readonly Inflector $inflector,
        private readonly EntityPrimaryKeyService $primaryKeyService,
    ) {
    }

    /**
     * @return array{string, array<string, mixed>}
     */
    public function getObjectInsertSql(ObjectManager $objectManager, object $object): array
    {
        $this->entityChecker->prePersistEntity($objectManager, $object);

        // 生成sql咯
        $params = [];
        $reflection = $objectManager->getClassMetadata($object::class)->getReflectionClass();
        foreach ($reflection->getProperties() as $property) {
            if (count($property->getAttributes(ORM\OneToMany::class)) > 0 || count($property->getAttributes(ORM\ManyToMany::class)) > 0) {
                continue;
            }
            $column = $property->getAttributes(ORM\Column::class);
            if (count($column) > 0) {
                $column = $column[0]->newInstance();
            } else {
                $column = null;
            }
            /** @var ORM\Column|null $column */
            $name = $column?->name;
            if (null === $name) {
                $name = $this->inflector->toSnakeCase($property->getName());
                if (count($property->getAttributes(ORM\ManyToOne::class)) > 0) {
                    $name = "{$name}_id";
                }
                if (count($property->getAttributes(ORM\OneToOne::class)) > 0) {
                    if (null === $property->getAttributes(ORM\OneToOne::class)[0]->newInstance()->inversedBy) {
                        continue;
                    }
                    $name = "{$name}_id";
                }
            }

            $val = $property->getValue($object);

            // 如果是主键并且没值的话，就跳过
            if (count($property->getAttributes(ORM\Id::class)) > 0 && $val <= 0) {
                continue;
            }

            // 如果是一个Entity的话，那我们就只关注其中的ID
            if (is_object($val)) {
                try {
                    $pkValues = $this->primaryKeyService->getPrimaryKeyValues($val);
                    if (count($pkValues) > 0) {
                        // 一般来讲，doctrine entity 是必须有主键的
                        $val = array_shift($pkValues);
                    }
                } catch (MappingException $exception) {
                    // 字段不存在，类不存在的话，这里就会报错，我们不需要处理
                }
            }

            if (is_bool($val)) {
                $val = $val ? 1 : 0;
            }

            if ($val instanceof \BackedEnum) {
                $val = strval($val->value);
            }

            // JSON格式？
            if (is_array($val)) {
                $val = Json::encode($val);
            }

            // 处理日期时间对象
            if ($val instanceof \DateTimeInterface) {
                $val = $val->format('Y-m-d H:i:s');
            }

            $params[$name] = $val;
        }
        $tableAttribute = $reflection->getAttributes(ORM\Table::class)[0]->newInstance();
        assert($tableAttribute instanceof ORM\Table);
        $tableName = $tableAttribute->name;
        assert(is_string($tableName));

        return [
            $tableName,
            $params,
        ];
    }

    /**
     * 格式化参数值
     */
    private function formatOrmValue(OrmParameter $value): string
    {
        $value = $value->getValue();

        if (null === $value) {
            return 'null';
        }

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
            if (count($value) === 0) {
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

        if (is_object($value)) {
            if (method_exists($value, 'getId')) {
                $id = $value->getId();
                return is_scalar($id) ? (string) $id : '0';
            }
        }

        return is_scalar($value) ? "'" . (string) $value . "'" : "''";
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
        
        foreach ($query->getParameters() as $k => $parameter) {
            $search = ":{$parameter->getName()}";
            $replace = $this->formatOrmValue($parameter);
            $dql = str_replace($search, $replace, $dql);
        }

        return $dql;
    }
}
