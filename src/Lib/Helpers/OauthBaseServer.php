<?php

namespace RestApi\Lib\Helpers;

use Cake\Controller\Controller;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\InternalErrorException;
use Cake\Log\LogTrait;
use RestApi\Model\Table\OauthAccessTokensTable;

abstract class OauthBaseServer
{
    use LogTrait;

    private $_uid;
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

    public function authorizeUserData(Controller $controller)
    {
        $userID = $controller->getRequest()->getParam('userID') ?? false;
        if ($userID !== false && !$this->isUserAllowed($userID)) {
            if (method_exists($controller, 'authorizeUserData')) {
                $controller->authorizeUserData();
            } else {
                $extra = $userID . ' -> ' . json_encode($this->_getDependentUserIDs());
                $this->log('ForbiddenException OAuthServer: ' . $extra, 'error');
                throw new ForbiddenException('Resource not allowed with this token ' . $this->getuserID());
            }
        }
    }

    public function isUserAllowed($userID): bool
    {
        $uID = $this->getUserID();
        if ($uID == $userID || $this->isManagerUser()) {
            return true;
        }
        return in_array($userID, $this->_getDependentUserIDs());
    }

    private function _getDependentUserIDs(): array
    {
        return $this->_oauthSetup->getUserModel()->getDependentUserIDs($this->getUserID()) + [$this->getUserID()];
    }

    public function getUserGroup(): ?int
    {
        if (!$this->getUserID()) {
            throw new InternalErrorException('Empty User ID, used? verifyAuthorization()');
        }
        return $this->_oauthSetup->getUserModel()->getUserGroup($this->getUserID());
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

    public function verifyAuthorization()
    {
        $this->_uid = $this->_oauthSetup->verifyAuthorization($this->silentVerificationPath());
        return $this->_uid;
    }

    public function getUserID()
    {
        return $this->_uid;
    }
}
