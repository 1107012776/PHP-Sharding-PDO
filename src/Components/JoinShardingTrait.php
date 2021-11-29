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
 * Join sharding
 * Trait JoinShardingTrait
 * @package PhpShardingPdo\Components
 */
trait JoinShardingTrait
{
    private $_table_alias = ''; //表别名

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
}