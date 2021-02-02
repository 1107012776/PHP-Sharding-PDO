<?php

namespace PhpShardingPdo\Components;
/**
 * 事务管理，分库分表之后
 * User: linyushan
 * Date: 2019/8/6
 * Time: 16:22
 */
trait TransactionShardingTrait
{

    private  $_startTransCount = 0; //事务开启统计
    private  $_useDatabaseArr = [];  //已被使用的数据库PDO对象source,用于事务操作
    private  $_exeSqlArr = [];  //事务中执行的sql
    private  $_exeSqlXaUniqidFilePath = '';  //事务sql文件，用户分布式事务中错误之后的排查

    /**
     * 启动事务
     * @access public
     * @return void
     */
    public function startTrans()
    {
        $this->_startTransCount++;
        return;
    }

    /**
     * 提交事务
     * @access public
     * @return boolean
     */
    public function commit()
    {
        $this->_startTransCount--;
        if ($this->_startTransCount > 0) {
            return true;
        }
        $this->_prepareSubmit(); //预提交事务
        /**
         * @var \PDO $db
         */
        foreach ($this->_useDatabaseArr as $db) {
            array_shift($this->_useDatabaseArr);
//            throw new \Exception('213213');
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
        $this->_startTransCount--;
        if ($this->_startTransCount > 0) {
            return true;
        }
        /**
         * @var \PDO $db
         */
        foreach ($this->_useDatabaseArr as $db) {
            array_shift($this->_useDatabaseArr);
            $db->rollBack();
        }
        return true;
    }

    /**
     * 获取已被使用的数据库pdo
     */
    public  function getUseDatabaseArr()
    {
        return $this->_useDatabaseArr;
    }

    /**
     * 设置使用的数据库pdo
     * @return array|boolean
     * @var \PDO $db
     */
    public  function setUseDatabaseArr($db)
    {
        if ($this->_startTransCount <= 0) {  //未开启事务
            return false;
        }
        if (in_array($db, $this->_useDatabaseArr)) {
            return $this->_useDatabaseArr;
        }
        $db->beginTransaction();
        array_push($this->_useDatabaseArr, $db);
        return $this->_useDatabaseArr;
    }


    /**
     * 防止sql注入自定义方法三
     * author: xiaochuan
     * @https://www.cnblogs.com/lemon66/p/4224892.html
     * @param: mixed $value 参数值
     */
    private function _sqlAddslashes($value)
    {
        if (!get_magic_quotes_gpc()) {  //函数在php中的作用是判断解析用户提示的数据，如包括有:post、get、cookie过来的数据增加转义字符“ ”，以确保这些数据不会引起程序，特别是数据库语句因为特殊字符引起的污染而出现致命的错误
            // 进行过滤
            $value = addslashes($value);
        }
        $value = str_replace("_", "\_", $value);
        $value = str_replace("%", "\%", $value);
        $value = nl2br($value);
        $value = htmlspecialchars($value);
        return $value;
    }

    /**
     * xa事务中预提交事务
     */
    private function _prepareSubmit()
    {
        $unqi = uniqid(time(), true);
        $unqi = str_replace('.', '', $unqi);
        if (empty($this->_exeSqlXaUniqidFilePath)) {
//            $this->_exeSqlXaUniqidFilePath = './' . date('YmdHis') . $unqi . '.log';
            return false;
        }
//        var_dump($this->_exeSqlXaUniqidFilePath,$this->_exeSqlArr);
        foreach ($this->_exeSqlArr as $sql) {
            file_put_contents($this->_exeSqlXaUniqidFilePath, $sql, FILE_APPEND);
        }
    }


    /**
     * 添加执行的sql
     */
    private function _addExeSql($sql, $bindParams)
    {
        $exeSql = $sql;
        foreach ($bindParams as $bKey => $bVal) {
            $bVal = $this->_sqlAddslashes($bVal);
            $exeSql = str_replace($bKey, "'$bVal'", $exeSql);
        }
        $this->_exeSqlArr[] = date('Y-m-d H:i:s') . ': ' . $exeSql . PHP_EOL;
    }


    /**
     * 删除事务日志
     */
    private function _delExeSqlLog()
    {
        $this->_exeSqlArr = [];
        @unlink($this->_exeSqlXaUniqidFilePath);
    }
}