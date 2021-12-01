<?php
/**
 * PhpShardingPdo  file.
 * @author linyushan  <1107012776@qq.com>
 * @link https://www.developzhe.com/
 * @package https://github.com/1107012776/PHP-Sharding-PDO
 * @copyright Copyright &copy; 2019-2021
 * @license https://github.com/1107012776/PHP-Sharding-PDO/blob/master/LICENSE
 */

namespace PhpShardingPdo\Common;

class ShardingConst
{
    //join 类型
    const INNER_JOIN = 1;  //内连接
    const LEFT_JOIN = 2;   //左连接
    const RIGHT_JOIN = 3;  //右连接


    //and string
    const AND = ' and ';
}