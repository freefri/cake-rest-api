<?php

declare(strict_types = 1);

namespace RestApi\Test\TestCase\Lib\Oauth;

use Cake\Http\Exception\ForbiddenException;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use RestApi\Lib\Oauth\AccessTokenEntity;
use RestApi\Lib\Oauth\ScopeRules;

class ScopeRulesTest extends TestCase
{
    public function testValidate()
    {
        $defaultRule = 'https://purl.imsglobal.org/spec/lti-ags/scope/result';
        // one route matching
        $this->assertTrue($this->_validate('GET', $defaultRule, [$defaultRule]));
        // matching between many routes
        $this->assertTrue($this->_validate('GET', $defaultRule, ['asdf', $defaultRule, 'klajsdf']));
        // not matching any routes
        $this->expectException(ForbiddenException::class);
        $this->_validate('GET', $defaultRule, ['asdf', '$defaultRule', 'klajsdf']);
    }

    public function testValidate_withReadOnly()
    {
        $defaultRule = 'https://purl.imsglobal.org/spec/lti-ags/scope/result';
        // matching method
        $this->assertTrue($this->_validate('GET', $defaultRule, [$defaultRule.'.readonly']));
        // not matching method
        $this->expectException(ForbiddenException::class);
        $this->_validate('POST', $defaultRule, ['asdf', '$defaultRule', 'klajsdf']);
    }

    public function testValidate_withEmptyRules()
    {
        $defaultRule = 'https://purl.imsglobal.org/spec/lti-ags/scope/result';
        $this->assertNull($this->_validate('GET', $defaultRule, []));
    }

    private function _validate(string $httpMethod, string $defaultRule, array $tokenScopes): ?bool
    {
        $req = $this->createMock(ServerRequest::class);
        $req->method('getMethod')->willReturn($httpMethod);
        $token = $this->createMock(AccessTokenEntity::class);
        $token->method('getScopes')->willReturn($tokenScopes);

        $rules = new ScopeRules();
        $rules->setDefaultRule($defaultRule);
        return $rules->validate($token, $req);
    }
}
