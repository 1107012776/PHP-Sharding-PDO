<?php
/**
 * phpShardingPdo  file.
 * @author linyushan
 * @link https://www.developzhe.com/
 * @package https://github.com/1107012776/PHP-Sharding-PDO
 * @copyright Copyright &copy; 2019-2021
 * @license https://github.com/1107012776/PHP-Sharding-PDO/blob/master/LICENSE
 */
namespace PhpShardingPdo\Components;

use \PhpShardingPdo\Core\StatementShardingPdo;

/**
 * 查询
 * User: linyushan
 * Date: 2019/8/2
 * Time: 14:57
 * @property \PDO $_current_exec_db
 */
trait SelectSearchSharingTrait
{


    /**
     * 存在limit的时候查询
     * @param $statementArr
     * @param $limit
     * @return array
     */
    private function _limitDefaultSearch($statementArr, $limit)
    {
        $result = [];
        if (!empty($this->_order_str)) {
            $statementCurrentRowObjArr = [];
            $orderArr = $this->_getOrderField();
            /**
             * @var \PDOStatement $s
             */
            foreach ($statementArr as $index => $s) {
                $statementCurrentRowObjArr[] = new StatementShardingPdo($s);
            }
            while ($limit > 0) {
                StatementShardingPdo::reSort($statementCurrentRowObjArr, $orderArr);
                /**
                 * @var StatementShardingPdo $que
                 */
                $que = $statementCurrentRowObjArr[0];
                $tmp = $que->getFetch();
                if (empty($tmp)) {
                    array_shift($statementCurrentRowObjArr);
                    if (empty($statementCurrentRowObjArr)) {
                        break;
                    }
                    $statementCurrentRowObjArr = array_values($statementCurrentRowObjArr);
                    continue;
                }
                $limit--;
                array_push($result, $tmp);
            }
        } else {
            /**
             * @var \PDOStatement $s
             */
            foreach ($statementArr as $index => $s) {
                while ($limit > 0) {
                    $tmp = $s->fetch(\PDO::FETCH_ASSOC);
                    if (empty($tmp)) {
                        break;
                    }
                    $limit--;
                    array_push($result, $tmp);
                }
            }
        }
        return $result;
    }


    private function _defaultSearch()
    {
        $result = [];
        $sqlArr = [];
        if (empty($this->_current_exec_table) && empty($this->_table_name_index)) {  //全部扫描
            $sql = 'select ' . $this->_field_str . ' from ' . $this->_table_name . $this->_condition_str . $this->_group_str . $this->_order_str . $this->_limit_str;
        } elseif (empty($this->_current_exec_table) && !empty($this->_table_name_index)) {
            foreach ($this->_table_name_index as $tableName) {
                $sqlArr[] = 'select ' . $this->_field_str . ' from ' . $tableName . $this->_condition_str . $this->_group_str . $this->_order_str . $this->_limit_str;
            }
        } else {
            $sql = 'select ' . $this->_field_str . ' from ' . $this->_current_exec_table . $this->_condition_str . $this->_group_str . $this->_order_str . $this->_limit_str;
        }
        $statementArr = [];
        if (empty($this->_current_exec_db)) {  //没有找到数据库
            $searchFunc = function ($sql) use (&$statementArr) {
                foreach ($this->_databasePdoInstanceMap as $key => $db) {
                    /**
                     * @var \PDOStatement $statement
                     * @var \PDO $db
                     */
                    $statement = $statementArr[] = $db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
                    $res[$key] = $statement->execute($this->_condition_bind);
                }
            };
            if (!empty($sqlArr)) {  //扫描多张表
                foreach ($sqlArr as $sql) {
                    $searchFunc($sql);
                }
            } else {
                $searchFunc($sql);
            }
            if (empty($statementArr)) {
                return false;
            }
            if (!empty($limit = $this->_getLimitReCount())) {
                return $this->_limitDefaultSearch($statementArr, $limit);
            } else {
                /**
                 * @var \PDOStatement $s
                 */
                foreach ($statementArr as $s) {
                    $tmp = $s->fetchAll(\PDO::FETCH_ASSOC);
                    !empty($tmp) && $result = array_merge($result, $tmp);
                }
            }
        } else {
            empty($sqlArr) && $sqlArr = [$sql];
            foreach ($sqlArr as $sql) {
                /**
                 * @var \PDOStatement $statement
                 */
                $statement = $statementArr[] = $this->_current_exec_db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
                $res = $statement->execute($this->_condition_bind);
            }
            if (count($statementArr) > 1) {
                if (!empty($limit = $this->_getLimitReCount())) {
                    return $this->_limitDefaultSearch($statementArr, $limit);
                } else {
                    /**
                     * @var \PDOStatement $s
                     */
                    foreach ($statementArr as $s) {
                        $tmp = $s->fetchAll(\PDO::FETCH_ASSOC);
                        !empty($tmp) && $result = array_merge($result, $tmp);
                    }
                }
            } else {
                $tmp = $statement->fetchAll(\PDO::FETCH_ASSOC);
                !empty($tmp) && $result = array_merge($result, $tmp);
            }
        }
        return $result;
    }
}