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
        $this->assertTrue(RestApiController::isValidParam('1'));
        $this->assertTrue(RestApiController::isValidParam('$jkj23kl'));
        $this->assertTrue(RestApiController::isValidParam('null'));
        $this->assertTrue(RestApiController::isValidParam('undefined'));
        $this->assertFalse(RestApiController::isValidParam('0'));
        $this->assertFalse(RestApiController::isValidParam('00000000000000000'));
        $this->assertFalse(RestApiController::isValidParam('   '));
        $this->assertFalse(RestApiController::isValidParam(' '));
        $this->assertFalse(RestApiController::isValidParam(''));
        $this->assertFalse(RestApiController::isValidParam(null));
    }
}
