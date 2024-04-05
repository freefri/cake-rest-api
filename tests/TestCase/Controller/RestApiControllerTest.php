<?php
declare(strict_types=1);

namespace RestApi\Test\TestCase\Controller;

use Cake\TestSuite\TestCase;
use RestApi\Controller\RestApiController;

class RestApiControllerTest extends TestCase
{
    public function testIsValidParam()
    {
        $this->assertTrue(RestApiController::isValidParam('123'));
        $this->assertFalse(RestApiController::isValidParam('0'));
        $this->assertFalse(RestApiController::isValidParam(''));
        $this->assertFalse(RestApiController::isValidParam(null));
    }
}
