<?php
/**
 * Created by PhpStorm.
 * User: 11070
 * Date: 2020/3/29
 * Time: 21:28
 */

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
