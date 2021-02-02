<?php
/**
 * phpShardingPdo  file.
 * @author linyushan
 * @link https://www.developzhe.com/
 * @package https://github.com/1107012776/PHP-Sharding-PDO
 * @copyright Copyright &copy; 2019-2021
 * @license https://github.com/1107012776/PHP-Sharding-PDO/blob/master/LICENSE
 */
namespace PhpShardingPdo\Core;
/**
 * Created by PhpStorm.
 * User: 11070
 * Date: 2019/7/28
 * Time: 17:13
 */
class StatementShardingPdo
{

    /**
     * @var \PDOStatement
     */
    private $_statement;
    private $_queue;

    public function __construct(\PDOStatement $statement)
    {
        $this->_statement = $statement;
    }

    public function getFetch()
    {
        if (!empty($this->_queue)) {
            $tmp = array_shift($this->_queue);
            $this->_queue = array_values($this->_queue);
            return $tmp;
        }
        return $this->getNextFetch();
    }

    public function getNextFetch()
    {
        $tmp = $this->_statement->fetch(\PDO::FETCH_ASSOC);
        $this->_queue[] = $tmp;
        return $tmp;
    }

    public function getCurrentFetch()
    {
        if (empty($this->_queue[0])) {
            return $this->getNextFetch();
        }
        return $this->_queue[0];
    }


    /**
     * 二维数组降序排序
     * @param $arr
     * @param $field //依据这个key来排序
     * @return array
     */
    public static function reSort(&$arr, $field)
    {
        $key = $field[0][0];
        $fh = $field[0][1];
        $ss = function ($key, $fh, $field) {
            return function ($a, $b) use ($key, $fh, $field) {
                $leng = count($field);
                /**
                 * @var StatementShardingPdo $a
                 */
                $aRow = $a->getCurrentFetch();
                /**
                 * @var StatementShardingPdo $b
                 */
                $bRow = $b->getCurrentFetch();
                if (!isset($aRow[$key])) {  //排序字段不在返回值里面之后false无法排序
                    return false;
                }
                switch ($fh) {
                    case 'asc':
                        if ($aRow[$key] == $bRow[$key]) {
                            for ($i = 1; $i < $leng; $i++) {
                                $key_z = $field[$i][0];
                                $fh_z = $field[$i][1];
                                if (!isset($aRow[$key_z])) {  //排序字段不在返回值里面之后false无法排序
                                    return false;
                                }
                                if ($aRow[$key_z] == $bRow[$key_z]) {  //推后一个字段对比
                                    continue;
                                }
                                if ($fh_z == 'asc') {
                                    return $aRow[$key_z] > $bRow[$key_z];
                                } else {
                                    return $aRow[$key_z] < $bRow[$key_z];
                                }
                            }
                            return false;
                        } else {
                            return $aRow[$key] > $bRow[$key];     //通过改变大于、小于来正向反向排序
                        }
                        break;
                    case 'desc':
                        if ($aRow[$key] == $bRow[$key]) {
                            for ($i = 1; $i < $leng; $i++) {
                                $key_z = $field[$i][0];
                                $fh_z = $field[$i][1];
                                if ($aRow[$key_z] == $bRow[$key_z]) {  //推后一个字段对比
                                    continue;
                                }
                                if ($fh_z == 'asc') {
                                    return $aRow[$key_z] > $bRow[$key_z];
                                } else {
                                    return $aRow[$key_z] < $bRow[$key_z];
                                }
                            }
                            return false;
                        } else {
                            return $aRow[$key] < $bRow[$key];     //通过改变大于、小于来正向反向排序
                        }
                        break;
                }
            };
        };
        usort($arr, $ss($key, $fh, $field));
        return $arr;
    }

    /**
     * Group二维数组降序排序
     * @param $arr
     * @param $field //依据这个key来排序
     * @return array
     */
    public static function reGroupSort(&$arr, $field)
    {
        $key = $field[0][0];
        $fh = $field[0][1];
        $ss = function ($key, $fh, $field) {
            return function ($a, $b) use ($key, $fh, $field) {
                $leng = count($field);
                if (is_object($a)) {
                    /**
                     * @var StatementShardingPdo $a
                     */
                    $aRow = $a->getCurrentFetch();
                    /**
                     * @var StatementShardingPdo $b
                     */
                    $bRow = $b->getCurrentFetch();
                } else {
                    $aRow = $a;
                    $bRow = $b;
                }

                if (!isset($aRow[$key])) {  //排序字段不在返回值里面之后false无法排序
                    return false;
                }
                switch ($fh) {
                    case 'asc':
                        if ($aRow[$key] == $bRow[$key]) {
                            for ($i = 1; $i < $leng; $i++) {
                                $key_z = $field[$i][0];
                                $fh_z = $field[$i][1];
                                if (!isset($aRow[$key_z])) {  //排序字段不在返回值里面之后false无法排序
                                    return false;
                                }
                                if ($aRow[$key_z] == $bRow[$key_z]) {  //推后一个字段对比
                                    continue;
                                }
                                if ($fh_z == 'asc') {
                                    return $aRow[$key_z] > $bRow[$key_z];
                                } else {
                                    return $aRow[$key_z] < $bRow[$key_z];
                                }
                            }
                            return false;
                        } else {
                            return $aRow[$key] > $bRow[$key];     //通过改变大于、小于来正向反向排序
                        }
                        break;
                    case 'desc':
                        if ($aRow[$key] == $bRow[$key]) {
                            for ($i = 1; $i < $leng; $i++) {
                                $key_z = $field[$i][0];
                                $fh_z = $field[$i][1];
                                if ($aRow[$key_z] == $bRow[$key_z]) {  //推后一个字段对比
                                    continue;
                                }
                                if ($fh_z == 'asc') {
                                    return $aRow[$key_z] > $bRow[$key_z];
                                } else {
                                    return $aRow[$key_z] < $bRow[$key_z];
                                }
                            }
                            return false;
                        } else {
                            return $aRow[$key] < $bRow[$key];     //通过改变大于、小于来正向反向排序
                        }
                        break;
                }
            };
        };
        usort($arr, $ss($key, $fh, $field));
        return $arr;
    }
}