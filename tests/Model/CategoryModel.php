<?php
namespace PhpShardingPdo\Test\Model;


use PhpShardingPdo\Core\Model;
use PhpShardingPdo\Test\ShardingInitConfig4;

Class CategoryModel extends Model
{
    protected $tableName = 'category';

    protected $shardingInitConfigClass = ShardingInitConfig4::class;

}