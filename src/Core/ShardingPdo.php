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
    /**
     * @var ShardingRuleConfiguration
     */
    private $_shardingRuleConfiguration;
    private $_table_name_index = [];
    private $_databasePdoInstanceMap = [];
    private $_tableRuleList = [];
    private $_table_name = '';


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
        return $this;
    }

    /**
     * @var string
     */
    private $_current_exec_table = '';  //具体执行的表
    /**
     * @var \PDO
     */
    private $_current_exec_db = ''; //具体执行的库

    /**
     * @param array $databasePdoInstanceMap
     * @param ShardingRuleConfiguration $config
     * @param string $exeSqlXaUniqidFilePath  //xa提交失败日志记录
     */
    public function __construct(array $databasePdoInstanceMap, ShardingRuleConfiguration $config, $exeSqlXaUniqidFilePath = '')
    {
        self::$_exeSqlXaUniqidFilePath = $exeSqlXaUniqidFilePath;
        $this->_databasePdoInstanceMap = $databasePdoInstanceMap;
        $this->_shardingRuleConfiguration = $config;
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
        $this->_condition = array_merge($this->_condition, $condition);
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
            $this->_limit_str = doubleval($offset);
        } else {
            $this->_limit_str = doubleval($offset) . ',' . doubleval($page_count);
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
        return $this->_search();
    }


    /**
     * 更新所有数据
     * @return boolean|int
     */
    public function update($data)
    {
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
        $this->_insert_data = $data;
        $this->_current_exec_db = $this->_getQpDb();
        $this->_current_exec_table = $this->_getQpTableName();
        return $this->_insertSharding();
    }

    /**
     * 删除数据
     * @return int|boolean
     */
    public function delete()
    {
        $this->_pare();
        return $this->_deleteSharding();
    }



    /***************************************** 私有方法  *********************************************/
    /**
     * 解析
     */
    private function _pare()
    {
        foreach ($this->_condition as $key => $val) {
            $zwKey = ":$key";  //占位符
            if (is_array($val)) {
                switch ($val[0]) {
                    case 'gt':
                        $this->_condition_str .= ' and ' . $key . ' > ' . $zwKey;
                        $this->_condition_bind[$zwKey] = $val;
                        break;
                    case 'egt':
                        $this->_condition_str .= ' and ' . $key . ' >= ' . $zwKey;
                        $this->_condition_bind[$zwKey] = $val;
                        break;
                    case 'elt':
                        $this->_condition_str .= ' and ' . $key . ' <= ' . $zwKey;
                        $this->_condition_bind[$zwKey] = $val;
                        break;
                    case 'lt':
                        $this->_condition_str .= ' and ' . $key . ' < ' . $zwKey;
                        $this->_condition_bind[$zwKey] = $val;
                        break;
                    case 'in':
                        $zwKeyIn = '';
                        foreach ($val[1] as $k => $v) {
                            $zwKeyIn .= ',' . $zwKey . $k;
                            $this->_condition_bind[$zwKey . $k] = $v;
                        }
                        trim($zwKeyIn, ',');
                        $this->_condition_str .= ' and ' . $key . ' in (' . $zwKeyIn . ')';
                        break;
                }
            } else {
                $this->_condition_str .= ' and ' . $key . ' = ' . $zwKey;
                $this->_condition_bind[$zwKey] = $val;
            }
        }
        if (!empty($this->_condition_str)) {
            $this->_condition_str = ' where ' . substr($this->_condition_str, 5, strlen($this->_condition_str) - 5);
        }
        if (!empty($this->_order_str)) {
            $this->_order_str = str_replace(' order by ', '', $this->_order_str);
            $this->_order_str = ' order by ' . $this->_order_str;
        }
        if (!empty($this->_limit_str)) {
            $this->_limit_str = str_replace(' limit ', '', $this->_limit_str);
            $this->_limit_str = ' limit ' . $this->_limit_str;
        }
        if (!empty($this->_group_str)) {
            $this->_group_str = str_replace(' group by ', '', $this->_group_str);
            $this->_group_str = ' group by ' . $this->_group_str;
        }
        if (!empty($this->_field)) {
            $this->_field_str = implode(',', $this->_field);
        }
        $this->_current_exec_db = $this->_getQpDb();
        $this->_current_exec_table = $this->_getQpTableName();
    }

    /**
     * limit页返回数,用于limit返回
     */
    private function _getLimitReCount()
    {
        if (empty($this->_limit_str)) {
            return false;
        }
        $limit = str_replace(' limit ', '', $this->_limit_str);
        if (strstr($limit, ',')) {
            $limit = explode(',', $limit)[0];
        }
        return intval($limit);
    }

    /**
     * 分库
     * @return null|\PDO
     */
    private function _getQpDb()
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
            return null;  //返回这个代表没有规则，则需要全部db扫描了
        }
        $tableRule = $this->_tableRuleList[0];
        $tableShardingStrategyConfig = $tableRule->getDatabaseShardingStrategyConfig();
        $name = $tableShardingStrategyConfig->getName();
        $number = null;
        if (!empty($this->_condition)) {
            foreach ($this->_condition as $key => $val) {
                if ($key == $name && !is_array($val)) {
                    $number = $tableShardingStrategyConfig->getNum($val);
                }
            }
        } elseif (!empty($this->_insert_data)) {
            foreach ($this->_insert_data as $key => $val) {
                if ($key == $name && !is_array($val)) {
                    $number = $tableShardingStrategyConfig->getNum($val);
                }
            }
        }
        if ($number === null) {
            return null;  //返回这个代表没有规则，则需要全部db扫描了
        }
        $index = $tableShardingStrategyConfig->getFix() . $number;
        return $this->_databasePdoInstanceMap[$index];
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
        $name = $tableShardingStrategyConfig->getName();
        $number = null;
        if (!empty($this->_condition)) {
            foreach ($this->_condition as $key => $val) {
                if ($key == $name && !is_array($val)) {
                    $number = $tableShardingStrategyConfig->getNum($val);
                }
            }
        } elseif (!empty($this->_insert_data)) {
            foreach ($this->_insert_data as $key => $val) {
                if ($key == $name && !is_array($val)) {
                    $number = $tableShardingStrategyConfig->getNum($val);
                }
            }
        }
        if ($number === null) {
            return null;  //返回这个代表没有规则，则需要全部表扫描了
        }
        return $tableShardingStrategyConfig->getFix() . $number;
    }

    private function _search()
    {
        $this->_pare();
        if (!empty($this->_group_str)) {  //存在group by
            return $this->_groupSearch();
        }
        return $this->_defaultSearch();
    }


    /**
     * 获取orderBy字段
     * @return $order =>
     * [
     *    [
     *        'id'=>'asc',
     *        'create_time'=>'desc'
     *    ]
     * ]
     */
    private function _getOrderField()
    {
        $order = str_replace(' order by ', '', $this->_order_str);
        if (strstr($order, ',')) {
            $order = explode(',', $order);
        }
        if (is_array($order)) {  //多个order by
            foreach ($order as &$v) {
                $v = trim($v);
                $v = explode(' ', $v);
                $v = array_filter($v);  //去空值
            }
        } else {
            $order = trim($order);
            $order = explode(' ', $order);
            $order = array_filter($order); //去空值
            $order = [$order];
        }
        return $order;
    }


    /**
     * 分组搜索
     * @return array|boolean
     */
    private function _groupSearch()
    {
        $sqlArr = [];
        $groupField = $this->_getGroupField();
        $intersect = array_intersect($groupField, $this->_field);
        if (empty($intersect) && $this->_field_str != '*') {
            $this->_field_str .= ',' . $groupField[0];
        }
        if (empty($this->_current_exec_table) && empty($this->_table_name_index)) {  //全部扫描
            $sql = 'select ' . $this->_field_str . ' from ' . $this->_table_name . $this->_condition_str . $this->_group_str . $this->_order_str;
        } elseif (empty($this->_current_exec_table) && !empty($this->_table_name_index)) {
            foreach ($this->_table_name_index as $tableName) {
                $sqlArr[] = 'select ' . $this->_field_str . ' from ' . $tableName . $this->_condition_str . $this->_group_str . $this->_order_str;
            }
        } else {
            $sql = 'select ' . $this->_field_str . ' from ' . $this->_current_exec_table . $this->_condition_str . $this->_group_str . $this->_order_str;
        }
        empty($sql) && $sql = '';
        return $this->_groupShardingSearch($sqlArr, $sql);
    }


}