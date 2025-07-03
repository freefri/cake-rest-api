<?php

declare(strict_types = 1);

namespace RestApi\Test\TestCase\Lib;

use Cake\Datasource\Exception\MissingDatasourceConfigException;
use Cake\TestSuite\TestCase;
use Psr\Log\LogLevel;
use RestApi\Lib\DatabaseLog;

class DatabaseLogTest extends TestCase
{
    public function testLog()
    {
        $log = new DatabaseLog();
        $this->expectException(MissingDatasourceConfigException::class);
        $log->log(LogLevel::DEBUG, 'Testing log in DatabaseLogTest');
    }
}
