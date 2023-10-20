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
 * 单个数据库配置
 * User: Administrator
 * Date: 2019/7/24
 * Time: 18:48
 */
class ShardingInitConfigOneDatabase extends ShardingInitConfigInter
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
        //category
        $tableRule = new ShardingTableRuleConfig();
        $tableRule->setLogicTable('category');
        $tableRule->setDatabaseShardingStrategyConfig(
            new InlineShardingStrategyConfiguration('db', [], function ($condition) {
                return 0;
            }));
        $tableRule->setTableShardingStrategyConfig(
            new InlineShardingStrategyConfiguration('category', [], function ($condition) {
                return '';
            }));
        $shardingRuleConfig->add($tableRule);  //表5规则
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
