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

use PhpShardingPdo\Common\ShardingConst;
use PhpShardingPdo\Inter\ShardingInitConfigInter;

/**
 * Model基类
 * User: lys
 * Date: 2019/7/24
 * Time: 17:41
 */
class Model
{
    protected $tableName = '';
    protected $tableNameIndexConfig = [
        // 'index' => '1,2', //分表索引 index ,号分割
        //'range' => [1,2]  //范围
    ];
    protected $shardingInitConfigClass = '';  //自定义数据源类
    /**
     * @var ShardingPdo $dao
     */
    private $dao;

    /**
     * Model constructor.
     * @param string $tableName
     * @throws \Exception
     */
    public function __construct($tableName = '')
    {
        if (!empty($tableName)) {
            $this->tableName = $tableName;
        }
        $this->dao = $this->shardingInitConfig();
        $this->dao->table($this->tableName, $this->tableNameIndexConfig);
        $this->_init();
    }

    public function where($param)
    {
        $this->dao->where($param);
        return $this;
    }

    public function renew()
    {
        $this->dao->renew();
        $this->_init();
        return $this;
    }

    /**
     * @param int $offset
     * @param null $page_count
     * @return $this
     */
    public function limit($offset = 0, $page_count = null)
    {
        $this->dao->limit($offset, $page_count);
        return $this;
    }

    public function order($param)
    {
        $this->dao->order($param);
        return $this;
    }

    public function group($param)
    {
        $this->dao->group($param);
        return $this;
    }

    /**
     * 查询需要返回的字段
     * @param array $fields
     * @return $this
     *
     */
    public function field($fields = [])
    {
        $this->dao->field($fields);
        return $this;
    }

    /**
     * @return boolean|array
     */
    public function find()
    {
        return $this->dao->find();
    }

    public function findAll()
    {
        return $this->dao->findAll();
    }

    /**
     * @param $data
     * @return boolean|int
     */
    public function update($data)
    {
        return $this->dao->update($data);
    }

    /**
     * @param $data
     * @return boolean|int
     */
    public function insert($data)
    {
        return $this->dao->insert($data);
    }

    /**
     * @var boolean $isForcePhysicalDeletion //是否强制物理删除
     * @return boolean|int
     */
    public function delete($isForcePhysicalDeletion = false)
    {
        if (isset($this->softDeleteKey)
            && empty($isForcePhysicalDeletion)
            && method_exists($this, 'getSoftDeleteUpdate')
        ) {
            return $this->dao->update($this->getSoftDeleteUpdate());
        }
        return $this->dao->delete();
    }


    /**
     * 查找所有数据的条数
     * @return int
     */
    public function count($field_count = '')
    {
        return $this->dao->count($field_count);
    }

    /**
     * 自增
     * @param string $field
     * @param int $number
     * @return boolean|int
     */
    public function incr($field = '', $number = 1)
    {
        return $this->dao->incr($field, $number);
    }


    /**
     * 自减
     * @param string $field
     * @param int $number
     * @return boolean|int
     */
    public function decr($field = '', $number = 1)
    {
        return $this->dao->decr($field, $number);
    }


    /**
     * 获取最后插入的id,有可能是插入失败，返回0
     * @return int
     */
    public function getLastInsertId()
    {
        return $this->dao->getLastInsertId();
    }


    /**
     * 启动事务
     * @access public
     * @return void
     */
    public function startTrans()
    {
        $this->dao->startTrans();
        return;
    }

    /**
     * 提交事务
     * @access public
     * @return boolean
     */
    public function commit()
    {
        return $this->dao->commit();
    }

    /**
     * 事务回滚
     * @access public
     * @return boolean
     */
    public function rollback()
    {
        return $this->dao->rollback();
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
        return $this->dao->replaceInto($data);
    }


    /**
     * 获取sql执行的错误信息
     * @return array
     */
    public function sqlErrors()
    {
        return $this->dao->sqlErrors();
    }

    /**
     * 初始化分库分表数据源类
     * @return \PhpShardingPdo\Core\ShardingPdo
     * @throws \Exception $e
     */
    protected function shardingInitConfig()
    {
        if (empty($this->shardingInitConfigClass)) {
            throw new \Exception('请配置初始化shardingInitConfig的类');
        } else {
            /**
             * @var ShardingInitConfigInter $configClass
             */
            $configClass = $this->shardingInitConfigClass;
            return $configClass::init();
        }
    }

    /**
     * 重新连接
     */
    public function reconnection(callable $errorCallback = null)
    {
        /**
         * @var ShardingInitConfigInter $configClass
         */
        $configClass = $this->shardingInitConfigClass;
        $this->dao = $configClass::reconnection($errorCallback);
        $this->dao->table($this->tableName, $this->tableNameIndexConfig);
        return $this;
    }


    /**
     * 创建一个join的计划实体
     * @param array $condition //on条件比如 ['a.id' => 'b.product_id']
     * @return JoinTablePlan
     */
    public function createJoinTablePlan($condition = [])
    {
        return $this->dao->createJoinTablePlan($condition);
    }

    /**
     * 内连接
     * @param  $obj
     * @return $this
     */
    public function innerJoin(JoinTablePlan $obj)
    {
        $obj->setJoinType(ShardingConst::INNER_JOIN);
        $this->dao->addJoinPlanObj($obj);
        return $this;
    }


    /**
     * 左连接
     * @param  $obj
     * @return $this
     */
    public function leftJoin(JoinTablePlan $obj)
    {
        $obj->setJoinType(ShardingConst::LEFT_JOIN);
        $this->dao->addJoinPlanObj($obj);
        return $this;
    }

    /**
     * 右连接
     * @param  $obj
     * @return $this
     */
    public function rightJoin(JoinTablePlan $obj)
    {
        $obj->setJoinType(ShardingConst::RIGHT_JOIN);
        $this->dao->addJoinPlanObj($obj);
        return $this;
    }


    /**
     * join之后的 where 条件，该方法不会使用占位符，避免直接传递不安全的输入
     * @param $condition //请传递比如 ['table1.id' => 'table2.product_id']
     * @return $this
     */
    public function joinWhereCondition($condition = [])
    {
        $this->dao->setJoinCondition($condition);
        return $this;
    }

    /**
     * 设置表别名
     * @var $alias //别名
     * @return Model
     */
    public function alias($alias = '')
    {
        $this->dao->setTableNameAlias($alias);
        return $this;
    }

    /**
     * 表别名
     */
    public function getTableAlias()
    {
        return $this->dao->getTableAlias();
    }

    /**
     * 获取join字段别名
     * @param string $key //字段名称
     * @return string  // 如 join_table_name_1.id
     */
    public function getFieldAlias($key)
    {
        return $this->dao->getFieldAlias($key);
    }


    public function __clone()
    {
        $this->dao = clone $this->dao;
    }


    /**
     * Model初始化必要的参数
     */
    protected function _init()
    {
        if (!method_exists($this, 'getSoftDeleteCondition')) {
            return;
        }
        //软删除
        $condition = $this->getSoftDeleteCondition();
        $this->dao->where($condition);
    }
}
