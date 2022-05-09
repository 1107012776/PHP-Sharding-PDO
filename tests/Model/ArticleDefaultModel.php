<?php

namespace PhpShardingPdo\Test\Model;


use PhpShardingPdo\Components\SoftDeleteTrait;
use PhpShardingPdo\Core\Model;
use PhpShardingPdo\Test\ShardingInitConfig4;
use PhpShardingPdo\Test\ShardingInitConfigDefault;

Class ArticleDefaultModel extends Model
{
    use SoftDeleteTrait;
    protected $tableName = 'article';

    protected $shardingInitConfigClass = ShardingInitConfigDefault::class;


}
