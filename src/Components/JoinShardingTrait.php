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
use PhpShardingPdo\Core\Model;

/**
 * Join sharding
 * Trait JoinShardingTrait
 * @package PhpShardingPdo\Components
 */
trait JoinShardingTrait
{
    private $_table_alias = ''; //表别名
    private $_join_type = 0; //join类型
    /**
     * @var array $_joinModelObjArr
     */
    private $_joinModelObjArr = []; //join的model对象
    private $_on_condition = []; //on条件
    private $_on_condition_str = ''; //on条件字符串

    /**
     * @return int
     */
    public function getJoinType()
    {
        return $this->_join_type;
    }

    /**
     * @return int
     */
    public function setJoinType($type)
    {
        return $this->_join_type = $type;
    }


    /**
     * 获取表别名
     * @param string $execTableName
     * @return string
     */
    protected function getTableAlias()
    {
        return $this->_table_alias;
    }

    /**
     * 获取别名字符串，用于替换原表名
     * @param string $execTableName
     * @return string
     */
    protected function getExecStringTableAlias($execTableName){
        if(empty($this->_table_alias)){
            return '`'.$execTableName.'`';
        }
        return '`'.$execTableName.'`'.' as '.$this->getTableAlias();
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
     * 添加join对象
     */
    public function addJoinModelObj(Model $obj){
        $this->_joinModelObjArr[] = $obj;
        return $this;
    }

    /**
     * 查询条件
     * @param array $condition
     * @return $this
     */
    public function on($condition = [])
    {
        foreach ($condition as $key => $val) {
            if (!isset($this->_on_condition[$key])) {
                $this->_on_condition[$key] = $val;
                continue;
            }
            if (isset($this->_on_condition[$key][0])
                && $this->_on_condition[$key][0] == 'more'
            ) {
                array_push($this->_on_condition[$key][1], $val);
            } else {  //为兼容一个键值多个查询条件
                $old = $this->_on_condition[$key];
                $this->_on_condition[$key] = [
                    'more', [$old]
                ];
                array_push($this->_on_condition[$key][1], $val);
            }
        }
        return $this;
    }
}