<?php

namespace PhpShardingPdo\Test\Model;


use PhpShardingPdo\Core\Model;
use PhpShardingPdo\Test\ShardingInitConfigOneDatabase;

Class CategoryOneModel extends Model
{
    protected $tableName = 'category';

    protected $shardingInitConfigClass = ShardingInitConfigOneDatabase::class;

}
