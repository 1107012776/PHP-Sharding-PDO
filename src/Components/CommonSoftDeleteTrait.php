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

class CommonSoftDeleteTrait
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
}