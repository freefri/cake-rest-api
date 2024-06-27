<?php

declare(strict_types = 1);

namespace RestApi\Test\TestCase\Lib\Swagger;

use Cake\Controller\Controller;
use Cake\Http\Response;
use Cake\TestSuite\TestCase;
use RestApi\Lib\Swagger\SwaggerBuilder;
use RestApi\Lib\Swagger\SwaggerFromController;

class SwaggerBuilderTest extends TestCase
{
    public function testToArray_addingSingleGetRequest()
    {
        $swagger = new SwaggerFromController();
        $controller = new Controller();
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
            'hello' => 'world',
        ];
        $res = $this->_getResponse($body, 200);
        $swagger->addToSwagger($controller, $request1, $res);

        $builder = new SwaggerBuilder($swagger);
        $array = $builder->toArray();
        $expected = [
            '' => [
                'get' => [
                    'operationId' => (int) 1,
                    'summary' => '',
                    'description' => 'Run bare',
                    'parameters' => [
                        [
                            'description' => 'Auth token',
                            'in' => 'header',
                            'name' => 'Authentication',
                            'example' => 'Bearer ****************',
                            'required' => true,
                            'schema' => [
                                'type' => 'string'
                            ]
                        ],
                        [
                            'description' => 'Language letter code (depending on setup: en, es, de, ar, eng, spa, es_AR, en_US)',
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
                        (int) 0 => ''
                    ],
                    'responses' => [
                        (int) 200 => [
                            'description' => 'OK',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'description' => 'Run bare',
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
                    'security' => [
                        (int) 0 => [
                            'bearerAuth' => []
                        ]
                    ]
                ]
            ]
        ];
        $this->assertEquals($expected, $array);
    }

    public function testToArray_addingMultipleRequestWithDifferentParamsShouldSumarizeTheParams()
    {
        $swagger = new SwaggerFromController();
        $controller = new Controller();
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
                    'operationId' => (int) 1,
                    'summary' => '',
                    'description' => 'Run bare',
                    'parameters' => [
                        [
                            'description' => 'Auth token',
                            'in' => 'header',
                            'name' => 'Authentication',
                            'example' => 'Bearer ****************',
                            'required' => true,
                            'schema' => [
                                'type' => 'string'
                            ]
                        ],
                        [
                            'description' => 'Language letter code (depending on setup: en, es, de, ar, eng, spa, es_AR, en_US)',
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
                        (int) 0 => ''
                    ],
                    'responses' => [
                        (int) 200 => [
                            'description' => 'OK',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'description' => 'Run bare',
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
                                            'description' => 'Run bare',
                                            'properties' => [
                                                'first' => [
                                                    'type' => 'string',
                                                    'example' => 'posted_body',
                                                ],
                                            ],
                                        ],
                                        [
                                            'type' => 'object',
                                            'description' => 'Run bare',
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
        $this->assertEquals($expected, $array);
    }

    private function _getResponse(array $body, int $status = 200): Response
    {
        return new Response([
            'status' => $status,
            'type' => 'application/json',
            'body' => json_encode($body)
        ]);
    }
}
