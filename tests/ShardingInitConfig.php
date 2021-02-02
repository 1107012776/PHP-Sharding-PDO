<?php
namespace PhpShardingPdo\Test;
use  \PhpShardingPdo\Core\ShardingTableRuleConfig;
use  \PhpShardingPdo\Core\InlineShardingStrategyConfiguration;
use  \PhpShardingPdo\Core\ShardingRuleConfiguration;
use  \PhpShardingPdo\Inter\ShardingInitConfigInter;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/24
 * Time: 18:48
 */
class ShardingInitConfig extends ShardingInitConfigInter
{
    /**
     * 获取分库分表map各个数据的实例
     * return
     */
    protected  function getDataSourceMap(){
        return [
            'db0' =>  self::initDataResurce1(),
            'db1' =>  self::initDataResurce2()
        ];
    }

    protected function getShardingRuleConfiguration()
    {
        // TODO: Implement getShardingRuleConfiguration() method.
        $tableRule = new ShardingTableRuleConfig();

        $tableRule->setLogicTable('t_order');
        $tableRule->setDatabaseShardingStrategyConfig(
            new InlineShardingStrategyConfiguration('db', [
                'operator' => '%',
                'data' => [    //具体的字段和相对运算符右边的数
                    'user_id',  //字段名
                    2
                ]]));
        $tableRule->setTableShardingStrategyConfig(
            new InlineShardingStrategyConfiguration('t_order_', [
                'operator' => '%',
                'data' => [    //具体的字段和相对运算符右边的数
                    'order_id',  //字段名
                    2
                ]]));
        $shardingRuleConfig  = new ShardingRuleConfiguration();
        $shardingRuleConfig->add($tableRule);
        $shardingRuleConfig->setActualDataNodes([
            'name' => 'db',  //数据库名称
            'range' => [1, 2] //范围
        ], [
            'name' => 't_order',  //表名称
            'range' => [1, 2] //范围
        ]);
        return $shardingRuleConfig;
    }


    protected static function initDataResurce1()
    {
        $dbms = 'mysql';     //数据库类型
        $host = 'localhost'; //数据库主机名
        $dbName = 'shardingpdo1';    //使用的数据库
        $user = 'root';      //数据库连接用户名
        $pass = '123456';          //对应的密码
        $dsn = "$dbms:host=$host;dbname=$dbName;port=3306;charset=utf8mb4";
        try {
            $dbh = new \PDO($dsn, $user, $pass); //初始化一个PDO对象
            //默认这个不是长连接，如果需要数据库长连接，需要最后加一个参数：array(PDO::ATTR_PERSISTENT => true) 变成这样：
            //$this->dbh = new PDO($dsn, $user, $pass, array(PDO::ATTR_PERSISTENT => true));
            $dbh->query('set names utf8mb4;');
            return $dbh;
        } catch (\PDOException $e) {
            die ("1Error!: " . $e->getMessage() . "<br/>");
        }
    }

    protected static function initDataResurce2()
    {
        $dbms = 'mysql';     //数据库类型
        $host = 'localhost'; //数据库主机名
        $dbName = 'shardingpdo2';    //使用的数据库
        $user = 'root';      //数据库连接用户名
        $pass = '123456';          //对应的密码
        $dsn = "$dbms:host=$host;dbname=$dbName;port=3306;charset=utf8mb4";
        try {
            $dbh = new \PDO($dsn, $user, $pass); //初始化一个PDO对象
            //默认这个不是长连接，如果需要数据库长连接，需要最后加一个参数：array(PDO::ATTR_PERSISTENT => true) 变成这样：
            //$this->dbh = new PDO($dsn, $user, $pass, array(PDO::ATTR_PERSISTENT => true));
            $dbh->query('set names utf8mb4;');
            return $dbh;
        } catch (\PDOException $e) {
            die ("2Error!: " . $e->getMessage() . "<br/>");
        }
    }
}