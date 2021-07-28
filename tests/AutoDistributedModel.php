<?php
namespace PhpShardingPdo\Test;


use PhpShardingPdo\Core\Model;

Class AutoDistributedModel extends Model
{
    protected $tableName = 'auto_distributed';

    protected $shardingInitConfigClass = ShardingInitConfig2::class;

}