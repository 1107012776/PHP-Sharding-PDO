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
use PhpShardingPdo\Core\SPDO;


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
    private static $_execSqlArr = 'transactionSharding_execSqlArr';  //事务中执行的sql
    private static $_execSqlTransactionUniqidFilePath = 'transactionSharding_execSqlTransactionUniqidFilePath';  //事务sql文件，用户分布式事务中错误之后的排查
    private static $_execSqlTransactionUniqidFilePathArr = 'transactionSharding_execSqlTransactionUniqidFilePathArr'; //真实允许中生成的事务sql文件路径，上面那个不是
    private static $_execXaXid = 'transactionSharding_execXaXid'; //xa xid

    public function initTrans()
    {
        ShardingPdoContext::setValue(self::$_startTransCount, 0);
        ShardingPdoContext::setValue(self::$_useDatabaseArr, []);
        ShardingPdoContext::setValue(self::$_execSqlArr, []);
        ShardingPdoContext::setValue(self::$_execSqlTransactionUniqidFilePathArr, []);
        ShardingPdoContext::setValue(self::$_execSqlTransactionUniqidFilePath, '');
        ShardingPdoContext::setValue(self::$_execXaXid, '');
    }

    /**
     * 启动事务
     * @access public
     * @return void
     */
    public function startTrans($xid = '')
    {
        if (ShardingPdoContext::getValue(self::$_startTransCount) <= 0) {  //发现是第一次开启事务，而非嵌套
            ShardingPdoContext::setValue(self::$_execSqlArr, []);  //在事务开启前，清理旧的sql执行记录
            $this->setXid($xid);
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
        $resArr = [];
        /**
         * @var \PDO $db
         */
        foreach ($useDatabaseArr as $db) {
            ShardingPdoContext::array_shift(self::$_useDatabaseArr);
//          throw new \Exception('中断则事务异常，产生事务日志');
            if (empty($this->getXid())) {
                $db->commit();
            } else {
                $resArr[] = $this->commitXa($db);
            }
        }
        $this->setXid('');
        if (!in_array(false, $resArr)) {
            $this->_delExeSqlLog(); //提交成功删除事务记录文件，如果没有删除成功，则说明中间存在事务提交失败
        } else {
            return false;
        }
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
        $resArr = [];
        /**
         * @var \PDO $db
         */
        foreach ($useDatabaseArr as $db) {
            ShardingPdoContext::array_shift(self::$_useDatabaseArr);
            if (empty($this->getXid()) && empty($xid)) {
                $db->rollBack();
            } else {
                $resArr[] = $this->rollbackXa($db);
            }
        }
        $this->setXid('');
        if (!in_array(false, $resArr)) {
            $this->_delExeSqlLog(); //提交成功删除事务记录文件，如果没有删除成功，则说明中间存在事务提交失败
        } else {
            return false;
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
     * @param \PDO $db
     * @return array|boolean
     */
    public function setUseDatabaseArr($db)
    {
        if (ShardingPdoContext::getValue(self::$_startTransCount) <= 0) {  //未开启事务
            return false;
        }
        if (in_array($db, self::getUseDatabaseArr())) {
            return self::getUseDatabaseArr();
        }
        ShardingPdoContext::array_push(self::$_useDatabaseArr, $db);
        /**
         * @var SPDO $db
         */
        $xid = ShardingPdoContext::getValue(self::$_execXaXid);
        if (empty($xid)) {
            $db->beginTransaction();
        } else {
            $xidStr = !strstr($xid, $db->getDatabaseName()) ? $xid . '_' . $db->getDatabaseName() : $xid;
            $sql = "xa start '$xidStr'";
            $this->_addExeSql($sql, [], $db);
            /**
             * @var SPDO $db
             */
            list($res, $statement) = static::exec($db, $sql);
            if (empty($res)) {
                $this->_sqlErrors[] = [$db->getDsn() => $statement->errorInfo(), 'xid' => $xidStr];
                return false;
            }
        }
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
     * 事务中预提交事务
     */
    private function _prepareSubmit()
    {
        if (empty(ShardingPdoContext::getValue(self::$_execSqlTransactionUniqidFilePath))) { //为空则不记录事务提交日志
            return false;
        }
        ShardingPdoContext::setValue(self::$_execSqlTransactionUniqidFilePathArr, []);  //每次事务预提交，清空旧的残留预提交，防止事务被串而删除
        $sqlArr = ShardingPdoContext::getValue(self::$_execSqlArr);
        if (empty($sqlArr)) {
            return false;
        }
        $log = 'START' . PHP_EOL;
        $log .= PHP_EOL;
        foreach ($sqlArr as $sql) {
            $log .= $sql;
        }
        $uniqid = spl_object_hash($this) . uniqid();
        $objHash = md5($uniqid) . sha1($uniqid);  //加上这个避免串事务
        $_execSqlTransactionUniqidFilePath = ShardingPdoContext::getValue(self::$_execSqlTransactionUniqidFilePath);
        $ext = pathinfo($_execSqlTransactionUniqidFilePath, PATHINFO_EXTENSION);
        $filePath = preg_replace('/\.' . $ext . '$/i', ShardingPdoContext::getCid() . '-' . $objHash . '-' . date('Y-m-d_H_i_s') . '.' . $ext, $_execSqlTransactionUniqidFilePath);
        ShardingPdoContext::array_push(self::$_execSqlTransactionUniqidFilePathArr, $filePath);
        file_put_contents($filePath, $log . PHP_EOL . 'END' . PHP_EOL, FILE_APPEND);
        ShardingPdoContext::setValue(self::$_execSqlArr, []);
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
            $ext = pathinfo($sqlLogPath, PATHINFO_EXTENSION);
            @file_put_contents(preg_replace('/\.' . $ext . '$/i', date('YmdH') . '.' . $ext, $sqlLogPath), $newSql, FILE_APPEND);
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
            $ext = pathinfo($sqlLogPath, PATHINFO_EXTENSION);
            @file_put_contents(preg_replace('/\.' . $ext . '$/i', date('YmdH') . '.' . $ext, $sqlLogPath), $newSql, FILE_APPEND);
        }
        ShardingPdoContext::array_push(self::$_execSqlArr, $newSql);
    }


    /**
     * 删除事务日志
     */
    private function _delExeSqlLog()
    {
        ShardingPdoContext::setValue(self::$_execSqlArr, []);
        $_execSqlTransactionUniqidFilePathArr = ShardingPdoContext::getValue(self::$_execSqlTransactionUniqidFilePathArr);
        foreach ($_execSqlTransactionUniqidFilePathArr as $filePath) {
            @unlink($filePath);
        }
        ShardingPdoContext::setValue(self::$_execSqlTransactionUniqidFilePathArr, []);
    }


    /**
     * 获取xid
     */
    public function getXid()
    {
        $xid = ShardingPdoContext::getValue(self::$_execXaXid);
        if (!empty($xid)) {
            return $xid;
        }
        return '';
    }

    /**
     * 设置xid
     * @param $setXid
     * @return string
     */
    public function setXid($setXid = '')
    {
        ShardingPdoContext::setValue(self::$_execXaXid, $setXid);
        return $setXid;
    }

    /**
     * xa将事务置于IDLE状态，表示事务内的SQL操作完成
     */
    public function endXa()
    {
        if (ShardingPdoContext::getValue(self::$_startTransCount) > 1) {
            return true;
        }
        $useDatabaseArr = ShardingPdoContext::getValue(self::$_useDatabaseArr);
        $xid = ShardingPdoContext::getValue(self::$_execXaXid);
        /**
         * @var SPDO $db
         */
        foreach ($useDatabaseArr as $index => $db) {
            $xidStr = !strstr($xid, $db->getDatabaseName()) ? $xid . '_' . $db->getDatabaseName() : $xid;
            $sql = "xa end '{$xidStr}'";
            $this->_addExeSql($sql, [], $db);
            list($res, $statement) = static::exec($db, $sql);
            if (empty($res)) {
                $this->_sqlErrors[] = [$db->getDsn() => $statement->errorInfo(), 'xid' => $xidStr];
                return false;
            }
        }
        return true;
    }

    /**
     * xa预提交
     */
    public function prepareXa()
    {
        if (ShardingPdoContext::getValue(self::$_startTransCount) > 1) {
            return true;
        }
        $useDatabaseArr = ShardingPdoContext::getValue(self::$_useDatabaseArr);
        empty($xid) && $xid = ShardingPdoContext::getValue(self::$_execXaXid);
        /**
         *
         * @var SPDO $db
         */
        foreach ($useDatabaseArr as $db) {
            $xidStr = !strstr($xid, $db->getDatabaseName()) ? $xid . '_' . $db->getDatabaseName() : $xid;
            $sql = "xa prepare '$xidStr'";
            $this->_addExeSql($sql, [], $db);
            list($res, $statement) = static::exec($db, $sql);
            if (empty($res)) {
                $this->_sqlErrors[] = [$db->getDsn() => $statement->errorInfo(), 'xid' => $xidStr];
                return false;
            }
        }
        return true;
    }

    public function commitXa($db)
    {
        $xid = ShardingPdoContext::getValue(self::$_execXaXid);
        /**
         * @var SPDO $db
         */
        $xidStr = !strstr($xid, $db->getDatabaseName()) ? $xid . '_' . $db->getDatabaseName() : $xid;
        $sql = sprintf("xa commit '%s'", $xidStr);
        $this->_addExeSql($sql, [], $db);
        list($res, $statement) = static::exec($db, $sql);
        if (empty($res)) {
            $this->_sqlErrors[] = [$db->getDsn() => $statement->errorInfo(), 'xid' => $xidStr];
            return false;
        }
        return true;
    }

    public function rollbackXa($db)
    {
        $xid = ShardingPdoContext::getValue(self::$_execXaXid);
        /**
         * @var SPDO $db
         */
        $xidStr = !strstr($xid, $db->getDatabaseName()) ? $xid . '_' . $db->getDatabaseName() : $xid;
        $sql = sprintf("xa rollback '%s'", $xidStr);
        $this->_addExeSql($sql, [], $db);
        list($res, $statement) = static::exec($db, $sql);
        if (empty($res)) {
            $this->_sqlErrors[] = [$db->getDsn() => $statement->errorInfo(), 'xid' => $xidStr];
            return false;
        }
        return true;
    }

    /**
     * 查看MySQL中存在的PREPARED状态的xa事务
     */
    public function recover()
    {
        $db = $this->_getQpDb();
        if (empty($db)) {
            return false;
        }
        if (!in_array($db, self::getUseDatabaseArr())) {
            ShardingPdoContext::array_push(self::$_useDatabaseArr, $db);
        }
        /**
         * @var SPDO $db
         */
        list($res, $statement) = static::exec($db, 'xa recover');
        /**
         * @var \PDOStatement $statement
         */
        if (empty($res)) {
            $this->_sqlErrors[] = [$db->getDsn() => $statement->errorInfo()];
            return false;
        }
        $data['list'] = $statement->fetchAll(\PDO::FETCH_ASSOC);
        !empty($data['list']) && $data['dsn'] = $db->getDsn();
        return $data;
    }

    /**
     * @param SPDO $db
     * @param $sql
     * @return array
     */
    public static function exec(SPDO $db, $sql)
    {
        $statement = $db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        /**
         * @var \PDOStatement $statement
         */
        $res = $statement->execute();
        if (empty($res)) {
            return [false, $statement];
        }
        return [true, $statement];
    }
}
