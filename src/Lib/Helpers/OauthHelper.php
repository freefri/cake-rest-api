<?php

namespace RestApi\Lib\Helpers;

use Cake\Controller\Controller;
use Cake\Http\Exception\InternalErrorException;
use OAuth2\Autoloader;
use OAuth2\GrantType\AuthorizationCode;
use OAuth2\GrantType\UserCredentials;
use OAuth2\Request;
use OAuth2\Response;
use OAuth2\Server;
use RestApi\Lib\Exception\SilentException;
use RestApi\Lib\Oauth\AccessTokenEntity;
use RestApi\Model\Table\OauthAccessTokensTable;
use RestApi\Model\Table\RestApiTable;

class OauthHelper
{
    /** @var Server */
    private $server;
    /** @var Request */
    public $request;
    /** @var Response */
    public $response;
    private AccessTokenEntity $_lastToken;
    private $_storage;
    private $_serverConfig = ['enforce_state' => true, 'allow_implicit' => true];

    public function __construct(OauthAccessTokensTable $storage)
    {
        $this->_storage = $storage;
    }

    public function setupOauth(Controller $controller)
    {
        Autoloader::register();
        // create array of supported grant types
        $grantTypes = [
            'authorization_code' => new AuthorizationCode($this->_storage),
            'user_credentials' => new UserCredentials($this->_storage),// password
        ];

        unset($_GET['access_token']);
        // add the server to the silex "container" so we can use it
        $this->request = Request::createFromGlobals();

        $authorization = $controller->getRequest()->getEnv('HTTP_AUTHORIZATION');
        if ($authorization) {
            $this->request->headers['AUTHORIZATION'] = explode(',', $authorization)[0];
        }
        $this->response = new Response();
        $allowOrigin = $controller->getResponse()->getHeader('Access-Control-Allow-Origin');
        if (isset($allowOrigin[0])) {
            $this->response->setHttpHeader('Access-Control-Allow-Origin', $allowOrigin[0]);
        }

        // instantiate the oauth server
        $this->server = new Server($this->_storage, $this->_serverConfig, $grantTypes);
    }

    public function verifyAuthorization(string $silentPath = '/api/me')
    {
        $isAuthorized = $this->server->verifyResourceRequest($this->request, $this->response);
        if (!$isAuthorized) {
            $err = 'Verify authorization error: ' .
                $this->response->getParameter('error_description');
            $code = $this->response->getStatusCode();
            if (($_SERVER['REQUEST_URI'] ?? '') === '/api/v2/me') {
                throw new SilentException($err, $code);
            } else {
                throw new InternalErrorException($err, $code);
            }
        }
        $token = $this->server->getAccessTokenData($this->request);
        $this->_lastToken = new AccessTokenEntity($token);
        $this->_lastToken->storeInfoForLogs();
        return $token['user_id'] ?? '';
    }

    public function getLastToken(): AccessTokenEntity
    {
        return $this->_lastToken;
    }

    public function getServer(): Server
    {
        return $this->server;
    }
    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function getUserModel()
    {
        /** @var RestApiTable $table */
        $table = $this->_storage->Users->getTarget();
        return $table;
    }
}
