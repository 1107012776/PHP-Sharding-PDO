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
        //$order1->commit();
        //$order1->commit();
        //var_dump($order);
        /*$xin = clone $order->renew();
        $xin1 = clone $order->renew();
        var_dump($xin===$xin1);*/
       //var_dump($order->find());
    }

    /**
     * 插入数据，支持事务嵌套
     * @throws \Exception
     */
    public function testInsert(){
        $order = new OrderModel();
        $order->startTrans();
        $order->startTrans();
        $insert = $order->renew()->insert(['user_id'=>1,'order_id'=>'1231','create_time'=>date('Y-m-d H:i:s')]);
        var_dump($insert,$order->getLastInsertId());
        $order->commit();
        $order->commit();
    }

    /**
     * 更新数据
     * @throws \Exception
     */
    public function testUpdate(){
        $order = new OrderModel();
        $order->startTrans();
        $order->startTrans();
        $res = $order->renew()->where(['id'=>3])->update(['create_time'=>date('Y-m-d H:i:s')]);
        var_dump($res);  //影响行数
        $order->commit();
        $order->commit();
    }


}
