<?php

namespace PhpShardingPdo\Inter;

use  PhpShardingPdo\Core\ShardingRuleConfiguration;
use  PhpShardingPdo\Core\ShardingDataSourceFactory;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/24
 * Time: 18:48
 */
abstract class ShardingInitConfigInter
{
    /**
     * @var \PhpShardingPdo\Core\ShardingPdo
     */
    private static $shardingPdo;

    /*
     * @return \PhpShardingPdo\Core\ShardingPdo
     */
    public static function init()
    {
        if (!empty(self::$shardingPdo)) {
            return clone self::$shardingPdo;
        }
        $obj = new static();
        $shardingRuleConfig = $obj->getShardingRuleConfiguration();
        self::$shardingPdo = ShardingDataSourceFactory::createDataSource($obj->getDataSourceMap(), $shardingRuleConfig, $obj->getExecXaSqlLogFilePath());
        return clone self::$shardingPdo;
    }


    /**
     * 获取分库分表map各个数据的实例
     * return array
     */
    abstract protected function getDataSourceMap();

    /**
     * @return ShardingRuleConfiguration
     */
    abstract protected function getShardingRuleConfiguration();

    /**
     * 获取sql执行错误日志路径
     * @return string
     */
    abstract protected function getExecXaSqlLogFilePath();

}