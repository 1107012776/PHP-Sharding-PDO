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
     * @var string $_shardingPdo
     */
    private static $_shardingPdo = 'shardingDataSourceFactory_shardingPdo';

    /**
     * @param string $databasePdoInstanceMapName
     * @param ShardingRuleConfiguration $config
     * @param $execSqlTransactionFilePath
     * @return ShardingPdo
     */
    public static function createDataSource($databasePdoInstanceMapName, ShardingRuleConfiguration $config, $execSqlTransactionFilePath = '')
    {
        $shardingPdo = ShardingPdoContext::getValue(self::$_shardingPdo . $databasePdoInstanceMapName);
        if (empty($shardingPdo)) {
            $shardingPdo = new ShardingPdo($databasePdoInstanceMapName, $config, $execSqlTransactionFilePath);
            ShardingPdoContext::setValue(self::$_shardingPdo . $databasePdoInstanceMapName, $shardingPdo);
        }
        return clone $shardingPdo;
    }
}
