<?php
declare(strict_types=1);

namespace RestApi\TestSuite;

use RestApi\Lib\Swagger\SwaggerFromController;

abstract class ApiCommonTestCase extends ApiCommonIntegrationTestCase
{
    private static SwaggerFromController $_swagger;
    private bool $_skipNext = false;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        self::$_swagger = new SwaggerFromController();
        parent::__construct($name, $data, $dataName);
    }

    abstract protected function _getEndpoint() : string;

    protected function skipNextRequestInSwagger()
    {
        $this->_skipNext = true;
    }

    protected function _sendRequest($url, $method, $data = []): void
    {
        parent::_sendRequest($url, $method, $data);
        if ($this->_skipNext) {
            $this->_skipNext = false;
            return;
        }

        $request = $this->_buildRequest($url, $method, $data);
        self::$_swagger->addToSwagger($this->_controller, $request, $this->_response);
    }

    public static function tearDownAfterClass(): void
    {
        $class = explode('\\', get_called_class());
        $className = array_pop($class);

        self::$_swagger->writeFile($className);
        parent::tearDownAfterClass();
    }
}
