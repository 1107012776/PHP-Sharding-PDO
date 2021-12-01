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

/**
 * join 实体类
 * Class JoinTableEntity
 * @package PhpShardingPdo\Core
 */
class JoinTableEntity{
    private $tableName = '';  //数据库表名
    private $tableNameAlias = '';  //数据表别名
    private $joinType = 0;  //join类型

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

}