<?php
include_once '../SimpleAutoload.php';

use PhpShardingPdo\Autoload\SimpleAutoload;
use PhpShardingPdo\Test\IntegrationTest;
SimpleAutoload::add([
    "PhpShardingPdo\\Test" => "testsSimple/",
]);

$testObj = new IntegrationTest();
$testObj->testExecStart();