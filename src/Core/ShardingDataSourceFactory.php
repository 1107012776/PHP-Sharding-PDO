<?php
/**
 * PhpShardingPdo  file.
 * @author linyushan  <1107012776@qq.com>
 * @link https://www.developzhe.com/
 * @package https://github.com/1107012776/PHP-Sharding-PDO
 * @copyright Copyright &copy; 2019-2021
 * @license https://github.com/1107012776/PHP-Sharding-PDO/blob/master/LICENSE
 */
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
    private static $_shardingPdo = 'shardingDataSourceFactory_shardingPdo';

    /**
     * @param array $databasePdoInstanceMap
     * @param ShardingRuleConfiguration $config
     * @return ShardingPdo $share
     */
    public static function createDataSource(array $databasePdoInstanceMap, ShardingRuleConfiguration $config, $exeSqlXaUniqidFilePath = '')
    {
        $_shardingPdo = ShardingPdoContext::getValue(self::$_shardingPdo);
        if (empty($_shardingPdo)) {
            $_shardingPdo = new ShardingPdo($databasePdoInstanceMap, $config, $exeSqlXaUniqidFilePath);
            ShardingPdoContext::setValue(self::$_shardingPdo, $_shardingPdo);
        }
        return clone $_shardingPdo;
    }
}