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
