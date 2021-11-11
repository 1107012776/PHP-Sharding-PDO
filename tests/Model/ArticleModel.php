<?php

namespace PhpShardingPdo\Test\Model;


use PhpShardingPdo\Components\SoftDeleteTrait;
use PhpShardingPdo\Core\Model;
use PhpShardingPdo\Test\ShardingInitConfig4;

Class ArticleModel extends Model
{
    use SoftDeleteTrait;
    protected $tableName = 'article';
    protected $tableNameIndexConfig = [
        'index' => '0,1', //分表索引 index ,号分割
        //'range' => [1,2]  //范围
    ];
    protected $shardingInitConfigClass = ShardingInitConfig4::class;


}