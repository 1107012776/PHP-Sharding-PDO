<?php
/**
 * PhpShardingPdo  file.
 * @author linyushan  <1107012776@qq.com>
 * @link https://www.developzhe.com/
 * @package https://github.com/1107012776/PHP-Sharding-PDO
 * @copyright Copyright &copy; 2019-2021
 * @license https://github.com/1107012776/PHP-Sharding-PDO/blob/master/LICENSE
 */

namespace PhpShardingPdo\Core;

/**
 * 继承\PDO，便于后期扩展
 */
class SPDO extends \PDO
{
    private $dsn;

    public function __construct($dsn, $username, $passwd, $options = [])
    {
        $this->dsn = $dsn;
        parent::__construct($dsn, $username, $passwd, $options);
    }

    public function getDsn()
    {
        return $this->dsn;
    }
}

