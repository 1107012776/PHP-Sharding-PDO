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


class ShardingPdoContext
{
    protected static $_self;
    protected $context;

    public static function getContext()
    {
        if (self::getCid() > -1) {
            $context = \Swoole\Coroutine::getContext();
            return $context;
        } else {
            if (empty(self::$_self)) {
                self::$_self = new ShardingPdoContext();
            }
            return self::$_self;
        }
    }

    public static function getValue($name)
    {
        $context = ShardingPdoContext::getContext();
        if ($context instanceof ShardingPdoContext) {
            return $context->_getValue($name);
        }
        if (empty($context[__CLASS__])) {
            $context[__CLASS__] = [];
        }
        if (isset($context[__CLASS__][$name])) {
            $value = $context[__CLASS__][$name];
            return $value;
        } else {
            return false;
        }
    }

    public static function setValue($name, $value)
    {
        $context = ShardingPdoContext::getContext();
        if ($context instanceof ShardingPdoContext) {
            return $context->_setValue($name, $value);
        }
        if (empty($context[__CLASS__])) {
            $context[__CLASS__] = [];
        }
        return $context[__CLASS__][$name] = $value;
    }

    public static function incrValue($name, $incrValue = 1)
    {
        $context = ShardingPdoContext::getContext();
        if ($context instanceof ShardingPdoContext) {
            return $context->_incrValue($name, $incrValue);
        }
        if (empty($context[__CLASS__])) {
            $context[__CLASS__] = [];
        }
        return $context[__CLASS__][$name] += $incrValue;
    }

    public static function decrValue($name, $decrValue = 1)
    {
        $context = ShardingPdoContext::getContext();
        if ($context instanceof ShardingPdoContext) {
            return $context->_decrValue($name, $decrValue);
        }
        if (empty($context[__CLASS__])) {
            $context[__CLASS__] = [];
        }
        return $context[__CLASS__][$name] -= $decrValue;
    }

    public static function getCid()
    {
        if (!class_exists('\Swoole\Coroutine')) {
            return -1;
        }
        return \Swoole\Coroutine::getCid();
    }

    public static function array_push($name, $value)
    {
        $context = ShardingPdoContext::getContext();
        if ($context instanceof ShardingPdoContext) {
            return $context->_array_push($name, $value);
        }
        if (empty($context[__CLASS__])) {
            $context[__CLASS__] = [];
        }
        return array_push($context[__CLASS__][$name], $value);
    }


    public static function array_shift($name)
    {
        $context = ShardingPdoContext::getContext();
        if ($context instanceof ShardingPdoContext) {
            return $context->_array_shift($name);
        }
        if (empty($context[__CLASS__])) {
            $context[__CLASS__] = [];
        }
        return array_shift($context[__CLASS__][$name]);
    }

    /**
     * 兼容旧版本（释放上下文）
     * @return bool
     */
    public static function nonCoroutineContextFreed()
    {
        return static::contextFreed();
    }

    /**
     * 释放当前php-sharding-pdo上下文
     * @return boolean
     */
    public static function contextFreed()
    {
        if (self::getCid() > -1) {
            $context = ShardingPdoContext::getContext();
            $context[__CLASS__] = [];
            return true;  //释放php-sharding-pdo协程上下文
        } else { //释放非协程上下文
            self::$_self = null;
            return true;
        }
    }

    /*************************          受保护的方法            *************************/

    protected function _getValue($name)
    {
        if (empty($this->context[__CLASS__])) {
            $this->context[__CLASS__] = [];
        }
        if (isset($this->context[__CLASS__][$name])) {
            $value = $this->context[__CLASS__][$name];
            return $value;
        } else {
            return false;
        }
    }


    protected function _setValue($name, $value)
    {
        if (empty($this->context[__CLASS__])) {
            $this->context[__CLASS__] = [];
        }
        return $this->context[__CLASS__][$name] = $value;
    }

    protected function _incrValue($name, $incrValue = 1)
    {
        if (empty($this->context[__CLASS__])) {
            $this->context[__CLASS__] = [];
        }
        return $this->context[__CLASS__][$name] += $incrValue;
    }

    protected function _decrValue($name, $decrValue = 1)
    {
        if (empty($this->context[__CLASS__])) {
            $this->context[__CLASS__] = [];
        }
        return $this->context[__CLASS__][$name] -= $decrValue;
    }


    protected function _array_push($name, $value)
    {
        if (empty($this->context[__CLASS__])) {
            $this->context[__CLASS__] = [];
        }
        return array_push($this->context[__CLASS__][$name], $value);
    }


    protected function _array_shift($name)
    {
        if (empty($this->context[__CLASS__])) {
            $this->context[__CLASS__] = [];
        }
        return array_shift($this->context[__CLASS__][$name]);
    }
}
