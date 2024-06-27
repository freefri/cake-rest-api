<?php

declare(strict_types = 1);

namespace RestApi\Test\TestCase\Lib\Swagger;

use Cake\Controller\Controller;
use Cake\Http\Response;
use Cake\TestSuite\TestCase;
use RestApi\Controller\RestApiController;
use RestApi\Lib\Swagger\SwaggerTestCase;

class SwaggerTestCaseTest extends TestCase
{
    public function testCreate()
    {
        $controller = new Controller();
        $request = [
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
                'hello' => 'param',
                'object' => ['something' => ['with' => 'depth']],
            ],
            'cookies' => []
        ];
        $body = [
            'data' => ['hello' => 'world'],
            'meta' => ['page' => 1],
        ];
        $res = $this->_getResponse($body, 403);
        $lastRoute = '/testurl_last/*';
        $test = new SwaggerTestCase($controller, $request, $res, $lastRoute);

        $this->assertEquals('patch', $test->getMethod());
        $this->assertEquals('403', $test->getStatusCodeString());
        $this->assertEquals('/testurl_last/', $test->getRoute());
        $this->assertEquals('Run', $test->getDescription());
        $this->assertEquals([['bearerAuth' => []]], $test->getSecurity());
        $expectedRequest = [
            'type' => 'object',
            'description' => 'Run',
            'properties' => [
                'hello' => [
                    'type' => 'string',
                    'example' => 'param'
                ],
                'object' => [
                    'type' => 'object',
                    'properties' => [
                        'something' => [
                            'type' => 'object',
                            'properties' => [
                                'with' => [
                                    'type' => 'string',
                                    'example' => 'depth',
                                ],
                            ],
                        ],
                    ],
                ]
            ]
        ];
        $this->assertEquals($expectedRequest, $test->getRequestSchema());
        $expectedResponse = [
            'type' => 'object',
            'description' => 'Run',
            'properties' => [
                'data' => [
                    'type' => 'object',
                    'properties' => [
                        'hello' => [
                            'type' => 'string',
                            'example' => 'world'
                        ]
                    ]
                ],
                'meta' => [
                    'page' => (int) 1
                ]
            ]
        ];
        $this->assertEquals($expectedResponse, $test->getResponseSchema());
    }

    private function _getResponse(array $body, int $status = 200): Response
    {
        return new Response([
            'status' => $status,
            'type' => 'application/json',
            'body' => json_encode($body)
        ]);
    }

    public function testGetSecurity()
    {
        // public controller
        $controller = $this->createMock(RestApiController::class);
        $controller->method('isPublicController')->willReturn(true);
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
            'error' => 'Forbidden',
            'code' => 403,
            'message' => 'Resource not allowed with this token',
            'exception' => '\Exception',
            'trigger' => 'somefile(231)',
        ];
        $res = $this->_getResponse($body, 403);
        $lastRoute = '/testurl_last/*';
        $test = new SwaggerTestCase($controller, $request, $res, $lastRoute);
        $this->assertEquals(null, $test->getSecurity());
        // require token
        $controller = $this->createMock(RestApiController::class);
        $controller->method('isPublicController')->willReturn(false);
        $test = new SwaggerTestCase($controller, $request, $res, $lastRoute);
        $this->assertEquals([['bearerAuth' => []]], $test->getSecurity());
    }

    public function testGetProp()
    {
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
            'error' => 'Forbidden',
            'code' => 403,
            'message' => 'Resource not allowed with this token',
            'exception' => '\Exception',
            'trigger' => 'somefile(231)',
        ];
        $lastRoute = '/testurl_last/*';
        $res = $this->_getResponse($body, 403);
        $controller = $this->createMock(RestApiController::class);
        $test = new SwaggerTestCase($controller, $request, $res, $lastRoute);
        // empty array
        $this->assertEquals([
            'type' => 'array',
            'items' => [
                'type' => 'object'
            ]
        ], $test->getProp([]));
        // not empty array
        $this->assertEquals([
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'hello' => [
                        'type' => 'string',
                        'example' => 'world',
                    ]
                ]
            ]
        ], $test->getProp([['hello' => 'world']]));
        // object 0 depth
        $this->assertEquals([
            'type' => 'object',
            'properties' => [
                'hello' => [
                    'type' => 'string',
                    'example' => 'world',
                ]
            ]
        ], $test->getProp(['hello' => 'world']));
        // array 0 depth
        $this->assertEquals([
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'hello' => [
                        'type' => 'string',
                        'example' => 'world',
                    ]
                ]
            ]
        ], $test->getProp([['hello' => 'world']]));
        // object big depth
        $this->assertEquals([
            'type' => 'string',
            'example' => '{`huge`:`depth`}'
        ], $test->getProp(['huge' => 'depth'], 'prop', 100));
        // numeric
        $this->assertEquals([
            'type' => 'number',
            'example' => 456
        ], $test->getProp(456));
        // boolean
        $this->assertEquals([
            'type' => 'boolean',
            'example' => true
        ], $test->getProp(true));
        // string password
        $this->assertEquals([
            'type' => 'string',
            'example' => '*****'
        ], $test->getProp('hello', 'password'));
    }
}
