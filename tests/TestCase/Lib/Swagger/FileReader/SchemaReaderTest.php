<?php

declare(strict_types = 1);

namespace RestApi\Test\TestCase\Lib\Swagger\FileReader;

use Cake\TestSuite\TestCase;
use RestApi\Lib\Swagger\FileReader\SchemaReader;
use RestApi\Lib\Swagger\TypeParser;

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
        $array = [
            'type' => 'array',
            'items' => [
                'type' => 'string', 'example' => 'two',
            ],
        ];
        $data['Course']['properties']['title'][2] = $array;
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
                        $array,
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
                            ['type' => 'string', 'example' => 'Test active course'],
                            ['type' => 'number', 'example' => 'Test active course'],
                            [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'string', 'example' => 'two',
                                ],
                            ],
                        ],
                    ],
                ],
                'description' => 'Entity Course',
                'required' => [
                    'id',
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
        // should not add when existing
        $reader = new SchemaReader();
        $types = [
            [
                'type' => 'string',
            ],
            [
                'type' => 'number',
            ],
        ];
        $res = $reader->addOneOfType($types, ['type' => 'string']);
        $this->assertEquals($types, $res);
        // should add one more
        $reader = new SchemaReader();
        $types = [
            [
                'type' => 'string',
            ],
        ];
        $res = $reader->addOneOfType($types, ['type' => 'number', 'example' => '5096']);
        $types = [
            [
                'type' => 'string',
            ],
            [
                'type' => 'number',
                'example' => '5096',
            ],
        ];
        $this->assertEquals($types, $res);
    }

    public function testGetNewContent()
    {
        // should not get empty objects
        $contentArray = [
            [
                'type' => 'array',
                'items' => [
                    '$ref' => '#/components/schemas/AttendeeServiceTag',
                ],
            ],
            [
                'type' => 'object', 'description' => TypeParser::ANYTHING, 'additionalProperties' => true,
            ],
        ];
        $reader = new SchemaReader();
        $res = $reader->getNewContent($contentArray);
        $expected = [
            'type' => 'array',
            'items' => [
                '$ref' => '#/components/schemas/AttendeeServiceTag',
            ],
        ];
        $this->assertEquals($expected, $res);
        // should not get empty objects
        $contentArray = [
            [
                'type' => 'object', 'description' => TypeParser::ANYTHING, 'additionalProperties' => true,
            ],
            [
                'type' => 'array',
                'items' => [
                    '$ref' => '#/components/schemas/AttendeeServiceTag',
                ],
            ],
        ];
        $reader = new SchemaReader();
        $res = $reader->getNewContent($contentArray);
        $this->assertEquals($expected, $res);
        // should not get string nullable when other type exists
        $contentArray = [
            [
                'type' => 'string', 'nullable' => true, 'example' => null,
            ],
            [
                '$ref' => '#/components/schemas/Result',
            ],
        ];
        $reader = new SchemaReader();
        $res = $reader->getNewContent($contentArray, 'three');
        $expected = [
            'nullable' => true,
            '$ref' => '#/components/schemas/Result'
        ];
        $this->assertEquals($expected, $res);
    }
}
