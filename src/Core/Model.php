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
     * 查找所有数据的条数
     * @return int
     */
    public function count()
    {
        return $this->dao->count();
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
    public function replaceInto($data){
        return $this->dao->replaceInto($data);
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

    /**
     * 重新连接
     */
    public function  reconnection(callable $errorCallback = null){
        /**
         * @var ShardingInitConfigInter $configClass
         */
        $configClass = $this->shardingInitConfigClass;
        $this->dao = $configClass::reconnection($errorCallback);
        $this->dao->table($this->tableName, $this->tableNameIndexConfig);
        return $this;
    }

    public function __clone()
    {
        // TODO: Implement __clone() method.
        $this->renew();
    }
}