<?php

namespace Tourze\DoctrineEntityCheckerBundle\Tests\Fixtures;

/**
 * 用于测试的示例实体
 */
class TestEntity
{
    /**
     * 实体ID
     */
    public $id;

    /**
     * 获取实体类名
     */
    public static function class(): string
    {
        return self::class;
    }
}
