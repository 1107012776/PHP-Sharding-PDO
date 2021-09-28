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
        $shardingInitName = self::$shardingInitConfigInterName.static::class;
        $databasePdoInstanceMapName =  $shardingInitName.'_pdo';
        $map = ShardingPdoContext::getValue($databasePdoInstanceMapName);
        $obj = new static();
        if(empty($map)){
            $map = $obj->getDataSourceMap();
            ShardingPdoContext::setValue($databasePdoInstanceMapName, $map);
        }
        $shardingRuleConfig = $obj->getShardingRuleConfiguration();
        $shardingPdo = ShardingDataSourceFactory::createDataSource($databasePdoInstanceMapName, $shardingRuleConfig, $obj->getExecXaSqlLogFilePath());
        return $shardingPdo;
    }


    public static function reconnection(callable $errorCallback = null)
    {
        $shardingInitName = self::$shardingInitConfigInterName.static::class;
        $databasePdoInstanceMapName =  $shardingInitName.'_pdo';
        $map = ShardingPdoContext::getValue($databasePdoInstanceMapName);
        if(!empty($map)){
            try{
                /**
                 * @var \PDO $db
                 */
                foreach ($map as &$db){
                    //让php先回收已断开长连接资源
                    $db->setAttribute(\PDO::ATTR_PERSISTENT, false);
                    $db = null;
                }
            }catch (\Exception $e){
                //回收失败
               !empty($errorCallback) && $errorCallback($e);
            }
            ShardingPdoContext::setValue($databasePdoInstanceMapName, false);
        }
        return static::init();
    }
    
    
   public static function close(callable $errorCallback = null)
   {
        $shardingInitName = self::$shardingInitConfigInterName.static::class;
        $databasePdoInstanceMapName =  $shardingInitName.'_pdo';
        $map = ShardingPdoContext::getValue($databasePdoInstanceMapName);
        if(!empty($map)){
            try{
                /**
                 * @var \PDO $db
                 */
                foreach ($map as &$db){
                    //让php先回收已断开长连接资源
                    $db->setAttribute(\PDO::ATTR_PERSISTENT, false);
                    $db = null;
                }
            }catch (\Exception $e){
                //回收失败
                !empty($errorCallback) && $errorCallback($e);
                return false;
            }
            ShardingPdoContext::setValue($databasePdoInstanceMapName, false);
        }
        return true;
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
