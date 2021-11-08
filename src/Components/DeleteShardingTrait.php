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
/**
 * 插入sharding
 * User: lys
 * Date: 2019/8/1
 * Time: 17:03
 * @var \PhpShardingPdo\Core\ShardingPdo $this
 * @property \PDO $_current_exec_db
 */
trait DeleteShardingTrait
{
    /**
     * @return bool|int
     */
    public function _deleteSharding()
    {
        $result = [];
        $sqlArr = [];
        if (empty($this->_current_exec_table) && empty($this->_table_name_index)) {  //全部扫描
            $sql = 'delete ' . ' from ' . $this->_table_name . $this->_condition_str .$this->_order_str . $this->_limit_str;
        } elseif (empty($this->_current_exec_table) && !empty($this->_table_name_index)) {
            foreach ($this->_table_name_index as $tableName) {
                $sqlArr[] = 'delete ' . ' from ' .'`'. $tableName .'`'. $this->_condition_str .  $this->_order_str . $this->_limit_str;
            }
        } else {
            $sql = 'delete ' . ' from ' .'`'. $this->_current_exec_table .'`'. $this->_condition_str . $this->_order_str . $this->_limit_str;
        }
        $statementArr = [];
        if (empty($this->_current_exec_db)) {  //没有找到数据库
            $deleteFunc = function ($sql) use (&$statementArr) {
                foreach ($this->_databasePdoInstanceMap() as $key => $db) {
                    /**
                     * @var \PDOStatement $statement
                     * @var \PDO $db
                     */
                    $statement = $statementArr[] = $db->prepare($sql, array(\PDO::ATTR_CURSOR => $this->attr_cursor));
                    $res[$key] = $statement->execute($this->_condition_bind);
                    if(empty($res[$key])){
                        $this->_sqlErrors[] = $statement->errorInfo();
                    }
                }
            };
            if (!empty($sqlArr)) {  //扫描多张表
                foreach ($sqlArr as $sql) {
                    $deleteFunc($sql);
                }
            } else {
                $deleteFunc($sql);
            }
            if (empty($statementArr)) {
                return false;
            }
            /**
            * @var \PDOStatement $s
            */
            foreach ($statementArr as $s) {
                    $tmp = $s->rowCount();
                    !empty($tmp) && $result[] = $tmp;
            }
        } else {  //找到了具体的数据库
            empty($sqlArr) && $sqlArr = [$sql];
            foreach ($sqlArr as $sql) {
                /**
                 * @var \PDOStatement $statement
                 */
                $statement = $statementArr[] = $this->_current_exec_db->prepare($sql, array(\PDO::ATTR_CURSOR => $this->attr_cursor));
                $res = $statement->execute($this->_condition_bind);
                if(empty($res)){
                    $this->_sqlErrors[] = $statement->errorInfo();
                }
            }
            /**
             * @var \PDOStatement $s
             */
            foreach ($statementArr as $s) {
                $tmp = $s->rowCount();
                !empty($tmp) && $result[] = $tmp;
            }
        }
        return array_sum($result);
    }
}