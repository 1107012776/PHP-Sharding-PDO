<?php

namespace PhpShardingPdo\Core;
/**
 * 返回数据dao实例DataSource
 * User: lys
 * Date: 2019/7/24
 * Time: 17:04
 */
class ShardingDataSourceFactory
{
    /**
     * @var ShardingPdo $_shardingPdo
     */
    private static $_shardingPdo = null;

    /**
     * @param array $databasePdoInstanceMap
     * @param ShardingRuleConfiguration $config
     * @return ShardingPdo $share
     */
    public static function createDataSource(array $databasePdoInstanceMap, ShardingRuleConfiguration $config, $exeSqlXaUniqidFilePath = '')
    {
        if (empty(self::$_shardingPdo)) {
            self::$_shardingPdo = new ShardingPdo($databasePdoInstanceMap, $config, $exeSqlXaUniqidFilePath);
        }
        return self::$_shardingPdo;
    }
}