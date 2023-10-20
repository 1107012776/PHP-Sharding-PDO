<?php

namespace PhpShardingPdo\Autoload;

class SimpleAutoload
{
    protected static $rules = [];

    public static function add($rule = [])
    {
        array_push(static::$rules, $rule);
    }

    public static function load($classname)
    {
        if(strpos($classname,'PhpShardingPdo') === false){
            return;
        }
        $fileArr = [];
        foreach (static::$rules as $index => $val){
            foreach ($val as $k => $v){
                $filename = sprintf('%s.php', str_replace($k, $v, $classname));
                $filename = str_replace('\\', '/', $filename);
                $dir = dirname(__FILE__);
                $filename = $dir.'/'.$filename;
                if (is_file($filename)) {
                    $fileArr[] = $filename;
                }
            }
        }
        foreach ($fileArr as $filename){
            require_once $filename;
        }
    }

    public static function init()
    {
        spl_autoload_register([SimpleAutoload::class, 'load']);
    }
}
SimpleAutoload::init();
SimpleAutoload::add([
    "PhpShardingPdo\\" => "src/",
]);
