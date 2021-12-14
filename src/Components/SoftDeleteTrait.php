<?php
/**
 * PhpShardingPdo  file.
 * @author linyushan <1107012776@qq.com>
 * @link https://www.developzhe.com/
 * @package https://github.com/1107012776/PHP-Sharding-PDO
 * @copyright Copyright &copy; 2019-2021
 * @license https://github.com/1107012776/PHP-Sharding-PDO/blob/master/LICENSE
 */

namespace PhpShardingPdo\Components;
/**
 * 软删除
 * Class SoftDeleteTrait
 * @package PhpShardingPdo\Components
 */
trait SoftDeleteTrait
{
    protected $softDeleteKey = 'del_flag';  //默认软删除键

    /**
     * 可以重写如下自定义软删除返回条件
     * @return array
     */
    protected function getSoftDeleteCondition()
    {
        return [
            $this->softDeleteKey => 0,
            /*          'delete_at' => [
                            'is', null
                        ],*/
        ];
    }

    /**
     * 软删除设置的值
     * @return array
     */
    protected function getSoftDeleteUpdate()
    {
        return [
            $this->softDeleteKey => 1,
            /*'delete_at' => date('Y-m-d H:i:s'),*/
        ];
    }

}
