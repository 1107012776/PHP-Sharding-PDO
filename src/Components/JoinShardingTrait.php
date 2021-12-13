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


use PhpShardingPdo\Core\JoinTablePlan;


/**
 * Join sharding
 * Trait JoinShardingTrait
 * @package PhpShardingPdo\Components
 */
trait JoinShardingTrait
{
    private $_table_alias = ''; //表别名

    /**
     * @var array $_joinTablePlanObjArr
     */
    private $_joinTablePlanObjArr = []; //join的JoinTablePlan对象数组


    /**
     * @return JoinTablePlan
     * @var $condition
     */
    public function createJoinTablePlan($condition)
    {
        $obj = new JoinTablePlan();
        $execTableName = $this->_getQpTableName();
        if(empty($execTableName)){
            return null;
        }
        $obj->setTableNameAlias($this->getTableAlias());
        $obj->setTableName($execTableName);
        $obj->setJoinCondition($condition); //on条件
        return $obj;
    }


    /**
     * 获取表别名
     * @return string
     */
    public function getTableAlias()
    {
        return $this->_table_alias;
    }

    /**
     * 获取具体查询sql 可能是join on的查询形式
     * @param $execTableName
     * @return string
     */
    protected function getExecSelectString($execTableName)
    {
        $sqlStr = $this->getExecStringTableAlias($execTableName);
        if (empty($this->_joinTablePlanObjArr)) {
            return $sqlStr;
        }
        $onStr = '';
        /**
         * @var JoinTablePlan $entityObj
         */
        foreach ($this->_joinTablePlanObjArr as $entityObj) {
            $sqlStr .= $entityObj->getJoinTypeText() . $entityObj->getTableName() . ' as ' . $entityObj->getTableNameAlias();
            $onStr .= $entityObj->getOnConditionStr();
        }
        if (empty($onStr)) {
            return $sqlStr;
        }
        return $sqlStr . ' on ' . substr($onStr, 5, strlen($onStr) - 5);
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
    public function setTableNameAlias($tableAlias = '')
    {
        $this->_table_alias = $tableAlias;
        return $this;
    }


    /**
     * 添加JoinTablePlan对象
     * @return $this
     * @var JoinTablePlan $obj
     */
    public function addJoinPlanObj(JoinTablePlan $obj)
    {
        if(empty($obj)){
            return $this;  //为空则添加无效
        }
        if (in_array($obj, $this->_joinTablePlanObjArr)) {
            return $this;
        }
        $this->_joinTablePlanObjArr[] = $obj;
        return $this;
    }

    public function getJoinConditionStr()
    {
        if (empty($this->_join_condition)) {
            return '';
        }
        if (!empty($this->_join_condition_str)) {
            return $this->_join_condition_str;
        }
        foreach ($this->_join_condition as $key => $val) {  //join on 的形式
            $this->_bindOn($key, $val);
        }
        return $this->_join_condition_str;
    }


}