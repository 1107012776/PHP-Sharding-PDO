<?php
/**
 * phpShardingPdo  file.
 * @author linyushan  <1107012776@qq.com>
 * @link https://www.developzhe.com/
 * @package https://github.com/1107012776/PHP-Sharding-PDO
 * @copyright Copyright &copy; 2019-2021
 * @license https://github.com/1107012776/PHP-Sharding-PDO/blob/master/LICENSE
 */
namespace PhpShardingPdo\Test;

use PhpShardingPdo\Core\Model;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/25
 * Time: 20:12
 */
class UserModel extends Model
{
    protected $tableName = 't_user';
    protected $tableNameIndexConfig = [
        'index' => '0,1', //分表索引 index ,号分割
        //'range' => [1,2]  //范围
    ];
    protected $shardingInitConfigClass = ShardingInitConfig::class;

}