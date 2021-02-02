<?php

namespace PhpShardingPdo\Core;
/**
 * 分库分表规则类
 * User: lys
 * Date: 2019/7/24
 * Time: 11:56
 */
class ShardingTableRuleConfig
{
    private $_tableName = '';
    private $_databaseShardingStrategy = null;
    private $_tableShardingStrategy = null;

    /**
     * 设置逻辑表名
     * @param string $tableName //需要分表的名称
     */
    public function setLogicTable($tableName = '')
    {
        $this->_tableName = $tableName;
    }


    /**
     * 配置分库
     * @param InlineShardingStrategyConfiguration $rule
     */
    public function setDatabaseShardingStrategyConfig(InlineShardingStrategyConfiguration $rule)
    {
        $this->_databaseShardingStrategy = $rule;
    }

    /**
     * 分表策略
     * @param InlineShardingStrategyConfiguration $rule
     */
    public function setTableShardingStrategyConfig(InlineShardingStrategyConfiguration $rule)
    {
        $this->_tableShardingStrategy = $rule;
    }

    /**
     * 获取切片的表名
     * @return string
     */
    public function getTableName()
    {
        return $this->_tableName;
    }

    /**
     * @return InlineShardingStrategyConfiguration
     */
    public function getTableShardingStrategyConfig()
    {
        return $this->_tableShardingStrategy;
    }

    /**
     * @return InlineShardingStrategyConfiguration
     */
    public function getDatabaseShardingStrategyConfig()
    {
        return $this->_databaseShardingStrategy;
    }
}