<?php

namespace RestApi\Lib\Helpers;

use Cake\Controller\Controller;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\InternalErrorException;
use Cake\Log\LogTrait;
use RestApi\Model\Table\OauthAccessTokensTable;

abstract class OAuthServer
{
    use LogTrait;

    private $_uid;
    private OauthHelper $_oauthSetup;

    public function __construct(array $config = [])
    {
        $this->_oauthSetup = new OauthHelper($this->getStorageClass());
        foreach ($config as $key => $value) {
            $this->{'_' . $key} = $value;
        }
    }
    protected abstract function getStorageClass(): OauthAccessTokensTable;

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

    protected abstract function managerGroups(): array;

    public function isManagerUser(): bool
    {
        return in_array($this->getUserGroup(), $this->managerGroups());
    }

    public function verifyAuthorization()
    {
        $this->_uid = $this->_oauthSetup->verifyAuthorization('/api/v3/me');
        return $this->_uid;
    }

    public function getUserID()
    {
        return $this->_uid;
    }
}
