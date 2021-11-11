<?php

namespace PhpShardingPdo\Common;
/**
 * 版权声明：本文为CSDN博主「「已注销」」的原创文章，遵循CC 4.0 BY-SA版权协议，转载请附上原文出处链接及本声明。
 * 原文链接：https://blog.csdn.net/ljh101/article/details/116502345
 * Class ConfigLoad
 * @package PhpEasyData\Components
 */
class ConfigEnv
{
    const ENV_PREFIX = 'PHP_';

    /**
     * 加载配置文件
     * @access public
     * @param string $filePath 配置文件路径
     * @return mixed
     */
    public static function loadFile(string $filePath)
    {
        if (!file_exists($filePath)) {
            return false;
        }
        //返回二位数组
        $env = parse_ini_file($filePath, true);
        foreach ($env as $key => $val) {
            $prefix = static::ENV_PREFIX . strtoupper($key);
            if (is_array($val)) {
                foreach ($val as $k => $v) {
                    $item = $prefix . '_' . strtoupper($k);
                    putenv("$item=$v");
                }
            } else {
                putenv("$prefix=$val");
            }
        }
        return true;
    }

    /**
     * 获取环境变量值
     * @access public
     * @param string $name 环境变量名（支持二级 . 号分割）
     * @param string $default 默认值
     * @return mixed
     */
    public static function get(string $name, $default = null)
    {
        $result = getenv(static::ENV_PREFIX . strtoupper(str_replace('.', '_', $name)));
        if (false !== $result) {
            if ('false' === $result) {
                $result = false;
            } elseif ('true' === $result) {
                $result = true;
            }
            return $result;
        }
        return $default;
    }
}
