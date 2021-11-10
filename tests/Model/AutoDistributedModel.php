<?php
namespace PhpShardingPdo\Test\Model;


use PhpShardingPdo\Core\Model;
use PhpShardingPdo\Test\ShardingInitConfig4;


/**
 * Class AutoDistributedModel
 * @package PhpShardingPdo\Test
 *
 *
 * CREATE TABLE `auto_distributed` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `stub` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
    PRIMARY KEY (`id`),
    UNIQUE KEY `stub` (`stub`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 *
 *例子:
    $model = new AutoDistributedModel();
    $data = ['stub' => 'b'];
    $res =$model->replaceInto($data);
    var_dump($res,$model->getLastInsertId());
 */
Class AutoDistributedModel extends Model
{
    protected $tableName = 'auto_distributed';

    protected $shardingInitConfigClass = ShardingInitConfig4::class;

}