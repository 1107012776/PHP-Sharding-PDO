<?php
/**
 * Created by PhpStorm.
 * User: 11070
 * Date: 2021/9/20
 * Time: 12:47
 */
namespace PhpShardingPdo\Test\Model;


use PhpShardingPdo\Core\Model;
use PhpShardingPdo\Test\ShardingInitConfig4;

class AccountModel extends Model {
    protected $tableName = 'account';
    protected $tableNameIndexConfig = [
        'index' => '0', //分表索引 index ,号分割
        //'range' => [1,2]  //范围
    ];
    protected $shardingInitConfigClass = ShardingInitConfig4::class;


}