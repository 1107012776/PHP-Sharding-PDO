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

$file_load_path = __DIR__.'/../../../autoload.php';
if (file_exists($file_load_path)) {
    include $file_load_path;
} else {
    $vendor = __DIR__.'/../vendor/autoload.php';
    include $vendor;
}


/**
 * @method assertEquals($a, $b)
 */
class DemoTest1 extends TestCase
{

    /**
     * php vendor/bin/phpunit tests/DemoTest.php --filter testSelect
     * @throws
     */
    public function testArticle()
    {
        $countModelList = new ArticleModel();
        $condition = ['del_flag' => 0];
        $page = 4;
        $limit = 10;
        $list = $countModelList->where($condition)->limit(($page-1)*$limit,$limit)->order('id desc')->findAll();
        var_dump(array_column($list,'id'));


    }


    /**
     * php vendor/bin/phpunit tests/DemoTest.php --filter testSelect
     * @throws
     */
    public function testClone()
    {
        $countModelList = new ArticleModel();
        $condition = ['del_flag' => 0];
        $page = 4;
        $limit = 1;
        $countModelList->where($condition)->limit(($page-1)*$limit,$limit)->order('id desc');
        $cloneModel = clone $countModelList;
        $list = $cloneModel->findAll();
        var_dump(array_column($list,'id'));


    }

    public function testAdmin()
    {
        $model = new ArticleModel();

        $data = [
            'article_title' => '挖词',
            'article_keyword' => '挖词',
            'article_descript' => '挖词',
            'author' => '挖词',
            'article_img' => 'http://wqweqwdadqwdwqdwqdwqd.com/1.jpg',
            'content' => '11111',
            'content_md' => '11111',
        ];

        $data['cate_id'] = 3;
        $data['update_time'] = $data['create_time'] = date('Y-m-d H:i:s');
        $res =$model->insert($data);
        var_dump($res);

    }

    public function testAuth()
    {
        $model = new ArticleModel();

        $data = [
            'article_title' => '挖词',
            'article_keyword' => '挖词',
            'article_descript' => '挖词',
            'author' => '挖词',
            'article_img' => 'http://wqweqwdadqwdwqdwqdwqd.com/1.jpg',
            'content' => '11111',
            'content_md' => '11111',
        ];

        $data['cate_id'] = 3;
        $data['update_time'] = $data['create_time'] = date('Y-m-d H:i:s');

        $auModel = new AutoDistributedModel();
        $data1 = ['stub' => 'b'];
        $res = $auModel->replaceInto($data1);
        var_dump($res,$auModel->getLastInsertId());
        $data['id'] = $auModel->getLastInsertId();
        $res =$model->insert($data);
        var_dump($res,$model->getLastInsertId());

    }

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
        $insert = $order->renew()->insert(['user_id' => 1, 'order_id' => '1231', 'create_time' => date('Y-m-d H:i:s')]);
        var_dump($insert, $order->getLastInsertId());
        $insert = $user->renew()->insert(['user_id' => 2, 'order_id' => '1231', 'create_time' => date('Y-m-d H:i:s')]);
        var_dump($insert, $user->getLastInsertId());
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
        $model = new ArticleModel();

        $model->startTrans();
        $model->startTrans();
        $res = $model->renew()->where(['id' => 160])->delete();
        var_dump($res);  //影响行数
        $model->commit();
        $model->commit();
    }

}
