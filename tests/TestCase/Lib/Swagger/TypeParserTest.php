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
        $url = 'https://ct-module-files.s3.eu-west-1.amazonaws.com/something?X-Amz-Content-Sha256=UNSIGNED-PAYLOAD&X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=AKIAWMOHZLNB6CZEQ5EK%2F20250724%2Feu-west-1%2Fs3%2Faws4_request&X-Amz-Date=20250724T143351Z&X-Amz-SignedHeaders=host&X-Amz-Expires=600&X-Amz-Signature=b1904f47df6392e493da47c3cd2a21f68d4ad5f2ca44e1508fd2cc4ec60c1d4f';
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
}
