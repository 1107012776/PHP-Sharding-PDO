<?php
/**
 * PhpShardingPdo  file.
 * @author linyushan  <1107012776@qq.com>
 * @link https://www.developzhe.com/
 * @package https://github.com/1107012776/PHP-Sharding-PDO
 * @copyright Copyright &copy; 2019-2021
 * @license https://github.com/1107012776/PHP-Sharding-PDO/blob/master/LICENSE
 */

namespace PhpShardingPdo\Test;

use PHPUnit\Framework\TestCase;

$file_load_path = __DIR__ . '/../../../autoload.php';
if (file_exists($file_load_path)) {
    include $file_load_path;
} else {
    $vendor = __DIR__ . '/../vendor/autoload.php';
    include $vendor;
}


/**
 * @method assertEquals($a, $b)
 */
class DemoTest extends TestCase
{

    /**
     * php vendor/bin/phpunit tests/DemoTest.php --filter testSelect
     * @throws
     */
    public function testSelect()
    {
        $order = new OrderModel();
        /*$res = $order->where(['user_id' => 2, 'order_id' => 2])->find();
        var_dump($res);
        $res = $order->renew()->where(['user_id' => 2, 'order_id' => 1])->find();
        var_dump($res);*/

        $res = $order->renew()->where(['id' => 3])->findAll();
        var_dump($res);
        //$dd = clone $order;
        $res = $order->renew()->order('id desc')->limit(1)->findAll();
        var_dump($res);
        var_dump($order->find());
    }

    public function testGroupBy()
    {
        $order = new OrderModel();
        $res = $order->renew()->field('order_id,sum(id),create_time,user_id')->group('order_id')->limit(100)->findAll();
        var_dump($res);
    }

    public function testOrderBy()
    {
        $order = new OrderModel();
        $res = $order->renew()->field('order_id,id,create_time,user_id')->order('order_id desc')->limit(5)->findAll();
        var_dump($res);
    }

    /**
     * 插入数据，支持事务嵌套
     * @throws \Exception
     * php vendor/bin/phpunit tests/DemoTest.php --filter testInsert
     */
    public function testInsert()
    {
        $order = new OrderModel();
        $user = new UserModel();
        $order->startTrans();
        $order->startTrans();
//        $order->reconnection();
        $insert = $order->renew()->insert(['user_id' => 1, 'order_id' => '1231', 'create_time' => date('Y-m-d H:i:s')]);
        var_dump($insert, $order->getLastInsertId());
//        $order->reconnection();
        /*        $insert = $user->renew()->insert(['user_id' => 2, 'order_id' => '1231', 'create_time' => date('Y-m-d H:i:s')]);
                var_dump($insert, $user->getLastInsertId());*/
        $insert = $order->renew()->insert(['user_id' => 1, 'order_id' => '1231', 'create_time' => date('Y-m-d H:i:s')]);
        var_dump($insert, $order->getLastInsertId());
//        $order->reconnection();
        /*        $insert = $user->renew()->insert(['user_id' => 2, 'order_id' => '1231', 'create_time' => date('Y-m-d H:i:s')]);
                var_dump($insert, $user->getLastInsertId());*/
        $insert = $order->renew()->insert(['user_id' => 1, 'order_id' => '1231', 'create_time' => date('Y-m-d H:i:s')]);
        var_dump($insert, $order->getLastInsertId());
        $user->commit();
        $user->commit();
    }

    /**
     * 更新数据
     * @throws \Exception
     */
    public function testUpdate()
    {
        $order = new OrderModel();
        $order->startTrans();
        $order->startTrans();
        $res = $order->renew()->where(['id' => 3])->update(['create_time' => date('Y-m-d H:i:s')]);
        var_dump($res);  //影响行数
        $order->commit();
        $order->commit();
    }

    /**
     * 删除数据
     * @throws \Exception
     */
    public function testDelete()
    {
        $order = new OrderModel();
        $order->startTrans();
        $order->startTrans();
        $res = $order->renew()->where(['id' => 9])->delete();
        var_dump($res);  //影响行数
        $order->commit();
        $order->commit();
    }

}
