<?php

declare(strict_types = 1);

namespace RestApi\Test\TestCase\Lib\Swagger;

use Cake\Controller\Controller;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use RestApi\Lib\Swagger\SwaggerFromController;

class SwaggerFromControllerTest extends TestCase
{
    public function testGetClassByType_shouldGetClass()
    {
        $swagger = new SwaggerFromController();
        $request = new ServerRequest();
        $controller = new Controller($request, 'Pets');
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
            'paths' => [
                '' => [
                    'get' => [
                        'operationId' => 'getPets',
                        'summary' => '',
                        'description' => 'Run bare',
                        'parameters' => [
                            (int) 0 => [
                                'description' => 'Auth token',
                                'in' => 'header',
                                'name' => 'Authentication',
                                'example' => 'Bearer ****************',
                                'required' => true,
                                'schema' => [
                                    'type' => 'string'
                                ]
                            ],
                            (int) 1 => [
                                'description' => 'ISO 639-1 2 letter language code (en, es, de, ar, etc.)',
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
                            (int) 0 => 'Pets'
                        ],
                        'responses' => [
                            (int) 200 => [
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
                                            ],
                                            'description' => 'Generic object when: Run bare'
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
            ],
            'componentSchemas' => []
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
        $expected2['paths']['']['get']['responses'][403] = [
            'description' => 'Forbidden',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'description' => 'Generic object when: Run bare',
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
