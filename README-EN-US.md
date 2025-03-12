# PHP-Sharding-PDO
A PHP and MySQL database sharding middleware that depends on PDO, supporting coroutines.

[Table of Contents](#PHP-Sharding-PDO)
- [I. Installation](#Installation)
- [II. Description](#Description)
- [III. Notes](#Notes)
- [IV. Unit Testing](#Unit-Testing)
- [V. Examples](#Examples)
    - [1. Basic Sharding Rule Configuration](#1-Basic-Sharding-Rule-Configuration)
    - [2. Model Creation](#2-Model-Creation)
    - [3. Basic Usage](#3-Basic-Usage)
        - [Query](#Query)
        - [Insert](#Insert)
        - [Update](#Update)
        - [Delete](#Delete)
    - [4. Join Usage](#4-Join-Usage)
    - [5. XA Usage](#5-XA-Usage)
- [VI. Cases](#Cases)

# Requirements
- PHP >= 7.2
- Swoole >= 4.1.0 (for coroutine environment)

# Installation
You can install the package via composer:
```bash
composer require lys/php-sharding-pdo
```

# Description
###### (1) Coroutine support is available, but you must enable \Swoole\Runtime::enableCoroutine(); in the main process
###### (2) Supports custom sharding rules and complex sharding implementations, based on WHERE conditions or INSERT data
###### (3) INSERT operations matching multiple databases or tables will return false; ensure your INSERT rule matches only one database and one table
###### (4) Due to MySQL's lack of scrolling cursor support:
> Pagination across multiple databases and tables will be slower. To optimize, use WHERE conditions to filter unnecessary result sets, e.g., WHERE id >= 1000000 or WHERE id <= 1000000

> Pagination within a single database and table maintains normal speed

###### (5) When handling transactions across two or more databases simultaneously, there's a risk of data inconsistency due to transaction commit failures (2PC). It's recommended to keep related data in the same database or use soft transactions for eventual consistency.

# Notes
###### (1) Coroutine mode requires enabling the following in the main process to prevent deadlocks:
```bash
\Swoole\Runtime::enableCoroutine(); 
```

###### (2) PDO persistent connections cannot be used with coroutines. Under high concurrency, you may encounter:
```bash
PHP Fatal error:  Uncaught Swoole\Error: Socket#30 has already been bound to another coroutine#2,
reading of the same socket in coroutine#4 at the same time is not allowed
```

###### (3) REPLACE INTO with auto-increment keys may return false or deadlock under high concurrency. For high-concurrency projects, use distributed primary key solutions like the Snowflake algorithm.

###### (4) For non-coroutine, memory-resident frameworks like Workerman, use the following code to release context:
```php
<?php
// Context should be reset after each request as values have time sensitivity (e.g., PDO instance timeout)
\PhpShardingPdo\Core\ShardingPdoContext::contextFreed();  
```

###### (5) Please use the latest version

# Unit Testing
```bash
git clone https://github.com/1107012776/PHP-Sharding-PDO.git
cd PHP-Sharding-PDO
composer install
```

### (1) First configure tests/Config/.env for test database connection:
> .env file
```php
[database]
host=localhost
username=root
password=testpassword
[shardingPdo]
#Enabling SQL logging affects performance
sqlLogOpen=false
sqlLogPath=sql.sql
```

### (2) Then run the following scripts:
> Non-coroutine
```bash
php vendor/bin/phpunit tests/IntegrationTest.php --filter testExecStart
```
> Coroutine
```bash
php vendor/bin/phpunit tests/IntegrationCoroutineTest.php --filter testExecStart
```

# For database sharding knowledge, refer to:
`https://blog.csdn.net/weixin_38642740/article/details/81448762`

# Examples
> See the tests directory for details

### 1. Basic Sharding Rule Configuration
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

/**
 * Sharding Configuration Example
 */
class ShardingInitConfig4 extends ShardingInitConfigInter
{
    /**
     * Get the data source map instances for database sharding
     * @return array
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
        // Article table configuration
        $tableRule = new ShardingTableRuleConfig();
        $tableRule->setLogicTable('article');
        $tableRule->setDatabaseShardingStrategyConfig(
            new InlineShardingStrategyConfiguration('db', [
                'operator' => '%',
                'data' => [    // Field and right operand for calculation
                    'user_id',  // Field name
                    4
                ]]));
        $tableRule->setTableShardingStrategyConfig(
            new InlineShardingStrategyConfiguration('article_', [   // Built-in modulo (%) sharding rule
                'operator' => '%',
                'data' => [    // Field and right operand for calculation
                    'cate_id',  // Field name
                    2
                ]]));
        $shardingRuleConfig = new ShardingRuleConfiguration();
        $shardingRuleConfig->add($tableRule);  // Table 1 rule

        // Account table configuration
        $tableRule = new ShardingTableRuleConfig();
        $tableRule->setLogicTable('account');
        $tableRule->setDatabaseShardingStrategyConfig(
            new InlineShardingStrategyConfiguration('db', 
            [  // Empty array since we're using custom sharding function
            ], function ($condition) {  // Custom sharding rule using anonymous function
                if (isset($condition['username']) && !is_array($condition['username'])) {
                    return crc32($condition['username']) % 4;
                }
                return null;
            }));
        $tableRule->setTableShardingStrategyConfig(
            new InlineShardingStrategyConfiguration('account_', [], function ($condition) {
                return 0;
            }));
        $shardingRuleConfig->add($tableRule);  // Table 2 rule

        // User table configuration
        $tableRule = new ShardingTableRuleConfig();
        $tableRule->setLogicTable('user');
        $tableRule->setDatabaseShardingStrategyConfig(
            new InlineShardingStrategyConfiguration('db', [], function ($condition) {
                if (isset($condition['id']) && !is_array($condition['id'])) {
                    return $condition['id'] % 4;
                }
                return null;
            }));
        $tableRule->setTableShardingStrategyConfig(
            new InlineShardingStrategyConfiguration('user_', [], function ($condition) {
                return 0;
            }));
        $shardingRuleConfig->add($tableRule);  // Table 3 rule

        // Auto_distributed table configuration
        $tableRule = new ShardingTableRuleConfig();
        $tableRule->setLogicTable('auto_distributed');
        $tableRule->setDatabaseShardingStrategyConfig(
            new InlineShardingStrategyConfiguration('db', [], function ($condition) {
                if (isset($condition['stub']) && !is_array($condition['stub'])) {
                    return $condition['stub'] % 4;
                }
                return null;
            }));
        $tableRule->setTableShardingStrategyConfig(
            new InlineShardingStrategyConfiguration('auto_distributed', [], function ($condition) {
                return '';
            }));
        $shardingRuleConfig->add($tableRule);  // Table 4 rule

        // Category table configuration
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
        $shardingRuleConfig->add($tableRule);  // Table 5 rule

        return $shardingRuleConfig;
    }

    /**
     * Initialize the first database resource
     * @return PDO
     * @throws PDOException
     */
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
}
```

### 2. Model Creation
```php
<?php

namespace PhpShardingPdo\Test\Model;
use PhpShardingPdo\Components\SoftDeleteTrait;
use PhpShardingPdo\Core\Model;
use PhpShardingPdo\Test\ShardingInitConfig4;
Class ArticleModel extends Model
{
    use SoftDeleteTrait; //Soft delete requires this
    protected $tableName = 'article';
    protected $tableNameIndexConfig = [
        'index' => '0,1', //Table index, comma-separated
        //'range' => [1,2]  //Range
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
        'index' => '0', //Table index, comma-separated
        //'range' => [1,2]  //Range
    ];
}
```

### 3. Basic Usage
#### Query
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
var_dump($newObj === $model);  //Outputs false
//count query
$count = $model->renew()->count();
var_dump($count);
$count = $model->renew()->where(['id' => ['gt', 100000]])->count('id');   //Index coverage query
var_dump($count);
//in query
$list = $model->renew()->where(['id' => ['in', [1,2,3]]])->findAll();  
var_dump($list);
//not in query
$list = $model->renew()->where(['id' => ['notIn', [1,2,3]]])->findAll();  
var_dump($list);
//gt greater than, egt greater than or equal to, lt less than, elt less than or equal to
$list = $model->renew()->where(['id' => ['gt', 1]])->findAll(); 
var_dump($list);
//between two values, equivalent to id >= 100 and id <= 10000
$list = $model->renew()->where(['id' => ['between', [100, 10000]]])->findAll();  
var_dump($list);
//Multiple conditions for the same field, equivalent to cate_id >= 1 and cate_id <= 4, same as between
$count = $model->renew()->where([
    'cate_id' => ['egt', 1]
])->where(['article_title' => '文章1'])
->where(['cate_id' => ['elt', 4]])
->count();
$this->assertEquals($count == 4, true);
//not between, not within two values, equivalent to id < 100 and id > 10000
$list = $model->renew()->where(['id' => ['notBetween', [100, 10000]]])->findAll();  
var_dump($list);
//neq not equal to, can be an array or a single value
$list = $model->renew()->where(['id' => ['neq', [1,2,3]]])->findAll();  
var_dump($list);
$list = $model->renew()->where(['id' => ['neq', 1]])->findAll();  
var_dump($list);
//like query
$list = $model->renew()->where(['article_title' => ['like','某网络科技%'],'type' => 1])->findAll();  
var_dump($list);
//not like query
$list = $model->renew()->where(['article_title' => ['notLike','某网络科技%'],'type' => 1])->findAll();  
var_dump($list);
//findInSet query
$count = $model->renew()->where([
    'cate_id' => ['findInSet', 1]
])->where(['article_title' => '文章1'])
->count();
$this->assertEquals($count == 2, true);
```

#### Insert
```php
<?php
$model = new \PhpShardingPdo\Test\Model\ArticleModel();
$user = new \PhpShardingPdo\Test\Model\UserModel();
$model->startTrans(); 
$model->startTrans(); //Nested transactions
$res = $user->renew()->insert(['id' => 2,  'create_time' => date('Y-m-d H:i:s')]);
$this->assertEquals(!empty($res), true);
$res = $model->renew()->insert(['user_id' => $user->getLastInsertId(), 'article_title' => '某网络科技', 'create_time' => date('Y-m-d H:i:s')]);
$this->assertEquals(!empty($res), true);
$user->commit();
$user->commit();
```

#### Update
```php
<?php
$model = new \PhpShardingPdo\Test\Model\ArticleModel();
$model->startTrans(); 
$res = $model->renew()->where(['id' => 3])->update(['update_time' => date('Y-m-d H:i:s')]);
var_dump($res);  //Number of affected rows
//decr decrement
$res = $model->renew()->where(['id' => 3])->decr('is_choice', 1);
var_dump($res); //Number of affected rows
//incr increment
$res = $model->renew()->where(['id' => 3])->incr('is_choice', 1);
var_dump($res); //Number of affected rows
$model->commit();
```

#### Delete
```php
<?php
$model = new \PhpShardingPdo\Test\Model\ArticleModel();
$model->startTrans();
$res = $model->renew()->where(['id' => 9])->delete();
var_dump($res);  //Number of affected rows
$model->commit();
//Force physical deletion (if soft deletion is set)
$model->startTrans();
$res = $model->renew()->where(['id' => 10])->delete(true);
var_dump($res);  //Number of affected rows
$model->commit();

```

### 4. Join Usage
> Join only supports the same database, not cross-database
```php
<?php
namespace PhpShardingPdo\Test;
ini_set("display_errors", "On");

error_reporting(E_ALL); //Show all error information
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

ConfigEnv::loadFile(dirname(__FILE__) . '/Config/.env');  //Load configuration

/**
* @method assertEquals($a, $b)
*/
class IntegrationTest extends TestCase
{
    /**
     * Join query test
     * php vendor/bin/phpunit tests/IntegrationTest.php --filter testJoin
     */
    public function testJoin()
    {
        $articleModel = new \PhpShardingPdo\Test\Model\ArticleModel();
        $articleModel->alias('ar');
        $cateModel = new \PhpShardingPdo\Test\Model\CategoryModel();
        $cateModel1 = clone $cateModel;
        //Input WHERE conditions are used to query specific table names, used for subsequent JOIN
        $plan = $cateModel1->alias('cate')->where([
            'id' => 1 
            ])->createJoinTablePlan([
            'cate.id' => $articleModel->getFieldAlias('cate_id') //ON condition for JOIN
        ]);
        //Plan failure means that the subsequent JOIN table name cannot be found, as it is determined by the sharding rule and input WHERE conditions
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
        //Perform three-table JOIN query
        $userModel = new UserModel();  //User table
        $articleModel1 = clone $articleModel; //Article table
        $cateModel1 = clone $cateModel;  //Category table
        $userModel1 = clone $userModel;  //User table
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
    
    public function testGroupByJoin()
    {
        $articleModel = new \PhpShardingPdo\Test\Model\ArticleModel();
        $articleModel->alias('ar');
        $cateModel = new \PhpShardingPdo\Test\Model\CategoryModel();
        $cateModel->alias('cate');
        $userModel = new UserModel();  //User table
        $userModel->alias('user');
        $articleModel1 = clone $articleModel;
        $cateModel1 = clone $cateModel;
        $userModel1 = clone $userModel;
        $user_id = 1;
        $catePlan = $cateModel1->alias('cate')->where(['id' => 1])->createJoinTablePlan([
            'cate.id' => $articleModel1->getFieldAlias('cate_id')
        ]);
        $articlePlan = $articleModel1->alias('ar')->where(['cate_id' => 1])->createJoinTablePlan([
            'user.id' => $articleModel1->getFieldAlias('user_id')
        ]);
        $this->assertEquals(!empty($catePlan), true);
        $this->assertEquals(!empty($articlePlan), true);
        $list = $userModel1->field(['user.id', 'ar.cate_id as a', 'cate.id as b'])
            ->innerJoin($catePlan)
            ->innerJoin($articlePlan)
            ->where([
                'id' => $user_id
            ])->order('user.id desc')->group('user.id')->findAll();
        $this->assertEquals(isset($list[0]['id']) && $list[0]['id'] == 1, true);
        $this->assertEquals(isset($list[0]['a']) && $list[0]['a'] == 1, true);
        $this->assertEquals(isset($list[0]['b']) && $list[0]['b'] == 1, true);
        $this->assertEquals(empty($userModel1->sqlErrors()), true);
        $articleModel1 = clone $articleModel;
        $cateModel1 = clone $cateModel;
        $userModel1 = clone $userModel;
        $catePlan = $cateModel1->alias('cate')->where(['id' => 1])->createJoinTablePlan([
            'cate.id' => $articleModel1->getFieldAlias('cate_id')
        ]);
        $articlePlan = $articleModel1->alias('ar')->where(['cate_id' => 1])->createJoinTablePlan([
            'user.id' => $articleModel1->getFieldAlias('user_id')
        ]);
        $this->assertEquals(!empty($catePlan), true);
        $this->assertEquals(!empty($articlePlan), true);
        $list = $userModel1->field(['user.id', 'ar.cate_id as a', 'cate.id as b'])
            ->innerJoin($catePlan)
            ->innerJoin($articlePlan)
            ->where([
                'id' => $user_id
            ])->joinWhereCondition([  //There's a risk of injection as it doesn't use placeholders, ensure input values are safe
                $userModel1->getFieldAlias('id') => ['neq', 'ar.cate_id'] //Pass values like ['user.id' => 'ar.cate_id']
            ])->order('user.id desc')->group('user.id')->findAll();
        $this->assertEquals(empty($list), true);
        $this->assertEquals(empty($userModel1->sqlErrors()), true);
    }
}
```

### 5. XA Usage
```php
<?php

$articleModel = new \PhpShardingPdo\Test\Model\ArticleXaModel();
$data = [
    'article_descript' => 'xa test data article_descript',
    'article_img' => '/upload/2021110816311943244.jpg',
    'article_keyword' => 'xa test data article_keyword',
    'article_title' => $this->article_title2,
    'author' => '学者',
    'cate_id' => 3,
    'content' => '<p>xa test data</p><br/>',
    'content_md' => 'xa test data',
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
* XA transaction Recover test (see tests directory for specific examples)
*/
$xid = '213123123213';
$data = [
    'article_descript' => 'xa test data article_descript',
    'article_img' => '/upload/2021110816311943244.jpg',
    'article_keyword' => 'xa test data article_keyword',
    'article_title' => $this->article_title2,
    'author' => '学者',
    'cate_id' => 1,
    'content' => '<p>xa test data</p><br/>',
    'content_md' => 'xa test data',
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
$articleModel->prepareXa(); //Pre-commit
$this->assertEquals(empty($articleModel->sqlErrors()), true);
 //Force release instance to disconnect the current PDO connection
 //Discover that only by releasing the original XA session PDO connection, the new session can recover and use XA commit xid or XA rollback xid
\PhpShardingPdo\Core\ShardingPdoContext::contextFreed();
  
$xid = '213123123213';
$xid .= '_phpshardingpdo2';
$articleModel = new \PhpShardingPdo\Test\Model\ArticleXaModel();
$res = $articleModel->where(['user_id' => 1, 'cate_id' => 1])->recover();  //Get recover XA list
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

# Cases
https://www.what.pub/

# License
[Apache-2.0](https://github.com/1107012776/PHP-Sharding-PDO/blob/master/LICENSE)

# More information
If you find this helpful or interesting, please give it a star

# Contribution
1. Fork, modify, and submit a merge request
2. Welcome to discuss better ideas or methods

# Contact Me (Contact WeChat)
<img src="https://www.developzhe.com/upload/dae99d4a-9639-4939-bd0d-16dcbb2d8490.png" width="200" height="200" alt="微信"/><br/>
> Add me on WeChat if needed

# Page visitor counter
![visitor counter](https://profile-counter.glitch.me/1107012776_PHP-Sharding-PDO/count.svg)
