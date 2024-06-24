<?php

declare(strict_types = 1);

namespace RestApi\Test\TestCase\Lib\Helpers;

use App\Controller\ApiController;
use App\Lib\Oauth\OAuthServer;
use Cake\TestSuite\TestCase;
use RestApi\Lib\Oauth\AccessTokenEntity;
use RestApi\Lib\Oauth\ScopeRules;

class OauthBaseServerTest extends TestCase
{
    public function testAuthorizeUserData()
    {
        $token = ['scope' => 'myTestScope mySecondTestScope'];
        $rules = new ScopeRules();
        $rules->setDefaultRule('thisScopeWillNotMatch');
        // Token has NO scope -> OK
        $this->_authorizeUserDataWithoutUrlUserId([], null);
        // Token HAS scope && NO rules -> OK
        $this->_authorizeUserDataWithoutUrlUserId($token, null);
        // Token HAS scope && NO rules -> match
        $this->expectExceptionMessage('Scope not allowed with this token  ->  thisScopeWillNotMatch');
        $this->_authorizeUserDataWithoutUrlUserId($token, $rules);
    }

    public function testAuthorizeUserData_withUserIdInUrl_noScopeToken_noUserToken()
    {
        $urlUserId = '1';
        $this->expectExceptionMessage('Empty User ID, used? verifyAuthorization()');
        $this->_authorizeUserData([], null, $urlUserId);
    }

    public function testAuthorizeUserData_withUserIdInUrl_MatchingTokenUid_scopeNotMatching()
    {
        $urlUserId = '1';
        $token = ['scope' => 'myTestScope mySecondTestScope', 'user_id' => $urlUserId];
        $rules = new ScopeRules();
        $rules->setDefaultRule('thisScopeWillNotMatch');
        $this->expectExceptionMessage('Scope not allowed with this token  ->  thisScopeWillNotMatch');
        $this->_authorizeUserData($token, $rules, $urlUserId);
    }

    public function testAuthorizeUserData_withUserIdInUrl_MatchingTokenUid_noScope()
    {
        $urlUserId = '1';
        $token = ['scope' => 'myTestScope mySecondTestScope', 'user_id' => $urlUserId];
        $this->_authorizeUserData($token, null, $urlUserId);
        $this->assertTrue(true);
    }

    private function _authorizeUserDataWithoutUrlUserId(array $token, ?ScopeRules $urlScopeRules): void
    {
        $this->_authorizeUserData($token, $urlScopeRules, '');
    }

    private function _authorizeUserData(array $token, ?ScopeRules $urlScopeRules, string $urlUserId): void
    {
        $controller = $this->createMock(ApiController::class);
        $controller->method('getUrlUserId')->willReturn($urlUserId);
        $controller->method('scopeRules')->willReturn($urlScopeRules);

        $tokenEntity = new AccessTokenEntity($token);

        /** @var OauthServer $oauth */
        $oauth = $this->getMockBuilder(OAuthServer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getToken'])
            ->getMock();
        $oauth->method('getToken')->willReturn($tokenEntity);

        $oauth->authorizeUserData($controller);
    }

    public function testGetUserID()
    {
        /** @var OauthServer $oauth */
        $oauth = $this->getMockBuilder(OAuthServer::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $this->assertEquals('', $oauth->getUserID());
    }
}
