<?php
/**
 * PhpShardingPdo  file.
 * @author linyushan  <1107012776@qq.com>
 * @link https://www.developzhe.com/
 * @package https://github.com/1107012776/PHP-Sharding-PDO
 * @copyright Copyright &copy; 2019-2021
 * @license https://github.com/1107012776/PHP-Sharding-PDO/blob/master/LICENSE
 */

namespace PhpShardingPdo\Components;
/**
 * 自增、自减sharding
 * User: lys
 * Date: 2019/8/1
 * Time: 17:03
 * @var \PhpShardingPdo\Core\ShardingPdo $this
 */
trait IncrDecrShardingTrait
{
    private $_incrOrDecrColumnStr = '';

    /**
     * @param $field
     * @param int|float $number
     * @return bool
     */
    public function incr($field, $number)
    {
        $this->clearSqlErrors();
        if (empty($field) || empty($number) || (
              !is_float($number)
              && !is_int($number)
              && !is_double($number)
            )
        ) {
            return false;
        }
        $this->_pare();
        $this->_incrOrDecrColumnStr = $field . ' = ' . $field . ' + ' . $number;
        return $this->_updateSharding();
    }

    /**
     * @param $field
     * @param int|float $number
     * @return bool
     */
    public function decr($field, $number)
    {
        $this->clearSqlErrors();
        if (empty($field) || empty($number) || (
                !is_float($number)
                && !is_int($number)
                && !is_double($number)
            )
        ) {
            return false;
        }
        $this->_pare();
        $this->_incrOrDecrColumnStr = $field . ' = ' . $field . ' - ' . $number;
        return $this->_updateSharding();
    }
}
