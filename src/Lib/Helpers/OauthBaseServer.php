<?php

namespace RestApi\Lib\Helpers;

use Cake\Controller\Controller;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\InternalErrorException;
use Cake\Log\LogTrait;
use RestApi\Controller\RestApiController;
use RestApi\Lib\Oauth\AccessTokenEntity;
use RestApi\Model\Table\OauthAccessTokensTable;

abstract class OauthBaseServer
{
    use LogTrait;

    private AccessTokenEntity $token;
    protected OauthHelper $_oauthSetup;

    public function __construct(array $config = [])
    {
        $this->_oauthSetup = new OauthHelper($this->loadStorage());
        foreach ($config as $key => $value) {
            $this->{'_' . $key} = $value;
        }
    }
    protected abstract function loadStorage(): OauthAccessTokensTable;

    public function setupOauth(Controller $controller)
    {
        $this->_oauthSetup->setupOauth($controller);
    }

    public function authorizeUserData(RestApiController $controller)
    {
        $userID = $controller->getUrlUserId() ?? false;
        $this->validateScopes($this->getToken(), $controller);
        if ($userID !== false && !$this->isUserAllowed($userID, $controller)) {
            if (method_exists($controller, 'authorizeUserData')) {
                $controller->authorizeUserData();
            } else {
                $extra = $userID . ' -> ' . json_encode($this->_getDependentUserIDs());
                $this->log('ForbiddenException OAuthServer: ' . $extra, 'error');
                throw new ForbiddenException(
                    'Resource not allowed with this token ' . $this->getToken()->getUserId());
            }
        }
    }

    public function getToken(): AccessTokenEntity
    {
        return $this->token;
    }

    public function isUserAllowed($userID, RestApiController $controller = null): bool
    {
        $uID = $this->getToken()->getUserId();
        if ($uID == $userID || $this->isManagerUser()) {
            return true;
        }
        return in_array($userID, $this->_getDependentUserIDs());
    }

    public function validateScopes(AccessTokenEntity $token, RestApiController $controller): ?bool
    {
        $tokenScope = $token->getScope();
        if ($tokenScope) {
            $rules = $controller->scopeRules();
            if ($rules) {
                return $rules->validate($token, $controller->getRequest());
            }
        }
        return null;
    }

    private function _getDependentUserIDs(): array
    {
        $userID = $this->getToken()->getUserId();
        if (!$userID) {
            return [];
        }
        return $this->_oauthSetup->getUserModel()->getDependentUserIDs($userID) + [$userID];
    }

    public function getUserGroup(): ?int
    {
        if (!$this->getToken()->getUserId()) {
            throw new InternalErrorException('Empty User ID, used? verifyAuthorization()');
        }
        return $this->_oauthSetup->getUserModel()->getUserGroup($this->getToken()->getUserId());
    }

    protected function managerGroups(): array
    {
        // return [GROUP_ADMIN, GROUP_MODERATOR];
        return [];
    }

    public function isManagerUser(): bool
    {
        return in_array($this->getUserGroup(), $this->managerGroups());
    }

    protected function silentVerificationPath(): string
    {
        return '/api/me';
    }

    public function verifyAuthorizationAndGetToken(): AccessTokenEntity
    {
        $this->_oauthSetup->verifyAuthorization($this->silentVerificationPath());
        return $this->_oauthSetup->getLastToken();
    }

    /**
     * @deprecated use verifyAuthorizationAndGetToken() instead
     */
    public function verifyAuthorization()
    {
        $this->token = $this->verifyAuthorizationAndGetToken();
        return $this->getUserID();
    }

    public function getUserID()
    {
        return $this->getToken()->getUIdOrEmpty();
    }
}
