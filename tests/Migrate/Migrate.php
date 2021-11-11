<?php

namespace PhpShardingPdo\Test\Migrate;

use PhpShardingPdo\Test\Migrate\build\DatabaseCreate;
use PhpShardingPdo\Test\Migrate\build\TableCreate;
use PhpShardingPdo\Test\Migrate\Inter\CreateInter;

/**
 * 测试数据库构建
 * Class Migrate
 * @package PhpShardingPdo\Test\Migrate
 */
class Migrate
{
    public static $classBuildMap = [
        DatabaseCreate::class,
        TableCreate::class,
    ];

    public static function setBuildMap($classBuildMap = [])
    {
        static::$classBuildMap = $classBuildMap;
    }

    public static function build()
    {
        /**
         * @var CreateInter $obj
         */
        foreach (static::$classBuildMap as $val) {
            $obj = (new $val);
            $obj->build();
        }
        return true;
    }
}
