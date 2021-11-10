<?php

namespace PhpShardingPdo\Test;


use PhpShardingPdo\Common\ConfigEnv;
use  \PhpShardingPdo\Core\ShardingTableRuleConfig;
use  \PhpShardingPdo\Core\InlineShardingStrategyConfiguration;
use  \PhpShardingPdo\Core\ShardingRuleConfiguration;
use  \PhpShardingPdo\Inter\ShardingInitConfigInter;
use PhpShardingPdo\Test\Migrate\build\DatabaseCreate;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/24
 * Time: 18:48
 */
class ShardingInitConfig4 extends ShardingInitConfigInter
{
    /**
     * 获取分库分表map各个数据的实例
     * return
     */
    protected function getDataSourceMap()
    {
        return [
            'db0' => self::initDataResurce1(),
            'db1' => self::initDataResurce2(),
            'db2' => self::initDataResurce3(),
            'db3' => self::initDataResurce4(),
        ];
    }

    protected function getShardingRuleConfiguration()
    {
        //article
        $tableRule = new ShardingTableRuleConfig();
        $tableRule->setLogicTable('article');
        $tableRule->setDatabaseShardingStrategyConfig(
            new InlineShardingStrategyConfiguration('db', [
                'operator' => '%',
                'data' => [    //具体的字段和相对运算符右边的数
                    'user_id',  //字段名
                    4
                ]]));
        $tableRule->setTableShardingStrategyConfig(
            new InlineShardingStrategyConfiguration('article_', [
                'operator' => '%',
                'data' => [    //具体的字段和相对运算符右边的数
                    'cate_id',  //字段名
                    2
                ]]));
        $shardingRuleConfig = new ShardingRuleConfiguration();
        $shardingRuleConfig->add($tableRule);  //表1规则
        //account
        $tableRule = new ShardingTableRuleConfig();
        $tableRule->setLogicTable('account');
        $tableRule->setDatabaseShardingStrategyConfig(
            new InlineShardingStrategyConfiguration('db', [], function ($condtion) {
                if (isset($condtion['username']) && !is_array($condtion['username'])) {
                    return crc32($condtion['username']) % 4;
                }
                return null;
            }));
        $tableRule->setTableShardingStrategyConfig(
            new InlineShardingStrategyConfiguration('account_', [], function ($condtion) {
                return 0;
            }));
        $shardingRuleConfig->add($tableRule);  //表1规则
        //user
        $tableRule = new ShardingTableRuleConfig();
        $tableRule->setLogicTable('user');
        $tableRule->setDatabaseShardingStrategyConfig(
            new InlineShardingStrategyConfiguration('db', [], function ($condtion) {
                if (isset($condtion['id']) && !is_array($condtion['id'])) {
                    return $condtion['id'] % 4;
                }
                return null;
            }));
        $tableRule->setTableShardingStrategyConfig(
            new InlineShardingStrategyConfiguration('user_', [], function ($condtion) {
                return 0;
            }));
        $shardingRuleConfig->add($tableRule);  //表1规则

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
            die();
        }
    }

    protected static function initDataResurce2()
    {
        $dbms = 'mysql';
        $dbName = DatabaseCreate::$databaseNameMap[1];
        $servername = ConfigEnv::get('database.host', "localhost");
        $username = ConfigEnv::get('database.username', "root");
        $password = ConfigEnv::get('database.password', "");
        $dsn = "$dbms:host=$servername;dbname=$dbName;port=3306;charset=utf8mb4";
        try {
            return self::connect($dsn, $username, $password);
        } catch (\PDOException $e) {
            die();
        }
    }

    protected static function initDataResurce3()
    {
        $dbms = 'mysql';
        $dbName = DatabaseCreate::$databaseNameMap[2];
        $servername = ConfigEnv::get('database.host', "localhost");
        $username = ConfigEnv::get('database.username', "root");
        $password = ConfigEnv::get('database.password', "");
        $dsn = "$dbms:host=$servername;dbname=$dbName;port=3306;charset=utf8mb4";
        try {
            return self::connect($dsn, $username, $password);
        } catch (\PDOException $e) {
            die();
        }
    }


    protected static function initDataResurce4()
    {
        $dbms = 'mysql';
        $dbName = DatabaseCreate::$databaseNameMap[3];
        $servername = ConfigEnv::get('database.host', "localhost");
        $username = ConfigEnv::get('database.username', "root");
        $password = ConfigEnv::get('database.password', "");
        $dsn = "$dbms:host=$servername;dbname=$dbName;port=3306;charset=utf8mb4";
        try {
            return self::connect($dsn, $username, $password);
        } catch (\PDOException $e) {
            die();
        }
    }

    protected static function connect($dsn, $user, $pass, $option = [])
    {
        //$dbh = new \PDO($dsn, $user, $pass); //初始化一个PDO对象
        //默认这个不是长连接，如果需要数据库长连接，需要最后加一个参数：array(PDO::ATTR_PERSISTENT => true) 变成这样：
//     $dbh = new \PDO($dsn, $user, $pass, array(\PDO :: ATTR_TIMEOUT => 30,\PDO::ATTR_PERSISTENT => true));
        $dbh = new \PDO($dsn, $user, $pass, $option);
        $dbh->query('set names utf8mb4;');
        return $dbh;
    }

    /**
     * 获取sql执行xa日志路径，当xa提交失败的时候会出现该日志
     * @return string
     */
    protected function getExecXaSqlLogFilePath()
    {
        return './execXaSqlLogFilePath.log';
    }
}