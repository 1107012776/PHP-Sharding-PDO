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
 * 分库分表字段规则
 * User: lys
 * Date: 2019/7/24
 * Time: 14:34
 */
class InlineShardingStrategyConfiguration
{
    private $_fix = '';
    private $_rule = '';
    private $_ruleCustomizeCallback = null;

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
     * @param callable $ruleCustomizeCallback //规则自定义算法，设定自定义算法之后，默认系统规则算法失效
     */
    public function __construct($fix = '', $rule = [], callable $ruleCustomizeCallback = null)
    {
        $this->_fix = $fix;
        $this->_rule = $rule;
        $this->_ruleCustomizeCallback = $ruleCustomizeCallback;
    }

    public function getFix()
    {
        return $this->_fix;
    }

    /**
     * 是否自定义规则
     * @return bool
     */
    public function isCustomizeRule(){
       return !empty($this->_ruleCustomizeCallback);
    }

    /**
     * 获取自定义规则返回的
     * @param $condition
     * @return bool
     */
    public function getCustomizeNum($condition){
        if(!empty($this->_ruleCustomizeCallback)){
            $ruleCallback = $this->_ruleCustomizeCallback;
            return $ruleCallback($condition);
        }
        return null;
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