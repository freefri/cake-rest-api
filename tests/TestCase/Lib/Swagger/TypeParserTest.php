<?php

declare(strict_types = 1);

namespace RestApi\Test\TestCase\Lib\Swagger;

use Cake\TestSuite\TestCase;
use RestApi\Lib\Swagger\StandardSchemas;
use RestApi\Lib\Swagger\TypeParser;
use RestApi\Model\Entity\LogEntry;
use RestApi\Model\Entity\RestApiEntity;

class TypeParserTest extends TestCase
{
    public function testAnonymizeVariables()
    {
        // secrets
        $res = TypeParser::anonymizeVariables('lkjafks-wekrwjl', 'signature');
        $this->assertEquals('*******-*******', $res);
        // date
        $res = TypeParser::anonymizeVariables('2014-03-24T09:32:30+01:00', 'created');
        $this->assertEquals('2016-04-15T10:34:55+02:00', $res);
        // long amazon signed urls
        $url = 'https://ct-module-files.s3.eu-west-1.amazonaws.com/something?X-Amz-Content-Sha256=UNSIGNED-PAYLOAD&X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=AKIAWM*******CZEQ5EK%2F20250724%2Feu-west-1%2Fs3%2Faws4_request&X-Amz-Date=20250724T143351Z&X-Amz-SignedHeaders=host&X-Amz-Expires=600&X-Amz-Signature=b1904f47df6392e493da47c3cd2a21f68d4ad5f2ca44e*******************';
        $res = TypeParser::anonymizeVariables($url, 'anything');
        $this->assertEquals('https://ct-module-files.s3.eu-west-1.amazonaws.com/something?X-Amz-Content-Sha256=UNSIGNED-PAYLOAD&X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=**********', $res);
        // references
        $string = 'wklerjklwej50-1753367645788akjsdflñakjd';
        $res = TypeParser::anonymizeVariables($string, 'reference');
        $this->assertEquals('wklerjklwej50-*************akjsdflñakjd', $res);
        // more references
        $string = '50-1395649913610';
        $res = TypeParser::anonymizeVariables($string, 'reference');
        $this->assertEquals('50-*************', $res);
    }

    public function testGetDataWithTypeList()
    {
        $res = TypeParser::getDataWithType([1, 2, 3]);
        $this->assertEquals([
            'type' => 'array',
            'items' => [
                'type' => 'number',
                'example' => 1,
            ],
        ], $res);
    }

    public function testGetDataWithTypeNull()
    {
        $res = TypeParser::getDataWithType(null);
        $this->assertEquals([
            'type' => 'string',
            'nullable' => true,
            'example' => null,
        ], $res);
    }

    public function testGetDataWithTypeBool()
    {
        $res = TypeParser::getDataWithType(true);
        $this->assertEquals([
            'type' => 'boolean',
            'example' => true,
        ], $res);

        $res = TypeParser::getDataWithType(false);
        $this->assertEquals([
            'type' => 'boolean',
            'example' => false,
        ], $res);
    }

    public function testGetDataWithTypeNumeric()
    {
        $res = TypeParser::getDataWithType(42);
        $this->assertEquals([
            'type' => 'number',
            'example' => 42,
        ], $res);

        // numeric string is treated as a number
        $res = TypeParser::getDataWithType('1.5');
        $this->assertEquals([
            'type' => 'number',
            'example' => 1.5,
        ], $res);
    }

    public function testGetDataWithTypeString()
    {
        $res = TypeParser::getDataWithType('hello');
        $this->assertEquals([
            'type' => 'string',
            'example' => 'hello',
        ], $res);
    }

    public function testGetDataWithTypeEmptyArray()
    {
        $res = TypeParser::getDataWithType([]);
        $this->assertEquals([
            'type' => 'object',
            'description' => TypeParser::ANYTHING,
            'additionalProperties' => true,
        ], $res);
    }

    public function testGetDataWithTypeObject()
    {
        $res = TypeParser::getDataWithType(['name' => 'John', 'age' => 30]);
        $this->assertEquals([
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'example' => 'John',
                ],
                'age' => [
                    'type' => 'number',
                    'example' => 30,
                ],
            ],
            'description' => 'Generic object.',
        ], $res);
    }

    public function testGetDataWithTypeObjectWithDescription()
    {
        $res = TypeParser::getDataWithType(['name' => 'John'], 'some test');
        $this->assertEquals([
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'example' => 'John',
                ],
            ],
            'description' => 'Generic object when: some test',
        ], $res);
    }

    public function testGetPropEmptyArray()
    {
        $res = TypeParser::getProp([]);
        $this->assertEquals([
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'description' => 'Empty array',
                'additionalProperties' => true,
            ],
        ], $res);
    }

    public function testGetPropList()
    {
        $res = TypeParser::getProp([1, 2]);
        $this->assertEquals([
            'type' => 'array',
            'items' => [
                'type' => 'number',
                'example' => 1,
            ],
        ], $res);
    }

    public function testGetPropObject()
    {
        $res = TypeParser::getProp(['a' => 1]);
        $this->assertEquals([
            'type' => 'object',
            'properties' => [
                'a' => [
                    'type' => 'number',
                    'example' => 1,
                ],
            ],
            'description' => 'objectInArray',
        ], $res);
    }

    public function testGetPropMaxDepth()
    {
        $res = TypeParser::getProp(['a' => 1], 'x', 10);
        $this->assertEquals([
            'type' => 'string',
            'example' => '{`a`:1}',
        ], $res);
    }

    public function testGetPropNumeric()
    {
        $res = TypeParser::getProp(5);
        $this->assertEquals([
            'type' => 'number',
            'example' => 5,
        ], $res);
    }

    public function testGetPropBool()
    {
        $res = TypeParser::getProp(true);
        $this->assertEquals([
            'type' => 'boolean',
            'example' => true,
        ], $res);
    }

    public function testGetPropString()
    {
        $res = TypeParser::getProp('hello');
        $this->assertEquals([
            'type' => 'string',
            'example' => 'hello',
        ], $res);
    }

    public function testGetPropStringAnonymized()
    {
        $res = TypeParser::getProp('mysecret', 'password');
        $this->assertEquals([
            'type' => 'string',
            'example' => '********',
        ], $res);
    }

    public function testGetPropPhoneNumber()
    {
        $res = TypeParser::getProp('+43666888777', 'phone');
        $this->assertEquals([
            'type' => 'string',
            'example' => '+43666888777',
        ], $res);
    }

    public function testGetItemsScalar()
    {
        $res = TypeParser::getItems([1, 2, 3]);
        $this->assertEquals([
            'type' => 'number',
            'example' => 1,
        ], $res);
    }

    public function testGetItemsArrayOfArrays()
    {
        $res = TypeParser::getItems([[1, 2], [3, 4]]);
        $this->assertEquals([
            'type' => 'array',
            'items' => [
                'type' => 'number',
                'example' => 1,
            ],
        ], $res);
    }

    public function testGetItemsArrayOfObjects()
    {
        $res = TypeParser::getItems([['a' => 1]]);
        $this->assertEquals([
            'type' => 'object',
            'properties' => [
                'a' => [
                    'type' => 'number',
                    'example' => 1,
                ],
            ],
            'description' => 'getItems',
        ], $res);
    }
}
