<?php

/**
 * PhpShardingPdo  file.
 * @author linyushan  <1107012776@qq.com>
 * @link https://www.developzhe.com/
 * @package https://github.com/1107012776/PHP-Sharding-PDO
 * @copyright Copyright &copy; 2019-2021
 * @license https://github.com/1107012776/PHP-Sharding-PDO/blob/master/LICENSE
 */

namespace PhpShardingPdo\Core;

use PhpShardingPdo\Common\ShardingConst;

/**
 * join 实体类
 * Class JoinTableEntity
 * @package PhpShardingPdo\Core
 */
class JoinTableEntity{
    private $tableName = '';  //数据库表名
    private $tableNameAlias = '';  //数据表别名
    private $joinType = 0;  //join类型
    private $_on_condition = []; //on条件
    private $_on_condition_str = ''; //on条件字符串

    /**
     * @return int
     */
    public function getJoinType(): int
    {
        return $this->joinType;
    }

    /**
     * @param int $joinType
     */
    public function setJoinType(int $joinType): void
    {
        $this->joinType = $joinType;
    }


    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     */
    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    /**
     * @return string
     */
    public function getTableNameAlias(): string
    {
        return $this->tableNameAlias;
    }

    /**
     * @param string $tableNameAlias
     */
    public function setTableNameAlias(string $tableNameAlias): void
    {
        $this->tableNameAlias = $tableNameAlias;
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


    public function getJoinTypeText(){
        switch ($this->joinType){
            case ShardingConst::INNER_JOIN:
                return ' inner join ';
            case ShardingConst::LEFT_JOIN:
                return ' left join ';
            case ShardingConst::RIGHT_JOIN:
                return ' right join ';
        }
    }

    public function getOnConditionStr(){
        if(!empty($this->_on_condition_str)){
            return ' on '.$this->_on_condition_str.' ';
        }
        foreach ($this->_on_condition as $key => $val) {  //join on 的形式
            $this->_bindOn($key, $val);
        }
        return ' on '.$this->_on_condition_str.' ';
    }

}