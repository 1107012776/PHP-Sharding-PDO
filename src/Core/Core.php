<?php

namespace PhpShardingPdo\Core;

class Core
{
    public function __construct()
    {
    }

    public function helloWord()
    {
        echo self::class . '_' . __FUNCTION__;
    }
}