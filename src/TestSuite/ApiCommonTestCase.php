<?php
declare(strict_types=1);

namespace RestApi\TestSuite;

use RestApi\Lib\Swagger\SwaggerFromController;

abstract class ApiCommonTestCase extends ApiCommonIntegrationTestCase
{
    private static SwaggerFromController $_swagger;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        $class = explode('\\', get_class($this));
        self::$_swagger = new SwaggerFromController(array_pop($class));
        parent::__construct($name, $data, $dataName);
    }

    abstract protected function _getEndpoint() : string;

    protected function _sendRequest($url, $method, $data = []): void
    {
        parent::_sendRequest($url, $method, $data);

        $request = $this->_buildRequest($url, $method, $data);
        self::$_swagger->addToSwagger($this->_controller, $request, $this->_response);
    }

    public static function tearDownAfterClass(): void
    {
        self::$_swagger->writeFile();
        parent::tearDownAfterClass();
    }
}
