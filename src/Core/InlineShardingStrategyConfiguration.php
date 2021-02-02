<?php
/**
 * phpShardingPdo  file.
 * @author linyushan
 * @link https://www.developzhe.com/
 * @package https://github.com/1107012776/PHP-Sharding-PDO
 * @copyright Copyright &copy; 2019-2021
 * @license https://github.com/1107012776/PHP-Sharding-PDO/blob/master/LICENSE
 */
namespace PhpShardingPdo\Core;
/**
 * 分库分表字段规则
 * User: lys
 * Date: 2019/7/24
 * Time: 14:34
 */
class InlineShardingStrategyConfiguration
{
    private $_fix = '';
    private $_rule = '';

    /**
     * $rule = [
     * 'operator' => '%',
     * 'data'  => [    //具体的字段和相对运算符右边的数
     * 'user_id',  //字段名
     * 2
     * ]]
     *
     * ];
     * InlineShardingStrategyConfiguration constructor.
     * @param string $fix //名称前缀
     * @param array $rule //规则算法
     */
    public function __construct($fix = '', $rule = [])
    {
        $this->_fix = $fix;
        $this->_rule = $rule;
    }

    public function getFix()
    {
        return $this->_fix;
    }

    /**
     * 返回规则的数
     * @param $value //未知类型的值用于运算
     * @return int
     */
    public function getNum($value)
    {
        switch ($this->_rule['operator']) {  //运算操作符
            case '%':
                $number = $value % $this->_rule['data'][1];
                break;
        }
        return $number;
    }

    /**
     * 返回规则名
     * @return string
     */
    public function getName()
    {
        return $this->_rule['data'][0];
    }
}