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
/**
 * 解析
 */
trait ParsingTrait
{

    private $_bind_index = 0;

    /**
     * 参数解析绑定
     * @param $key
     * @param $val
     */
    private function _bind($key, $val)
    {
        $this->_bind_index++;  //自加
        $zwKey = ':' . str_replace('.','_',$key) . '_' . $this->_bind_index;  //占位符
        if (is_array($val)) {
            switch ($val[0]) {
                case 'neq':
                    if (!is_array($val[1])) {
                        $zwKey .= '_neq_0';
                        $this->_condition_str .= ShardingConst::AND . $key . ' != ' . $zwKey;
                        $this->_condition_bind[$zwKey] = $val[1];
                        break;
                    }
                    foreach ($val[1] as $k => $v) {   //多个不等于
                        $zwKeyNeq = $zwKey . '_neq_' . $k;
                        $this->_condition_str .= ShardingConst::AND . $key . ' != ' . $zwKeyNeq;
                        $this->_condition_bind[$zwKeyNeq] = $v;
                    }
                    break;
                case 'like':
                    $zwKey .= '_0';
                    $this->_condition_str .= ShardingConst::AND . $key . ' like ' . $zwKey;
                    $this->_condition_bind[$zwKey] = $val[1];
                    break;
                case 'gt':
                    $zwKey .= '_0';
                    $this->_condition_str .= ShardingConst::AND . $key . ' > ' . $zwKey;
                    $this->_condition_bind[$zwKey] = $val[1];
                    break;
                case 'egt':
                    $zwKey .= '_0';
                    $this->_condition_str .= ShardingConst::AND . $key . ' >= ' . $zwKey;
                    $this->_condition_bind[$zwKey] = $val[1];
                    break;
                case 'elt':
                    $zwKey .= '_0';
                    $this->_condition_str .= ShardingConst::AND . $key . ' <= ' . $zwKey;
                    $this->_condition_bind[$zwKey] = $val[1];
                    break;
                case 'lt':
                    $zwKey .= '_0';
                    $this->_condition_str .= ShardingConst::AND . $key . ' < ' . $zwKey;
                    $this->_condition_bind[$zwKey] = $val[1];
                    break;
                case 'in':
                    $zwKeyIn = '';
                    foreach ($val[1] as $k => $v) {
                        $zwKeyIn .= ',' . $zwKey . '_in_' . $k;
                        $this->_condition_bind[$zwKey . '_in_' . $k] = $v;
                    }
                    $zwKeyIn = trim($zwKeyIn, ',');
                    $this->_condition_str .= ShardingConst::AND . $key . ' in (' . $zwKeyIn . ')';
                    break;
                case 'notIn':
                    $zwKeyIn = '';
                    foreach ($val[1] as $k => $v) {
                        $zwKeyIn .= ',' . $zwKey . '_notIn_' . $k;
                        $this->_condition_bind[$zwKey . '_notIn_' . $k] = $v;
                    }
                    $zwKeyIn = trim($zwKeyIn, ',');
                    $this->_condition_str .= ShardingConst::AND . $key . ' not in (' . $zwKeyIn . ')';
                    break;
                case 'between':
                    $zwKeyMin = $zwKey . '_between_min_0';
                    $zwKeyMax = $zwKey . '_between_max_0';
                    $this->_condition_str .= ShardingConst::AND . $key . ' <= ' . $zwKeyMax;
                    $this->_condition_str .= ShardingConst::AND . $key . ' >= ' . $zwKeyMin;
                    $this->_condition_bind[$zwKeyMin] = min($val[1]);
                    $this->_condition_bind[$zwKeyMax] = max($val[1]);
                    break;
                case 'notBetween':
                    $zwKeyMin = $zwKey . '_notBetween_min_0';
                    $zwKeyMax = $zwKey . '_notBetween_max_0';
                    $this->_condition_str .= ShardingConst::AND . $key . ' > ' . $zwKeyMax;
                    $this->_condition_str .= ShardingConst::AND . $key . ' < ' . $zwKeyMin;
                    $this->_condition_bind[$zwKeyMin] = min($val[1]);
                    $this->_condition_bind[$zwKeyMax] = max($val[1]);
                    break;
                case 'is':
                    $zwKeyIs = $zwKey . '_is_0';
                    if ($val[1] === null) {
                        $this->_condition_str .= ShardingConst::AND . $key . ' is NULL';
                    } else {
                        $this->_condition_str .= ShardingConst::AND . $key . ' is ' . $zwKeyIs;
                        $this->_condition_bind[$zwKeyIs] = $val[1];
                    }
                    break;
                case 'isNot':
                    $zwKeyIs = $zwKey . '_isNot_0';
                    if ($val[1] === null) {
                        $this->_condition_str .= ShardingConst::AND . $key . ' is not NULL';
                    } else {
                        $this->_condition_str .= ShardingConst::AND . $key . ' is not ' . $zwKeyIs;
                        $this->_condition_bind[$zwKeyIs] = $val[1];
                    }
                    break;
                case 'findInSet':
                    $zwKeyIs = $zwKey . '_findInSet_0';
                    $this->_condition_str .= ShardingConst::AND . 'FIND_IN_SET(' . $zwKeyIs . ',' . $key . ')';
                    $this->_condition_bind[$zwKeyIs] = $val[1];
                    break;
                case 'more':
                    foreach ($val[1] as $subVal) {
                        $this->_bind($key, $subVal);
                    }
                    break;
            }
        } else {
            $zwKey .= '_0';
            $this->_condition_str .= ShardingConst::AND . $key . ' = ' . $zwKey;
            $this->_condition_bind[$zwKey] = $val;
        }
    }

    /**
     * 解析
     */
    private function _pare()
    {
        foreach ($this->_condition as $key => $val) {
            $alias = $this->getTableAlias();  //表别名
            if (!empty($alias)
                && strpos($key, $alias) === false
                && strpos($key, '.') === false  //有 “.”号说明可能是别的表的join条件
            ) {
                $this->_bind($alias . '.' . $key, $val);
            }else{
                $this->_bind($key, $val);
            }
        }
        if(empty($this->_condition_str)){
            $this->_condition_str = $this->getJoinConditionStr();
        }else{
            $this->_condition_str .= $this->getJoinConditionStr();
        }
        if (!empty($this->_condition_str)) {
            $this->_condition_str = ' where ' . substr($this->_condition_str, 5, strlen($this->_condition_str) - 5);
        }
        if (!empty($this->_order_str)) {
            $this->_order_str = str_replace(' order by ', '', $this->_order_str);
            $this->_order_str = ' order by ' . $this->_order_str;
        }
        if (!empty($this->_limit_str)) {
            $this->_limit_str = str_replace(' limit ', '', $this->_limit_str);
            $this->_limit_str = ' limit ' . $this->_limit_str;
        }
        if (!empty($this->_group_str)) {
            $this->_group_str = str_replace(' group by ', '', $this->_group_str);
            $this->_group_str = ' group by ' . $this->_group_str;
        }
        if (!empty($this->_field)) {
            $this->_field_str = implode(',', $this->_field);
        }
        $this->_current_exec_db = $this->_getQpDb();
        $this->_current_exec_table = $this->_getQpTableName();
    }

}