<?php

declare(strict_types = 1);

namespace RestApi\Test\TestCase\Lib\Swagger;

use Cake\Controller\Controller;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use RestApi\Lib\Swagger\StandardSchemas;
use RestApi\Lib\Swagger\SwaggerBuilder;
use RestApi\Lib\Swagger\SwaggerFromController;
use RestApi\Lib\Swagger\SwaggerTestCase;
use RestApi\Model\Entity\LogEntry;
use RestApi\Model\Entity\RestApiEntity;

class SwaggerBuilderTest extends TestCase
{
    private function _getLangIso()
    {
        return SwaggerTestCase::acceptLanguage();
    }

    public function testToArray_addingSingleGetRequest()
    {
        $swagger = new SwaggerFromController();
        $request = new ServerRequest();
        $controller = new Controller($request, 'Pets');
        $request1 = [
            'url' => '/testurl/3',
            'session' => null,
            'query' => [],
            'files' => [],
            'environment' => [
                'REQUEST_METHOD' => 'GET',
                'QUERY_STRING' => '',
                'REQUEST_URI' => '/testurl/3'
            ],
            'cookies' => []
        ];
        $body = [
            'data' => $this->_getRequestData(),
        ];
        $res = $this->_getResponse($body, 200);
        $swagger->addToSwagger($controller, $request1, $res);

        $builder = new SwaggerBuilder($swagger);
        $array = $builder->toArray();
        $paths = [
            '' => [
                'get' => [
                    'operationId' => 'getPets',
                    'summary' => '',
                    'description' => 'Run bare',
                    'parameters' => [
                        [
                            'description' => 'Auth token',
                            'in' => 'header',
                            'name' => 'Authorization',
                            'example' => 'Bearer ****************',
                            'required' => true,
                            'schema' => [
                                'type' => 'string'
                            ]
                        ],
                        [
                            'description' => $this->_getLangIso(),
                            'in' => 'header',
                            'name' => 'Accept-Language',
                            'example' => 'en',
                            'required' => false,
                            'schema' => [
                                'type' => 'string'
                            ]
                        ],
                    ],
                    'tags' => [
                        (int) 0 => 'Pets'
                    ],
                    'responses' => [
                        (int) 200 => [
                            'description' => 'OK',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/ResRestApiNsLogEntry'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'security' => [
                        (int) 0 => [
                            'bearerAuth' => []
                        ]
                    ]
                ]
            ]
        ];

        $expectedSchemas = [
            'RestApiNsLogEntry' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        (int) 0 => [
                            'type' => 'number',
                            'example' => (int) 1
                        ],
                        (int) 1 => [
                            'type' => 'number',
                            'example' => (int) 1
                        ],
                        (int) 2 => [
                            'type' => 'number',
                            'example' => (int) 1
                        ]
                    ],
                    'something' => [
                        (int) 0 => [
                            '$ref' => '#/components/schemas/RestApiNsLogEntry'
                        ]
                    ],
                    'many' => [
                        (int) 0 => [
                            'type' => 'array',
                            'items' => [
                                '$ref' => '#/components/schemas/RestApiNsLogEntry'
                            ]
                        ]
                    ]
                ],
                'description' => 'Entity RestApiNsLogEntry'
            ],
            'ResRestApiNsLogEntry' => [
                'type' => 'object',
                'properties' => [
                    'data' => [
                        (int) 0 => [
                            '$ref' => '#/components/schemas/RestApiNsLogEntry'
                        ]
                    ]
                ],
                'description' => 'Data wrapper for RestApiNsLogEntry',
                'required' => [
                    (int) 0 => 'data'
                ]
            ]
        ];
        $this->assertEquals(['paths' => $paths, 'componentSchemas' => $expectedSchemas], $array);
    }

    public function testToArray_withRedirection()
    {
        $swagger = new SwaggerFromController();
        $request = new ServerRequest();
        $controller = new Controller($request, 'Pets');
        $request1 = [
            'url' => '/testurl/3',
            'session' => null,
            'query' => [],
            'files' => [],
            'environment' => [
                'REQUEST_METHOD' => 'GET',
                'QUERY_STRING' => '',
                'REQUEST_URI' => '/testurl/3'
            ],
            'cookies' => []
        ];
        $location = null;
        $res = new Response([
            'status' => 301,
            'type' => 'application/json',
            'headers' => ['Location' => [null]]
        ]);
        $swagger->addToSwagger($controller, $request1, $res);

        $builder = new SwaggerBuilder($swagger);
        $array = $builder->toArray();
        $expected = [
            '' => [
                'get' => [
                    'operationId' => 'getPets',
                    'summary' => '',
                    'description' => 'Run bare',
                    'parameters' => [
                        [
                            'description' => 'Auth token',
                            'in' => 'header',
                            'name' => 'Authorization',
                            'example' => 'Bearer ****************',
                            'required' => true,
                            'schema' => [
                                'type' => 'string'
                            ]
                        ],
                        [
                            'description' => $this->_getLangIso(),
                            'in' => 'header',
                            'name' => 'Accept-Language',
                            'example' => 'en',
                            'required' => false,
                            'schema' => [
                                'type' => 'string'
                            ]
                        ],
                    ],
                    'tags' => [
                        (int) 0 => 'Pets'
                    ],
                    'responses' => [
                        (int) 301 => [
                            'description' => 'Redirect. Moved Permanently',
                            'headers' => [
                                'Location' => [
                                    'description' => 'Run bare',
                                    'schema' => [
                                        'type' => 'string',
                                        'example' => $location,
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'security' => [
                        (int) 0 => [
                            'bearerAuth' => []
                        ]
                    ]
                ]
            ]
        ];
        $this->assertEquals(['paths' => $expected, 'componentSchemas' => []], $array);
    }

    public function testToArray_addingMultipleRequestWithDifferentParamsShouldSumarizeTheParams()
    {
        $swagger = new SwaggerFromController();
        $request = new ServerRequest();
        $controller = new Controller($request, 'Pets');
        $request1 = [
            'url' => '/testurl/3',
            'session' => null,
            'query' => [],
            'files' => [],
            'environment' => [
                'REQUEST_METHOD' => 'PATCH',
                'QUERY_STRING' => '',
                'REQUEST_URI' => '/testurl/3'
            ],
            'post' => [
                'first' => 'posted_body'
            ],
            'cookies' => []
        ];
        $request2 = $request1;
        $request2['query'] = ['my_param' => 'param_value'];
        $request2['post'] = [
            'first' => 'another_posted_body',
            'second' => 'second_posted_body',
        ];
        $body = [
            'hello' => 'world',
        ];
        $res = $this->_getResponse($body, 200);
        $swagger->addToSwagger($controller, $request1, $res);
        $swagger->addToSwagger($controller, $request2, $res);

        $builder = new SwaggerBuilder($swagger);
        $array = $builder->toArray();
        $expected = [
            '' => [
                'patch' => [
                    'operationId' => 'patchPets',
                    'summary' => '',
                    'description' => 'Run bare',
                    'parameters' => [
                        [
                            'description' => 'Auth token',
                            'in' => 'header',
                            'name' => 'Authorization',
                            'example' => 'Bearer ****************',
                            'required' => true,
                            'schema' => [
                                'type' => 'string'
                            ]
                        ],
                        [
                            'description' => $this->_getLangIso(),
                            'in' => 'header',
                            'name' => 'Accept-Language',
                            'example' => 'en',
                            'required' => false,
                            'schema' => [
                                'type' => 'string'
                            ]
                        ],
                        [
                            'description' => '',
                            'in' => 'query',
                            'name' => 'my_param',
                            'example' => 'param_value',
                            'required' => false,
                            'schema' => [
                                'type' => 'string'
                            ]
                        ],
                    ],
                    'tags' => [
                        (int) 0 => 'Pets'
                    ],
                    'responses' => [
                        (int) 200 => [
                            'description' => 'OK',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'description' => 'Generic object when: Run bare',
                                        'properties' => [
                                            'hello' => [
                                                'type' => 'string',
                                                'example' => 'world'
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'requestBody' => [
                        'description' => 'Request body can match to any of the 2 provided schemas',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'oneOf' => [
                                        [
                                            'type' => 'object',
                                            'description' => 'Generic object when: Run bare',
                                            'properties' => [
                                                'first' => [
                                                    'type' => 'string',
                                                    'example' => 'posted_body',
                                                ],
                                            ],
                                        ],
                                        [
                                            'type' => 'object',
                                            'description' => 'Generic object when: Run bare',
                                            'properties' => [
                                                'first' => [
                                                    'type' => 'string',
                                                    'example' => 'another_posted_body',
                                                ],
                                                'second' => [
                                                    'type' => 'string',
                                                    'example' => 'second_posted_body',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'security' => [
                        (int) 0 => [
                            'bearerAuth' => []
                        ]
                    ]
                ]
            ]
        ];
        $this->assertEquals(['paths' => $expected, 'componentSchemas' => []], $array);
    }

    public function testToArray_addingMultipleRequestWithDifferentParamsShouldSumarizeTheParamsAlsoInRequestBody()
    {
        $swagger = new SwaggerFromController();
        $request = new ServerRequest();
        $controller = new Controller($request, 'Pets');
        $request1 = [
            'url' => '/testurl/3',
            'session' => null,
            'query' => [],
            'files' => [],
            'environment' => [
                'REQUEST_METHOD' => 'PATCH',
                'QUERY_STRING' => '',
                'REQUEST_URI' => '/testurl/3'
            ],
            'post' => [
                '_c' => 'ExamplePostedBody',
                'first' => 'posted_body'
            ],
            'cookies' => []
        ];
        $request2 = $request1;
        $request2['query'] = ['my_param' => 'param_value'];
        $request2['post'] = [
            '_c' => 'ExamplePostedBody',
            'first' => 'another_posted_body',
            'second' => 'second_posted_body',
        ];
        $body = [
            'hello' => 'world',
        ];
        $res = $this->_getResponse($body, 200);
        $swagger->addToSwagger($controller, $request1, $res);
        $swagger->addToSwagger($controller, $request2, $res);

        $builder = new SwaggerBuilder($swagger);
        $array = $builder->toArray();
        $expected = [
            '' => [
                'patch' => [
                    'operationId' => 'patchPets',
                    'summary' => '',
                    'description' => 'Run bare',
                    'parameters' => [
                        [
                            'description' => 'Auth token',
                            'in' => 'header',
                            'name' => 'Authorization',
                            'example' => 'Bearer ****************',
                            'required' => true,
                            'schema' => [
                                'type' => 'string'
                            ]
                        ],
                        [
                            'description' => $this->_getLangIso(),
                            'in' => 'header',
                            'name' => 'Accept-Language',
                            'example' => 'en',
                            'required' => false,
                            'schema' => [
                                'type' => 'string'
                            ]
                        ],
                        [
                            'description' => '',
                            'in' => 'query',
                            'name' => 'my_param',
                            'example' => 'param_value',
                            'required' => false,
                            'schema' => [
                                'type' => 'string'
                            ]
                        ],
                    ],
                    'tags' => [
                        (int) 0 => 'Pets'
                    ],
                    'responses' => [
                        (int) 200 => [
                            'description' => 'OK',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'description' => 'Generic object when: Run bare',
                                        'properties' => [
                                            'hello' => [
                                                'type' => 'string',
                                                'example' => 'world'
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'requestBody' => [
                        'description' => '',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/ExamplePostedBody'
                                ],
                            ],
                        ],
                    ],
                    'security' => [
                        (int) 0 => [
                            'bearerAuth' => []
                        ]
                    ]
                ]
            ]
        ];
        $schemas = [
            'ExamplePostedBody' => [
                'type' => 'object',
                'properties' => [
                    'first' => [
                        (int) 0 => [
                            'type' => 'string',
                            'example' => 'posted_body'
                        ],
                        (int) 1 => [
                            'type' => 'string',
                            'example' => 'another_posted_body'
                        ]
                    ],
                    'second' => [
                        (int) 0 => [
                            'type' => 'string',
                            'example' => 'second_posted_body'
                        ]
                    ]
                ],
                'description' => 'Entity ExamplePostedBody'
            ]
        ];
        $this->assertEquals(['paths' => $expected, 'componentSchemas' => $schemas], $array);
    }

    public function testToArray_skippingRequestBodyFromErrorResponses()
    {
        $swagger = new SwaggerFromController();
        $request = new ServerRequest();
        $controller = new Controller($request, 'Pets');
        $request1 = [
            'url' => '/testurl/3',
            'session' => null,
            'query' => [],
            'files' => [],
            'environment' => [
                'REQUEST_METHOD' => 'PATCH',
                'QUERY_STRING' => '',
                'REQUEST_URI' => '/testurl/3'
            ],
            'post' => [
                'first' => 'posted_body'
            ],
            'cookies' => []
        ];
        $request2 = $request1;
        $request2['query'] = ['my_param' => 'param_value'];
        $request2['post'] = [
            'first' => 'another_posted_body',
            'second' => 'second_posted_body',
        ];
        $body = [
            'hello' => 'world',
        ];
        $res = $this->_getResponse($body, 400);
        $res2 = $this->_getResponse($body, 200);
        $swagger->addToSwagger($controller, $request1, $res);
        $swagger->addToSwagger($controller, $request2, $res2);

        $builder = new SwaggerBuilder($swagger);
        $array = $builder->toArray();
        $expected = [
            '' => [
                'patch' => [
                    'operationId' => 'patchPets',
                    'summary' => '',
                    'description' => 'Run bare',
                    'parameters' => [
                        [
                            'description' => '',
                            'in' => 'query',
                            'name' => 'my_param',
                            'example' => 'param_value',
                            'required' => false,
                            'schema' => [
                                'type' => 'string'
                            ]
                        ],
                        [
                            'description' => 'Auth token',
                            'in' => 'header',
                            'name' => 'Authorization',
                            'example' => 'Bearer ****************',
                            'required' => true,
                            'schema' => [
                                'type' => 'string'
                            ]
                        ],
                        [
                            'description' => $this->_getLangIso(),
                            'in' => 'header',
                            'name' => 'Accept-Language',
                            'example' => 'en',
                            'required' => false,
                            'schema' => [
                                'type' => 'string'
                            ]
                        ],
                    ],
                    'tags' => [
                        (int) 0 => 'Pets'
                    ],
                    'responses' => [
                        200 => [
                            'description' => 'OK',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'description' => 'Generic object when: Run bare',
                                        'properties' => [
                                            'hello' => [
                                                'type' => 'string',
                                                'example' => 'world'
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                    ],
                    'requestBody' => [
                        'description' => '',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'description' => 'Generic object when: Run bare',
                                    'properties' => [
                                        'first' => [
                                            'type' => 'string',
                                            'example' => 'another_posted_body',
                                        ],
                                        'second' => [
                                            'type' => 'string',
                                            'example' => 'second_posted_body',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'security' => [
                        (int) 0 => [
                            'bearerAuth' => []
                        ]
                    ]
                ]
            ]
        ];
        $this->assertEquals(['paths' => $expected, 'componentSchemas' => []], $array);
    }

    private function _getResponse(array $body, int $status = 200): Response
    {
        return new Response([
            'status' => $status,
            'type' => 'application/json',
            'body' => json_encode($body)
        ]);
    }

    private function _getRequestData(): array
    {
        return [
            RestApiEntity::CLASS_NAME => LogEntry::class,
            'id' => 1,
            'something' => [
                RestApiEntity::CLASS_NAME => LogEntry::class,
                'id' => 1,
            ],
            'many' => [
                [
                    RestApiEntity::CLASS_NAME => LogEntry::class,
                    'id' => 1,
                ]
            ]
        ];
    }

    public function testGetComponentSchemas()
    {
        $swagger = new SwaggerFromController();
        $builder = new SwaggerBuilder($swagger);
        $schemas1 = new StandardSchemas();
        $s1 = ['Event' => [
            'type' => 'object',
            'properties' => [
                'id' => [
                    (int) 0 => [
                        'type' => 'number',
                        'example' => (int) 50
                    ]
                ],
                '_links' => [
                    (int) 0 => [
                        '$ref' => '#/components/schemas/LinksSeller'
                    ]
                ]
            ],
        ]];
        $schemas1->setSchemas($s1);
        $builder->addSchemas($schemas1);

        $schemas2 = new StandardSchemas();
        $s2 = ['Event' => [
            'type' => 'object',
            'properties' => [
                'id' => [
                    (int) 0 => [
                        'type' => 'number',
                        'example' => (int) 50
                    ]
                ],
                '_links' => [
                    (int) 0 => [
                        'type' => 'object',
                        'description' => 'Any object',
                        'additionalProperties' => true
                    ]
                ]
            ],
        ]];
        $schemas2->setSchemas($s2);
        $builder->addSchemas($schemas2);

        $res = $builder->getComponentSchemas();

        $expected = [
            'Event' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        [
                            'type' => 'number',
                            'example' => (int) 50
                        ],
                        [
                            'type' => 'number',
                            'example' => (int) 50
                        ]
                    ],
                    '_links' => [
                        [
                            '$ref' => '#/components/schemas/LinksSeller'
                        ],
                        [
                            'type' => 'object',
                            'description' => 'Any object',
                            'additionalProperties' => true
                        ],
                    ]
                ],
            ]
        ];
        $this->assertEquals($expected, $res);
    }
}
