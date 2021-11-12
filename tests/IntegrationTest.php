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
ini_set("display_errors", "On");

error_reporting(E_ALL); //显示所有错误信息
ini_set('date.timezone', 'Asia/Shanghai');

use PhpShardingPdo\Common\ConfigEnv;
use PhpShardingPdo\Test\Migrate\Migrate;
use PhpShardingPdo\Test\Model\ArticleModel;
use PHPUnit\Framework\TestCase;

$file_load_path = __DIR__ . '/../../../autoload.php';
if (file_exists($file_load_path)) {
    require_once $file_load_path;
} else {
    $vendor = __DIR__ . '/../vendor/autoload.php';
    require_once $vendor;
}

ConfigEnv::loadFile(dirname(__FILE__) . '/Config/.env');  //加载配置

/**
 * @method assertEquals($a, $b)
 */
class IntegrationTest extends TestCase
{
    private $article_title1 = '测试数据article_title1';
    private $article_title2 = '测试数据article_title2';

    /**
     * 重新构建数据库
     * php vendor/bin/phpunit tests/IntegrationTest.php --filter testBuild
     */
    public function testBuild()
    {
        $res = Migrate::build();
        $this->assertEquals($res, true);
    }

    /**
     * 一键启动测试
     * php vendor/bin/phpunit tests/IntegrationTest.php --filter testExecStart
     */
    public function testExecStart()
    {
        $this->testBuild();
        $this->testInsert();
        $this->testSelectFind();
        $this->testSelectFindAll();
        $this->testSelectOrderFindAll();
        $this->testSelectGroupFindAll();
        $this->testSelectGroupOrderFindAll();
        $this->testSelectGroupOrderLimitFindAll();
        $this->testUpdateDelete();
        $this->testLike();
    }

    /**
     * 插入测试
     * php vendor/bin/phpunit tests/IntegrationTest.php --filter testInsert
     */
    public function testInsert()
    {
        $this->insert(1, $this->article_title1);
        $this->insert(1, $this->article_title1);
        $this->insert(2, $this->article_title1);
        $this->insert(3, $this->article_title1);
    }

    public function insert($cate_id, $article_title)
    {
        $model = new \PhpShardingPdo\Test\Model\ArticleModel();
        $data = [
            'article_descript' => '测试数据article_descript',
            'article_img' => '/upload/2021110816311943244.jpg',
            'article_keyword' => '测试数据article_keyword',
            'article_title' => $article_title,
            'author' => '学者',
            'cate_id' => $cate_id,
            'content' => '<p>测试数据</p><br/>',
            'content_md' => '测试数据',
            'create_time' => "2021-11-08 16:31:20",
            'update_time' => "2021-11-08 16:31:20",
            'user_id' => $this->testUserId(),
        ];
        $data['id'] = $this->testGetId(2);
        $res = $model->renew()->insert($data);
        $this->assertEquals(!empty($res), true);
        return $data['id'];
    }

    public function testGetId($stub = 1)
    {
        $autoModel = new \PhpShardingPdo\Test\Model\AutoDistributedModel();
        while (true) {
            $resReplaceInto = $autoModel->replaceInto(['stub' => $stub]);
            if (empty($resReplaceInto)) {
                usleep(50);
                continue;
            }
            break;
        }
        $this->assertEquals($autoModel->getLastInsertId() > 0, true);
        return $autoModel->getLastInsertId();
    }

    public function testUserId()
    {
        $model = new \PhpShardingPdo\Test\Model\UserModel();
        $model->startTrans();
        $accountModel = new \PhpShardingPdo\Test\Model\AccountModel();
        $username = 'test_' . date('YmdHis') . uniqid();
        $id = $this->testGetId(1);
        $data = [
            'username' => $username,
            'password' => date('YmdHis'),
            'email' => 'test@163.com',
            'nickname' => '学者',
            'id' => $id,
        ];
        $res = $model->insert($data);
        if (empty($res)) {
            $model->rollback();
            var_dump($model->sqlErrors());
        }
        $this->assertEquals(!empty($res), true);
        $res = $accountModel->insert([
            'username' => $username,
            'id' => $id
        ]);
        if (empty($res)) {
            $model->rollback();
            var_dump($accountModel->sqlErrors());
        }
        $this->assertEquals(!empty($res), true);
        $model->commit();
        return $id;
    }

    public function testSelectFind()
    {
        $model = new \PhpShardingPdo\Test\Model\ArticleModel();
        $info = $model->where([
            'cate_id' => 1
        ])->where(['article_title' => $this->article_title1])
            ->find();
        $this->assertEquals(!empty($info), true);
        $count = $model->renew()->where([
            'cate_id' => 1,
        ])->limit(1)->where(['article_title' => $this->article_title1])
            ->count();
        $this->assertEquals($count == 2, true);
    }

    public function testSelectFindAll()
    {
        $model = new \PhpShardingPdo\Test\Model\ArticleModel();
        $list = $model->where([
            'cate_id' => 1
        ])->findAll();
        $this->assertEquals(count($list) == 2, true);
        $count = $model->renew()->where([
            'cate_id' => 1
        ])->where(['article_title' => $this->article_title1])
            ->count();
        $this->assertEquals($count == 2, true);
        $count = $model->renew()->where([
            'cate_id' => ['gt', 1]
        ])->where(['article_title' => $this->article_title1])
            ->count();
        $this->assertEquals($count == 2, true);
        $count = $model->renew()->where([
            'cate_id' => ['between', [1, 3]]
        ])->where(['article_title' => $this->article_title1])
            ->count();
        $this->assertEquals($count == 4, true);
        $count = $model->renew()->where([
            'cate_id' => ['notBetween', [1, 3]]
        ])->where(['article_title' => $this->article_title1])
            ->count();
        $this->assertEquals($count == 0, true);
        $count = $model->renew()->where([
            'cate_id' => ['neq', [1, 3]]
        ])->where(['article_title' => $this->article_title1])
            ->count();
        $this->assertEquals($count == 1, true);
        $count = $model->renew()->where([
            'cate_id' => ['egt', 1]
        ])->where(['article_title' => $this->article_title1])
            ->count();
        $this->assertEquals($count == 4, true);
        $count = $model->renew()->where([
            'cate_id' => ['elt', 2]
        ])->where(['article_title' => $this->article_title1])
            ->count();
        $this->assertEquals($count == 3, true);
        $count = $model->renew()->where([
            'cate_id' => ['lt', 2]
        ])->where(['article_title' => $this->article_title1])
            ->count();
        $this->assertEquals($count == 2, true);
        $count = $model->renew()->where([
            'cate_id' => ['gt', 2]
        ])->where(['article_title' => $this->article_title1])
            ->count();
        $this->assertEquals($count == 1, true);
        $count = $model->renew()->where([
            'cate_id' => ['in', [1, 2]]
        ])->where(['article_title' => $this->article_title1])
            ->count();
        $this->assertEquals($count == 3, true);
        $count = $model->renew()->where([
            'cate_id' => ['notIn', [1, 2]]
        ])->where(['article_title' => $this->article_title1])
            ->count();
        $this->assertEquals($count == 1, true);
        $count = $model->renew()->where([
            'cate_id' => ['is', null]
        ])->where(['article_title' => $this->article_title1])
            ->count();
        $this->assertEquals($count == 0, true);
        $count = $model->renew()->where([
            'cate_id' => ['isNot', null]
        ])->where(['article_title' => $this->article_title1])
            ->count();
        $this->assertEquals($count == 4, true);
        $count = $model->renew()->where([
            'cate_id' => 1
        ])->where(['article_title' => $this->article_title1])
            ->where(['cate_id' => 2])
            ->count();
        $this->assertEquals($count == 0, true);
        $count = $model->renew()->where([
            'cate_id' => ['gt', 1]
        ])->where(['article_title' => $this->article_title1])
            ->where(['cate_id' => ['lt', 3]])
            ->count();
        $this->assertEquals($count == 1, true);
    }

    public function testSelectOrderFindAll()
    {
        $model = new \PhpShardingPdo\Test\Model\ArticleModel();
        $list = $model->where([
            'cate_id' => 1
        ])
            ->where(['article_title' => $this->article_title1])
            ->order('update_time desc')->findAll();
        $this->assertEquals(!empty($list), true);
        $model = new \PhpShardingPdo\Test\Model\ArticleModel();
        $list = $model->where([
            'cate_id' => 1
        ])
            ->where(['article_title' => $this->article_title1])
            ->limit(1)
            ->order('update_time desc')->findAll();
        $this->assertEquals(count($list) === 1, true);
    }


    public function testSelectGroupFindAll()
    {
        $model = new \PhpShardingPdo\Test\Model\ArticleModel();
        $list = $model->where([
            'cate_id' => 1,
        ])
            ->where(['article_title' => $this->article_title1])
            ->group('article_title')->findAll();
        $this->assertEquals(!empty($list), true);
    }

    public function testSelectGroupOrderFindAll()
    {
        $model = new \PhpShardingPdo\Test\Model\ArticleModel();
        $list = $model->field('article_title,sum(is_choice) as choice')
            ->where(['article_title' => $this->article_title1])
            ->order('article_title desc')
            ->group('article_title')->findAll();
        $this->assertEquals(count($list) == 1, true);
    }


    public function testSelectGroupOrderLimitFindAll()
    {
        $model = new \PhpShardingPdo\Test\Model\ArticleModel();
        $list = $model->field('article_title,sum(is_choice) as choice')
            ->where(['article_title' => $this->article_title1])
            ->order('article_title desc')
            ->group('article_title')
            ->limit(0, 1)
            ->findAll();
        $this->assertEquals(count($list) == 1, true);
        $this->assertEquals($list[0]['choice'] == 4, true);
    }

    public function testUpdateDelete()
    {
        $id = $this->insert(3, $this->article_title2);
        $model = new ArticleModel();
        $info = $model->renew()->where([
            'id' => $id
        ])->find();
        $this->assertEquals(!empty($info), true);
        //更新操作
        $updateTime = date('Y-m-d H:i:s', time() + 3600);
        $res = $model->renew()->where([
            'article_title' => $this->article_title2
        ])->update([
            'update_time' => $updateTime
        ]);
        $this->assertEquals(!empty($res), true);
        $info = $model->renew()->where([
            'id' => $id
        ])->find();
        $this->assertEquals(strtotime($info['update_time']) == strtotime($updateTime), true);
        //软删除
        $res = $model->renew()->where([
            'article_title' => $this->article_title2
        ])->delete();
        $this->assertEquals(!empty($res), true);
        $info = $model->renew()->where([
            'id' => $id
        ])->find();
        $this->assertEquals(!empty($info), false);
        $id = $this->insert(3, $this->article_title2);
        //真实物理删除
        $info = $model->renew()->where([
            'id' => $id
        ])->find();
        $this->assertEquals(!empty($info), true);
        $res = $model->renew()->where([
            'article_title' => $this->article_title2
        ])->delete(true);
        $this->assertEquals(!empty($res), true);
    }


    public function testLike()
    {
        $title = '张三是某网络科技的呀';
        $id = $this->insert(3, $title);
        $model = new ArticleModel();
        $info = $model->renew()->where([
            'article_title' => [
                'like', '%某网络科技%'
            ],
        ])->find();
        $this->assertEquals(!empty($info), true);
        $this->assertEquals($info['id'] == $id, true);
        $res = $model->renew()->where([
            'article_title' => [
                'like', '%某网络科技%'
            ],
        ])->delete(true);
        $this->assertEquals(!empty($res), true);
        $title = '张三是某网络科技的呀';
        $id = $this->insert(3, $title);
        $model = new ArticleModel();
        $info = $model->renew()->where([
            'article_title' => [
                'like', '%某网络科技%'
            ],
        ])->find();
        $this->assertEquals(!empty($info), true);
        $this->assertEquals($info['id'] == $id, true);
        $res = $model->renew()->where([
            'article_title' => [
                'like', '%某网络科技%'
            ],
        ])->delete();
        $this->assertEquals(!empty($res), true);
    }

}
