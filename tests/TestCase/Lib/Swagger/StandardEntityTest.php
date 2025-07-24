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
}
