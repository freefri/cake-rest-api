<?php

namespace RestApi\Lib\Helpers;

use Cake\Controller\Controller;
use OAuth2\Autoloader;
use OAuth2\GrantType\AuthorizationCode;
use OAuth2\GrantType\UserCredentials;
use OAuth2\Request;
use OAuth2\Response;
use OAuth2\Server;
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
