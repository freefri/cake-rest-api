<?php

declare(strict_types = 1);

namespace RestApi\Test\TestCase\Lib\Swagger;

use Cake\TestSuite\TestCase;
use RestApi\Lib\Swagger\StandardEntity;

class StandardEntityTest extends TestCase
{
    public function testIsDataResult()
    {
        // only data
        $data = [
            'data' => []
        ];
        $schemas = new StandardEntity($data);
        $this->assertTrue($schemas->isDataResult());
        // more than data
        $data = [
            'data' => [],
            'more' => [],
        ];
        $schemas = new StandardEntity($data);
        $this->assertTrue($schemas->isDataResult());
        // without data
        $data = [
            'more' => [],
        ];
        $schemas = new StandardEntity($data);
        $this->assertFalse($schemas->isDataResult());
    }

    public function testIsDataArray()
    {
        // without data
        $data = [
            'more' => [],
        ];
        $schemas = new StandardEntity($data);
        $this->assertFalse($schemas->isDataArray());
        // with data no array
        $data = [
            'data' => [
                'id' => '1'
            ],
        ];
        $schemas = new StandardEntity($data);
        $this->assertFalse($schemas->isDataArray());
        // with data array
        $data = [
            'data' => [
                [
                    'id' => '1'
                ]
            ],
        ];
        $schemas = new StandardEntity($data);
        $this->assertTrue($schemas->isDataArray());
    }

    public function test_parseType()
    {
        $schemas = new StandardEntity([]);
        $json = ['_c' => 'RestApi\Model\Entity\LogEntry'];
        // Add namespace
        $res = $schemas->_parseType($json);
        $this->assertEquals('RestApiNsLogEntry', $res);
        // Remove namespace
        putenv('SWAGGER_NAMESPACE_TO_REMOVE=RestApi');
        $res = $schemas->_parseType($json);
        $this->assertEquals('LogEntry', $res);
        // Add namespace
        putenv('SWAGGER_NAMESPACE_TO_REMOVE=');
        $res = $schemas->_parseType($json);
        $this->assertEquals('RestApiNsLogEntry', $res);
        // Remove namespace
        putenv('SWAGGER_NAMESPACE_TO_REMOVE_3=RestApi');
        $res = $schemas->_parseType($json);
        $this->assertEquals('LogEntry', $res);
        // Add namespace
        putenv('SWAGGER_NAMESPACE_TO_REMOVE_3=');
        $res = $schemas->_parseType($json);
        $this->assertEquals('RestApiNsLogEntry', $res);
    }
}
