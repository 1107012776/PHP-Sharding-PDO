<?php

use PHPUnit\Framework\TestCase;
use PhpShardingPdo\Common\Common;
$file_load_path = '../../../autoload.php';
if(file_exists($file_load_path)){
    include $file_load_path;
}else{
    include '../vendor/autoload.php';
}

class StackTest extends TestCase
{
    public function testRun(){
        var_dump(Common::getHello());
    }
    public function testPushAndPop()
    {
        $stack = [];
        $this->assertEquals(0, count($stack));

        array_push($stack, 'foo');
        $this->assertEquals('foo', $stack[count($stack) - 1]);
        $this->assertEquals(1, count($stack));

        $this->assertEquals('foo', array_pop($stack));
        $this->assertEquals(0, count($stack));
    }
}
