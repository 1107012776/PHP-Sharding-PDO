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
use PhpShardingPdo\Core\ShardingPdoContext;
use PhpShardingPdo\Test\Migrate\Migrate;
use PhpShardingPdo\Test\Model\ArticleModel;
use PhpShardingPdo\Test\Model\CategoryOneModel;
use PhpShardingPdo\Test\Model\UserModel;

ConfigEnv::loadFile(dirname(__FILE__) . '/Config/.env');  //加载配置


class IntegrationTest
{
    private $article_title1 = '测试数据article_title1';
    private $article_title2 = '测试数据article_title2';
    private $article_title3 = '测试数据groupBy';

    public function assertEquals($a, $b)
    {
        if($a != $b){
            new \Exception(sprintf('异常%s!=%s', strval($a), strval($b)));
        }
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
        $this->testJoin();
        $this->testLeftJoin();
        $this->testRightJoin();
        $this->testOrderByJoin();
        $this->testGroupByJoin();
        $this->testXaTransaction();  //xa事务测试
        $this->testOneDatabase();  //单库测试，某个表只在某个数据库
        $this->testClearArticle();  //清理所有数据
        $this->testGroupByComprehensive();  //综合group by测试
    }

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

    /**
     * 查询测试
     * php vendor/bin/phpunit tests/IntegrationTest.php --filter testSelectFindAll
     */
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
        $count = $model->renew()->where([
            'cate_id' => ['egt', 1]
        ])->where(['article_title' => $this->article_title1])
            ->where(['cate_id' => ['elt', 4]])
            ->count();
        $this->assertEquals($count == 4, true);
        $count = $model->renew()->where([
            'cate_id' => ['findInSet', 1]
        ])->where(['article_title' => $this->article_title1])
            ->count();
        $this->assertEquals($count == 2, true);

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
        $model = new \PhpShardingPdo\Test\Model\ArticleModel();
        $count = $model->renew()->where([
            'cate_id' => 1,
        ])->count();
        $this->assertEquals($count == 2, true);
        $count = $model->renew()->where([
            'cate_id' => 2,
        ])->count();
        $this->assertEquals($count == 1, true);
        $count = $model->renew()->where([
            'cate_id' => 3,
        ])->count();
        $this->assertEquals($count == 1, true);
        $count = $model->renew()->where([
            'cate_id' => 4,
        ])->count();
        $this->assertEquals($count == 0, true);
        $count = $model->renew()
            ->group('cate_id,del_flag')->count();
        $this->assertEquals($count == 3, true);
        $list = $model->renew()
            ->group('cate_id,del_flag')->findAll();
        $this->assertEquals(count($list) == 3, true);
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
        $model = new \PhpShardingPdo\Test\Model\ArticleModel();
        $list = $model->renew()->field('article_title,sum(is_choice) as choice')
            ->where(['article_title' => $this->article_title1])
            ->order('article_title desc')
            ->group('article_title,del_flag')
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
        $this->testIncrDecr();
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
        $count = $model->renew()->where([
            'article_title' => [
                'notLike', '%某网络科技%'
            ],
        ])->count();
        $this->assertEquals($count == 4, true);
        $res = $model->renew()->where([
            'article_title' => [
                'like', '%某网络科技%'
            ],
        ])->delete();
        $this->assertEquals(!empty($res), true);
    }

    /**
     * php vendor/bin/phpunit tests/IntegrationTest.php --filter testIncrDecr
     */
    public function testIncrDecr()
    {
        $model = new ArticleModel();
        $title = '张三是某网络科技的呀testIncrDecr';
        $id = $this->insert(3, $title);
        $res = $model->renew()->where([
            'id' => $id,
            'article_title' => $title
        ])->decr('is_push', 1);
        $this->assertEquals(!empty($res), true);
        $res = $model->renew()->where([
            'id' => $id,
            'article_title' => $title
        ])->incr('is_push', 2);
        $this->assertEquals(!empty($res), true);
        $res = $model->renew()->where([
            'id' => $id,
            'article_title' => $title
        ])->delete(true);
        $this->assertEquals(!empty($res), true);
    }


    /**
     * join查询测试
     * php vendor/bin/phpunit tests/IntegrationTest.php --filter testJoin
     */
    public function testJoin()
    {
        $articleModel = new \PhpShardingPdo\Test\Model\ArticleModel();
        $articleModel->alias('ar');
        $cateModel = new \PhpShardingPdo\Test\Model\CategoryModel();
        $cateModel1 = clone $cateModel;
        $plan = $cateModel1->alias('cate')->where(['id' => 1])->createJoinTablePlan([
            'cate.id' => $articleModel->getFieldAlias('cate_id')
        ]);
        $articleModel1 = clone $articleModel;
        $this->assertEquals(!empty($plan), true);
        $list = $articleModel1->innerJoin($plan)
            ->where(['cate_id' => 1])->findAll();
        $this->assertEquals(count($list) == 2, true);
        $this->assertEquals(empty($articleModel1->sqlErrors()), true);
        $articleModel1 = clone $articleModel;
        $count = $articleModel1->innerJoin($plan)
            ->where(['cate_id' => 1])->count();
        $this->assertEquals($count == 2, true);
        $this->assertEquals(empty($articleModel1->sqlErrors()), true);
        $articleModel1 = clone $articleModel;
        $list = $articleModel1->field(['ar.cate_id as a', 'cate.id as b'])->innerJoin($plan)
            ->where(['cate_id' => 1])->findAll();
        $this->assertEquals(isset($list[1]['a']) && $list[1]['a'] == 1, true);
        $this->assertEquals(empty($articleModel1->sqlErrors()), true);
        $cateModel1 = clone $cateModel;
        $plan = $cateModel1->alias('cate')->where(['id' => 1])->createJoinTablePlan([
            'cate.id' => ['findInSet', $articleModel1->getFieldAlias('cate_id')]
        ]);
        $this->assertEquals(!empty($plan), true);
        $articleModel1 = clone $articleModel;
        $list = $articleModel1->field(['ar.cate_id as a', 'cate.id as b'])->innerJoin($plan)
            ->where([$articleModel1->getFieldAlias('cate_id') => 1])->findAll();
        $this->assertEquals(isset($list[1]['a']) && $list[1]['a'] == 1, true);
        $this->assertEquals(empty($articleModel1->sqlErrors()), true);
        $articleModel1 = clone $articleModel;
        $cateModel1 = clone $cateModel;
        $plan = $cateModel1->alias('cate')->where(['id' => 1])->createJoinTablePlan([
            'cate.id' => ['findInSet', $articleModel1->getFieldAlias('cate_id')]
        ]);
        $this->assertEquals(!empty($plan), true);
        $list = $articleModel1->field(['ar.cate_id as a', 'cate.id as b'])->innerJoin($plan)
            ->where([$cateModel1->getFieldAlias('id') => 1])->findAll();
        $this->assertEquals(isset($list[1]['a']) && $list[1]['a'] == 1, true);
        $this->assertEquals(empty($articleModel1->sqlErrors()), true);
        $articleModel1 = clone $articleModel;
        $cateModel1 = clone $cateModel;
        $plan = $cateModel1->alias('cate')->where(['id' => 1])->createJoinTablePlan([
            'cate.id' => ['findInSet', $articleModel1->getFieldAlias('cate_id')]
        ]);
        $this->assertEquals(!empty($plan), true);
        $list = $articleModel1->field(['ar.cate_id as a', 'cate.id as b'])->innerJoin($plan)
            ->where([$cateModel1->getFieldAlias('id') => 10])->findAll();
        $this->assertEquals(empty($list), true);
        $this->assertEquals(empty($articleModel1->sqlErrors()), true);
        //实行三表关联查询
        $userModel = new UserModel();  //用户表
        $articleModel1 = clone $articleModel; //文章表
        $cateModel1 = clone $cateModel;  //分类表
        $userModel1 = clone $userModel;  //用户表
        $user_id = 1;
        $catePlan = $cateModel1->alias('cate')->where(['id' => 1])->createJoinTablePlan([
            'cate.id' => $articleModel1->getFieldAlias('cate_id')
        ]);
        $articlePlan = $articleModel1->alias('ar')->where(['cate_id' => 1])->createJoinTablePlan([
            'user.id' => $articleModel1->getFieldAlias('user_id')
        ]);
        $this->assertEquals(!empty($catePlan), true);
        $this->assertEquals(!empty($articlePlan), true);
        $list = $userModel1->alias('user')->field(['user.id', 'ar.cate_id as a', 'cate.id as b'])
            ->innerJoin($catePlan)
            ->innerJoin($articlePlan)
            ->where([
                'id' => $user_id
            ])->findAll();
        $this->assertEquals(isset($list[0]['id']) && $list[0]['id'] == 1, true);
        $this->assertEquals(isset($list[0]['a']) && $list[0]['a'] == 1, true);
        $this->assertEquals(isset($list[0]['b']) && $list[0]['b'] == 1, true);
        $this->assertEquals(empty($userModel1->sqlErrors()), true);

        //没有on条件的join
        $articleModel1 = clone $articleModel; //文章表
        $cateModel1 = clone $cateModel;  //分类表
        $userModel1 = clone $userModel;  //用户表
        $user_id = 1;
        $catePlan = $cateModel1->alias('cate')->where(['id' => 1])->createJoinTablePlan([]);
        $articlePlan = $articleModel1->alias('ar')->where(['cate_id' => 1])->createJoinTablePlan([]);
        $this->assertEquals(!empty($catePlan), true);
        $this->assertEquals(!empty($articlePlan), true);
        $list = $userModel1->alias('user')->field(['user.id', 'ar.article_title', 'ar.cate_id as a', 'cate.id as b', 'cate.name'])
            ->innerJoin($catePlan)
            ->innerJoin($articlePlan)
            ->where([
                'id' => $user_id
            ])->findAll();
        $this->assertEquals(empty($userModel1->sqlErrors()), true);
        $this->assertEquals(!empty($list), true);

    }

    public function testLeftJoin()
    {
        $articleModel = new \PhpShardingPdo\Test\Model\ArticleModel();
        $articleModel->alias('ar');
        $cateModel = new \PhpShardingPdo\Test\Model\CategoryModel();
        $cateModel->alias('cate');
        $articleModel1 = clone $articleModel;
        $cateModel1 = clone $cateModel;
        $plan = $cateModel1->where(['id' => 1])->createJoinTablePlan([
            'cate.id' => $articleModel1->getFieldAlias('cate_id')
        ]);
        $this->assertEquals(!empty($plan), true);
        $list = $articleModel1->field(['ar.*', 'cate.name as cate_name'])->leftJoin($plan)
            ->where([$cateModel1->getFieldAlias('id') => 1])->findAll();
        $this->assertEquals(count($list) == 2, true);
    }

    public function testRightJoin()
    {
        $articleModel = new \PhpShardingPdo\Test\Model\ArticleModel();
        $articleModel->alias('ar');
        $cateModel = new \PhpShardingPdo\Test\Model\CategoryModel();
        $cateModel->alias('cate');
        $articleModel1 = clone $articleModel;
        $cateModel1 = clone $cateModel;
        $plan = $cateModel1->where(['id' => 1])->createJoinTablePlan([
            'cate.id' => $articleModel1->getFieldAlias('cate_id')
        ]);
        $this->assertEquals(!empty($plan), true);
        $list = $articleModel1->field(['ar.*', 'cate.name as cate_name'])->rightJoin($plan)
            ->where([
                $articleModel1->getFieldAlias('cate_id') => 1,
                $articleModel1->getFieldAlias('user_id') => 1,
            ])->findAll();
        $this->assertEquals(count($list) == 1, true);
    }

    public function testOrderByJoin()
    {
        $articleModel = new \PhpShardingPdo\Test\Model\ArticleModel();
        $articleModel->alias('ar');
        $cateModel = new \PhpShardingPdo\Test\Model\CategoryModel();
        $cateModel->alias('cate');
        $userModel = new UserModel();  //用户表
        $userModel->alias('user');
        $articleModel1 = clone $articleModel;
        $cateModel1 = clone $cateModel;
        $userModel1 = clone $userModel;
        $user_id = 1;
        $catePlan = $cateModel1->alias('cate')->where(['id' => 1])->createJoinTablePlan([
            'cate.id' => $articleModel1->getFieldAlias('cate_id')
        ]);
        $articlePlan = $articleModel1->alias('ar')->where(['cate_id' => 1])->createJoinTablePlan([
            'user.id' => $articleModel1->getFieldAlias('user_id')
        ]);
        $this->assertEquals(!empty($catePlan), true);
        $this->assertEquals(!empty($articlePlan), true);
        $list = $userModel1->field(['user.id', 'ar.cate_id as a', 'cate.id as b'])
            ->innerJoin($catePlan)
            ->innerJoin($articlePlan)
            ->where([
                'id' => $user_id
            ])->order('user.id desc')->findAll();
        $this->assertEquals(isset($list[0]['id']) && $list[0]['id'] == 1, true);
        $this->assertEquals(isset($list[0]['a']) && $list[0]['a'] == 1, true);
        $this->assertEquals(isset($list[0]['b']) && $list[0]['b'] == 1, true);
        $this->assertEquals(empty($userModel1->sqlErrors()), true);
    }

    public function testGroupByJoin()
    {
        $articleModel = new \PhpShardingPdo\Test\Model\ArticleModel();
        $articleModel->alias('ar');
        $cateModel = new \PhpShardingPdo\Test\Model\CategoryModel();
        $cateModel->alias('cate');
        $userModel = new UserModel();  //用户表
        $userModel->alias('user');
        $articleModel1 = clone $articleModel;
        $cateModel1 = clone $cateModel;
        $userModel1 = clone $userModel;
        $user_id = 1;
        $catePlan = $cateModel1->alias('cate')->where(['id' => 1])->createJoinTablePlan([
            'cate.id' => $articleModel1->getFieldAlias('cate_id')
        ]);
        $articlePlan = $articleModel1->alias('ar')->where(['cate_id' => 1])->createJoinTablePlan([
            'user.id' => $articleModel1->getFieldAlias('user_id')
        ]);
        $this->assertEquals(!empty($catePlan), true);
        $this->assertEquals(!empty($articlePlan), true);
        $list = $userModel1->field(['user.id', 'ar.cate_id as a', 'cate.id as b'])
            ->innerJoin($catePlan)
            ->innerJoin($articlePlan)
            ->where([
                'id' => $user_id
            ])->order('user.id desc')->group('user.id')->findAll();
        $this->assertEquals(isset($list[0]['id']) && $list[0]['id'] == 1, true);
        $this->assertEquals(isset($list[0]['a']) && $list[0]['a'] == 1, true);
        $this->assertEquals(isset($list[0]['b']) && $list[0]['b'] == 1, true);
        $this->assertEquals(empty($userModel1->sqlErrors()), true);
        $articleModel1 = clone $articleModel;
        $cateModel1 = clone $cateModel;
        $userModel1 = clone $userModel;
        $catePlan = $cateModel1->alias('cate')->where(['id' => 1])->createJoinTablePlan([
            'cate.id' => $articleModel1->getFieldAlias('cate_id')
        ]);
        $articlePlan = $articleModel1->alias('ar')->where(['cate_id' => 1])->createJoinTablePlan([
            'user.id' => $articleModel1->getFieldAlias('user_id')
        ]);
        $this->assertEquals(!empty($catePlan), true);
        $this->assertEquals(!empty($articlePlan), true);
        $list = $userModel1->field(['user.id', 'ar.cate_id as a', 'cate.id as b'])
            ->innerJoin($catePlan)
            ->innerJoin($articlePlan)
            ->where([
                'id' => $user_id
            ])->joinWhereCondition([  //这边存在注入的可能，因为不会使用占位符，请确保你传入的值是安全的
                $userModel1->getFieldAlias('id') => ['neq', 'ar.cate_id'] //请传递比如 ['user.id' => 'ar.cate_id']
            ])->order('user.id desc')->group('user.id')->findAll();
        $this->assertEquals(empty($list), true);
        $this->assertEquals(empty($userModel1->sqlErrors()), true);
        $this->testRenewJoin();
    }

    /**
     * Join model的renew操作
     */
    public function testRenewJoin()
    {
        $articleModel = new \PhpShardingPdo\Test\Model\ArticleModel();
        $articleModel->alias('ar');
        $cateModel = new \PhpShardingPdo\Test\Model\CategoryModel();
        $cateModel->alias('cate');
        $userModel = new UserModel();  //用户表
        $userModel->alias('user');
        $articleModel1 = clone $articleModel;
        $cateModel1 = clone $cateModel;
        $userModel1 = clone $userModel;
        $user_id = 1;
        $catePlan = $cateModel1->alias('cate')->where(['id' => 1])->createJoinTablePlan([
            'cate.id' => $articleModel1->getFieldAlias('cate_id')
        ]);
        $articlePlan = $articleModel1->alias('ar')->where(['cate_id' => 1])->createJoinTablePlan([
            'user.id' => $articleModel1->getFieldAlias('user_id')
        ]);
        $this->assertEquals(!empty($catePlan), true);
        $this->assertEquals(!empty($articlePlan), true);
        $list = $userModel1->field(['user.id', 'ar.cate_id as a', 'cate.id as b'])
            ->innerJoin($catePlan)
            ->innerJoin($articlePlan)
            ->where([
                'id' => $user_id
            ])->order('user.id desc')->group('user.id')->findAll();
        $this->assertEquals(isset($list[0]['id']) && $list[0]['id'] == 1, true);
        $this->assertEquals(isset($list[0]['a']) && $list[0]['a'] == 1, true);
        $this->assertEquals(isset($list[0]['b']) && $list[0]['b'] == 1, true);
        $this->assertEquals(empty($userModel1->sqlErrors()), true);
        $articleModel1->renew();
        $userModel1->renew();
        $user_id = 4;
        $articlePlan = $articleModel1->alias('ar')->where(['cate_id' => 3])->createJoinTablePlan([
            'user.id' => $articleModel1->getFieldAlias('user_id')
        ]);
        $this->assertEquals(!empty($articlePlan), true);
        $list = $userModel1->alias('user')->field(['user.id', 'ar.cate_id as a'])
            ->innerJoin($articlePlan)
            ->where([
                'id' => $user_id
            ])->order('user.id desc')->group('user.id')->findAll();
        $this->assertEquals(isset($list[0]['id']) && $list[0]['id'] == 4, true);
        $this->assertEquals(isset($list[0]['a']) && $list[0]['a'] == 3, true);
        $this->assertEquals(empty($userModel1->sqlErrors()), true);
    }

    /**
     * xa 事务测试
     */
    public function testXaTransaction()
    {
        $articleModel = new \PhpShardingPdo\Test\Model\ArticleXaModel();
        $data = [
            'article_descript' => 'xa测试数据article_descript',
            'article_img' => '/upload/2021110816311943244.jpg',
            'article_keyword' => 'xa测试数据article_keyword',
            'article_title' => $this->article_title2,
            'author' => '学者',
            'cate_id' => 3,
            'content' => '<p>xa测试数据</p><br/>',
            'content_md' => 'xa测试数据',
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
            'user_id' => $this->testUserId(),
        ];
        $data['id'] = $this->testGetId(2);
        $articleModel->startTrans($articleModel->createXid());
        $res = $articleModel->renew()->insert($data);
        $this->assertEquals(!empty($res), true);
        $articleModel->endXa();
        $this->assertEquals(empty($articleModel->sqlErrors()), true);
        $articleModel->prepareXa();
        $this->assertEquals(empty($articleModel->sqlErrors()), true);
        $articleModel->commit();
        $this->assertEquals(empty($articleModel->sqlErrors()), true);
        $row = $articleModel->where(['id' => $articleModel->getLastInsertId()])->find();
        $this->assertEquals(!empty($row), true);
        $articleModel = new \PhpShardingPdo\Test\Model\ArticleXaModel();
        $data['id'] = $this->testGetId(2);
        $articleModel->startTrans($articleModel->createXid());
        $articleModel->startTrans($articleModel->createXid());
        $res = $articleModel->renew()->where(['id' => $row['id']])->delete();
        $this->assertEquals(!empty($res), true);
        $articleModel->endXa();
        $this->assertEquals(empty($articleModel->sqlErrors()), true);
        $articleModel->prepareXa();
        $this->assertEquals(empty($articleModel->sqlErrors()), true);
        $articleModel->rollback();
        $this->assertEquals(empty($articleModel->sqlErrors()), true);
        $res = $articleModel->renew()->insert($data);
        $this->assertEquals(!empty($res), true);
        $articleModel->endXa();
        $this->assertEquals(empty($articleModel->sqlErrors()), true);
        $articleModel->prepareXa();
        $this->assertEquals(empty($articleModel->sqlErrors()), true);
        $articleModel->rollback();
        $this->assertEquals(empty($articleModel->sqlErrors()), true);
        $row = $articleModel->where(['id' => $articleModel->getLastInsertId()])->find();
        $this->assertEquals(empty($row), true);
        $this->testXaTransactionRecover();
    }

    /**
     * xa 事务测试Recover
     */
    public function testXaTransactionRecover()
    {
        $xid = '213123123213';
        $data = [
            'article_descript' => 'xa测试数据article_descript',
            'article_img' => '/upload/2021110816311943244.jpg',
            'article_keyword' => 'xa测试数据article_keyword',
            'article_title' => $this->article_title2,
            'author' => '学者',
            'cate_id' => 1,
            'content' => '<p>xa测试数据</p><br/>',
            'content_md' => 'xa测试数据',
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
            'user_id' => 1,
        ];
        $data['id'] = $this->testGetId(2);
        $articleModel = new \PhpShardingPdo\Test\Model\ArticleXaModel();
        $articleModel->startTrans($xid);
        $res = $articleModel->renew()->insert($data);
        $this->assertEquals(!empty($res), true);
        $articleModel->endXa();
        $this->assertEquals(empty($articleModel->sqlErrors()), true);
        $articleModel->prepareXa();
        $this->assertEquals(empty($articleModel->sqlErrors()), true);
        ShardingPdoContext::contextFreed(); //强制释放实例
        $this->testXaRecover();
    }

    public function testXaRecover()
    {
        $xid = '213123123213';
        $xid .= '_phpshardingpdo2';
        $articleModel = new \PhpShardingPdo\Test\Model\ArticleXaModel();
        $res = $articleModel->where(['user_id' => 1, 'cate_id' => 1])->recover();
        $this->assertEquals(!empty($res['list']), true);
        $isset = false;
        foreach ($res['list'] as $item) {
            if ($item['data'] == $xid) {
                $isset = true;
            }
        }
        $this->assertEquals($isset, true);
        $articleModel->setXid($xid);
        $res = $articleModel->commit();
        $this->assertEquals($res, true);
        $this->assertEquals(empty($articleModel->sqlErrors()), true);
    }

    /**
     * 单个库测试，某个表只存在于单个库
     */
    public function testOneDatabase()
    {
        $model = new CategoryOneModel();
        $list = $model->renew()->findAll();
        $this->assertEquals(implode(',', [1, 2, 3]), implode(',', array_column($list, 'id')));
        $list = $model->renew()->limit(2, 1)->findAll();
        $this->assertEquals(implode(',', [3]), implode(',', array_column($list, 'id')));
        $list = $model->renew()->limit(2, 1)->order('id desc')->findAll();
        $this->assertEquals(implode(',', [1]), implode(',', array_column($list, 'id')));
        $list = $model->renew()->limit(2, 1)->order('id asc')->findAll();
        $this->assertEquals(implode(',', [3]), implode(',', array_column($list, 'id')));
    }

    public function testClearArticle()
    {
        $articleModel = new \PhpShardingPdo\Test\Model\ArticleModel();
        $articleModel->delete(true); //全部清理掉，物理真实删除
        $this->assertEquals($articleModel->renew()->count() == 0, true);
    }

    /**
     * Group by综合测试
     */
    public function testGroupByComprehensive()
    {
        $this->testClearArticle();  //清理所有数据
        $this->insert(1, $this->article_title1);
        $this->insert(1, $this->article_title1);
        $this->insert(2, $this->article_title1);
        $this->insert(3, $this->article_title1);

        $this->insert(1, $this->article_title2);
        $this->insert(1, $this->article_title2);
        $this->insert(2, $this->article_title2);
        $this->insert(3, $this->article_title2);

        $this->insert(1, $this->article_title3);
        $this->insert(1, $this->article_title3);
        $this->insert(2, $this->article_title3);
        $this->insert(3, $this->article_title3);
        $articleModel = new \PhpShardingPdo\Test\Model\ArticleModel();
        $list = $articleModel->renew()->group("article_title")->findAll();
        $this->assertEquals(count($list) == 3, true);
        $count = $articleModel->renew()->group("article_title")->count();
        $this->assertEquals($count == 3, true);
        $list = $articleModel->renew()->group("article_title,cate_id")->findAll();
        $this->assertEquals(count($list) == 9, true);
        $count = $articleModel->renew()->group("article_title,cate_id")->count();
        $this->assertEquals($count == 9, true);
        $list = $articleModel->renew()->findAll();
        $this->assertEquals(count($list) == 12, true);
        $list = $articleModel->renew()->group("article_title,cate_id,user_id")->findAll();
        $this->assertEquals(count($list) == 12, true);
        $count = $articleModel->renew()->group("article_title,cate_id,user_id")->count();
        $this->assertEquals($count == 12, true);
        $count = $articleModel->renew()->group("article_title,cate_id,user_id")->limit(1)->count();
        $this->assertEquals($count == 1, true);
        $count = $articleModel->renew()->group("article_title,cate_id,user_id")->limit(0, 1)->count();
        $this->assertEquals($count == 1, true);
        $list = $articleModel->renew()->group("article_title,cate_id,user_id")->limit(1)->findAll();
        $this->assertEquals(count($list) == 1, true);
        $list = $articleModel->renew()->group("article_title,cate_id,user_id")->limit(0, 1)->findAll();
        $this->assertEquals(count($list) == 1, true);
        $list = $articleModel->renew()->field('sum(cate_id),cate_id')->group("cate_id")->limit(0, 1)->findAll();
        $this->assertEquals(count($list) == 1, true);
        $list = $articleModel->renew()->field('sum(cate_id),cate_id')->group("cate_id")->limit(0, 4)->findAll();
        $this->assertEquals(count($list) == 3, true);
        $count = $articleModel->renew()->field('sum(cate_id),cate_id')->group("cate_id")->limit(0, 4)->count();
        $this->assertEquals($count == 3, true);
        $count = $articleModel->renew()->field('sum(cate_id),cate_id')->group("cate_id")->where(['cate_id' => ['gt', 2]])->limit(0, 4)->count();
        $this->assertEquals($count == 1, true);
    }


}

