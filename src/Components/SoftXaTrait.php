<?php
/**
 * PhpShardingPdo  file.
 * @author linyushan <1107012776@qq.com>
 * @link https://www.developzhe.com/
 * @package https://github.com/1107012776/PHP-Sharding-PDO
 * @copyright Copyright &copy; 2019-2021
 * @license https://github.com/1107012776/PHP-Sharding-PDO/blob/master/LICENSE
 */

namespace PhpShardingPdo\Components;
/**
 * Xa事务
 * Class SoftXaTrait
 * @package PhpShardingPdo\Components
 */
trait SoftXaTrait
{
    /**
     * 获取xid
     */
    public function getXid(){

    }

    /**
     * xa将事务置于IDLE状态，表示事务内的SQL操作完成
     */
    public function endXa(){

    }

    /**
     * xa预提交
     */
    public function prepareXa(){

    }

    /**
     * 查看MySQL中存在的PREPARED状态的xa事务
     */
    public function recover(){

    }
}
