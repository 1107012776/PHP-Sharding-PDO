<?php

namespace PhpShardingPdo\Test\Model;


use PhpShardingPdo\Components\SoftDeleteTrait;
use PhpShardingPdo\Components\SoftXaTrait;
use PhpShardingPdo\Core\Model;
use PhpShardingPdo\Test\ShardingInitConfig4;

Class ArticleXaModel extends Model
{
    use SoftDeleteTrait;
    use SoftXaTrait;
    protected $tableName = 'article';
    protected $tableNameIndexConfig = [
        'index' => '0,1', //分表索引 index ,号分割
        //'range' => [1,2]  //范围
    ];
    protected $shardingInitConfigClass = ShardingInitConfig4::class;


}
