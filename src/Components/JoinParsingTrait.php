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
use PhpShardingPdo\Common\ShardingConst;

trait JoinParsingTrait{
    private $_join_condition = []; //on条件
    private $_join_condition_str = ''; //on条件字符串

    /**
     * 查询条件，on是join表之间的条件对条件，请勿直接传递值进来，这边不会做占位符处理
     * @param array $condition
     * @return $this
     */
    public function setJoinCondition($condition = [])
    {
        foreach ($condition as $key => $val) {
            if (!isset($this->_join_condition[$key])) {
                $this->_join_condition[$key] = $val;
                continue;
            }
            if (isset($this->_join_condition[$key][0])
                && $this->_join_condition[$key][0] == 'more'
            ) {
                array_push($this->_join_condition[$key][1], $val);
            } else {  //为兼容一个键值多个查询条件
                $old = $this->_join_condition[$key];
                $this->_join_condition[$key] = [
                    'more', [$old]
                ];
                array_push($this->_join_condition[$key][1], $val);
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
                        $this->_join_condition_str .= ShardingConst::AND . $key . ' != ' . $val[1];
                        break;
                    }
                    foreach ($val[1] as $k => $v) {   //多个不等于
                        $this->_join_condition_str .= ShardingConst::AND . $key . ' != ' . $v;
                    }
                    break;
                case 'gt':
                    $this->_join_condition_str .= ShardingConst::AND . $key . ' > ' . $val[1];
                    break;
                case 'egt':
                    $this->_join_condition_str .= ShardingConst::AND . $key . ' >= ' . $val[1];
                    break;
                case 'elt':
                    $this->_join_condition_str .= ShardingConst::AND . $key . ' <= ' . $val[1];
                    break;
                case 'lt':
                    $this->_join_condition_str .= ShardingConst::AND . $key . ' < ' . $val[1];
                    break;
                case 'in':
                    $zwKeyIn = '';
                    foreach ($val[1] as $k => $v) {
                        $zwKeyIn .= ',' . $v;
                    }
                    $zwKeyIn = trim($zwKeyIn, ',');
                    $this->_join_condition_str .= ShardingConst::AND . $key . ' in (' . $zwKeyIn . ')';
                    break;
                case 'notIn':
                    $zwKeyIn = '';
                    foreach ($val[1] as $k => $v) {
                        $zwKeyIn .= ',' . $v;
                    }
                    $zwKeyIn = trim($zwKeyIn, ',');
                    $this->_join_condition_str .= ShardingConst::AND . $key . ' not in (' . $zwKeyIn . ')';
                    break;
                case 'between':
                    $zwKeyMin = min($val[1]);
                    $zwKeyMax = max($val[1]);
                    $this->_join_condition_str .= ShardingConst::AND . $key . ' <= ' . $zwKeyMax;
                    $this->_join_condition_str .= ShardingConst::AND . $key . ' >= ' . $zwKeyMin;
                    break;
                case 'notBetween':
                    $zwKeyMin = min($val[1]);
                    $zwKeyMax = max($val[1]);
                    $this->_join_condition_str .= ShardingConst::AND . $key . ' > ' . $zwKeyMax;
                    $this->_join_condition_str .= ShardingConst::AND . $key . ' < ' . $zwKeyMin;
                    break;
                case 'is':
                    if ($val[1] === null) {
                        $this->_join_condition_str .= ShardingConst::AND . $key . ' is NULL';
                    } else {
                        $this->_join_condition_str .= ShardingConst::AND . $key . ' is ' . $val[1];
                    }
                    break;
                case 'isNot':
                    if ($val[1] === null) {
                        $this->_join_condition_str .= ShardingConst::AND . $key . ' is not NULL';
                    } else {
                        $this->_join_condition_str .= ShardingConst::AND . $key . ' is not ' . $val[1];
                    }
                    break;
                case 'findInSet':
                    $this->_join_condition_str .= ShardingConst::AND . 'FIND_IN_SET(' . $val[1] . ',' . $key . ')';
                    break;
                case 'more':
                    foreach ($val[1] as $subVal) {
                        $this->_bindOn($key, $subVal);
                    }
                    break;
            }
        } else {
            $this->_join_condition_str .= ShardingConst::AND . $key . ' = ' . $val;
        }
    }

}