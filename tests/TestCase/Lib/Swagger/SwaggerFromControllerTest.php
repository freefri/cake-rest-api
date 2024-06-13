<?php

declare(strict_types = 1);

namespace RestApi\Test\TestCase\Lib\Swagger;

use Cake\Controller\Controller;
use Cake\Http\Response;
use Cake\TestSuite\TestCase;
use RestApi\Lib\Swagger\SwaggerFromController;

class SwaggerFromControllerTest extends TestCase
{
    public function testGetClassByType_shouldGetClass()
    {
        $swagger = new SwaggerFromController();
        $controller = new Controller();
        $request = [
            'url' => '/testurl/3',
            'session' => null,
            'query' => [],
            'files' => [],
            'environment' => [
                'REQUEST_METHOD' => 'GET',
                'QUERY_STRING' => '',
                'REQUEST_URI' => '/testurl/3'
            ],
            'post' => [],
            'cookies' => []
        ];
        $body = [
            'hello' => 'world',
        ];
        $res = $this->_getResponse($body, 200);
        $swagger->addToSwagger($controller, $request, $res);

        $expected = [
            '' => [
                'get' => [
                    'operationId' => (int) 1,
                    'summary' => '',
                    'description' => 'Run Bare',
                    'parameters' => [
                        (int) 0 => [
                            'description' => '2 letter code language (en, es, de, ar)',
                            'in' => 'header',
                            'name' => 'Accept-Language',
                            'example' => 'en',
                            'required' => false,
                            'schema' => [
                                'type' => 'string'
                            ]
                        ]
                    ],
                    'tags' => [
                        ''
                    ],
                    'responses' => [
                        200 => [
                            'description' => 'OK',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
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
                    ]
                ]
            ]
        ];
        $this->assertEquals($expected, $swagger->jsonSerialize());

        $body = [
            'error' => 'Forbidden',
            'code' => 403,
            'message' => 'Resource not allowed with this token',
            'exception' => '\Exception',
            'trigger' => 'somefile(231)',
        ];
        $res = $this->_getResponse($body, 403);
        $swagger->addToSwagger($controller, $request, $res);

        $expected2 = $expected;
        $expected2['']['get']['responses'][403] = [
            'description' => 'Forbidden',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'error' => [
                                'type' => 'string',
                                'example' => 'Forbidden'
                            ],
                            'code' => [
                                'type' => 'number',
                                'example' => 403
                            ],
                        ]
                    ]
                ]
            ]
        ];
        $this->assertEquals($expected2, $swagger->jsonSerialize());
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
