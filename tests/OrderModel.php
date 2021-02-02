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