<?php

namespace PhpShardingPdo\Test\Migrate\build;

use PhpShardingPdo\Common\ConfigEnv;
use PhpShardingPdo\Test\Migrate\Inter\CreateInter;

/**
 * 创建数据库
 * Class DatabaseCreate
 * @package PhpShardingPdo\Test\Migrate
 */
class DatabaseCreate implements CreateInter
{
    /**
     * @var \PDO
     */
    private $conn;
    public static $databaseNameMap = [
        'phpshardingpdo1',
        'phpshardingpdo2',
        'phpshardingpdo3',
        'phpshardingpdo4',
    ];

    public function connect()
    {
        $servername = ConfigEnv::get('database.host', "localhost");
        $username = ConfigEnv::get('database.username', "root");
        $password = ConfigEnv::get('database.password', "");
        try {
            $this->conn = new \PDO('mysql:host=' . $servername, $username, $password);
            // 设置 PDO 错误模式为异常
            $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            echo var_export($e->getTraceAsString(), true) . PHP_EOL;
        }
    }

    public function build()
    {
        $this->connect();
        $this->create();
    }

    public function create()
    {
        $database = 'phpshardingpdo';
        $i = 0;
        $databaseName = $database . (++$i);
        $sql = "drop database if exists {$databaseName};CREATE DATABASE {$databaseName} CHARACTER SET utf8mb4";
        // 使用 exec() ，因为没有结果返回
        $this->conn->exec($sql);
        $databaseName = $database . (++$i);
        $sql = "drop database if exists {$databaseName};CREATE DATABASE {$databaseName}  CHARACTER SET utf8mb4";
        // 使用 exec() ，因为没有结果返回
        $this->conn->exec($sql);
        $databaseName = $database . (++$i);
        $sql = "drop database if exists {$databaseName};CREATE DATABASE {$databaseName}  CHARACTER SET utf8mb4";
        // 使用 exec() ，因为没有结果返回
        $this->conn->exec($sql);
        $databaseName = $database . (++$i);
        $sql = "drop database if exists {$databaseName};CREATE DATABASE {$databaseName}  CHARACTER SET utf8mb4";
        // 使用 exec() ，因为没有结果返回
        $this->conn->exec($sql);
    }

    public static function getConn($databaseName)
    {
        $servername = ConfigEnv::get('database.host', "localhost");
        $username = ConfigEnv::get('database.username', "root");
        $password = ConfigEnv::get('database.password', "");
        $dsn = "mysql:host=$servername;dbname=$databaseName;port=3306;charset=utf8mb4";
        try {
            $dbh = new \PDO($dsn, $username, $password); //初始化一个PDO对象
            //默认这个不是长连接，如果需要数据库长连接，需要最后加一个参数：array(PDO::ATTR_PERSISTENT => true) 变成这样：
            //$this->dbh = new PDO($dsn, $user, $pass, array(PDO::ATTR_PERSISTENT => true));
            $dbh->query('set names utf8mb4;');
            return $dbh;
        } catch (\PDOException $e) {
            die ("Error!: " . $e->getMessage() . "<br/>");
        }
    }
}

