<?php

declare(strict_types = 1);

namespace RestApi\Test\TestCase\Lib\Swagger\FileReader;

use Cake\TestSuite\TestCase;
use RestApi\Lib\Swagger\FileReader\SchemaReader;

class SchemaReaderTest extends TestCase
{
    public function testMerge()
    {
        $data = [
            'Course' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        [
                            'type' => 'number', 'example' => (int) 50,
                        ],
                    ],
                    'title' => [
                        [
                            'type' => 'string', 'example' => 'Test active course',
                        ],
                    ],
                ],
                'description' => 'Entity Course',
            ],
        ];
        $reader = new SchemaReader();
        $reader->add($data);
        $data['Course']['properties']['title'][0]['type'] = 'number';
        $reader->add($data);
        $res = $reader->merge();
        $expected = [
            'Course' => [
                'type' => 'object', 'properties' => [
                    'id' => [
                        [
                            'type' => 'number', 'example' => (int) 50,
                        ],
                        [
                            'type' => 'number', 'example' => (int) 50,
                        ],
                    ],
                    'title' => [

                        [
                            'type' => 'string', 'example' => 'Test active course',
                        ],
                        [
                            'type' => 'number', 'example' => 'Test active course',
                        ],
                    ],
                ],
                'description' => 'Entity Course',
            ],
        ];
        $this->assertEquals($expected, $res);
        return $reader;
    }

    public function testToArray()
    {
        $reader = $this->testMerge();
        $res = $reader->toArray();

        $expected = [
            'Course' => [
                'type' => 'object', 'properties' => [
                    'id' => [
                        'type' => 'number',
                        'example' => 50,
                    ],
                    'title' => [
                        'oneOf' => [
                            ['type' => 'string'],
                            ['type' => 'number'],
                        ],
                        'example' => 'Test active course',
                    ],
                ],
                'description' => 'Entity Course',
                'required' => [
                    'id',
                    'title'
                ],
            ],
        ];
        $this->assertEquals($expected, $res);
    }

    public function testToArray_shouldReplaceStringNullable()
    {
        $data = [
            'Trainer' => [
                'type' => 'object',
                'properties' => [
                    'rating' => [
                        [
                            'type' => 'string',
                            'nullable' => true,
                            'example' => null,
                        ],
                    ],
                ],
            ],
        ];
        $reader = new SchemaReader();
        $reader->add($data);
        $data['Trainer']['properties']['rating'][0] = [
            'type' => 'number',
            'example' => 5.62,
        ];
        $reader->add($data);
        $reader->merge();
        $res = $reader->toArray();

        $expected = [
            'Trainer' => [
                'type' => 'object', 'properties' => [
                    'rating' => [
                        'type' => 'number',
                        'nullable' => true,
                        'example' => 5.62,
                    ],
                ],
            ],
        ];
        $this->assertEquals($expected, $res);
    }

    public function testAddOneOfType()
    {
        $reader = new SchemaReader();
        $types = [
            [
                'type' => 'string',
            ],
            [
                'type' => 'number',
            ],
        ];
        $res = $reader->addOneOfType($types, 'string');
        $this->assertEquals($types, $res);
    }
}
