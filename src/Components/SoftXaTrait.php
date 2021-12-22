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

use PhpShardingPdo\Core\ShardingPdo;

/**
 * Xa事务
 * Class SoftXaTrait
 * @package PhpShardingPdo\Components
 * @property ShardingPdo $dao
 */
trait SoftXaTrait
{
    /**
     * 获取xid
     */
    public function createXid()
    {
        $xid = date('YmdHis', time()) . uniqid('xid');
        return $xid;
    }

    /**
     * 设置xid
     */
    public function setXid($xid)
    {
        $this->dao->setXid($xid);
        return $xid;
    }


    /**
     * xa将事务置于IDLE状态，表示事务内的SQL操作完成
     */
    public function endXa()
    {
        return $this->dao->endXa();
    }

    /**
     * xa预提交
     */
    public function prepareXa()
    {
        return $this->dao->prepareXa();
    }

    /**
     * 查看MySQL中存在的PREPARED状态的xa事务
     */
    public function recover()
    {
        return $this->dao->recover();
    }
}
