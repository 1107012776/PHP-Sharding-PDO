<?php
include_once '../SimpleAutoload.php';

use PhpShardingPdo\Autoload\SimpleAutoload;
use PhpShardingPdo\Test\IntegrationTest;
SimpleAutoload::add([
    "PhpShardingPdo\\Test" => "testsSimple/",
]);
\Swoole\Runtime::enableCoroutine();
$testObj = new \PhpShardingPdo\Test\IntegrationCoroutineTest();
$testObj->testExecStart();