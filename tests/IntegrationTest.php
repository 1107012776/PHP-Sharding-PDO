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

use PhpShardingPdo\Common\ConfigEnv;
use PhpShardingPdo\Test\Migrate\Migrate;
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
class IntegrationTest extends TestCase
{
    /**
     * 重新构建数据库
     */
    public function testBuild(){
        ConfigEnv::loadFile('./Config/.env');
        Migrate::build();
    }



}