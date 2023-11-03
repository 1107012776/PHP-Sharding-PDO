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
use PhpShardingPdo\Components\JoinParsingTrait;

/**
 * join 计划
 * Class JoinTablePlan
 * @package PhpShardingPdo\Core
 */
class JoinTablePlan
{
    use JoinParsingTrait;
    private $tableName = '';  //数据库表名
    private $tableNameAlias = '';  //数据表别名
    private $joinType = 0;  //join类型


    /**
     * @return int
     */
    public function getJoinType()
    {
        return $this->joinType;
    }

    /**
     * @param int $joinType
     */
    public function setJoinType($joinType)
    {
        $this->joinType = $joinType;
    }


    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * @return string
     */
    public function getTableNameAlias()
    {
        return $this->tableNameAlias;
    }

    /**
     * @param string $tableNameAlias
     */
    public function setTableNameAlias($tableNameAlias)
    {
        $this->tableNameAlias = $tableNameAlias;
    }

    public function getJoinTypeText()
    {
        switch ($this->joinType) {
            case ShardingConst::INNER_JOIN:
                return ' inner join ';
            case ShardingConst::LEFT_JOIN:
                return ' left join ';
            case ShardingConst::RIGHT_JOIN:
                return ' right join ';
        }
    }

    public function getOnConditionStr()
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
        if (!empty($this->_join_condition_str)) {
            return $this->_join_condition_str;
        }
        return '';
    }

}