<?php

declare(strict_types = 1);

namespace RestApi\Test\TestCase\Lib\Swagger;

use Cake\Controller\Controller;
use Cake\Http\Response;
use Cake\TestSuite\TestCase;
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

        $this->assertEquals('get', $test->getMethod());
        $this->assertEquals('403', $test->getStatusCodeString());
        $this->assertEquals('/testurl_last/', $test->getRoute());
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
