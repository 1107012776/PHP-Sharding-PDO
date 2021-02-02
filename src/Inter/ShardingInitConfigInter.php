<?php
/**
 * phpShardingPdo  file.
 * @author linyushan  <1107012776@qq.com>
 * @link https://www.developzhe.com/
 * @package https://github.com/1107012776/PHP-Sharding-PDO
 * @copyright Copyright &copy; 2019-2021
 * @license https://github.com/1107012776/PHP-Sharding-PDO/blob/master/LICENSE
 */
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
     * 获取sql执行xa日志路径，当xa提交失败的时候会出现该日志
     * @return string
     */
    abstract protected function getExecXaSqlLogFilePath();

}