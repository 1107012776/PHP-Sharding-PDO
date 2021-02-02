<?php

namespace PhpShardingPdo\Components;
/**
 * 插入sharding
 * User: lys
 * Date: 2019/8/1
 * Time: 17:03
 * @var \PhpShardingPdo\Core\ShardingPdo $this
 * @property \PDO $_current_exec_db
 */
trait InsertShardingTrait
{
    public function getLastInsertId()
    {
        return $this->_last_insert_id;
    }

    /**
     * 插入应该是必须选中具体库，具体表的，不然很危险，导致插入到多个库多张表，数据很乱
     * @return bool|int
     */
    public function _insertSharding()
    {
        $this->_last_insert_id = 0;  //初始化_last_insert_id
        $sqlArr = [];
        $sql = 'insert into ###TABLENAME### (';
        $column_str = '';
        $value_str = '';
        $bindParams = [];
        foreach ($this->_insert_data as $k => $v) {
            $column_str .= ',`' . $k . '`';
            $value_str .= ',:' . $k . '';
            $bindParams[':' . $k] = $v;
        }
        $column_str = trim($column_str, ',');
        if (empty($column_str)) {
            return false;
        }
        $sql .= $column_str . ')';
        $value_str = trim($value_str, ',');
        $sql .= 'values(' . $value_str . ')';
        if (empty($this->_current_exec_table) && empty($this->_table_name_index)) {
            $sqlArr[] = str_replace('###TABLENAME###', $this->_table_name, $sql);
        } elseif (empty($this->_current_exec_table) && !empty($this->_table_name_index)) {  //不允许插入到多张表
            return false;
        } else {
            $sqlArr[] = str_replace('###TABLENAME###', $this->_current_exec_table, $sql);
        }
        $statementArr = [];
        $rowsCount = 0;
        $searchFunc = function ($sql) use (&$statementArr, $bindParams, &$rowsCount) {
            if (!empty($this->_current_exec_db)) {  //有找到具体的库
                self::setUseDatabaseArr($this->_current_exec_db);
                /**
                 * @var \PDOStatement $statement
                 */
                $statement = $statementArr[] = $this->_current_exec_db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
                $res = $statement->execute($bindParams);
                $this->_addExeSql($sql, $bindParams);
                $rowsCount += $statement->rowCount();
                $this->_last_insert_id = $this->_current_exec_db->lastInsertId();
                return $res;
            }
            return false; //必须找到具体的库才能插入，否者直接false
        };
        foreach ($sqlArr as $sql) {
            $res = $searchFunc($sql);
            if (empty($res)) {  //出现false则说有出现失败插入
                return false;
            }
        }
        return $rowsCount;
    }
}