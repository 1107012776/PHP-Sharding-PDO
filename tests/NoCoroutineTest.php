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

use PhpShardingPdo\Core\ShardingPdoContext;
use PHPUnit\Framework\TestCase;
use Swoole\Coroutine;

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
class NoCoroutineTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();
        \Swoole\Runtime::enableCoroutine();
        date_default_timezone_set('Asia/Shanghai');
    }

    /**
     * php vendor/bin/phpunit tests/NoCoroutineTest.php --filter testObj
     * @throws
     */
    public function testObj()
    {

        var_dump(ShardingPdoContext::getCid(), 1);

    }


}
