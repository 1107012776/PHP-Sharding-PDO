<?php

namespace PhpShardingPdo\Test\Migrate\build;

use PhpShardingPdo\Test\Migrate\Inter\CreateInter;

/**
 * 创建表
 * Class TableCreate
 * @package PhpShardingPdo\Test\Migrate
 */
class TableCreate implements CreateInter
{
    public function build()
    {
        $sqlDir = dirname(__FILE__);
        $contentDDL = file_get_contents($sqlDir . '/sql/sql.sql');
        foreach (DatabaseCreate::$databaseNameMap as $name) {
            DatabaseCreate::getConn($name)->exec($contentDDL);
        }
    }
}
