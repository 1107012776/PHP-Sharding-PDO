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
 * 分片规则配置
 * User: lys
 * Date: 2019/7/24
 * Time: 16:49
 */
class ShardingRuleConfiguration
{
    private $_tableRuleList = [];
    private $_actualDataNodesArr = [];

    /**
     * 配置分库分表的规则和查询范围
     * $databaseRule = [
     * 'name' => 'db',  //数据库名称
     * 'index' => '1,2',  //分库index
     * 'range' => [1,2] //范围
     * ];
     * $tableRule = [
     * 'name' => 't_order',  //数据库名称
     * 'index' => '1,2',  //分表index
     * 'range' => [1,2] //范围
     * ];
     * @描述 index 和 range 二者取一，存在index则优先取index
     * @param array $databaseRule
     * @param array $tableRule
     */
    public function setActualDataNodes($databaseRule = [], $tableRule = [])
    {
        $this->_actualDataNodesArr[] = [
            'databaseRule' => $databaseRule,
            'tableRule' => $tableRule,
        ];
    }

    /**
     * 设置分表分库规则
     * @param ShardingTableRuleConfig $tableRule
     */
    public function add(ShardingTableRuleConfig $tableRule)
    {
        array_push($this->_tableRuleList, $tableRule);
    }

    /**
     *获取分表分库规则列表
     * @return array
     */
    public function getTableRuleList()
    {
        return $this->_tableRuleList;
    }
}
