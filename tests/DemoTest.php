<?php
namespace PhpShardingPdo\Test;
use PHPUnit\Framework\TestCase;

$file_load_path = '../../../autoload.php';
if (file_exists($file_load_path)) {
    include $file_load_path;
} else {
    include '../vendor/autoload.php';
}


/**
 * @method assertEquals($a, $b)
 */
class DemoTest extends TestCase
{
    protected function setUp()
    {


    }

    public function testEEE(){
        //连接
        $options = array(
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, //默认是PDO::ERRMODE_SILENT, 0, (忽略错误模式)
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,   // 默认是PDO::FETCH_BOTH, 4
        );
        $dbms = 'mysql';     //数据库类型
        $host = 'localhost'; //数据库主机名
        $dbName = 'pybbs-go';    //使用的数据库
        $user = 'root';      //数据库连接用户名
        $pass = '123456';          //对应的密码
        $dsn = "$dbms:host=$host;dbname=$dbName;port=3306;charset=utf8";
        try {
            $dbh = new \PDO($dsn, $user, $pass, $options); //初始化一个PDO对象
            //默认这个不是长连接，如果需要数据库长连接，需要最后加一个参数：array(PDO::ATTR_PERSISTENT => true) 变成这样：
            //$this->dbh = new PDO($dsn, $user, $pass, array(PDO::ATTR_PERSISTENT => true));
            $dbh->query('set names utf8;');
            return $dbh;
        } catch (\PDOException $e) {
            die ("2Error!: " . $e->getMessage() . "<br/>");
        }
    }

    /**
     * php vendor/bin/phpunit tests/ConsumerTest.php --filter testSelect
     * @throws
     */
    public function testSelect()
    {
        $order = new OrderModel();
        /*$res = $order->where(['user_id' => 2, 'order_id' => 2])->find();
        var_dump($res);
        $res = $order->renew()->where(['user_id' => 2, 'order_id' => 1])->find();
        var_dump($res);*/
        //$res = $order->renew()->field('order_id,sum(id),create_time,user_id')->group('order_id')->limit(100)->findAll();
       //var_dump($res);
        /*$insert = $order->renew()->insert(['create_time'=>date('Y-m-d H:i:s')]);
        var_dump($insert);*/
       //$res = $order->renew()->where(['id'=>3])->update(['create_time'=>date('Y-m-d H:i:s')]);
        //var_dump($res);
        $res = $order->renew()->where(['id'=>3])->findAll();
        var_dump($res);

        //$dd = clone $order;
        $res = $order->renew()->order('id desc')->limit(1)->findAll();
        var_dump($res);
        var_dump($order->find());
        var_dump($order->find());
        $order->startTrans();
        $order->startTrans();
        $insert = $order->renew()->insert(['user_id'=>1,'order_id'=>'1231','create_time'=>date('Y-m-d H:i:s')]);
        var_dump($insert,$order->getLastInsertId());

        //$order1->commit();
        //$order1->commit();
        //var_dump($order);
        /*$xin = clone $order->renew();
        $xin1 = clone $order->renew();

        var_dump($xin===$xin1);*/
       //var_dump($order->find());
    }


}
