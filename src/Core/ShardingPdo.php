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
 * 分库分表pdo核心类
 * User: lys
 * Date: 2019/7/24
 * Time: 17:27
 * https://www.php.cn/php-weizijiaocheng-361207.html
 */
class ShardingPdo
{
    use \PhpShardingPdo\Components\SelectSearchSharingTrait;
    use \PhpShardingPdo\Components\GroupByShardingTrait;
    use \PhpShardingPdo\Components\InsertShardingTrait;
    use \PhpShardingPdo\Components\UpdateShardingTrait;
    use \PhpShardingPdo\Components\DeleteShardingTrait;
    use \PhpShardingPdo\Components\TransactionShardingTrait;
    use \PhpShardingPdo\Components\ReplaceIntoShardingTrait;
    use \PhpShardingPdo\Components\IncrDecrShardingTrait;
    use \PhpShardingPdo\Components\ParsingTrait;
    use \PhpShardingPdo\Components\JoinShardingTrait;
    use \PhpShardingPdo\Components\JoinParsingTrait;
    /**
     * @var ShardingRuleConfiguration
     */
    private $_shardingRuleConfiguration;
    private $_table_name_index = [];
    private $_tableRuleList = [];
    private $_table_name = '';
    private $_configDatabasePdoInstanceMapName = '';


    //以下需要初始化值
    private $_condition = [];
    private $_condition_str = '';
    private $_condition_bind = [];
    private $_field = [];
    private $_limit_str = '';
    private $_order_str = '';
    private $_group_str = '';
    private $_field_str = '*';
    private $_insert_data = [];
    private $_update_data = [];
    private $_last_insert_id = 0;  //最后插入的id
    private $offset = 0; //偏移量
    private $offset_limit = 0; //偏移之后返回数

    /**
     * 重置数据
     * @return ShardingPdo
     */
    public function renew()
    {
        $this->_condition = [];
        $this->_field = [];
        $this->_condition_str = '';
        $this->_condition_bind = [];
        $this->_limit_str = '';
        $this->_order_str = '';
        $this->_group_str = '';
        $this->_field_str = '*';
        $this->_insert_data = [];
        $this->_update_data = [];
        $this->_last_insert_id = 0;  //最后插入的id
        $this->_sqlErrors = [];  //错误信息
        $this->offset = 0;
        $this->offset_limit = 0;
        $this->fetch_style = \PDO::FETCH_ASSOC;
        $this->attr_cursor = \PDO::CURSOR_FWDONLY;
        $this->_incrOrDecrColumnStr = '';
        $this->_bind_index = 0;
        $this->_table_alias = '';  //表别名
        $this->_joinTablePlanObjArr = [];
        $this->freeExecDb();
        $this->_current_exec_table = '';
        return $this;
    }

    public function freeExecDb()
    {
        $this->_current_exec_db_index = null;
    }

    /**
     * @var string
     */
    private $_current_exec_table = '';  //具体执行的表

    private $_current_exec_db_index = null; //具体执行的库 index

    /**
     * @param string $configDatabasePdoInstanceMapName
     * @param ShardingRuleConfiguration $config
     * @param string $exeSqlXaUniqidFilePath //xa提交失败日志记录
     */
    public function __construct($configDatabasePdoInstanceMapName, ShardingRuleConfiguration $config, $exeSqlXaUniqidFilePath = '')
    {
        $this->initTrans();
        $this->_configDatabasePdoInstanceMapName = $configDatabasePdoInstanceMapName;
        ShardingPdoContext::setValue(self::$_execSqlTransactionUniqidFilePath, $exeSqlXaUniqidFilePath);
        $this->_shardingRuleConfiguration = $config;
    }

    /**
     * 获取$databasePdoInstanceMap
     * @return array
     */
    private function _databasePdoInstanceMap()
    {
        $databasePdoInstanceMap = ShardingPdoContext::getValue($this->_configDatabasePdoInstanceMapName);
        return $databasePdoInstanceMap;
    }

    public function table($tableName = '', $config = [])
    {
        if (empty($tableName)) {
            return false;
        }
        $this->_table_name = $tableName;
        if (!empty($config)) {  //表的切片配置
            if (isset($config['index'])) {  //索引的形式
                $arr = explode(',', $config['index']);
                foreach ($arr as $v) {
                    $this->_table_name_index[] = $this->_table_name . '_' . $v;
                }
            } elseif (isset($config['range'])) { //范围的形式
                for ($i = $config['range'][0]; $i <= $config['range'][1]; $i++) {
                    $this->_table_name_index[] = $this->_table_name . '_' . $i;
                }
            }
        }
        $this->renew();
        return $this;
    }


    /**
     * 查询条件
     * @param array $condition
     * @return ShardingPdo
     */
    public function where($condition = [])
    {
        $keywords = [  //这边关键词不想限制太死了，不然有些时候输入关键词，会被过滤掉，导致开发者无法及时察觉错误
            'more'
        ];
        foreach ($condition as $key => $val) {
            $key = trim($key);  //去空格
            if (in_array(strtolower($key), $keywords) //发现是条件关键词，则不允许
                || strpos($key, ' ') !== false //条件的key不能存在空格
            ) {
                continue;
            }
            if (!isset($this->_condition[$key])) {
                $this->_condition[$key] = $val;
                continue;
            }
            if (isset($this->_condition[$key][0])
                && $this->_condition[$key][0] == 'more'
            ) {
                array_push($this->_condition[$key][1], $val);
            } else {  //为兼容一个键值多个查询条件
                $old = $this->_condition[$key];
                $this->_condition[$key] = [
                    'more', [$old]
                ];
                array_push($this->_condition[$key][1], $val);
            }
        }
        return $this;
    }

    /**
     * @param int $offset
     * @param $page_count
     * @return $this
     */
    public function limit($offset = 0, $page_count = null)
    {
        if (empty($page_count)) {
            $this->_limit_str = sprintf("%.0f", $offset);
        } else {
            $this->offset = sprintf("%.0f", $offset);  //偏移量必须单独处理，否者分页存在问题
            $this->offset_limit = sprintf("%.0f", $page_count);
            $this->_limit_str = sprintf("%.0f", $offset) . ',' . sprintf("%.0f", $page_count);
        }
        return $this;
    }

    /**
     * order by 排序
     * @param $order
     * @return $this
     */
    public function order($order)
    {
        $this->_order_str .= ',' . $order;
        $this->_order_str = trim($this->_order_str, ',');
        return $this;
    }

    /**
     * group by 分组
     * @param $group
     * @return $this
     */
    public function group($group)
    {
        $this->_group_str .= ',' . $group;
        $this->_group_str = trim($this->_group_str, ',');
        return $this;
    }

    /**
     * 获取的字段
     * @param array $fields
     * @return $this
     */
    public function field($fields = [])
    {
        if (is_string($fields)) {
            $fields = explode(',', $fields);
            $fields = array_filter($fields);
        }
        $this->_field = array_merge($this->_field, $fields);
        $this->_field = array_filter($this->_field);
        $this->_field = array_unique($this->_field);
        return $this;
    }


    /**
     * 查找一条数据
     * @return array|bool
     */
    public function find()
    {
        $this->clearSqlErrors();
        $this->limit(1);
        $search = $this->_search();
        return empty($search) ? false : $search[0];
    }

    /**
     * 查找所有数据
     * @return array|bool
     */
    public function findAll()
    {
        $this->clearSqlErrors();
        return $this->_search();
    }


    /**
     * 查找所有数据的条数
     * @return int
     */
    public function count($field_count = '')
    {
        $this->clearSqlErrors();
        $old = $this->_field_str;
        empty($field_count) && $field_count = '*';
        $this->_field_str = 'count(' . $field_count . ') as total_count_num';
        $list = $this->_search();
        $this->_field_str = $old;
        $count = 0;
        if (empty($list)) {
            return $count;
        }
        foreach ($list as &$value) {
            $count += $value['total_count_num'];
        }
        return $count;
    }


    /**
     * 更新所有数据
     * @return boolean|int
     */
    public function update($data)
    {
        $this->clearSqlErrors();
        $this->_pare();
        $this->_update_data = $data;
        return $this->_updateSharding();
    }

    /**
     * 插入数据
     * @return int|boolean
     */
    public function insert($data)
    {
        $this->clearSqlErrors();
        $this->_insert_data = $data;
        $this->_getQpDb();
        $this->_current_exec_table = $this->_getQpTableName();
        return $this->_insertSharding();
    }

    /**
     * 在分布式里面，数据库的自增ID机制的主要原理是：
     * 数据库自增ID和mysql数据库的replace_into()函数实现的。
     * 这里的replace数据库自增ID和mysql数据库的replace_into()函数实现的。
     * 这里的replace into跟insert功能类似，不同点在于：replace into首先尝试插入数据列表中，
     * 如果发现表中已经有此行数据（根据主键或唯一索引判断）则先删除，再插入。否则直接插入新数据
     * @param $data
     * @return boolean|int
     */
    public function replaceInto($data)
    {
        $this->clearSqlErrors();
        $this->_insert_data = $data;
        $this->_getQpDb();
        $this->_current_exec_table = $this->_getQpTableName();
        return $this->_replaceIntoSharding();
    }

    /**
     * 删除数据
     * @return int|boolean
     */
    public function delete()
    {
        $this->clearSqlErrors();
        $this->_pare();
        return $this->_deleteSharding();
    }



    /***************************************** 私有方法  *********************************************/

    /**
     * limit页返回数,用于limit返回
     */
    private function _getLimitReCount()
    {
        if (!empty($this->offset_limit)) {
            return $this->offset_limit;
        }
        if (empty($this->_limit_str)) {
            return false;
        }
        $limit = str_replace(' limit ', '', $this->_limit_str);
        if (strstr($limit, ',')) {
            $limit = explode(',', $limit)[1];
        }
        return intval($limit);
    }

    /**
     * 分库
     * @return null|\PDO
     */
    private function _getQpDb()
    {
        $map = $this->_databasePdoInstanceMap();
        if (empty($this->_tableRuleList)) {
            $tableRuleList = $this->_shardingRuleConfiguration->getTableRuleList();
            /**
             * @var ShardingTableRuleConfig $tableRule
             */
            foreach ($tableRuleList as $tableRule) {
                if ($tableRule->getTableName() != $this->_table_name) {
                    continue;
                }
                $this->_tableRuleList[] = $tableRule;
            }
        }
        if (empty($this->_tableRuleList[0])) {
            if (count($map) == 1) {  //只有一个数据库，那就是当前
                $mapValues = array_values($map);
                $this->_current_exec_db_index = array_keys($map)[0];
                return $mapValues[0];
            }
            return null;  //返回这个代表没有规则，则需要全部db扫描了
        }
        $tableRule = $this->_tableRuleList[0];
        $tableShardingStrategyConfig = $tableRule->getDatabaseShardingStrategyConfig();
        $number = null;
        if ($tableShardingStrategyConfig->isCustomizeRule()) {  //是否自定义规则
            $customizeCondition = !empty($this->_insert_data) ? $this->_insert_data : $this->_condition;
            if (empty($customizeCondition)) {  //自定义，却找不到条件
                return null;  //返回这个代表没有规则，则需要全部db扫描了
            }
            $customizeConditionNew = [];
            foreach ($customizeCondition as $key => $val) {
                strpos($key, '.') !== false && $keyArr = explode('.', $key);
                if (!empty($keyArr) && $keyArr[0] == $this->getTableAlias()) {
                    $customizeConditionNew[$keyArr[1]] = $val;
                } else {
                    $customizeConditionNew[$key] = $val;
                }
            }
            $number = $tableShardingStrategyConfig->getCustomizeNum($customizeConditionNew);  //自定义规则
            if ($number === null) {
                return null;  //返回这个代表没有规则，则需要全部db扫描了
            }
            $index = $tableShardingStrategyConfig->getFix() . $number;
            $this->_current_exec_db_index = $index;
            return isset($map[$index]) ? $map[$index] : null;
        }
        $name = $tableShardingStrategyConfig->getName();
        if (!empty($this->_insert_data)) {  //优先insert的
            foreach ($this->_insert_data as $key => $val) {
                if ($key == $name && !is_array($val)) {
                    $number = $tableShardingStrategyConfig->getNum($val);
                }
            }
        } elseif (!empty($this->_condition)) {
            foreach ($this->_condition as $key => $val) {
                if ($key == $name && !is_array($val)) {
                    $number = $tableShardingStrategyConfig->getNum($val);
                } elseif ($key == $this->getFieldAlias($name) && !is_array($val)) {  //join的情况
                    $number = $tableShardingStrategyConfig->getNum($val);
                }
            }
        }
        if ($number === null) {
            if (count($map) == 1) {  //只有一个数据库，那就是当前
                $mapValues = array_values($map);
                $this->_current_exec_db_index = array_keys($map)[0];
                return $mapValues[0];
            }
            return null;  //返回这个代表没有规则，则需要全部db扫描了
        }
        $index = $tableShardingStrategyConfig->getFix() . $number;
        $this->_current_exec_db_index = $index;
        return isset($map[$index]) ? $map[$index] : null;
    }

    protected function getCurrentExecDb($index = '')
    {
        empty($index) && $index = $this->_current_exec_db_index;
        if (empty($index)) {
            return null;
        }
        $map = $this->_databasePdoInstanceMap();
        return isset($map[$index]) ? $map[$index] : null;
    }

    /**
     * 分表
     * 获取水平切片的表名
     */
    private function _getQpTableName()
    {
        if (empty($this->_tableRuleList)) {
            $tableRuleList = $this->_shardingRuleConfiguration->getTableRuleList();
            /**
             * @var ShardingTableRuleConfig $tableRule
             */
            foreach ($tableRuleList as $tableRule) {
                if ($tableRule->getTableName() != $this->_table_name) {
                    continue;
                }
                $this->_tableRuleList[] = $tableRule;
            }
        }
        if (empty($this->_tableRuleList[0])) {
            return null;  //返回这个代表没有规则，则需要全部表扫描了
        }
        $tableRule = $this->_tableRuleList[0];
        $tableShardingStrategyConfig = $tableRule->getTableShardingStrategyConfig();
        $number = null;
        if ($tableShardingStrategyConfig->isCustomizeRule()) {  //是否自定义规则
            $customizeCondition = !empty($this->_insert_data) ? $this->_insert_data : $this->_condition;
            if (empty($customizeCondition)) {  //自定义，却找不到条件
                return null;  //返回这个代表没有规则，则需要全部表扫描了
            }
            $customizeConditionNew = [];
            foreach ($customizeCondition as $key => $val) {
                strpos($key, '.') !== false && $keyArr = explode('.', $key);
                if (!empty($keyArr) && $keyArr[0] == $this->getTableAlias()) {
                    $customizeConditionNew[$keyArr[1]] = $val;
                } else {
                    $customizeConditionNew[$key] = $val;
                }
            }
            $number = $tableShardingStrategyConfig->getCustomizeNum($customizeConditionNew);  //自定义规则
            if ($number === null) {
                return null;
            }
            return $tableShardingStrategyConfig->getFix() . $number;
        }
        $name = $tableShardingStrategyConfig->getName();
        if (!empty($this->_insert_data)) {
            foreach ($this->_insert_data as $key => $val) {
                if ($key == $name && !is_array($val)) {
                    $number = $tableShardingStrategyConfig->getNum($val);
                }
            }
        } elseif (!empty($this->_condition)) {
            foreach ($this->_condition as $key => $val) {
                if ($key == $name && !is_array($val)) {
                    $number = $tableShardingStrategyConfig->getNum($val);
                } elseif ($key == $this->getFieldAlias($name) && !is_array($val)) {  //join的情况
                    $number = $tableShardingStrategyConfig->getNum($val);
                }
            }
        }
        if ($number === null) {
            return null;  //返回这个代表没有规则，则需要全部表扫描了
        }
        return $tableShardingStrategyConfig->getFix() . $number;
    }

    public function __destruct()
    {
        $this->freeExecDb();
    }

}
