<?php

namespace PhpShardingPdo\Core;

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
    }

    public function where($param)
    {
        $this->dao->where($param);
        return $this;
    }

    public function renew()
    {
        $this->dao->renew();
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
     * @return boolean|int
     */
    public function delete()
    {
        return $this->dao->delete();
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
     * 初始化分库分表数据源类
     * @return \pdo\sharding\ShardingPdo
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

    public function __clone()
    {
        // TODO: Implement __clone() method.
        $this->renew();
    }
}