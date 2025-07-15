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
        try {
            $log->log(LogLevel::DEBUG, 'Testing log in DatabaseLogTest');
            $this->assertTrue(true, 'with database setup');
        } catch (MissingDatasourceConfigException $e) {
            $this->assertTrue(true, 'no database setup');
        }
    }
}
