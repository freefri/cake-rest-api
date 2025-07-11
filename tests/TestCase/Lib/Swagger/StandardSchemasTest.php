<?php

declare(strict_types = 1);

namespace RestApi\Test\TestCase\Lib\Swagger;

use Cake\TestSuite\TestCase;
use RestApi\Lib\Swagger\StandardSchemas;
use RestApi\Model\Entity\LogEntry;
use RestApi\Model\Entity\RestApiEntity;

class StandardSchemasTest extends TestCase
{
    public function testGetResponseSchemas()
    {
        $schemas = new StandardSchemas();

        $data = [
            'data' => $this->_getRequestData(), // directly data with entity
            'other' => 1,
        ];

        $res = $schemas->getResponseSchemas($data);
        $expected = [
            'type' => 'object',
            'properties' => [
                '$ref' => '#/components/schemas/ResRestApiNsLogEntry'
            ]
        ];
        $this->assertEquals($this->getExpectedSchemas(), $schemas->getSchemas());
    }

    public function testGetResponseSchemas_withPagination()
    {
        $schemas = new StandardSchemas();

        $data = [
            'data' => [
                $this->_getRequestData() // data with array of entities
            ],
            'total' => 1,
            'limit' => 2, // with pagination
            '_links' => [
                'self' => 'https://example.com/self',
                'next' => 'https://example.com/next',
            ]
        ];

        $res = $schemas->getResponseSchemas($data);
        $expected = [
            'type' => 'object',
            'properties' => [
                '$ref' => '#/components/schemas/PageRestApiNsLogEntry'
            ]
        ];
        $this->assertEquals($expected, $res);
        $expectedSchemas = [
            'RestApiNsLogEntry' => $this->getRestApiNsLogEntry(),
            'PageRestApiNsLogEntry' => [
                'type' => 'object',
                'properties' => [
                    'data' => [
                        'type' => 'array',
                        'items' => [
                            '$ref' => '#/components/schemas/RestApiNsLogEntry'
                        ],
                    ],
                    'total' => [
                        'type' => 'number',
                        'example' => 1
                    ],
                    'limit' => [
                        'type' => 'number',
                        'example' => 2
                    ],
                    '_links' => [
                        '$ref' => '#/components/schemas/PaginationLinks'
                    ],
                ]
            ],
            'PaginationLinks' => [
                'type' => 'object',
                'properties' => [
                    'self' => [
                        'type' => 'string',
                        'example' => 'https://example.com/self',
                    ],
                    'next' => [
                        'type' => 'string',
                        'example' => 'https://example.com/next',
                    ]
                ]
            ],
        ];
        $this->assertEquals($expectedSchemas, $schemas->getSchemas());
    }

    public function testGetResponseSchemas_withAnything()
    {
        $schemas = new StandardSchemas();

        $data = [
            'any' => [
                $this->_getRequestData()
            ],
        ];

        $res = $schemas->getResponseSchemas($data);
        $expected = [
            'type' => 'object',
            'properties' => [
                'any' => [
                    'type' => 'object',
                    'properties' => [
                        'simple' => [
                            'type' => 'number',
                            'example' => 1
                        ]
                    ]
                ]
            ]
        ];
        $this->assertEquals($expected, $res);
        $expectedSchemas = [];
        $this->assertEquals($expectedSchemas, $schemas->getSchemas());
    }

    public function testGetResponseSchemas_withAnythingSimple()
    {
        $schemas = new StandardSchemas();

        $data = [
            'any' => [
                'simple' => 1
            ],
        ];
        $res = $schemas->getResponseSchemas($data);
        $expected = [
            'type' => 'object',
            'properties' => [
                'any' => [
                    'type' => 'object',
                    'properties' => [
                        'simple' => [
                            'type' => 'number',
                            'example' => 1
                        ]
                    ]
                ]
            ]
        ];
        $this->assertEquals($expected, $res);
        $expectedSchemas = [];
        $this->assertEquals($expectedSchemas, $schemas->getSchemas());
        $expected = [
            'type' => 'object',
            'properties' => [
                'any' => [
                    'type' => 'object',
                    'properties' => [
                        'simple' => [
                            'type' => 'number',
                            'example' => 1
                        ]
                    ]
                ]
            ]
        ];
        $this->assertEquals($expected, $res);
        $expectedSchemas = [];
        $this->assertEquals($expectedSchemas, $schemas->getSchemas());
    }

    private function _getRequestData(): array
    {
        return [
            RestApiEntity::CLASS_NAME => LogEntry::class,
            'id' => 1,
            'something' => [ // directly entity
                RestApiEntity::CLASS_NAME => LogEntry::class,
                'id' => 1,
            ],
            'many' => [ // directly array of entities
                [
                    RestApiEntity::CLASS_NAME => LogEntry::class,
                    'id' => 1,
                ]
            ]
        ];
    }

    private function getExpectedSchemas(): array
    {
        return [
            'RestApiNsLogEntry' => $this->getRestApiNsLogEntry(),
            'ResRestApiNsLogEntry' => [
                'type' => 'object',
                'description' => 'Data wrapper for RestApiNsLogEntry',
                'required' => [
                    'data',
                ],
                'properties' => [
                    'data' => [
                        ['$ref' => '#/components/schemas/RestApiNsLogEntry']
                    ],
                    'other' => [
                        [
                            'type' => 'number',
                            'example' => (int)1
                        ]
                    ]
                ]
            ]
        ];
    }

    private function getRestApiNsLogEntry(): array
    {
        return [
            'type' => 'object',
            'description' => 'Entity RestApiNsLogEntry',
            'properties' => [
                'id' => [
                    [
                        'type' => 'number',
                        'example' => (int)1
                    ],
                    [
                        'type' => 'number',
                        'example' => (int)1
                    ],
                    [
                        'type' => 'number',
                        'example' => (int)1
                    ],
                ],
                'something' => [
                    ['$ref' => '#/components/schemas/RestApiNsLogEntry']
                ],
                'many' => [
                    [
                        'type' => 'array',
                        'items' => [
                            '$ref' => '#/components/schemas/RestApiNsLogEntry'
                        ],
                    ]
                ]
            ]
        ];
    }
}
