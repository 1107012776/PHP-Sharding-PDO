<?php
/**
 * PhpShardingPdo  file.
 * @author linyushan  <1107012776@qq.com>
 * @link https://www.developzhe.com/
 * @package https://github.com/1107012776/PHP-Sharding-PDO
 * @copyright Copyright &copy; 2019-2021
 * @license https://github.com/1107012776/PHP-Sharding-PDO/blob/master/LICENSE
 */

namespace PhpShardingPdo\Components;


use PhpShardingPdo\Common\ConfigEnv;
use PhpShardingPdo\Core\ShardingPdoContext;


/**
 * 事务管理，分库分表之后
 * User: linyushan
 * Date: 2019/8/6
 * Time: 16:22
 */
trait TransactionShardingTrait
{

    private static $_startTransCount = 'transactionSharding_startTransCount'; //事务开启统计
    private static $_useDatabaseArr = 'transactionSharding_useDatabase';  //已被使用的数据库PDO对象source,用于事务操作
    private static $_exeSqlArr = 'transactionSharding_exeSqlArr';  //事务中执行的sql
    private static $_exeSqlXaUniqidFilePath = 'transactionSharding_exeSqlXaUniqidFilePath';  //事务sql文件，用户分布式事务中错误之后的排查
    private static $_exeSqlXaUniqidFilePathArr = 'transactionSharding_exeSqlXaUniqidFilePathArr'; //真实允许中生成的xa文件路径，上面那个非

    public function initTrans()
    {
        ShardingPdoContext::setValue(self::$_startTransCount, 0);
        ShardingPdoContext::setValue(self::$_useDatabaseArr, []);
        ShardingPdoContext::setValue(self::$_exeSqlArr, []);
        ShardingPdoContext::setValue(self::$_exeSqlXaUniqidFilePathArr, []);
        ShardingPdoContext::setValue(self::$_exeSqlXaUniqidFilePath, '');
    }

    /**
     * 启动事务
     * @access public
     * @return void
     */
    public function startTrans()
    {
        if (ShardingPdoContext::getValue(self::$_startTransCount) <= 0) {  //发现是第一次开启事务，而非嵌套
            ShardingPdoContext::setValue(self::$_exeSqlArr, []);  //在事务开启前，清理旧的sql执行记录
        }
        ShardingPdoContext::incrValue(self::$_startTransCount);
        return;
    }

    /**
     * 提交事务
     * @access public
     * @return boolean
     */
    public function commit()
    {
        ShardingPdoContext::decrValue(self::$_startTransCount);
        if (ShardingPdoContext::getValue(self::$_startTransCount) > 0) {
            return true;
        }
        $this->_prepareSubmit(); //预提交事务
        $useDatabaseArr = ShardingPdoContext::getValue(self::$_useDatabaseArr);
        /**
         * @var \PDO $db
         */
        foreach ($useDatabaseArr as $db) {
            ShardingPdoContext::array_shift(self::$_useDatabaseArr);
//          throw new \Exception('中断则事务异常，产生xa日志');
            $db->commit();
        }
        $this->_delExeSqlLog(); //提交成功删除事务记录文件，如果没有删除成功，则说明中间存在事务提交失败
        return true;
    }

    /**
     * 事务回滚
     * @access public
     * @return boolean
     */
    public function rollback()
    {
        ShardingPdoContext::decrValue(self::$_startTransCount);
        if (ShardingPdoContext::getValue(self::$_startTransCount) > 0) {
            return true;
        }
        $useDatabaseArr = ShardingPdoContext::getValue(self::$_useDatabaseArr);
        /**
         * @var \PDO $db
         */
        foreach ($useDatabaseArr as $db) {
            ShardingPdoContext::array_shift(self::$_useDatabaseArr);
            $db->rollBack();
        }
        return true;
    }

    /**
     * 获取已被使用的数据库pdo
     */
    public static function getUseDatabaseArr()
    {
        return ShardingPdoContext::getValue(self::$_useDatabaseArr);
    }

    /**
     * 设置使用的数据库pdo
     * @return array|boolean
     * @var \PDO $db
     */
    public static function setUseDatabaseArr($db)
    {
        if (ShardingPdoContext::getValue(self::$_startTransCount) <= 0) {  //未开启事务
            return false;
        }
        if (in_array($db, self::getUseDatabaseArr())) {
            return self::getUseDatabaseArr();
        }
        $db->beginTransaction();
        ShardingPdoContext::array_push(self::$_useDatabaseArr, $db);
        return ShardingPdoContext::getValue(self::$_useDatabaseArr);
    }


    /**
     * 防止sql注入自定义方法三
     * author: xiaochuan
     * @https://www.cnblogs.com/lemon66/p/4224892.html
     * @param: mixed $value 参数值
     */
    private function _sqlAddslashes($value)
    {
        $value = addslashes($value);
        return $value;
    }

    /**
     * xa事务中预提交事务
     */
    private function _prepareSubmit()
    {
        if (empty(ShardingPdoContext::getValue(self::$_exeSqlXaUniqidFilePath))) { //为空则不记录xa提交日志
            return false;
        }
        ShardingPdoContext::setValue(self::$_exeSqlXaUniqidFilePathArr, []);  //每次事务预提交，清空旧的残留预提交，防止事务被串而删除
        $log = 'START' . PHP_EOL;
        $log .= PHP_EOL;
        foreach (ShardingPdoContext::getValue(self::$_exeSqlArr) as $sql) {
            $log .= $sql;
        }
        $uniqid = spl_object_hash($this) . uniqid();
        $objHash = md5($uniqid) . sha1($uniqid);  //加上这个避免串事务
        $_exeSqlXaUniqidFilePath = ShardingPdoContext::getValue(self::$_exeSqlXaUniqidFilePath);
        $filePath = str_replace('.log', ShardingPdoContext::getCid() . '-' . $objHash . '-' . date('Y-m-d_H_i_s') . '.log', $_exeSqlXaUniqidFilePath);
        ShardingPdoContext::array_push(self::$_exeSqlXaUniqidFilePathArr, $filePath);
        file_put_contents($filePath, $log . PHP_EOL . 'END' . PHP_EOL, FILE_APPEND);
        ShardingPdoContext::setValue(self::$_exeSqlArr, []);
    }

    /**
     * 添加查询的sql
     */
    private function _addSelectSql($sql, $bindParams, $pdoObj = null)
    {
        /**
         * @var \PhpShardingPdo\Core\SPDO $pdoObj
         */
        method_exists($pdoObj, 'getDsn') ? $dsn = $pdoObj->getDsn() : $dsn = '';
        $exeSql = $sql;
        foreach ($bindParams as $bKey => $bVal) {
            $bVal = $this->_sqlAddslashes($bVal);
            $exeSql = str_replace($bKey, "'$bVal'", $exeSql);
        }
        $newSql = date('Y-m-d H:i:s', time()) . '[' . $dsn . ']: ' . PHP_EOL . $exeSql . ';' . PHP_EOL;
        $sqlLogPath = ConfigEnv::get('shardingPdo.sqlLogPath');
        $sqlLogOpen = ConfigEnv::get('shardingPdo.sqlLogOpen', false);
        if (!empty($sqlLogPath) && $sqlLogOpen) {
            @file_put_contents(str_replace('.sql', date('YmdH') . '.sql', $sqlLogPath), $newSql, FILE_APPEND);
        }
    }


    /**
     * 添加执行的sql
     */
    private function _addExeSql($sql, $bindParams, $pdoObj = null)
    {
        /**
         * @var \PhpShardingPdo\Core\SPDO $pdoObj
         */
        method_exists($pdoObj, 'getDsn') ? $dsn = $pdoObj->getDsn() : $dsn = '';
        $exeSql = $sql;
        foreach ($bindParams as $bKey => $bVal) {
            $bVal = $this->_sqlAddslashes($bVal);
            $exeSql = str_replace($bKey, "'$bVal'", $exeSql);
        }
        $newSql = date('Y-m-d H:i:s', time()) . '[' . $dsn . ']: ' . PHP_EOL . $exeSql . ';' . PHP_EOL;
        $sqlLogPath = ConfigEnv::get('shardingPdo.sqlLogPath');
        $sqlLogOpen = ConfigEnv::get('shardingPdo.sqlLogOpen', false);
        if (!empty($sqlLogPath) && $sqlLogOpen) {
            @file_put_contents(str_replace('.sql', date('YmdH') . '.sql', $sqlLogPath), $newSql, FILE_APPEND);
        }
        ShardingPdoContext::array_push(self::$_exeSqlArr, $newSql);
    }


    /**
     * 删除事务日志
     */
    private function _delExeSqlLog()
    {
        ShardingPdoContext::setValue(self::$_exeSqlArr, []);
        $_exeSqlXaUniqidFilePathArr = ShardingPdoContext::getValue(self::$_exeSqlXaUniqidFilePathArr);
        foreach ($_exeSqlXaUniqidFilePathArr as $filePath) {
            @unlink($filePath);
        }
        ShardingPdoContext::setValue(self::$_exeSqlXaUniqidFilePathArr, []);
    }
}
