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

use PhpShardingPdo\Common\ShardingConst;
use PhpShardingPdo\Core\JoinTableEntity;
use PhpShardingPdo\Core\Model;

/**
 * Join sharding
 * Trait JoinShardingTrait
 * @package PhpShardingPdo\Components
 */
trait JoinShardingTrait
{
    private $_table_alias = ''; //表别名
    private $_join_type = 0; //join类型
    /**
     * @var array $_joinEntityObjArr
     */
    private $_joinEntityObjArr = []; //join的JoinTableEntity对象
    private $_on_condition = []; //on条件
    private $_on_condition_str = ''; //on条件字符串

    /**
     * @return int
     */
    public function getJoinType()
    {
        return $this->_join_type;
    }

    /**
     * @return int
     */
    public function setJoinType($type)
    {
        return $this->_join_type = $type;
    }

    /**
     * @return JoinTableEntity
     */
    public function getJoinTableEntity(){
        $obj = new JoinTableEntity();
        $obj->setTableNameAlias($this->getTableAlias());
        $obj->setTableName( $this->_getQpTableName());
        return $obj;
    }


    /**
     * 获取表别名
     * @param string $execTableName
     * @return string
     */
    protected function getTableAlias()
    {
        return $this->_table_alias;
    }

    /**
     * 获取别名字符串，用于替换原表名
     * @param string $execTableName
     * @return string
     */
    protected function getExecStringTableAlias($execTableName)
    {
        if (empty($this->_table_alias)) {
            return '`' . $execTableName . '`';
        }
        return '`' . $execTableName . '`' . ' as ' . $this->getTableAlias();
    }


    /**
     * 设置表别名
     * @param string $tableAlias
     * @return $this
     */
    public function setTableNameAs($tableAlias = '')
    {
        $this->_table_alias = $tableAlias;
        return $this;
    }

    /**
     * 添加JoinTableEntity对象
     */
    public function addJoinEntityObj(JoinTableEntity $obj)
    {
        $this->_joinEntityObjArr[] = $obj;
        return $this;
    }

    /**
     * 查询条件，on是join表之间的条件对条件，请勿直接传递值进来，这边不会做占位符处理
     * @param array $condition
     * @return $this
     */
    public function on($condition = [])
    {
        foreach ($condition as $key => $val) {
            if (!isset($this->_on_condition[$key])) {
                $this->_on_condition[$key] = $val;
                continue;
            }
            if (isset($this->_on_condition[$key][0])
                && $this->_on_condition[$key][0] == 'more'
            ) {
                array_push($this->_on_condition[$key][1], $val);
            } else {  //为兼容一个键值多个查询条件
                $old = $this->_on_condition[$key];
                $this->_on_condition[$key] = [
                    'more', [$old]
                ];
                array_push($this->_on_condition[$key][1], $val);
            }
        }
        return $this;
    }

    /*******************************************  join 解析 ********************************************************/
    /**
     * 参数解析绑定
     * @param $key
     * @param $val
     */
    private function _bindOn($key, $val)
    {
        if (is_array($val)) {
            switch ($val[0]) {
                case 'neq':
                    if (!is_array($val[1])) {
                        $this->_on_condition_str .= ' and ' . $key . ' != ' . $val[1];
                        break;
                    }
                    foreach ($val[1] as $k => $v) {   //多个不等于
                        $this->_on_condition_str .= ' and ' . $key . ' != ' . $v;
                    }
                    break;
                case 'gt':
                    $this->_on_condition_str .= ' and ' . $key . ' > ' . $val[1];
                    break;
                case 'egt':
                    $this->_on_condition_str .= ' and ' . $key . ' >= ' . $val[1];
                    break;
                case 'elt':
                    $this->_on_condition_str .= ' and ' . $key . ' <= ' . $val[1];
                    break;
                case 'lt':
                    $this->_on_condition_str .= ' and ' . $key . ' < ' . $val[1];
                    break;
                case 'in':
                    $zwKeyIn = '';
                    foreach ($val[1] as $k => $v) {
                        $zwKeyIn .= ',' . $v;
                    }
                    $zwKeyIn = trim($zwKeyIn, ',');
                    $this->_on_condition_str .= ' and ' . $key . ' in (' . $zwKeyIn . ')';
                    break;
                case 'notIn':
                    $zwKeyIn = '';
                    foreach ($val[1] as $k => $v) {
                        $zwKeyIn .= ',' . $v;
                    }
                    $zwKeyIn = trim($zwKeyIn, ',');
                    $this->_on_condition_str .= ' and ' . $key . ' not in (' . $zwKeyIn . ')';
                    break;
                case 'between':
                    $zwKeyMin = min($val[1]);
                    $zwKeyMax = max($val[1]);
                    $this->_on_condition_str .= ' and ' . $key . ' <= ' . $zwKeyMax;
                    $this->_on_condition_str .= ' and ' . $key . ' >= ' . $zwKeyMin;
                    break;
                case 'notBetween':
                    $zwKeyMin = min($val[1]);
                    $zwKeyMax = max($val[1]);
                    $this->_on_condition_str .= ' and ' . $key . ' > ' . $zwKeyMax;
                    $this->_on_condition_str .= ' and ' . $key . ' < ' . $zwKeyMin;
                    break;
                case 'is':
                    if ($val[1] === null) {
                        $this->_on_condition_str .= ' and ' . $key . ' is NULL';
                    } else {
                        $this->_on_condition_str .= ' and ' . $key . ' is ' . $val[1];
                    }
                    break;
                case 'isNot':
                    if ($val[1] === null) {
                        $this->_on_condition_str .= ' and ' . $key . ' is not NULL';
                    } else {
                        $this->_on_condition_str .= ' and ' . $key . ' is not ' . $val[1];
                    }
                    break;
                case 'findInSet':
                    $this->_on_condition_str .= ' and ' . 'FIND_IN_SET(' . $val[1] . ',' . $key . ')';
                    break;
                case 'more':
                    foreach ($val[1] as $subVal) {
                        $this->_bindOn($key, $subVal);
                    }
                    break;
            }
        } else {
            $this->_on_condition_str .= ' and ' . $key . ' = ' . $val;
        }
    }
}