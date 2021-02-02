# PHP-Sharding-PDO
PHP版，MySQL切片分库分表的类库插件，需要依赖PDO
### 安装
composer require lys/php-sharding-pdo

### 说明
暂不支持协程

#### 示例
##### 1.我们需要配置一下基本的分块规则配置类
```php
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
    protected function getDataSourceMap()
    {
        return [
            'db0' => self::initDataResurce1(),
            'db1' => self::initDataResurce2()
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
        $shardingRuleConfig = new ShardingRuleConfiguration();
        $shardingRuleConfig->add($tableRule);


        $tableRuleUser = new ShardingTableRuleConfig();

        $tableRuleUser->setLogicTable('t_user');
        $tableRuleUser->setDatabaseShardingStrategyConfig(
            new InlineShardingStrategyConfiguration('db', [
                'operator' => '%',
                'data' => [    //具体的字段和相对运算符右边的数
                    'user_id',  //字段名
                    2
                ]]));
        $tableRuleUser->setTableShardingStrategyConfig(
            new InlineShardingStrategyConfiguration('t_user_', [
                'operator' => '%',
                'data' => [    //具体的字段和相对运算符右边的数
                    'order_id',  //字段名
                    2
                ]]));
        $shardingRuleConfig = new ShardingRuleConfiguration();
        $shardingRuleConfig->add($tableRule);  //表1规则
        $shardingRuleConfig->add($tableRuleUser);  //表2规则
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

    /**
     * 获取sql执行xa日志路径，当xa提交失败的时候会出现该日志
     * @return string
     */
    protected  function getExecXaSqlLogFilePath(){
        $unqi = uniqid(time(), true);
        $unqi = str_replace('.', '', $unqi);
        return './' . date('YmdHis') . $unqi . '.log';
    }
}
```
##### 2.model创建
```php
<?php

namespace PhpShardingPdo\Test;

use PhpShardingPdo\Core\Model;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/25
 * Time: 20:12
 */
class OrderModel extends Model
{
    protected $tableName = 't_order';
    protected $tableNameIndexConfig = [
        'index' => '0,1', //分表索引 index ,号分割
        //'range' => [1,2]  //范围
    ];
    protected $shardingInitConfigClass = ShardingInitConfig::class;

}
```

```php
<?php

namespace PhpShardingPdo\Test;

use PhpShardingPdo\Core\Model;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/25
 * Time: 20:12
 */
class UserModel extends Model
{
    protected $tableName = 't_user';
    protected $tableNameIndexConfig = [
        'index' => '0,1', //分表索引 index ,号分割
        //'range' => [1,2]  //范围
    ];
    protected $shardingInitConfigClass = ShardingInitConfig::class;

}
```

##### 3.基础用法
###### 查询
```php
<?php
$order = new PhpShardingPdo\Test\OrderModel();
$res = $order->where(['user_id' => 2, 'order_id' => 2])->find();
var_dump($res);
$res = $order->renew()->where(['user_id' => 2, 'order_id' => 1])->find();
var_dump($res);
$res = $order->renew()->where(['id' => 3])->findAll();
var_dump($res);
//order by
$res = $order->renew()->order('order_id desc')->limit(100)->findAll();
var_dump($res);
var_dump($order->find());
//group by
$res = $order->renew()->field('order_id,sum(id),create_time,user_id')->group('order_id')->limit(100)->findAll();
var_dump($res);
$newObj = clone $order->renew();
var_dump($newObj === $order);  //输出false
```

###### 插入
```php
<?php
$order = new \PhpShardingPdo\Test\OrderModel();
$user = new \PhpShardingPdo\Test\UserModel();
$order->startTrans();
$order->startTrans();
$insert = $order->renew()->insert(['user_id' => 1, 'order_id' => '1231', 'create_time' => date('Y-m-d H:i:s')]);
var_dump($insert, $order->getLastInsertId());
$insert = $user->renew()->insert(['user_id' => 2, 'order_id' => '1231', 'create_time' => date('Y-m-d H:i:s')]);
var_dump($insert, $user->getLastInsertId());
$user->commit();
$user->commit();
```


###### 更新
```php
<?php
$order = new PhpShardingPdo\Test\OrderModel();
$order->startTrans(); //事务嵌套
$res = $order->renew()->where(['id' => 3])->update(['create_time' => date('Y-m-d H:i:s')]);
var_dump($res);  //影响行数
$order->commit();
```

###### 删除
```php
<?php
$order = new  PhpShardingPdo\Test\OrderModel();
$order->startTrans();
$res = $order->renew()->where(['id' => 9])->delete();
var_dump($res);  //影响行数
$order->commit();

```
