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

use PhpShardingPdo\Core\StatementShardingPdo;

/**
 * group by 数据归并特质类
 * User: linyushan
 * Date: 2019/8/1
 * Time: 11:51
 * @var \PhpShardingPdo\Core\ShardingPdo $this
 * @property \PDO $_current_exec_db
 */
trait  GroupByShardingTrait
{
    private function _groupShardingSearch($sqlArr, $sql)
    {
        $result = [];
        empty($sqlArr) && $sqlArr = [$sql];
        $statementArr = [];
        $searchFunc = function ($sql) use (&$statementArr) {
            if (!empty($this->_current_exec_db)) {  //有找到具体的库
                /**
                 * @var \PDOStatement $statement
                 */
                $statement = $statementArr[] = $this->_current_exec_db->prepare($sql, array(\PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                $res = $statement->execute($this->_condition_bind);
                return $res;
            }
            /**
             * @var \Pdo $db
             */
            foreach ($this->_databasePdoInstanceMap() as $key => $db) {  //没有找到具体的库
                /**
                 * @var \PDOStatement $statement
                 */
                $statement = $statementArr[] = $db->prepare($sql, array(\PDO::ATTR_CURSOR => $this->attr_cursor));
                $res[$key] = $statement->execute($this->_condition_bind);
            }
            return $res;
        };
        if (count($sqlArr) <= 1 && !empty($this->_current_exec_db)) {  //查找到具体的表和库了
            $sqlArr[0] = $sqlArr[0] . $this->_limit_str;
        }
        foreach ($sqlArr as $sql) {
            $searchFunc($sql);
        }
        if (count($statementArr) <= 1) {   //一个分表查询
            //只有一个表，这个简单直接来就行，没有什么其他的问题
            $tmp = $statementArr[0]->fetchAll($this->fetch_style);
            !empty($tmp) && $result = array_merge($result, $tmp);
            return $result;
        }
        if (!empty($limit = $this->_getLimitReCount())) {
            return $this->_limitShardingGroupSearch($statementArr, $limit);
        }
        //不存在limit的情况下 group by归并
        $groupField = $this->_getGroupField();
        if (!empty($this->_order_str)) {
            $orderField = $this->_getOrderField();
        }
        $intersect = array_intersect($groupField, $this->_field);
        if (empty($intersect) || $groupField[0] != $intersect[0]
            || empty($orderField) || $orderField[0][0] != $intersect[0]
        ) {  //不存在交集，内存归并
            if (empty($intersect)) {  //优化group by，使用group by 则必定查询返回
                $intersect[0] = $groupField[0];
            }
            /**
             * @var \PDOStatement $s
             */
            foreach ($statementArr as $s) {
                $tmp = $s->fetchAll($this->fetch_style);
                !empty($tmp) && $result = array_merge($result, $tmp);
            }
            //默认给group by 字段进行一个排序
            StatementShardingPdo::reGroupSort($result, [[$intersect[0], 'asc']]);
            $data = [];
            foreach ($result as &$val) {
                $tmp = $val;
                if (strstr($this->_field_str, 'sum(') &&
                    !empty($data) && end($data)[$intersect[0]] == $tmp[$intersect[0]]) {  //存在sum则累积相加
                    foreach ($this->_field as $v) {
                        if (strstr($v, 'sum(')) {
                            $data[count($data) - 1][$v] += $tmp[$v];
                            continue;
                        }
                    }
                } else {
                    if (!empty($data) && end($data)[$intersect[0]] == $tmp[$intersect[0]]) { //只能取一条
                        continue;
                    }
                }
                $data[] = $val;
            }
            $result = $data;
            if (!empty($orderField)) {
                StatementShardingPdo::reGroupSort($result, $orderField);
            }
            return $result;
        }
        //存在交集

        if (empty($orderField)) {  //优化
            $orderField = [[$groupField[0], 'asc']];
        }
        if (empty($intersect)) {  //优化group by，使用group by 则必定查询返回
            $intersect[0] = $groupField[0];
        }
        $statementCurrentRowObjArr = [];
        /**
         * @var \PDOStatement $s
         */
        foreach ($statementArr as $index => $s) {
            $statementCurrentRowObjArr[] = new StatementShardingPdo($s);
        }
        while (!empty($statementCurrentRowObjArr)) {
            StatementShardingPdo::reGroupSort($statementCurrentRowObjArr, $orderField);
            if (empty($statementCurrentRowObjArr)) {
                break;
            }
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
            if (strstr($this->_field_str, 'sum(') &&
                !empty($result) && end($result)[$intersect[0]] == $tmp[$intersect[0]]) {  //存在sum则累积相加
                foreach ($this->_field as $v) {
                    if (strstr($v, 'sum(')) {
                        $v = strtolower($v);
                        if(strstr($v,' as ')){  //处理sum之后的别名数值累加
                            $vFieldArr = explode(' as ',$v);
                            $vFieldArr[1] = trim($vFieldArr[1]);
                            $result[count($result) - 1][$vFieldArr[1]] += $tmp[$vFieldArr[1]];
                        }else{
                            $result[count($result) - 1][$v] += $tmp[$v];
                        }
                        continue 2;
                    }
                }
            } else {
                if (!empty($result) && end($result)[$intersect[0]] == $tmp[$intersect[0]]) {
                    continue;
                }
            }
            array_push($result, $tmp);
        }
        if (!empty($orderField)) {
            StatementShardingPdo::reGroupSort($result, $orderField);
        }
        return $result;
    }

    /**
     * group流式归并数据
     * @param $statementArr
     * @param $limit
     * @return array
     */
    private function _limitShardingGroupSearch($statementArr, $limit = 0)
    {
        $offsetDataFlag = [];
        $result = [];
        //不存在limit的情况下 group by归并
        $groupField = $this->_getGroupField();
        if (!empty($this->_order_str)) {
            $orderField = $this->_getOrderField();
        }
        $intersect = array_intersect($groupField, $this->_field);
        if (empty($intersect)) {  //优化group by，使用group by 则必定查询返回
            $intersect[0] = $groupField[0];
        }
        if (empty($orderField)) {  //优化，没有交集制造交集
            $orderField = [[$groupField[0], 'asc']];
        }
        //存在交集
        $statementCurrentRowObjArr = [];
        /**
         * @var \PDOStatement $s
         */
        foreach ($statementArr as $index => $s) {
            $statementCurrentRowObjArr[] = new StatementShardingPdo($s);
        }
        while (!empty($statementCurrentRowObjArr)) {
            StatementShardingPdo::reGroupSort($statementCurrentRowObjArr, $orderField);
            if (empty($statementCurrentRowObjArr)) {
                break;
            }
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
            if (strstr($this->_field_str, 'sum(') &&
                !empty($result) && end($result)[$intersect[0]] == $tmp[$intersect[0]]) {  //存在sum则累积相加
                foreach ($this->_field as $v) {
                    if (strstr($v, 'sum(')) {
                        $v = strtolower($v);
                        if(strstr($v,' as ')){  //处理sum之后的别名数值累加
                            $vFieldArr = explode(' as ',$v);
                            $vFieldArr[1] = trim($vFieldArr[1]);
                            $result[count($result) - 1][$vFieldArr[1]] += $tmp[$vFieldArr[1]];
                        }else{
                            $result[count($result) - 1][$v] += $tmp[$v];
                        }
                        continue 2;
                    }
                }
            } else {
                if(!empty($offsetDataFlag[$tmp[$intersect[0]]])){
                    continue;
                }
                if ($this->offset > 0
                && empty($offsetDataFlag[$tmp[$intersect[0]]])
                ) {
                    $this->offset--;
                    $offsetDataFlag[$tmp[$intersect[0]]] = 1;
                    continue;
                }
                if (!empty($result) && end($result)[$intersect[0]] == $tmp[$intersect[0]]) {
                    continue;
                }
            }
            if ($limit <= 0) {
                break;
            }
            $limit--;
            array_push($result, $tmp);
        }
        if (!empty($orderField)) {
            StatementShardingPdo::reGroupSort($result, $orderField);
        }
        return $result;
    }


    /**
     * 获取分组信息
     */
    private function _getGroupField()
    {
        $group = str_replace(' group by ', '', $this->_group_str);
        if (strstr($group, ',')) {
            $group = explode(',', $group);
        }
        if (is_array($group)) {  //多个group by
            foreach ($group as &$v) {
                $v = trim($v);
            }
        } else {
            $group = trim($group);
            $group = [$group];
        }
        return $group;
    }
}