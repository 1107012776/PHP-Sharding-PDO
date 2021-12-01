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

    /**
     * @var array $_joinEntityObjArr
     */
    private $_joinEntityObjArr = []; //join的JoinTableEntity对象


    /**
     * @var $condition
     * @return JoinTableEntity
     */
    public function getJoinTableEntity($condition)
    {
        $obj = new JoinTableEntity();
        $obj->setTableNameAlias($this->getTableAlias());
        $obj->setTableName($this->_getQpTableName());
        $obj->setJoinCondition($condition); //on条件
        return $obj;
    }


    /**
     * 获取表别名
     * @return string
     */
    protected function getTableAlias()
    {
        return $this->_table_alias;
    }

    /**
     * 获取具体查询sql 可能是join on的查询形式
     * @param $execTableName
     * @return string
     */
    protected function getExecSelectString($execTableName){
        $sqlStr = $this->getExecStringTableAlias($execTableName);
        if(empty($this->_joinEntityObjArr)){
            return $sqlStr;
        }

        /**
         * @var JoinTableEntity $entityObj
         */
        foreach ($this->_joinEntityObjArr as $entityObj){
            $sqlStr .= $entityObj->getJoinTypeText().$entityObj->getTableName().' as '.$entityObj->getTableNameAlias().$entityObj->getOnConditionStr();
        }
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
     * @return $this
     * @var JoinTableEntity $obj
     */
    public function addJoinEntityObj(JoinTableEntity $obj)
    {
        $this->_joinEntityObjArr[] = $obj;
        $this->_joinEntityObjArr = array_unique($this->_joinEntityObjArr);  //去重
        return $this;
    }

    public function getOnConditionStr(){
        if(empty($this->_join_condition)){
            return '';
        }
        if(!empty($this->_join_condition_str)){
            return ' and '.$this->_join_condition_str.' ';
        }
        foreach ($this->_join_condition as $key => $val) {  //join on 的形式
            $this->_bindOn($key, $val);
        }
        return ' and '.$this->_join_condition_str.' ';
    }


}