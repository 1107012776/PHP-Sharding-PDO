<?php
/**
 * PhpShardingPdo  file.
 * @author linyushan  <1107012776@qq.com>
 * @link https://www.developzhe.com/
 * @package https://github.com/1107012776/PHP-Sharding-PDO
 * @copyright Copyright &copy; 2019-2021
 * @license https://github.com/1107012776/PHP-Sharding-PDO/blob/master/LICENSE
 */

namespace PhpShardingPdo\Inter;

use PhpShardingPdo\Core\ShardingPdoContext;
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
    public static $shardingInitConfigInterName = 'shardingInitConfigInter';

    /*
     * @return \PhpShardingPdo\Core\ShardingPdo
     */
    public static function init()
    {
        $databasePdoInstanceMapName = static::getDatabasePdoInstanceMapName();
        $map = ShardingPdoContext::getValue($databasePdoInstanceMapName);
        $obj = new static();
        if (empty($map)) {
            $map = $obj->getDataSourceMap();
            ShardingPdoContext::setValue($databasePdoInstanceMapName, $map);
        }
        $shardingRuleConfig = $obj->getShardingRuleConfiguration();
        $shardingPdo = ShardingDataSourceFactory::createDataSource($databasePdoInstanceMapName, $shardingRuleConfig, $obj->getExecTransactionSqlLogFilePath());
        return $shardingPdo;
    }

    /**
     * 获取构造的db Map数组
     * @return array|boolean
     */
    public static function getDbMap()
    {
        $databasePdoInstanceMapName = static::getDatabasePdoInstanceMapName();
        $map = ShardingPdoContext::getValue($databasePdoInstanceMapName);
        return $map;
    }


    /**
     * 重新链接
     * @param callable|null $errorCallback
     * @return \PhpShardingPdo\Core\ShardingPdo
     */
    public static function reconnection(callable $errorCallback = null)
    {
        $databasePdoInstanceMapName = static::getDatabasePdoInstanceMapName();
        $map = ShardingPdoContext::getValue($databasePdoInstanceMapName);
        if (!empty($map)) {
            try {
                /**
                 * @var \PDO $db
                 */
                foreach ($map as &$db) {
                    //让php先回收已断开长连接资源
                    $db->setAttribute(\PDO::ATTR_PERSISTENT, false);
                    $db = null;
                }
            } catch (\Exception $e) {
                //回收失败
                !empty($errorCallback) && $errorCallback($e);
            }
            ShardingPdoContext::setValue($databasePdoInstanceMapName, false);
        }
        return static::init();
    }


    public static function close(callable $errorCallback = null)
    {
        $databasePdoInstanceMapName = static::getDatabasePdoInstanceMapName();
        $map = ShardingPdoContext::getValue($databasePdoInstanceMapName);
        if (!empty($map)) {
            try {
                /**
                 * @var \PDO $db
                 */
                foreach ($map as &$db) {
                    //让php先回收已断开长连接资源
                    $db->setAttribute(\PDO::ATTR_PERSISTENT, false);
                    $db = null;
                }
            } catch (\Exception $e) {
                //回收失败
                !empty($errorCallback) && $errorCallback($e);
                return false;
            }
            ShardingPdoContext::setValue($databasePdoInstanceMapName, false);
        }
        return true;
    }

    protected static function getDatabasePdoInstanceMapName()
    {
        $shardingInitName = self::$shardingInitConfigInterName . static::class;
        $databasePdoInstanceMapName = $shardingInitName . '_pdo';
        return $databasePdoInstanceMapName;
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
     * 获取事务sql执行日志路径，当事务提交失败的时候会出现该日志
     * @return string
     */
    abstract protected function getExecTransactionSqlLogFilePath();

}
