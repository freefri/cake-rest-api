<?php
declare(strict_types=1);

namespace RestApi\TestSuite;

abstract class ApiCommonErrorsTest extends ApiCommonAssertionsTest
{
    public function testPost_shouldThrowBadRequestExceptionWhenEmptyBodyProvided()
    {
        $this->skipNextRequestInSwagger();
        $this->actionExpectsException('', 'post', 'Empty body or invalid Content-Type in HTTP request');
    }

    public function testPut_shouldThrowBadRequestExceptionWhenNoIdProvided()
    {
        $this->skipNextRequestInSwagger();
        $this->put($this->_getEndpoint(), ['x' => 'y']);

        $body = (string)$this->_response->getBody();
        $this->assertResponseError('HTTP method requires ID' . $body);
        $this->assertEquals('HTTP method requires ID', json_decode($body, true)['message']);
    }

    public function testPut_shouldThrowBadRequestExceptionWhenNoBodyProvided()
    {
        $this->skipNextRequestInSwagger();
        $this->actionExpectsException(50, 'put', 'Empty body or invalid Content-Type in HTTP request');
    }

    public function testPatch_shouldThrowBadRequestExceptionWhenNoBodyProvided()
    {
        $this->skipNextRequestInSwagger();
        $this->actionExpectsException(50, 'patch', 'Empty body or invalid Content-Type in HTTP request');
    }

    public function testPatch_shouldThrowBadRequestExceptionWhenNoIdProvided()
    {
        $this->skipNextRequestInSwagger();
        $this->patch($this->_getEndpoint(), ['x' => 'y']);
        $body = (string)$this->_response->getBody();
        $this->assertResponseError('HTTP method requires ID ' . $body);
        $this->assertEquals('HTTP method requires ID', json_decode($body, true)['message']);
    }

    public function testDelete_shouldThrowBadRequestExceptionWhenNoIdProvided()
    {
        $this->skipNextRequestInSwagger();
        $this->delete($this->_getEndpoint());
        $body = (string)$this->_response->getBody();
        $this->assertResponseError('HTTP method requires ID' . $body);
        $this->assertEquals('HTTP method requires ID', json_decode($body, true)['message']);
    }
}
