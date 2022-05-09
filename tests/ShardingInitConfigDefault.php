<?php

namespace PhpShardingPdo\Test;


use PhpShardingPdo\Common\ConfigEnv;
use PhpShardingPdo\Core\ShardingPdoContext;
use PhpShardingPdo\Core\ShardingTableRuleConfig;
use PhpShardingPdo\Core\InlineShardingStrategyConfiguration;
use PhpShardingPdo\Core\ShardingRuleConfiguration;
use PhpShardingPdo\Inter\ShardingInitConfigInter;
use PhpShardingPdo\Test\Migrate\build\DatabaseCreate;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/24
 * Time: 18:48
 */
class ShardingInitConfigDefault extends ShardingInitConfigInter
{
    /**
     * 获取分库分表map各个数据的实例
     * return
     */
    protected function getDataSourceMap()
    {
        return [
            'db0' => self::initDataResurce1(),
        ];
    }

    protected function getShardingRuleConfiguration()
    {
        $shardingRuleConfig = new ShardingRuleConfiguration();
        return $shardingRuleConfig;
    }


    protected static function initDataResurce1()
    {
        $dbms = 'mysql';
        $dbName = DatabaseCreate::$databaseNameMap[0];
        $servername = ConfigEnv::get('database.host', "localhost");
        $username = ConfigEnv::get('database.username', "root");
        $password = ConfigEnv::get('database.password', "");
        $dsn = "$dbms:host=$servername;dbname=$dbName;port=3306;charset=utf8mb4";
        try {
            return self::connect($dsn, $username, $password);
        } catch (\PDOException $e) {
            if (ShardingPdoContext::getCid() > -1) {
                \Swoole\Event::exit();
            } else {
                die();
            }
        }
    }

    protected static function connect($dsn, $user, $pass, $option = [])
    {
        //$dbh = new PhpShardingPdo\Core\SPDO($dsn, $user, $pass); //初始化一个PDO对象
        //默认这个不是长连接，如果需要数据库长连接，需要最后加一个参数：array(PDO::ATTR_PERSISTENT => true) 变成这样：
        //$dbh = new \PhpShardingPdo\Core\SPDO($dsn, $user, $pass, array(\PDO :: ATTR_TIMEOUT => 30,\PDO::ATTR_PERSISTENT => true));
        $dbh = new \PhpShardingPdo\Core\SPDO($dsn, $user, $pass, $option);
        $dbh->query('set names utf8mb4;');
        return $dbh;
    }


    /**
     * 获取事务sql执行日志路径，当事务提交失败的时候会出现该日志
     * @return string
     */
    protected function getExecTransactionSqlLogFilePath()
    {
        return './execTransactionSqlLogFilePath.log';
    }
}
