# PHP-Sharding-PDO
PHP、MySQL分库分表中间件，需要依赖PDO，PHP分库分表，支持协程

[目录](#PHP-Sharding-PDO)
- [一、安装](#安装)

- [二、说明](#说明)

- [三、注意](#注意)

- [四、单元测试](#单元测试)

- [五、示例](#示例)

  - [1.基本的分块规则配置类](#1我们需要配置一下基本的分块规则配置类)

  - [2.Model创建](#2Model创建)

  - [3.基础用法](#3基础用法)

    - [查询](#查询)
    
    - [插入](#插入)
    
    - [更新](#更新)
    
    - [删除](#删除)
  - [4.Join用法](#4Join用法)  
  
  - [5.XA用法](#5XA用法)  
- [六、案例](#案例)
  
# 环境要求
- PHP >= 7.2
- Swoole >= 4.1.0 （协程环境）
# 安装

You can install the package via composer:

```bash
composer require lys/php-sharding-pdo
```
# 说明
###### （1）已支持协程，使用协程必须在主进程开启   \Swoole\Runtime::enableCoroutine(); 
###### （2）支持分片规则自定义，支持实现复杂的分片，分片规则是依赖输入的where条件或者insert插入的数据来的
# 注意
###### （1）协程模式必须在主进程开启这个东西，否则会出现死锁
```bash
\Swoole\Runtime::enableCoroutine(); 
```
###### （2）协程中不能使用pdo长连接，在高并发的情况下，会出现如下异常
```bash
PHP Fatal error:  Uncaught Swoole\Error: Socket#30 has already been bound to another coroutine#2,
reading of the same socket in coroutine#4 at the same time is not allowed
```
###### （3）Replace into自增主键，并发量大的时候可能出现返回false和死锁的，所以不适合高并发项目的使用，高并发，请使用雪花算法等一些分布式主键方案

###### （4）非协程情况下，并且常驻内存，如workerman框架请使用如下代码释放上下文，上下文管理为单例，所以需要该方法释放单例实例，一般是在一个请求结束，或者一个任务结束，释放完上下文，请重新new Model实例才行，因为释放上下文，清理了上下文中的PDO实例，方法如下:
```php
<?php
//上下文本身应该在一次请求结束，就要重置，本身里面的值就有时效性，比如PDO实例会超时断连
\PhpShardingPdo\Core\ShardingPdoContext::contextFreed();  

```
# 单元测试
```bash
git clone https://github.com/1107012776/PHP-Sharding-PDO.git

cd PHP-Sharding-PDO

composer install
```
### （1）先要配置tests/Config/.env ，测试环境数据库链接

> .env文件
```php
[database]
host=localhost
username=root
password=testpassword
[shardingPdo]
#开启记录sql日志会影响性能
sqlLogOpen=false
sqlLogPath=sql.sql
```
### （2）然后执行如下脚本

> 非协程
```bash
php vendor/bin/phpunit tests/IntegrationTest.php --filter testExecStart
```
> 协程
```bash
php vendor/bin/phpunit tests/IntegrationCoroutineTest.php --filter testExecStart
```


# 示例 
> 详细请看tests目录
### 1.我们需要配置一下基本的分块规则配置类
```php
<?php

namespace PhpShardingPdo\Test;
use PhpShardingPdo\Common\ConfigEnv;
use PhpShardingPdo\Core\ShardingTableRuleConfig;
use PhpShardingPdo\Core\InlineShardingStrategyConfiguration;
use PhpShardingPdo\Core\ShardingPdoContext;
use PhpShardingPdo\Core\ShardingRuleConfiguration;
use PhpShardingPdo\Inter\ShardingInitConfigInter;
use PhpShardingPdo\Test\Migrate\build\DatabaseCreate;
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
        $shardingRuleConfig->add($tableRule);  //表2规则
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
        $shardingRuleConfig->add($tableRule);  //表3规则


        //auto_distributed
        $tableRule = new ShardingTableRuleConfig();
        $tableRule->setLogicTable('auto_distributed');
        $tableRule->setDatabaseShardingStrategyConfig(
            new InlineShardingStrategyConfiguration('db', [], function ($condtion) {
                if (isset($condtion['stub']) && !is_array($condtion['stub'])) {
                    return $condtion['stub'] % 4;
                }
                return null;
            }));
        $tableRule->setTableShardingStrategyConfig(
            new InlineShardingStrategyConfiguration('auto_distributed', [], function ($condtion) {
                return '';
            }));
        $shardingRuleConfig->add($tableRule);  //表4规则

        //category
        $tableRule = new ShardingTableRuleConfig();
        $tableRule->setLogicTable('category');
        $tableRule->setDatabaseShardingStrategyConfig(
            new InlineShardingStrategyConfiguration('db', [], function ($condtion) {
                return 0;
            }));
        $tableRule->setTableShardingStrategyConfig(
            new InlineShardingStrategyConfiguration('category', [], function ($condtion) {
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
            }else{
                die();
            }
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
            if (ShardingPdoContext::getCid() > -1) {
                \Swoole\Event::exit();
            }else{
                die();
            }
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
            if (ShardingPdoContext::getCid() > -1) {
                \Swoole\Event::exit();
            }else{
                die();
            }
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
            if (ShardingPdoContext::getCid() > -1) {
                \Swoole\Event::exit();
            }else{
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

```
### 2.Model创建
```php
<?php

namespace PhpShardingPdo\Test\Model;
use PhpShardingPdo\Components\SoftDeleteTrait;
use PhpShardingPdo\Core\Model;
use PhpShardingPdo\Test\ShardingInitConfig4;
Class ArticleModel extends Model
{
    use SoftDeleteTrait; //软删除需要配置这个
    protected $tableName = 'article';
    protected $tableNameIndexConfig = [
        'index' => '0,1', //分表索引 index ,号分割
        //'range' => [1,2]  //范围
    ];
    protected $shardingInitConfigClass = ShardingInitConfig4::class;
}

```

```php
<?php

namespace PhpShardingPdo\Test\Model;
use PhpShardingPdo\Core\Model;
use PhpShardingPdo\Test\ShardingInitConfig4;
Class UserModel extends Model
{
    protected $tableName = 'user';
    protected $shardingInitConfigClass = ShardingInitConfig4::class;
    protected $tableNameIndexConfig = [
        'index' => '0', //分表索引 index ,号分割
        //'range' => [1,2]  //范围
    ];
}
```

### 3.基础用法
#### 查询
```php
<?php
$model = new \PhpShardingPdo\Test\Model\ArticleModel();
$res = $model->where(['user_id' => 2, 'cate_id' => 1])->find();
var_dump($res);
$res = $model->renew()->where(['user_id' => 2, 'cate_id' => 1])->find();
var_dump($res);
$res = $model->renew()->where(['id' => 3])->findAll();
var_dump($res);
//order by
$res = $model->renew()->order('user_id desc')->limit(100)->findAll();
var_dump($res);
var_dump($model->find());
//group by
$res = $model->renew()->field('sum(id) as total,create_time,user_id')->group('user_id')->limit(100)->findAll();
var_dump($res);
$newObj = clone $model->renew();
var_dump($newObj === $model);  //输出false
//count 查询
$count = $model->renew()->count();
var_dump($count);
$count = $model->renew()->where(['id' => ['gt', 100000]])->count('id');   //索引覆盖型查询
var_dump($count);
//in 查询
$list = $model->renew()->where(['id' => ['in', [1,2,3]]])->findAll();  
var_dump($list);
//not in 查询
$list = $model->renew()->where(['id' => ['notIn', [1,2,3]]])->findAll();  
var_dump($list);
//gt大于  egt大于等于  lt小于  elt小于等于
$list = $model->renew()->where(['id' => ['gt', 1]])->findAll(); 
var_dump($list);
//between 两者之间 相当于  id >= 100 and id <= 10000
$list = $model->renew()->where(['id' => ['between', [100, 10000]]])->findAll();  
var_dump($list);
//同一个字段多条件查询 相当于 cate_id >= 1 and cate_id <= 4 和上面的between一样
$count = $model->renew()->where([
    'cate_id' => ['egt', 1]
])->where(['article_title' => '文章1'])
->where(['cate_id' => ['elt', 4]])
->count();
$this->assertEquals($count == 4, true);
//not between  不在两者之间 相当于  id < 100 and id > 10000
$list = $model->renew()->where(['id' => ['notBetween', [100, 10000]]])->findAll();  
var_dump($list);
//neq 不等于  可以是数组，也可以单个
$list = $model->renew()->where(['id' => ['neq', [1,2,3]]])->findAll();  
var_dump($list);
$list = $model->renew()->where(['id' => ['neq', 1]])->findAll();  
var_dump($list);
//like 查询
$list = $model->renew()->where(['article_title' => ['like','某网络科技%'],'type' => 1])->findAll();  
var_dump($list);
//not like 查询
$list = $model->renew()->where(['article_title' => ['notLike','某网络科技%'],'type' => 1])->findAll();  
var_dump($list);
//findInSet 查询
$count = $model->renew()->where([
    'cate_id' => ['findInSet', 1]
])->where(['article_title' => '文章1'])
->count();
$this->assertEquals($count == 2, true);
```

#### 插入
```php
<?php
$model = new \PhpShardingPdo\Test\Model\ArticleModel();
$user = new \PhpShardingPdo\Test\Model\UserModel();
$model->startTrans(); 
$model->startTrans(); //事务嵌套
$res = $user->renew()->insert(['id' => 2,  'create_time' => date('Y-m-d H:i:s')]);
$this->assertEquals(!empty($res), true);
$res = $model->renew()->insert(['user_id' => $user->getLastInsertId(), 'article_title' => '某网络科技', 'create_time' => date('Y-m-d H:i:s')]);
$this->assertEquals(!empty($res), true);
$user->commit();
$user->commit();
```


#### 更新
```php
<?php
$model = new \PhpShardingPdo\Test\Model\ArticleModel();
$model->startTrans(); 
$res = $model->renew()->where(['id' => 3])->update(['update_time' => date('Y-m-d H:i:s')]);
var_dump($res);  //影响行数
//decr 自减
$res = $model->renew()->where(['id' => 3])->decr('is_choice', 1);
var_dump($res); //影响行数
//incr 自增
$res = $model->renew()->where(['id' => 3])->incr('is_choice', 1);
var_dump($res); //影响行数
$model->commit();
```

#### 删除
```php
<?php
$model = new \PhpShardingPdo\Test\Model\ArticleModel();
$model->startTrans();
$res = $model->renew()->where(['id' => 9])->delete();
var_dump($res);  //影响行数
$model->commit();
//强制物理删除（如果有设置软删除的话）
$model->startTrans();
$res = $model->renew()->where(['id' => 10])->delete(true);
var_dump($res);  //影响行数
$model->commit();

```

### 4.Join用法
> Join只支持同个数据库的，不支持跨库
```php
<?php
namespace PhpShardingPdo\Test;
ini_set("display_errors", "On");

error_reporting(E_ALL); //显示所有错误信息
ini_set('date.timezone', 'Asia/Shanghai');

use PhpShardingPdo\Common\ConfigEnv;
use PhpShardingPdo\Test\Migrate\Migrate;
use PhpShardingPdo\Test\Model\ArticleModel;
use PhpShardingPdo\Test\Model\UserModel;
use PHPUnit\Framework\TestCase;

$file_load_path = __DIR__ . '/../../../autoload.php';
if (file_exists($file_load_path)) {
    require_once $file_load_path;
} else {
    $vendor = __DIR__ . '/../vendor/autoload.php';
    require_once $vendor;
}

ConfigEnv::loadFile(dirname(__FILE__) . '/Config/.env');  //加载配置

/**
* @method assertEquals($a, $b)
*/
class IntegrationTest extends TestCase
{
    /**
     * join查询测试
     * php vendor/bin/phpunit tests/IntegrationTest.php --filter testJoin
     */
    public function testJoin()
    {
        $articleModel = new \PhpShardingPdo\Test\Model\ArticleModel();
        $articleModel->alias('ar');
        $cateModel = new \PhpShardingPdo\Test\Model\CategoryModel();
        $cateModel1 = clone $cateModel;
        //这边输入where条件是用来查询具体表名的，用于后续join
        $plan = $cateModel1->alias('cate')->where([
            'id' => 1 
            ])->createJoinTablePlan([
            'cate.id' => $articleModel->getFieldAlias('cate_id') //这边是on条件 用于关联
        ]);
        //plan计划失败，其实就是找不到后续要用到的具体join表名，而表名由分表规则及输入where条件决定
        $this->assertEquals(!empty($plan), true); 
        $articleModel1 = clone $articleModel;
        $list = $articleModel1->innerJoin($plan)
            ->where(['cate_id' => 1])->findAll();
        $this->assertEquals(count($list) == 2, true);
        $this->assertEquals(empty($articleModel1->sqlErrors()), true);
        $articleModel1 = clone $articleModel;
        $count = $articleModel1->innerJoin($plan)
            ->where(['cate_id' => 1])->count();
        $this->assertEquals($count == 2, true);
        $this->assertEquals(empty($articleModel1->sqlErrors()), true);
        //实行三表关联查询
        $userModel = new UserModel();  //用户表
        $articleModel1 = clone $articleModel; //文章表
        $cateModel1 = clone $cateModel;  //分类表
        $userModel1 = clone $userModel;  //用户表
        $user_id = 1;
        $catePlan = $cateModel1->alias('cate')->where(['id' => 1])->createJoinTablePlan([
            'cate.id' => $articleModel1->getFieldAlias('cate_id')
        ]);
        $articlePlan = $articleModel1->alias('ar')->where(['cate_id' => 1])->createJoinTablePlan([
            'user.id' => $articleModel1->getFieldAlias('user_id')
        ]);
        $this->assertEquals(!empty($catePlan), true);
        $this->assertEquals(!empty($articlePlan), true);
        $list = $userModel1->alias('user')->field(['user.id', 'ar.cate_id as a', 'cate.id as b'])
            ->innerJoin($catePlan)
            ->innerJoin($articlePlan)
            ->where([
                'id' => $user_id
            ])->findAll();
        $this->assertEquals(isset($list[0]['id']) && $list[0]['id'] == 1, true);
        $this->assertEquals(isset($list[0]['a']) && $list[0]['a'] == 1, true);
        $this->assertEquals(isset($list[0]['b']) && $list[0]['b'] == 1, true);
        $this->assertEquals(empty($userModel1->sqlErrors()), true);
    }

    public function testLeftJoin()
    {
        $articleModel = new \PhpShardingPdo\Test\Model\ArticleModel();
        $articleModel->alias('ar');
        $cateModel = new \PhpShardingPdo\Test\Model\CategoryModel();
        $cateModel->alias('cate');
        $articleModel1 = clone $articleModel;
        $cateModel1 = clone $cateModel;
        $plan = $cateModel1->where(['id' => 1])->createJoinTablePlan([
            'cate.id' => $articleModel1->getFieldAlias('cate_id')
        ]);
        $this->assertEquals(!empty($plan), true);
        $list = $articleModel1->field(['ar.*', 'cate.name as cate_name'])->leftJoin($plan)
            ->where([$cateModel1->getFieldAlias('id') => 1])->findAll();
        $this->assertEquals(count($list) == 2, true);
    }

    public function testRightJoin()
    {
        $articleModel = new \PhpShardingPdo\Test\Model\ArticleModel();
        $articleModel->alias('ar');
        $cateModel = new \PhpShardingPdo\Test\Model\CategoryModel();
        $cateModel->alias('cate');
        $articleModel1 = clone $articleModel;
        $cateModel1 = clone $cateModel;
        $plan = $cateModel1->where(['id' => 1])->createJoinTablePlan([
            'cate.id' => $articleModel1->getFieldAlias('cate_id')
        ]);
        $this->assertEquals(!empty($plan), true);
        $list = $articleModel1->field(['ar.*', 'cate.name as cate_name'])->rightJoin($plan)
            ->where([
                $articleModel1->getFieldAlias('cate_id') => 1,
                $articleModel1->getFieldAlias('user_id') => 1,
            ])->findAll();
        $this->assertEquals(count($list) == 1, true);
    }
}
```

### 5.XA用法
```php
<?php

$articleModel = new \PhpShardingPdo\Test\Model\ArticleXaModel();
$data = [
    'article_descript' => 'xa测试数据article_descript',
    'article_img' => '/upload/2021110816311943244.jpg',
    'article_keyword' => 'xa测试数据article_keyword',
    'article_title' => $this->article_title2,
    'author' => '学者',
    'cate_id' => 3,
    'content' => '<p>xa测试数据</p><br/>',
    'content_md' => 'xa测试数据',
    'create_time' => date('Y-m-d H:i:s'),
    'update_time' => date('Y-m-d H:i:s'),
    'user_id' => $this->testUserId(),
];
$data['id'] = $this->testGetId(2);
$articleModel->startTrans($articleModel->createXid());
$res = $articleModel->renew()->insert($data);
$this->assertEquals(!empty($res), true);
$articleModel->endXa();
$this->assertEquals(empty($articleModel->sqlErrors()), true);
$articleModel->prepareXa();
$this->assertEquals(empty($articleModel->sqlErrors()), true);
$articleModel->commit();
$this->assertEquals(empty($articleModel->sqlErrors()), true);
$row = $articleModel->where(['id' => $articleModel->getLastInsertId()])->find();
$this->assertEquals(!empty($row), true);
$articleModel = new \PhpShardingPdo\Test\Model\ArticleXaModel();
$data['id'] = $this->testGetId(2);
$articleModel->startTrans($articleModel->createXid());
$res = $articleModel->renew()->where(['id' => $row['id']])->delete();
$this->assertEquals(!empty($res), true);
$res = $articleModel->renew()->insert($data);
$this->assertEquals(!empty($res), true);
$articleModel->endXa();
$this->assertEquals(empty($articleModel->sqlErrors()), true);
$articleModel->prepareXa();
$this->assertEquals(empty($articleModel->sqlErrors()), true);
$articleModel->rollback();
$this->assertEquals(empty($articleModel->sqlErrors()), true);
$row = $articleModel->where(['id' => $articleModel->getLastInsertId()])->find();
$this->assertEquals(empty($row), true);

/**
* xa 事务Recover测试 (具体看tests目录里面的测试用例)
*/
$xid = '213123123213';
$data = [
    'article_descript' => 'xa测试数据article_descript',
    'article_img' => '/upload/2021110816311943244.jpg',
    'article_keyword' => 'xa测试数据article_keyword',
    'article_title' => $this->article_title2,
    'author' => '学者',
    'cate_id' => 1,
    'content' => '<p>xa测试数据</p><br/>',
    'content_md' => 'xa测试数据',
    'create_time' => date('Y-m-d H:i:s'),
    'update_time' => date('Y-m-d H:i:s'),
    'user_id' => 1,
];
$data['id'] = $this->testGetId(2);
$articleModel = new \PhpShardingPdo\Test\Model\ArticleXaModel();
$articleModel->startTrans($xid);
$res = $articleModel->renew()->insert($data);
$this->assertEquals(!empty($res), true);
$articleModel->endXa();
$this->assertEquals(empty($articleModel->sqlErrors()), true);
$articleModel->prepareXa(); //预提交
$this->assertEquals(empty($articleModel->sqlErrors()), true);
 //强制释放实例，做断开当前PDO连接
 //发现只有断开原始xa session PDO连接，新session才能恢复使用xa commit xid 或者 xa rollback xid
\PhpShardingPdo\Core\ShardingPdoContext::contextFreed();
  
$xid = '213123123213';
$xid .= '_phpshardingpdo2';
$articleModel = new \PhpShardingPdo\Test\Model\ArticleXaModel();
$res = $articleModel->where(['user_id' => 1, 'cate_id' => 1])->recover();  //获取recover xa list
$this->assertEquals(!empty($res['list']), true);
$isset = false;
foreach ($res['list'] as $item) {
    if ($item['data'] == $xid) {
        $isset = true;
    }
}
$this->assertEquals($isset, true);
$articleModel->setXid($xid);
$res = $articleModel->commit();
$this->assertEquals($res, true);
$this->assertEquals(empty($articleModel->sqlErrors()), true);

```
# 案例
https://www.what.pub/

# License
[Apache-2.0](https://github.com/1107012776/PHP-Sharding-PDO/blob/master/LICENSE)

# 更多请关注本人的博客
https://www.developzhe.com

# 联系我 (Contact WeChat)

<img src="https://www.developzhe.com/upload/dae99d4a-9639-4939-bd0d-16dcbb2d8490.png" width="200" height="200" alt="微信"/><br/>

# Page visitor counter
![visitor counter](https://profile-counter.glitch.me/1107012776_PHP-Sharding-PDO/count.svg)


<a href="https://info.flagcounter.com/qIrY"><img src="https://s11.flagcounter.com/count2/qIrY/bg_FFFFFF/txt_000000/border_CCCCCC/columns_2/maxflags_10/viewers_0/labels_0/pageviews_0/flags_0/percent_0/" alt="Flag Counter" border="0"></a>
