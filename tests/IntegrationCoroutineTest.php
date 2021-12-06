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


error_reporting(E_ALL); //显示所有错误信息
use PhpShardingPdo\Common\ConfigEnv;


$file_load_path = __DIR__ . '/../../../autoload.php';
if (file_exists($file_load_path)) {
    require_once $file_load_path;
} else {
    $vendor = __DIR__ . '/../vendor/autoload.php';
    require_once $vendor;
}

ConfigEnv::loadFile(dirname(__FILE__) . '/Config/.env');  //加载配置


/**
 *
 * drop DATABASE phpshardingpdo1;
 * drop DATABASE phpshardingpdo2;
 * drop DATABASE phpshardingpdo3;
 * drop DATABASE phpshardingpdo4;
 * 协程测试
 * php vendor/bin/phpunit tests/IntegrationCoroutineTest.php --filter testExecStart
 * Class IntegrationCoroutineTest
 * @package PhpShardingPdo\Test
 */
class IntegrationCoroutineTest extends IntegrationTest
{

    /**
     * 一键启动测试
     * php vendor/bin/phpunit tests/IntegrationCoroutineTest.php --filter testExecStart
     */
    public function testExecStart()
    {
        go(function () {
            \Swoole\Runtime::enableCoroutine();
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
        });
        \Swoole\Event::wait(); //https://wiki.swoole.com/wiki/page/1081.html
    }

}

